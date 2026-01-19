<?php
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
