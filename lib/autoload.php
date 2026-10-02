<?php

declare(strict_types=1);

// Autoload de runtime (Composer é usado apenas para testes).
spl_autoload_register(static function (string $class): void {
    if (!str_starts_with($class, 'HPanel\\')) {
        return;
    }
    $file = __DIR__ . '/' . str_replace('\\', '/', substr($class, strlen('HPanel\\'))) . '.php';
    if (is_file($file)) {
        require $file;
    }
});
