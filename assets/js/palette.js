/**
 * Paleta de comandos (Ctrl+K / ⌘+K / "/"): sites, servidores e comandos com busca aproximada.
 * Só existe com sessão: sem o botão `data-action="palette"` no header, nada é ligado.
 */
import { cached, logout } from './api.js';
import { h, icon, $, $$, clear, on } from './h.js';
import { rank } from './fuzzy.js';
import * as favs from './favorites.js';
import { openExternal } from './components.js';
import { toggleTheme } from './theme.js';
import { toast } from './toast.js';

const RECENT_KEY = 'hp_recent';
const MAX_RECENT = 20;
const MAX_SHOWN = 50;
const FAV_BOOST = 3;

const serverUrl = (orderId, tab) => `server?orderId=${encodeURIComponent(orderId)}${tab ? `#${tab}` : ''}`;

function readRecent() {
  try {
    const raw = JSON.parse(localStorage.getItem(RECENT_KEY) ?? '[]');
    return Array.isArray(raw) ? raw.filter((x) => typeof x === 'string').slice(0, MAX_RECENT) : [];
  } catch { return []; }
}

function pushRecent(id) {
  try {
    localStorage.setItem(RECENT_KEY, JSON.stringify([id, ...readRecent().filter((x) => x !== id)].slice(0, MAX_RECENT)));
  } catch { /* storage indisponível ou cheio */ }
}

let dialog, input, list, status, actionsBox, actionsList;
let servers = null; // null = sites ainda não carregados nesta página
let loading = false;
let failed = false;
let results = [];
let active = 0;
let actionMode = false;
let actionIndex = 0;
let previous = null;
let demo = false;

function go(url) {
  close();
  location.assign(url);
}

function openTab(url) {
  window.open(url, '_blank', 'noopener,noreferrer');
  close();
}

const SITE_ACTIONS = [
  { label: 'Visitar', icon: 'globe', run: (s) => openTab(`https://${s.domain}`) },
  { label: 'hPanel', icon: 'external', run: (s) => openTab(`https://hpanel.hostinger.com/websites/${encodeURIComponent(s.domain)}`) },
  { label: 'Arquivos', icon: 'folder', run: (s) => { openExternal('file-browser', { orderId: s.orderId, domain: s.domain }); close(); } },
  { label: 'Servidor', icon: 'server', run: (s) => go(serverUrl(s.orderId)) },
];

async function runLogout() {
  close();
  try {
    await logout();
  } catch (err) {
    toast(err.message, 'danger');
  }
}

const siteItem = (domain, orderId, serverTitle) => ({
  id: `site:${domain}`, kind: 'site', icon: 'globe', text: domain, sub: serverTitle || 'Servidor',
  domain, orderId, run: () => go(serverUrl(orderId, 'sites')),
});

function serverItem(s) {
  const count = (s.websites || []).length;
  return {
    id: `server:${s.orderId}`, kind: 'server', icon: 'server', text: `Ir para servidor ${s.title || 'Servidor'}`,
    sub: `${count} ${count === 1 ? 'site' : 'sites'}`, run: () => go(serverUrl(s.orderId)),
  };
}

function commands() {
  return [
    { id: 'cmd:theme', kind: 'cmd', icon: 'moon', text: 'Alternar tema', sub: 'Claro ou escuro', run: () => { toggleTheme(); close(); } },
    { id: 'cmd:settings', kind: 'cmd', icon: 'settings', text: 'Configurações', sub: 'Conexão e tema', run: () => go('settings') },
    { id: 'cmd:logout', kind: 'cmd', icon: 'logout', text: demo ? 'Sair do demo' : 'Sair', sub: 'Encerrar a sessão', run: runLogout },
  ];
}

/** Sites (da conta + favoritos ainda não carregados), servidores e comandos; ids únicos. */
function allItems() {
  const items = new Map();
  for (const s of servers || []) {
    for (const w of s.websites || []) items.set(`site:${w.domain}`, siteItem(w.domain, s.orderId, s.title));
  }
  for (const f of favs.list()) if (!items.has(`site:${f.domain}`)) items.set(`site:${f.domain}`, siteItem(f.domain, f.orderId, f.serverTitle));
  for (const s of servers || []) items.set(`server:${s.orderId}`, serverItem(s));
  for (const c of commands()) items.set(c.id, c);
  return [...items.values()];
}

function load() {
  if (servers || loading) return;
  loading = true;
  failed = false;
  cached('websites', undefined, (data) => {
    servers = data.servers || [];
    if (dialog.open) render(true);
  })
    .catch(() => { failed = !servers; })
    .finally(() => {
      loading = false;
      if (dialog.open) render(true);
    });
}

