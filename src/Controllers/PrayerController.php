<?php
namespace App\Controllers;

use App\AuthMiddleware;
use App\Models\PrayerRequest;

class PrayerController extends Controller
{
    // Tela 27: Mural de Oração
    public function index()
    {
        AuthMiddleware::requireLogin();

        $category = $_GET['categoria'] ?? 'todos';
        $model = new PrayerRequest($this->pdo);
        $requests = $model->getAll($category === 'todos' ? null : $category);

        $this->render('vida-espiritual/oracao', [
            'requests' => $requests,
            'category' => $category,
        ]);
    }

    // Tela 49: Detalhe do Pedido
    public function show(int $id)
    {
        AuthMiddleware::requireLogin();

        $model   = new PrayerRequest($this->pdo);
        $request = $model->getById($id);

        if (!$request) {
            $_SESSION['flash']['error'] = 'Pedido não encontrado.';
            $this->redirect('/oracao');
        }

        $comments  = $model->getComments($id);
        $hasPrayed = $model->hasPrayed($id, (int) $_SESSION['user_id']);

        $this->render('vida-espiritual/oracao-detalhe', [
            'request'   => $request,
            'comments'  => $comments,
            'hasPrayed' => $hasPrayed,
        ]);
    }

    // Tela 28: Formulário de Novo Pedido
    public function create()
    {
        AuthMiddleware::requireLogin();
        $this->render('vida-espiritual/oracao-novo');
    }

    // Tela 28: Salvar Pedido
    public function store()
    {
        AuthMiddleware::requireLogin();
        csrf_verify();

        $title = trim($_POST['title'] ?? '');
        if (!$title) {
            $_SESSION['flash']['error'] = 'O pedido não pode estar vazio.';
            $this->redirect('/oracao/novo');
        }

        $model = new PrayerRequest($this->pdo);
        $id = $model->create([
            'user_id'      => (int) $_SESSION['user_id'],
            'title'        => $title,
            'description'  => trim($_POST['description'] ?? ''),
            'category'     => $_POST['category'] ?? 'other',
            'is_urgent'    => isset($_POST['is_urgent']) ? 1 : 0,
            'is_anonymous' => isset($_POST['is_anonymous']) ? 1 : 0,
        ]);

        $_SESSION['flash']['success'] = 'Pedido publicado! O ministério está orando por você. 🙏';
        $this->redirect("/oracao/{$id}");
    }

    // Orar por um pedido (POST)
    public function pray(int $id)
    {
        AuthMiddleware::requireLogin();
        csrf_verify();

        $model = new PrayerRequest($this->pdo);
        $model->pray($id, (int) $_SESSION['user_id']);

        $this->redirect("/oracao/{$id}");
    }

    // Comentar em um pedido (POST)
    public function storeComment(int $id)
    {
        AuthMiddleware::requireLogin();
        csrf_verify();

        $comment = trim($_POST['comment'] ?? '');
        if ($comment) {
            $model = new PrayerRequest($this->pdo);
            $model->addComment($id, (int) $_SESSION['user_id'], $comment);
        }

        $this->redirect("/oracao/{$id}");
    }
}
