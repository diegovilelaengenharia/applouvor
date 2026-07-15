<?php
/**
 * diag.php — smoke test de infra (FASE 00, ciclo v7)
 *
 * Prova que o PHP em produção enxerga as env vars cadastradas no painel Hostinger e consegue
 * abrir conexão com o MySQL. Responde JSON. NUNCA expõe DB_PASS nem qualquer segredo — nem no
 * caminho de sucesso, nem no de erro (as mensagens de PDOException do MySQL não incluem a
 * senha em texto, só "using password: yes/no", mas mesmo assim não ecoamos DB_PASS aqui).
 *
 * Critério de sucesso da FASE 00: esta página responde {"db":"OK", ...} em
 * https://louvor.vilela.eng.br/diag.php logo após um deploy normal via GitHub Actions,
 * sem nenhum passo manual.
 */

declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');

$resposta = [
    'db'         => 'ERRO',
    'checked_at' => date('c'),
];

// A leitura de config.php também pode falhar de propósito (env var obrigatória ausente —
// ver app_env_required()). Isso conta como diagnóstico "ERRO", não como fatal error cru:
// o smoke test tem que sempre devolver JSON legível, nunca uma página de erro do PHP.
try {
    require_once __DIR__ . '/src/config/config.php';
    $resposta['app_env'] = APP_ENV;

    $pdo = new PDO(
        'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4',
        DB_USER,
        DB_PASS,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_TIMEOUT => 5]
    );
    $pdo->query('SELECT 1');

    $resposta['db']   = 'OK';
    $resposta['host'] = DB_HOST;
    http_response_code(200);
} catch (Throwable $e) {
    $resposta['db']      = 'ERRO';
    $resposta['message'] = $e->getMessage();
    http_response_code(500);
}

echo json_encode($resposta, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
