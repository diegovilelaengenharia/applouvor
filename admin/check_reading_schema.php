<?php
require_once '../includes/db.php';

function checkTable($pdo, $table) {
    try {
        $stmt = $pdo->query("DESCRIBE $table");
        $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
        echo "$table EXISTS. Columns: " . implode(", ", $columns) . "\n";
    } catch (Exception $e) {
        echo "$table MISSING. Error: " . $e->getMessage() . "\n";
    }
}

checkTable($pdo, 'reading_progress');
checkTable($pdo, 'user_settings');
checkTable($pdo, 'leitura_progresso'); // Trying portuguese name too
