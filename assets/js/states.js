import { h, icon } from './h.js';

const FRIENDLY = {
  upstream_unavailable: 'A Hostinger não respondeu agora.',
  offline: 'Você está sem conexão com a internet.',
  internal: 'Algo deu errado do nosso lado.',
};

export function errorBlock(err, retry) {
  return h('div', { class: 'state state-error', role: 'status' },
    icon('alert'),
    h('strong', {}, FRIENDLY[err?.code] || err?.message || 'Não foi possível carregar.'),
    retry ? h('button', { type: 'button', class: 'btn btn-secondary btn-sm', on: { click: retry } }, icon('refresh'), 'Tentar novamente') : null);
}

export function emptyBlock(title, hint) {
  return h('div', { class: 'state' }, icon('info'), h('strong', {}, title), hint ? h('span', {}, hint) : null);
}

export const skeletonLines = (n) => Array.from({ length: n }, () => h('div', { class: 'skeleton skeleton-line' }));
export const skeletonCards = (n) => Array.from({ length: n }, () => h('div', { class: 'skeleton skeleton-card', 'aria-hidden': 'true' }));
