<?php

declare(strict_types=1);

namespace HPanel;

final class View
{
    public static function boot(): Context
    {
        Http::securityHeaders();
        header('Cache-Control: no-store');
        try {
            return App::context();
        } catch (ConfigException $e) {
            http_response_code(500);
            self::installScreen($e->getMessage());
            exit;
        }
    }

    public static function redirect(string $path): never
    {
        header('Location: ' . App::context()->config->base . ltrim($path, '/'), true, 302);
        exit;
    }

    public static function e(mixed $value): string
    {
        return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }

    public static function icon(string $name, string $class = 'i'): string
    {
        return '<svg class="' . self::e($class) . '" aria-hidden="true"><use href="assets/icons.svg#' . self::e($name) . '"></use></svg>';
    }

    /** @param array<string, mixed> $boot dados entregues ao JS em <script type="application/json" id="boot"> */
    public static function render(string $page, string $title, array $boot, string $bodyFile): never
    {
        $ctx = App::context();
        $base = $ctx->config->base;
        $connected = $ctx->session->isConnected();
        $demo = $ctx->isDemo();
        $bootJson = json_encode($boot, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE);
        require App::root() . '/partials/layout.php';
        exit;
    }

    private static function installScreen(string $message): void
    {
        echo '<!DOCTYPE html><html lang="pt-BR"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1">'
            . '<title>Instalação · HPanel</title><link rel="stylesheet" href="assets/css/app.css"><script src="assets/js/theme-init.js"></script></head>'
            . '<body><main class="container narrow"><section class="card stack"><h1>Quase lá</h1><p>' . self::e($message) . '</p>'
            . '<p class="muted">Depois de ajustar, recarregue esta página.</p></section></main></body></html>';
    }
}
