<?php

namespace App\Controllers;

use App\AuthMiddleware;
use App\Validator;
use App\Models\User;

class ProfileController extends Controller
{
    /**
     * Tela 11: Perfil do usuário logado
     */
    public function index()
    {
        AuthMiddleware::requireLogin();

        $userModel = new User($this->pdo);
        $user = $userModel->find((int) $_SESSION['user_id']);

        if (!$user) {
            \logout();
        }

        $this->render('perfil/index', ['user' => $user]);
    }

    /**
     * Tela 12: Editar Perfil (GET)
     */
    public function edit()
    {
        AuthMiddleware::requireLogin();

        $userModel = new User($this->pdo);
        $user = $userModel->find((int) $_SESSION['user_id']);

        $this->render('perfil/editar', ['user' => $user]);
    }

    /**
     * Tela 12: Salvar Perfil (POST)
     */
    public function update()
    {
        AuthMiddleware::requireLogin();
        csrf_verify();

        $name = trim($_POST['name'] ?? '');

        $validator = new Validator();
        $validator->required($name, 'Nome');

        if ($validator->hasErrors()) {
            $_SESSION['flash']['error'] = $validator->getFirstError();
            $this->redirect('/perfil/editar');
        }

        $userModel = new User($this->pdo);
        $userModel->updateProfile((int) $_SESSION['user_id'], [
            'name'       => $name,
            'email'      => trim($_POST['email'] ?? ''),
            'phone'      => trim($_POST['phone'] ?? ''),
            'instrument' => trim($_POST['instrument'] ?? ''),
            'bio'        => trim($_POST['bio'] ?? ''),
            'birth_date' => $_POST['birth_date'] ?? null,
        ]);

        // Mantém o nome exibido na sessão sincronizado
        $_SESSION['user_name'] = $name;

        $_SESSION['flash']['success'] = 'Perfil atualizado com sucesso!';
        $this->redirect('/perfil');
    }

    /**
     * Tela 34: Alterar Senha (GET)
     */
    public function password()
    {
        AuthMiddleware::requireLogin();
        $this->render('perfil/senha');
    }

    /**
     * Tela 34: Salvar nova senha (POST)
     */
    public function updatePassword()
    {
        AuthMiddleware::requireLogin();
        csrf_verify();

        $current = $_POST['current_password'] ?? '';
        $new     = $_POST['new_password'] ?? '';
        $confirm = $_POST['confirm_password'] ?? '';

        $userModel = new User($this->pdo);
        $user = $userModel->find((int) $_SESSION['user_id']);

        // 1. Senha atual confere?
        if (!$user || !password_verify($current, $user['password'])) {
            $_SESSION['flash']['error'] = 'A senha atual está incorreta.';
            $this->redirect('/perfil/senha');
        }

        // 2. Mínimo de 8 caracteres
        if (strlen($new) < 8) {
            $_SESSION['flash']['error'] = 'A nova senha deve ter pelo menos 8 caracteres.';
            $this->redirect('/perfil/senha');
        }

        // 3. Confirmação confere?
        if ($new !== $confirm) {
            $_SESSION['flash']['error'] = 'A confirmação da nova senha não confere.';
            $this->redirect('/perfil/senha');
        }

        $userModel->updatePassword((int) $user['id'], password_hash($new, PASSWORD_DEFAULT));

        $_SESSION['flash']['success'] = 'Senha alterada com sucesso!';
        $this->redirect('/perfil');
    }
}
