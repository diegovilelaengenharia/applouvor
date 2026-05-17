---
phase: 02-confirmar-escala
plan: 02D
type: execute
wave: 3
depends_on: [02A, 02B, 02C]
files_modified:
  - includes/config.php
  - includes/web_push_helper.php
  - api/send_reminders.php
  - admin/escala_detalhe.php
  - admin/index.php
autonomous: true
requirements:
  - ESC-05

must_haves:
  truths:
    - "includes/config.php define VAPID_PUBLIC_KEY e VAPID_PRIVATE_KEY e VAPID_SUBJECT"
    - "includes/web_push_helper.php metodo encrypt() implementa aes128gcm real (RFC 8291) com hash_hkdf + openssl_pkey_derive + openssl_encrypt AES-128-GCM"
    - "api/send_reminders.php existe, requer admin, aceita POST schedule_id, envia push para status=pending"
    - "admin/escala_detalhe.php dispara push de convocacao apos $pdo->commit() em save_changes (apenas para membros novos)"
    - "admin/index.php exibe secao com escalas nos proximos 2 dias e botao Lembrar quem nao confirmou"
  artifacts:
    - path: "api/send_reminders.php"
      provides: "Endpoint de envio de lembretes push"
    - path: "includes/web_push_helper.php"
      provides: "AESGCM real (RFC 8291)"
  key_links:
    - from: "admin/index.php botao Lembrar"
      to: "api/send_reminders.php"
      via: "fetch POST {schedule_id}"
      pattern: "send_reminders"
    - from: "admin/escala_detalhe.php save_changes"
      to: "WebPushHelper::sendNotification"
      via: "push de convocacao pos-commit"
      pattern: "sendNotification|WebPushHelper"
---

<objective>
Implementar push notifications server-side para convocacao e lembretes.

Prioridade de valor (implementar nesta ordem):
1. Botao "Lembrar quem nao confirmou" no dashboard (valor garantido mesmo sem crypto funcionar)
2. AESGCM real em web_push_helper.php (RFC 8291 — aes128gcm)
3. Trigger automatico de push ao salvar escala (convocacao de novos membros)

Se o AESGCM falhar nos testes, o botao manual DEVE funcionar de qualquer forma (D-02 fallback garantido).

Output: Lider pode notificar musicos pendentes com um clique; novos membros sao notificados automaticamente ao salvar a escala (ESC-05).
</objective>

<context>
@.planning/phases/02-confirmar-escala/02-CONTEXT.md
</context>

<tasks>

