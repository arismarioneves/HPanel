/**
 * UI - Diálogos compartilhados (alert/confirm) no padrão do template
 *
 * Substitui window.alert() e window.confirm() por modais estilizados.
 * API baseada em Promise:
 *   await UI.alert('Mensagem', { title, variant, okLabel })
 *   const ok = await UI.confirm('Mensagem', { title, variant, okLabel, cancelLabel })
 *
 * variant: 'info' (padrão) | 'success' | 'warning' | 'danger'
 */

const UI = (() => {
    let overlay = null;
    let dialog = null;
    let titleEl = null;
    let messageEl = null;
    let footerEl = null;
    let previousFocus = null;
    let activeResolve = null;
    let keyHandler = null;

    // Ícones por variante (SVG inline, herda currentColor)
    const ICONS = {
        info: '<circle cx="12" cy="12" r="10"></circle><line x1="12" y1="16" x2="12" y2="12"></line><line x1="12" y1="8" x2="12.01" y2="8"></line>',
        success: '<path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline>',
        warning: '<path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"></path><line x1="12" y1="9" x2="12" y2="13"></line><line x1="12" y1="17" x2="12.01" y2="17"></line>',
        danger: '<circle cx="12" cy="12" r="10"></circle><line x1="15" y1="9" x2="9" y2="15"></line><line x1="9" y1="9" x2="15" y2="15"></line>'
    };

    /**
     * Cria a estrutura do modal uma única vez e reaproveita
     */
    function ensureDom() {
        if (overlay) return;

        overlay = document.createElement('div');
        overlay.className = 'ui-modal-overlay';
        overlay.setAttribute('role', 'dialog');
        overlay.setAttribute('aria-modal', 'true');

        dialog = document.createElement('div');
        dialog.className = 'ui-modal';

        const header = document.createElement('div');
        header.className = 'ui-modal-header';

        const iconEl = document.createElement('span');
        iconEl.className = 'ui-modal-icon';

        titleEl = document.createElement('span');
        titleEl.className = 'ui-modal-title';

        header.appendChild(iconEl);
        header.appendChild(titleEl);

        messageEl = document.createElement('div');
        messageEl.className = 'ui-modal-message';

        footerEl = document.createElement('div');
        footerEl.className = 'ui-modal-footer';

        dialog.appendChild(header);
        dialog.appendChild(messageEl);
        dialog.appendChild(footerEl);
        overlay.appendChild(dialog);
        document.body.appendChild(overlay);

        dialog._iconEl = iconEl;

        // Clique fora do diálogo = cancelar
        overlay.addEventListener('click', (e) => {
            if (e.target === overlay) close(false);
        });
    }

    function setIcon(variant) {
        const svg = ICONS[variant] || ICONS.info;
        dialog._iconEl.innerHTML =
            `<svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">${svg}</svg>`;
    }

    /**
     * Abre o modal com os botões informados
     * @param {Object} cfg
     * @returns {Promise<boolean>}
     */
    function open(cfg) {
        ensureDom();

        const variant = cfg.variant || 'info';
        setIcon(variant);
        dialog.className = 'ui-modal ui-modal-' + variant;

        titleEl.textContent = cfg.title || '';
        titleEl.style.display = cfg.title ? '' : 'none';

        // Mensagem em texto puro (multi-linha preservada via CSS pre-line)
        messageEl.textContent = cfg.message || '';

        footerEl.innerHTML = '';

        if (cfg.showCancel) {
            const cancelBtn = document.createElement('button');
            cancelBtn.className = 'btn btn-secondary';
            cancelBtn.textContent = cfg.cancelLabel || 'Cancelar';
            cancelBtn.addEventListener('click', () => close(false));
            footerEl.appendChild(cancelBtn);
        }

        const okBtn = document.createElement('button');
        okBtn.className = 'btn ' + (variant === 'danger' ? 'btn-danger' : 'btn-primary');
        okBtn.textContent = cfg.okLabel || 'OK';
        okBtn.addEventListener('click', () => close(true));
        footerEl.appendChild(okBtn);

        previousFocus = document.activeElement;

        return new Promise((resolve) => {
            activeResolve = resolve;

            keyHandler = (e) => {
                if (e.key === 'Escape') {
                    e.preventDefault();
                    close(false);
                } else if (e.key === 'Enter') {
                    e.preventDefault();
                    close(true);
                }
            };
            document.addEventListener('keydown', keyHandler);

            // Ativa após inserir no DOM para permitir a transição
            requestAnimationFrame(() => {
                overlay.classList.add('active');
                okBtn.focus();
            });
        });
    }

    function close(result) {
        if (!overlay) return;

        overlay.classList.remove('active');

        if (keyHandler) {
            document.removeEventListener('keydown', keyHandler);
            keyHandler = null;
        }

        if (previousFocus && typeof previousFocus.focus === 'function') {
            previousFocus.focus();
        }
        previousFocus = null;

        const resolve = activeResolve;
        activeResolve = null;
        if (resolve) resolve(result);
    }

    return {
        /**
         * Alerta simples (um botão). Resolve quando fechado.
         * @param {string} message
         * @param {Object} [opts] { title, variant, okLabel }
         * @returns {Promise<void>}
         */
        alert(message, opts = {}) {
            return open({
                message,
                title: opts.title || 'Aviso',
                variant: opts.variant || 'info',
                okLabel: opts.okLabel || 'OK',
                showCancel: false
            }).then(() => undefined);
        },

        /**
         * Confirmação (dois botões). Resolve true (confirmar) ou false (cancelar).
         * @param {string} message
         * @param {Object} [opts] { title, variant, okLabel, cancelLabel }
         * @returns {Promise<boolean>}
         */
        confirm(message, opts = {}) {
            return open({
                message,
                title: opts.title || 'Confirmação',
                variant: opts.variant || 'warning',
                okLabel: opts.okLabel || 'Confirmar',
                cancelLabel: opts.cancelLabel || 'Cancelar',
                showCancel: true
            });
        }
    };
})();

// Export para módulos, se disponível
if (typeof module !== 'undefined' && module.exports) {
    module.exports = UI;
}
