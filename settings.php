<?php

declare(strict_types=1);

require __DIR__ . '/lib/autoload.php';

use HPanel\View;

View::boot();
View::render('settings', 'Configurações', [], __DIR__ . '/partials/pages/settings.php');