<task type="auto">
  <name>Task 1: Adicionar constantes VAPID em includes/config.php</name>
  <files>
    includes/config.php
  </files>
  <read_first>
    Ler `includes/config.php` completo.
    Verificar se VAPID_PUBLIC_KEY, VAPID_PRIVATE_KEY ou VAPID_SUBJECT ja estao definidos.
    Se ja existirem, NAO duplicar.
    Localizar onde termina o bloco if/else de producao/local (~linha 62) para adicionar apos ele.
    As chaves VAPID estao em `maintenance/vapid_config.php` (gitignored — nao ler esse arquivo).
    Elas devem ser carregadas de variaveis de ambiente ou do .env.
  </read_first>
  <action>
    Adicionar APOS o fechamento do bloco if ($isProduction) / else (apos a linha `define('APP_URL'...)`
    e ANTES do bloco de INFORMACOES DA IGREJA):

    ```php
    // ======================================
    // VAPID — Web Push Notifications
    // ======================================
    // Chaves geradas em maintenance/generate_vapid.php (gitignored)
    // Local: adicionar ao .env: VAPID_PUBLIC_KEY=... VAPID_PRIVATE_KEY=... (PEM)
    // Producao: configurar no .htaccess do Hostinger
    define('VAPID_PUBLIC_KEY',  App\DotEnv::get('VAPID_PUBLIC_KEY',  getenv('VAPID_PUBLIC_KEY')  ?: ''));
    define('VAPID_PRIVATE_KEY', App\DotEnv::get('VAPID_PRIVATE_KEY', getenv('VAPID_PRIVATE_KEY') ?: ''));
    define('VAPID_SUBJECT',     App\DotEnv::get('VAPID_SUBJECT',     getenv('VAPID_SUBJECT')     ?: 'mailto:diegonunesvilela@gmail.com'));
    ```

    NOTA: App\DotEnv::get() aceita dois argumentos (key, default). O segundo `getenv(...)` e um
    fallback extra para quando App\DotEnv nao carregou o .env (producao sem .env).
    Simplificar se necessario: se App\DotEnv::get nao aceitar dois args com fallback, usar:
    ```php
    define('VAPID_PUBLIC_KEY',  getenv('VAPID_PUBLIC_KEY')  ?: (App\DotEnv::get('VAPID_PUBLIC_KEY')  ?: ''));
    define('VAPID_PRIVATE_KEY', getenv('VAPID_PRIVATE_KEY') ?: (App\DotEnv::get('VAPID_PRIVATE_KEY') ?: ''));
    define('VAPID_SUBJECT',     getenv('VAPID_SUBJECT')     ?: (App\DotEnv::get('VAPID_SUBJECT')     ?: 'mailto:diegonunesvilela@gmail.com'));
    ```
  </action>
  <acceptance_criteria>
    - `includes/config.php` contem `define('VAPID_PUBLIC_KEY'`
    - `includes/config.php` contem `define('VAPID_PRIVATE_KEY'`
    - `includes/config.php` contem `define('VAPID_SUBJECT'`
    - Arquivo carrega sem erros PHP: `php -l includes/config.php` retorna "No syntax errors"
  </acceptance_criteria>
  <done>Constantes VAPID adicionadas em config.php.</done>
</task>

