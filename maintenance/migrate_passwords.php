<?php
// migrate_passwords.php
// Script para migrar senhas em texto plano para hashes seguros (bcrypt)

require_once 'includes/config.php';
require_once 'includes/db.php';

echo "<h2>Iniciando migração de senhas...</h2>";

try {
    // 1. Buscar todos os usuários
    $users = App\DB::table('users')->get();
    
    $count = 0;
    $skipped = 0;

    foreach ($users as $user) {
        $currentPass = $user['password'];
        $id = $user['id'];
        $name = $user['name'];

        // Verificar se já é um hash (bcrypt começa com $2y$ ou $2a$)
        if (substr($currentPass, 0, 4) === '$2y$' || substr($currentPass, 0, 4) === '$2a$') {
            echo "Recebido usuario <b>$name</b>: Senha já é hash. Pulando.<br>";
            $skipped++;
            continue;
        }

        // Criar hash
        $newHash = password_hash($currentPass, PASSWORD_DEFAULT);

        // Atualizar no banco
        App\DB::table('users')
            ->where('id', '=', $id)
            ->update(['password' => $newHash]);

        echo "Migrado usuario <b>$name</b>: OK.<br>";
        $count++;
    }

    echo "<h3>Resumo:</h3>";
    echo "Migrados: $count<br>";
    echo "Pulados (já eram hash): $skipped<br>";
    echo "Total: " . count($users) . "<br>";
    echo "<hr><strong>SUCESSO!</strong> Agora atualize o arquivo <code>includes/auth.php</code>.";

} catch (Exception $e) {
    echo "Erro fatal: " . $e->getMessage();
}
