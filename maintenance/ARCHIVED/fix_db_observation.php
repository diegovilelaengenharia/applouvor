<?php
require_once __DIR__ . '/../includes/db.php';

try {
    echo "Checking columns in user_unavailability...\n";
    $stmt = $pdo->query("SHOW COLUMNS FROM user_unavailability LIKE 'observation'");
    $col = $stmt->fetch();

    if ($col) {
        echo "Column 'observation' ALREADY EXISTS.\n";
    } else {
        echo "Column 'observation' NOT found. Adding it...\n";
        $pdo->exec("ALTER TABLE user_unavailability ADD COLUMN observation TEXT DEFAULT NULL");
        echo "Column 'observation' ADDED SUCCESSFULLY.\n";
    }

} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
