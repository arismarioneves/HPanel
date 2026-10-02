<?php

declare(strict_types=1);

namespace HPanel;

/**
 * Fonte de dados do painel. Implementações lançam ApiError
 * (session_expired / upstream_unavailable) — nunca devolvem null para erro.
 */
interface DataSource
{
    /** @return list<array> recursos (servidores) com `websites` */
    public function servers(): array;

    public function account(string $username, string $domain, int $orderId): array;

    /** @return list<array> */
    public function databases(string $username, string $domain, int $orderId): array;

    public function phpMyAdminLink(string $username, string $db, string $domain, int $orderId): string;

    public function fileBrowserLink(string $username, string $domain, int $orderId): string;

    /** @return array{current:?string, currentFull:?string, versions:list<array{version:string,label:string}>} */
    public function phpVersion(string $username, string $domain, int $orderId): array;

    public function setPhpVersion(string $username, string $domain, int $orderId, string $version): void;

    public function gitKey(string $username, string $domain, int $orderId): ?string;

    public function createGitKey(string $username, string $domain, int $orderId): string;

    public function deleteGitKey(string $username, string $domain, int $orderId): void;
}
