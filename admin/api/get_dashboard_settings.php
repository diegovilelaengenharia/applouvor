<?php
require_once '../../includes/db.php';
session_start();

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Não autorizado']);
    exit;
}

$userId = $_SESSION['user_id'];

try {
    $stmt = $pdo->prepare("
        SELECT card_id, is_visible, display_order 
        FROM user_dashboard_settings 
        WHERE user_id = ? 
        ORDER BY display_order ASC
    ");
    $stmt->execute([$userId]);
    $settings = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Se não houver configurações, retornar padrão (todos os 23 cards)
    if (empty($settings)) {
        $defaultCards = [
            // GESTÃO
            ['card_id' => 'escalas', 'is_visible' => true, 'display_order' => 1],
            ['card_id' => 'repertorio', 'is_visible' => true, 'display_order' => 2],
            ['card_id' => 'membros', 'is_visible' => false, 'display_order' => 8],
            ['card_id' => 'stats_escalas', 'is_visible' => false, 'display_order' => 9],
            ['card_id' => 'stats_repertorio', 'is_visible' => false, 'display_order' => 10],
            ['card_id' => 'relatorios', 'is_visible' => false, 'display_order' => 11],
            ['card_id' => 'agenda', 'is_visible' => false, 'display_order' => 12],
            ['card_id' => 'indisponibilidades', 'is_visible' => false, 'display_order' => 13],
            // ESPÍRITO
            ['card_id' => 'leitura', 'is_visible' => true, 'display_order' => 3],
            ['card_id' => 'devocional', 'is_visible' => true, 'display_order' => 6],
            ['card_id' => 'oracao', 'is_visible' => true, 'display_order' => 7],
            ['card_id' => 'config_leitura', 'is_visible' => false, 'display_order' => 14],
            // COMUNICA
            ['card_id' => 'avisos', 'is_visible' => true, 'display_order' => 4],
            ['card_id' => 'aniversariantes', 'is_visible' => true, 'display_order' => 5],
            ['card_id' => 'chat', 'is_visible' => false, 'display_order' => 15],
            // ADMIN
            ['card_id' => 'lider', 'is_visible' => false, 'display_order' => 16],
            ['card_id' => 'perfil', 'is_visible' => false, 'display_order' => 17],
            ['card_id' => 'configuracoes', 'is_visible' => false, 'display_order' => 18],
            ['card_id' => 'monitoramento', 'is_visible' => false, 'display_order' => 19],
            ['card_id' => 'pastas', 'is_visible' => false, 'display_order' => 20],
            // EXTRAS
            ['card_id' => 'playlists', 'is_visible' => false, 'display_order' => 21],
            ['card_id' => 'artistas', 'is_visible' => false, 'display_order' => 22],
            ['card_id' => 'classificacoes', 'is_visible' => false, 'display_order' => 23],
        ];
        
        echo json_encode(['success' => true, 'settings' => $defaultCards]);
        exit;
    }
    
    echo json_encode(['success' => true, 'settings' => $settings]);
    
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Erro ao buscar configurações']);
}
