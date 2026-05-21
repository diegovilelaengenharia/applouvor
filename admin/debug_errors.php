<?php
// admin/debug_errors.php - Teste final pós-deploy
header('Content-Type: text/plain; charset=utf-8');

$base = '/home/u884436813/domains/vilela.eng.br/public_html/applouvor/';

echo "=== TESTE PÓS-DEPLOY ===\n";
echo "admin/dashboard_data.php existe: " . (file_exists($base . 'admin/dashboard_data.php') ? 'SIM (' . filesize($base . 'admin/dashboard_data.php') . ' bytes)' : 'NÃO!!!') . "\n";
echo "admin/debug_errors.php existe: " . (file_exists($base . 'admin/debug_errors.php') ? 'SIM' : 'NAO (esperado)') . "\n\n";

echo "HEAD servidor: ";
$head = $base . '.git/refs/heads/main';
echo file_exists($head) ? trim(file_get_contents($head)) : 'N/A';
echo "\nHEAD esperado: c4aff86...\n\n";

echo "=== FIM ===\n";
