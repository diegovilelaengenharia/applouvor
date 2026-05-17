<?php
require_once __DIR__ . '/../includes/db.php';

try {
    echo "Verificando colunas de estatísticas na tabela users...\n";

    // Adicionar last_login
    try {
        $pdo->exec("ALTER TABLE users ADD COLUMN last_login DATETIME NULL");
        echo "Coluna 'last_login' adicionada.\n";
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), 'Duplicate column') !== false) {
            echo "Coluna 'last_login' já existe.\n";
        } else {
            echo "Erro ao adicionar last_login: " . $e->getMessage() . "\n";
        }
    }

    // Adicionar login_count
    try {
        $pdo->exec("ALTER TABLE users ADD COLUMN login_count INT DEFAULT 0");
        echo "Coluna 'login_count' adicionada.\n";
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), 'Duplicate column') !== false) {
            echo "Coluna 'login_count' já existe.\n";
        } else {
            echo "Erro ao adicionar login_count: " . $e->getMessage() . "\n";
        }
    }
} catch (Exception $e) {
    echo "Erro fatal: " . $e->getMessage() . "\n";
}
