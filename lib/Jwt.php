<?php

declare(strict_types=1);

namespace HPanel;

final class Jwt
{
    private const SHAPE = '/^eyJ[A-Za-z0-9_-]+\.[A-Za-z0-9_-]+\.[A-Za-z0-9_-]+$/D';

    public static function isWellFormed(string $token): bool
    {
        return preg_match(self::SHAPE, $token) === 1;
    }

    /** Timestamp `exp` do payload, sem validar assinatura (quem valida é a Hostinger). */
    public static function expiry(string $token): ?int
    {
        if (!self::isWellFormed($token)) {
            return null;
        }
        $payload = json_decode((string) base64_decode(strtr(explode('.', $token)[1], '-_', '+/')), true);
        return is_array($payload) && isset($payload['exp']) && is_numeric($payload['exp']) ? (int) $payload['exp'] : null;
    }
}
