import '../app.js';
import { clearCache, get, post } from '../api.js';
import { h, icon, $, $$, clear, on } from '../h.js';
import { setThemeMode, themeMode } from '../theme.js';
import { toast } from '../toast.js';
import { confirmDialog } from '../modal.js';

const state = $('#connState');

function busy(button, isBusy) {
  button.disabled = isBusy;
  button.setAttribute('aria-busy', String(isBusy));
}

const statusLine = (dot, text, detail) => h('div', { class: 'stack' },
  h('div', { class: 'conn-status' }, h('span', { class: `dot ${dot}` }), text),
  detail ? h('span', { class: 'muted small' }, detail) : null);

function renewButton() {
  const button = h('button', { type: 'button', class: 'btn btn-secondary btn-sm' }, icon('refresh'), 'Renovar agora');
  button.addEventListener('click', async () => {
    busy(button, true);
    try {
      await post('renew');
      toast('Token renovado.', 'ok');
      refresh();
    } catch (err) {
      toast(err.message, 'danger');
    } finally {
      busy(button, false);
    }
  });
  return button;
}

function logoutButton() {
  const button = h('button', { type: 'button', class: 'btn btn-danger-ghost btn-sm' }, icon('logout'), 'Sair');
  button.addEventListener('click', async () => {
    if (!(await confirmDialog('Sair da conta?', 'O token é apagado deste servidor. Para voltar, será preciso conectar de novo.', 'Sair', 'danger'))) return;
    busy(button, true);
    try {
      await post('logout');
      clearCache();
      location.assign('./');
    } catch (err) {
      toast(err.message, 'danger');
      busy(button, false);
    }
  });
  return button;
}

const connectLink = (label, primary) => h('a', { class: `btn ${primary ? 'btn-primary' : 'btn-secondary'} btn-sm`, href: 'connect' }, label);

async function refresh() {
  let s;
  try {
    s = await get('session');
  } catch (err) {
    clear(state).append(h('p', {}, err.message));
    return;
  }
  if (!s.connected) {
    location.assign('./');
    return;
  }

  if (s.demo) {
    clear(state).append(
      statusLine('dot-warn', 'Modo demonstração', 'Você está vendo dados fictícios. Nada aqui vem da Hostinger.'),
      h('div', { class: 'row' },
        connectLink('Conectar sua conta', true),
        h('button', { type: 'button', class: 'btn btn-secondary btn-sm', 'data-action': 'logout' }, icon('logout'), 'Sair do demo')));
    return;
  }

  if (s.expired) {
    clear(state).append(
      statusLine('dot-warn', 'Sessão expirada', 'Conecte de novo com um token novo para continuar.'),
      h('div', { class: 'row' }, connectLink('Reconectar', true), logoutButton()));
    return;
  }

  const minutes = s.minutesLeft;
  const near = minutes !== null && minutes <= 15;
  clear(state).append(
    statusLine(near ? 'dot-warn' : 'dot-ok', 'Conectado à Hostinger', minutes === null
      ? 'Validade do token desconhecida.'
      : `Token válido por ${minutes} min · renovado automaticamente enquanto você usa o painel.`),
    h('div', { class: 'row' }, renewButton(), connectLink('Trocar conta', false), logoutButton()));
}

// Tema: claro / escuro / sistema.
const radios = $$('input[name="theme"]');
const syncTheme = () => {
  const mode = themeMode();
  radios.forEach((r) => { r.checked = r.value === mode; });
};
radios.forEach((r) => r.addEventListener('change', () => { if (r.checked) setThemeMode(r.value); }));
on(document, 'toggle-theme', syncTheme); // o botão do topo grava claro/escuro
syncTheme();

refresh();
