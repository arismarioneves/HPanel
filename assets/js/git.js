import { get, post } from './api.js';
import { h, icon, clear } from './h.js';
import { errorBlock, emptyBlock, skeletonLines } from './states.js';
import { toast } from './toast.js';
import { openModal, confirmDialog } from './modal.js';
import { badge, copyText } from './components.js';

/**
 * Git do domínio: repositórios, webhook de auto deploy, deploy manual e saída do último build.
 * O hPanel gerencia o Git por vhost — os subdomínios de um domínio são tratados aqui, no pai.
 */
export function openGitModal(ctx) {
  const body = h('div', { class: 'stack' });
  openModal({ title: `Git · ${ctx.domain}`, content: body, wide: true, actions: [{ label: 'Fechar', value: null }] });
  load(body, ctx);
}

async function load(body, ctx) {
  clear(body).append(...skeletonLines(4));
  try {
    const { repos } = await get('git-repos', { orderId: ctx.orderId, domain: ctx.domain });
    render(body, ctx, repos);
  } catch (err) {
    clear(body).append(errorBlock(err, () => load(body, ctx)));
  }
}

function render(body, ctx, repos) {
  clear(body).append(
    h('p', { class: 'muted small' }, `Repositórios implantados em ${ctx.domain}. Os subdomínios deste domínio também são gerenciados aqui.`),
    repos.length
      ? h('div', { class: 'repo-list' }, repos.map((repo) => repoCard(body, ctx, repo)))
      : emptyBlock('Nenhum repositório configurado.', 'Use o formulário abaixo para implantar um repositório Git neste domínio.'),
    h('div', { class: 'repo-sep' }),
    createForm(body, ctx),
  );
}

function repoCard(body, ctx, repo) {
  const output = h('div', { class: 'repo-output', hidden: true });
  const outputButton = h('button', {
    type: 'button', class: 'btn btn-secondary btn-sm', 'aria-expanded': 'false',
    on: { click: () => toggleOutput(outputButton, output, ctx, repo) },
  }, icon('terminal'), 'Saída');

  const deployButton = h('button', { type: 'button', class: 'btn btn-primary btn-sm' }, icon('rocket'), 'Implantar');
  const deleteButton = h('button', { type: 'button', class: 'btn btn-danger-ghost btn-sm' }, icon('trash'), 'Excluir');
  const buttons = [deployButton, outputButton, deleteButton];

  deployButton.addEventListener('click', async () => {
    const ok = await confirmDialog(
      'Implantar agora?',
      `A Hostinger vai atualizar ${ctx.domain} com a branch ${repo.branch} de ${repo.repoUrl}. Arquivos alterados no servidor podem bloquear o deploy.`,
      'Implantar',
    );
    if (ok) mutate(body, ctx, buttons, deployButton, { action: 'deploy', repoId: repo.id }, 'Deploy disparado. Use "Saída" para acompanhar.');
  });

  deleteButton.addEventListener('click', async () => {
    const ok = await confirmDialog(
      'Excluir este repositório?',
      `${repo.repoUrl} deixa de ser implantado em ${ctx.domain} e o webhook de auto deploy para de funcionar. Os arquivos já publicados permanecem no servidor.`,
      'Excluir',
      'danger',
    );
    if (ok) mutate(body, ctx, buttons, deleteButton, { action: 'delete', repoId: repo.id }, 'Repositório removido.');
  });

  return h('article', { class: 'repo' },
    h('div', { class: 'repo-head' },
      icon('git'),
      h('span', { class: 'repo-url mono' }, repo.repoUrl),
      h('div', { class: 'row badges' },
        badge(repo.branch || '—', 'accent'),
        badge(repo.installPath ? `/${repo.installPath}` : 'public_html'))),
    webhookRow(repo),
    h('div', { class: 'row' }, buttons),
    output);
}

