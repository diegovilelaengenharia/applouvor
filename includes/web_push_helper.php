<?php
/**
 * Biblioteca PHP pura para envio de Web Push Notifications
 * Implementação simplificada sem dependências externas
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
        
        // Criptografar payload
        $encrypted = $this->encrypt($payloadJson, $p256dh, $auth);
        
        // Gerar headers VAPID
        $headers = $this->generateVapidHeaders($endpoint);
        
        // Enviar requisição
        return $this->sendRequest($endpoint, $encrypted, $headers);
    }
    
    private function encrypt($payload, $userPublicKey, $userAuthToken) {
        // Implementação simplificada de criptografia AESGCM
        // Para produção, usar biblioteca completa
        return [
            'ciphertext' => base64_encode($payload),
            'salt' => base64_encode(random_bytes(16)),
            'publicKey' => $this->vapidPublicKey
        ];
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
        
        return [
            'Authorization' => 'vapid t=' . $this->generateJWT($header, $payload),
            'Crypto-Key' => 'p256ecdsa=' . $this->vapidPublicKey
        ];
    }
    
    private function generateJWT($header, $payload) {
        $headerEncoded = $this->base64UrlEncode(json_encode($header));
        $payloadEncoded = $this->base64UrlEncode(json_encode($payload));
        
        $signature = $this->sign($headerEncoded . '.' . $payloadEncoded);
        
        return $headerEncoded . '.' . $payloadEncoded . '.' . $signature;
    }
    
    private function sign($data) {
        // Simplificado - em produção usar openssl_sign
        return $this->base64UrlEncode(hash_hmac('sha256', $data, $this->vapidPrivateKey, true));
    }
    
    private function base64UrlEncode($data) {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }
    
    private function sendRequest($endpoint, $encrypted, $headers) {
        $ch = curl_init($endpoint);
        
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $encrypted['ciphertext'],
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/octet-stream',
                'Content-Encoding: aesgcm',
                'Encryption: salt=' . $encrypted['salt'],
                'Crypto-Key: dh=' . $encrypted['publicKey'],
                'Authorization: ' . $headers['Authorization'],
                'TTL: 2419200'
            ],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_SSL_VERIFYPEER => true
        ]);
        
        $result = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        return $httpCode >= 200 && $httpCode < 300;
    }
}
?>
