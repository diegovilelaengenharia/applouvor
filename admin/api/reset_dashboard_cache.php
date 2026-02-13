<?php
// admin/api/reset_dashboard_cache.php
// Script temporário para limpar cache do dashboard

header('Content-Type: application/json; charset=utf-8');
require_once '../../includes/auth.php';
require_once '../../includes/db.php';

checkLogin();

$userId = $_SESSION['user_id'] ?? 1;

try {
    // Limpar configurações antigas
    $stmt = $pdo->prepare("DELETE FROM user_dashboard_settings WHERE user_id = ?");
    $stmt->execute([$userId]);
    
    // Inserir nova configuração padrão
    $defaultCards = [
        // Gestão
        ['card_id' => 'escalas', 'order' => 1],
        ['card_id' => 'repertorio', 'order' => 2],
        ['card_id' => 'historico', 'order' => 3],
        ['card_id' => 'membros', 'order' => 4],
        ['card_id' => 'ausencias', 'order' => 5],
        ['card_id' => 'agenda', 'order' => 6],
        // Espiritualidade
        ['card_id' => 'leitura', 'order' => 7],
        ['card_id' => 'devocional', 'order' => 8],
        ['card_id' => 'oracao', 'order' => 9],
        // Comunicação
        ['card_id' => 'avisos', 'order' => 10],
        ['card_id' => 'aniversarios', 'order' => 11],
    ];
    
    $stmt = $pdo->prepare("INSERT INTO user_dashboard_settings (user_id, card_id, is_visible, display_order) VALUES (?, ?, 1, ?)");
    
    foreach ($defaultCards as $card) {
        $stmt->execute([$userId, $card['card_id'], $card['order']]);
    }
    
    echo json_encode(['success' => true, 'message' => 'Dashboard resetado com sucesso!']);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