/** O webhook é um gatilho de deploy: fica mascarado por padrão e nunca aparece em log. */
function webhookRow(repo) {
  if (!repo.webhookUrl) {
    return h('p', { class: 'hint' }, 'Sem webhook de auto deploy para este repositório.');
  }
  const token = repo.webhookUrl.slice(repo.webhookUrl.lastIndexOf('/') + 1);
  const masked = repo.webhookUrl.slice(0, -token.length) + '•'.repeat(Math.min(token.length, 32));
  const value = h('code', { class: 'repo-webhook' }, masked);
  const reveal = h('button', {
    type: 'button', class: 'btn btn-ghost btn-icon btn-sm', 'aria-pressed': 'false', 'aria-label': 'Mostrar webhook', 'data-tip': 'Mostrar webhook',
    on: {
      click: () => {
        const shown = reveal.getAttribute('aria-pressed') === 'true';
        reveal.setAttribute('aria-pressed', String(!shown));
        reveal.dataset.tip = shown ? 'Mostrar webhook' : 'Ocultar webhook';
        clear(value).append(shown ? masked : repo.webhookUrl);
      },
    },
  }, icon('eye'));

  return h('div', { class: 'repo-webhook-row' },
    h('span', { class: 'muted small' }, repo.webhookProvider ? `Auto deploy · ${repo.webhookProvider}` : 'Auto deploy'),
    value,
    h('div', { class: 'row' },
      reveal,
      h('button', {
        type: 'button', class: 'btn btn-ghost btn-icon btn-sm', 'aria-label': 'Copiar webhook', 'data-tip': 'Copiar webhook',
        on: { click: () => copyText(repo.webhookUrl, 'Webhook copiado. Ele dispara um deploy — trate como senha.') },
      }, icon('copy')),
      repo.webhookSetupUrl
        ? h('a', {
          class: 'btn btn-ghost btn-icon btn-sm', href: repo.webhookSetupUrl, target: '_blank', rel: 'noopener noreferrer',
          'aria-label': 'Cadastrar webhook no provedor', 'data-tip': 'Cadastrar no provedor',
        }, icon('external'))
        : null));
}

async function toggleOutput(button, box, ctx, repo) {
  const opening = box.hidden;
  box.hidden = !opening;
  button.setAttribute('aria-expanded', String(opening));
  if (!opening) return;
  clear(box).append(...skeletonLines(2));
  button.setAttribute('aria-busy', 'true');
  try {
    const { output } = await get('git-repos', { orderId: ctx.orderId, domain: ctx.domain, repoId: repo.id });
    clear(box).append(output ? h('pre', { class: 'code' }, output) : emptyBlock('Nenhum deploy registrado ainda.'));
  } catch (err) {
    clear(box).append(errorBlock(err, () => { box.hidden = true; toggleOutput(button, box, ctx, repo); }));
  } finally {
    button.removeAttribute('aria-busy');
  }
}

/** Deploy/exclusão: a resposta já traz a lista atualizada, então a modal é redesenhada com ela. */
async function mutate(body, ctx, buttons, active, payload, message) {
  buttons.forEach((b) => { b.disabled = true; });
  active.setAttribute('aria-busy', 'true');
  try {
    const { repos } = await post('git-repos', { orderId: ctx.orderId, domain: ctx.domain, ...payload });
    toast(message, 'ok');
    render(body, ctx, repos);
  } catch (err) {
    toast(err.message, 'danger');
    buttons.forEach((b) => { b.disabled = false; });
    active.removeAttribute('aria-busy');
  }
}

function createForm(body, ctx) {
  const repository = h('input', { class: 'input', type: 'text', required: true, autocomplete: 'off', spellcheck: 'false', placeholder: 'git@github.com:usuario/repo.git' });
  const branch = h('input', { class: 'input', type: 'text', required: true, autocomplete: 'off', spellcheck: 'false', placeholder: 'main' });
  const directory = h('input', { class: 'input', type: 'text', autocomplete: 'off', spellcheck: 'false', placeholder: 'em branco = public_html' });
  const status = h('p', { class: 'hint hint-error', role: 'alert' });
  const submit = h('button', { type: 'submit', class: 'btn btn-primary' }, icon('plus'), 'Criar repositório');

  const form = h('form', { class: 'stack' },
    h('h3', {}, 'Novo repositório'),
    h('p', { class: 'hint' }, 'Para repositórios privados, cadastre antes a chave SSH do servidor (aba Ferramentas) no GitHub ou GitLab. A pasta de destino precisa estar vazia.'),
    h('div', {}, h('label', { class: 'label' }, 'Repositório'), repository),
    h('div', { class: 'grid' },
      h('div', {}, h('label', { class: 'label' }, 'Branch'), branch),
      h('div', {}, h('label', { class: 'label' }, 'Diretório'), directory)),
    h('div', { class: 'row' }, submit, status));

  form.addEventListener('submit', async (event) => {
    event.preventDefault();
    submit.disabled = true;
    submit.setAttribute('aria-busy', 'true');
    status.textContent = '';
    try {
      const { repos } = await post('git-repos', {
        orderId: ctx.orderId,
        domain: ctx.domain,
        action: 'create',
        repository: repository.value.trim(),
        branch: branch.value.trim(),
        directory: directory.value.trim(),
      });
      toast('Repositório criado. O deploy inicial já foi disparado.', 'ok');
      render(body, ctx, repos);
    } catch (err) {
      status.textContent = err.message;
      submit.disabled = false;
    } finally {
      submit.removeAttribute('aria-busy');
    }
  });

  return form;
}
