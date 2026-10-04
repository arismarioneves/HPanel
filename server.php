<?php

declare(strict_types=1);

require __DIR__ . '/lib/autoload.php';

use HPanel\View;

$ctx = View::boot();
if (!$ctx->session->isConnected()) {
    View::redirect('');
}
$orderId = filter_var($_GET['orderId'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
if ($orderId === false) {
    View::redirect('');
}
View::render('server', 'Servidor', ['orderId' => $orderId], __DIR__ . '/partials/pages/server.php');
