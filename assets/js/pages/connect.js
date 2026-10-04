import '../app.js';
import { clearCache, post } from '../api.js';
import { startDemo } from '../demo.js';
import { $, on } from '../h.js';
import { inspectToken } from '../jwt.js';
import { toast } from '../toast.js';

const form = $('#connectForm');
const input = $('#jwt');
const status = $('#jwtStatus');
const submit = form.querySelector('button[type="submit"]');
const SUCCESS_DELAY = 800;

function duration(minutes) {
  if (minutes < 60) return `${minutes} min`;
  const rest = minutes % 60;
  return rest ? `${Math.floor(minutes / 60)} h ${rest} min` : `${minutes / 60} h`;
}

/** tone: '' (neutro), 'ok' ou 'error'. */
function setStatus(message, tone) {
  status.textContent = message;
  status.className = tone ? `field-status field-status-${tone}` : 'field-status';
  input.setAttribute('aria-invalid', tone === 'error' ? 'true' : 'false');
}

function busy(button, isBusy) {
  button.disabled = isBusy;
  button.setAttribute('aria-busy', String(isBusy));
}

/** Feedback ao vivo; devolve o resultado da inspeção. */
function check() {
  const result = inspectToken(input.value);
  if (!input.value.trim()) setStatus('', '');
  else if (!result.ok) setStatus(result.error, 'error');
  else setStatus(result.minutesLeft === undefined ? '✓ Token válido' : `✓ Token válido · expira em ${duration(result.minutesLeft)}`, 'ok');
  submit.disabled = !result.ok;
  return result;
}

input.addEventListener('input', check);

form.addEventListener('submit', async (event) => {
  event.preventDefault();
  const result = check();
  if (!result.ok) { input.focus(); return; }

  busy(submit, true);
  input.readOnly = true;
  try {
    const { servers } = await post('connect', { token: result.token });
    clearCache();
    input.value = '';
    setStatus(`✓ ${servers} ${servers === 1 ? 'servidor encontrado' : 'servidores encontrados'}`, 'ok');
    setTimeout(() => location.assign('./'), SUCCESS_DELAY);
  } catch (err) {
    input.readOnly = false;
    busy(submit, false);
    if (err.code === 'invalid_input' || err.code === 'rate_limited') {
      setStatus(err.message, 'error');
      input.focus();
    } else {
      toast(err.message, 'danger');
    }
  }
});

on(document, 'demo', startDemo);

check();
input.focus();
