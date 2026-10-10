<?php use HPanel\View; ?>
<section class="hero" aria-labelledby="heroTitle">
    <div class="hero-copy">
        <h1 id="heroTitle">Seus servidores Hostinger, numa tela só.</h1>
        <p class="hero-lead muted">Veja uso de recursos, sites, bancos de dados, versão do PHP e chaves SSH de todos os seus planos — rápido e sem o peso do hPanel.</p>
        <div class="row hero-ctas">
            <a href="connect" class="btn btn-primary"><?= View::icon('key') ?>Conectar conta Hostinger</a>
            <button type="button" class="btn btn-secondary" data-action="demo"><?= View::icon('server') ?>Ver demonstração</button>
        </div>
    </div>
    <img class="hero-shot" src="<?= View::e(View::asset('img/dashboard-preview.webp')) ?>" width="1920" height="1230" loading="eager"
         alt="Painel do HPanel no tema escuro: cards de servidores com uso de disco e inodes e os sites de cada plano.">
</section>

<section class="landing-section" aria-labelledby="featuresTitle">
    <h2 id="featuresTitle" class="landing-title">Destaques</h2>
    <div class="features">
        <article class="card feature">
            <span class="feature-icon" aria-hidden="true"><?= View::icon('home') ?></span>
            <h3>Tudo em uma tela</h3>
            <p class="muted">Todos os servidores lado a lado, com disco, inodes e sites — e alerta a partir de 80 % de uso.</p>
        </article>
        <article class="card feature">
            <span class="feature-icon" aria-hidden="true"><?= View::icon('search') ?></span>
            <h3>Busca instantânea (Ctrl+K)</h3>
            <p class="muted">Encontre qualquer site ou servidor pelo nome e vá direto para ele, sem navegar por menus.</p>
        </article>
        <article class="card feature">
            <span class="feature-icon" aria-hidden="true"><?= View::icon('key') ?></span>
            <h3>Seus dados cifrados</h3>
            <p class="muted">O token de acesso fica cifrado no servidor deste painel; o navegador guarda só um identificador de sessão.</p>
        </article>
    </div>
</section>

<section class="landing-section" aria-labelledby="howTitle">
    <h2 id="howTitle" class="landing-title">Como funciona</h2>
    <ol class="steps">
        <li class="card step">
            <span class="guide-num" aria-hidden="true">1</span>
            <div>
                <h3>Entre no hPanel</h3>
                <p class="muted">Faça login normalmente na sua conta Hostinger.</p>
            </div>
        </li>
        <li class="card step">
            <span class="guide-num" aria-hidden="true">2</span>
            <div>
                <h3>Copie o token</h3>
                <p class="muted">Pegue o valor do cookie <code>jwt</code> nas ferramentas do navegador (<kbd>F12</kbd>). O passo a passo está na tela de conexão.</p>
            </div>
        </li>
        <li class="card step">
            <span class="guide-num" aria-hidden="true">3</span>
            <div>
                <h3>Cole e pronto</h3>
                <p class="muted">O HPanel valida o token e mostra seus servidores na hora.</p>
            </div>
        </li>
    </ol>
</section>

<section class="landing-section" aria-labelledby="trustTitle">
    <h2 id="trustTitle" class="landing-title">Transparência</h2>
    <div class="card">
        <ul class="facts-list">
            <li><strong>O que é guardado:</strong> só o token da sua sessão no hPanel, cifrado no servidor deste painel, e um cache curto dos dados exibidos.</li>
            <li><strong>Por quanto tempo:</strong> até 7 dias sem uso; depois disso a sessão é apagada automaticamente.</li>
            <li><strong>Ao sair:</strong> o token e o cache são apagados na hora.</li>
            <li><strong>Código aberto:</strong> confira tudo em <a href="https://github.com/arismarioneves/HPanel" target="_blank" rel="noopener noreferrer">github.com/arismarioneves/HPanel</a>.</li>
        </ul>
    </div>
</section>

<footer class="landing-footer muted small">
    <p>Projeto independente, sem vínculo com a Hostinger.</p>
    <a href="https://github.com/arismarioneves/HPanel" target="_blank" rel="noopener noreferrer"><?= View::icon('external') ?>Repositório</a>
</footer>
