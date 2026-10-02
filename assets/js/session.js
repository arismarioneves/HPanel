import { h } from './h.js';
import { openModal } from './modal.js';

let open = false;

window.addEventListener('hp:session-expired', async () => {
  if (open) return;
  open = true;
  const reconnect = await openModal({
    title: 'Sua sessão com a Hostinger terminou',
    content: h('p', {}, 'Por segurança, o acesso expira quando o painel fica muito tempo sem uso. Conecte novamente para continuar de onde parou.'),
    actions: [{ label: 'Agora não', value: false }, { label: 'Reconectar', value: true, variant: 'primary' }],
  });
  open = false;
  if (reconnect) location.assign('connect');
});
