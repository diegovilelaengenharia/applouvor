<?php
namespace App\Controllers;

use App\AuthMiddleware;
use App\Models\Aviso;

class AvisoController extends Controller
{
    // Tela 14: Lista de Avisos
    public function index()
    {
        AuthMiddleware::requireLogin();
        $model = new Aviso($this->pdo);
        $avisos = $model->getActive();
        $this->render('app/avisos', ['avisos' => $avisos]);
    }

    // Tela 15: Detalhe do Aviso
    public function show(int $id)
    {
        AuthMiddleware::requireLogin();
        $model = new Aviso($this->pdo);
        $aviso = $model->getById($id);

        if (!$aviso) {
            $_SESSION['flash']['error'] = 'Aviso não encontrado.';
            $this->redirect('/avisos');
        }

        $this->render('app/aviso-detalhe', ['aviso' => $aviso]);
    }

    // Tela 15 (admin): Formulário de criação
    public function create()
    {
        AuthMiddleware::requireAdmin();
        $this->render('app/aviso-form', ['aviso' => null]);
    }

    // Tela 15 (admin): Salvar aviso
    public function store()
    {
        AuthMiddleware::requireAdmin();
        csrf_verify();

        $titulo = trim($_POST['titulo'] ?? '');
        if (!$titulo) {
            $_SESSION['flash']['error'] = 'O título é obrigatório.';
            $this->redirect('/avisos/novo');
        }

        $model = new Aviso($this->pdo);
        $id = $model->create([
            'titulo'         => $titulo,
            'conteudo'       => trim($_POST['conteudo'] ?? ''),
            'tipo'           => $_POST['tipo'] ?? 'geral',
            'prioridade'     => $_POST['prioridade'] ?? 'media',
            'fixado'         => isset($_POST['fixado']) ? 1 : 0,
            'data_expiracao' => $_POST['data_expiracao'] ?? null,
            'user_id'        => $_SESSION['user_id'],
        ]);

        $_SESSION['flash']['success'] = 'Aviso publicado com sucesso!';
        $this->redirect("/avisos/{$id}");
    }

    // (admin): Excluir aviso
    public function destroy(int $id)
    {
        AuthMiddleware::requireAdmin();
        csrf_verify();

        $model = new Aviso($this->pdo);
        $model->delete($id);

        $_SESSION['flash']['success'] = 'Aviso removido.';
        $this->redirect('/avisos');
    }
}
