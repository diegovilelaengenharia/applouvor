<?php
// admin/debug_errors.php
header('Content-Type: text/html; charset=utf-8');

// Força a exibição de todos os erros
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

echo "<h2>Iniciando Diagnóstico de Erros no APP Louvor</h2>";
echo "Diretório de execução: " . getcwd() . "<br>";
echo "PHP Version: " . phpversion() . "<br><br>";

function test_include($label, $path) {
    echo "Incluindo <b>$label</b> ($path)... ";
    if (!file_exists($path)) {
        echo "<span style='color:red;font-weight:bold;'>ARQUIVO NÃO EXISTE!</span><br>";
        return false;
    }
    
    try {
        require_once $path;
        echo "<span style='color:green;font-weight:bold;'>OK</span><br>";
        return true;
    } catch (Throwable $e) {
        echo "<span style='color:red;font-weight:bold;'>FALHOU!</span><br>";
        echo "Mensagem: " . $e->getMessage() . "<br>";
        echo "Linha: " . $e->getLine() . " no arquivo " . $e->getFile() . "<br>";
        echo "Trace:<pre>" . $e->getTraceAsString() . "</pre><br>";
        return false;
    }
}

// Testar autoloading e config primeiro
echo "<h3>1. Testando Dependências Estruturais</h3>";
$configOk = test_include("Config (config.php)", "../src/config/config.php");

if ($configOk) {
    echo "<br>Ambiente detectado: " . (defined('APP_ENV') ? APP_ENV : 'Não definido') . "<br>";
    echo "Modo debug: " . (defined('APP_DEBUG') ? (APP_DEBUG ? 'Ativo' : 'Inativo') : 'Não definido') . "<br>";
    echo "DB Host: " . (defined('DB_HOST') ? DB_HOST : 'Não definido') . "<br>";
    echo "DB Name: " . (defined('DB_NAME') ? DB_NAME : 'Não definido') . "<br>";
}

echo "<h3>2. Testando Demais Componentes</h3>";
test_include("Autenticação (auth.php)", "../src/helpers/auth.php");
test_include("Banco de Dados (db.php)", "../src/config/db.php");
test_include("Layout Base (layout.php)", "../src/layout/layout.php");
test_include("Cards do Dashboard (dashboard_cards.php)", "../src/layout/dashboard_cards.php");
test_include("Renderizador (dashboard_render.php)", "../src/layout/dashboard_render.php");

echo "<h3>3. Testando Carregamento de Dados</h3>";
test_include("Dados do Dashboard (dashboard_data.php)", "dashboard_data.php");

echo "<br><b>Fim do Diagnóstico.</b>";
