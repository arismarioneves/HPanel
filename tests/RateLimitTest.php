<?php

declare(strict_types=1);

namespace HPanel\Tests;

use HPanel\RateLimit;
use PHPUnit\Framework\TestCase;

final class RateLimitTest extends TestCase
{
    private string $dir;
    private RateLimit $limit;

    protected function setUp(): void
    {
        $this->dir = sys_get_temp_dir() . '/hpanel-rl-' . bin2hex(random_bytes(4));
        $this->limit = new RateLimit($this->dir, random_bytes(32));
    }

    protected function tearDown(): void
    {
        array_map('unlink', glob($this->dir . '/*') ?: []);
        @rmdir($this->dir);
    }

    public function testBlocksAfterMaxWithinWindowAndReleasesAfter(): void
    {
        for ($i = 0; $i < 3; $i++) {
            self::assertTrue($this->limit->hit('connect', '1.1.1.1', 3, 600, 1000 + $i));
        }
        self::assertFalse($this->limit->hit('connect', '1.1.1.1', 3, 600, 1100));
        self::assertTrue($this->limit->hit('connect', '1.1.1.1', 3, 600, 1601), 'primeira tentativa saiu da janela');
    }

    public function testIpsAreIndependentAndNotStoredInClear(): void
    {
        for ($i = 0; $i < 3; $i++) {
            $this->limit->hit('connect', '1.1.1.1', 3, 600, 1000);
        }
        self::assertTrue($this->limit->hit('connect', '2.2.2.2', 3, 600, 1000));
        foreach (glob($this->dir . '/*') ?: [] as $f) {
            self::assertStringNotContainsString('1.1.1.1', basename($f) . file_get_contents($f));
        }
    }

    public function testUnwritableDirMessageHidesPath(): void
    {
        $file = tempnam(sys_get_temp_dir(), 'hpfile');
        $log = ini_set('error_log', sys_get_temp_dir() . '/hpanel-test.log');
        try {
            new RateLimit($file . '/ratelimit', random_bytes(32));
            self::fail('esperava ConfigException');
        } catch (\HPanel\ConfigException $e) {
            self::assertStringNotContainsString(basename($file), $e->getMessage());
            self::assertStringContainsString('storage/', $e->getMessage());
        } finally {
            ini_set('error_log', (string) $log);
            unlink($file);
        }
    }
}
