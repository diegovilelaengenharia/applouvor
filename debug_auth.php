<?php
// debug_auth.php - Diagnóstico de Login
require_once 'includes/db.php';
// require_once 'includes/auth.php'; // DISABLED to avoid redirect loop if not logged in (auth.php might check login?)
// Let's check auth.php content again... it has checkLogin() but doesn't call it globally. It calls session_start().
// So it is safe to include.
require_once 'includes/auth.php';

$name = 'Diego'; // Nome relatado pelo usuário
$pass = '9577';  // Senha relatada pelo usuário

// Plain text diagnostic for curl
header('Content-Type: text/html; charset=utf-8');

echo "<h1>Diagnóstico de Login</h1>";
echo "<!-- DIAGNOSTIC_START -->\n";
echo "Testando usuário: <strong>$name</strong><br>";

try {
    // 1. Verificar Conexão DB
    echo "Conexão DB: OK<br>";

    // 2. Buscar Usuário (Case Insensitive force)
    $user = App\DB::table('users')->where('name', '=', $name)->first();
    
    if (!$user) {
        echo "<span style='color:red'>ERRO: Usuário '$name' (exato) não encontrado.</span><br>";
        
        // Tenta buscar com LIKE
        $userLike = $pdo->query("SELECT * FROM users WHERE name LIKE '%Diego%'")->fetchAll();
        if ($userLike) {
            echo "Sugestão: Encontrei estes usuários parecidos:<ul>";
            foreach ($userLike as $u) {
                echo "<li>ID: {$u['id']} - Nome: '{$u['name']}' (Role: {$u['role']})</li>";
            }
            echo "</ul>";
        }
    } else {
        echo "<span style='color:green'>SUCESSO: Usuário '$name' encontrado (ID: {$user['id']}).</span><br>";
        
        // 3. Verificar Senha
        $dbPass = $user['password'];
        $match = false;
        
        if (password_verify($pass, $dbPass)) {
            $match = true;
            echo "<span style='color:green; font-weight:bold; font-size:1.2em;'>SENHA: OK (Hash)</span><br>";
        } elseif ($pass === $dbPass) {
            $match = true;
            echo "<span style='color:green; font-weight:bold; font-size:1.2em;'>SENHA: OK (Texto Plano)</span><br>";
        } else {
            echo "<span style='color:red; font-weight:bold; font-size:1.2em;'>SENHA: INCORRETA</span><br>";
            echo "Hash no DB começa com: " . substr($dbPass, 0, 10) . "...<br>";
        }
        
        // 4. Verificar Sessão/Cookie param
        echo "<h2>Verificação de Ambiente</h2>";
        echo "HTTPS ativo server var? " . (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'Sim' : 'Não') . "<br>";
        
        $cookieParams = session_get_cookie_params();
        echo "Cookie Secure: " . ($cookieParams['secure'] ? 'Sim' : 'Não') . "<br>";
        echo "Session ID: " . session_id() . "<br>";
        
        if ($cookieParams['secure'] && (!isset($_SERVER['HTTPS']) || $_SERVER['HTTPS'] !== 'on')) {
            echo "<p style='color:red; font-weight:bold'>ALERTA CRÍTICO: Cookies Secure=True mas HTTPS=False/Null. Login falhará.</p>";
        }
    }

} catch (Exception $e) {
    echo "<p style='color:red'>Erro fatal: " . $e->getMessage() . "</p>";
}
echo "\n<!-- DIAGNOSTIC_END -->";
?>
