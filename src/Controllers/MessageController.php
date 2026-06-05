<?php
namespace App\Controllers;

use App\AuthMiddleware;

class MessageController extends Controller
{
    // Tela 19: Mensagens
    public function index()
    {
        AuthMiddleware::requireLogin();
        $this->render('app/mensagens', []);
    }
}
