<?php

declare(strict_types=1);

namespace HPanel\Tests;

use HPanel\ApiError;
use HPanel\HostingerAuth;
use HPanel\Session;
use HPanel\SessionStore;
use HPanel\Tests\Support\FakeTransport;
use HPanel\Tests\Support\TestJwt;
use PHPUnit\Framework\TestCase;

final class HostingerAuthTest extends TestCase
{
    private string $dir;
    private SessionStore $store;
    private int $now = 2_000_000_000;
    private ?string $sid = null;

    protected function setUp(): void
    {
        $this->dir = sys_get_temp_dir() . '/hpanel-auth-' . bin2hex(random_bytes(4));
        $this->store = new SessionStore($this->dir, random_bytes(32));
    }

    protected function tearDown(): void
    {
        array_map('unlink', glob($this->dir . '/*') ?: []);
        @rmdir($this->dir);
    }

    private function sessionWith(string $token): Session
    {
        $s = new Session($this->store, null, '/', false, function (string $n, string $v): void { $this->sid = $v; }, fn() => $this->now);
        $s->start(['token' => $token, 'gaid' => 'g']);
        return new Session($this->store, $this->sid, '/', false, static function (): void {}, fn() => $this->now);
    }

    public function testFreshTokenIsUsedWithoutCallingHostinger(): void
    {
        $token = TestJwt::make($this->now + 3600);
        $http = new FakeTransport([]);
        self::assertSame($token, (new HostingerAuth($http))->ensureFresh($this->sessionWith($token), $this->now));
        self::assertSame([], $http->calls);
    }

    public function testRenewsNearExpiryAndPersistsNewToken(): void
    {
        $old = TestJwt::make($this->now + 600);
        $new = TestJwt::make($this->now + 3600);
        $http = new FakeTransport([['status' => 200, 'cookies' => ['jwt' => $new]]]);
        $session = $this->sessionWith($old);

        self::assertSame($new, (new HostingerAuth($http))->ensureFresh($session, $this->now));
        self::assertSame('jwt=' . $old . '; language=pt_BR', $http->calls[0]['cookie']);
        self::assertSame($new, $session->reload()['token']);
        self::assertSame('g', $session->reload()['gaid'], 'demais campos preservados');
    }

    public function testFailedRenewKeepsStillValidToken(): void
    {
        $old = TestJwt::make($this->now + 300);
        $http = new FakeTransport([['status' => 500]]);
        self::assertSame($old, (new HostingerAuth($http))->ensureFresh($this->sessionWith($old), $this->now));
    }

    public function testFailedRenewOfExpiredTokenIsSessionExpired(): void
    {
        $http = new FakeTransport([['status' => 401]]);
        $this->expectExceptionObject(ApiError::sessionExpired());
        (new HostingerAuth($http))->ensureFresh($this->sessionWith(TestJwt::make($this->now - 10)), $this->now);
    }

    public function testRenewRejectsMalformedCookie(): void
    {
        $http = new FakeTransport([['status' => 200, 'cookies' => ['jwt' => 'deleted']]]);
        self::assertNull((new HostingerAuth($http))->renew(TestJwt::make($this->now)));
    }

    public function testForceRenewsEvenWhenFresh(): void
    {
        $new = TestJwt::make($this->now + 7200);
        $http = new FakeTransport([['status' => 200, 'cookies' => ['jwt' => $new]]]);
        self::assertSame($new, (new HostingerAuth($http))->ensureFresh($this->sessionWith(TestJwt::make($this->now + 3600)), $this->now, true));
    }

    public function testForceRenewFailureIsUpstreamWhenTokenStillValid(): void
    {
        $http = new FakeTransport([['status' => 500]]);
        $this->expectExceptionObject(ApiError::upstream());
        (new HostingerAuth($http))->ensureFresh($this->sessionWith(TestJwt::make($this->now + 3600)), $this->now, true);
    }
}
