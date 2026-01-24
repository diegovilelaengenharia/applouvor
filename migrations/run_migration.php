<?php
// run_migration.php - Executar migration 005
require_once '../includes/db.php';

try {
    // Ler arquivo SQL
    $file = isset($_GET['file']) ? $_GET['file'] : '005_create_roles_system.sql';
    $filepath = __DIR__ . '/' . $file;
    
    if (!file_exists($filepath)) {
        die("Arquivo not found: $file");
    }

    $sql = file_get_contents($filepath);
    
    // Executar
    $pdo->exec($sql);
    
    echo "Migration ($file) executada com sucesso!\n";

    
} catch (PDOException $e) {
    echo "Erro ao executar migration: " . $e->getMessage() . "\n";
}
?>
