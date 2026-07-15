<?php
/**
 * src/config/config.php — configuração de banco de dados (FASE 00, ciclo v7)
 *
 * Lição do ciclo v6 (jun/2026): credenciais NUNCA MAIS viajam pelo pipeline de deploy
 * (nem embutidas em config.php pelo CI, nem em arquivo db_credentials.php subido por FTPS
 * paralelo). A partir daqui, a ÚNICA fonte de credenciais em produção é variável de ambiente
 * lida via getenv(), cadastrada no PAINEL da Hostinger (aba "Variáveis de Ambiente" do PHP).
 *
 * Falha de configuração é RUIDOSA de propósito: se uma env var obrigatória não existir,
 * lançamos exceção com o nome da variável — nunca caímos silenciosamente num valor padrão
 * inventado (foi assim que o ciclo v6 mascarou o problema por 10 commits).
 */

declare(strict_types=1);

/**
 * Lê uma variável de ambiente obrigatória. Lança RuntimeException com mensagem clara
 * (sem valor sensível) se ela não estiver definida ou estiver vazia.
 */
function app_env_required(string $key): string
{
    $value = getenv($key);
    if ($value === false || $value === '') {
        throw new RuntimeException(
            "Variável de ambiente obrigatória '{$key}' não definida. " .
            "Em produção: cadastre no painel Hostinger (PHP > Variáveis de Ambiente). " .
            "Em desenvolvimento local: defina em site/.env (veja site/.env.example)."
        );
    }
    return $value;
}

/**
 * Lê uma variável de ambiente opcional, com valor padrão explícito.
 */
function app_env_optional(string $key, string $default): string
{
    $value = getenv($key);
    return ($value === false) ? $default : $value;
}

// ======================================
// DESENVOLVIMENTO LOCAL: carrega site/.env (se existir) para dentro do ambiente do processo.
// Em produção este arquivo não existe (é gitignored) — getenv() lê direto do painel Hostinger.
// Parser propositalmente simples (sem dependência de composer/vendor): KEY=VALUE por linha,
// ignora linhas vazias e comentários (#).
// ======================================
$envFile = __DIR__ . '/../../.env';
if (is_file($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '' || str_starts_with($line, '#') || !str_contains($line, '=')) {
            continue;
        }
        [$key, $value] = array_map('trim', explode('=', $line, 2));
        if ($key !== '' && getenv($key) === false) {
            putenv("{$key}={$value}");
        }
    }
    unset($lines, $line, $key, $value);
}
unset($envFile);

// ======================================
// CREDENCIAIS DO BANCO DE DADOS — só via getenv(), sempre.
// DB_HOST/DB_NAME/DB_USER são obrigatórias. DB_PASS é opcional (MySQL local sem senha, ex.
// XAMPP com usuário root) mas em produção o painel Hostinger deve cadastrá-la mesmo assim.
// ======================================
define('DB_HOST', app_env_required('DB_HOST'));
define('DB_NAME', app_env_required('DB_NAME'));
define('DB_USER', app_env_required('DB_USER'));
define('DB_PASS', app_env_optional('DB_PASS', ''));

// ======================================
// AMBIENTE
// ======================================
define('APP_ENV', app_env_optional('APP_ENV', 'production'));
define('APP_DEBUG', app_env_optional('APP_DEBUG', 'false') === 'true');

if (APP_DEBUG) {
    ini_set('display_errors', '1');
    error_reporting(E_ALL);
} else {
    ini_set('display_errors', '0');
    error_reporting(E_ALL & ~E_NOTICE & ~E_DEPRECATED & ~E_STRICT & ~E_USER_NOTICE & ~E_USER_DEPRECATED);
}

// ======================================
// INFORMAÇÕES DA IGREJA (estático — sem custo de manter aqui na FASE 00)
// ======================================
define('CHURCH_NAME', 'PIB Oliveira');
define('CHURCH_SLOGAN', 'Uma igreja viva, edificando vidas');
define('CHURCH_ADDRESS', 'R. José Eduardo Abdo, 105');
define('CHURCH_SERVICE_TIMES', 'Domingos às 09h | 19h');
define('CHURCH_INSTAGRAM', 'https://www.instagram.com/piboliveiramg/');
define('CHURCH_FACEBOOK', 'https://www.facebook.com/piboliveiramg');

define('APP_VERSION', '7.1.0-fase01');
define('APP_COPYRIGHT', '© ' . date('Y') . ' Louvor PIB Oliveira');