<task type="auto">
  <name>Task 2: Implementar AESGCM real (RFC 8291 aes128gcm) em web_push_helper.php</name>
  <files>
    includes/web_push_helper.php
  </files>
  <read_first>
    Ler `includes/web_push_helper.php` completo.
    Localizar o metodo `encrypt()` (~linha 58) que atualmente e um MOCK (retorna ciphertext vazio).
    Localizar o metodo `sendRequest()` (~linha 175) para entender os headers atuais.
    Verificar PHP version: o projeto usa PHP 8+, logo hash_hkdf() e openssl_pkey_derive() estao disponiveis.
  </read_first>
  <action>
    SUBSTITUICAO COMPLETA da classe WebPushHelper.

    Substituir o conteudo inteiro de `includes/web_push_helper.php` por:

    ```php
    <?php
    /**
     * Web Push Notifications — RFC 8291 (aes128gcm)
     * PHP puro, sem Composer. Requer PHP 8+ com OpenSSL e ext-curl.
     */
    class WebPushHelper {
        private string $vapidPublicKey;
        private string $vapidPrivateKey;
        private string $vapidSubject;

        public function __construct(string $publicKey, string $privateKey, string $subject = 'mailto:contato@pibolveira.com') {
            $this->vapidPublicKey  = $publicKey;
            $this->vapidPrivateKey = $privateKey;
            $this->vapidSubject    = $subject;
        }

        /**
         * Envia notificacao push para uma subscription.
         * $subscription = ['endpoint' => '...', 'p256dh' => '...', 'auth' => '...']
         * $payload      = ['title' => '...', 'body' => '...', 'icon' => '...', 'url' => '...']
         */
        public function sendNotification(array $subscription, array $payload): bool {
            if (empty($subscription['endpoint']) || empty(VAPID_PUBLIC_KEY) || empty(VAPID_PRIVATE_KEY)) {
                error_log('WebPush: endpoint ou chaves VAPID vazias');
                return false;
            }

            $payloadJson = json_encode($payload, JSON_UNESCAPED_UNICODE);

            try {
                $encrypted = $this->encrypt($payloadJson, $subscription['p256dh'], $subscription['auth']);
            } catch (\Throwable $e) {
                error_log('WebPush encrypt error: ' . $e->getMessage());
                return false;
            }

            $vapidHeader = $this->buildVapidAuthHeader($subscription['endpoint']);
            return $this->sendRequest($subscription['endpoint'], $encrypted, $vapidHeader);
        }

        // ---- Criptografia RFC 8291 (aes128gcm) ----

        private function encrypt(string $payload, string $p256dhBase64, string $authBase64): array {
            // Decodificar chaves do subscriber
            $receiverKey  = $this->base64UrlDecode($p256dhBase64);  // 65 bytes (04 || x || y)
            $authSecret   = $this->base64UrlDecode($authBase64);    // 16 bytes

            // Gerar par de chaves efemero EC P-256
            $localKeyRes = openssl_pkey_new(['curve_name' => 'prime256v1', 'private_key_type' => OPENSSL_KEYTYPE_EC]);
            if (!$localKeyRes) throw new \RuntimeException('openssl_pkey_new falhou: ' . openssl_error_string());

            $localDetails   = openssl_pkey_get_details($localKeyRes);
            $localPublicRaw = "\x04"
                . str_pad($localDetails['ec']['x'], 32, "\x00", STR_PAD_LEFT)
                . str_pad($localDetails['ec']['y'], 32, "\x00", STR_PAD_LEFT); // 65 bytes

            // Carregar chave publica do subscriber como PEM para ECDH
            $derPrefix = "\x30\x59\x30\x13\x06\x07\x2a\x86\x48\xce\x3d\x02\x01"
                       . "\x06\x08\x2a\x86\x48\xce\x3d\x03\x01\x07\x03\x42\x00";
            $receiverPem = "-----BEGIN PUBLIC KEY-----\n"
                         . chunk_split(base64_encode($derPrefix . $receiverKey), 64, "\n")
                         . "-----END PUBLIC KEY-----\n";
            $receiverKeyRes = openssl_pkey_get_public($receiverPem);
            if (!$receiverKeyRes) throw new \RuntimeException('Nao foi possivel carregar chave do subscriber');

            // ECDH: derivar segredo compartilhado
            $sharedSecret = openssl_pkey_derive($receiverKeyRes, $localKeyRes);
            if ($sharedSecret === false) throw new \RuntimeException('openssl_pkey_derive falhou: ' . openssl_error_string());

            // HKDF (RFC 8291 secao 3.3)
            // Passo 1: PRK = HKDF-Extract(auth_secret, shared_secret) com info = "WebPush: info\x00" || recv_key || sender_key
            $authInfo = "WebPush: info\x00" . $receiverKey . $localPublicRaw;
            $ikm = hash_hkdf('sha256', $sharedSecret, 32, $authInfo, $authSecret);

            // Passo 2: salt aleatorio de 16 bytes
            $salt = random_bytes(16);

            // Passo 3: derivar chave de conteudo e nonce
            $contentKey = hash_hkdf('sha256', $ikm, 16, "Content-Encoding: aes128gcm\x00", $salt);
            $nonce      = hash_hkdf('sha256', $ikm, 12, "Content-Encoding: nonce\x00",      $salt);

            // Pad: adicionar byte 0x02 no final do payload (delimiter RFC 8291)
            $paddedPayload = $payload . "\x02";

            // Cifrar com AES-128-GCM
            $tag        = '';
            $ciphertext = openssl_encrypt($paddedPayload, 'aes-128-gcm', $contentKey, OPENSSL_RAW_DATA, $nonce, $tag, '', 16);
            if ($ciphertext === false) throw new \RuntimeException('openssl_encrypt falhou: ' . openssl_error_string());

            // Header binario RFC 8291: salt(16) + rs(4 big-endian) + idlen(1) + sender_key(65)
            $recordSize = pack('N', 4096);
            $keyIdLen   = chr(65);
            $header     = $salt . $recordSize . $keyIdLen . $localPublicRaw;

            return [
                'body'    => $header . $ciphertext . $tag,
                'salt_b64' => $this->base64UrlEncode($salt),
            ];
        }

        // ---- VAPID JWT (ES256) ----

        private function buildVapidAuthHeader(string $endpoint): string {
            $url      = parse_url($endpoint);
            $audience = $url['scheme'] . '://' . $url['host'];
            $header   = ['typ' => 'JWT', 'alg' => 'ES256'];
            $payload  = ['aud' => $audience, 'exp' => time() + 43200, 'sub' => $this->vapidSubject];
            $jwt      = $this->generateJWT($header, $payload);
            return 'vapid t=' . $jwt . ', k=' . $this->vapidPublicKey;
        }

        private function generateJWT(array $header, array $payload): string {
            $data      = $this->base64UrlEncode(json_encode($header)) . '.' . $this->base64UrlEncode(json_encode($payload));
            $signature = '';
            openssl_sign($data, $signature, $this->vapidPrivateKey, OPENSSL_ALGO_SHA256);
            return $data . '.' . $this->base64UrlEncode($this->derSignatureToRaw($signature));
        }

        private function derSignatureToRaw(string $sig): string {
            $pos = 2;
            if (ord($sig[$pos]) !== 0x02) return $sig;
            $pos++; $rLen = ord($sig[$pos]); $pos++;
            $r = substr($sig, $pos, $rLen); $pos += $rLen;
            if (ord($sig[$pos]) !== 0x02) return $sig;
            $pos++; $sLen = ord($sig[$pos]); $pos++;
            $s = substr($sig, $pos, $sLen);
            return $this->padOrTrim($r) . $this->padOrTrim($s);
        }

        private function padOrTrim(string $bin): string {
            if (ord($bin[0]) === 0x00 && strlen($bin) > 32) $bin = substr($bin, 1);
            return str_pad($bin, 32, "\x00", STR_PAD_LEFT);
        }

        // ---- HTTP Request ----

        private function sendRequest(string $endpoint, array $encrypted, string $vapidAuth): bool {
            $body = $encrypted['body'];
            $ch   = curl_init($endpoint);
            curl_setopt_array($ch, [
                CURLOPT_POST           => true,
                CURLOPT_POSTFIELDS     => $body,
                CURLOPT_HTTPHEADER     => [
                    'Authorization: ' . $vapidAuth,
                    'Content-Type: application/octet-stream',
                    'Content-Encoding: aes128gcm',
                    'Content-Length: ' . strlen($body),
                    'TTL: 2419200',
                ],
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_SSL_VERIFYPEER => true,
            ]);
            $result   = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            if ($httpCode >= 400) {
                error_log("WebPush HTTP $httpCode: $result | endpoint: $endpoint");
            }
            return $httpCode >= 200 && $httpCode < 300;
        }

        // ---- Helpers ----

        private function base64UrlEncode(string $data): string {
            return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
        }

        private function base64UrlDecode(string $data): string {
            return base64_decode(strtr($data, '-_', '+/') . str_repeat('=', (4 - strlen($data) % 4) % 4));
        }
    }
    ```
  </action>
  <acceptance_criteria>
    - `includes/web_push_helper.php` contem `hash_hkdf('sha256'`
    - `includes/web_push_helper.php` contem `openssl_pkey_derive`
    - `includes/web_push_helper.php` contem `openssl_encrypt($paddedPayload, 'aes-128-gcm'`
    - `includes/web_push_helper.php` contem `Content-Encoding: aes128gcm`
    - `includes/web_push_helper.php` contem `WebPush: info\x00`
    - `includes/web_push_helper.php` contem `aes128gcm\x00`
    - `php -l includes/web_push_helper.php` retorna "No syntax errors"
    - O metodo encrypt() NAO retorna mais `['ciphertext' => '']` (mock removido)
  </acceptance_criteria>
  <done>AESGCM real implementado em web_push_helper.php.</done>
