<?php

declare(strict_types=1);

namespace HPanel\Tests;

use HPanel\ApiError;
use HPanel\Tests\Support\TestJwt;
use HPanel\Validate;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class ValidateTest extends TestCase
{
    public function testOrderIdAcceptsPositiveIntegers(): void
    {
        self::assertSame(123, Validate::orderId('123'));
        self::assertSame(7, Validate::orderId(7));
    }

    #[DataProvider('badOrderIds')]
    public function testOrderIdRejects(mixed $v): void
    {
        $this->expectException(ApiError::class);
        Validate::orderId($v);
    }

    public static function badOrderIds(): array
    {
        return [['0'], ['-1'], ['1e3'], ['12a'], [''], [null], [[1]], [1.5]];
    }

    public function testDomainAcceptsRealWorldNamesAndLowercases(): void
    {
        self::assertSame('loja.exemplo.com.br', Validate::domain('Loja.Exemplo.com.br'));
        self::assertSame('xn--aco-tla.com', Validate::domain('xn--aco-tla.com'));
        self::assertSame('a-b.co', Validate::domain('a-b.co'));
    }

    #[DataProvider('badDomains')]
    public function testDomainRejects(mixed $v): void
    {
        $this->expectException(ApiError::class);
        Validate::domain($v);
    }

    public static function badDomains(): array
    {
        return [['exemplo'], ['a..b.com'], ['-a.com'], ['a-.com'], ['exa mple.com'], ['javascript:alert(1)'], ['../etc/passwd'], ['a.com/x'], [null]];
    }

    public function testDbNameAndPhpVersion(): void
    {
        self::assertSame('u123_loja', Validate::dbName('u123_loja'));
        self::assertSame('8.2', Validate::phpVersion('8.2'));
        $this->expectException(ApiError::class);
        Validate::dbName('loja`; DROP');
    }

    public function testPhpVersionRejectsGarbage(): void
    {
        $this->expectException(ApiError::class);
        Validate::phpVersion('8.2; rm');
    }

    public function testJwtTrimsAndRejectsNonTokens(): void
    {
        $t = TestJwt::make(2000000000);
        self::assertSame($t, Validate::jwt("  {$t}\n"));
        $this->expectException(ApiError::class);
        Validate::jwt('jwt=' . $t);
    }
}
