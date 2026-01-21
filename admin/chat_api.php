<?php
// admin/chat_api.php
require_once '../includes/auth.php';
require_once '../includes/db.php';

header('Content-Type: application/json');

$userId = $_SESSION['user_id'] ?? 1;

// GET: Buscar mensagens
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $stmt = $pdo->query("
        SELECT cm.*, u.name as user_name, u.avatar 
        FROM chat_messages cm
        JOIN users u ON cm.user_id = u.id
        ORDER BY cm.created_at DESC
        LIMIT 50
    ");
    $messages = array_reverse($stmt->fetchAll(PDO::FETCH_ASSOC));
    echo json_encode($messages);
    exit;
}

// POST: Enviar mensagem
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);

    if (empty($data['message'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Mensagem vazia']);
        exit;
    }

    $message = trim($data['message']);

    $stmt = $pdo->prepare("INSERT INTO chat_messages (user_id, message) VALUES (?, ?)");
    $stmt->execute([$userId, $message]);

    echo json_encode(['success' => true, 'id' => $pdo->lastInsertId()]);
    exit;
}

http_response_code(405);
echo json_encode(['error' => 'Método não permitido']);
