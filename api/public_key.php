<?php
// api/public_key.php
header('Content-Type: application/json');

$configFile = __DIR__ . '/../includes/vapid_config.php';

if (file_exists($configFile)) {
    $config = require $configFile;
    echo json_encode([
        'success' => true,
        'publicKey' => $config['publicKey']
    ]);
} else {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Config not found']);
}
?>