</task>

<task type="auto">
  <name>Task 3: Criar api/send_reminders.php (endpoint de lembretes manuais)</name>
  <files>
    api/send_reminders.php
  </files>
  <read_first>
    Ler `api/confirm_scale.php` para entender o padrao do projeto de endpoints API.
    Ler `api/push_subscription.php` para entender como as subscriptions sao armazenadas.
    Confirmar estrutura da tabela push_subscriptions: (id, user_id, endpoint, p256dh, auth, created_at).
    Confirmar que `includes/config.php` e requerido antes de `includes/web_push_helper.php`.
  </read_first>
  <action>
    Criar `api/send_reminders.php` com o seguinte conteudo:

    ```php
    <?php
    // api/send_reminders.php — Envia lembretes push para musicos pendentes de uma escala
    header('Content-Type: application/json');
    require_once '../includes/auth.php';
    require_once '../includes/db.php';
    require_once '../includes/config.php';
    require_once '../includes/web_push_helper.php';

    checkAdmin(); // Apenas admin pode enviar lembretes

    $scheduleId = (int)($_POST['schedule_id'] ?? 0);
    if (!$scheduleId) {
        echo json_encode(['success' => false, 'message' => 'schedule_id obrigatorio']);
        exit;
    }

    // Buscar dados da escala
    $stmt = $pdo->prepare("SELECT event_type, event_date FROM schedules WHERE id = ?");
    $stmt->execute([$scheduleId]);
    $schedule = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$schedule) {
        echo json_encode(['success' => false, 'message' => 'Escala nao encontrada']);
        exit;
    }

    // Buscar subscriptions de participantes com status = 'pending'
    $stmt = $pdo->prepare("
        SELECT ps.endpoint, ps.p256dh, ps.auth
        FROM push_subscriptions ps
        INNER JOIN schedule_users su ON ps.user_id = su.user_id
        WHERE su.schedule_id = ? AND su.status = 'pending'
    ");
    $stmt->execute([$scheduleId]);
    $subscriptions = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (empty($subscriptions)) {
        echo json_encode(['success' => true, 'sent' => 0, 'message' => 'Nenhum musico pendente com notificacoes ativas']);
        exit;
    }

    if (empty(VAPID_PUBLIC_KEY) || empty(VAPID_PRIVATE_KEY)) {
        echo json_encode(['success' => false, 'message' => 'Chaves VAPID nao configuradas no servidor']);
        exit;
    }

    $webPush = new WebPushHelper(VAPID_PUBLIC_KEY, VAPID_PRIVATE_KEY, VAPID_SUBJECT);
    $dateFormatted = date('d/m', strtotime($schedule['event_date']));

    $payload = [
        'title' => 'Lembrete: ' . $schedule['event_type'],
        'body'  => 'Escala em ' . $dateFormatted . '. Confirme sua presenca no app.',
        'icon'  => '/applouvor/assets/icons/icon-192.png',
        'url'   => '/applouvor/admin/escala_detalhe.php?id=' . $scheduleId,
    ];

    $sent   = 0;
    $failed = 0;
    foreach ($subscriptions as $sub) {
        if ($webPush->sendNotification($sub, $payload)) {
            $sent++;
        } else {
            $failed++;
        }
    }

    echo json_encode([
        'success' => true,
        'sent'    => $sent,
        'failed'  => $failed,
        'total'   => count($subscriptions),
        'message' => "$sent notificacoes enviadas de " . count($subscriptions) . " pendentes",
    ]);
    ```
  </action>
  <acceptance_criteria>
    - `api/send_reminders.php` existe
    - `api/send_reminders.php` contem `checkAdmin()`
    - `api/send_reminders.php` contem `status = 'pending'`
    - `api/send_reminders.php` contem `WebPushHelper(`
    - `api/send_reminders.php` contem `sendNotification(`
    - `api/send_reminders.php` contem `VAPID_PUBLIC_KEY`
    - `php -l api/send_reminders.php` retorna "No syntax errors"
  </acceptance_criteria>
  <done>api/send_reminders.php criado com envio de lembretes para participantes pendentes.</done>
