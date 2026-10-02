<?php

declare(strict_types=1);

require __DIR__ . '/../lib/autoload.php';

use HPanel\{Context, Http, Jwt, Request};

Http::handle(['GET'], static function (Request $req, Context $ctx): array {
    $data = $ctx->session->data();
    if ($data === null) {
        return ['connected' => false, 'demo' => false];
    }
    if ($ctx->isDemo()) {
        return ['connected' => true, 'demo' => true, 'expired' => false, 'expiresAt' => null, 'minutesLeft' => null];
    }
    $exp = Jwt::expiry((string) ($data['token'] ?? ''));
    return [
        'connected' => true,
        'demo' => false,
        'expired' => $exp !== null && $exp <= $ctx->now(),
        'expiresAt' => $exp,
        'minutesLeft' => $exp === null ? null : max(0, intdiv($exp - $ctx->now(), 60)),
    ];
}, false);
