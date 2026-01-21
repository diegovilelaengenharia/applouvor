<?php
require_once '../includes/db.php';
try {
    $stmt = $pdo->query("DESCRIBE schedules");
    $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
    echo "COLUMNS: " . implode(", ", $columns);
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage();
}
