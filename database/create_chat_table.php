<?php
// Script para criar tabela chat_messages
require_once '../includes/db.php';

try {
    // Ler arquivo SQL
    $sql = file_get_contents(__DIR__ . '/migrations/create_chat_messages.sql');
    
    // Executar SQL
    $pdo->exec($sql);
    
    echo "✅ Tabela chat_messages criada com sucesso!\n";
} catch (PDOException $e) {
    echo "❌ Erro ao criar tabela: " . $e->getMessage() . "\n";
}
