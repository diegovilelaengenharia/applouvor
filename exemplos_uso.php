<?php
/**
 * EXEMPLOS DE USO DAS NOVAS CLASSES
 * Este arquivo demonstra como usar as melhorias implementadas
 * 
 * NÃO EXECUTE ESTE ARQUIVO EM PRODUÇÃO!
 * É apenas para referência e testes.
 */

require_once 'includes/config.php';
require_once 'includes/db.php';

echo "<h1>Exemplos de Uso - App Louvor v4.1</h1>";
echo "<style>body { font-family: sans-serif; max-width: 800px; margin: 40px auto; } code { background: #f4f4f4; padding: 2px 6px; border-radius: 3px; } pre { background: #f4f4f4; padding: 15px; border-radius: 5px; overflow-x: auto; }</style>";

// ============================================
// 1. VALIDAÇÃO DE FORMULÁRIOS
// ============================================
echo "<h2>1. Validação de Formulários</h2>";

$validator = new App\Validator();
$validator->required('', 'Nome');
$validator->email('email-invalido', 'E-mail');
$validator->min('ab', 3, 'Senha');

if ($validator->hasErrors()) {
    echo "<h3>Erros encontrados:</h3>";
    echo "<ul>";
    foreach ($validator->getErrors() as $field => $error) {
        echo "<li><strong>$field:</strong> $error</li>";
    }
    echo "</ul>";
}

echo "<h3>Código usado:</h3>";
echo "<pre>\$validator = new App\\Validator();
\$validator->required('', 'Nome');
\$validator->email('email-invalido', 'E-mail');
\$validator->min('ab', 3, 'Senha');

if (\$validator->hasErrors()) {
    foreach (\$validator->getErrors() as \$field => \$error) {
        echo \$error;
    }
}</pre>";

// ============================================
// 2. QUERY BUILDER
// ============================================
echo "<h2>2. Query Builder</h2>";

// Exemplo 1: Buscar usuários
echo "<h3>Buscar todos os usuários:</h3>";
try {
    $users = App\DB::table('users')
        ->orderBy('name', 'ASC')
        ->limit(5)
        ->get();
    
    echo "<p>Encontrados: " . count($users) . " usuários</p>";
    echo "<pre>";
    print_r($users);
    echo "</pre>";
} catch (Exception $e) {
    echo "<p style='color: red;'>Erro: " . $e->getMessage() . "</p>";
}

echo "<h3>Código usado:</h3>";
echo "<pre>\$users = App\\DB::table('users')
    ->orderBy('name', 'ASC')
    ->limit(5)
    ->get();</pre>";

// Exemplo 2: Buscar com WHERE
echo "<h3>Buscar músicas aprovadas:</h3>";
try {
    $songs = App\DB::table('songs')
        ->where('status', '=', 'approved')
        ->orderBy('title', 'ASC')
        ->limit(3)
        ->get();
    
    echo "<p>Encontradas: " . count($songs) . " músicas</p>";
    echo "<pre>";
    print_r($songs);
    echo "</pre>";
} catch (Exception $e) {
    echo "<p style='color: red;'>Erro: " . $e->getMessage() . "</p>";
}

echo "<h3>Código usado:</h3>";
echo "<pre>\$songs = App\\DB::table('songs')
    ->where('status', '=', 'approved')
    ->orderBy('title', 'ASC')
    ->limit(3)
    ->get();</pre>";

// Exemplo 3: Contar registros
echo "<h3>Contar total de membros:</h3>";
try {
    $total = App\DB::table('members')->count();
    echo "<p>Total de membros: <strong>$total</strong></p>";
} catch (Exception $e) {
    echo "<p style='color: red;'>Erro: " . $e->getMessage() . "</p>";
}

echo "<h3>Código usado:</h3>";
echo "<pre>\$total = App\\DB::table('members')->count();</pre>";

// ============================================
// 3. AUTENTICAÇÃO
// ============================================
echo "<h2>3. Middleware de Autenticação</h2>";

echo "<h3>Verificar se está logado:</h3>";
if (App\AuthMiddleware::check()) {
    echo "<p style='color: green;'>✅ Usuário está logado</p>";
    echo "<p>ID: " . App\AuthMiddleware::userId() . "</p>";
    echo "<p>Role: " . App\AuthMiddleware::userRole() . "</p>";
} else {
    echo "<p style='color: orange;'>⚠️ Usuário não está logado</p>";
}

echo "<h3>Código usado:</h3>";
echo "<pre>if (App\\AuthMiddleware::check()) {
    echo 'Usuário logado';
    echo 'ID: ' . App\\AuthMiddleware::userId();
    echo 'Role: ' . App\\AuthMiddleware::userRole();
}</pre>";

echo "<h3>Token CSRF:</h3>";
$csrfToken = App\AuthMiddleware::generateCsrfToken();
echo "<p>Token gerado: <code>$csrfToken</code></p>";
echo "<p>Use em formulários:</p>";
echo "<pre>&lt;form method=\"POST\"&gt;
    " . App\AuthMiddleware::csrfField() . "
    &lt;!-- outros campos --&gt;
&lt;/form&gt;</pre>";

// ============================================
// 4. VARIÁVEIS DE AMBIENTE
// ============================================
echo "<h2>4. Variáveis de Ambiente</h2>";

echo "<h3>Configurações carregadas do .env:</h3>";
echo "<ul>";
echo "<li><strong>DB_HOST:</strong> " . DB_HOST . "</li>";
echo "<li><strong>DB_NAME:</strong> " . DB_NAME . "</li>";
echo "<li><strong>APP_ENV:</strong> " . APP_ENV . "</li>";
echo "<li><strong>APP_DEBUG:</strong> " . (APP_DEBUG ? 'true' : 'false') . "</li>";
echo "<li><strong>APP_URL:</strong> " . APP_URL . "</li>";
echo "</ul>";

echo "<h3>Como usar:</h3>";
echo "<pre>// No arquivo .env
DB_HOST=localhost
DB_NAME=louvor_pib

// No código PHP
define('DB_HOST', App\\DotEnv::get('DB_HOST', 'fallback'));
echo DB_HOST; // localhost</pre>";

// ============================================
// RESUMO
// ============================================
echo "<hr>";
echo "<h2>📚 Resumo das Melhorias</h2>";
echo "<ol>";
echo "<li><strong>Autoloading PSR-4:</strong> Classes carregadas automaticamente</li>";
echo "<li><strong>Variáveis de Ambiente:</strong> Credenciais no arquivo .env</li>";
echo "<li><strong>Validação Centralizada:</strong> Classe Validator para formulários</li>";
echo "<li><strong>Query Builder:</strong> Queries SQL mais elegantes</li>";
echo "<li><strong>Middleware de Auth:</strong> Autenticação centralizada</li>";
echo "<li><strong>Proteção CSRF:</strong> Tokens para formulários</li>";
echo "</ol>";

echo "<hr>";
echo "<p><strong>Versão:</strong> " . APP_VERSION . "</p>";
echo "<p><strong>Desenvolvido por:</strong> Diego T. N. Vilela</p>";
