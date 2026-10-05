<?php

declare(strict_types=1);

namespace HPanel;

final class Validate
{
    private const DOMAIN = '/^(?=.{1,253}$)(?:[a-z0-9](?:[a-z0-9-]{0,61}[a-z0-9])?\.)+[a-z0-9](?:[a-z0-9-]{0,61}[a-z0-9])$/D';

    public static function orderId(mixed $v): int
    {
        if (is_int($v) && $v > 0) {
            return $v;
        }
        if (is_string($v) && ctype_digit($v) && (int) $v > 0) {
            return (int) $v;
        }
        throw ApiError::invalid('Servidor inválido.');
    }

    public static function domain(mixed $v): string
    {
        $d = is_string($v) ? strtolower(trim($v)) : '';
        if (preg_match(self::DOMAIN, $d) === 1) {
            return $d;
        }
        throw ApiError::invalid('Domínio inválido.');
    }

    public static function dbName(mixed $v): string
    {
        if (is_string($v) && preg_match('/^[A-Za-z0-9_]{1,64}$/D', $v) === 1) {
            return $v;
        }
        throw ApiError::invalid('Banco de dados inválido.');
    }

    public static function repoId(mixed $v): int
    {
        if (is_int($v) && $v > 0) {
            return $v;
        }
        if (is_string($v) && ctype_digit($v) && (int) $v > 0) {
            return (int) $v;
        }
        throw ApiError::invalid('Repositório inválido.');
    }

    /** `https://host/user/repo.git` ou `git@host:user/repo.git` (o hPanel aceita os dois). */
    public static function repoUrl(mixed $v): string
    {
        $u = is_string($v) ? trim($v) : '';
        $ok = $u !== '' && strlen($u) <= 512
            && preg_match('#^(https://[A-Za-z0-9.-]+(?::\d+)?/\S+|(?:ssh://)?[A-Za-z0-9._-]+@[A-Za-z0-9.-]+[:/]\S+)$#D', $u) === 1;
        if ($ok) {
            return $u;
        }
        throw ApiError::invalid('URL do repositório inválida. Use https://github.com/usuario/repo.git ou git@github.com:usuario/repo.git.');
    }

    public static function branch(mixed $v): string
    {
        $b = is_string($v) ? trim($v) : '';
        if (preg_match('#^[A-Za-z0-9][A-Za-z0-9._/-]{0,254}$#D', $b) === 1 && !str_contains($b, '..')) {
            return $b;
        }
        throw ApiError::invalid('Branch inválida.');
    }

    /** Pasta de destino relativa ao domínio; vazia = `public_html`. */
    public static function directory(mixed $v): string
    {
        $d = trim(is_string($v) ? trim($v) : '', '/');
        if ($d === '') {
            return '';
        }
        if (preg_match('#^[A-Za-z0-9._/-]{1,255}$#D', $d) === 1 && !str_contains($d, '..')) {
            return $d;
        }
        throw ApiError::invalid('Diretório inválido. Use um caminho relativo simples, como "app" ou "site/publico".');
    }

    public static function phpVersion(mixed $v): string
    {
        if (is_string($v) && preg_match('/^\d{1,2}\.\d{1,2}$/D', $v) === 1) {
            return $v;
        }
        throw ApiError::invalid('Versão PHP inválida.');
    }

    public static function jwt(mixed $v): string
    {
        $t = is_string($v) ? trim($v) : '';
        if (strlen($t) <= 8192 && Jwt::isWellFormed($t)) {
            return $t;
        }
        throw ApiError::invalid('Isso não parece um token JWT. Ele começa com "eyJ" e tem três partes separadas por ponto.');
    }
}
