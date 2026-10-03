<?php

declare(strict_types=1);

namespace HPanel;

/** Inicia uma sessão de demonstração, com rate limit por IP (cada uma cria um arquivo). */
final class DemoStart
{
    public const MAX = 20;
    public const WINDOW = 600;

    public static function start(RateLimit $limit, Session $session, string $ip, int $now): int
    {
        if (!$limit->hit('demo', $ip, self::MAX, self::WINDOW, $now)) {
            throw ApiError::rateLimited();
        }
        $session->start(['demo' => true]);
        return count((new DemoSource())->servers());
    }
}
