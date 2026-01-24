<?php
// run_migration.php - Executar migration 005
require_once '../includes/db.php';

try {
    // Ler arquivo SQL
    $sql = file_get_contents(__DIR__ . '/005_create_roles_system.sql');
    
    // Executar
    $pdo->exec($sql);
    
    echo "Migration 005 executada com sucesso!\n";
    echo "Tabelas 'roles' e 'user_roles' criadas.\n";
    echo "18 funções cadastradas.\n";
    
} catch (PDOException $e) {
    echo "Erro ao executar migration: " . $e->getMessage() . "\n";
}
?>
