<?php

declare(strict_types=1);

namespace HPanel;

/**
 * Servidores e sites da sessão (cache 1 h). Também é a checagem de posse:
 * username/domínio usados nas chamadas vêm daqui, nunca do cliente.
 */
final class Catalog
{
    private const TTL = 3600;

    /** @param \Closure(): DataSource $source */
    public function __construct(private Session $session, private \Closure $source)
    {
    }

    public function servers(bool $refresh = false): array
    {
        return $this->session->cached('servers', self::TTL, fn(): array => ($this->source)()->servers(), $refresh);
    }

    public function server(int $orderId): array
    {
        foreach ([false, true] as $refresh) {
            foreach ($this->servers($refresh)['value'] as $server) {
                if ((int) ($server['orderId'] ?? 0) === $orderId) {
                    return $server;
                }
            }
        }
        throw ApiError::notFound('Servidor não encontrado nesta conta.');
    }

    public function site(int $orderId, string $domain): array
    {
        foreach ($this->server($orderId)['websites'] ?? [] as $site) {
            if (strtolower((string) ($site['domain'] ?? '')) === $domain) {
                return self::ref($orderId, $site);
            }
        }
        throw ApiError::notFound('Site não encontrado neste servidor.');
    }

    public function mainSite(int $orderId): array
    {
        $sites = $this->server($orderId)['websites'] ?? [];
        foreach ($sites as $site) {
            if (($site['vhostType'] ?? '') === 'main') {
                return self::ref($orderId, $site);
            }
        }
        if ($sites !== []) {
            return self::ref($orderId, $sites[0]);
        }
        throw ApiError::notFound('Este servidor ainda não tem sites.');
    }

    public function resolve(int $orderId, mixed $domain): array
    {
        return $domain === null || $domain === '' ? $this->mainSite($orderId) : $this->site($orderId, Validate::domain($domain));
    }

    public function account(int $orderId, bool $refresh = false): array
    {
        $main = $this->mainSite($orderId);
        return $this->session->cached(
            "account:{$orderId}",
            self::TTL,
            fn(): array => ($this->source)()->account($main['username'], $main['domain'], $orderId),
            $refresh
        );
    }

    private static function ref(int $orderId, array $site): array
    {
        return ['orderId' => $orderId, 'domain' => (string) ($site['domain'] ?? ''), 'username' => (string) ($site['username'] ?? '')];
    }
}
