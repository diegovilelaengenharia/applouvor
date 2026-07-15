<?php

declare(strict_types=1);

namespace App;

use PDO;

/**
 * Front controller mínimo: casa método+path contra rotas registradas, resolve params
 * nomeados via regex ({id} etc.) e instancia o Controller com o PDO compartilhado.
 */
class Router
{
    /** @var array<int, array{method: string, pattern: string, handler: array|callable}> */
    private array $routes = [];

    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function get(string $path, array|callable $handler): void
    {
        $this->add('GET', $path, $handler);
    }

    public function post(string $path, array|callable $handler): void
    {
        $this->add('POST', $path, $handler);
    }

    private function add(string $method, string $path, array|callable $handler): void
    {
        $pattern = preg_replace('#\{([a-zA-Z_][a-zA-Z0-9_]*)\}#', '(?P<$1>[^/]+)', $path);
        $this->routes[] = [
            'method' => $method,
            'pattern' => '#^' . $pattern . '$#',
            'handler' => $handler,
        ];
    }

    public function dispatch(string $uri, string $method): void
    {
        $path = rtrim(parse_url($uri, PHP_URL_PATH) ?: '/', '/');
        if ($path === '') {
            $path = '/';
        }

        foreach ($this->routes as $route) {
            if ($route['method'] !== $method || !preg_match($route['pattern'], $path, $matches)) {
                continue;
            }

            $params = array_filter($matches, 'is_string', ARRAY_FILTER_USE_KEY);
            $handler = $route['handler'];

            if (is_callable($handler)) {
                call_user_func($handler, ...$params);
                return;
            }

            [$controllerClass, $action] = $handler;
            $controller = new $controllerClass($this->pdo);
            call_user_func([$controller, $action], ...$params);
            return;
        }

        http_response_code(404);
        echo '404 — rota não encontrada';
    }
}
