<?php
require_once __DIR__ . '/../../src/config/db.php';

try {
    $pdo->exec("ALTER TABLE user_unavailability ADD COLUMN observation TEXT DEFAULT NULL");
    echo "Column 'observation' added successfully.";
} catch (PDOException $e) {
    echo "Error (might already exist): " . $e->getMessage();
}
?>
