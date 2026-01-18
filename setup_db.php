<?php
require_once 'includes/config.php';

// Conexão sem especificar banco para poder criar
$host = 'localhost';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    echo "<h3>Configuração do Banco de Dados</h3>";

    // Criar Banco
    $dbname = 'pibo_louvor';
    $pdo->exec("CREATE DATABASE IF NOT EXISTS `$dbname` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    echo "✅ Banco de dados '$dbname' verificado/criado.<br>";

    $pdo->exec("USE `$dbname`");

    // Importar Schema
    $sql = file_get_contents('schema.sql');

    // Simples parser de SQL
    $sql = preg_replace('/--.*$/m', '', $sql); // Remove comentários
    $statements = array_filter(array_map('trim', explode(';', $sql)));

    foreach ($statements as $stmt) {
        if (!empty($stmt)) {
            $pdo->exec($stmt);
        }
    }
    echo "✅ Tabelas criadas e dados iniciais importados.<br>";
    echo "<br><b>Tudo pronto!</b> <a href='index.php'>Ir para o Login</a>";
} catch (PDOException $e) {
    echo "❌ Erro: " . $e->getMessage();
    echo "<br><br>Verifique se o MySQL está rodando no XAMPP.";
}
