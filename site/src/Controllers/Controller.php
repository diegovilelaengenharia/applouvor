<?php

declare(strict_types=1);

namespace App\Controllers;

use PDO;

abstract class Controller
{
    protected PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    protected function render(string $view, array $data = []): void
    {
        $path = __DIR__ . '/../Views/' . $view . '.php';
        if (!is_file($path)) {
            throw new \RuntimeException("View não encontrada: {$view}");
        }

        extract($data, EXTR_SKIP);
        require $path;
    }

    protected function json(array $data, int $status = 200): void
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
    }

    protected function redirect(string $url): void
    {
        header('Location: ' . $url);
    }
}
