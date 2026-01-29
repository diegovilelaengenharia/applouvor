<?php
// includes/google_calendar.php

/**
 * Classe para integração com Google Calendar API
 * Gerencia autenticação OAuth2 e sincronização bidirecional de eventos
 */
class GoogleCalendarIntegration {
    private $pdo;
    private $userId;
    private $clientId;
    private $clientSecret;
    private $redirectUri;
    
    // Escopos necessários da API
    const SCOPES = 'https://www.googleapis.com/auth/calendar.events';
    const TOKEN_URI = 'https://oauth2.googleapis.com/token';
    const AUTH_URI = 'https://accounts.google.com/o/oauth2/v2/auth';
    const API_BASE = 'https://www.googleapis.com/calendar/v3';
    
    public function __construct($pdo, $userId) {
        $this->pdo = $pdo;
        $this->userId = $userId;
        
        // Configurações OAuth2 (devem ser definidas em config.php)
        $this->clientId = defined('GOOGLE_CLIENT_ID') ? GOOGLE_CLIENT_ID : '';
        $this->clientSecret = defined('GOOGLE_CLIENT_SECRET') ? GOOGLE_CLIENT_SECRET : '';
        $this->redirectUri = defined('GOOGLE_REDIRECT_URI') ? GOOGLE_REDIRECT_URI : '';
    }
    
    /**
     * Gera URL de autorização OAuth2
     */
    public function getAuthUrl() {
        if (empty($this->clientId)) {
            throw new Exception('Credenciais do Google não configuradas');
        }
        
        $params = [
            'client_id' => $this->clientId,
            'redirect_uri' => $this->redirectUri,
            'response_type' => 'code',
            'scope' => self::SCOPES,
            'access_type' => 'offline',
            'prompt' => 'consent'
        ];
        
        return self::AUTH_URI . '?' . http_build_query($params);
    }
    
    /**
     * Troca código de autorização por tokens de acesso
     */
    public function exchangeCodeForTokens($code) {
        $data = [
            'code' => $code,
            'client_id' => $this->clientId,
            'client_secret' => $this->clientSecret,
            'redirect_uri' => $this->redirectUri,
            'grant_type' => 'authorization_code'
        ];
        
        $response = $this->makeRequest(self::TOKEN_URI, 'POST', $data);
        
        if (isset($response['access_token'])) {
            $this->saveTokens($response);
            return true;
        }
        
        return false;
    }
    
