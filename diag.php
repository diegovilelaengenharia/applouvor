<?php
// DIAGNOSTICO TEMPORARIO — remover apos debug
echo "<pre>\n";
echo "__DIR__: " . __DIR__ . "\n";
echo "DOCROOT: " . ($_SERVER['DOCUMENT_ROOT'] ?? 'nao definido') . "\n\n";

$configFile = __DIR__ . '/src/config/config.php';
echo "config.php path: $configFile\n";
echo "config.php exists: " . (file_exists($configFile) ? 'SIM' : 'NAO') . "\n\n";

// Le o config.php sem executar para ver o que tem
$raw = file_get_contents($configFile);
if (str_contains($raw, 'CRED_START')) {
    echo "STATUS CRED: PLACEHOLDER INTACTO (CI nao substituiu)\n";
    preg_match('/CRED_START.*?CRED_END/s', $raw, $m);
    echo "Bloco:\n" . ($m[0] ?? '') . "\n";
} elseif (str_contains($raw, "define('DB_PASS'")) {
    preg_match("/define\('DB_PASS',\s*'([^']*)'\)/", $raw, $m);
    $passLen = strlen($m[1] ?? '');
    preg_match("/define\('DB_HOST',\s*'([^']*)'\)/", $raw, $h);
    echo "STATUS CRED: INJETADAS PELO CI\n";
    echo "  DB_HOST: " . ($h[1] ?? '?') . "\n";
    echo "  DB_PASS len: $passLen\n";
} else {
    echo "STATUS CRED: CONTEUDO INESPERADO\n";
}

echo "\n--- Teste via config.php ---\n";
try {
    require_once __DIR__ . '/src/config/config.php';
    echo "DB_HOST definido: " . (defined('DB_HOST') ? DB_HOST : '[nao]') . "\n";
    echo "DB_PASS definido: " . (defined('DB_PASS') ? (strlen(DB_PASS) > 0 ? 'SIM (len=' . strlen(DB_PASS) . ')' : '[VAZIO]') : '[nao]') . "\n";

    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
        DB_USER, DB_PASS,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
    echo "CONEXAO: OK\n";
} catch (PDOException $e) {
    echo "CONEXAO FALHOU: [" . $e->getCode() . "] " . $e->getMessage() . "\n";
} catch (Throwable $e) {
    echo "ERRO GERAL: " . $e->getMessage() . "\n";
}

echo "</pre>\n";
