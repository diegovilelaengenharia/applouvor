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
// CREDENCIAIS DO BANCO DE DADOS
// ======================================
// Agora usando variáveis de ambiente (.env)
// Fallback para valores antigos se .env não existir
define('DB_HOST', App\DotEnv::get('DB_HOST', 'srv1074.hstgr.io'));
define('DB_NAME', App\DotEnv::get('DB_NAME', 'u884436813_applouvor'));
define('DB_USER', App\DotEnv::get('DB_USER', 'u884436813_admin'));
define('DB_PASS', App\DotEnv::get('DB_PASS', 'Diego@159753'));

// ======================================
// CONFIGURAÇÕES DA APLICAÇÃO
// ======================================
define('APP_ENV', App\DotEnv::get('APP_ENV', 'production'));
define('APP_DEBUG', App\DotEnv::get('APP_DEBUG', 'false') === 'true');
define('APP_URL', App\DotEnv::get('APP_URL', 'https://applouvor.diegovilelaengenharia.com.br'));

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
