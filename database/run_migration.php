<?php
// Script para executar migração do banco de dados
require_once '../includes/db.php';

try {
    // Ler arquivo SQL
    $sql = file_get_contents(__DIR__ . '/migrations/add_dashboard_settings.sql');
    
    // Executar SQL
    $pdo->exec($sql);
    
    echo "✅ Migração executada com sucesso!\n";
    echo "Tabela 'user_dashboard_settings' criada e populada.\n";
    
} catch (PDOException $e) {
    echo "❌ Erro ao executar migração: " . $e->getMessage() . "\n";
    exit(1);
}
