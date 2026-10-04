<?php

declare(strict_types=1);

// Roteador para `php -S` em desenvolvimento: emula o .htaccess.
// Uso: php -S 127.0.0.1:8099 tools/dev-router.php

$root = dirname(__DIR__);
$path = rawurldecode((string) parse_url((string) $_SERVER['REQUEST_URI'], PHP_URL_PATH));

if (!str_starts_with($path, '/.well-known/') && preg_match('#(^|/)\.|^/(storage|lib|partials|tests|tools|vendor|docs)(/|$)|^/(config(\.exemplo)?\.php|composer\.(json|lock)|package\.json|phpunit\.xml)$#', $path) === 1) {
    http_response_code(403);
    exit('Forbidden');
}
if ($path === '/') {
    require $root . '/index.php';
    return true;
}
if (is_file($root . $path)) {
    return false;
}
if (is_file($root . rtrim($path, '/') . '.php')) {
    require $root . rtrim($path, '/') . '.php';
    return true;
}
http_response_code(404);
exit('Not found');
