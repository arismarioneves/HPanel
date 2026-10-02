<?php

declare(strict_types=1);

namespace HPanel\Tests;

use HPanel\Config;
use HPanel\Context;
use HPanel\RateLimit;
use HPanel\Session;
use HPanel\SessionStore;
use HPanel\Tests\Support\FakeTransport;
use HPanel\Tests\Support\TestJwt;
use PHPUnit\Framework\TestCase;

final class ContextTest extends TestCase
{
    private string $dir;
    private int $now = 2_000_000_000;

    protected function setUp(): void
    {
        $this->dir = sys_get_temp_dir() . '/hpanel-ctx-' . bin2hex(random_bytes(4));
    }

    protected function tearDown(): void
    {
        array_map('unlink', glob($this->dir . '/*/*') ?: []);
        array_map('rmdir', glob($this->dir . '/*') ?: []);
        @rmdir($this->dir);
    }

    public function testTokenIsRenewedOncePerRequest(): void
    {
        $secret = random_bytes(32);
        $store = new SessionStore($this->dir . '/sessions', $secret);
        $sid = null;
        $first = new Session($store, null, '/', false, function (string $n, string $v) use (&$sid): void { $sid = $v; }, fn() => $this->now);
        $first->start(['token' => TestJwt::make($this->now + 600), 'gaid' => 'g']);

        $new = TestJwt::make($this->now + 3600);
        $http = new FakeTransport([['status' => 200, 'cookies' => ['jwt' => $new]]]);
        $session = new Session($store, $sid, '/', false, static function (): void {}, fn() => $this->now);
        $ctx = new Context(Config::fromArray(['app_secret' => bin2hex($secret)], $this->dir), $session, $http, new RateLimit($this->dir . '/rl', $secret), fn() => $this->now);

        self::assertSame($new, $ctx->freshToken());
        $ctx->source();
        self::assertSame($new, $ctx->freshToken());
        self::assertCount(1, $http->calls, 'renovação oportunista + source() fazem uma só chamada');
    }
}
