<?php
// admin/update_avatar_correto.php
// Script definitivo para rodar na raiz do admin
require_once '../includes/db.php';

try {
    // 1. Atualizar Avatar do Diego (ID 1)
    $stmt = $pdo->prepare("UPDATE users SET photo = 'avatar_diego.jpg' WHERE id = 1");
    $stmt->execute();
    echo "SUCESSO: Avatar atualizado para ID 1.<br>";
} catch (PDOException $e) {
    echo "ERRO SQL: " . $e->getMessage();
}
