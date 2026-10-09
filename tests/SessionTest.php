<?php

declare(strict_types=1);

namespace HPanel\Tests;

use HPanel\ApiError;
use HPanel\Session;
use HPanel\SessionStore;
use PHPUnit\Framework\TestCase;
use HPanel\Tests\Support\TempDir;

final class SessionTest extends TestCase
{
    private string $dir;
    private SessionStore $store;
    /** @var list<array{0:string,1:string,2:array}> */
    private array $cookies = [];
    private int $now = 1_000_000;

    protected function setUp(): void
    {
        $this->dir = TempDir::make('hpanel-s');
        $this->store = new SessionStore($this->dir, random_bytes(32));
    }

    protected function tearDown(): void
    {
        array_map('unlink', glob($this->dir . '/*') ?: []);
        @rmdir($this->dir);
    }

    private function session(?string $sid = null): Session
    {
        return new Session(
            $this->store,
            $sid,
            '/painel/',
            true,
            function (string $n, string $v, array $o): void { $this->cookies[] = [$n, $v, $o]; },
            fn(): int => $this->now,
        );
    }

    public function testStartIssuesHardenedCookieAndPersists(): void
    {
        $s = $this->session();
        $s->start(['token' => 't1']);

        [$name, $sid, $opts] = end($this->cookies);
        self::assertSame('hp_sid', $name);
        self::assertMatchesRegularExpression('/^[A-Za-z0-9_-]{43}$/', $sid);
        self::assertTrue($opts['httponly']);
        self::assertTrue($opts['secure']);
        self::assertSame('Strict', $opts['samesite']);
        self::assertSame('/painel/', $opts['path']);
        self::assertSame($this->now + SessionStore::IDLE_TTL, $opts['expires']);
        self::assertSame(['token' => 't1'], $this->session($sid)->data());
    }

    public function testStartRegeneratesSidAndDropsPreviousSession(): void
    {
        $first = $this->session();
        $first->start(['token' => 'velho']);
        $oldSid = end($this->cookies)[1];

        $again = $this->session($oldSid);
        $again->start(['token' => 'novo']);
        $newSid = end($this->cookies)[1];

        self::assertNotSame($oldSid, $newSid);
        self::assertFalse($this->session($oldSid)->isConnected());
        self::assertSame('novo', $this->session($newSid)->data()['token']);
    }

    public function testDestroyRemovesServerDataAndExpiresCookie(): void
    {
        $s = $this->session();
        $s->start(['token' => 't']);
        $sid = end($this->cookies)[1];

        $this->session($sid)->destroy();

        self::assertFalse($this->session($sid)->isConnected());
        [, $value, $opts] = end($this->cookies);
        self::assertSame('', $value);
        self::assertLessThan($this->now, $opts['expires']);
    }

    public function testCachedServesWithinTtlAndRecomputesAfter(): void
    {
        $s = $this->session();
        $s->start(['token' => 't']);
        $calls = 0;
        $produce = function () use (&$calls): array { $calls++; return ['n' => $calls]; };

        self::assertSame(['n' => 1], $s->cached('k', 60, $produce)['value']);
        $this->now += 59;
        self::assertSame(['n' => 1], $s->cached('k', 60, $produce)['value']);
        $this->now += 2;
        $r = $s->cached('k', 60, $produce);
        self::assertSame(['n' => 2], $r['value']);
        self::assertSame($this->now, $r['cachedAt']);
        self::assertSame(['n' => 3], $s->cached('k', 60, $produce, true)['value'], 'refresh ignora o cache');
    }

    public function testCachedDoesNotOverwriteTokenRenewedConcurrently(): void
    {
        $s = $this->session();
        $s->start(['token' => 'antigo']);
        $sid = end($this->cookies)[1];

        $s->cached('servers', 60, function () use ($sid): array {
            // outra requisição renova o token enquanto esta busca dados
            $other = $this->session($sid);
            $d = $other->data();
            $d['token'] = 'renovado';
            $other->write($d);
            return ['x'];
        });

        $fresh = $this->session($sid)->data();
        self::assertSame('renovado', $fresh['token']);
        self::assertSame(['x'], $fresh['cache']['servers']['value']);
    }

    public function testCachedWithoutSessionIsNotConnected(): void
    {
        $this->expectExceptionObject(ApiError::notConnected());
        $this->session()->cached('k', 60, fn() => 1);
    }
}
