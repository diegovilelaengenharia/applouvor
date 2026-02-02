<?php
/**
 * API para marcar devocionais como lidos
 */

require_once '../includes/auth.php';
require_once '../includes/db.php';

checkLogin();

header('Content-Type: application/json');

$userId = $_SESSION['user_id'];
$devotionalId = $_POST['devotional_id'] ?? $_GET['devotional_id'] ?? null;

if (!$devotionalId) {
    http_response_code(400);
    echo json_encode(['error' => 'devotional_id é obrigatório']);
    exit;
}

try {
    // Verificar se já foi lido
    $stmt = $pdo->prepare("SELECT id FROM devotional_reads WHERE user_id = ? AND devotional_id = ?");
    $stmt->execute([$userId, $devotionalId]);
    $alreadyRead = $stmt->fetch();
    
    if (!$alreadyRead) {
        // Marcar como lido
        $stmt = $pdo->prepare("INSERT INTO devotional_reads (user_id, devotional_id) VALUES (?, ?)");
        $stmt->execute([$userId, $devotionalId]);
        
        echo json_encode(['success' => true, 'message' => 'Marcado como lido', 'is_read' => true]);
    } else {
        echo json_encode(['success' => true, 'message' => 'Já estava marcado como lido', 'is_read' => true]);
    }
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Erro ao marcar como lido: ' . $e->getMessage()]);
}
