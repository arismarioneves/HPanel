<?php

declare(strict_types=1);

namespace HPanel\Tests;

use HPanel\ApiError;
use HPanel\HostingerSource;
use HPanel\Tests\Support\FakeTransport;
use PHPUnit\Framework\TestCase;

final class HostingerSourceTest extends TestCase
{
    private static function ok(array $json): array
    {
        return ['status' => 200, 'body' => json_encode($json)];
    }

    private static function page(int $n, int $from = 0): array
    {
        $r = [];
        for ($i = 0; $i < $n; $i++) {
            $r[] = ['orderId' => $from + $i];
        }
        return self::ok(['data' => ['resources' => $r]]);
    }

    public function testServersFollowsPaginationUntilShortPage(): void
    {
        $http = new FakeTransport([self::page(25), self::page(3, 25)]);
        $servers = (new HostingerSource($http, 'TOKEN', 'GA'))->servers();

        self::assertCount(28, $servers);
        self::assertCount(2, $http->calls);
        self::assertStringContainsString('page=2', $http->calls[1]['url']);
        self::assertStringContainsString('gaid=GA', $http->calls[1]['url']);
        self::assertSame('jwt=TOKEN; language=pt_BR', $http->calls[0]['cookie']);
    }

    public function test401MeansSessionExpired(): void
    {
        $this->expectExceptionObject(ApiError::sessionExpired());
        (new HostingerSource(new FakeTransport([['status' => 401]]), 't', ''))->servers();
    }

    public function testServerErrorsAndNetworkFailuresAreUpstream(): void
    {
        foreach ([['status' => 500, 'body' => '{"message":"stack trace interno"}'], ['status' => 0], ['status' => 200, 'body' => '<html>']] as $resp) {
            try {
                (new HostingerSource(new FakeTransport([$resp]), 't', ''))->servers();
                self::fail('esperava ApiError');
            } catch (ApiError $e) {
                self::assertSame('upstream_unavailable', $e->errorCode);
                self::assertStringNotContainsString('stack', $e->getMessage());
            }
        }
    }

    public function testAccountScopedCallsSendScopeHeadersAndEncodePath(): void
    {
        $http = new FakeTransport([self::ok(['data' => ['link' => 'https://fm.example/x']])]);
        $link = (new HostingerSource($http, 't', ''))->fileBrowserLink('u123', 'loja.com', 42);

        self::assertSame('https://fm.example/x', $link);
        self::assertContains('x-hpanel-order-id: 42', $http->calls[0]['headers']);
        self::assertContains('x-hpanel-username: u123', $http->calls[0]['headers']);
        self::assertContains('x-hpanel-domain: loja.com', $http->calls[0]['headers']);
        self::assertStringContainsString('vhost=loja.com', $http->calls[0]['url']);
    }

    public function testLinkMustBeHttps(): void
    {
        $this->expectExceptionObject(ApiError::upstream());
        (new HostingerSource(new FakeTransport([self::ok(['data' => ['link' => 'javascript:alert(1)']])]), 't', ''))
            ->fileBrowserLink('u', 'd.com', 1);
    }

    public function testPhpVersionMergesAndSortsVersions(): void
    {
        $http = new FakeTransport([self::ok(['data' => [
            'version' => '8.1', 'versionFull' => '8.1.27',
            'olderVersions' => ['7.4' => 'PHP 7.4', '8.0' => 'PHP 8.0'],
            'newerVersions' => ['8.3' => 'PHP 8.3', '8.2' => 'PHP 8.2'],
        ]])]);
        $info = (new HostingerSource($http, 't', ''))->phpVersion('u', 'd.com', 1);

        self::assertSame('8.1', $info['current']);
        self::assertSame(['7.4', '8.0', '8.1', '8.2', '8.3'], array_column($info['versions'], 'version'));
    }

    public function testCreateGitKeyWhenKeyAlreadyExistsReturnsCurrentKey(): void
    {
        $http = new FakeTransport([
            ['status' => 422, 'body' => '{"message":"Key pair already exists","errorCode":9999}'],
            self::ok(['data' => ['publicKey' => 'ssh-rsa AAA']]),
        ]);
        self::assertSame('ssh-rsa AAA', (new HostingerSource($http, 't', ''))->createGitKey('u', 'd.com', 1));
        self::assertSame('POST', $http->calls[0]['method']);
        self::assertSame('GET', $http->calls[1]['method']);
    }

    public function testDeleteAcceptsEmptyBody(): void
    {
        $http = new FakeTransport([['status' => 204, 'body' => '']]);
        (new HostingerSource($http, 't', ''))->deleteGitKey('u', 'd.com', 1);
        self::assertSame('DELETE', $http->calls[0]['method']);
    }
}
