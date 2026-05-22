<?php
// Configurar sessão para 30 dias
ini_set('session.gc_maxlifetime', 2592000);

// Detectar HTTPS diretamente do servidor (não depende de APP_ENV)
// auth.php é incluso ANTES de config.php, então APP_ENV ainda não existe aqui
$isSecure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
         || ($_SERVER['SERVER_PORT'] ?? 80) == 443;

session_set_cookie_params([
    'lifetime' => 2592000,
    'path'     => '/',
    'secure'   => $isSecure,
    'httponly' => true,
    'samesite' => 'Lax', // 'Strict' bloqueia redirecionamentos entre paginas
]);
session_start();

// Auto-login seguro no ambiente de desenvolvimento local
// Facilita testes locais na SPA React em localhost:5173 sem depender de login prévio na porta 8080
$isLocalDev = ($_SERVER['HTTP_HOST'] ?? '') === 'localhost:8080' 
           || ($_SERVER['HTTP_HOST'] ?? '') === '127.0.0.1:8080'
           || ($_SERVER['SERVER_ADDR'] ?? '') === '127.0.0.1'
           || ($_SERVER['SERVER_ADDR'] ?? '') === '::1';

if ($isLocalDev && !isset($_SESSION['user_id'])) {
    $_SESSION['user_id'] = 1;
    $_SESSION['user_name'] = 'Líder (Mock Dev)';
    $_SESSION['user_role'] = 'admin';
    $_SESSION['user_avatar'] = 'https://ui-avatars.com/api/?name=Lider+Mock&background=2e7eed&color=fff';
}

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
        $acessoNegadoPath = (strpos($_SERVER['PHP_SELF'], '/admin/') !== false) ? 'acesso_negado.php' : 'admin/acesso_negado.php';
        header("Location: " . $acessoNegadoPath);
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
            $_SESSION['user_avatar'] = $user['photo'] ?? $user['avatar'] ?? null;
    
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
