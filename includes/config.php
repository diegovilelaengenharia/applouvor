<?php
// includes/config.php

// ======================================
// AUTOLOADER E VARIÁVEIS DE AMBIENTE
// ======================================
// Carrega autoloader de classes
require_once __DIR__ . '/autoload.php';

// Carrega variáveis de ambiente do .env
$dotenv = new App\DotEnv(__DIR__ . '/..');
$dotenv->load();

// ======================================
// DETECÇÃO DE AMBIENTE
// ======================================
// Se o arquivo .env NÃO existir, assumimos que é PRODUÇÃO
// (pois no local sempre temos o .env, e no servidor é ignorado pelo git)
$envPath = __DIR__ . '/../.env';
$isProduction = !file_exists($envPath);

// Debug (opcional, remova se necessário)
// echo "Env Exists: " . (file_exists($envPath) ? 'Yes' : 'No') . "<br>";

// ======================================
// CREDENCIAIS DO BANCO DE DADOS
// ======================================
if ($isProduction) {
    // FORÇA CREDENCIAIS DE PRODUÇÃO
    define('DB_HOST', 'srv1074.hstgr.io');
    define('DB_NAME', 'u884436813_applouvor');
    define('DB_USER', 'u884436813_admin');
    define('DB_PASS', 'Diego@159753');
    
    // Configurações de App
    define('APP_ENV', 'production');
    define('APP_DEBUG', false);
    define('APP_URL', 'https://vilela.eng.br/applouvor');
    
    // Segurança
    ini_set('display_errors', 0);
    error_reporting(E_ALL & ~E_NOTICE & ~E_DEPRECATED & ~E_STRICT & ~E_USER_NOTICE & ~E_USER_DEPRECATED);

} else {
    // AMBIENTE LOCAL (Usa .env ou defaults)
    define('DB_HOST', App\DotEnv::get('DB_HOST', 'localhost'));
    define('DB_NAME', App\DotEnv::get('DB_NAME', 'louvor_pib'));
    define('DB_USER', App\DotEnv::get('DB_USER', 'root'));
    define('DB_PASS', App\DotEnv::get('DB_PASS', ''));

    // Configurações de App
    define('APP_ENV', App\DotEnv::get('APP_ENV', 'local'));
    define('APP_DEBUG', App\DotEnv::get('APP_DEBUG', 'true') === 'true');
    define('APP_URL', App\DotEnv::get('APP_URL', 'http://localhost:8000'));
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
define('APP_VERSION', '4.1'); // Atualizado para refletir melhorias
define('APP_COPYRIGHT', '© ' . date('Y') . ' Louvor PIB Oliveira');
