<?php
// Configurações do Banco de Dados
$host = 'localhost';
$dbname = 'u604639433_louvor_pib'; // Nome sugerido para Hostinger
$username = 'root'; // Alterar na hospedagem
$password = ''; // Alterar na hospedagem

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
?>
