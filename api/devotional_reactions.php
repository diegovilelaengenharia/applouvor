<?php
/**
 * API Endpoint para Reações de Devocionais
 * Handles AJAX requests for devotional reactions
 */

require_once '../includes/auth.php';
require_once '../includes/db.php';
require_once '../includes/devotional_helpers.php';

checkLogin();

header('Content-Type: application/json');

$userId = $_SESSION['user_id'] ?? 1;

// Handle POST requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($data['devotional_id']) || !isset($data['reaction_type'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Missing required parameters']);
        exit;
    }
    
    $devotionalId = (int)$data['devotional_id'];
    $reactionType = $data['reaction_type'];
    
    // Validate reaction type
    if (!in_array($reactionType, ['amen', 'prayer', 'inspired'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid reaction type']);
        exit;
    }
    
    try {
        $result = toggleDevotionalReaction($pdo, $devotionalId, $userId, $reactionType);
        $counts = getDevotionalReactionCounts($pdo, $devotionalId);
        $userReactions = getUserReactions($pdo, $devotionalId, $userId);
        
        echo json_encode([
            'success' => true,
            'action' => $result['action'],
            'reaction_type' => $result['type'],
            'counts' => $counts,
            'user_reactions' => $userReactions
        ]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to toggle reaction']);
    }
}

// Handle GET requests (get current state)
elseif ($_SERVER['REQUEST_METHOD'] === 'GET') {
    if (!isset($_GET['devotional_id'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Missing devotional_id']);
        exit;
    }
    
    $devotionalId = (int)$_GET['devotional_id'];
    
    try {
        $counts = getDevotionalReactionCounts($pdo, $devotionalId);
        $userReactions = getUserReactions($pdo, $devotionalId, $userId);
        
        echo json_encode([
            'success' => true,
            'counts' => $counts,
            'user_reactions' => $userReactions
        ]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to get reactions']);
    }
}

else {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
}
