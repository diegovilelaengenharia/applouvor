<?php
// deploy.php - Custom Deployment Script
// Access via: https://vilela.eng.br/applouvor/deploy.php?secret=louvor2026

$secret = 'louvor2026';
$provided_secret = $_GET['secret'] ?? '';

if ($provided_secret !== $secret) {
    http_response_code(403);
    die('⛔ Acesso negado. Token inválido.');
}

// Configuration
$branch = 'main';
$logFile = 'deploy_log.txt';

// Helper function to log and print
function logMsg($msg) {
    global $logFile;
    $timestamp = date('Y-m-d H:i:s');
    $formattedMsg = "[$timestamp] $msg";
    file_put_contents($logFile, $formattedMsg . "\n", FILE_APPEND);
    echo "$msg\n";
}

// Start Output
header('Content-Type: text/plain; charset=utf-8');
echo "🚀 Iniciando Deploy para branch: $branch...\n";
echo "--------------------------------------------\n";

try {
    // 1. Check Directory
    $path = __DIR__;
    chdir($path);
    logMsg("Diretório de trabalho: $path");

    // 2. Git Status
    logMsg("Verificando status do Git...");
    $status = shell_exec("git status 2>&1");
    echo "$status\n";

    // 3. Git Fetch
    logMsg("Executando: git fetch origin $branch");
    $fetch = shell_exec("git fetch origin $branch 2>&1");
    echo "$fetch\n";

    // 4. Git Reset (Force Sync)
    // WARNING: This discards local changes on the server!
    logMsg("Executando: git reset --hard origin/$branch");
    $reset = shell_exec("git reset --hard origin/$branch 2>&1");
    echo "$reset\n";

    // 5. OpCache Reset (if applicable)
    if (function_exists('opcache_reset')) {
        opcache_reset();
        logMsg("🧹 PHP Opcache limpo.");
    }

    logMsg("✅ Deploy Concluído com Sucesso!");

} catch (Exception $e) {
    logMsg("❌ Erro Crítico: " . $e->getMessage());
    http_response_code(500);
}
?>
