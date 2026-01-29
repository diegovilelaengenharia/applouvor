<?php
// api/push_subscription.php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';

header('Content-Type: application/json');

// Check authentication
// Note: includes/auth.php might redirect if not logged in. 
// For API, we might want to check session directly to avoid HTML redirect, 
// but auth.php usually handles session start.

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method Not Allowed']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);

if (!$input || !isset($input['endpoint'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Invalid subscription data']);
    exit;
}

$userId = $_SESSION['user_id'];
$endpoint = $input['endpoint'];
$p256dh = $input['keys']['p256dh'] ?? '';
$auth = $input['keys']['auth'] ?? '';

try {
    // Check if subscription already exists
    $stmtCheck = $pdo->prepare("SELECT id FROM push_subscriptions WHERE endpoint = ? AND user_id = ?");
    $stmtCheck->execute([$endpoint, $userId]);
    
    if ($stmtCheck->rowCount() > 0) {
        // Update keys if changed? Usually endpoint is unique enough.
        echo json_encode(['success' => true, 'message' => 'Subscription updated']);
        exit;
    }

    // Insert new subscription
    $stmt = $pdo->prepare("INSERT INTO push_subscriptions (user_id, endpoint, p256dh, auth) VALUES (?, ?, ?, ?)");
    $stmt->execute([$userId, $endpoint, $p256dh, $auth]);

    echo json_encode(['success' => true, 'message' => 'Subscription saved']);

} catch (PDOException $e) {
    error_log('Error saving subscription: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Database error']);
}
?>
