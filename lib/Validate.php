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
