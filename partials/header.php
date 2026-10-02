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
            <?php if ($connected): ?>
                <a href="settings" class="btn btn-ghost btn-icon" aria-label="Configurações" data-tip="Configurações"<?= $page === 'settings' ? ' aria-current="page"' : '' ?>><?= View::icon('settings') ?></a>
            <?php endif; ?>
            <?php if ($page === 'landing'): ?>
                <a href="connect" class="btn btn-primary btn-sm topbar-cta">Entrar</a>
            <?php endif; ?>
        </nav>
    </div>
</header>
