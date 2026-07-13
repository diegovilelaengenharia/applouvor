<?php
// src/helpers/rate_limit.php

/**
 * Cria a tabela de tentativas de login se ela não existir
 */
function rateLimitEnsureTable(\PDO $pdo): void {
    $pdo->exec("CREATE TABLE IF NOT EXISTS login_attempts (
        ip       VARCHAR(45)  NOT NULL,
        attempts TINYINT      NOT NULL DEFAULT 1,
        since    INT UNSIGNED NOT NULL,
        blocked  INT UNSIGNED NOT NULL DEFAULT 0,
        PRIMARY KEY (ip)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
}

/**
 * Verifica se um IP está bloqueado temporariamente
 */
function rateLimitCheck(\PDO $pdo, string $ip): array {
    rateLimitEnsureTable($pdo);

    $now = time();

    // Limpar tentativas antigas (mais de 2 horas)
    $pdo->prepare("DELETE FROM login_attempts WHERE since < ?")->execute([$now - 7200]);

    $stmt = $pdo->prepare("SELECT * FROM login_attempts WHERE ip = ?");
    $stmt->execute([$ip]);
    $row = $stmt->fetch();

    if (!$row) {
        return ['blocked' => false, 'attempts' => 0];
    }

    // Se estiver no período de bloqueio ativo
    if ($row['blocked'] > $now) {
        return ['blocked' => true, 'wait' => $row['blocked'] - $now, 'attempts' => $row['attempts']];
    }

    // Janela de rate limit expirou (mais de 60s desde a primeira tentativa nesta janela)
    if ($now - $row['since'] > 60) {
        $pdo->prepare("DELETE FROM login_attempts WHERE ip = ?")->execute([$ip]);
        return ['blocked' => false, 'attempts' => 0];
    }

    return ['blocked' => $row['attempts'] >= 5, 'attempts' => $row['attempts']];
}

/**
 * Registra uma tentativa de login (sucesso remove, falha incrementa)
 */
function rateLimitRecord(\PDO $pdo, string $ip, bool $success): void {
    rateLimitEnsureTable($pdo);

    $now = time();

    if ($success) {
        $pdo->prepare("DELETE FROM login_attempts WHERE ip = ?")->execute([$ip]);
        return;
    }

    // Insere ou atualiza. No 5º erro consecutivo, bloqueia por 60s
    $pdo->prepare("INSERT INTO login_attempts (ip, attempts, since, blocked)
        VALUES (?, 1, ?, 0)
        ON DUPLICATE KEY UPDATE
            attempts = attempts + 1,
            blocked  = IF(attempts + 1 >= 5, ? + 60, 0)
    ")->execute([$ip, $now, $now]);
}
