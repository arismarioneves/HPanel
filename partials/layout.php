<?php

use HPanel\View;

/** @var string $base @var string $page @var string $title @var string $bodyFile @var string $bootJson @var bool $connected */
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <base href="<?= View::e($base) ?>">
    <title><?= View::e($title) ?> · HPanel</title>
    <link rel="icon" href="favicon.ico" sizes="any">
    <link rel="icon" type="image/png" href="icon.png">
    <link rel="apple-touch-icon" href="icon.png">
    <link rel="preload" href="assets/fonts/inter-latin-var.woff2" as="font" type="font/woff2" crossorigin>
    <link rel="stylesheet" href="assets/css/app.css">
    <script src="assets/js/theme-init.js"></script>
    <script type="module" src="assets/js/pages/<?= View::e($page) ?>.js"></script>
</head>
<body data-page="<?= View::e($page) ?>">
    <a class="skip-link" href="#main">Pular para o conteúdo</a>
    <?php require __DIR__ . '/header.php'; ?>
    <main id="main" class="container">
        <?php require $bodyFile; ?>
    </main>
    <div id="toasts" class="toasts" aria-live="polite" aria-atomic="false"></div>
    <script type="application/json" id="boot"><?= $bootJson ?></script>
</body>
</html>
