<?php

declare(strict_types=1);

namespace HPanel\Tests;

use HPanel\Jwt;
use HPanel\Tests\Support\TestJwt;
use PHPUnit\Framework\TestCase;

final class JwtTest extends TestCase
{
    public function testReadsExpFromUnpaddedBase64Url(): void
    {
        self::assertSame(1893456000, Jwt::expiry(TestJwt::make(1893456000)));
    }

    public function testRejectsMalformedTokens(): void
    {
        self::assertFalse(Jwt::isWellFormed('abc'));
        self::assertFalse(Jwt::isWellFormed('eyJ.a'));
        self::assertFalse(Jwt::isWellFormed('eyJa.b.c d'));
        self::assertNull(Jwt::expiry('eyJa.bm9wZQ.c'));
    }

    public function testRejectsTrailingNewline(): void
    {
        self::assertFalse(Jwt::isWellFormed(TestJwt::make(1) . "\n"));
    }

    public function testPayloadWithoutExpHasNoExpiry(): void
    {
        $t = 'eyJ0eXAiOiJKV1QifQ.' . rtrim(strtr(base64_encode('{"sub":"x"}'), '+/', '-_'), '=') . '.c2ln';
        self::assertTrue(Jwt::isWellFormed($t));
        self::assertNull(Jwt::expiry($t));
    }
}
