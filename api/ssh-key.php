<?php

declare(strict_types=1);

require __DIR__ . '/../lib/autoload.php';

use HPanel\{ApiError, Context, Http, Request, Validate};

Http::handle(['GET', 'POST'], static function (Request $req, Context $ctx): array {
    $orderId = Validate::orderId($req->get('orderId'));
    $main = $ctx->catalog->mainSite($orderId);
    $source = $ctx->source();

    if ($req->method === 'GET') {
        return ['publicKey' => $source->gitKey($main['username'], $main['domain'], $orderId)];
    }

    $action = $req->get('action');
    if ($action === 'recreate') {
        $source->deleteGitKey($main['username'], $main['domain'], $orderId);
    } elseif ($action !== 'create') {
        throw ApiError::invalid('Ação inválida.');
    }
    return ['publicKey' => $source->createGitKey($main['username'], $main['domain'], $orderId)];
});
