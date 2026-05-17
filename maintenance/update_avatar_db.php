<?php
require_once 'includes/db.php';

try {
    // 1. Adicionar coluna 'photo' na tabela users se não existir
    echo "<h2>Updating Schema (Users table)...</h2>";
    try {
        $pdo->exec("ALTER TABLE users ADD COLUMN photo VARCHAR(255) AFTER phone");
        echo "Column 'photo' added successfully.<br>";
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), 'Duplicate column') !== false) {
            echo "Column 'photo' already exists.<br>";
        } else {
            echo "Error adding column: " . $e->getMessage() . "<br>";
        }
    }

    // 2. Atualizar Avatar do Diego
    echo "<h2>Updating Diego's Avatar...</h2>";
    // Buscar id por nome aproximado
    $stmt = $pdo->prepare("SELECT id, name FROM users WHERE name LIKE ? OR name LIKE ? LIMIT 1");
    $stmt->execute(['%Diego%', '%Admin%']); // Assumindo que Diego pode ser o admin ou ter nome Diego
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user) {
        $stmtUpdate = $pdo->prepare("UPDATE users SET photo = ? WHERE id = ?");
        $stmtUpdate->execute(['avatar_diego_real.jpg', $user['id']]);
        echo "Updated avatar for user: " . $user['name'] . " (ID: " . $user['id'] . ")<br>";
    } else {
        echo "User 'Diego' not found. Creating him...<br>";
        // Se não achar, cria o Diego
        $pdo->prepare("INSERT INTO users (name, role, instrument, phone, password, photo) VALUES (?, ?, ?, ?, ?, ?)")
            ->execute(['Diego', 'admin', 'Violão', '35 98452-9577', 'applouvor', 'avatar_diego_real.jpg']);
        echo "User Diego created.<br>";
    }

    echo "<h2>Done!</h2>";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
