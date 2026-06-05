<?php
// router.php - Front Controller Central

// 1. Carrega banco de dados e autoloader
require_once __DIR__ . '/src/config/db.php';

// Carrega os helpers globais de segurança (Fase 2)
require_once __DIR__ . '/src/helpers/auth.php';
require_once __DIR__ . '/src/helpers/csrf.php';
require_once __DIR__ . '/src/helpers/rate_limit.php';

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

// Tela de Login e Processamento
$router->get('/', [App\Controllers\LoginController::class, 'index']);
$router->post('/login', [App\Controllers\LoginController::class, 'login']);

// Logout
$router->get('/logout', [App\Controllers\LoginController::class, 'logout']);

// Dashboard
$router->get('/dashboard', [App\Controllers\DashboardController::class, 'index']);

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
