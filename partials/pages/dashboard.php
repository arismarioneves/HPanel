<?php use HPanel\View; ?>
<section class="page-head">
    <div>
        <h1>Servidores</h1>
        <p class="muted" id="freshness">Carregando…</p>
    </div>
    <button type="button" class="btn btn-secondary" data-action="refresh"><?= View::icon('refresh') ?>Atualizar</button>
</section>

<section class="kpis" aria-label="Resumo">
    <div class="kpi"><span class="kpi-value" data-kpi="servers">–</span><span class="kpi-label">Servidores</span></div>
    <div class="kpi"><span class="kpi-value" data-kpi="sites">–</span><span class="kpi-label">Sites</span></div>
    <div class="kpi"><span class="kpi-value" data-kpi="wordpress">–</span><span class="kpi-label">WordPress</span></div>
</section>

<div class="search">
    <label class="search-box">
        <?= View::icon('search') ?>
        <input id="siteSearch" type="search" placeholder="Buscar site em todos os servidores" autocomplete="off" aria-label="Buscar site em todos os servidores" aria-controls="searchResults">
    </label>
    <div id="searchResults" class="search-results" hidden></div>
</div>

<section id="servers" class="grid" aria-live="polite" aria-busy="true"></section>
