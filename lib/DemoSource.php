<?php

declare(strict_types=1);

namespace HPanel;

/** Dados fictícios (lib/demo/fixtures.json) para o modo demonstração. Nunca acessa a rede. */
final class DemoSource implements DataSource
{
    public const BLOCKED = 'Indisponível no modo demonstração.';

    private array $fx;

    public function __construct()
    {
        $json = file_get_contents(__DIR__ . '/demo/fixtures.json');
        $this->fx = json_decode($json === false ? '' : $json, true, 512, JSON_THROW_ON_ERROR);
    }

    public function servers(): array
    {
        return $this->fx['servers'];
    }

    public function account(string $username, string $domain, int $orderId): array
    {
        return $this->fx['accounts'][(string) $orderId] ?? throw ApiError::notFound('Servidor não encontrado nesta conta.');
    }

    public function databases(string $username, string $domain, int $orderId): array
    {
        return $this->fx['databases'][(string) $orderId] ?? throw ApiError::notFound('Servidor não encontrado nesta conta.');
    }

    public function phpMyAdminLink(string $username, string $db, string $domain, int $orderId): string
    {
        throw self::blocked();
    }

    public function fileBrowserLink(string $username, string $domain, int $orderId): string
    {
        throw self::blocked();
    }

    public function rootFileBrowserLink(string $username, string $domain, int $orderId): string
    {
        throw self::blocked();
    }

    public function phpVersion(string $username, string $domain, int $orderId): array
    {
        return $this->fx['phpVersion'];
    }

    public function setPhpVersion(string $username, string $domain, int $orderId, string $version): void
    {
        throw self::blocked();
    }

    public function gitRepos(string $username, string $domain, int $orderId): array
    {
        return $this->fx['gitRepos'][$domain] ?? [];
    }

    public function createGitRepo(string $username, string $domain, int $orderId, string $repository, string $branch, string $directory): void
    {
        throw self::blocked();
    }

    public function deleteGitRepo(string $username, string $domain, int $orderId, int $repoId): void
    {
        throw self::blocked();
    }

    public function deployGitRepo(string $username, string $domain, int $orderId, int $repoId): void
    {
        throw self::blocked();
    }

    public function gitRepoOutput(string $username, string $domain, int $orderId, int $repoId): string
    {
        return $this->fx['gitOutput'];
    }

    public function gitKey(string $username, string $domain, int $orderId): ?string
    {
        return $this->fx['gitKey'];
    }

    public function createGitKey(string $username, string $domain, int $orderId): string
    {
        throw self::blocked();
    }

    public function deleteGitKey(string $username, string $domain, int $orderId): void
    {
        throw self::blocked();
    }

    public static function blocked(): ApiError
    {
        return ApiError::forbidden(self::BLOCKED);
    }
}
