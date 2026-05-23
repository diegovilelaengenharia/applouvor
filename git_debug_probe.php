<?php
/**
 * Probe de Depuração do Git e Arquivos do Servidor Remoto
 */

header('Content-Type: text/plain; charset=utf-8');

echo "=== PROBE DE DEPURAÇÃO DO SERVIDOR ===\n\n";

echo "Caminho Absoluto Atual: " . __DIR__ . "\n";
echo "Data/Hora Servidor: " . date('Y-m-d H:i:s') . "\n\n";

echo "=== LISTA DE ARQUIVOS NA PASTA ATUAL ===\n";
$files = scandir(__DIR__);
foreach ($files as $file) {
    if ($file != '.' && $file != '..') {
        $isDir = is_dir(__DIR__ . '/' . $file) ? '[DIR]' : '[FILE]';
        echo "$isDir $file\n";
    }
}

echo "\n=== LISTA DE ARQUIVOS EM dashboard/ ===\n";
$dashboardPath = __DIR__ . '/dashboard';
if (is_dir($dashboardPath)) {
    $dashFiles = scandir($dashboardPath);
    foreach ($dashFiles as $file) {
        if ($file != '.' && $file != '..') {
            $isDir = is_dir($dashboardPath . '/' . $file) ? '[DIR]' : '[FILE]';
            echo "$isDir $file\n";
        }
    }
} else {
    echo "Pasta dashboard/ não existe neste caminho!\n";
}

echo "\n=== EXECUÇÃO DE COMANDOS DO SISTEMA ===\n";
if (function_exists('shell_exec')) {
    echo "git status:\n";
    $gitStatus = shell_exec('git status 2>&1');
    echo $gitStatus ? $gitStatus : "Nenhum resultado de git status.\n";
    
    echo "\ngit log -n 3:\n";
    $gitLog = shell_exec('git log -n 3 --oneline 2>&1');
    echo $gitLog ? $gitLog : "Nenhum resultado de git log.\n";
} else {
    echo "shell_exec está desativado neste PHP.\n";
}
