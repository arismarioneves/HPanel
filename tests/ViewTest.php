<?php

declare(strict_types=1);

namespace HPanel\Tests;

use HPanel\Tests\Support\TempDir;
use HPanel\View;
use PHPUnit\Framework\TestCase;

final class ViewTest extends TestCase
{
    private string $dir;

    protected function setUp(): void
    {
        $this->dir = TempDir::make('hpanel-assets', 'js');
        file_put_contents($this->dir . '/js/a.js', 'export const a = 1;');
        file_put_contents($this->dir . '/app.css', 'body{}');
        touch($this->dir . '/js/a.js', 1_700_000_000);
        touch($this->dir . '/app.css', 1_700_000_000);
    }

    protected function tearDown(): void
    {
        array_map('unlink', [...(glob($this->dir . '/js/*') ?: []), ...(glob($this->dir . '/*.*') ?: [])]);
        @rmdir($this->dir . '/js');
        @rmdir($this->dir);
    }

    public function testAssetVersionChangesWhenAnyAssetChanges(): void
    {
        $v1 = View::assetVersion($this->dir);
        self::assertMatchesRegularExpression('/^[0-9a-f]+$/', $v1, 'precisa casar com assets/v/[0-9a-f]+/ do .htaccess');
        self::assertSame($v1, View::assetVersion($this->dir), 'sem mudança, mesma versão (cache continua válido)');

        file_put_contents($this->dir . '/js/a.js', 'export const a = 1; export const b = 2;');
        touch($this->dir . '/js/a.js', 1_700_000_000);
        $v2 = View::assetVersion($this->dir);
        self::assertNotSame($v1, $v2, 'conteúdo novo em subpasta');

        touch($this->dir . '/app.css', 1_700_000_100);
        $v3 = View::assetVersion($this->dir);
        self::assertNotSame($v2, $v3, 'mesmo tamanho, arquivo regravado no deploy');

        file_put_contents($this->dir . '/js/b.js', '');
        self::assertNotSame($v3, View::assetVersion($this->dir), 'arquivo novo');
    }
}
