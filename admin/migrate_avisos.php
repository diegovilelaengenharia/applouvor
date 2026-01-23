<?php
// admin/migrate_avisos.php
require_once '../includes/db.php';

echo "<h1>Migração Tabela Avisos</h1>";

try {
    // 1. Adicionar coluna target_audience
    $pdo->exec("
        ALTER TABLE avisos 
        ADD COLUMN target_audience ENUM('all', 'admins', 'team', 'leaders') DEFAULT 'all' AFTER type;
    ");
    echo "<p>✅ Coluna 'target_audience' adicionada.</p>";
} catch (Exception $e) {
    echo "<p>⚠️ Erro/Info (Pode já existir): " . $e->getMessage() . "</p>";
}

try {
    // 2. Adicionar coluna expires_at
    $pdo->exec("
        ALTER TABLE avisos 
        ADD COLUMN expires_at DATE DEFAULT NULL AFTER message;
    ");
    echo "<p>✅ Coluna 'expires_at' adicionada.</p>";
} catch (Exception $e) {
    echo "<p>⚠️ Erro/Info (Pode já existir): " . $e->getMessage() . "</p>";
}

try {
    // 3. Adicionar coluna view_count
    $pdo->exec("
        ALTER TABLE avisos 
        ADD COLUMN view_count INT DEFAULT 0 AFTER archived_at;
    ");
    echo "<p>✅ Coluna 'view_count' adicionada.</p>";
} catch (Exception $e) {
    echo "<p>⚠️ Erro/Info (Pode já existir): " . $e->getMessage() . "</p>";
}

echo "<hr><p>Migração concluída. Pode deletar este arquivo.</p>";
