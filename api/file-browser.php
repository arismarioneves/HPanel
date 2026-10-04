<?php

declare(strict_types=1);

require __DIR__ . '/../lib/autoload.php';

use HPanel\{Context, Http, Request, Validate};

Http::handle(['GET'], static function (Request $req, Context $ctx): array {
    $orderId = Validate::orderId($req->get('orderId'));
    $site = $ctx->catalog->site($orderId, Validate::domain($req->get('domain')));
    return ['link' => $ctx->source()->fileBrowserLink($site['username'], $site['domain'], $orderId)];
});
