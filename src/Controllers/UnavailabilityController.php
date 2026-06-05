<?php

namespace App\Controllers;

use App\AuthMiddleware;
use App\Models\Unavailability;

class UnavailabilityController extends Controller
{
    /**
     * Tela 17: Indisponibilidades (lista + form).
     */
    public function index()
    {
        AuthMiddleware::requireLogin();

        $model = new Unavailability($this->pdo);
        $periods = $model->forUser((int) $_SESSION['user_id']);

        $this->render('perfil/indisponibilidades', ['periods' => $periods]);
    }

    /**
     * Tela 17: Salvar novo período (POST).
     */
    public function store()
    {
        AuthMiddleware::requireLogin();
        csrf_verify();

        $start = $_POST['start_date'] ?? '';
        $end   = $_POST['end_date'] ?? '';

        if (empty($start) || empty($end)) {
            $_SESSION['flash']['error'] = 'Informe as datas de início e fim.';
            $this->redirect('/indisponibilidades');
        }

        if ($end < $start) {
            $_SESSION['flash']['error'] = 'A data final não pode ser anterior à inicial.';
            $this->redirect('/indisponibilidades');
        }

        $model = new Unavailability($this->pdo);
        $model->create([
            'user_id'    => (int) $_SESSION['user_id'],
            'start_date' => $start,
            'end_date'   => $end,
            'reason'     => trim($_POST['reason'] ?? ''),
        ]);

        $_SESSION['flash']['success'] = 'Indisponibilidade registrada!';
        $this->redirect('/indisponibilidades');
    }

    /**
     * Tela 17: Remover período (POST).
     */
    public function destroy($id)
    {
        AuthMiddleware::requireLogin();
        csrf_verify();

        $model = new Unavailability($this->pdo);
        $model->deleteOwned((int) $id, (int) $_SESSION['user_id']);

        $_SESSION['flash']['success'] = 'Indisponibilidade removida.';
        $this->redirect('/indisponibilidades');
    }
}
