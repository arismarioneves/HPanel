import '../app.js';
import { cached } from '../api.js';
import { sameData } from '../swr.js';
import { h, icon, $, $$, clear, on } from '../h.js';
import { errorBlock, emptyBlock, skeletonCards } from '../states.js';
import { meter, badge } from '../components.js';
import { relTime } from '../format.js';

const grid = $('#servers');
const search = $('#siteSearch');
const results = $('#searchResults');
let sites = [];
// Último resultado de uso por servidor: re-renderizar os cards não volta os medidores ao skeleton.
const usageBox = new Map();
let loadSeq = 0;

const setKpi = (key, value) => { $(`[data-kpi="${key}"]`).textContent = String(value); };

async function load(refresh = false) {
  const seq = ++loadSeq;
  const requested = new Set();
  let shown = null;
  grid.setAttribute('aria-busy', 'true');
  if (refresh) usageBox.clear();
  clear(grid).append(...skeletonCards(6));
  try {
    await cached('websites', undefined, (data) => {
      if (seq !== loadSeq) return;
      if (shown && sameData(shown, data)) { $('#freshness').textContent = `Atualizado ${relTime(data.cachedAt)}`; return; }
      shown = data;
      render(data.servers, data.cachedAt);
      loadUsage(data.servers.filter((s) => !requested.has(s.orderId)), refresh, seq);
      data.servers.forEach((s) => requested.add(s.orderId));
    }, { refresh });
  } catch (err) {
    if (seq !== loadSeq || shown) return;
    $('#freshness').textContent = '';
    clear(grid).append(errorBlock(err, () => load(refresh)));
  } finally {
    if (seq === loadSeq) grid.setAttribute('aria-busy', 'false');
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
  const title = s.title || 'Servidor';
  return h('a', { class: 'card server-card', href: `server?orderId=${encodeURIComponent(s.orderId)}`, dataset: { order: String(s.orderId) } },
    h('h3', { class: 'card-title', title }, title),
    h('div', { class: 'card-sub' },
      badge(s.planDisplayableName || s.planName || 'Plano', 'accent'),
      s.datacenter?.title ? h('span', { class: 'card-sub-text', title: s.datacenter.title }, s.datacenter.title) : null),
    h('div', { class: 'meters', dataset: { meters: '' } }, ...(usageBox.get(s.orderId)?.() || [h('div', { class: 'skeleton skeleton-line' }), h('div', { class: 'skeleton skeleton-line' })])),
    h('div', { class: 'card-foot' },
      h('span', {}, icon('globe'), `${count} ${count === 1 ? 'site' : 'sites'}`),
      s.server?.hostname ? h('span', { class: 'mono', title: 'Hostname' }, s.server.hostname) : null));
}

/** `make` cria os nós de uso; guardado para redesenhar os cards sem voltar ao skeleton. */
function showUsage(orderId, make) {
  usageBox.set(orderId, make);
  const box = grid.querySelector(`[data-order="${CSS.escape(String(orderId))}"] [data-meters]`);
  if (box) clear(box).append(...make());
}

const note = (text) => () => [h('span', { class: 'muted small' }, text)];

function loadUsage(servers, refresh, seq) {
  return Promise.allSettled(servers.map(async (s) => {
    if (!(s.websites || []).length) { showUsage(s.orderId, note('Sem sites ainda')); return; }
    let prev = null;
    try {
      await cached('usage', { orderId: s.orderId }, (data) => {
        if (seq !== loadSeq || (prev && sameData(prev, data))) return;
        prev = data;
        const { usage } = data;
        const keys = [['storage', 'Disco', 'mb'], ['inodes', 'Inodes', 'n']].filter(([key]) => usage?.[key]?.limit > 0);
        showUsage(s.orderId, keys.length
          ? () => keys.map(([key, label, fmt]) => meter(label, usage[key].value, usage[key].limit, fmt, true))
          : note('Sem dados de uso'));
      }, { refresh });
    } catch {
      if (seq === loadSeq) showUsage(s.orderId, note('Uso indisponível no momento'));
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