    /**
     * Salva ou atualiza tokens no banco de dados
     */
    private function saveTokens($tokenData) {
        $expiresAt = date('Y-m-d H:i:s', time() + ($tokenData['expires_in'] ?? 3600));
        
        $stmt = $this->pdo->prepare("
            INSERT INTO google_calendar_tokens 
            (user_id, access_token, refresh_token, expires_at, scope, token_type, auto_sync_enabled) 
            VALUES (?, ?, ?, ?, ?, ?, TRUE)
            ON DUPLICATE KEY UPDATE
                access_token = VALUES(access_token),
                refresh_token = COALESCE(VALUES(refresh_token), refresh_token),
                expires_at = VALUES(expires_at),
                scope = VALUES(scope),
                updated_at = NOW()
        ");
        
        $stmt->execute([
            $this->userId,
            $tokenData['access_token'],
            $tokenData['refresh_token'] ?? null,
            $expiresAt,
            $tokenData['scope'] ?? self::SCOPES,
            $tokenData['token_type'] ?? 'Bearer'
        ]);
    }
    
    /**
     * Obtém token de acesso válido (renova se necessário)
     */
    private function getAccessToken() {
        $stmt = $this->pdo->prepare("
            SELECT * FROM google_calendar_tokens 
            WHERE user_id = ? AND auto_sync_enabled = TRUE
        ");
        $stmt->execute([$this->userId]);
        $tokenData = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$tokenData) {
            throw new Exception('Usuário não conectou com Google Calendar');
        }
        
        // Verificar se token expirou
        if (strtotime($tokenData['expires_at']) < time() + 300) { // 5 minutos de buffer
            return $this->refreshAccessToken($tokenData['refresh_token']);
        }
        
        return $tokenData['access_token'];
    }
    
    /**
     * Renova token de acesso usando refresh token
     */
    private function refreshAccessToken($refreshToken) {
        if (empty($refreshToken)) {
            throw new Exception('Refresh token não disponível');
        }
        
        $data = [
            'client_id' => $this->clientId,
            'client_secret' => $this->clientSecret,
            'refresh_token' => $refreshToken,
            'grant_type' => 'refresh_token'
        ];
        
        $response = $this->makeRequest(self::TOKEN_URI, 'POST', $data);
        
        if (isset($response['access_token'])) {
            $this->saveTokens(array_merge($response, ['refresh_token' => $refreshToken]));
            return $response['access_token'];
        }
        
        throw new Exception('Falha ao renovar token');
    }
    
    /**
     * Cria evento no Google Calendar
     */
    public function createEvent($eventData) {
        $accessToken = $this->getAccessToken();
        $calendarId = $this->getSelectedCalendarId();
        
        $googleEvent = $this->formatEventForGoogle($eventData);
        
        $url = self::API_BASE . "/calendars/{$calendarId}/events";
        $response = $this->makeRequest($url, 'POST', $googleEvent, [
            'Authorization: Bearer ' . $accessToken,
            'Content-Type: application/json'
        ]);
        
        if (isset($response['id'])) {
            $this->logSync($eventData['id'], 'created', 'app_to_google', true);
            return $response['id'];
        }
        
        $this->logSync($eventData['id'], 'created', 'app_to_google', false, 
                      json_encode($response));
        return null;
    }
    
    /**
     * Atualiza evento no Google Calendar
     */
    public function updateEvent($eventData, $googleEventId) {
        $accessToken = $this->getAccessToken();
        $calendarId = $this->getSelectedCalendarId();
        
        $googleEvent = $this->formatEventForGoogle($eventData);
        
        $url = self::API_BASE . "/calendars/{$calendarId}/events/{$googleEventId}";
        $response = $this->makeRequest($url, 'PUT', $googleEvent, [
            'Authorization: Bearer ' . $accessToken,
            'Content-Type: application/json'
        ]);
        
        if (isset($response['id'])) {
            $this->logSync($eventData['id'], 'updated', 'app_to_google', true);
            return true;
        }
        
        $this->logSync($eventData['id'], 'updated', 'app_to_google', false, 
                      json_encode($response));
        return false;
    }
    
    /**
     * Exclui evento do Google Calendar
     */
    public function deleteEvent($googleEventId, $localEventId = null) {
        $accessToken = $this->getAccessToken();
        $calendarId = $this->getSelectedCalendarId();
        
        $url = self::API_BASE . "/calendars/{$calendarId}/events/{$googleEventId}";
        $this->makeRequest($url, 'DELETE', null, [
            'Authorization: Bearer ' . $accessToken
        ]);
        
        $this->logSync($localEventId, 'deleted', 'app_to_google', true);
        return true;
    }
    
    /**
     * Formata evento do app para formato do Google Calendar
     */
    private function formatEventForGoogle($eventData) {
        $event = [
            'summary' => $eventData['title'],
            'description' => $eventData['description'] ?? '',
            'location' => $eventData['location'] ?? ''
        ];
        
        // Formato de data/hora
        if ($eventData['all_day']) {
            $event['start'] = ['date' => date('Y-m-d', strtotime($eventData['start_datetime']))];
            $event['end'] = ['date' => date('Y-m-d', strtotime($eventData['start_datetime'] . ' +1 day'))];
        } else {
            $event['start'] = [
                'dateTime' => date('c', strtotime($eventData['start_datetime'])),
                'timeZone' => 'America/Sao_Paulo'
            ];
            
            $endTime = $eventData['end_datetime'] ?? $eventData['start_datetime'];
            $event['end'] = [
                'dateTime' => date('c', strtotime($endTime)),
                'timeZone' => 'America/Sao_Paulo'
            ];
        }
        
        // Cor do evento (Google usa IDs de cores específicos)
        $colorMap = [
            '#3b82f6' => '9',  // Azul
            '#047857' => '10', // Verde
            '#f59e0b' => '5',  // Amarelo
            '#ec4899' => '4',  // Rosa
            '#8b5cf6' => '3',  // Roxo
            '#ef4444' => '11'  // Vermelho
        ];
        
        if (isset($colorMap[$eventData['color']])) {
            $event['colorId'] = $colorMap[$eventData['color']];
        }
        
        return $event;
    }
    
    /**
     * Obtém calendário selecionado (ou usa primário)
     */
    private function getSelectedCalendarId() {
        $stmt = $this->pdo->prepare("
            SELECT selected_calendar_id FROM google_calendar_tokens 
            WHERE user_id = ?
        ");
        $stmt->execute([$this->userId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return $result['selected_calendar_id'] ?? 'primary';
    }
    
    /**
     * Registra log de sincronização
     */
    private function logSync($eventId, $action, $direction, $success, $errorMessage = null) {
        $stmt = $this->pdo->prepare("
            INSERT INTO google_calendar_sync_log 
            (user_id, event_id, action, direction, success, error_message) 
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        
        $stmt->execute([
            $this->userId,
            $eventId,
            $action,
            $direction,
            $success ? 1 : 0,
            $errorMessage
        ]);
    }
    
    /**
     * Faz requisição HTTP
     */
    private function makeRequest($url, $method = 'GET', $data = null, $headers = []) {
        $ch = curl_init();
        
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
        
        if ($data) {
            if ($method === 'POST' || $method === 'PUT') {
                $jsonData = is_array($data) ? json_encode($data) : $data;
                curl_setopt($ch, CURLOPT_POSTFIELDS, is_array($data) && empty($headers) ? http_build_query($data) : $jsonData);
            }
        }
        
        if (!empty($headers)) {
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        }
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($method === 'DELETE' && $httpCode === 204) {
            return ['success' => true];
        }
        
        return json_decode($response, true) ?? [];
    }
    
    /**
     * Verifica se usuário está conectado
     */
    public function isConnected() {
        $stmt = $this->pdo->prepare("
            SELECT id FROM google_calendar_tokens 
            WHERE user_id = ? AND auto_sync_enabled = TRUE
        ");
        $stmt->execute([$this->userId]);
        return $stmt->fetch() !== false;
    }
    
    /**
     * Desconecta conta Google
     */
    public function disconnect() {
        $stmt = $this->pdo->prepare("
            UPDATE google_calendar_tokens 
            SET auto_sync_enabled = FALSE 
            WHERE user_id = ?
        ");
        return $stmt->execute([$this->userId]);
    }
}
