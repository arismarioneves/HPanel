<?php

declare(strict_types=1);

namespace HPanel\Tests;

use HPanel\SessionStore;
use HPanel\Tests\Support\TestJwt;
use PHPUnit\Framework\TestCase;
use HPanel\Tests\Support\TempDir;

final class SessionStoreTest extends TestCase
{
    private string $dir;
    private string $secret;
    private SessionStore $store;

    protected function setUp(): void
    {
        $this->dir = TempDir::make('hpanel-ss');
        $this->secret = random_bytes(32);
        $this->store = new SessionStore($this->dir, $this->secret);
    }

    protected function tearDown(): void
    {
        array_map('unlink', glob($this->dir . '/*') ?: []);
        @rmdir($this->dir);
    }

    private function files(): array
    {
        return glob($this->dir . '/*.json') ?: [];
    }

    public function testRoundTrip(): void
    {
        $this->store->save('sid-1', ['token' => 'eyJ.x.y', 'gaid' => 'g'], 1000);
        self::assertSame(['token' => 'eyJ.x.y', 'gaid' => 'g'], $this->store->load('sid-1'));
        self::assertNull($this->store->load('sid-2'));
    }

    public function testDiskHoldsNeitherTokenNorSid(): void
    {
        $token = TestJwt::make(2000000000);
        $this->store->save('sid-secreto', ['token' => $token], 1000);
        [$file] = $this->files();
        $raw = (string) file_get_contents($file);

        self::assertStringNotContainsString($token, $raw);
        self::assertStringNotContainsString('sid-secreto', $raw . basename($file));
    }

    public function testOtherServerSecretCannotReadAndDropsFile(): void
    {
        $this->store->save('sid-1', ['token' => 't'], 1000);
        $intruder = new SessionStore($this->dir, random_bytes(32));
        self::assertNull($intruder->load('sid-1'), 'nome do arquivo depende do secret');
        self::assertCount(1, $this->files());
    }

    public function testTamperedFileIsDiscarded(): void
    {
        $this->store->save('sid-1', ['token' => 't'], 1000);
        [$file] = $this->files();
        $env = json_decode((string) file_get_contents($file), true);
        $env['data'] = base64_encode(str_repeat('A', 64));
        file_put_contents($file, json_encode($env));

        self::assertNull($this->store->load('sid-1'));
        self::assertSame([], $this->files());
    }

    public function testDeleteRemovesSession(): void
    {
        $this->store->save('sid-1', ['token' => 't'], 1000);
        $this->store->delete('sid-1');
        self::assertNull($this->store->load('sid-1'));
    }

    public function testGcRemovesIdleAndDeadSessionsOnly(): void
    {
        $now = 10_000_000;
        $this->store->save('ativa', ['token' => TestJwt::make($now + 3600)], $now - 60);
        $this->store->save('parada', ['token' => TestJwt::make($now + 3600)], $now - SessionStore::IDLE_TTL - 1);
        $this->store->save('morta', ['token' => TestJwt::make($now - SessionStore::DEAD_TOKEN_TTL - 1)], $now - 60);
        $this->store->save('demo', ['demo' => true], $now - 60);

        $oldTmp = $this->dir . '/velho.json.abcd1234.tmp';
        file_put_contents($oldTmp, 'x');
        touch($oldTmp, $now - 3601);
        $newTmp = $this->dir . '/novo.json.abcd1234.tmp';
        file_put_contents($newTmp, 'x');
        touch($newTmp, $now - 60);

        self::assertSame(3, $this->store->gc($now));
        self::assertFileDoesNotExist($oldTmp);
        self::assertFileExists($newTmp);
        self::assertNotNull($this->store->load('ativa'));
        self::assertNotNull($this->store->load('demo'));
        self::assertNull($this->store->load('parada'));
        self::assertNull($this->store->load('morta'));
    }

    public function testGcRemovesDemoSessionsIdleOverADay(): void
    {
        $now = 10_000_000;
        $this->store->save('demo-velha', ['demo' => true], $now - SessionStore::DEAD_TOKEN_TTL - 1);
        $this->store->save('demo-nova', ['demo' => true], $now - 3600);

        self::assertSame(1, $this->store->gc($now));
        self::assertNull($this->store->load('demo-velha'));
        self::assertNotNull($this->store->load('demo-nova'));
    }

    public function testUnwritableDirMessageHidesPath(): void
    {
        $file = tempnam(sys_get_temp_dir(), 'hpfile');
        $log = ini_set('error_log', sys_get_temp_dir() . '/hpanel-test.log');
        try {
            new SessionStore($file . '/sessions', random_bytes(32));
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
