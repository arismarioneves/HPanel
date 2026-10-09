import { cached, get, post } from './api.js';
import { sameData } from './swr.js';
import { h, icon, clear } from './h.js';
import { errorBlock, emptyBlock, skeletonLines } from './states.js';
import { toast } from './toast.js';
import { confirmDialog } from './modal.js';
import { iconButton, copyText } from './components.js';
import { FIELDS, PRESETS, describeCron, isValidCron, normalizeCron } from './cron-expr.js';

const PHP_BIN = '/usr/bin/php ';
const SEARCH_FROM = 6;

/**
 * Aba Cron jobs: lista com busca, saída da última execução, exclusão e criação.
 * O cron é da conta (servidor inteiro), não de um domínio. `onJobs` recebe a lista sempre que ela muda.
 * Formulário e busca são montados uma vez; revalidações e mutações só redesenham a lista.
 */
export function renderCron(p, { orderId, refresh = false, onJobs = () => {} }) {
  const ctx = { orderId, onJobs };
  const body = h('div', { class: 'stack' }, ...skeletonLines(4));
  p.append(body);
  load(body, ctx, refresh);
}

async function load(body, ctx, refresh) {
  let shown = null;
  try {
    await cached('cron-jobs', { orderId: ctx.orderId }, (data) => {
      if (shown && sameData(shown, data)) return;
      if (!shown) mount(body, ctx, data);
      shown = data;
      showJobs(ctx, data.jobs);
    }, { maxAgeMs: 60000, refresh });
  } catch (err) {
    if (!shown) clear(body).append(errorBlock(err, () => load(body, ctx, true)));
  }
}

function mount(body, ctx, data) {
  const form = createForm(ctx, data.home);
  const toggle = h('button', { type: 'button', class: 'btn btn-primary btn-sm' }, icon('plus'), 'Nova tarefa');
  ctx.setFormOpen = (open) => {
    form.hidden = !open;
    toggle.setAttribute('aria-expanded', String(open));
    if (open) form.querySelector('input[type=text]')?.focus();
  };
  toggle.addEventListener('click', () => ctx.setFormOpen(form.hidden));
  form.hidden = data.jobs.length > 0;
  toggle.setAttribute('aria-expanded', String(!form.hidden));

  ctx.query = h('input', { type: 'search', placeholder: 'Buscar por comando ou horário', 'aria-label': 'Buscar tarefas cron', autocomplete: 'off', spellcheck: 'false' });
  ctx.query.addEventListener('input', () => applyFilter(ctx));
  ctx.search = h('label', { class: 'search-box cron-search' }, icon('search'), ctx.query);
  ctx.count = h('span', { class: 'muted small' });
  ctx.toolbar = h('div', { class: 'cron-toolbar' }, ctx.search, ctx.count);
  ctx.listBox = h('div', { class: 'stack' });

  clear(body).append(
    h('div', { class: 'section-head' },
      h('div', {},
        h('h2', {}, 'Tarefas cron'),
        h('p', { class: 'muted small' }, 'Valem para o servidor inteiro (todos os sites desta conta).')),
      toggle),
    form,
    ctx.toolbar,
    ctx.listBox);
}

function showJobs(ctx, jobs) {
  ctx.onJobs(jobs);
  ctx.total = jobs.length;
  ctx.toolbar.hidden = jobs.length === 0;
  ctx.search.hidden = jobs.length < SEARCH_FROM;
  if (!jobs.length) {
    ctx.list = null;
    clear(ctx.listBox).append(emptyBlock('Nenhuma tarefa cron.', 'Crie uma tarefa para rodar um script ou chamar uma URL em horários fixos.'));
    return;
  }
  ctx.list = h('ul', { class: 'cron-list' }, jobs.map((job) => jobRow(ctx, job)));
  ctx.none = h('p', { class: 'muted small' }, 'Nenhuma tarefa encontrada.');
  clear(ctx.listBox).append(ctx.list, ctx.none);
  applyFilter(ctx);
}

