<?php
namespace App\Controllers;

use App\AuthMiddleware;
use App\Models\Suggestion;

class SuggestionController extends Controller
{
    // Tela 31: Sugestões de Música
    public function index()
    {
        AuthMiddleware::requireLogin();
        $status = in_array($_GET['status'] ?? '', ['pending', 'approved', 'rejected'])
            ? $_GET['status'] : 'pending';

        $model    = new Suggestion($this->pdo);
        $sugestoes = $model->getByStatus($status);
        $counts    = $model->countByStatus();

        $this->render('sugestoes/index', compact('sugestoes', 'status', 'counts'));
    }

    // Formulário de sugestão
    public function create()
    {
        AuthMiddleware::requireLogin();
        $this->render('sugestoes/nova', []);
    }

    // Salvar sugestão
    public function store()
    {
        AuthMiddleware::requireLogin();
        csrf_verify();

        $title = trim($_POST['title'] ?? '');
        if (!$title) {
            $_SESSION['flash']['error'] = 'Informe o título da música.';
            $this->redirect('/sugestoes/nova');
        }

        $key   = trim($_POST['key'] ?? '');
        $notes = trim($_POST['notes'] ?? '');
        if ($key) {
            $notes = "Tom: {$key}" . ($notes ? " | {$notes}" : '');
        }

        $model = new Suggestion($this->pdo);
        $model->create([
            'user_id' => $_SESSION['user_id'],
            'title'   => $title,
            'artist'  => trim($_POST['artist'] ?? ''),
            'link'    => trim($_POST['link'] ?? ''),
            'notes'   => $notes,
        ]);

        $_SESSION['flash']['success'] = 'Sugestão enviada! A liderança vai avaliar em breve.';
        $this->redirect('/sugestoes');
    }

    // (admin) Aprovar
    public function approve(int $id)
    {
        AuthMiddleware::requireAdmin();
        csrf_verify();
        (new Suggestion($this->pdo))->updateStatus($id, 'approved');
        $_SESSION['flash']['success'] = 'Sugestão aprovada!';
        $this->redirect('/sugestoes');
    }

    // (admin) Recusar
    public function reject(int $id)
    {
        AuthMiddleware::requireAdmin();
        csrf_verify();
        (new Suggestion($this->pdo))->updateStatus($id, 'rejected');
        $_SESSION['flash']['success'] = 'Sugestão recusada.';
        $this->redirect('/sugestoes');
    }
}
