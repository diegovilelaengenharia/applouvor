<?php
namespace App\Controllers;

use PDO;

abstract class Controller {
    protected PDO $pdo;

    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
    }

    /**
     * Helper to render a view
     */
    protected function render(string $viewPath, array $data = []) {
        // Extrai as variáveis para a view
        extract($data);

        $fullPath = __DIR__ . '/../Views/' . $viewPath . '.php';

        if (file_exists($fullPath)) {
            require $fullPath;
        } else {
            throw new \Exception("View not found: " . $viewPath);
        }
    }

    /**
     * Return JSON response
     */
    protected function json(array $data, int $status = 200) {
        http_response_code($status);
        header('Content-Type: application/json');
        echo json_encode($data);
        exit;
    }

    /**
     * Redirect
     */
    protected function redirect(string $url) {
        header("Location: $url");
        exit;
    }
}
