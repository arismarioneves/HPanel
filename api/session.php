<?php

declare(strict_types=1);

require __DIR__ . '/../lib/autoload.php';

use HPanel\{Context, Http, Jwt, Request};

Http::handle(['GET'], static function (Request $req, Context $ctx): array {
    $data = $ctx->session->data();
    if ($data === null) {
        return ['connected' => false];
    }
    $exp = Jwt::expiry((string) ($data['token'] ?? ''));
    return [
        'connected' => true,
        'expired' => $exp !== null && $exp <= $ctx->now(),
        'expiresAt' => $exp,
        'minutesLeft' => $exp === null ? null : max(0, intdiv($exp - $ctx->now(), 60)),
    ];
}, false);
