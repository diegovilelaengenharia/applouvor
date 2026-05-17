<?php
require_once '../includes/db.php';

try {
    // 1. Adicionar coluna photo se não existir
    $pdo->exec("ALTER TABLE users ADD COLUMN photo VARCHAR(255) DEFAULT NULL");
    echo "Coluna 'photo' adicionada com sucesso.<br>";
} catch (PDOException $e) {
    // Se der erro de duplicate column, tudo bem
    if (strpos($e->getMessage(), 'Duplicate column menu') !== false) {
        echo "Coluna já existe.<br>";
    } else {
        echo "Info/Erro: " . $e->getMessage() . "<br>";
    }
}

try {
    // 2. Atualizar Avatar do Diego
    $stmt = $pdo->prepare("UPDATE users SET photo = 'avatar_diego.jpg' WHERE id = 1"); // Assumindo ID 1
    $stmt->execute();
    echo "Avatar vinculado ao ID 1.<br>";
} catch (PDOException $e) {
    echo "Erro Update: " . $e->getMessage();
}
