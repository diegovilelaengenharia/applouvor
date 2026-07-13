<?php
namespace App\Controllers;

use App\AuthMiddleware;

class MinisterioController extends Controller
{
    // Tela 32: Ministério / Quem Somos
    public function index()
    {
        AuthMiddleware::requireLogin();
        $stmt = $this->pdo->query("SELECT COUNT(*) FROM users");
        $totalMembros = (int)$stmt->fetchColumn();
        $this->render('ministerio/index', compact('totalMembros'));
    }
}