/** Esconde as linhas fora da busca; a primeira visível perde a borda de cima (sem cortar tooltips com overflow). */
function applyFilter(ctx) {
  if (!ctx.list) return;
  const q = ctx.search.hidden ? '' : ctx.query.value.trim().toLowerCase();
  let visible = 0;
  for (const row of ctx.list.children) {
    const match = !q || row.dataset.search.includes(q);
    row.hidden = !match;
    row.classList.toggle('is-first', match && visible === 0);
    if (match) visible++;
  }
  ctx.list.hidden = visible === 0;
  ctx.none.hidden = visible > 0;
  ctx.count.textContent = visible === ctx.total
    ? `${ctx.total} tarefa${ctx.total === 1 ? '' : 's'}`
    : `${visible} de ${ctx.total} tarefas`;
}

function jobRow(ctx, job) {
  const desc = describeCron(job.time);
  const output = h('div', { class: 'cron-output', hidden: true });
  const outputButton = h('button', {
    type: 'button', class: 'btn btn-ghost btn-sm', 'aria-expanded': 'false',
    on: { click: () => toggleOutput(outputButton, output, ctx, job) },
  }, icon('terminal'), 'Resultado');
  const deleteButton = iconButton('trash', 'Excluir tarefa', async () => {
    const ok = await confirmDialog(
      'Excluir esta tarefa cron?',
      `"${job.command}" (${desc || job.time}) deixa de ser executada. Os arquivos não são alterados.`,
      'Excluir',
      'danger',
    );
    if (!ok) return;
    deleteButton.disabled = true;
    deleteButton.setAttribute('aria-busy', 'true');
    try {
      const data = await post('cron-jobs', { orderId: ctx.orderId, action: 'delete', id: job.id });
      toast('Tarefa cron excluída.', 'ok');
      showJobs(ctx, data.jobs);
    } catch (err) {
      toast(err.message, 'danger');
      deleteButton.disabled = false;
      deleteButton.removeAttribute('aria-busy');
    }
  });
  deleteButton.classList.add('btn-danger-ghost');

  return h('li', { class: 'cron', dataset: { search: `${job.time} ${job.command} ${desc || ''}`.toLowerCase() } },
    h('div', { class: 'cron-when' },
      h('code', { class: 'cron-time' }, job.time),
      desc ? h('span', { class: 'muted small' }, desc) : null),
    h('code', { class: 'cron-cmd' }, job.command),
    h('div', { class: 'cron-actions' },
      outputButton,
      iconButton('copy', 'Copiar comando', () => copyText(job.command, 'Comando copiado.')),
      deleteButton),
    output);
}

async function toggleOutput(button, box, ctx, job) {
  const opening = box.hidden;
  box.hidden = !opening;
  button.setAttribute('aria-expanded', String(opening));
  if (!opening) return;
  clear(box).append(...skeletonLines(2));
  button.setAttribute('aria-busy', 'true');
  try {
    const { output } = await get('cron-jobs', { orderId: ctx.orderId, id: job.id });
    clear(box).append(output
      ? h('pre', { class: 'code' }, output)
      : h('p', { class: 'muted small' }, 'Sem saída registrada. A tarefa ainda não rodou ou não imprimiu nada.'));
  } catch (err) {
    clear(box).append(errorBlock(err, () => { box.hidden = true; toggleOutput(button, box, ctx, job); }));
  } finally {
    button.removeAttribute('aria-busy');
  }
}

