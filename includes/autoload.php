<?php
/**
 * Autoloader PSR-4 Simples
 * Carrega automaticamente classes do namespace App\
 */

spl_autoload_register(function ($class) {
    // Namespace base do projeto
    $prefix = 'App\\';
    
    // Diretório base para o namespace
    $base_dir = __DIR__ . '/classes/';
    
    // Verifica se a classe usa o namespace base
    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        // Não é do nosso namespace, ignora
        return;
    }
    
    // Pega o nome relativo da classe
    $relative_class = substr($class, $len);
    
    // Substitui namespace separators por directory separators
    // e adiciona .php
    $file = $base_dir . str_replace('\\', '/', $relative_class) . '.php';
    
    // Se o arquivo existe, carrega
    if (file_exists($file)) {
        require $file;
    }
});
