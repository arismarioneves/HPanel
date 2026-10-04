<?php

declare(strict_types=1);

require __DIR__ . '/../lib/autoload.php';

use HPanel\{Context, Http, Request, Validate};

Http::handle(['GET'], static function (Request $req, Context $ctx): array {
    $orderId = Validate::orderId($req->get('orderId'));
    $site = $ctx->catalog->resolve($orderId, $req->get('domain'));
    $db = Validate::dbName($req->get('db'));
    return ['link' => $ctx->source()->phpMyAdminLink($site['username'], $db, $site['domain'], $orderId)];
});
