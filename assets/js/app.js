import './session.js';
import { clearCache, post } from './api.js';
import { on } from './h.js';
import { toggleTheme } from './theme.js';
import { toast } from './toast.js';

on(document, 'toggle-theme', toggleTheme);

// "Sair do demo" (e outros botões de sair): encerra a sessão e volta para a página inicial.
on(document, 'logout', async (event, button) => {
  button.disabled = true;
  try {
    await post('logout');
    clearCache();
    location.assign('./');
  } catch (err) {
    button.disabled = false;
    toast(err.message, 'danger');
  }
});

// <base href> faria "#main" navegar para a raiz; foca o conteúdo direto.
document.addEventListener('click', (event) => {
  if (!event.target.closest('.skip-link')) return;
  event.preventDefault();
  document.getElementById('main')?.focus();
});

const syncOnline = () => document.body.classList.toggle('is-offline', !navigator.onLine);
window.addEventListener('offline', () => { syncOnline(); toast('Você está sem conexão. As ações voltam quando a internet voltar.', 'warn', 6000); });
window.addEventListener('online', () => { syncOnline(); toast('Conexão restabelecida.', 'ok'); });
syncOnline();
