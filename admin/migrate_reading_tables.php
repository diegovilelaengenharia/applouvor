<?php
/**
 * Migration Script - Create Reading Tables
 * Run this once to create the necessary database tables for the reading module
 */

require_once '../includes/db.php';

echo "=== Reading Module Database Migration ===\n\n";

try {
    // Read SQL file
    $sqlFile = __DIR__ . '/sql/create_reading_tables.sql';
    
    if (!file_exists($sqlFile)) {
        throw new Exception("SQL file not found: $sqlFile");
    }
    
    $sql = file_get_contents($sqlFile);
    
    // Split by semicolons to execute each statement separately
    $statements = array_filter(
        array_map('trim', explode(';', $sql)),
        function($stmt) {
            return !empty($stmt) && !preg_match('/^--/', $stmt);
        }
    );
    
    echo "Found " . count($statements) . " SQL statements to execute.\n\n";
    
    $pdo->beginTransaction();
    
    foreach ($statements as $index => $statement) {
        echo "Executing statement " . ($index + 1) . "...\n";
        $pdo->exec($statement);
        echo "✓ Success\n\n";
    }
    
    $pdo->commit();
    
    echo "\n=== Migration Completed Successfully ===\n";
    echo "The following tables were created:\n";
    echo "- user_reading_settings\n";
    echo "- reading_progress\n";
    echo "- reading_notes\n\n";
    
    // Verify tables exist
    echo "Verifying tables...\n";
    $tables = ['user_reading_settings', 'reading_progress', 'reading_notes'];
    
    foreach ($tables as $table) {
        $stmt = $pdo->query("SHOW TABLES LIKE '$table'");
        if ($stmt->rowCount() > 0) {
            echo "✓ $table exists\n";
        } else {
            echo "✗ $table NOT FOUND\n";
        }
    }
    
} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    echo "\n✗ ERROR: " . $e->getMessage() . "\n";
    exit(1);
}
