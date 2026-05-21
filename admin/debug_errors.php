<?php
// admin/debug_errors.php
header('Content-Type: text/plain; charset=utf-8');

// Força a exibição de todos os erros
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

echo "=== INICIANDO DIAGNÓSTICO DE ERROS ===\n";
echo "Diretório de execução: " . getcwd() . "\n";
echo "PHP Version: " . phpversion() . "\n\n";

function test_include($label, $path) {
    echo "Incluindo $label ($path)... ";
    if (!file_exists($path)) {
        echo "ERRO: ARQUIVO NÃO EXISTE!\n";
        return false;
    }
    
    try {
        require_once $path;
        echo "OK\n";
        return true;
    } catch (Throwable $e) {
        echo "FALHOU!\n";
        echo "Mensagem: " . $e->getMessage() . "\n";
        echo "Linha: " . $e->getLine() . " no arquivo " . $e->getFile() . "\n";
        echo "Trace:\n" . $e->getTraceAsString() . "\n\n";
        return false;
    }
}

echo "--- 1. Testando Dependências Estruturais ---\n";
$configOk = test_include("Config (config.php)", "../src/config/config.php");

if ($configOk) {
    echo "Ambiente detectado: " . (defined('APP_ENV') ? APP_ENV : 'Não definido') . "\n";
    echo "Modo debug: " . (defined('APP_DEBUG') ? (APP_DEBUG ? 'Ativo' : 'Inativo') : 'Não definido') . "\n";
    echo "DB Host: " . (defined('DB_HOST') ? DB_HOST : 'Não definido') . "\n";
    echo "DB Name: " . (defined('DB_NAME') ? DB_NAME : 'Não definido') . "\n\n";
}

echo "--- 2. Testando Demais Componentes ---\n";
test_include("Autenticação (auth.php)", "../src/helpers/auth.php");
test_include("Banco de Dados (db.php)", "../src/config/db.php");
test_include("Layout Base (layout.php)", "../src/layout/layout.php");
test_include("Cards do Dashboard (dashboard_cards.php)", "../src/layout/dashboard_cards.php");
test_include("Renderizador (dashboard_render.php)", "../src/layout/dashboard_render.php");

echo "\n--- 3. Testando Carregamento de Dados ---\n";
test_include("Dados do Dashboard (dashboard_data.php)", "dashboard_data.php");

echo "\n=== FIM DO DIAGNÓSTICO ===\n";
