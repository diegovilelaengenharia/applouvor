<?php
// src/config/config.php

// ======================================
// AUTOLOADER E VARIÁVEIS DE AMBIENTE
// ======================================
// Carrega autoloader de classes
require_once __DIR__ . '/autoload.php';

// Carrega variáveis de ambiente do .env (na RAIZ do projeto, conforme CLAUDE.md)
$dotenv = new App\DotEnv(__DIR__ . '/../..');
$dotenv->load();

// ======================================
// DETECÇÃO DE AMBIENTE
// ======================================
// Se o arquivo .env NÃO existir, assumimos que é PRODUÇÃO
// (pois no local sempre temos o .env, e no servidor é ignorado pelo git)
$envPath = __DIR__ . '/../../.env';
$isProduction = !file_exists($envPath);

// Debug (opcional, remova se necessário)
// echo "Env Exists: " . (file_exists($envPath) ? 'Yes' : 'No') . "<br>";

// ======================================
// CREDENCIAIS DO BANCO DE DADOS
// ======================================
if ($isProduction) {
    // Credenciais lidas de variáveis de ambiente do servidor (Hostinger)
    // Configure em: Painel Hostinger > Avancado > PHP Config ou via .htaccess:
    //   SetEnv DB_HOST srv1074.hstgr.io
    //   SetEnv DB_NAME u884436813_applouvor
    //   SetEnv DB_USER u884436813_admin
    //   SetEnv DB_PASS sua_senha_aqui
    define('DB_HOST', getenv('DB_HOST') ?: 'srv1074.hstgr.io');
    define('DB_NAME', getenv('DB_NAME') ?: 'u884436813_applouvor');
    define('DB_USER', getenv('DB_USER') ?: 'u884436813_admin');
    define('DB_PASS', getenv('DB_PASS') ?: ''); // Senha deve vir do servidor!

    define('APP_ENV',   'production');
    define('APP_DEBUG', false);
    define('APP_URL',   'https://vilela.eng.br/applouvor');

    define('VAPID_PUBLIC_KEY',  getenv('VAPID_PUBLIC_KEY')  ?: '');
    define('VAPID_PRIVATE_KEY', getenv('VAPID_PRIVATE_KEY') ?: '');

    ini_set('display_errors', 0);
    error_reporting(E_ALL & ~E_NOTICE & ~E_DEPRECATED & ~E_STRICT & ~E_USER_NOTICE & ~E_USER_DEPRECATED);

} else {
    // AMBIENTE LOCAL (Usa .env ou defaults)
    define('DB_HOST', App\DotEnv::get('DB_HOST', 'localhost'));
    define('DB_NAME', App\DotEnv::get('DB_NAME', 'pibo_louvor'));
    define('DB_USER', App\DotEnv::get('DB_USER', 'root'));
    define('DB_PASS', App\DotEnv::get('DB_PASS', ''));

    // Configurações de App
    define('APP_ENV', App\DotEnv::get('APP_ENV', 'local'));
    define('APP_DEBUG', App\DotEnv::get('APP_DEBUG', 'true') === 'true');
    
    // APP_URL detectado automaticamente do domínio atual (funciona com localhost e applouvor.local)
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost:8080';
    define('APP_URL', $protocol . '://' . $host);

    define('VAPID_PUBLIC_KEY',  App\DotEnv::get('VAPID_PUBLIC_KEY',  ''));
    define('VAPID_PRIVATE_KEY', App\DotEnv::get('VAPID_PRIVATE_KEY', ''));
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
define('APP_VERSION', '5.1.0'); // Milestone 1 — Modernização + Features Faltantes (2026-05-18)
define('APP_COPYRIGHT', '© ' . date('Y') . ' Louvor PIB Oliveira');
