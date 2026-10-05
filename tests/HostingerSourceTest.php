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

    public function testGitReposFlattensAutoDeployInfoAndTrimsInstallPath(): void
    {
        $http = new FakeTransport([self::ok(['data' => [
            [
                'id' => 702822,
                'repoUrl' => 'git@github.com:acme/site.git',
                'branch' => 'review',
                'installPath' => '/',
                'autoDeployInfo' => ['webhookUrl' => 'https://webhooks.hostinger.com/deploy/abc', 'webhookProvider' => 'Github', 'webhookSetupUrl' => 'https://github.com/acme/site/settings/hooks/new'],
            ],
            ['id' => 702823, 'repoUrl' => 'git@github.com:acme/app.git', 'branch' => 'main', 'installPath' => 'app'],
        ]])]);
        $repos = (new HostingerSource($http, 't', ''))->gitRepos('u123', 'loja.com', 42);

        self::assertStringEndsWith('/vhosts/loja.com/git-repos?gaid=', $http->calls[0]['url']);
        self::assertSame('', $repos[0]['installPath']);
        self::assertSame('https://webhooks.hostinger.com/deploy/abc', $repos[0]['webhookUrl']);
        self::assertSame('app', $repos[1]['installPath']);
        self::assertNull($repos[1]['webhookUrl']);
    }

    public function testDeployUsesPutOnRepoId(): void
    {
        $http = new FakeTransport([['status' => 204, 'body' => '']]);
        (new HostingerSource($http, 't', ''))->deployGitRepo('u123', 'loja.com', 42, 702822);

        self::assertSame('PUT', $http->calls[0]['method']);
        self::assertStringContainsString('/git-repos/702822/deploy', $http->calls[0]['url']);
    }

    public function testCreateGitRepoSurfacesTheRefusalFromHostinger(): void
    {
        $http = new FakeTransport([['status' => 422, 'body' => '{"message":"Install path directory is not empty"}']]);
        try {
            (new HostingerSource($http, 't', ''))->createGitRepo('u123', 'loja.com', 42, 'git@github.com:acme/site.git', 'main', 'app');
            self::fail('esperava ApiError');
        } catch (ApiError $e) {
            self::assertSame('invalid_input', $e->errorCode);
            self::assertSame('Install path directory is not empty', $e->getMessage());
        }
        self::assertSame('{"repository":"git@github.com:acme\/site.git","branch":"main","directory":"app"}', $http->calls[0]['body']);
    }

    public function testCreateGitRepoHidesLongOrMissingUpstreamMessages(): void
    {
        foreach ([['status' => 400, 'body' => '{"message":"' . str_repeat('x', 201) . '"}'], ['status' => 400, 'body' => '{"trace":"interno"}']] as $resp) {
            try {
                (new HostingerSource(new FakeTransport([$resp]), 't', ''))->createGitRepo('u', 'd.com', 1, 'git@h:a/b.git', 'main', '');
                self::fail('esperava ApiError');
            } catch (ApiError $e) {
                self::assertStringStartsWith('A Hostinger recusou', $e->getMessage());
            }
        }
    }

    public function testGitRepoOutputReadsTheDeploymentLog(): void
    {
        $http = new FakeTransport([self::ok(['data' => ['output' => "Deployment start
Deployment finished"]])]);
        $out = (new HostingerSource($http, 't', ''))->gitRepoOutput('u123', 'loja.com', 42, 702822);

        self::assertStringContainsString('Deployment finished', $out);
        self::assertStringContainsString('/git-repos/702822/output', $http->calls[0]['url']);
    }

    public function testCreateGitKeyWhenKeyAlreadyExistsReturnsCurrentKey(): void
    {
        $http = new FakeTransport([
            ['status' => 422, 'body' => '{"message":"Key pair already exists","errorCode":9999}'],
            self::ok(['data' => ['publicKey' => 'ssh-rsa AAA']]),
        ]);
        self::assertSame('ssh-rsa AAA', (new HostingerSource($http, 't', ''))->createGitKey('u', 'd.com', 1));
        self::assertSame('POST', $http->calls[0]['method']);
        self::assertSame('{}', $http->calls[0]['body']);
        self::assertSame('GET', $http->calls[1]['method']);
    }

    public function testDeleteAcceptsEmptyBody(): void
    {
        $http = new FakeTransport([['status' => 204, 'body' => '']]);
        (new HostingerSource($http, 't', ''))->deleteGitKey('u', 'd.com', 1);
        self::assertSame('DELETE', $http->calls[0]['method']);
    }
}
