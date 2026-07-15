<?php
/**
 * autoload.php — carregador PSR-4 para o namespace App\ (FASE 01, ciclo v7)
 *
 * Sem composer/vendor de propósito: a Hostinger deste plano não roda `composer install` no
 * deploy (o webhook só clona o repo), então o autoloader precisa ser autossuficiente.
 * Procura em src/ primeiro e depois em src/classes/, para App\Foo e App\Bar\Foo funcionarem
 * igual, independente da subpasta onde a classe mora.
 */

declare(strict_types=1);

spl_autoload_register(function (string $class): void {
    $prefix = 'App\\';
    if (!str_starts_with($class, $prefix)) {
        return;
    }

    $relative = substr($class, strlen($prefix));
    $relativePath = str_replace('\\', '/', $relative) . '.php';

    foreach ([__DIR__ . '/' . $relativePath, __DIR__ . '/classes/' . $relativePath] as $file) {
        if (is_file($file)) {
            require $file;
            return;
        }
    }
});
