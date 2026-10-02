import { clearCache, post } from './api.js';
import { toast } from './toast.js';

/** Handler de `data-action="demo"`: abre a sessão de demonstração e vai para o dashboard. */
export async function startDemo(event, button) {
  button.disabled = true;
  button.setAttribute('aria-busy', 'true');
  try {
    await post('demo');
    clearCache();
    location.assign('./');
  } catch (err) {
    button.disabled = false;
    button.setAttribute('aria-busy', 'false');
    toast(err.message, 'danger');
  }
}
