<?php

declare(strict_types=1);

require __DIR__ . '/../lib/autoload.php';

use HPanel\{ApiError, Context, Http, Request, Validate};

/** Período => [minutos, passo em minutos]; os mesmos pares que o hPanel usa (~60–90 pontos). */
const RANGES = ['1h' => [60, 1], '6h' => [360, 5], '24h' => [1440, 20], '7d' => [10080, 120], '30d' => [43200, 480]];

Http::handle(['GET'], static function (Request $req, Context $ctx): array {
    $orderId = Validate::orderId($req->get('orderId'));
    $range = $req->get('range') ?? '24h';
    [$minutes, $step] = RANGES[$range] ?? throw ApiError::invalid('Período inválido.');
    $main = $ctx->catalog->mainSite($orderId);
    return [
        'range' => $range,
        'series' => $ctx->source()->metrics($main['username'], $main['domain'], $orderId, $minutes, $step),
    ];
});
