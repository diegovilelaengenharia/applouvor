<?php
// admin/debug_errors.php - Verificador de arquivos físicos
header('Content-Type: text/plain; charset=utf-8');

$base = '/home/u884436813/domains/vilela.eng.br/public_html/applouvor/';

$files = [
    'src/layout/modals/dashboard-modal.php',
    'src/layout/modals/notification-modal.php',
    'src/config/config.php',
    'src/config/db.php',
    'src/config/autoload.php',
    'src/helpers/auth.php',
    'src/classes/DB.php',
    'src/classes/DotEnv.php',
    'assets/css/main.css',
    'assets/js/main.js',
];

echo "=== VERIFICADOR DE ARQUIVOS (PARTE 2) ===\n";
echo "Base: $base\n\n";
foreach ($files as $f) {
    $full = $base . $f;
    $exists = file_exists($full);
    $size   = $exists ? filesize($full) . ' bytes' : 'N/A';
    $status = $exists ? "OK ($size)" : "!!! AUSENTE !!!";
    echo "$f => $status\n";
}

// Verificar hash do último commit no servidor
echo "\n=== GIT STATUS ===\n";
$gitMsg = $base . '.git/COMMIT_EDITMSG';
if (file_exists($gitMsg)) {
    echo "Último commit msg: " . file_get_contents($gitMsg) . "\n";
}
$gitHead = $base . '.git/refs/heads/main';
if (file_exists($gitHead)) {
    echo "HEAD main hash: " . file_get_contents($gitHead) . "\n";
}

// Erro fatal do admin/index.php - testar redirecionamento
echo "\n=== TESTE HTTP SELF ===\n";
echo "PHP_SELF: " . $_SERVER['PHP_SELF'] . "\n";
echo "HTTP_HOST: " . ($_SERVER['HTTP_HOST'] ?? 'N/A') . "\n";

echo "\n=== FIM ===\n";
