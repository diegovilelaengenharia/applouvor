<?php
namespace App\Controllers;

use App\AuthMiddleware;
use App\Models\Member;

class MemberController extends Controller
{
    // Tela 20: Lista de Membros
    public function index()
    {
        AuthMiddleware::requireAdmin();
        $search = trim($_GET['q'] ?? '');
        $sort = in_array($_GET['sort'] ?? '', ['presenca', 'name']) ? $_GET['sort'] : 'name';

        $model = new Member($this->pdo);
        $membros = $model->getAll($search, $sort);
        $this->render('membros/index', compact('membros', 'search', 'sort'));
    }

    // Tela 21: Detalhe do Membro
    public function show(int $id)
    {
        AuthMiddleware::requireAdmin();
        $model = new Member($this->pdo);
        $membro = $model->getById($id);

        if (!$membro) {
            $_SESSION['flash']['error'] = 'Membro não encontrado.';
            $this->redirect('/membros');
        }

        $proximasEscalas = $model->getNextSchedules($id);
        $this->render('membros/show', compact('membro', 'proximasEscalas'));
    }

    // Tela 36: Convidar Membro — form
    public function invite()
    {
        AuthMiddleware::requireAdmin();
        $this->render('membros/convidar', []);
    }

    // Tela 36: Convidar Membro — salvar
    public function storeInvite()
    {
        AuthMiddleware::requireAdmin();
        csrf_verify();

        $name     = trim($_POST['name'] ?? '');
        $password = trim($_POST['password'] ?? '');

        if (!$name || !$password) {
            $_SESSION['flash']['error'] = 'Nome e senha são obrigatórios.';
            $this->redirect('/membros/convidar');
        }

        if (strlen($password) < 4) {
            $_SESSION['flash']['error'] = 'A senha deve ter ao menos 4 caracteres.';
            $this->redirect('/membros/convidar');
        }

        $model = new Member($this->pdo);
        $id = $model->createMember([
            'name'       => $name,
            'instrument' => trim($_POST['instrument'] ?? ''),
            'phone'      => trim($_POST['phone'] ?? ''),
            'email'      => trim($_POST['email'] ?? ''),
            'password'   => $password,
        ]);

        $_SESSION['flash']['success'] = "Membro {$name} adicionado com sucesso!";
        $this->redirect("/membros/{$id}");
    }
}
