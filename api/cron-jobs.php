<?php

declare(strict_types=1);

require __DIR__ . '/../lib/autoload.php';

use HPanel\{ApiError, Context, Http, Request, Validate};

/**
 * Cron jobs da conta (servidor). GET lista (ou `id` = saída da última execução);
 * POST `action` create|delete devolve a lista atualizada.
 */
Http::handle(['GET', 'POST'], static function (Request $req, Context $ctx): array {
    $orderId = Validate::orderId($req->get('orderId'));
    $main = $ctx->catalog->mainSite($orderId);
    $source = $ctx->source();
    $list = static fn(): array => [
        'jobs' => $source->cronJobs($main['username'], $main['domain'], $orderId),
        'home' => "/home/{$main['username']}/",
    ];

    if ($req->method === 'GET') {
        $id = $req->get('id');
        if ($id === null || $id === '') {
            return $list();
        }
        return ['output' => $source->cronJobOutput($main['username'], $main['domain'], $orderId, Validate::cronId($id))];
    }

    match ($req->get('action')) {
        'create' => $source->createCronJob(
            $main['username'],
            $main['domain'],
            $orderId,
            Validate::cronTime($req->get('time')),
            Validate::cronCommand($req->get('command')),
        ),
        'delete' => $source->deleteCronJob($main['username'], $main['domain'], $orderId, Validate::cronId($req->get('id'))),
        default => throw ApiError::invalid('Ação inválida.'),
    };

    return $list();
});
