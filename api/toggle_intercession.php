<?php
// api/toggle_intercession.php - Toggle intercession status for prayer requests
require_once '../includes/auth.php';
require_once '../includes/db.php';

header('Content-Type: application/json');

checkLogin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Invalid method']);
    exit;
}

$userId = $_SESSION['user_id'] ?? null;
$prayerId = $_POST['prayer_id'] ?? null;

if (!$userId || !$prayerId) {
    echo json_encode(['success' => false, 'error' => 'Missing parameters']);
    exit;
}

try {
    // Verificar se já intercedeu
    $check = $pdo->prepare("SELECT id FROM prayer_interactions WHERE prayer_id = ? AND user_id = ? AND type = 'pray'");
    $check->execute([$prayerId, $userId]);
    $existing = $check->fetch();
    
    if ($existing) {
        // Já intercedeu -> Remover (toggle off)
        $stmt = $pdo->prepare("DELETE FROM prayer_interactions WHERE prayer_id = ? AND user_id = ? AND type = 'pray'");
        $stmt->execute([$prayerId, $userId]);
        
        // Decrementar contador
        $pdo->prepare("UPDATE prayer_requests SET prayer_count = GREATEST(prayer_count - 1, 0) WHERE id = ?")->execute([$prayerId]);
        
        echo json_encode(['success' => true, 'is_interceded' => false]);
    } else {
        // Não intercedeu ainda -> Adicionar (toggle on)
        $stmt = $pdo->prepare("INSERT INTO prayer_interactions (prayer_id, user_id, type, created_at) VALUES (?, ?, 'pray', NOW())");
        $stmt->execute([$prayerId, $userId]);
        
        // Incrementar contador
        $pdo->prepare("UPDATE prayer_requests SET prayer_count = prayer_count + 1 WHERE id = ?")->execute([$prayerId]);
        
        echo json_encode(['success' => true, 'is_interceded' => true]);
    }
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'error' => 'Database error: ' . $e->getMessage()]);
}
