<?php
// Script de verificação de usuários
require_once 'includes/no-cache.php';
require_once 'includes/db.php';

echo "<h1>Lista de Usuários no Banco de Dados</h1>";
echo "<p>Ambiente: " . (defined('APP_ENV') ? APP_ENV : 'N/A') . "</p>";
echo "<p>Host DB: " . (defined('DB_HOST') ? DB_HOST : 'N/A') . "</p>";

try {
    $stmt = $pdo->query("SELECT id, name, role, instrument, created_at FROM users ORDER BY name ASC");
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (empty($users)) {
        echo "<p><strong>Nenhum usuário encontrado!</strong></p>";
    } else {
        echo "<table border='1' cellpadding='10'>";
        echo "<tr><th>ID</th><th>Nome</th><th>Função</th><th>Instrumento</th><th>Criado em</th></tr>";
        foreach ($users as $user) {
            echo "<tr>";
            echo "<td>" . $user['id'] . "</td>";
            echo "<td>" . htmlspecialchars($user['name']) . "</td>";
            echo "<td>" . htmlspecialchars($user['role']) . "</td>";
            echo "<td>" . htmlspecialchars($user['instrument'] ?? '-') . "</td>";
            echo "<td>" . $user['created_at'] . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
} catch (PDOException $e) {
    echo "<p style='color:red'>Erro ao consultar banco: " . $e->getMessage() . "</p>";
}
