<?php

declare(strict_types=1);

require __DIR__ . '/../lib/autoload.php';

use HPanel\{Context, Http, Request, Validate};

// Sem `domain`: gerenciador na raiz da conta (todos os sites do servidor).
Http::handle(['GET'], static function (Request $req, Context $ctx): array {
    $orderId = Validate::orderId($req->get('orderId'));
    $domain = $req->get('domain');
    if ($domain === null || $domain === '') {
        $main = $ctx->catalog->mainSite($orderId);
        return ['link' => $ctx->source()->rootFileBrowserLink($main['username'], $main['domain'], $orderId)];
    }
    $site = $ctx->catalog->site($orderId, Validate::domain($domain));
    return ['link' => $ctx->source()->fileBrowserLink($site['username'], $site['domain'], $orderId)];
});
