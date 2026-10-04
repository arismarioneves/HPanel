<?php

declare(strict_types=1);

require __DIR__ . '/../lib/autoload.php';

use HPanel\{Context, Http, Request, Validate};

Http::handle(['GET'], static function (Request $req, Context $ctx): array {
    $r = $ctx->catalog->account(Validate::orderId($req->get('orderId')), $req->flag('refresh'));
    return ['usage' => $r['value']['usage'] ?? null, 'cachedAt' => $r['cachedAt']];
});
