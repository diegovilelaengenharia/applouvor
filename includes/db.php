<?php
// includes/db.php

require_once 'config.php';

// Configurações do Banco de Dados

// FORÇANDO USO DO BANCO REMOTO (Hostinger)
// Detecta se está rodando localmente (XAMPP/localhost)
/*
if ($_SERVER['HTTP_HOST'] == 'localhost:8000' || $_SERVER['HTTP_HOST'] == 'localhost') {
    $host = 'localhost';
    $dbname = 'pibo_louvor'; // Atualizado para o novo nome do banco
    $username = 'root';
    $password = '';
} else {
*/
// Produção (Hostinger) - SEMPRE ATIVO AGORA
$host = 'srv1074.hstgr.io';
$dbname = 'u884436813_applouvor';
$username = 'u884436813_admin';
$password = 'Diego@159753';
// }

try {
    // Tenta conectar
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    // Se for erro de "Unknown database", informa
    if ($e->getCode() == 1049) {
        die("<div style='font-family:sans-serif; padding:20px; text-align:center;'>
                <h3>Banco de Dados não encontrado</h3>
                <p>O banco <b>$dbname</b> não existe no seu MySQL local.</p>
                <p>Por favor, crie este banco no phpMyAdmin e importe o arquivo <code>schema.sql</code>.</p>
             </div>");
    }
    // Se for erro de conexão recusada (MySQL off)
    if ($e->getCode() == 2002) {
        die("<div style='font-family:sans-serif; padding:20px; text-align:center;'>
                <h3>MySQL Parado</h3>
                <p>O servidor não conseguiu conectar ao banco de dados.</p>
                <p>👉 Abra o <b>XAMPP Control Panel</b> e inicie o serviço <b>MySQL</b>.</p>
             </div>");
    }

    die("Erro na conexão com o banco de dados: " . $e->getMessage());
}
