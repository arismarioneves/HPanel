<?php

use HPanel\View;

/** @var bool $connected @var string $page */
?>
<header class="topbar">
    <div class="container topbar-inner">
        <a href="./" class="brand" aria-label="HPanel — início">
            <span class="brand-mark" aria-hidden="true">H</span>
            <span class="brand-name">HPanel</span>
        </a>
        <nav class="topbar-actions" aria-label="Principal">
            <?php if ($connected): ?>
                <a href="./" class="btn btn-ghost btn-icon" aria-label="Servidores" data-tip="Servidores"<?= $page === 'dashboard' ? ' aria-current="page"' : '' ?>><?= View::icon('home') ?></a>
            <?php endif; ?>
            <button type="button" class="btn btn-ghost btn-icon" data-action="toggle-theme" aria-label="Alternar tema claro/escuro" data-tip="Tema">
                <?= View::icon('moon', 'i only-light') ?><?= View::icon('sun', 'i only-dark') ?>
            </button>
            <a href="settings" class="btn btn-ghost btn-icon" aria-label="Configurações" data-tip="Configurações"<?= $page === 'settings' ? ' aria-current="page"' : '' ?>><?= View::icon('settings') ?></a>
        </nav>
    </div>
</header>
