<?php
/**
 * Autoloader PSR-4 Inteligente e Unificado
 * Carrega automaticamente classes do namespace App\
 * buscando na raiz de src/ e na pasta src/classes/
 */
spl_autoload_register(function ($class) {
    $prefix = 'App\\';
    
    // Verifica se a classe usa o prefixo do namespace
    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        return;
    }

    // Pega o nome relativo da classe
    $relative_class = substr($class, $len);
    $relative_path = str_replace('\\', '/', $relative_class) . '.php';

    // 1. Tenta carregar a partir de src/ (ex: App\Router -> src/Router.php)
    $file = __DIR__ . '/' . $relative_path;
    if (file_exists($file)) {
        require $file;
        return;
    }

    // 2. Tenta carregar a partir de src/classes/ (ex: App\DotEnv -> src/classes/DotEnv.php)
    $fileClasses = __DIR__ . '/classes/' . $relative_path;
    if (file_exists($fileClasses)) {
        require $fileClasses;
        return;
    }
});