function option(item, i, fav, recent) {
  return h('li', { id: `palette-opt-${i}`, role: 'option', class: 'palette-item', 'aria-selected': 'false', dataset: { index: String(i) } },
    icon(item.icon, 'i palette-item-icon'),
    h('span', { class: 'palette-item-text' },
      h('span', { class: 'palette-item-title' }, item.text),
      item.sub ? h('span', { class: 'palette-item-sub' }, item.sub) : null),
    fav ? h('span', { class: 'palette-fav', title: 'Favorito' }, icon('star'), h('span', { class: 'sr-only' }, 'Favorito')) : null,
    recent ? h('span', { class: 'palette-tag' }, 'Recente') : null,
    item.kind === 'site' ? h('kbd', { class: 'palette-tab-hint', 'aria-hidden': 'true' }, 'Tab') : null);
}

function message(query) {
  if (results.length) return loading && !servers ? 'Carregando sites…' : '';
  if (failed) return 'Não foi possível carregar os sites agora.';
  if (loading && !servers) return 'Carregando sites…';
  return query ? `Nada encontrado para “${query}”.` : 'Digite para buscar sites, servidores e comandos. Favoritos e recentes aparecem aqui.';
}

/** `keep`: mantém o item ativo (dados novos chegando); senão volta ao primeiro. */
function render(keep = false) {
  const query = input.value.trim();
  const keepId = keep ? results[active]?.id : null;
  const favIds = new Set(favs.list().map((f) => `site:${f.domain}`));
  const recent = readRecent();
  const boost = (item) => {
    const r = recent.indexOf(item.id);
    return (favIds.has(item.id) ? FAV_BOOST : 0) + (r >= 0 ? 2 - r * 0.05 : 0);
  };
  results = rank(query, allItems(), { boost }).slice(0, MAX_SHOWN);
  const kept = keepId ? results.findIndex((x) => x.id === keepId) : -1;
  active = kept >= 0 ? kept : 0;
  if (kept < 0) actionMode = false;
  clear(list).append(...results.map((item, i) => option(item, i, favIds.has(item.id), !query && recent.includes(item.id))));
  list.hidden = !results.length;
  const text = message(query);
  status.textContent = text;
  status.hidden = !text;
  updateActive();
}

function updateActive() {
  [...list.children].forEach((el, i) => {
    el.setAttribute('aria-selected', String(i === active));
    el.classList.toggle('is-active', i === active);
  });
  const item = results[active];
  if (item?.kind !== 'site') actionMode = false;
  renderActions(item);
  if (!item) {
    input.removeAttribute('aria-activedescendant');
    input.setAttribute('aria-controls', 'paletteList');
    return;
  }
  list.children[active].scrollIntoView({ block: 'nearest' });
  input.setAttribute('aria-controls', actionMode ? 'paletteActions' : 'paletteList');
  input.setAttribute('aria-activedescendant', actionMode ? `palette-act-${actionIndex}` : `palette-opt-${active}`);
}

/** Ações do site ativo numa listbox horizontal à parte (opções da lista não podem ter controles dentro). */
function renderActions(item) {
  actionsBox.hidden = item?.kind !== 'site';
  actionsBox.classList.toggle('is-active', actionMode);
  if (actionsBox.hidden) return;
  actionsList.setAttribute('aria-label', `Ações para ${item.domain}`);
  clear(actionsList).append(...SITE_ACTIONS.map((a, i) => h('span', {
    id: `palette-act-${i}`, role: 'option', class: `palette-action${actionMode && i === actionIndex ? ' is-active' : ''}`,
    'aria-selected': String(actionMode && i === actionIndex), dataset: { action: String(i) },
  }, icon(a.icon), a.label)));
}

function openItem(i) {
  const item = results[i];
  if (!item) return;
  pushRecent(item.id);
  item.run();
}

function runAction(i) {
  const item = results[active];
  if (item?.kind !== 'site') return;
  pushRecent(item.id);
  SITE_ACTIONS[i].run(item);
}

function move(delta) {
  if (!results.length) return;
  active = (active + delta + results.length) % results.length;
  actionMode = false;
  updateActive();
}

function onKey(e) {
  if (e.isComposing) return;
  switch (e.key) {
    case 'ArrowDown':
    case 'ArrowUp':
      e.preventDefault();
      move(e.key === 'ArrowDown' ? 1 : -1);
      break;
    case 'ArrowLeft':
    case 'ArrowRight':
      if (!actionMode) return;
      e.preventDefault();
      actionIndex = (actionIndex + (e.key === 'ArrowRight' ? 1 : -1) + SITE_ACTIONS.length) % SITE_ACTIONS.length;
      updateActive();
      break;
    case 'Tab':
      e.preventDefault(); // o foco fica no campo; Tab só alterna entre a lista e as ações do site
      if (e.shiftKey) actionMode = false;
      else if (results[active]?.kind === 'site') { actionMode = !actionMode; actionIndex = 0; }
      updateActive();
      break;
    case 'Enter':
      e.preventDefault();
      if (actionMode) runAction(actionIndex);
      else openItem(active);
      break;
    case 'Escape':
      e.preventDefault();
      close();
      break;
    default:
  }
}

