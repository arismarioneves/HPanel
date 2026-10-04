<?php

declare(strict_types=1);

namespace HPanel\Tests;

use HPanel\ApiError;
use HPanel\Http;
use PHPUnit\Framework\TestCase;

final class HttpTest extends TestCase
{
    private const OK = ['HTTP_X_HPANEL' => '1', 'HTTP_HOST' => 'painel.exemplo.com', 'HTTP_ORIGIN' => 'https://painel.exemplo.com'];

    public function testCsrfAcceptsSameOriginWithHeader(): void
    {
        Http::checkCsrf(self::OK);
        Http::checkCsrf(['HTTP_X_HPANEL' => '1', 'HTTP_HOST' => 'localhost:8099', 'HTTP_ORIGIN' => 'http://localhost:8099']);
        Http::checkCsrf(['HTTP_X_HPANEL' => '1', 'HTTP_HOST' => 'painel.exemplo.com', 'HTTP_REFERER' => 'https://painel.exemplo.com/settings']);
        $this->addToAssertionCount(3);
    }

    public function testCsrfRejections(): void
    {
        $cases = [
            'sem header' => ['HTTP_HOST' => 'painel.exemplo.com', 'HTTP_ORIGIN' => 'https://painel.exemplo.com'],
            'outra origem' => ['HTTP_ORIGIN' => 'https://evil.com'] + self::OK,
            'porta diferente' => ['HTTP_ORIGIN' => 'https://painel.exemplo.com:8443'] + self::OK,
            'sem origem' => ['HTTP_X_HPANEL' => '1', 'HTTP_HOST' => 'painel.exemplo.com'],
            'subdominio' => ['HTTP_ORIGIN' => 'https://x.painel.exemplo.com'] + self::OK,
        ];
        foreach ($cases as $name => $server) {
            try {
                Http::checkCsrf($server);
                self::fail("aceitou: {$name}");
            } catch (ApiError $e) {
                self::assertSame('forbidden', $e->errorCode, $name);
            }
        }
    }

    public function testRunWrapsSuccess(): void
    {
        self::assertSame([200, ['ok' => true, 'data' => ['n' => 1]]], Http::run(static fn() => ['n' => 1]));
    }

    public function testRunMapsApiError(): void
    {
        [$status, $body] = Http::run(static fn() => throw ApiError::sessionExpired());
        self::assertSame(401, $status);
        self::assertSame('session_expired', $body['code']);
        self::assertFalse($body['ok']);
    }

    public function testRunHidesInternalErrors(): void
    {
        $log = ini_set('error_log', sys_get_temp_dir() . '/hpanel-test.log');
        [$status, $body] = Http::run(static fn() => throw new \RuntimeException('/var/www/segredo.php linha 3'));
        ini_set('error_log', (string) $log);

        self::assertSame(500, $status);
        self::assertSame('internal', $body['code']);
        self::assertStringNotContainsString('segredo', $body['message']);
    }
}
