<?php

declare(strict_types=1);

namespace HPanel;

/** APIs internas do hPanel. Único lugar que conhece URLs e headers da Hostinger. */
final class HostingerSource implements DataSource
{
    public const BASE = 'https://hpanel.hostinger.com';
    public const UA = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36';
    private const PAGE_SIZE = 25;
    private const MAX_PAGES = 50;
    private const KEY_EXISTS = 9999;

    public function __construct(private HttpTransport $http, private string $token, private string $gaid)
    {
    }

    public function servers(): array
    {
        $all = [];
        for ($page = 1; $page <= self::MAX_PAGES; $page++) {
            $resources = $this->json('GET', "/api/wh-api/api/hapi/v1/orders/websites?page={$page}&ownership=owned")['data']['resources'] ?? [];
            array_push($all, ...$resources);
            if (count($resources) < self::PAGE_SIZE) {
                break;
            }
        }
        return $all;
    }

    public function account(string $username, string $domain, int $orderId): array
    {
        return $this->json('GET', '/api/rest-hosting/v3/account', self::scope($username, $domain, $orderId))['data'] ?? [];
    }

    public function databases(string $username, string $domain, int $orderId): array
    {
        $path = self::accountPath($username) . '/databases?page=1&perPage=100&onlyAssigned=0&vhost=' . rawurlencode($domain);
        return $this->json('GET', $path, self::scope($username, $domain, $orderId))['data']['resources'] ?? [];
    }

    public function phpMyAdminLink(string $username, string $db, string $domain, int $orderId): string
    {
        $path = self::accountPath($username) . '/databases/' . rawurlencode($db) . '/phpmyadmin-link';
        return self::link($this->json('GET', $path, self::scope($username, $domain, $orderId)));
    }

    public function fileBrowserLink(string $username, string $domain, int $orderId): string
    {
        $path = self::accountPath($username) . '/file-browser-link?vhost=' . rawurlencode($domain) . '&locale=pt_BR';
        return self::link($this->json('GET', $path, self::scope($username, $domain, $orderId)));
    }

    public function phpVersion(string $username, string $domain, int $orderId): array
    {
        $d = $this->json('GET', self::phpPath($username, $domain), self::scope($username, $domain, $orderId))['data'] ?? [];
        $labels = [];
        foreach ([$d['olderVersions'] ?? [], $d['newerVersions'] ?? []] as $group) {
            foreach (is_array($group) ? $group : [] as $version => $label) {
                $labels[(string) $version] = (string) $label;
            }
        }
        $current = isset($d['version']) ? (string) $d['version'] : null;
        if ($current !== null) {
            $labels[$current] = 'PHP ' . $current;
        }
        uksort($labels, 'version_compare');
        $versions = [];
        foreach ($labels as $version => $label) {
            $versions[] = ['version' => (string) $version, 'label' => $label];
        }
        return ['current' => $current, 'currentFull' => isset($d['versionFull']) ? (string) $d['versionFull'] : null, 'versions' => $versions];
    }

    public function setPhpVersion(string $username, string $domain, int $orderId, string $version): void
    {
        $this->json('PATCH', self::phpPath($username, $domain), self::scope($username, $domain, $orderId), ['phpVersion' => $version]);
    }

    public function gitKey(string $username, string $domain, int $orderId): ?string
    {
        $key = $this->json('GET', self::accountPath($username) . '/git-key', self::scope($username, $domain, $orderId))['data']['publicKey'] ?? null;
        return is_string($key) && $key !== '' ? $key : null;
    }

    public function createGitKey(string $username, string $domain, int $orderId): string
    {
        $r = $this->raw('POST', self::accountPath($username) . '/git-key', self::scope($username, $domain, $orderId));
        $key = $r['json']['data']['publicKey'] ?? null;
        if ($r['status'] >= 200 && $r['status'] < 300 && is_string($key) && $key !== '') {
            return $key;
        }
        if (($r['json']['errorCode'] ?? null) === self::KEY_EXISTS) {
            return $this->gitKey($username, $domain, $orderId) ?? throw ApiError::upstream();
        }
        $this->fail($r['status'], 'POST git-key');
    }

    public function deleteGitKey(string $username, string $domain, int $orderId): void
    {
        $this->json('DELETE', self::accountPath($username) . '/git-key', self::scope($username, $domain, $orderId));
    }

    /** @return array{status:int, json:?array} */
    private function raw(string $method, string $path, array $headers = [], ?array $body = null): array
    {
        $base = [
            'accept: application/json;charset=utf-8',
            'accept-language: pt-BR,pt;q=0.9,en;q=0.8',
            'referer: ' . self::BASE . '/',
            'user-agent: ' . self::UA,
        ];
        if ($method !== 'GET') {
            $base[] = 'content-type: application/json';
            $base[] = 'origin: ' . self::BASE;
        }
        $url = self::BASE . $path . (str_contains($path, '?') ? '&' : '?') . 'gaid=' . rawurlencode($this->gaid);
        $payload = $method === 'GET' ? null : json_encode($body ?? new \stdClass());
        $r = $this->http->send($method, $url, array_merge($base, $headers), 'jwt=' . $this->token . '; language=pt_BR', $payload);
        $json = $r['body'] !== null && $r['body'] !== '' ? json_decode($r['body'], true) : null;
        return ['status' => $r['status'], 'json' => is_array($json) ? $json : null];
    }

    private function json(string $method, string $path, array $headers = [], ?array $body = null): array
    {
        $r = $this->raw($method, $path, $headers, $body);
        $ok = $r['status'] >= 200 && $r['status'] < 300;
        if ($ok && ($r['json'] !== null || $method === 'DELETE')) {
            return $r['json'] ?? [];
        }
        $this->fail($ok ? 502 : $r['status'], "{$method} {$path}");
    }

    private function fail(int $status, string $what): never
    {
        if ($status === 401) {
            throw ApiError::sessionExpired();
        }
        error_log("HPanel upstream {$what}: HTTP {$status}");
        throw ApiError::upstream();
    }

    private static function link(array $json): string
    {
        $link = $json['data']['link'] ?? null;
        if (is_string($link) && str_starts_with($link, 'https://')) {
            return $link;
        }
        throw ApiError::upstream();
    }

    private static function accountPath(string $username): string
    {
        return '/api/wh-api/api/hapi/v1/accounts/' . rawurlencode($username);
    }

    private static function phpPath(string $username, string $domain): string
    {
        return self::accountPath($username) . '/vhosts/' . rawurlencode($domain) . '/php/version';
    }

    /** @return list<string> */
    private static function scope(string $username, string $domain, int $orderId): array
    {
        return ["x-hpanel-order-id: {$orderId}", "x-hpanel-username: {$username}", "x-hpanel-domain: {$domain}"];
    }
}
