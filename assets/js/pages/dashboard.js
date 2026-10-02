import '../app.js';
import { get } from '../api.js';
import { h, icon, $, $$, clear, on } from '../h.js';
import { errorBlock, emptyBlock, skeletonCards } from '../states.js';
import { meter, badge } from '../components.js';
import { relTime } from '../format.js';

const grid = $('#servers');
const search = $('#siteSearch');
const results = $('#searchResults');
let sites = [];

const setKpi = (key, value) => { $(`[data-kpi="${key}"]`).textContent = String(value); };

async function load(refresh = false) {
  grid.setAttribute('aria-busy', 'true');
  clear(grid).append(...skeletonCards(6));
  try {
    const data = await get('websites', refresh ? { refresh: 1 } : undefined);
    render(data.servers, data.cachedAt);
    loadUsage(data.servers, refresh);
  } catch (err) {
    $('#freshness').textContent = '';
    clear(grid).append(errorBlock(err, () => load(refresh)));
  } finally {
    grid.setAttribute('aria-busy', 'false');
  }
}

function render(servers, cachedAt) {
  sites = servers.flatMap((s) => (s.websites || []).map((w) => ({ ...w, orderId: s.orderId, serverTitle: s.title || 'Servidor' })));
  setKpi('servers', servers.length);
  setKpi('sites', sites.length);
  setKpi('wordpress', sites.filter((s) => s.type === 'wordpress').length);
  $('#freshness').textContent = `Atualizado ${relTime(cachedAt)}`;

  if (!servers.length) {
    clear(grid).append(emptyBlock('Nenhum servidor nesta conta.', 'Os planos de hospedagem da sua conta Hostinger aparecem aqui.'));
    return;
  }
  clear(grid).append(...servers.map(card));
}

function card(s) {
  const count = (s.websites || []).length;
  return h('a', { class: 'card server-card', href: `server?orderId=${encodeURIComponent(s.orderId)}`, dataset: { order: String(s.orderId) } },
    h('div', { class: 'card-head' },
      h('h3', { class: 'card-title', title: s.title || 'Servidor' }, s.title || 'Servidor'),
      badge(s.planDisplayableName || s.planName || 'Plano', 'accent')),
    h('dl', { class: 'meta' },
      h('div', {}, h('dt', {}, 'Sites'), h('dd', {}, String(count))),
      h('div', {}, h('dt', {}, 'Datacenter'), h('dd', {}, s.datacenter?.title || '—'))),
    h('div', { class: 'meters', dataset: { meters: '' } }, h('div', { class: 'skeleton skeleton-line' }), h('div', { class: 'skeleton skeleton-line' })));
}

function loadUsage(servers, refresh) {
  return Promise.allSettled(servers.map(async (s) => {
    const box = grid.querySelector(`[data-order="${CSS.escape(String(s.orderId))}"] [data-meters]`);
    if (!box) return;
    try {
      const { usage } = await get('usage', refresh ? { orderId: s.orderId, refresh: 1 } : { orderId: s.orderId });
      const meters = [['storage', 'Disco', 'mb'], ['inodes', 'Inodes', 'n']]
        .filter(([key]) => usage?.[key]?.limit > 0)
        .map(([key, label, fmt]) => meter(label, usage[key].value, usage[key].limit, fmt, true));
      clear(box).append(...(meters.length ? meters : [h('span', { class: 'muted small' }, 'Sem dados de uso')]));
    } catch {
      clear(box).append(h('span', { class: 'muted small' }, 'Uso indisponível no momento'));
    }
  }));
}

function showResults(query) {
  const q = query.trim().toLowerCase();
  if (q.length < 2) { results.hidden = true; return; }
  const found = sites.filter((s) => s.domain.toLowerCase().includes(q)).slice(0, 10);
  clear(results).append(...(found.length
    ? found.map((s) => h('div', { class: 'result' },
      h('a', { href: `server?orderId=${encodeURIComponent(s.orderId)}#sites` },
        h('div', { class: 'result-domain' }, s.domain),
        h('div', { class: 'result-server' }, s.serverTitle)),
      h('div', { class: 'row' },
        h('a', { class: 'btn btn-ghost btn-icon btn-sm', href: `https://${s.domain}`, target: '_blank', rel: 'noopener noreferrer', 'aria-label': 'Visitar site', 'data-tip': 'Visitar' }, icon('globe')),
        h('a', { class: 'btn btn-ghost btn-icon btn-sm', href: `https://hpanel.hostinger.com/websites/${encodeURIComponent(s.domain)}`, target: '_blank', rel: 'noopener noreferrer', 'aria-label': 'Abrir no hPanel', 'data-tip': 'hPanel' }, icon('external')))))
    : [h('div', { class: 'state' }, 'Nenhum site encontrado.')]));
  results.hidden = false;
}

search.addEventListener('input', () => showResults(search.value));
search.addEventListener('focus', () => showResults(search.value));
search.addEventListener('keydown', (e) => { if (e.key === 'Escape') { search.value = ''; results.hidden = true; } });
document.addEventListener('click', (e) => { if (!e.target.closest('.search')) results.hidden = true; });

on(document, 'refresh', () => load(true));

load();
