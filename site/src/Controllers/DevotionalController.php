<?php
namespace App\Controllers;

use App\AuthMiddleware;
use App\Models\Devotional;

class DevotionalController extends Controller
{
    // Tela 29: Lista de Devocionais
    public function index()
    {
        AuthMiddleware::requireLogin();

        $userId = (int) $_SESSION['user_id'];
        $model  = new Devotional($this->pdo);
        $devocionais = $model->getAll();
        $streak      = $model->getUserStreak($userId);

        // Marca quais já foram lidos pelo usuário
        foreach ($devocionais as &$d) {
            $d['has_read'] = $model->hasRead((int) $d['id'], $userId);
        }

        $this->render('vida-espiritual/devocionais', [
            'devocionais' => $devocionais,
            'streak'      => $streak,
        ]);
    }

    // Tela 30: Detalhe do Devocional
    public function show(int $id)
    {
        AuthMiddleware::requireLogin();

        $userId = (int) $_SESSION['user_id'];
        $model  = new Devotional($this->pdo);
        $devocional = $model->getById($id);

        if (!$devocional) {
            $_SESSION['flash']['error'] = 'Devocional não encontrado.';
            $this->redirect('/devocionais');
        }

        $comments = $model->getComments($id);
        $hasRead  = $model->hasRead($id, $userId);

        $this->render('vida-espiritual/devocional', [
            'devocional' => $devocional,
            'comments'   => $comments,
            'hasRead'    => $hasRead,
        ]);
    }

    // Marcar devocional como lido
    public function markRead(int $id)
    {
        AuthMiddleware::requireLogin();
        csrf_verify();

        $model = new Devotional($this->pdo);
        $model->markRead($id, (int) $_SESSION['user_id']);

        $_SESSION['flash']['success'] = 'Devocional marcado como lido! ✓';
        $this->redirect("/devocionais/{$id}");
    }

    // Tela 50: Comentar no Devocional
    public function storeComment(int $id)
    {
        AuthMiddleware::requireLogin();
        csrf_verify();

        $comment = trim($_POST['comment'] ?? '');
        if ($comment) {
            $model = new Devotional($this->pdo);
            $model->addComment($id, (int) $_SESSION['user_id'], $comment);
        }

        $this->redirect("/devocionais/{$id}#comentarios");
    }
}
