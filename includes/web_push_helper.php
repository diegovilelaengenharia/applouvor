<?php
/**
 * Biblioteca PHP pura para envio de Web Push Notifications
 * Implementação com AESGCM real usando OpenSSL (P-256 ECDH + HKDF + AES-128-GCM)
 * e assinaturas VAPID (ES256)
 */

class WebPushHelper {
    private $vapidPublicKey;
    private $vapidPrivateKey;
    private $vapidSubject;

    public function __construct($publicKey, $privateKey, $subject = 'mailto:contato@pibolveira.com') {
        $this->vapidPublicKey = $publicKey;
        $this->vapidPrivateKey = $privateKey;
        $this->vapidSubject = $subject;
    }

    /**
     * Enviar notificação push
     */
    public function sendNotification($subscription, $payload) {
        $endpoint = $subscription['endpoint'];
        $p256dh = $subscription['p256dh'];
        $auth = $subscription['auth'];

        // Preparar payload
        $payloadJson = json_encode($payload);

        // Criptografar payload com AESGCM real
        $encrypted = $this->encrypt($payloadJson, $p256dh, $auth);
        // Fallback D-02: se encrypt falhar, ainda tenta enviar sem payload
        // (notificação genérica sem título/corpo, mas entregue)
        if ($encrypted === null) {
            error_log('WebPush: AESGCM falhou, enviando sem payload para ' . $endpoint);
            $encrypted = ['ciphertext' => '', 'salt' => '', 'localPublicKey' => ''];
        }

        // Gerar headers VAPID
        $headers = $this->generateVapidHeaders($endpoint);

        // Enviar requisição
        return $this->sendRequest($endpoint, $encrypted, $headers);
    }

    private function encrypt($payload, $userPublicKeyBase64, $userAuthTokenBase64) {
        // Decodificar chaves do subscriber
        $userPublicKey = base64_decode(strtr($userPublicKeyBase64, '-_', '+/'));
        $userAuthToken = base64_decode(strtr($userAuthTokenBase64, '-_', '+/'));

        // Gerar salt aleatório de 16 bytes
        $salt = random_bytes(16);

        // Gerar par de chaves ECDH efêmero (P-256)
        $localKeyPair = openssl_pkey_new([
            'curve_name' => 'prime256v1',
            'private_key_type' => OPENSSL_KEYTYPE_EC,
        ]);
        if (!$localKeyPair) {
            error_log('WebPush: openssl_pkey_new falhou: ' . openssl_error_string());
            return null;
        }

        // Extrair chave pública efêmera no formato uncompressed (0x04 + X + Y = 65 bytes)
        $localKeyDetails = openssl_pkey_get_details($localKeyPair);
        $localPublicKeyRaw = "\x04" . str_pad($localKeyDetails['ec']['x'], 32, "\x00", STR_PAD_LEFT)
                                     . str_pad($localKeyDetails['ec']['y'], 32, "\x00", STR_PAD_LEFT);

        // Carregar chave pública do subscriber (formato uncompressed P-256)
        $pem = $this->rawPublicKeyToPem($userPublicKey);
        $subscriberKey = openssl_pkey_get_public($pem);
        if (!$subscriberKey) {
            error_log('WebPush: falha ao carregar chave pública do subscriber: ' . openssl_error_string());
            return null;
        }

        // Shared secret via ECDH
        $sharedSecret = '';
        if (!openssl_pkey_derive($sharedSecret, $localKeyPair, $subscriberKey)) {
            error_log('WebPush: ECDH derive falhou: ' . openssl_error_string());
            return null;
        }

        // HKDF para derivar chave de criptografia e nonce
        // PRK = HMAC-SHA256(auth, sharedSecret)
        $prk = $this->hkdfExtract($userAuthToken, $sharedSecret);

        // CEK (Content Encryption Key) 16 bytes
        $cekInfo = "Content-Encoding: aesgcm\x00";
        $cek = $this->hkdfExpand($prk, $salt . $localPublicKeyRaw . $userPublicKey . $cekInfo, 16);

        // NONCE 12 bytes
        $nonceInfo = "Content-Encoding: nonce\x00";
        $nonce = $this->hkdfExpand($prk, $salt . $localPublicKeyRaw . $userPublicKey . $nonceInfo, 12);

        // Padding: 2 bytes de padding length (0) + payload
        $paddedPayload = "\x00\x00" . $payload;

        // Criptografar com AES-128-GCM
        $tag = '';
        $ciphertext = openssl_encrypt(
            $paddedPayload,
            'aes-128-gcm',
            $cek,
            OPENSSL_RAW_DATA,
            $nonce,
            $tag
        );

        if ($ciphertext === false) {
            error_log('WebPush: openssl_encrypt falhou: ' . openssl_error_string());
            return null;
        }

        return [
            'ciphertext'     => $ciphertext . $tag,
            'salt'           => base64_encode($salt),
            'localPublicKey' => base64_encode($localPublicKeyRaw),
        ];
    }

