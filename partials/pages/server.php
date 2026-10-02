<?php use HPanel\View; ?>
<a href="./" class="back"><?= View::icon('arrow-left') ?>Servidores</a>

<section class="page-head">
    <div>
        <h1 id="serverTitle"><span class="skeleton skeleton-title"></span></h1>
        <p class="muted" id="serverMeta"></p>
    </div>
    <div class="row" id="serverActions"></div>
</section>

<nav class="tabs" role="tablist" aria-label="Seções do servidor">
    <a role="tab" href="#visao" data-tab="visao" aria-controls="panel-visao">Visão geral</a>
    <a role="tab" href="#sites" data-tab="sites" aria-controls="panel-sites">Sites</a>
    <a role="tab" href="#bancos" data-tab="bancos" aria-controls="panel-bancos">Bancos</a>
    <a role="tab" href="#ferramentas" data-tab="ferramentas" aria-controls="panel-ferramentas">Ferramentas</a>
</nav>

<section id="panel-visao" role="tabpanel" hidden></section>
<section id="panel-sites" role="tabpanel" hidden></section>
<section id="panel-bancos" role="tabpanel" hidden></section>
<section id="panel-ferramentas" role="tabpanel" hidden></section>
