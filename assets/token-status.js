/**
 * TokenAutoRenew - Renovação silenciosa do JWT na navegação.
 *
 * Ao carregar qualquer página (com throttle de alguns minutos), verifica a sessão:
 * se o token estiver expirado ou perto de expirar, tenta renovar em segundo plano
 * usando a sessão deslizante do hPanel (/auth/refresh via api/renew-token.php).
 *
 * Sem UI: o aviso e os controles (renovar/sair) ficam apenas na página de Configurações.
 * Se o token estiver morto de vez, a renovação falha e as páginas mostram o aviso
 * padrão para reconfigurar o token.
 *
 * Depende de HostingerConfig (config.js).
 */

const TokenAutoRenew = (() => {
    const THROTTLE_MS = 5 * 60 * 1000;   // no máx. 1 verificação a cada 5 min (entre navegações)
    const RENEW_THRESHOLD_MIN = 15;      // renova se faltar <= 15 min (ou já expirado)
    const LAST_CHECK_KEY = 'hostinger_token_lastcheck';

    /**
     * Verifica e, se necessário, renova o token.
     * @param {boolean} force Ignora o throttle (usado por chamadas explícitas)
     * @returns {Promise<void>}
     */
    async function maybeRenew(force = false) {
        if (!window.HostingerConfig || !HostingerConfig.isConfigured()) return;

        // Throttle entre navegações (persistido no localStorage)
        if (!force) {
            const last = parseInt(localStorage.getItem(LAST_CHECK_KEY) || '0', 10);
            if (Date.now() - last < THROTTLE_MS) return;
        }
        localStorage.setItem(LAST_CHECK_KEY, String(Date.now()));

        try {
            const res = await HostingerConfig.fetch('api/jwt-status.php');
            const data = await res.json();
            if (!data.success || !data.status) return;

            const { minutesLeft, expired } = data.status;
            if (expired || minutesLeft <= RENEW_THRESHOLD_MIN) {
                // Renovação silenciosa — a página usa o token atualizado na próxima navegação
                await HostingerConfig.fetch('api/renew-token.php', { method: 'POST' });
            }
        } catch (e) {
            console.error('token auto-renew:', e);
        }
    }

    function init() {
        maybeRenew();
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }

    return { maybeRenew };
})();

if (typeof module !== 'undefined' && module.exports) {
    module.exports = TokenAutoRenew;
}
