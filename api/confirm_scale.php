<?php
// api/confirm_scale.php
header('Content-Type: application/json');
require_once '../src/helpers/auth.php';
require_once '../src/config/db.php';

$userId = $_SESSION['user_id'] ?? 0;
$data = json_decode(file_get_contents('php://input'), true);

$scheduleId = $data['schedule_id'] ?? 0;
$status = $data['status'] ?? ''; // 'confirmed' or 'declined'

if (!$userId || !$scheduleId || !in_array($status, ['confirmed', 'declined'])) {
    echo json_encode(['success' => false, 'message' => 'Dados inválidos']);
    exit;
}

try {
    $stmt = $pdo->prepare("
        UPDATE schedule_users 
        SET status = ? 
        WHERE schedule_id = ? AND user_id = ?
    ");
    $stmt->execute([$status, $scheduleId, $userId]);

    echo json_encode(['success' => true, 'message' => 'Status atualizado com sucesso']);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Erro no banco de dados: ' . $e->getMessage()]);
}
