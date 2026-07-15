<?php
/**
 * router.php — front controller (FASE 01, ciclo v7)
 *
 * Toda requisição que não é um arquivo/pasta real cai aqui via .htaccess (?route=...). Monta
 * autoload + conexão de banco + tabela de rotas, e despacha.
 */

declare(strict_types=1);

require_once __DIR__ . '/src/autoload.php';
require_once __DIR__ . '/src/config/db.php';

$router = new App\Router($pdo);

$router->get('/', [App\Controllers\PageController::class, 'home']);

$route = $_GET['route'] ?? '/';
if (!str_starts_with($route, '/')) {
    $route = '/' . $route;
}

$router->dispatch($route, $_SERVER['REQUEST_METHOD']);
