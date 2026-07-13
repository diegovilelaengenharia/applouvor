<?php

namespace App\Controllers;

use App\AuthMiddleware;

/**
 * Páginas estáticas / utilitárias (Wave 3).
 */
class PageController extends Controller
{
    /**
     * Tela 38: Ajuda / FAQ (logado).
     */
    public function ajuda()
    {
        AuthMiddleware::requireLogin();
        $this->render('app/ajuda');
    }

    /**
     * Tela 37: Onboarding / Guia do Músico (logado).
     */
    public function onboarding()
    {
        AuthMiddleware::requireLogin();
        $this->render('app/onboarding');
    }

    /**
     * Tela 39: Offline (PWA) — público (sem conexão não há sessão garantida).
     */
    public function offline()
    {
        $this->render('app/offline');
    }
}
