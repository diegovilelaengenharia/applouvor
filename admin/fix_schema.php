<?php
require_once __DIR__ . '/../includes/db.php';

try {
    echo "Verificando schema da tabela users...\n";

    // 1. Adicionar email
    try {
        $pdo->exec("ALTER TABLE users ADD COLUMN email VARCHAR(100) NULL AFTER name");
        echo "Coluna 'email' adicionada.\n";
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), 'Duplicate column') !== false) {
            echo "Coluna 'email' já existe.\n";
        } else {
            echo "Erro ao adicionar email: " . $e->getMessage() . "\n";
        }
    }

    // 2. Adicionar address_street
    try {
        $pdo->exec("ALTER TABLE users ADD COLUMN address_street VARCHAR(255) NULL");
        echo "Coluna 'address_street' adicionada.\n";
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), 'Duplicate column') !== false) {
            echo "Coluna 'address_street' já existe.\n";
        } else {
            echo "Erro ao adicionar address_street: " . $e->getMessage() . "\n";
        }
    }

    // 3. Adicionar address_number
    try {
        $pdo->exec("ALTER TABLE users ADD COLUMN address_number VARCHAR(20) NULL");
        echo "Coluna 'address_number' adicionada.\n";
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), 'Duplicate column') !== false) {
            echo "Coluna 'address_number' já existe.\n";
        } else {
            echo "Erro ao adicionar address_number: " . $e->getMessage() . "\n";
        }
    }

    // 4. Adicionar address_neighborhood
    try {
        $pdo->exec("ALTER TABLE users ADD COLUMN address_neighborhood VARCHAR(100) NULL");
        echo "Coluna 'address_neighborhood' adicionada.\n";
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), 'Duplicate column') !== false) {
            echo "Coluna 'address_neighborhood' já existe.\n";
        } else {
            echo "Erro ao adicionar address_neighborhood: " . $e->getMessage() . "\n";
        }
    }

    // 5. Adicionar avatar
    try {
        $pdo->exec("ALTER TABLE users ADD COLUMN avatar VARCHAR(255) NULL");
        echo "Coluna 'avatar' adicionada.\n";
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), 'Duplicate column') !== false) {
            echo "Coluna 'avatar' já existe.\n";
        } else {
            echo "Erro ao adicionar avatar: " . $e->getMessage() . "\n";
        }
    }

    echo "Schema verificado com sucesso.\n";
} catch (Exception $e) {
    echo "Erro fatal: " . $e->getMessage() . "\n";
}
