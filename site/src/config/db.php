<?php
/**
 * src/config/db.php — abre a conexão PDO usada pelo app (FASE 01, ciclo v7)
 *
 * Separado de config.php de propósito: config.php só define as constantes DB_ e APP_ (lido
 * também pelo diag.php, que precisa poder testar credenciais sem abrir uma conexão real de
 * app). Este arquivo assume que config.php já rodou e cria o $pdo que Router/Controllers usam.
 */

declare(strict_types=1);

require_once __DIR__ . '/config.php';

try {
    $pdo = new PDO(
        'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4',
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_TIMEOUT => 5,
        ]
    );
} catch (PDOException $e) {
    throw new RuntimeException('Falha ao conectar no banco: ' . $e->getMessage(), previous: $e);
}
