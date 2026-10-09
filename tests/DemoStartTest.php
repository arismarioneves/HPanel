<?php

declare(strict_types=1);

namespace HPanel\Tests;

use HPanel\ApiError;
use HPanel\DemoStart;
use HPanel\RateLimit;
use HPanel\Session;
use HPanel\SessionStore;
use PHPUnit\Framework\TestCase;
use HPanel\Tests\Support\TempDir;

final class DemoStartTest extends TestCase
{
    private string $dir;

    protected function setUp(): void
    {
        $this->dir = TempDir::make('hpanel-demo', 's', 'r');
    }

    protected function tearDown(): void
    {
        foreach (['/s', '/r'] as $sub) {
            array_map('unlink', glob($this->dir . $sub . '/*') ?: []);
            @rmdir($this->dir . $sub);
        }
        @rmdir($this->dir);
    }

    public function testRateLimitedPerIpAfterMax(): void
    {
        $store = new SessionStore($this->dir . '/s', random_bytes(32));
        $limit = new RateLimit($this->dir . '/r', random_bytes(32));
        $session = fn(): Session => new Session($store, null, '/', true, static function (): void {}, static fn(): int => 1000);

        for ($i = 0; $i < DemoStart::MAX; $i++) {
            self::assertGreaterThan(0, DemoStart::start($limit, $session(), '1.1.1.1', 1000));
        }
        try {
            DemoStart::start($limit, $session(), '1.1.1.1', 1000);
            self::fail('esperava rate limit');
        } catch (ApiError $e) {
            self::assertSame('rate_limited', $e->errorCode);
        }
        self::assertCount(DemoStart::MAX, glob($this->dir . '/s/*.json') ?: []);
        self::assertGreaterThan(0, DemoStart::start($limit, $session(), '2.2.2.2', 1000));
    }
}
