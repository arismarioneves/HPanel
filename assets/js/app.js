import './session.js';
import { on } from './h.js';
import { toggleTheme } from './theme.js';
import { toast } from './toast.js';

on(document, 'toggle-theme', toggleTheme);

const syncOnline = () => document.body.classList.toggle('is-offline', !navigator.onLine);
window.addEventListener('offline', () => { syncOnline(); toast('Você está sem conexão. As ações voltam quando a internet voltar.', 'warn', 6000); });
window.addEventListener('online', () => { syncOnline(); toast('Conexão restabelecida.', 'ok'); });
syncOnline();
