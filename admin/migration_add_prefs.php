<?php
// admin/migration_add_prefs.php
require_once '../includes/db.php';

echo "Iniciando migração...\n";

try {
    // Verificar se a coluna já existe
    $stmt = $pdo->query("SHOW COLUMNS FROM users LIKE 'dashboard_prefs'");
    $exists = $stmt->fetch();

    if (!$exists) {
        $pdo->exec("ALTER TABLE users ADD COLUMN dashboard_prefs TEXT DEFAULT NULL");
        echo "Sucesso: Coluna 'dashboard_prefs' adicionada na tabela users.\n";
    } else {
        echo "Info: Coluna 'dashboard_prefs' já existe.\n";
    }
} catch (PDOException $e) {
    echo "Erro: " . $e->getMessage() . "\n";
}
