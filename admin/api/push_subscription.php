<?php
/**
 * API para Gerenciar Push Subscriptions
 */

require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/db.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Não autorizado']);
    exit;
}

$userId = $_SESSION['user_id'];
$method = $_SERVER['REQUEST_METHOD'];

try {
    if ($method === 'POST') {
        // Receber dados
        $input = json_decode(file_get_contents('php://input'), true);
        
        if (!isset($input['endpoint'])) {
            throw new Exception('Endpoint obrigatório');
        }
        
        $endpoint = $input['endpoint'];
        $keys = $input['keys'] ?? [];
        $p256dh = $keys['p256dh'] ?? '';
        $auth = $keys['auth'] ?? '';
        
        // Verificar se já existe
        $stmtCheck = $pdo->prepare("SELECT id FROM push_subscriptions WHERE user_id = ? AND endpoint = ?");
        $stmtCheck->execute([$userId, $endpoint]);
        
        if ($stmtCheck->rowCount() > 0) {
            // Atualizar
            $stmtUpdate = $pdo->prepare("UPDATE push_subscriptions SET p256dh = ?, auth = ? WHERE user_id = ? AND endpoint = ?");
            $stmtUpdate->execute([$p256dh, $auth, $userId, $endpoint]);
            $message = 'Subscription atualizada';
        } else {
            // Inserir
            $stmtInsert = $pdo->prepare("INSERT INTO push_subscriptions (user_id, endpoint, p256dh, auth) VALUES (?, ?, ?, ?)");
            $stmtInsert->execute([$userId, $endpoint, $p256dh, $auth]);
            $message = 'Subscription criada';
        }
        
        echo json_encode(['success' => true, 'message' => $message]);
        
    } elseif ($method === 'DELETE') {
        // Remover subscription
        $input = json_decode(file_get_contents('php://input'), true);
        $endpoint = $input['endpoint'] ?? null;
        
        if ($endpoint) {
            $stmt = $pdo->prepare("DELETE FROM push_subscriptions WHERE user_id = ? AND endpoint = ?");
            $stmt->execute([$userId, $endpoint]);
        }
        
        echo json_encode(['success' => true]);
        
    } else {
        throw new Exception('Método não permitido');
    }
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>
