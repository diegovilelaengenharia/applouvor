<?php

declare(strict_types=1);

namespace App\Controllers;

/**
 * Rota de prova da FASE 01: confirma Router → Controller → View sem depender do banco
 * (o smoke test de banco já é responsabilidade do diag.php, separado de propósito).
 */
class PageController extends Controller
{
    public function home(): void
    {
        $this->render('app/home', [
            'appVersion' => defined('APP_VERSION') ? APP_VERSION : 'dev',
        ]);
    }
}
