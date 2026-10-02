import '../app.js';
import { get, post } from '../api.js';
import { h, icon, $, clear } from '../h.js';
import { toast } from '../toast.js';
import { confirmDialog } from '../modal.js';

const state = $('#connState');
const card = $('#connectCard');
const form = $('#connectForm');
const input = $('#jwt');
const fieldError = $('#jwtError');

function showFieldError(message) {
  fieldError.textContent = message || '';
  fieldError.hidden = !message;
  input.setAttribute('aria-invalid', message ? 'true' : 'false');
}

/** Pré-validação local (o servidor valida de novo). */
function localCheck(token) {
  if (!/^eyJ[\w-]+\.[\w-]+\.[\w-]+$/.test(token)) return 'Isso não parece um token JWT. Ele começa com "eyJ" e tem três partes separadas por ponto.';
  try {
    const payload = JSON.parse(atob(token.split('.')[1].replace(/-/g, '+').replace(/_/g, '/')));
    if (payload.exp && payload.exp * 1000 <= Date.now()) return 'Esse token já expirou. Entre no hPanel e copie um novo.';
  } catch { /* o servidor decide */ }
  return '';
}

function busy(button, on) {
  button.disabled = on;
  button.setAttribute('aria-busy', String(on));
}

async function refresh() {
  let s;
  try {
    s = await get('session');
  } catch (err) {
    clear(state).append(h('p', {}, err.message));
    return;
  }
  card.hidden = s.connected;
  if (!s.connected) {
    clear(state).append(h('div', { class: 'conn-status' }, h('span', { class: 'dot dot-off' }), 'Nenhuma conta conectada'));
    input.focus();
    return;
  }

  const minutes = s.minutesLeft;
  const near = minutes !== null && minutes <= 15;
  const renewBtn = h('button', { type: 'button', class: 'btn btn-secondary btn-sm' }, icon('refresh'), 'Renovar agora');
  const logoutBtn = h('button', { type: 'button', class: 'btn btn-danger-ghost btn-sm' }, icon('logout'), 'Sair');

  renewBtn.addEventListener('click', async () => {
    busy(renewBtn, true);
    try {
      await post('renew');
      toast('Token renovado.', 'ok');
      refresh();
    } catch (err) {
      toast(err.message, 'danger');
    } finally {
      busy(renewBtn, false);
    }
  });

  logoutBtn.addEventListener('click', async () => {
    if (!(await confirmDialog('Sair da conta?', 'O token é apagado deste servidor. Para voltar, será preciso conectar de novo.', 'Sair', 'danger'))) return;
    busy(logoutBtn, true);
    try {
      await post('logout');
      toast('Você saiu. O token foi apagado do servidor.', 'ok');
      refresh();
    } catch (err) {
      toast(err.message, 'danger');
      busy(logoutBtn, false);
    }
  });

  clear(state).append(
    h('div', { class: 'stack' },
      h('div', { class: 'conn-status' }, h('span', { class: near ? 'dot dot-warn' : 'dot dot-ok' }), 'Conectado à Hostinger'),
      h('span', { class: 'muted small' }, minutes === null
        ? 'Validade do token desconhecida.'
        : `Token válido por ${minutes} min · renovado automaticamente enquanto você usa o painel.`)),
    h('div', { class: 'row' }, renewBtn, logoutBtn));
}

input.addEventListener('input', () => showFieldError(''));

form.addEventListener('submit', async (event) => {
  event.preventDefault();
  const token = input.value.trim();
  const problem = localCheck(token);
  if (problem) { showFieldError(problem); input.focus(); return; }

  const button = form.querySelector('button[type="submit"]');
  busy(button, true);
  try {
    const { servers } = await post('connect', { token });
    input.value = '';
    toast(`Conectado! ${servers} ${servers === 1 ? 'servidor encontrado' : 'servidores encontrados'}.`, 'ok');
    location.assign('./');
  } catch (err) {
    if (err.code === 'invalid_input' || err.code === 'rate_limited') showFieldError(err.message);
    else toast(err.message, 'danger');
    busy(button, false);
  }
});

refresh();
