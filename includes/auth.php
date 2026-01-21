<?php
// Configurar sessão para 30 dias
ini_set('session.gc_maxlifetime', 2592000); // 30 dias
session_set_cookie_params([
    'lifetime' => 2592000,
    'path' => '/',
    'secure' => true, // Apenas HTTPS (Production)
    'httponly' => true,
    'samesite' => 'Strict'
]);
session_start();

// Verifica se o usuário está logado
function checkLogin()
{
    if (!isset($_SESSION['user_id'])) {
        header("Location: ../index.php");
        exit;
    }
}

// Verifica se é Admin
function checkAdmin()
{
    checkLogin();
    if ($_SESSION['user_role'] !== 'admin') {
        header("Location: ../app/index.php"); // Redireciona para área comum se tentar acessar admin
        exit;
    }
}

// Função de Login
function login($name, $password, $pdo)
{
    $stmt = $pdo->prepare("SELECT * FROM users WHERE name = :name");
    $stmt->execute(['name' => $name]);
    $user = $stmt->fetch();

    if ($user && $password === $user['password']) { // Comparação direta conforme solicitado (senhas simples)
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_name'] = $user['name'];
        $_SESSION['user_role'] = $user['role'];
        $_SESSION['user_avatar'] = $user['avatar'] ?? null;

        // Atualizar estatísticas de login
        $stmtUpdate = $pdo->prepare("UPDATE users SET last_login = NOW(), login_count = login_count + 1 WHERE id = ?");
        $stmtUpdate->execute([$user['id']]);

        return true;
    }
    return false;
}

// Tratamento de Logout via GET
if (isset($_GET['logout'])) {
    session_destroy();
    header("Location: ../index.php");
    exit;
}
