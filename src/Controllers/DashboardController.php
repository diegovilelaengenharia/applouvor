<?php
namespace App\Controllers;

use App\AuthMiddleware;

class DashboardController extends Controller
{
    /**
     * Exibe a página do Dashboard (GET /dashboard)
     */
    public function index()
    {
        // Exige que o usuário esteja logado
        AuthMiddleware::requireLogin();

        // Dados do usuário logado na sessão
        $userName = $_SESSION['user_name'] ?? 'Músico';
        $userRole = $_SESSION['user_role'] ?? 'user';

        $this->render('dashboard', [
            'userName' => $userName,
            'userRole' => $userRole
        ]);
    }
}
