<?php
// deploy.php - Webhook Handler for GitHub
// Access this via: https://vilela.eng.br/applouvor/deploy.php?secret=YOUR_SECRET (Configuration needed)

// 1. Configuração
$secret = 'louvor2026'; // Defina uma senha simples para o webhook
$branch = 'main';

// 2. Verificação de Segurança
if (!isset($_GET['secret']) || $_GET['secret'] !== $secret) {
    http_response_code(403);
    die("⛔ Acesso Negado.");
}

// 3. Header para Streaming de Log
header('Content-Type: text/plain');
header('X-Accel-Buffering: no'); // Para Nginx não fazer buffer

echo "🚀 Iniciando Deploy Automático...\n";
echo "---------------------------------\n";

// 4. Verificar se exec() está habilitado
if (!function_exists('exec')) {
    echo "❌ ERRO CRÍTICO: A função 'exec()' está desabilitada neste servidor.\n";
    echo "Solução: O PHP não pode rodar comandos git.\n";
    echo "Alternativa: Use a funcionalidade 'Git' do painel da Hostinger para configurar o Webhook nativo.\n";
    exit;
}

// 5. Executar Git Pull
$commands = [
    'echo $PWD',
    'whoami',
    'git pull origin ' . $branch . ' 2>&1',
    'git status 2>&1'
];

foreach ($commands as $cmd) {
    echo "\n> $cmd\n";
    $output = [];
    $return_var = 0;
    exec($cmd, $output, $return_var);
    foreach ($output as $line) {
        echo "$line\n";
    }
    if ($return_var !== 0) {
        echo "⚠️ Erro ao executar comando (Código $return_var)\n";
    }
}

echo "\n---------------------------------\n";
echo "✅ Script finalizado.\n";
?>
