<?php

declare(strict_types=1);

require __DIR__ . '/lib/autoload.php';

use HPanel\View;

$ctx = View::boot();
if (!$ctx->session->isConnected()) {
    View::render('landing', 'Painel leve para Hostinger', [], __DIR__ . '/partials/pages/landing.php');
}
View::render('dashboard', 'Servidores', [], __DIR__ . '/partials/pages/dashboard.php');
