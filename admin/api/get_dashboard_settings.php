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
    
    // Se não houver configurações, retornar padrão (10 cards - sincronizado com dashboard_cards.php)
    if (empty($settings)) {
        $defaultCards = [
            // GESTÃO
            ['card_id' => 'escalas', 'is_visible' => true, 'display_order' => 1],
            ['card_id' => 'repertorio', 'is_visible' => true, 'display_order' => 2],
            ['card_id' => 'membros', 'is_visible' => true, 'display_order' => 3],
            ['card_id' => 'agenda', 'is_visible' => true, 'display_order' => 4],
            ['card_id' => 'ausencias', 'is_visible' => false, 'display_order' => 5],
            // ESPÍRITO
            ['card_id' => 'leitura', 'is_visible' => true, 'display_order' => 6],
            ['card_id' => 'devocional', 'is_visible' => true, 'display_order' => 7],
            ['card_id' => 'oracao', 'is_visible' => true, 'display_order' => 8],
            // COMUNICAÇÃO
            ['card_id' => 'avisos', 'is_visible' => true, 'display_order' => 9],
            ['card_id' => 'aniversarios', 'is_visible' => true, 'display_order' => 10],
        ];
        
        echo json_encode(['success' => true, 'settings' => $defaultCards]);
        exit;
    }
    
    echo json_encode(['success' => true, 'settings' => $settings]);
    
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Erro ao buscar configurações']);
}
