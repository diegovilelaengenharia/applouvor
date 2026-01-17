<?php
require_once 'includes/db.php';

try {
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 0");

    // Drop tables if exist

    // 1. Drop tables in correct dependency order (child first, then parent)
    $tables = [
        'schedule_users',
        'schedule_songs',
        'schedules',
        'songs',
        'users'
    ];

    foreach ($tables as $table) {
        $pdo->exec("DROP TABLE IF EXISTS `$table`");
        echo "Dropped table: $table<br>";
    }

    $pdo->exec("SET FOREIGN_KEY_CHECKS = 1");

    // 2. Read and run schema
    $sql = file_get_contents('schema.sql');

    // Remove comments to avoid parsing issues if we split simply
    $sql = preg_replace('/--.*$/m', '', $sql);

    // Split by semicolon
    $statements = array_filter(array_map('trim', explode(';', $sql)));

    foreach ($statements as $stmt) {
        if (!empty($stmt)) {
            // Skip USE database commands if we are already connected, or handle them.
            // PDO might not allow changing DB easily depending on driver, but usually OK.
            // Also IF NOT EXISTS pibo_louvor might fail if user doesn't have create db perm, but usually local root does.
            try {
                $pdo->exec($stmt);
            } catch (PDOException $e) {
                // If error is about "No database selected" or "Database exists", just continue/log
                echo "Warning on statement: " . htmlspecialchars(substr($stmt, 0, 50)) . "... <br>Error: " . $e->getMessage() . "<br>";
            }
        }
    }

    echo "Database migrated to new Vibe Code schema successfully!";
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