    private function rawPublicKeyToPem($rawKey) {
        // Encode P-256 public key (65 bytes uncompressed) para PEM SubjectPublicKeyInfo
        $header = "\x30\x59\x30\x13\x06\x07\x2a\x86\x48\xce\x3d\x02\x01"
                . "\x06\x08\x2a\x86\x48\xce\x3d\x03\x01\x07\x03\x42\x00";
        $der = $header . $rawKey;
        return "-----BEGIN PUBLIC KEY-----\n"
             . chunk_split(base64_encode($der), 64, "\n")
             . "-----END PUBLIC KEY-----\n";
    }

    private function hkdfExtract($salt, $inputKeyingMaterial) {
        return hash_hmac('sha256', $inputKeyingMaterial, $salt, true);
    }

    private function hkdfExpand($prk, $info, $length) {
        $output = '';
        $t = '';
        $counter = 1;
        while (strlen($output) < $length) {
            $t = hash_hmac('sha256', $t . $info . chr($counter), $prk, true);
            $output .= $t;
            $counter++;
        }
        return substr($output, 0, $length);
    }

    private function generateVapidHeaders($endpoint) {
        $url = parse_url($endpoint);
        $audience = $url['scheme'] . '://' . $url['host'];

        $header = [
            'typ' => 'JWT',
            'alg' => 'ES256'
        ];

        $payload = [
            'aud' => $audience,
            'exp' => time() + 43200, // 12 horas
            'sub' => $this->vapidSubject
        ];

        $jwt = $this->generateJWT($header, $payload);

        return [
            'Authorization' => 'vapid t=' . $jwt . '; k=' . $this->vapidPublicKey,
        ];
    }

    private function generateJWT($header, $payload) {
        $headerEncoded = $this->base64UrlEncode(json_encode($header));
        $payloadEncoded = $this->base64UrlEncode(json_encode($payload));

        $data = $headerEncoded . '.' . $payloadEncoded;
        $signature = $this->sign($data);

        return $data . '.' . $signature;
    }

    private function sign($data) {
        // Correct VAPID signature using ECDSA P-256 (ES256)
        $signature = '';
        // $this->vapidPrivateKey must be a PEM string
        $success = openssl_sign($data, $signature, $this->vapidPrivateKey, OPENSSL_ALGO_SHA256);

        if (!$success) {
            error_log('OpenSSL Sign Failed: ' . openssl_error_string());
            return '';
        }

        // OpenSSL returns DER format, but Web Push requires Raw (R|S)
        $rawSignature = $this->signatureDerToRaw($signature);

        return $this->base64UrlEncode($rawSignature);
    }

    private function signatureDerToRaw($signature) {
        // Parse DER signature to extract R and S components
        // Sequence (0x30) + Length + Integer (0x02) + Length + R + Integer (0x02) + Length + S

        $len = strlen($signature);
        if ($len < 8) return $signature; // Error or unexpected

        // Skip Sequence Tag & Length
        $pos = 2; // Usually 0x30, 0x44 (or similar)

        // R
        if (ord($signature[$pos]) != 0x02) return $signature;
        $pos++;
        $rLen = ord($signature[$pos]);
        $pos++;
        $r = substr($signature, $pos, $rLen);
        $pos += $rLen;

        // S
        if (ord($signature[$pos]) != 0x02) return $signature;
        $pos++;
        $sLen = ord($signature[$pos]);
        $pos++;
        $s = substr($signature, $pos, $sLen);

        // Pad or trim to 32 bytes
        $r = $this->adjustBigInt($r);
        $s = $this->adjustBigInt($s);

        return $r . $s;
    }

    private function adjustBigInt($bin) {
        // Remove leading zero if present (DER adds 0x00 if MSB is 1 to indicate positive)
        if (ord($bin[0]) == 0x00 && strlen($bin) > 32) {
             $bin = substr($bin, 1);
        }

        // Pad left with zeros if shorter than 32
        while (strlen($bin) < 32) {
             $bin = chr(0) . $bin;
        }

        return $bin;
    }

    private function base64UrlEncode($data) {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    private function sendRequest($endpoint, $encrypted, $headers) {
        $ch = curl_init($endpoint);

        $headersList = [
            'Authorization: ' . $headers['Authorization'],
            'TTL: 2419200',
        ];

        $postBody = null;

        if ($encrypted !== null && !empty($encrypted['ciphertext'])) {
            $headersList[] = 'Content-Type: application/octet-stream';
            $headersList[] = 'Content-Encoding: aesgcm';
            $headersList[] = 'Encryption: salt=' . $encrypted['salt'];
            $headersList[] = 'Crypto-Key: dh=' . rtrim(strtr(base64_encode(base64_decode($encrypted['localPublicKey'])), '+/', '-_'), '=')
                            . ';p256ecdsa=' . $this->vapidPublicKey;
            $postBody = $encrypted['ciphertext'];
        }

        curl_setopt_array($ch, [
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => $postBody,
            CURLOPT_HTTPHEADER     => $headersList,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_SSL_VERIFYPEER => true,
        ]);

        $result   = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode >= 400) {
            error_log("WebPush Error $httpCode for $endpoint: $result");
            return false;
        }

        return $httpCode >= 200 && $httpCode < 300;
    }
}
?>
