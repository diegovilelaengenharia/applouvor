<?php
require_once '../src/helpers/auth.php';
require_once '../src/config/db.php';

checkLogin();

try {
    $stmt = $pdo->query("DESCRIBE users");
    $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
    echo "COLUMNS: " . implode(", ", $columns);
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage();
}
