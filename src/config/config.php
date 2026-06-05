<?php
// src/config/config.php

// Carrega autoloader de classes
require_once __DIR__ . '/../autoload.php';

// Carrega variáveis de ambiente do .env na raiz do projeto
$dotenv = new App\DotEnv(__DIR__ . '/../..');
$dotenv->load();

// ======================================
// DETECÇÃO DE AMBIENTE
// ======================================
$envPath = __DIR__ . '/../../.env';
$isProduction = !file_exists($envPath);

// ======================================
// CREDENCIAIS DO BANCO DE DADOS
// ======================================
if ($isProduction) {
    // Credenciais de produção: lidas de db_credentials.php (gerado no deploy) ou do ambiente
    $credFile = __DIR__ . '/db_credentials.php';
    $creds = is_file($credFile) ? (require $credFile) : [];

    define('DB_HOST', $creds['DB_HOST'] ?? (getenv('DB_HOST') ?: 'srv1074.hstgr.io'));
    define('DB_NAME', $creds['DB_NAME'] ?? (getenv('DB_NAME') ?: 'u884436813_applouvor'));
    define('DB_USER', $creds['DB_USER'] ?? (getenv('DB_USER') ?: 'u884436813_admin'));
    define('DB_PASS', $creds['DB_PASS'] ?? (getenv('DB_PASS') ?: ''));

    define('APP_ENV',   'production');
    define('APP_DEBUG', false);
    define('APP_URL',   'https://louvor.vilela.eng.br');

    ini_set('display_errors', 0);
    error_reporting(E_ALL & ~E_NOTICE & ~E_DEPRECATED & ~E_STRICT & ~E_USER_NOTICE & ~E_USER_DEPRECATED);

} else {
    // AMBIENTE LOCAL
    define('DB_HOST', App\DotEnv::get('DB_HOST', 'localhost'));
    define('DB_NAME', App\DotEnv::get('DB_NAME', 'pibo_louvor'));
    define('DB_USER', App\DotEnv::get('DB_USER', 'root'));
    define('DB_PASS', App\DotEnv::get('DB_PASS', ''));

    define('APP_ENV', App\DotEnv::get('APP_ENV', 'local'));
    define('APP_DEBUG', App\DotEnv::get('APP_DEBUG', 'true') === 'true');
    
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost:8080';
    define('APP_URL', $protocol . '://' . $host);
    
    ini_set('display_errors', 1);
    error_reporting(E_ALL);
}

// ======================================
// INFORMAÇÕES DA IGREJA
// ======================================
define('CHURCH_NAME', 'PIB Oliveira');
define('CHURCH_SLOGAN', 'Uma igreja viva, edificando vidas');
define('CHURCH_ADDRESS', 'R. José Eduardo Abdo, 105');
define('CHURCH_SERVICE_TIMES', 'Domingos às 09h | 19h');

// Social Media
define('CHURCH_INSTAGRAM', 'https://www.instagram.com/piboliveiramg/');
define('CHURCH_FACEBOOK', 'https://www.facebook.com/piboliveiramg');

// System Info
define('APP_VERSION', '6.0.0');
define('APP_COPYRIGHT', '© ' . date('Y') . ' Louvor PIB Oliveira');
