<?php
namespace App;

class Router {
    private array $routes = [];

    public function add(string $method, string $path, callable|array $handler) {
        // Converter path com parâmetros (ex: /membro/{id}) para regex
        $pattern = preg_replace('/\{([a-zA-Z0-9_]+)\}/', '(?P<\1>[a-zA-Z0-9_-]+)', $path);
        $pattern = '#^' . $pattern . '$#';
        
        $this->routes[] = [
            'method'  => $method,
            'pattern' => $pattern,
            'handler' => $handler
        ];
    }

    public function get(string $path, callable|array $handler) {
        $this->add('GET', $path, $handler);
    }

    public function post(string $path, callable|array $handler) {
        $this->add('POST', $path, $handler);
    }

    public function dispatch(string $uri, string $method) {
        // Remover query string do URI
        if (($pos = strpos($uri, '?')) !== false) {
            $uri = substr($uri, 0, $pos);
        }
        $uri = rtrim($uri, '/');
        if (empty($uri)) $uri = '/';

        foreach ($this->routes as $route) {
            if ($route['method'] === $method || $route['method'] === 'ANY') {
                if (preg_match($route['pattern'], $uri, $matches)) {
                    // Filtrar as chaves numéricas do array $matches
                    $params = array_filter($matches, 'is_string', ARRAY_FILTER_USE_KEY);
                    
                    $handler = $route['handler'];
                    
                    if (is_array($handler) && count($handler) === 2) {
                        $controllerName = $handler[0];
                        $methodName = $handler[1];
                        
                        if (class_exists($controllerName)) {
                            global $pdo;
                            $controller = new $controllerName($pdo);
                            if (method_exists($controller, $methodName)) {
                                return call_user_func_array([$controller, $methodName], $params);
                            }
                        }
                        http_response_code(500);
                        echo "Controller/Method not found: {$controllerName}::{$methodName}";
                        return;
                    } elseif (is_callable($handler)) {
                        return call_user_func_array($handler, $params);
                    }
                }
            }
        }

        // Rota não encontrada
        http_response_code(404);
        if (file_exists(__DIR__ . '/Views/app/404.php')) {
            require_once __DIR__ . '/Views/app/404.php';
        } else {
            echo "<h1>404 - Página Não Encontrada</h1>";
            echo "<p>A rota solicitada '{$uri}' não foi mapeada no roteador.</p>";
        }
    }
}
