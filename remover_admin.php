<?php
// Script para remover o usuário Admin padrão
require_once 'includes/db.php';

try {
    $stmt = $pdo->prepare("DELETE FROM users WHERE name = 'Admin'");
    $stmt->execute();

    echo "✅ Usuário 'Admin' removido com sucesso!<br><br>";
    echo "Agora você pode fazer login como:<br>";
    echo "<strong>Nome:</strong> Diego<br>";
    echo "<strong>Senha:</strong> 9577<br><br>";
    echo "<a href='index.php'>Ir para Login</a>";
} catch (PDOException $e) {
    echo "❌ Erro: " . $e->getMessage();
}
