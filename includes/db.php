<?php
// includes/db.php

require_once 'config.php';

try {
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]
    );

    // Inicializa Query Builder com a conexão
    App\DB::setConnection($pdo);

} catch (PDOException $e) {
    if ($e->getCode() == 1049) {
        die("<div style='font-family:sans-serif;padding:20px;text-align:center'><h3>Banco de Dados não encontrado</h3><p>O banco <b>" . DB_NAME . "</b> não existe. Crie-o e importe o <code>schema.sql</code>.</p></div>");
    }
    if ($e->getCode() == 2002) {
        die("<div style='font-family:sans-serif;padding:20px;text-align:center'><h3>MySQL Parado</h3><p>Inicie o serviço MySQL no XAMPP Control Panel.</p></div>");
    }
    if (defined('APP_DEBUG') && APP_DEBUG) {
        die("Erro de conexão: " . $e->getMessage());
    }
    die("Erro de conexão com o banco de dados. Tente novamente mais tarde.");
}
