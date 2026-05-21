<?php
// admin/debug_errors.php - Verificar deploy + admin/index.php response
header('Content-Type: text/plain; charset=utf-8');

$base = '/home/u884436813/domains/vilela.eng.br/public_html/applouvor/';

echo "=== GIT HEAD ===\n";
$gitHead = $base . '.git/refs/heads/main';
if (file_exists($gitHead)) {
    echo "HEAD main hash no servidor: " . trim(file_get_contents($gitHead)) . "\n";
    echo "HEAD esperado (local):      93650d8c1d158c... (ver acima)\n";
} else {
    echo ".git/COMMIT_EDITMSG não encontrado!\n";
}

echo "\n=== LENDO admin/index.php (primeiras 20 linhas) ===\n";
$adminFile = $base . 'admin/index.php';
if (file_exists($adminFile)) {
    $lines = file($adminFile);
    for ($i = 0; $i < min(20, count($lines)); $i++) {
        echo ($i+1) . ": " . $lines[$i];
    }
    echo "\n... total: " . count($lines) . " linhas\n";
} else {
    echo "!!! admin/index.php NAO EXISTE !!!\n";
}

echo "\n=== LENDO src/layout/layout.php (linha 24) ===\n";
$layoutFile = $base . 'src/layout/layout.php';
if (file_exists($layoutFile)) {
    $lines = file($layoutFile);
    echo "Linha 24: " . ($lines[23] ?? 'não encontrada') . "\n";
    echo "Versão do arquivo: ";
    // Buscar "require_once" na área relevante
    for ($i = 20; $i < min(30, count($lines)); $i++) {
        echo ($i+1) . ": " . $lines[$i];
    }
}

echo "\n=== FIM ===\n";
