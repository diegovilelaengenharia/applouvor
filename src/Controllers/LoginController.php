<?php
namespace App\Controllers;

use App\Validator;
use App\AuthMiddleware;

class LoginController extends Controller
{
    /**
     * Exibe a tela de login (GET /)
     */
    public function index()
    {
        // Se o usuário já estiver logado, redireciona diretamente para o dashboard
        if (AuthMiddleware::check()) {
            $this->redirect('/dashboard');
        }

        $this->render('auth/login', ['error' => '']);
    }

    /**
     * Processa a requisição de login (POST /login)
     */
    public function login()
    {
        // 1. Validação CSRF obrigatória
        csrf_verify();

        $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';

        // 2. Verifica limite de tentativas de login (Rate Limiting)
        $rateCheck = rateLimitCheck($this->pdo, $ip);
        if ($rateCheck['blocked']) {
            $wait = $rateCheck['wait'] ?? 60;
            $this->render('auth/login', ['error' => "Muitas tentativas de login de forma consecutiva. Aguarde {$wait} segundo(s) antes de tentar novamente."]);
            return;
        }

        $name = trim($_POST['name'] ?? '');
        $password = trim($_POST['password'] ?? '');

        // 3. Validação dos campos obrigatórios
        $validator = new Validator();
        $validator->required($name, 'Usuário');
        $validator->required($password, 'Senha');

        if ($validator->hasErrors()) {
            $this->render('auth/login', ['error' => $validator->getFirstError()]);
            return;
        }

        // 4. Executa a autenticação
        if (\login($name, $password, $this->pdo)) {
            // Registra sucesso e limpa histórico de tentativas do IP
            rateLimitRecord($this->pdo, $ip, true);
            
            // Regenera o ID da sessão por segurança
            session_regenerate_id(true);

            $this->redirect('/dashboard');
        } else {
            // Registra falha para controle de rate limit
            rateLimitRecord($this->pdo, $ip, false);

            $attempts = ($rateCheck['attempts'] ?? 0) + 1;
            $remaining = max(0, 5 - $attempts);
            
            $error = "Usuário ou senha incorretos.";
            if ($remaining > 0) {
                $error .= " ({$remaining} tentativa(s) restante(s))";
            } else {
                $error .= " (IP bloqueado temporariamente)";
            }

            $this->render('auth/login', ['error' => $error]);
        }
    }

    /**
     * Tela 33: Recuperar Senha (GET /recuperar-senha) — público.
     *
     * NOTA: ainda não há infraestrutura de e-mail/token de redefinição.
     * Por ora, orienta o usuário a acionar a liderança/suporte (um admin
     * pode redefinir a senha). Trocar por fluxo self-service quando houver
     * envio de e-mail + tabela de tokens.
     */
    public function recover()
    {
        if (AuthMiddleware::check()) {
            $this->redirect('/dashboard');
        }
        $this->render('auth/recuperar-senha');
    }

    /**
     * Processa a requisição de logout (GET /logout)
     */
    public function logout()
    {
        \logout();
    }
}
