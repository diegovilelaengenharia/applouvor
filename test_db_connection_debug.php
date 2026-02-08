<?php
require_once 'includes/config.php';

echo "DB_HOST: " . DB_HOST . "\n";
echo "DB_NAME: " . DB_NAME . "\n";
echo "DB_USER: " . DB_USER . "\n";
echo "DB_PASS: " . (empty(DB_PASS) ? "(empty)" : "******") . "\n";

echo "\nTests:\n";
try {
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8",
        DB_USER,
        DB_PASS
    );
    echo "Connection SUCCESS!\n";
} catch (PDOException $e) {
    echo "Connection FAILED: " . $e->getMessage() . "\n";
}
