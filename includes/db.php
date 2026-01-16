<?php
// Configurações do Banco de Dados
$host = 'localhost';
$dbname = 'u884436813_applouvor';
$username = 'u884436813_admin';
$password = 'Diego@159753';

// Para desenvolvimento local (se diferente da produção)
// $host = 'localhost';
// $dbname = 'louvor_pib_local';
// $username = 'root';
// $password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Erro na conexão com o banco de dados: " . $e->getMessage());
}