</task>

<task type="auto">
  <name>Task 4: Disparar push de convocacao ao salvar escala (admin/escala_detalhe.php)</name>
  <files>
    admin/escala_detalhe.php
  </files>
  <read_first>
    Ler `admin/escala_detalhe.php` e localizar o bloco `save_changes` (~linha 79).
    Localizar exatamente:
    1. O trecho que deleta e re-insere schedule_users (~linha 88-99)
    2. A linha `$pdo->commit();` (~linha 108) — push deve ser disparado APOS este commit
    3. A linha `header("Location: escala_detalhe.php?id=$id&success=1");` (~linha 109) — push deve ser ANTES do redirect

    Estrategia: capturar IDs antigos antes do DELETE, capturar novos IDs apos o INSERT,
    e disparar push apenas para os IDs que sao NOVOS (nao estavam antes).
    Isso evita spam para membros que ja estavam escalados.
  </read_first>
  <action>
    MODIFICACAO 1 — Capturar membros existentes ANTES do DELETE (inserir logo antes da linha `$pdo->beginTransaction()`):

    ```php
    // Capturar membros atuais para comparar depois do save (evitar push para quem ja estava)
    $existingMemberIds = [];
    if (isset($_POST['save_changes']) && $_SESSION['user_role'] === 'admin') {
        $stmtExisting = $pdo->prepare("SELECT user_id FROM schedule_users WHERE schedule_id = ?");
        $stmtExisting->execute([$id]);
        $existingMemberIds = $stmtExisting->fetchAll(PDO::FETCH_COLUMN);
    }
    ```

    ATENCAO: Esta query deve ser executada ANTES do beginTransaction() existente.
    Inserir como o primeiro bloco dentro do `if (isset($_POST['save_changes'])...)`:

    Localizar:
    ```php
    if (isset($_POST['save_changes']) && $_SESSION['user_role'] === 'admin') {
        try {
            $pdo->beginTransaction();
    ```

    Substituir por:
    ```php
    if (isset($_POST['save_changes']) && $_SESSION['user_role'] === 'admin') {
        // Capturar membros existentes antes do save para push apenas para novos membros
        $stmtExisting = $pdo->prepare("SELECT user_id FROM schedule_users WHERE schedule_id = ?");
        $stmtExisting->execute([$id]);
        $existingMemberIds = array_map('intval', $stmtExisting->fetchAll(PDO::FETCH_COLUMN));

        try {
            $pdo->beginTransaction();
    ```

    MODIFICACAO 2 — Disparar push APOS `$pdo->commit()`, ANTES do redirect.

    Localizar:
    ```php
            $pdo->commit();
            header("Location: escala_detalhe.php?id=$id&success=1");
            exit;
    ```

    Substituir por:
    ```php
            $pdo->commit();

            // Disparar push de convocacao para novos membros (D-03)
            if (!empty($_POST['members'])) {
                $newMemberIds = array_filter(array_keys($_POST['members']), 'is_numeric');
                $newMemberIds = array_map('intval', $newMemberIds);
                $membersToNotify = array_diff($newMemberIds, $existingMemberIds);

                if (!empty($membersToNotify) && !empty(VAPID_PUBLIC_KEY) && !empty(VAPID_PRIVATE_KEY)) {
                    require_once '../includes/web_push_helper.php';
                    $inPlaceholders = implode(',', array_fill(0, count($membersToNotify), '?'));
                    $stmtSubs = $pdo->prepare("
                        SELECT endpoint, p256dh, auth
                        FROM push_subscriptions
                        WHERE user_id IN ($inPlaceholders)
                    ");
                    $stmtSubs->execute(array_values($membersToNotify));
                    $subscriptions = $stmtSubs->fetchAll(PDO::FETCH_ASSOC);

                    if (!empty($subscriptions)) {
                        $stmtSched = $pdo->prepare("SELECT event_type, event_date FROM schedules WHERE id = ?");
                        $stmtSched->execute([$id]);
                        $schedInfo = $stmtSched->fetch(PDO::FETCH_ASSOC);
                        $dateFormatted = date('d/m', strtotime($schedInfo['event_date']));

                        $webPush = new WebPushHelper(VAPID_PUBLIC_KEY, VAPID_PRIVATE_KEY, VAPID_SUBJECT);
                        $payload = [
                            'title' => 'Nova escala: ' . $schedInfo['event_type'],
                            'body'  => 'Voce foi escalado para ' . $dateFormatted . '. Confirme sua presenca.',
                            'icon'  => '/applouvor/assets/icons/icon-192.png',
                            'url'   => '/applouvor/admin/escala_detalhe.php?id=' . $id,
                        ];
                        foreach ($subscriptions as $sub) {
                            $webPush->sendNotification($sub, $payload);
                        }
                    }
                }
            }

            header("Location: escala_detalhe.php?id=$id&success=1");
            exit;
    ```
  </action>
  <acceptance_criteria>
    - `admin/escala_detalhe.php` contem `$existingMemberIds`
    - `admin/escala_detalhe.php` contem `array_diff($newMemberIds, $existingMemberIds)`
    - `admin/escala_detalhe.php` contem `push de convocacao para novos membros`
    - `admin/escala_detalhe.php` contem `WebPushHelper(VAPID_PUBLIC_KEY`
    - O push e disparado APOS `$pdo->commit()` e ANTES do `header("Location:`
    - `php -l admin/escala_detalhe.php` retorna "No syntax errors"
  </acceptance_criteria>
  <done>Push de convocacao disparado automaticamente para novos membros ao salvar escala.</done>
