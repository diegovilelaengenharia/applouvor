<?php
// Configurar sessão para 30 dias
ini_set('session.gc_maxlifetime', 2592000); // 30 dias

// Define se está em produção (secure cookie só em HTTPS)
$isSecure = defined('APP_ENV') ? (APP_ENV === 'production') : true;

session_set_cookie_params([
    'lifetime' => 2592000,
    'path' => '/',
    'secure' => $isSecure, // false em local (HTTP), true em produção (HTTPS)
    'httponly' => true,
    'samesite' => 'Strict'
]);
session_start();

// Verifica se o usuário está logado
function checkLogin()
{
    if (!isset($_SESSION['user_id'])) {
        // Redireciona para login relativo ao root
        $loginPath = str_repeat('../', substr_count($_SERVER['PHP_SELF'], '/') - 1) . 'index.php';
        header("Location: " . $loginPath);
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
    // Usando Query Builder
    $user = App\DB::table('users')
        ->where('name', '=', $name)
        ->first();

    if ($user) {
        // Verificar se a senha é hash (seguro) ou texto plano (legado/migração)
        $isPasswordCorrect = false;

        if (password_verify($password, $user['password'])) {
            $isPasswordCorrect = true;
            
            // Rehash se necessário (ex: algoritmo atualizou ou custo aumentou)
            if (password_needs_rehash($user['password'], PASSWORD_DEFAULT)) {
                $newHash = password_hash($password, PASSWORD_DEFAULT);
                App\DB::table('users')
                    ->where('id', '=', $user['id'])
                    ->update(['password' => $newHash]);
            }
        } elseif ($password === $user['password']) {
            // Fallback para senha antiga em texto plano (migração automática no login)
            $isPasswordCorrect = true;
            
            // Atualizar para hash imediatamente
            $newHash = password_hash($password, PASSWORD_DEFAULT);
            App\DB::table('users')
                ->where('id', '=', $user['id'])
                ->update(['password' => $newHash]);
        }

        if ($isPasswordCorrect) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_name'] = $user['name'];
            $_SESSION['user_role'] = $user['role'];
            $_SESSION['user_avatar'] = $user['avatar'] ?? null;
    
            // Atualizar estatísticas de login usando Query Builder
            App\DB::table('users')
                ->where('id', '=', $user['id'])
                ->update([
                    'last_login' => date('Y-m-d H:i:s'),
                    'login_count' => $user['login_count'] + 1
                ]);
    
            return true;
        }
    }
    return false;
}

// Logout via POST (seguro) ou via ?logout=1 (fallback legacy)
if (isset($_GET['logout']) || (isset($_POST['action']) && $_POST['action'] === 'logout')) {
    session_destroy();
    $loginPath = str_repeat('../', substr_count($_SERVER['PHP_SELF'], '/') - 1) . 'index.php';
    header("Location: " . $loginPath);
    exit;
}
