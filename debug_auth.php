<?php
// debug_auth.php - Diagnóstico de Login
require_once 'includes/db.php';

$name = 'Diego'; // Nome relatado pelo usuário
$pass = '9577';  // Senha relatada pelo usuário

echo "<h1>Diagnóstico de Login</h1>";
echo "<p>Testando usuário: <strong>$name</strong></p>";

try {
    // 1. Verificar Conexão DB
    echo "<p>Conexão DB: OK</p>";

    // 2. Buscar Usuário (Case Insensitive force)
    // Tenta exato primeiro
    $user = App\DB::table('users')->where('name', '=', $name)->first();
    
    if (!$user) {
        echo "<p style='color:red'>ERRO: Usuário '$name' (exato) não encontrado.</p>";
        
        // Tenta buscar com LIKE
        $userLike = $pdo->query("SELECT * FROM users WHERE name LIKE '%Diego%'")->fetchAll();
        if ($userLike) {
            echo "<p>Sugestão: Encontrei estes usuários parecidos:</p><ul>";
            foreach ($userLike as $u) {
                echo "<li>ID: {$u['id']} - Nome: '{$u['name']}' (Role: {$u['role']})</li>";
            }
            echo "</ul>";
        } else {
            echo "<p>Nenhum usuário contendo 'Diego' encontrado.</p>";
        }
    } else {
        echo "<p style='color:green'>SUCESSO: Usuário '$name' encontrado (ID: {$user['id']}).</p>";
        
        // 3. Verificar Senha
        $dbPass = $user['password'];
        $isHash = strlen($dbPass) > 50; // Simple check for hash length
        
        echo "<p>Tipo de senha no DB: " . ($isHash ? "Hash detectado" : "Texto puro (Legado)") . "</p>";
        
        $match = false;
        if (password_verify($pass, $dbPass)) {
            $match = true;
            echo "<p style='color:green'>SENHA: OK (Bateu com password_verify)</p>";
        } elseif ($pass === $dbPass) {
            $match = true;
            echo "<p style='color:green'>SENHA: OK (Texto plano - Sistema vai atualizar pro hash no login)</p>";
        } else {
            echo "<p style='color:red'>SENHA: INCORRETA. A senha digitada não confere.</p>";
        }
        
        // 4. Verificar Sessão/Cookie param
        echo "<h2>Verificação de Ambiente</h2>";
        echo "<p>HTTPS ativo? " . (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'Sim' : 'Não') . "</p>";
        
        $cookieParams = session_get_cookie_params();
        echo "<p>Cookie Secure: " . ($cookieParams['secure'] ? 'Sim' : 'Não') . "</p>";
        
        if ($cookieParams['secure'] && (!isset($_SERVER['HTTPS']) || $_SERVER['HTTPS'] !== 'on')) {
            echo "<p style='color:red; font-weight:bold'>ALERTA CRÍTICO: Cookies estão configurados como 'Secure' (apenas HTTPS), mas o acesso parece ser HTTP. O login vai falhar pois o cookie de sessão será rejeitado pelo navegador.</p>";
        }
        
        // 5. Verificar Permissões
        echo "<p>Role: {$user['role']}</p>";
        if ($user['role'] === 'admin') {
            echo "<p>Redirecionamento esperado: /admin/index.php</p>";
        } else {
            echo "<p>Redirecionamento esperado: /app/index.php</p>";
        }
    }

} catch (Exception $e) {
    echo "<p style='color:red'>Erro fatal: " . $e->getMessage() . "</p>";
}
?>
