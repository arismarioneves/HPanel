<section class="page-head">
    <div>
        <h1>Configurações</h1>
        <p class="muted">Conexão com a Hostinger, aparência e dados guardados no navegador.</p>
    </div>
</section>

<div class="stack">
    <section class="card stack" aria-labelledby="connTitle">
        <h2 id="connTitle">Conexão</h2>
        <div id="connState" class="conn" aria-live="polite">
            <div class="skeleton skeleton-line"></div>
        </div>
    </section>

    <section class="card stack" aria-labelledby="themeTitle">
        <h2 id="themeTitle">Tema</h2>
        <div class="segmented" role="radiogroup" aria-labelledby="themeTitle">
            <label><input type="radio" name="theme" value="light"><span>Claro</span></label>
            <label><input type="radio" name="theme" value="dark"><span>Escuro</span></label>
            <label><input type="radio" name="theme" value="system"><span>Sistema</span></label>
        </div>
        <p class="hint">“Sistema” acompanha a preferência de tema do seu dispositivo.</p>
    </section>

    <section class="card stack" aria-labelledby="favTitle">
        <h2 id="favTitle">Favoritos e recentes</h2>
        <p class="hint">Ficam salvos só neste navegador. Apenas os sites da conta carregada aparecem no painel e na busca.</p>
        <div class="row"><button type="button" id="clearFavs" class="btn btn-danger-ghost btn-sm">Limpar favoritos e recentes</button></div>
    </section>
</div>
