<?php

declare(strict_types=1);

require __DIR__ . '/../lib/autoload.php';

use HPanel\{Context, Http, Request};

Http::handle(['POST'], static function (Request $req, Context $ctx): mixed {
    $ctx->session->destroy();
    return null;
});
