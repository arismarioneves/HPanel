<?php

declare(strict_types=1);

require __DIR__ . '/../lib/autoload.php';

use HPanel\{ApiError, Context, Http, Request, Validate};

Http::handle(['POST'], static function (Request $req, Context $ctx): array {
    $orderId = Validate::orderId($req->get('orderId'));
    $site = $ctx->catalog->site($orderId, Validate::domain($req->get('domain')));
    $version = Validate::phpVersion($req->get('version'));

    $source = $ctx->source();
    $available = array_column($source->phpVersion($site['username'], $site['domain'], $orderId)['versions'], 'version');
    if (!in_array($version, $available, true)) {
        throw ApiError::invalid('Essa versão do PHP não está disponível para este site.');
    }
    $source->setPhpVersion($site['username'], $site['domain'], $orderId, $version);
    return ['current' => $version];
});
