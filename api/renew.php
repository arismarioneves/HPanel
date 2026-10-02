<?php

declare(strict_types=1);

require __DIR__ . '/../lib/autoload.php';

use HPanel\{Context, Http, Jwt, Request};

Http::handle(['POST'], static function (Request $req, Context $ctx): array {
    $token = $ctx->auth()->ensureFresh($ctx->session, $ctx->now(), true);
    $exp = Jwt::expiry($token);
    return ['expiresAt' => $exp, 'minutesLeft' => $exp === null ? null : max(0, intdiv($exp - $ctx->now(), 60))];
});
