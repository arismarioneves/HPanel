<?php

declare(strict_types=1);

namespace HPanel\Tests;

use HPanel\Crypto;
use PHPUnit\Framework\TestCase;

final class CryptoTest extends TestCase
{
    private string $secret;
    private string $key;

    protected function setUp(): void
    {
        $this->secret = str_repeat("\x07", 32);
        $this->key = Crypto::key('sid-um', $this->secret);
    }

    public function testRoundTrip(): void
    {
        self::assertSame('{"token":"abc"}', Crypto::open(Crypto::seal('{"token":"abc"}', $this->key), $this->key));
    }

    public function testKeyFromAnotherSidCannotOpen(): void
    {
        $sealed = Crypto::seal('segredo', $this->key);
        self::assertNull(Crypto::open($sealed, Crypto::key('sid-dois', $this->secret)));
    }

    public function testKeyFromAnotherSecretCannotOpen(): void
    {
        $sealed = Crypto::seal('segredo', $this->key);
        self::assertNull(Crypto::open($sealed, Crypto::key('sid-um', str_repeat("\x08", 32))));
    }

    public function testTamperedCiphertextIsRejected(): void
    {
        $bin = base64_decode(Crypto::seal('segredo', $this->key));
        $last = strlen($bin) - 1;
        $bin[$last] = chr(ord($bin[$last]) ^ 1);
        self::assertNull(Crypto::open(base64_encode($bin), $this->key));
    }

    public function testIvIsFreshOnEverySeal(): void
    {
        self::assertNotSame(Crypto::seal('mesmo texto', $this->key), Crypto::seal('mesmo texto', $this->key));
    }

    public function testGarbageIsRejected(): void
    {
        self::assertNull(Crypto::open('***', $this->key));
        self::assertNull(Crypto::open(base64_encode('curto'), $this->key));
    }

    public function testFileIdDoesNotRevealSid(): void
    {
        $id = Crypto::fileId('sid-um', $this->secret);
        self::assertMatchesRegularExpression('/^[a-f0-9]{64}$/', $id);
        self::assertNotSame($id, Crypto::fileId('sid-um', str_repeat("\x08", 32)));
    }
}
