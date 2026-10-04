<?php

declare(strict_types=1);

require __DIR__ . '/../lib/autoload.php';

use HPanel\{ApiError, Context, Http, Request, Validate};

Http::handle(['GET'], static function (Request $req, Context $ctx): array {
    $orderId = Validate::orderId($req->get('orderId'));
    $server = $ctx->catalog->server($orderId);
    $main = $ctx->catalog->mainSite($orderId);

    // Falha só nos detalhes da conta não derruba a página: as abas de sites seguem funcionando.
    $account = null;
    $cachedAt = null;
    try {
        $r = $ctx->catalog->account($orderId, $req->flag('refresh'));
        $account = $r['value'];
        $cachedAt = $r['cachedAt'];
    } catch (ApiError $e) {
        if ($e->errorCode !== 'upstream_unavailable') {
            throw $e;
        }
    }

    return ['server' => $server, 'account' => $account, 'mainDomain' => $main['domain'], 'username' => $main['username'], 'cachedAt' => $cachedAt];
});