</task>

<task type="auto">
  <name>Task 5: Botao "Lembrar quem nao confirmou" no dashboard admin/index.php</name>
  <files>
    admin/index.php
  </files>
  <read_first>
    Ler `admin/index.php` completo.
    Localizar a secao do dashboard do lider (admin) — onde sao exibidas escalas proximas ou estatisticas.
    Encontrar um ponto logico para adicionar uma secao "Escalas nos proximos 2 dias com pendentes".
    Verificar se ja existe alguma busca de escalas proximas no PHP no topo do arquivo.
    Verificar como outros botoes de acao no dashboard sao estilizados para manter consistencia.
  </read_first>
  <action>
    PASSO 1 — No topo de admin/index.php (na secao de queries PHP, antes do HTML),
    adicionar a query para escalas nos proximos 2 dias com participantes pendentes:

    ```php
    // Escalas nos proximos 2 dias com musicos pendentes (para botao de lembrete)
    $upcomingWithPending = [];
    if ($_SESSION['user_role'] === 'admin') {
        $stmtUpcoming = $pdo->query("
            SELECT s.id, s.event_type, s.event_date,
                   COUNT(CASE WHEN su.status = 'pending' THEN 1 END) AS pending_count,
                   COUNT(su.user_id) AS total_count
            FROM schedules s
            LEFT JOIN schedule_users su ON s.id = su.schedule_id
            WHERE s.event_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 2 DAY)
            GROUP BY s.id
            HAVING pending_count > 0
            ORDER BY s.event_date ASC
        ");
        $upcomingWithPending = $stmtUpcoming ? $stmtUpcoming->fetchAll(PDO::FETCH_ASSOC) : [];
    }
    ```

    PASSO 2 — No HTML do dashboard (secao admin, visivel apenas para admin),
    adicionar card de lembretes. Inserir em um ponto logico (ex: apos o primeiro card do dashboard
    ou antes do fechamento do container principal):

    ```php
    <?php if ($_SESSION['user_role'] === 'admin' && !empty($upcomingWithPending)): ?>
    <div class="pib-card" style="margin-bottom: 16px;">
        <div style="font-size: 0.8rem; font-weight: 800; color: var(--color-text-muted); text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 12px;">
            <i data-lucide="bell" width="14" style="vertical-align: middle;"></i> Confirmacoes Pendentes
        </div>
        <?php foreach ($upcomingWithPending as $upSched): ?>
        <div style="display: flex; align-items: center; justify-content: space-between; padding: 10px 0; border-bottom: 1px solid var(--color-border);">
            <div>
                <div style="font-weight: 700; font-size: 0.9rem;"><?= htmlspecialchars($upSched['event_type']) ?></div>
                <div style="font-size: 0.75rem; color: var(--color-text-muted);">
                    <?= date('d/m', strtotime($upSched['event_date'])) ?> •
                    <span style="color: var(--color-warning, #D97706);"><?= $upSched['pending_count'] ?> pendente(s)</span>
                    de <?= $upSched['total_count'] ?>
                </div>
            </div>
            <button
                onclick="enviarLembrete(<?= $upSched['id'] ?>, this)"
                style="font-size: 0.75rem; font-weight: 700; padding: 6px 12px; background: var(--color-primary); color: white; border: none; border-radius: var(--radius-sm, 8px); cursor: pointer; white-space: nowrap; min-height: 36px;">
                <i data-lucide="send" width="12" style="vertical-align: middle;"></i> Lembrar
            </button>
        </div>
        <?php endforeach; ?>
    </div>

    <script>
    function enviarLembrete(scheduleId, btn) {
        btn.disabled = true;
        btn.textContent = 'Enviando...';
        var formData = new FormData();
        formData.append('schedule_id', scheduleId);
        fetch('../api/send_reminders.php', {method: 'POST', body: formData})
            .then(function(r) { return r.json(); })
            .then(function(data) {
                btn.textContent = data.sent + ' enviados';
                btn.style.background = '#22C55E';
            })
            .catch(function() {
                btn.disabled = false;
                btn.textContent = 'Erro';
                btn.style.background = '#EF4444';
            });
    }
    </script>
    <?php endif; ?>
    ```
  </action>
  <acceptance_criteria>
    - `admin/index.php` contem `$upcomingWithPending`
    - `admin/index.php` contem `BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 2 DAY)`
    - `admin/index.php` contem `pending_count > 0`
    - `admin/index.php` contem `enviarLembrete(`
    - `admin/index.php` contem `fetch('../api/send_reminders.php'`
    - `admin/index.php` contem `Confirmacoes Pendentes`
    - `php -l admin/index.php` retorna "No syntax errors"
  </acceptance_criteria>
  <done>Botao de lembrete adicionado no dashboard admin para escalas nos proximos 2 dias com pendentes.</done>
