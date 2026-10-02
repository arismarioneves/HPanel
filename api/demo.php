<?php

declare(strict_types=1);

require __DIR__ . '/../lib/autoload.php';

use HPanel\{Context, DemoSource, Http, Request};

Http::handle(['POST'], static function (Request $req, Context $ctx): array {
    $ctx->session->start(['demo' => true]);
    return ['servers' => count((new DemoSource())->servers())];
}, false);
