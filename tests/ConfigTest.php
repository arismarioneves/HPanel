<?php

declare(strict_types=1);

namespace HPanel\Tests;

use HPanel\Config;
use PHPUnit\Framework\TestCase;

final class ConfigTest extends TestCase
{
    private string $dir;

    protected function setUp(): void
    {
        $this->dir = sys_get_temp_dir() . '/hpanel-cfg-' . bin2hex(random_bytes(4));
        mkdir($this->dir);
    }

    protected function tearDown(): void
    {
        array_map('unlink', glob($this->dir . '/*') ?: []);
        rmdir($this->dir);
    }

    public function testGeneratesAndPersistsSecretOnFirstLoad(): void
    {
        file_put_contents($this->dir . '/config.php', "<?php return ['base' => 'painel'];");
        $first = Config::load($this->dir);
        $second = Config::load($this->dir);

        self::assertSame(32, strlen($first->appSecret));
        self::assertSame($first->appSecret, $second->appSecret);
        self::assertSame('/painel/', $second->base, 'base existente é preservada');
    }

    public function testMalformedSecretIsRejectedNotReplaced(): void
    {
        $src = "<?php return ['app_secret' => 'curto'];";
        file_put_contents($this->dir . '/config.php', $src);

        try {
            Config::load($this->dir);
            self::fail('esperava ConfigException');
        } catch (\HPanel\ConfigException $e) {
            self::assertStringContainsString('app_secret inválido', $e->getMessage());
        }
        self::assertSame($src, file_get_contents($this->dir . '/config.php'), 'config.php não é sobrescrito');
    }

    public function testSecretWithTrailingNewlineIsRejected(): void
    {
        $this->expectException(\HPanel\ConfigException::class);
        file_put_contents($this->dir . '/config.php', "<?php return ['app_secret' => '" . str_repeat('ab', 32) . "\n'];");
        Config::load($this->dir);
    }

    public function testNormalizesBaseAndDefaultsStorage(): void
    {
        $c = Config::fromArray(['base' => '\\sub\\pasta\\', 'app_secret' => str_repeat('ab', 32)], '/raiz');
        self::assertSame('/sub/pasta/', $c->base);
        self::assertSame('/raiz/storage', $c->storageDir);
        self::assertSame('/', Config::fromArray(['app_secret' => str_repeat('ab', 32)], '/r')->base);
    }
}
