/**
 * TokenStatus - Indicador de expiração do token JWT (compartilhado)
 *
 * Mostra um banner fixo APENAS quando o token está vencendo (<= 10 min) ou expirado,
 * com botão de renovar em 1 clique. Depende de HostingerConfig (config.js) e UI (ui.js).
 */

const TokenStatus = (() => {
    const CHECK_INTERVAL_MS = 5 * 60 * 1000; // revalida a cada 5 min
    const THRESHOLD_MIN = 10;                // banner aparece faltando <= 10 min
    let el = null;

    function ensureDom() {
        if (el) return;
        el = document.createElement('div');
        el.className = 'token-banner';
        el.innerHTML = `
            <svg class="token-banner-icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <circle cx="12" cy="12" r="10"></circle>
                <polyline points="12 6 12 12 16 14"></polyline>
            </svg>
            <div class="token-banner-text">
                <span class="token-banner-title"></span>
                <span class="token-banner-sub"></span>
            </div>
            <button class="btn btn-primary token-banner-btn" type="button">Renovar agora</button>
        `;
        document.body.appendChild(el);
        el.querySelector('.token-banner-btn').addEventListener('click', renew);
    }

    function show(minutesLeft, expired) {
        ensureDom();
        const title = el.querySelector('.token-banner-title');
        const sub = el.querySelector('.token-banner-sub');

        if (expired) {
            el.classList.add('token-banner-danger');
            title.textContent = 'Token expirado';
            sub.textContent = 'Renove ou reconfigure o token JWT.';
        } else {
            el.classList.toggle('token-banner-danger', minutesLeft <= 3);
            title.textContent = `Token expira em ${minutesLeft} min`;
            sub.textContent = 'Renove para não perder a sessão.';
        }
        el.classList.add('visible');
    }

    function hide() {
        if (el) el.classList.remove('visible');
    }

    async function check() {
        if (!window.HostingerConfig || !HostingerConfig.isConfigured()) return;
        try {
            const res = await HostingerConfig.fetch('api/jwt-status.php');
            const data = await res.json();
            if (data.success && data.status) {
                const { minutesLeft, expired } = data.status;
                if (expired || minutesLeft <= THRESHOLD_MIN) {
                    show(minutesLeft, expired);
                } else {
                    hide();
                }
            }
        } catch (e) {
            console.error('token-status:', e);
        }
    }

    async function renew() {
        ensureDom();
        const btn = el.querySelector('.token-banner-btn');
        const original = btn.textContent;
        btn.disabled = true;
        btn.textContent = 'Renovando...';

        try {
            const res = await HostingerConfig.fetch('api/renew-token.php', { method: 'POST' });
            const data = await res.json();

            if (data.success && data.status) {
                if (data.status.minutesLeft > THRESHOLD_MIN) {
                    hide();
                } else {
                    show(data.status.minutesLeft, data.status.expired);
                }
                if (window.UI) UI.alert(`Token renovado. Válido por ${data.status.minutesLeft} min.`, { variant: 'success', title: 'Pronto' });
            } else {
                const msg = data.error || 'Não foi possível renovar o token.';
                if (window.UI) UI.alert(msg, { variant: 'danger', title: 'Erro' });
            }
        } catch (e) {
            console.error('token-status renew:', e);
            if (window.UI) UI.alert('Erro ao renovar o token.', { variant: 'danger', title: 'Erro' });
        } finally {
            btn.disabled = false;
            btn.textContent = original;
        }
    }

    function init() {
        check();
        setInterval(check, CHECK_INTERVAL_MS);
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }

    return { check, renew, hide };
})();

if (typeof module !== 'undefined' && module.exports) {
    module.exports = TokenStatus;
}
