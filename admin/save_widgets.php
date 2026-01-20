<?php
// admin/save_widgets.php
require_once '../includes/auth.php';
require_once '../includes/db.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);

if (!isset($input['widgets']) || !is_array($input['widgets'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid input']);
    exit;
}

try {
    $pdo->beginTransaction();

    // Clear existing widgets setting for this user to avoid conflicts (or use ON DUPLICATE KEY UPDATE logic)
    // Simpler approach: Insert or Update each

    foreach ($input['widgets'] as $index => $widget) {
        $name = $widget['name'];
        $enabled = $widget['enabled'] ? 1 : 0;
        $position = $index;

        $stmt = $pdo->prepare("
            INSERT INTO user_widgets (user_id, widget_name, position, enabled) 
            VALUES (?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE position = VALUES(position), enabled = VALUES(enabled)
        ");
        $stmt->execute([$_SESSION['user_id'], $name, $position, $enabled]);
    }

    $pdo->commit();
    echo json_encode(['success' => true]);
} catch (Exception $e) {
    $pdo->rollBack();
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
