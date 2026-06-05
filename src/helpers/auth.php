<?php
// src/helpers/auth.php

// Configurar sessão para 30 dias
ini_set('session.gc_maxlifetime', 2592000);

$isSecure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
         || ($_SERVER['SERVER_PORT'] ?? 80) == 443;

session_set_cookie_params([
    'lifetime' => 2592000,
    'path'     => '/',
    'secure'   => $isSecure,
    'httponly' => true,
    'samesite' => 'Strict',
]);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Função de Login
 */
function login($name, $password, $pdo)
{
    // Buscar usuário pelo nome
    $user = App\DB::table('users')
        ->where('name', '=', $name)
        ->first();

    if ($user) {
        $isPasswordCorrect = false;

        // Verificar hash bcrypt
        if (password_verify($password, $user['password'])) {
            $isPasswordCorrect = true;
            
            // Rehash se necessário
            if (password_needs_rehash($user['password'], PASSWORD_DEFAULT)) {
                $newHash = password_hash($password, PASSWORD_DEFAULT);
                App\DB::table('users')
                    ->where('id', '=', $user['id'])
                    ->update(['password' => $newHash]);
            }
        } elseif ($password === $user['password']) {
            // Migração automática de senhas legado em texto plano
            $isPasswordCorrect = true;
            
            $newHash = password_hash($password, PASSWORD_DEFAULT);
            App\DB::table('users')
                ->where('id', '=', $user['id'])
                ->update(['password' => $newHash]);
        }

        if ($isPasswordCorrect) {
            $_SESSION['user_id']         = $user['id'];
            $_SESSION['user_name']       = $user['name'];
            $_SESSION['user_role']       = $user['role'];
            $_SESSION['user_avatar']     = $user['avatar'] ?? null;
            $_SESSION['user_instrument'] = $user['instrument'] ?? null;
    
            // Atualizar logs de login
            App\DB::table('users')
                ->where('id', '=', $user['id'])
                ->update([
                    'last_login' => date('Y-m-d H:i:s'),
                    'login_count' => ($user['login_count'] ?? 0) + 1
                ]);
    
            return true;
        }
    }
    return false;
}

/**
 * Função de Logout
 */
function logout()
{
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    $_SESSION = [];
    session_destroy();
    header("Location: /");
    exit;
}
