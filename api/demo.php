<?php

declare(strict_types=1);

require __DIR__ . '/../lib/autoload.php';

use HPanel\{Context, DemoStart, Http, Request};

Http::handle(['POST'], static function (Request $req, Context $ctx): array {
    $servers = DemoStart::start($ctx->rateLimit, $ctx->session, (string) ($_SERVER['REMOTE_ADDR'] ?? ''), $ctx->now());
    return ['servers' => $servers];
}, false);
