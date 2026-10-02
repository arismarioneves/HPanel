<?php

declare(strict_types=1);

require __DIR__ . '/../lib/autoload.php';

use HPanel\{Context, Http, Request};

Http::handle(['GET'], static function (Request $req, Context $ctx): array {
    $r = $ctx->catalog->servers($req->flag('refresh'));
    return ['servers' => $r['value'], 'cachedAt' => $r['cachedAt']];
});
