<?php
// admin/debug_errors.php - Verificador de arquivos físicos
header('Content-Type: text/plain; charset=utf-8');

$base = '/home/u884436813/domains/vilela.eng.br/public_html/applouvor/';

$files = [
    'admin/index.php',
    'admin/sidebar.php',
    'admin/lider.php',
    'src/layout/layout.php',
    'src/layout/dashboard_cards.php',
    'src/layout/dashboard_render.php',
    'src/layout/head.php',
    'src/layout/bottom-nav.php',
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

echo "=== VERIFICADOR DE ARQUIVOS FÍSICOS ===\n";
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
$gitDir = $base . '.git/COMMIT_EDITMSG';
if (file_exists($gitDir)) {
    echo "Último commit: " . file_get_contents($gitDir);
} else {
    echo ".git/COMMIT_EDITMSG não encontrado!\n";
}

echo "\n=== FIM ===\n";
