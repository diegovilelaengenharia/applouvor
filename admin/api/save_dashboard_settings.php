<?php
require_once '../../includes/db.php';
session_start();

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Não autorizado']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Método não permitido']);
    exit;
}

$userId = $_SESSION['user_id'];
$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['cards']) || !is_array($data['cards'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Dados inválidos']);
    exit;
}

$cards = $data['cards'];
$validCardIds = [
    // GESTÃO
    'escalas', 'repertorio', 'membros', 'agenda', 'ausencias', 'historico',
    // ESPÍRITO
    'leitura', 'devocional', 'oracao',
    // COMUNICAÇÃO
    'avisos', 'aniversarios'
];

// Validar que pelo menos 1 card está visível
$visibleCount = 0;
foreach ($cards as $card) {
    if (isset($card['is_visible']) && $card['is_visible']) {
        $visibleCount++;
    }
}

if ($visibleCount === 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Pelo menos um card deve estar visível']);
    exit;
}

try {
    $pdo->beginTransaction();
    
    // Deletar configurações antigas
    $stmt = $pdo->prepare("DELETE FROM user_dashboard_settings WHERE user_id = ?");
    $stmt->execute([$userId]);
    
    // Inserir novas configurações
    $stmt = $pdo->prepare("
        INSERT INTO user_dashboard_settings (user_id, card_id, is_visible, display_order) 
        VALUES (?, ?, ?, ?)
    ");
    
    foreach ($cards as $index => $card) {
        if (!in_array($card['card_id'], $validCardIds)) {
            continue; // Ignorar IDs inválidos
        }
        
        $isVisible = isset($card['is_visible']) ? (bool)$card['is_visible'] : true;
        $displayOrder = isset($card['display_order']) ? (int)$card['display_order'] : $index + 1;
        
        $stmt->execute([$userId, $card['card_id'], $isVisible, $displayOrder]);
    }
    
    $pdo->commit();
    
    echo json_encode(['success' => true, 'message' => 'Configurações salvas com sucesso']);
    
} catch (PDOException $e) {
    $pdo->rollBack();
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Erro ao salvar configurações']);
}
