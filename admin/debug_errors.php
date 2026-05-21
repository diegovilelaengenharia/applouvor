<?php
// admin/debug_errors.php
header('Content-Type: text/plain; charset=utf-8');

echo "=== INICIANDO DIAGNÓSTICO DE ERROS ===\n";
echo "Diretório de execução: " . getcwd() . "\n";
echo "PHP Version: " . phpversion() . "\n\n";

echo "--- Conteúdo do Topo de src/layout/layout.php ---\n";
$layoutFile = '../src/layout/layout.php';
if (file_exists($layoutFile)) {
    $lines = file($layoutFile);
    for ($i = 0; $i < min(40, count($lines)); $i++) {
        echo ($i + 1) . ": " . $lines[$i];
    }
} else {
    echo "ERRO: O arquivo layout.php não existe!\n";
}

echo "\n=== FIM DO DIAGNÓSTICO ===\n";
