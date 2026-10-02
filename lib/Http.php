<?php

declare(strict_types=1);

namespace HPanel;

final class Http
{
    public const CSP = "default-src 'self'; img-src 'self' data:; style-src 'self'; script-src 'self'; font-src 'self'; "
        . "connect-src 'self'; frame-ancestors 'none'; base-uri 'self'; form-action 'self'; object-src 'none'";

    public static function securityHeaders(): void
    {
        header('Content-Security-Policy: ' . self::CSP);
        header('X-Content-Type-Options: nosniff');
        header('X-Frame-Options: DENY');
        header('Referrer-Policy: no-referrer');
        header('Permissions-Policy: camera=(), microphone=(), geolocation=(), payment=()');
    }

    /** Mutação só com header próprio e origem do mesmo host (inclui porta). */
    public static function checkCsrf(array $server): void
    {
        $denied = ApiError::forbidden('Requisição recusada por segurança. Recarregue a página e tente de novo.');
        if (($server['HTTP_X_HPANEL'] ?? '') !== '1') {
            throw $denied;
        }
        $host = strtolower((string) ($server['HTTP_HOST'] ?? ''));
        $origin = (string) ($server['HTTP_ORIGIN'] ?? $server['HTTP_REFERER'] ?? '');
        $originHost = strtolower((string) parse_url($origin, PHP_URL_HOST));
        $port = parse_url($origin, PHP_URL_PORT);
        if ($port !== null && $port !== false) {
            $originHost .= ':' . $port;
        }
        if ($host === '' || $originHost !== $host) {
            throw $denied;
        }
    }

    /** @return array{0:int, 1:array} status HTTP e envelope */
    public static function run(callable $fn): array
    {
        try {
            return [200, ['ok' => true, 'data' => $fn()]];
        } catch (ApiError $e) {
            return [$e->status, ['ok' => false, 'code' => $e->errorCode, 'message' => $e->getMessage()]];
        } catch (ConfigException $e) {
            error_log('HPanel config: ' . $e->getMessage());
            return [500, ['ok' => false, 'code' => 'internal', 'message' => $e->getMessage()]];
        } catch (\Throwable $e) {
            error_log('HPanel: ' . $e);
            return [500, ['ok' => false, 'code' => 'internal', 'message' => 'Algo deu errado do nosso lado. Tente novamente.']];
        }
    }

    /**
     * @param list<string> $methods
     * @param callable(Request, Context): mixed $handler
     */
    public static function handle(array $methods, callable $handler, bool $refreshToken = true): never
    {
        self::securityHeaders();
        header('Content-Type: application/json; charset=utf-8');
        header('Cache-Control: no-store');

        [$status, $payload] = self::run(static function () use ($methods, $handler, $refreshToken): mixed {
            $request = Request::fromGlobals();
            if (!in_array($request->method, $methods, true)) {
                throw ApiError::methodNotAllowed();
            }
            if ($request->method !== 'GET') {
                self::checkCsrf($_SERVER);
            }
            $ctx = App::context();
            if ($refreshToken && $ctx->session->isConnected()) {
                try {
                    // Renovação oportunista: toda chamada autenticada mantém o token vivo.
                    $ctx->freshToken();
                } catch (ApiError) {
                    // O handler relança o erro se de fato precisar da Hostinger.
                }
            }
            return $handler($request, $ctx);
        });

        http_response_code($status);
        echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }
}
