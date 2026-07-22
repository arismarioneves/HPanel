<?php

/**
 * Bootstrap da aplicação.
 *
 * Resolve o caminho base público (APP_BASE) a partir do config.php local.
 * Se o config.php não existir, assume a raiz do domínio ("/").
 *
 * APP_BASE é sempre normalizado para começar e terminar com "/".
 * Deve ser incluído no topo de cada página que renderiza links.
 */

if (!defined('APP_BASE')) {
    $config = is_file(__DIR__ . '/config.php') ? (require __DIR__ . '/config.php') : [];

    $base = is_array($config) ? ($config['base'] ?? '/') : '/';
    $base = str_replace('\\', '/', (string) $base);
    $base = '/' . trim($base, '/');
    if ($base !== '/') {
        $base .= '/';
    }

    define('APP_BASE', $base);
}