</task>

</tasks>

<verification>
Testar manualmente com php -S localhost:8080:

1. Verificar sintaxe de todos os arquivos modificados:
   php -l includes/config.php
   php -l includes/web_push_helper.php
   php -l api/send_reminders.php
   php -l admin/escala_detalhe.php
   php -l admin/index.php

2. Verificar que config.php define VAPID_PUBLIC_KEY (mesmo que vazia):
   php -r "require 'includes/config.php'; var_dump(defined('VAPID_PUBLIC_KEY'));" (deve retornar bool(true))

3. Abrir admin/index.php — se houver escalas nos proximos 2 dias com musicos pendentes,
   o card "Confirmacoes Pendentes" deve aparecer com o botao "Lembrar"

4. Clicar "Lembrar" — deve chamar api/send_reminders.php (pode falhar sem VAPID keys reais,
   mas deve retornar JSON e nao crashar o PHP)

5. Salvar uma escala como admin com um membro novo adicionado — nao deve dar erro PHP
   (push pode silenciosamente falhar se VAPID keys nao estiverem configuradas)

NOTA: Push real so vai funcionar em producao com as chaves VAPID reais configuradas no .htaccess.
Em ambiente local sem chaves, o helper retorna false silenciosamente (error_log registra).
</verification>

<success_criteria>
- ESC-05: Push de lembrete 2 dias antes via botao manual no dashboard ✓
- Push automatico de convocacao ao salvar escala (para novos membros) ✓
- Fallback D-02: botao manual funcional mesmo sem push real ✓
- AESGCM real implementado (RFC 8291 aes128gcm) ✓
- Sem Composer, sem dependencias externas ✓
- php -l todos os arquivos sem erros de sintaxe ✓
</success_criteria>
