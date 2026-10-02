import { h, icon } from './h.js';

const ICONS = { info: 'info', ok: 'check', warn: 'alert', danger: 'alert' };

export function toast(message, variant = 'info', ms = 4000) {
  const box = document.getElementById('toasts');
  if (!box) return;
  const el = h('div', { class: `toast toast-${variant}`, role: variant === 'danger' ? 'alert' : 'status' },
    icon(ICONS[variant] || 'info'), h('span', {}, message));
  let gone = false;
  const dismiss = () => {
    if (gone) return;
    gone = true;
    el.classList.add('leaving');
    setTimeout(() => el.remove(), 220);
  };
  el.append(h('button', { type: 'button', class: 'btn btn-ghost btn-icon btn-sm', 'aria-label': 'Fechar', on: { click: dismiss } }, icon('x')));
  box.append(el);
  if (variant !== 'danger') setTimeout(dismiss, ms);
}
