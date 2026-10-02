<?php use HPanel\View; ?>
<section class="page-head">
    <div>
        <h1>Configurações</h1>
        <p class="muted">Conexão com a sua conta Hostinger.</p>
    </div>
</section>

<div class="stack">
    <section class="card stack" aria-labelledby="connTitle">
        <h2 id="connTitle">Conexão</h2>
        <div id="connState" class="conn" aria-live="polite">
            <div class="skeleton skeleton-line"></div>
        </div>
    </section>

    <section class="card stack" id="connectCard" aria-labelledby="connectTitle" hidden>
        <h2 id="connectTitle">Conectar conta Hostinger</h2>
        <ol class="steps">
            <li>Entre em <a href="https://hpanel.hostinger.com" target="_blank" rel="noopener noreferrer">hpanel.hostinger.com</a>.</li>
            <li>Abra as ferramentas do desenvolvedor (<kbd>F12</kbd>) → <strong>Application</strong> → <strong>Cookies</strong> → <code>hpanel.hostinger.com</code>.</li>
            <li>Copie o valor de <code>jwt</code> e cole abaixo.</li>
        </ol>
        <form id="connectForm" class="form" novalidate>
            <div>
                <label for="jwt" class="label">Token JWT</label>
                <textarea id="jwt" name="token" class="input mono" rows="3" placeholder="eyJ…" autocomplete="off" spellcheck="false" aria-describedby="jwtHint jwtError"></textarea>
                <p id="jwtError" class="field-error" hidden></p>
                <p id="jwtHint" class="hint">O token fica cifrado no servidor e é apagado quando você sai. Ele é renovado sozinho enquanto você usa o painel.</p>
            </div>
            <div class="row">
                <button type="submit" class="btn btn-primary"><?= View::icon('check') ?>Conectar</button>
            </div>
        </form>
    </section>
</div>
