<?php use HPanel\View; ?>
<section class="page-head">
    <div>
        <h1>Conectar conta Hostinger</h1>
        <p class="muted">Leva menos de um minuto: copie o token da sua sessão no hPanel e cole aqui.</p>
    </div>
</section>

<div class="connect-layout">
    <section class="card stack" aria-labelledby="stepsTitle">
        <h2 id="stepsTitle">Como pegar o token</h2>
        <ol class="guide">
            <li class="guide-step">
                <span class="guide-num" aria-hidden="true">1</span>
                <div class="guide-body">
                    <strong>Entre no hPanel</strong>
                    <p class="muted small">Faça login normalmente na sua conta.</p>
                    <a class="btn btn-secondary btn-sm" href="https://hpanel.hostinger.com" target="_blank" rel="noopener noreferrer"><?= View::icon('external') ?>Abrir hpanel.hostinger.com</a>
                </div>
            </li>
            <li class="guide-step">
                <span class="guide-num" aria-hidden="true">2</span>
                <div class="guide-body">
                    <strong>Abra os cookies do site</strong>
                    <p class="muted small">Pressione <kbd>F12</kbd> e siga o caminho:</p>
                    <p class="guide-path">
                        <span>Application</span><span aria-hidden="true">›</span><span>Cookies</span><span aria-hidden="true">›</span><code>hpanel.hostinger.com</code>
                    </p>
                </div>
            </li>
            <li class="guide-step">
                <span class="guide-num" aria-hidden="true">3</span>
                <div class="guide-body">
                    <strong>Copie o valor de <code>jwt</code></strong>
                    <p class="muted small">Dê dois cliques na coluna <em>Value</em> da linha <code>jwt</code> e copie.</p>
                    <div class="guide-cookie" aria-hidden="true">
                        <span class="guide-cookie-name">jwt</span>
                        <span class="guide-cookie-value mono">eyJhbGciOiJSUzI1NiIsInR5cCI6…</span>
                    </div>
                </div>
            </li>
        </ol>
    </section>

    <div class="stack">
        <section class="card stack" aria-labelledby="formTitle">
            <h2 id="formTitle">Cole o token</h2>
            <form id="connectForm" class="form" novalidate>
                <div>
                    <label for="jwt" class="label">Token JWT</label>
                    <textarea id="jwt" name="token" class="input mono" rows="4" placeholder="eyJ…" autocomplete="off" spellcheck="false" aria-describedby="jwtStatus"></textarea>
                    <p id="jwtStatus" class="field-status" aria-live="polite"></p>
                </div>
                <div class="row">
                    <button type="submit" class="btn btn-primary" disabled><?= View::icon('check') ?>Conectar</button>
                </div>
            </form>
        </section>

        <section class="card stack" aria-labelledby="privacyTitle">
            <h2 id="privacyTitle">Seus dados</h2>
            <ul class="facts-list small">
                <li>O token fica cifrado no servidor deste painel.</li>
                <li>Ele é apagado quando você sai.</li>
                <li>Código aberto: <a href="https://github.com/arismarioneves/HPanel" target="_blank" rel="noopener noreferrer">github.com/arismarioneves/HPanel</a>.</li>
            </ul>
        </section>

        <p class="muted small connect-demo">
            Só quer conhecer?
            <button type="button" class="btn btn-ghost btn-sm" data-action="demo">Ver demonstração</button>
        </p>
    </div>
</div>
