<?php

namespace App\Controllers;

use App\AuthMiddleware;
use App\Models\Song;

class SongController extends Controller
{
    /**
     * Tela 07: Repertório (Lista)
     */
    public function index()
    {
        AuthMiddleware::requireLogin();
        
        $songModel = new Song($this->pdo);
        
        if (isset($_GET['q']) && !empty($_GET['q'])) {
            $songs = $songModel->search($_GET['q']);
        } else {
            $songs = $songModel->getAll();
        }

        $this->render('repertorio/index', ['songs' => $songs]);
    }

    /**
     * Tela 08: Detalhe da Música
     */
    public function show($id)
    {
        AuthMiddleware::requireLogin();

        $songModel = new Song($this->pdo);
        $song = $songModel->find($id);

        if (!$song) {
            $_SESSION['flash']['error'] = 'Música não encontrada.';
            $this->redirect('/repertorio');
        }

        $this->render('repertorio/show', ['song' => $song]);
    }

    /**
     * Tela 09: Criar Música (GET)
     */
    public function create()
    {
        AuthMiddleware::requireLogin();
        if ($_SESSION['user_role'] !== 'admin') {
            $_SESSION['flash']['error'] = 'Acesso restrito.';
            $this->redirect('/repertorio');
        }

        $this->render('repertorio/form');
    }

    /**
     * Tela 09: Salvar Música (POST)
     */
    public function store()
    {
        AuthMiddleware::requireLogin();
        if ($_SESSION['user_role'] !== 'admin') {
            $this->redirect('/repertorio');
        }
        csrf_verify();

        $songModel = new Song($this->pdo);
        $id = $songModel->create($_POST);

        $_SESSION['flash']['success'] = 'Música adicionada ao repertório!';
        $this->redirect("/musicas/{$id}");
    }

    /**
     * Tela 09: Editar Música (GET)
     */
    public function edit($id)
    {
        AuthMiddleware::requireLogin();
        if ($_SESSION['user_role'] !== 'admin') {
            $this->redirect('/repertorio');
        }

        $songModel = new Song($this->pdo);
        $song = $songModel->find($id);

        $this->render('repertorio/form', ['song' => $song]);
    }

    /**
     * Tela 09: Atualizar Música (POST)
     */
    public function update($id)
    {
        AuthMiddleware::requireLogin();
        if ($_SESSION['user_role'] !== 'admin') {
            $this->redirect('/repertorio');
        }
        csrf_verify();

        $songModel = new Song($this->pdo);
        $songModel->update($id, $_POST);

        $_SESSION['flash']['success'] = 'Música atualizada!';
        $this->redirect("/musicas/{$id}");
    }

    /**
     * Deletar Música (POST)
     */
    public function destroy($id)
    {
        AuthMiddleware::requireLogin();
        if ($_SESSION['user_role'] !== 'admin') {
            $this->redirect('/repertorio');
        }
        csrf_verify();

        $songModel = new Song($this->pdo);
        $songModel->delete($id);

        $_SESSION['flash']['success'] = 'Música removida.';
        $this->redirect('/repertorio');
    }

    /**
     * Tela 10: Cifra (Modo Palco)
     */
    public function cifra($id)
    {
        AuthMiddleware::requireLogin();

        $songModel = new Song($this->pdo);
        $song = $songModel->find($id);

        if (!$song) {
            $this->redirect('/repertorio');
        }

        $this->render('repertorio/cifra', ['song' => $song]);
    }
}
