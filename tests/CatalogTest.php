<?php

declare(strict_types=1);

namespace HPanel\Tests;

use HPanel\ApiError;
use HPanel\Catalog;
use HPanel\DataSource;
use HPanel\Session;
use HPanel\SessionStore;
use PHPUnit\Framework\TestCase;
use HPanel\Tests\Support\TempDir;

final class CatalogTest extends TestCase
{
    private string $dir;
    private int $fetches = 0;

    protected function setUp(): void
    {
        $this->dir = TempDir::make('hpanel-cat');
    }

    protected function tearDown(): void
    {
        array_map('unlink', glob($this->dir . '/*') ?: []);
        @rmdir($this->dir);
    }

    private function catalog(): Catalog
    {
        $store = new SessionStore($this->dir, random_bytes(32));
        $session = new Session($store, null, '/', false, static function (): void {}, static fn() => 1000);
        $session->start(['token' => 't']);
        $source = $this->createMock(DataSource::class);
        $source->method('servers')->willReturnCallback(function (): array {
            $this->fetches++;
            return [[
                'orderId' => 10,
                'websites' => [
                    ['domain' => 'blog.loja.com', 'vhostType' => 'subdomain', 'username' => 'u10'],
                    ['domain' => 'loja.com', 'vhostType' => 'main', 'username' => 'u10'],
                ],
            ]];
        });
        return new Catalog($session, static fn() => $source);
    }

    public function testSiteResolvesUsernameFromOwnedServer(): void
    {
        self::assertSame(['orderId' => 10, 'domain' => 'blog.loja.com', 'username' => 'u10'], $this->catalog()->site(10, 'blog.loja.com'));
    }

    public function testDomainFromAnotherServerIsNotFound(): void
    {
        $this->expectExceptionObject(ApiError::notFound('Site não encontrado neste servidor.'));
        $this->catalog()->site(10, 'alheio.com');
    }

    public function testUnknownServerRetriesOnceWithFreshDataThenFails(): void
    {
        $c = $this->catalog();
        try {
            $c->server(99);
            self::fail('esperava not_found');
        } catch (ApiError $e) {
            self::assertSame('not_found', $e->errorCode);
        }
        self::assertSame(2, $this->fetches, '1 do cache inicial + 1 refresh');
    }

    public function testResolveDefaultsToMainSite(): void
    {
        $c = $this->catalog();
        self::assertSame('loja.com', $c->resolve(10, null)['domain']);
        self::assertSame('blog.loja.com', $c->resolve(10, 'BLOG.loja.com')['domain']);
    }
}
