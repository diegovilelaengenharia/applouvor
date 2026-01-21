<?php
// admin/_update_avatar_temp.php
// Ajuste de caminho: se este arquivo está em /admin, e includes está na raiz -> ../includes/db.php
// Se includes estiver dentro de admin -> includes/db.php
// Vou testar ../includes/db.php primeiro pois é o padrão MVC simples
if (file_exists('../includes/db.php')) {
    require_once '../includes/db.php';
} elseif (file_exists('includes/db.php')) {
    require_once 'includes/db.php';
} else {
    die("Não encontrei includes/db.php");
}

try {
    // 1. Atualizar Avatar do Diego (ID 1)
    $stmt = $pdo->prepare("UPDATE users SET photo = 'avatar_diego.jpg' WHERE id = 1");
    $stmt->execute();
    echo "Avatar atualizado com sucesso! (ID 1 vinculado a avatar_diego.jpg)<br>";
} catch (PDOException $e) {
    echo "Erro: " . $e->getMessage();
}