function createForm(ctx, home) {
  let mode = 'php';
  const phpPrefix = `${PHP_BIN}${home}`;
  const prefix = h('span', { class: 'input-prefix-text mono', title: phpPrefix }, phpPrefix);
  const command = h('input', { class: 'input mono', type: 'text', autocomplete: 'off', spellcheck: 'false', 'aria-label': 'Comando' });
  const modeHint = h('p', { class: 'hint' });

  const modeButtons = [['php', 'Script PHP'], ['custom', 'Comando personalizado']].map(([key, label]) => h('button', {
    type: 'button', class: 'chip-btn chip-sm', 'aria-pressed': String(key === mode),
    on: { click: () => setMode(key) },
  }, label));

  function setMode(next) {
    mode = next;
    modeButtons.forEach((b, i) => b.setAttribute('aria-pressed', String(['php', 'custom'][i] === mode)));
    prefix.hidden = mode !== 'php';
    command.placeholder = mode === 'php' ? 'public_html/wp-cron.php' : 'wget -O /dev/null https://seusite.com/cron.php';
    modeHint.textContent = mode === 'php'
      ? 'Caminho do script a partir da pasta da conta. Ex.: domains/seusite.com/public_html/cron.php'
      : 'Qualquer comando de uma linha. Para chamar uma URL, use wget ou curl.';
  }
  setMode('php');

  const preset = h('select', { class: 'input', 'aria-label': 'Frequência' },
    PRESETS.map(([expr, label]) => h('option', { value: expr }, `${label} (${expr})`)),
    h('option', { value: '' }, 'Personalizado'));
  const parts = FIELDS.map(([key, label, range]) => h('input', {
    class: 'input mono', type: 'text', autocomplete: 'off', spellcheck: 'false',
    'aria-label': `${label} (${range})`, placeholder: '*', dataset: { field: key },
  }));
  const preview = h('p', { class: 'hint cron-preview' });
  const expr = () => normalizeCron(parts.map((i) => i.value).join(' '));

  function sync(fromPreset) {
    if (fromPreset && preset.value) preset.value.split(' ').forEach((v, i) => { parts[i].value = v; });
    const e = expr();
    if (!fromPreset) preset.value = PRESETS.some(([p]) => p === e) ? e : '';
    parts.forEach((input) => input.setAttribute('aria-invalid', String(!/^[0-9*,/-]+$/.test(input.value.trim()))));
    clear(preview).append(...(isValidCron(e)
      ? ['Executa: ', h('strong', {}, describeCron(e) || 'horário personalizado'), ' · ', h('code', {}, e)]
      : ['Preencha os 5 campos com números, *, vírgula, hífen ou barra (ex.: */5).']));
  }
  const reset = () => {
    command.value = '';
    preset.value = '0 0 * * *';
    sync(true);
  };
  preset.addEventListener('change', () => sync(true));
  parts.forEach((input) => input.addEventListener('input', () => sync(false)));
  reset();

  const status = h('p', { class: 'hint hint-error', role: 'alert' });
  const submit = h('button', { type: 'submit', class: 'btn btn-primary' }, icon('plus'), 'Criar tarefa');

  const form = h('form', { class: 'card stack cron-form' },
    h('div', { class: 'row' }, modeButtons),
    h('div', {}, h('label', { class: 'label' }, 'Comando'), h('div', { class: 'input-prefix' }, prefix, command), modeHint),
    h('div', {}, h('label', { class: 'label' }, 'Frequência'), preset),
    h('div', { class: 'cron-fields' }, FIELDS.map(([, label, range], i) =>
      h('label', { class: 'cron-field' }, h('span', { class: 'small' }, label), parts[i], h('span', { class: 'muted small' }, range)))),
    preview,
    h('div', { class: 'row' }, submit, status));

  form.addEventListener('submit', async (event) => {
    event.preventDefault();
    const time = expr();
    const raw = command.value.trim();
    status.textContent = '';
    if (!isValidCron(time)) { status.textContent = 'Horário inválido.'; return; }
    if (!raw) { status.textContent = 'Informe o comando.'; command.focus(); return; }
    submit.disabled = true;
    submit.setAttribute('aria-busy', 'true');
    try {
      const data = await post('cron-jobs', {
        orderId: ctx.orderId,
        action: 'create',
        time,
        command: mode === 'php' ? `${phpPrefix}${raw.replace(/^\/+/, '')}` : raw,
      });
      toast('Tarefa cron criada.', 'ok');
      reset();
      ctx.setFormOpen(false);
      showJobs(ctx, data.jobs);
    } catch (err) {
      status.textContent = err.message;
    } finally {
      submit.disabled = false;
      submit.removeAttribute('aria-busy');
    }
  });

  return form;
}
