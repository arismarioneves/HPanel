import { h, icon } from './h.js';
import { get } from './api.js';
import { toast } from './toast.js';
import { percent, level, formatMb, formatNumber } from './format.js';

export function meter(label, value, limit, fmt = 'n', compact = false) {
  const p = percent(value, limit);
  const show = fmt === 'mb' ? formatMb : formatNumber;
  const fill = h('div', { class: 'meter-fill' });
  fill.style.width = `${p}%`;
  return h('div', { class: `meter meter-${level(p)}` },
    h('div', { class: 'meter-head' }, h('span', {}, label), h('strong', {}, `${p}%`)),
    h('div', { class: 'meter-track', role: 'progressbar', 'aria-label': label, 'aria-valuemin': 0, 'aria-valuemax': 100, 'aria-valuenow': p }, fill),
    compact ? null : h('div', { class: 'meter-foot' }, h('span', {}, `${show(value)} usados`), h('span', {}, `de ${show(limit)}`)));
}

export const badge = (text, tone = '') => h('span', { class: tone ? `badge badge-${tone}` : 'badge' }, text);

export function section(title, body, aside) {
  return h('section', { class: 'section' },
    h('div', { class: 'section-head' }, h('h2', {}, title), aside ? h('span', { class: 'muted small' }, aside) : null),
    body);
}

export const fact = (label, value) => h('div', {}, h('dt', {}, label), h('dd', {}, value == null || value === '' ? '—' : String(value)));

export const iconButton = (name, label, onClick) =>
  h('button', { type: 'button', class: 'btn btn-ghost btn-icon btn-sm', 'aria-label': label, 'data-tip': label, on: { click: onClick } }, icon(name));

/** Abre link gerado pela Hostinger (arquivos, phpMyAdmin) numa aba nova sem perder o gesto do usuário. */
export async function openExternal(endpoint, params) {
  const win = window.open('about:blank', '_blank');
  try {
    const { link } = await get(endpoint, params);
    if (!/^https:\/\//.test(link)) throw new Error('link inválido');
    if (win) {
      win.opener = null;
      win.location.href = link;
    } else {
      location.assign(link);
    }
  } catch (err) {
    win?.close();
    toast(err.message || 'Não foi possível abrir o link.', 'danger');
  }
}
