<?php
// router.php - Front Controller Central

// 1. Carrega banco de dados e autoloader
require_once __DIR__ . '/src/config/db.php';

// 2. Instancia o roteador
$router = new App\Router();

// 3. Resolve a rota atual (Apache .htaccess vs PHP CLI Server)
if (php_sapi_name() === 'cli-server') {
    $filePath = __DIR__ . parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    if (file_exists($filePath) && !is_dir($filePath)) {
        return false; // Serve o arquivo estático diretamente
    }
    $route = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
} else {
    $route = $_GET['route'] ?? '/';
}

// Garante barra inicial na rota
if (strpos($route, '/') !== 0) {
    $route = '/' . $route;
}

$method = $_SERVER['REQUEST_METHOD'];

// ============================================================
// REGISTRO DE ROTAS
// ============================================================

// Rota raiz temporária
$router->get('/', function() {
    header('Content-Type: text/html; charset=utf-8');
    echo "<div style='font-family:sans-serif;text-align:center;padding-top:100px;'>";
    echo "<h1 style='color:#2E7EED;'>APP Louvor Novíssimo</h1>";
    echo "<p>Fundação do projeto estruturada com sucesso sob metodologia GSD! 🚀</p>";
    echo "<p>Banco de dados conectado: <b>" . DB_NAME . "</b></p>";
    echo "<a href='api/ping' style='color:#2E7EED;text-decoration:none;'>Testar API Ping</a>";
    echo "</div>";
});

// Rota de teste da API
$router->get('/api/ping', function() {
    header('Content-Type: application/json');
    echo json_encode([
        'status' => 'ok',
        'message' => 'Front Controller & Banco de Dados Ativos',
        'app' => CHURCH_NAME,
        'version' => APP_VERSION
    ]);
});

// 4. Despacha a requisição
$router->dispatch($route, $method);