function build() {
  input = h('input', {
    type: 'text', class: 'palette-input', role: 'combobox', 'aria-expanded': 'true', 'aria-autocomplete': 'list',
    'aria-controls': 'paletteList', 'aria-label': 'Buscar sites, servidores e comandos',
    placeholder: 'Buscar sites, servidores e comandos…', autocomplete: 'off', spellcheck: 'false',
  });
  list = h('ul', { id: 'paletteList', class: 'palette-list', role: 'listbox', 'aria-label': 'Resultados' });
  status = h('p', { class: 'palette-status', role: 'status' });
  actionsList = h('span', { id: 'paletteActions', class: 'palette-actions-list', role: 'listbox', 'aria-orientation': 'horizontal' });
  actionsBox = h('div', { class: 'palette-actions', hidden: true }, h('span', { class: 'palette-actions-label' }, 'Ações'), actionsList);
  const hint = (keys, text) => h('span', {}, ...keys.map((k) => h('kbd', {}, k)), ` ${text}`);
  dialog = h('dialog', { class: 'palette', 'aria-label': 'Paleta de comandos' },
    h('div', { class: 'palette-search' }, icon('search'), input, h('kbd', { 'aria-hidden': 'true' }, 'Esc')),
    list, status, actionsBox,
    h('footer', { class: 'palette-foot', 'aria-hidden': 'true' },
      hint(['↑', '↓'], 'navegar'), hint(['Enter'], 'abrir'), hint(['Tab'], 'ações do site'), hint(['Esc'], 'fechar')));

  input.addEventListener('input', () => render());
  input.addEventListener('keydown', onKey);
  // O foco nunca sai do campo: cliques na lista não o roubam.
  for (const el of [list, actionsList]) el.addEventListener('mousedown', (e) => e.preventDefault());
  list.addEventListener('mousemove', (e) => {
    const i = Number(e.target.closest('[role="option"]')?.dataset.index ?? -1);
    if (i >= 0 && i !== active) { active = i; actionMode = false; updateActive(); }
  });
  list.addEventListener('click', (e) => {
    const el = e.target.closest('[role="option"]');
    if (el) openItem(Number(el.dataset.index));
  });
  actionsList.addEventListener('click', (e) => {
    const el = e.target.closest('[role="option"]');
    if (el) runAction(Number(el.dataset.action));
  });
  dialog.addEventListener('click', (e) => { if (e.target === dialog) close(); });
  dialog.addEventListener('close', () => {
    previous?.focus?.();
    previous = null;
  });
  document.body.append(dialog);
}

function open() {
  if (!dialog) build();
  if (dialog.open) return;
  // Outro diálogo aberto (ex.: sessão expirada) tem prioridade.
  if (document.querySelector('dialog[open]')) return;
  previous = document.activeElement;
  input.value = '';
  results = [];
  actionMode = false;
  load();
  render();
  dialog.showModal();
  input.focus();
}

function close() {
  if (dialog?.open) dialog.close();
}

const isTextField = (el) => el instanceof HTMLElement
  && (el.isContentEditable || el.tagName === 'TEXTAREA' || el.tagName === 'SELECT'
    || (el.tagName === 'INPUT' && !['button', 'checkbox', 'radio', 'submit', 'reset', 'range', 'color', 'file'].includes(el.type)));

function onGlobalKey(e) {
  if ((e.ctrlKey || e.metaKey) && !e.altKey && !e.shiftKey && (e.key || '').toLowerCase() === 'k') {
    e.preventDefault();
    if (dialog?.open) close();
    else open();
  } else if (e.key === '/' && !e.ctrlKey && !e.metaKey && !e.altKey && !isTextField(e.target) && !dialog?.open) {
    e.preventDefault();
    open();
  }
}

const trigger = $('.topbar [data-action="palette"]');
if (trigger) {
  demo = trigger.hasAttribute('data-demo');
  on(document, 'palette', () => open());
  document.addEventListener('keydown', onGlobalKey);
  if (/mac|iphone|ipad/i.test(navigator.userAgentData?.platform || navigator.platform || '')) {
    $$('[data-shortcut]').forEach((el) => { el.textContent = '⌘ K'; });
    trigger.dataset.tip = 'Buscar · ⌘ K';
  }
}
