// deploy.php - Custom Deployment Script
// Access via: https://vilela.eng.br/applouvor/deploy.php?secret=louvor2026

// Enable Error Reporting
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Disable Time Limit
set_time_limit(0); 

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
    flush(); // Force output
}

function execCmd($cmd) {
    logMsg("Executando: $cmd");
    $output = [];
    $return_var = 0;
    exec($cmd . " 2>&1", $output, $return_var);
    
    foreach ($output as $line) {
        echo "  > $line\n";
    }
    
    if ($return_var !== 0) {
        logMsg("⚠️ Comando retornou erro (código $return_var)");
        return false;
    }
    return true;
}

// Start Output
header('Content-Type: text/plain; charset=utf-8');
while (ob_get_level()) ob_end_flush(); // Disable output buffering

echo "🚀 Iniciando Deploy para branch: $branch...\n";
echo "--------------------------------------------\n";

try {
    // 1. Check Directory
    $path = __DIR__;
    chdir($path);
    logMsg("Diretório de trabalho: $path");
    
    // Check if .git exists
    if (!is_dir('.git')) {
        throw new Exception("Diretório .git não encontrado. Não é um repositório git.");
    }

    // 2. Git Status
    if (!execCmd("git status")) {
        throw new Exception("Falha ao verificar status do git. Verifique permissões ou instalação do git.");
    }

    // 3. Git Fetch
    if (!execCmd("git fetch origin $branch")) {
        throw new Exception("Falha ao fazer git fetch.");
    }

    // 4. Git Reset (Force Sync)
    // WARNING: This discards local changes on the server!
    if (!execCmd("git reset --hard origin/$branch")) {
        throw new Exception("Falha ao fazer git reset.");
    }

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
