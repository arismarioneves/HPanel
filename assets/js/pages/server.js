import '../app.js';
import { get, post } from '../api.js';
import { h, icon, $, clear, boot } from '../h.js';
import { errorBlock, emptyBlock, skeletonLines } from '../states.js';
import { toast } from '../toast.js';
import { confirmDialog } from '../modal.js';
import { meter, badge, section, fact, iconButton, openExternal } from '../components.js';
import { formatDate, formatMb, relTime } from '../format.js';
import { groupSites } from '../sites.js';

const { orderId } = boot();
const TABS = ['visao', 'sites', 'bancos', 'ferramentas'];
const rendered = new Set();
let detail = null;
let loadError = null;
let phpDomain = null;
let phpControls = null;

const VHOST = { main: 'Principal', addon: 'Addon', subdomain: 'Subdomínio' };
const FILTERS = [
  ['all', 'Todos', () => true],
  ['wordpress', 'WordPress', (s) => s.type === 'wordpress'],
  ['other', 'Outros', (s) => s.type !== 'wordpress'],
  ['main', 'Principal', (s) => s.vhostType === 'main'],
  ['addon', 'Addon', (s) => s.vhostType === 'addon'],
  ['subdomain', 'Subdomínio', (s) => s.vhostType === 'subdomain'],
];

const currentTab = () => (TABS.includes(location.hash.slice(1)) ? location.hash.slice(1) : 'visao');
const panel = (tab) => $(`#panel-${tab}`);

function highlightTab() {
  const tab = currentTab();
  for (const t of TABS) {
    $(`[data-tab="${t}"]`).setAttribute('aria-selected', String(t === tab));
    panel(t).hidden = t !== tab;
  }
  return tab;
}

function showTab() {
  const tab = highlightTab();
  if (detail && !rendered.has(tab)) {
    rendered.add(tab);
    RENDER[tab](panel(tab));
  } else if (!detail && loadError) {
    clear(panel(tab)).append(errorBlock(loadError, () => init()));
  }
}

async function init(refresh = false) {
  const tab = highlightTab();
  const p = panel(tab);
  loadError = null;
  clear(p).append(...skeletonLines(6));
  try {
    detail = await get('account', refresh ? { orderId, refresh: 1 } : { orderId });
  } catch (err) {
    loadError = err;
    clear(p).append(errorBlock(err, () => init(refresh)));
    if (detail) rendered.delete(tab);
    else $('#serverTitle').textContent = 'Servidor';
    return;
  }
  rendered.clear();
  TABS.forEach((t) => clear(panel(t)));
  renderHead();
  showTab();
}

function renderHead() {
  const { server, account } = detail;
  document.title = `${server.title || 'Servidor'} · HPanel`;
  $('#serverTitle').textContent = server.title || 'Servidor';
  const meta = $('#serverMeta');
  clear(meta).append([server.planDisplayableName || server.planName, server.datacenter?.title].filter(Boolean).join(' · '));
  if (account?.ip) {
    meta.append(' · IP ', h('button', {
      type: 'button', class: 'btn btn-ghost btn-sm mono', 'data-tip': 'Copiar IP',
      on: { click: () => copy(account.ip, 'IP copiado.') },
    }, account.ip, icon('copy')));
  }
  clear($('#serverActions')).append(
    detail.cachedAt ? h('span', { class: 'muted small' }, `Dados ${relTime(detail.cachedAt)}`) : null,
    h('button', { type: 'button', class: 'btn btn-secondary btn-sm', on: { click: () => init(true) } }, icon('refresh'), 'Atualizar'));
}

async function copy(text, message) {
  try {
    await navigator.clipboard.writeText(text);
    toast(message, 'ok');
  } catch {
    toast('Não deu para copiar automaticamente. Selecione o texto e copie.', 'warn');
  }
}

/* ===== Visão geral ===== */
function renderVisao(p) {
  const a = detail.account;
  if (!a) {
    p.append(emptyBlock('Detalhes da conta indisponíveis agora.', 'A Hostinger não retornou os dados de uso. Use "Atualizar" para tentar de novo — as outras abas funcionam normalmente.'));
    return;
  }
  const usage = [['storage', 'Armazenamento', 'mb'], ['inodes', 'Inodes', 'n'], ['databases', 'Bancos de dados', 'n'], ['subdomains', 'Subdomínios', 'n'], ['ftp_accounts', 'Contas FTP', 'n']]
    .filter(([k]) => a.usage?.[k]?.limit > 0)
    .map(([k, label, fmt]) => meter(label, a.usage[k].value, a.usage[k].limit, fmt));
  const lim = a.plan_limits || {};
  const backup = Number(a.backup_interval || 0);

  p.append(
    section('Uso de recursos', usage.length ? h('div', { class: 'meter-grid' }, usage) : emptyBlock('Sem dados de uso.')),
    section('Limites do plano', h('dl', { class: 'facts' },
      fact('CPU', lim.cpu_cores != null ? `${lim.cpu_cores} núcleos` : null),
      fact('RAM', lim.ram ? formatMb(lim.ram / 1024) : null),
      fact('Entry processes', lim.entry_processes),
      fact('Processos ativos', lim.active_processes),
      fact('Máx. addons', lim.max_addons),
      fact('Banda', 'Ilimitada'))),
    section('Informações', h('dl', { class: 'facts' },
      fact('Hostname', a.server?.hostname),
      fact('Web server', a.web_server),
      fact('Banco de dados', a.database_version),
      fact('Host do banco', a.server?.database?.hostname),
      fact('IP do banco', a.server?.database?.ip),
      fact('Usuário', a.username),
      fact('Usuário FTP', a.ftp_user),
      fact('Shell (SSH)', a.shell_enabled ? 'Habilitado' : 'Desabilitado'),
      fact('Backup', backup === 1 ? 'Diário' : backup > 0 ? `A cada ${backup} dias` : 'Indisponível'),
      fact('Criado em', formatDate(a.created_at)))),
  );
}

/* ===== Sites ===== */
function renderSites(p) {
  const all = detail.server.websites || [];
  let filter = 'all';
  let query = '';
  const list = h('div', { class: 'site-list' });

  const chips = h('div', { class: 'chips', role: 'group', 'aria-label': 'Filtrar sites' },
    FILTERS.map(([key, label, fn]) => h('button', {
      type: 'button', class: 'chip-btn', 'aria-pressed': String(key === filter), dataset: { filter: key },
    }, label, h('span', { class: 'count' }, String(all.filter(fn).length)))));
  chips.addEventListener('click', (e) => {
    const b = e.target.closest('[data-filter]');
    if (!b) return;
    filter = b.dataset.filter;
    chips.querySelectorAll('[data-filter]').forEach((x) => x.setAttribute('aria-pressed', String(x === b)));
    draw();
  });

  const input = h('input', { type: 'search', placeholder: 'Buscar domínio', 'aria-label': 'Buscar domínio', autocomplete: 'off' });
  input.addEventListener('input', () => { query = input.value.trim().toLowerCase(); draw(); });

  function draw() {
    const fn = FILTERS.find((f) => f[0] === filter)[2];
    const shown = all.filter((s) => fn(s) && (!query || s.domain.toLowerCase().includes(query)));
    clear(list);
    if (!shown.length) {
      list.append(emptyBlock('Nenhum site encontrado.', 'Ajuste a busca ou o filtro.'));
      return;
    }
    for (const g of groupSites(shown)) list.append(siteRow(g.site, g.children));
  }

  p.append(h('div', { class: 'toolbar' }, h('label', { class: 'search-box' }, icon('search'), input), chips), list);
  draw();
}

function siteRow(site, children = []) {
  const dbs = h('div', { class: 'site-dbs', hidden: true });
  const dbButton = iconButton('database', 'Bancos de dados', () => toggleDbs(dbButton, dbs, site.domain));
  dbButton.setAttribute('aria-expanded', 'false');

  return h('article', { class: 'site' },
    h('div', { class: 'site-main' },
      h('div', {},
        h('a', { class: 'site-domain', href: `https://${site.domain}`, target: '_blank', rel: 'noopener noreferrer' },
          site.vhostType === 'main' ? icon('star', 'i i-main') : null, site.domain),
        h('div', { class: 'site-meta' }, `Criado ${formatDate(site.createdAt)} · Atualizado ${formatDate(site.updatedAt)}`)),
      h('div', { class: 'row badges' },
        badge(site.type === 'wordpress' ? 'WordPress' : 'Outro', site.type === 'wordpress' ? 'info' : ''),
        badge(VHOST[site.vhostType] || site.vhostType || '—'),
        badge(site.status === 'enabled' ? 'Ativo' : 'Inativo', site.status === 'enabled' ? 'ok' : 'danger')),
      h('div', { class: 'site-actions' },
        iconButton('folder', 'Gerenciador de arquivos', () => openExternal('file-browser', { orderId, domain: site.domain })),
        dbButton,
        iconButton('code', 'Versão PHP', () => { phpDomain = site.domain; location.hash = 'ferramentas'; phpControls?.select(site.domain); }),
        h('a', {
          class: 'btn btn-ghost btn-icon btn-sm', href: `https://hpanel.hostinger.com/websites/${encodeURIComponent(site.domain)}`,
          target: '_blank', rel: 'noopener noreferrer', 'aria-label': 'Abrir no hPanel', 'data-tip': 'Abrir no hPanel',
        }, icon('external')))),
    dbs,
    children.length ? h('div', { class: 'site-children' }, children.map((c) => siteRow(c))) : null);
}

async function toggleDbs(button, box, domain) {
  const opening = box.hidden;
  box.hidden = !opening;
  button.setAttribute('aria-expanded', String(opening));
  if (!opening || box.dataset.loaded) return;
  clear(box).append(...skeletonLines(2));
  try {
    const { databases } = await get('databases', { orderId, domain });
    box.dataset.loaded = '1';
    clear(box).append(dbList(databases, domain));
  } catch (err) {
    clear(box).append(errorBlock(err, () => { box.hidden = true; toggleDbs(button, box, domain); }));
  }
}

function dbList(databases, domain) {
  if (!databases.length) return emptyBlock('Nenhum banco de dados.', 'Crie bancos pelo hPanel; eles aparecem aqui.');
  return h('ul', { class: 'db-list' }, databases.map((db) => h('li', { class: 'db' },
    icon('database'),
    h('div', { class: 'db-id' }, h('strong', {}, db.name), h('span', { class: 'muted small' }, `${db.user || '—'} · ${formatMb(db.diskUsageMb || 0)}`)),
    h('button', {
      type: 'button', class: 'btn btn-secondary btn-sm',
      on: { click: () => openExternal('phpmyadmin', domain ? { orderId, domain, db: db.name } : { orderId, db: db.name }) },
    }, 'phpMyAdmin', icon('external')))));
}

/* ===== Bancos ===== */
async function renderBancos(p) {
  clear(p).append(...skeletonLines(4));
  try {
    const { databases } = await get('databases', { orderId });
    clear(p).append(section('Bancos de dados da conta', dbList(databases, null), detail.username));
  } catch (err) {
    clear(p).append(errorBlock(err, () => renderBancos(p)));
  }
}

/* ===== Ferramentas ===== */
function renderFerramentas(p) {
  p.append(phpCard(), h('div', { class: 'section' }), sshCard());
}

function phpCard() {
  const domains = (detail.server.websites || []).map((s) => s.domain).sort();
  const domainSel = h('select', { class: 'input', 'aria-label': 'Domínio' }, domains.map((d) => h('option', { value: d }, d)));
  const versionSel = h('select', { class: 'input', 'aria-label': 'Versão do PHP', disabled: true });
  const status = h('p', { class: 'muted small' });
  const save = h('button', { type: 'button', class: 'btn btn-primary', disabled: true }, 'Aplicar');
  if (phpDomain && domains.includes(phpDomain)) domainSel.value = phpDomain;

  async function load() {
    const domain = domainSel.value;
    domainSel.disabled = true;
    versionSel.disabled = true;
    save.disabled = true;
    clear(versionSel).append(h('option', {}, 'Carregando…'));
    status.textContent = '';
    try {
      const v = await get('php-version', { orderId, domain });
      if (domainSel.value !== domain) return;
      clear(versionSel).append(...v.versions.map((x) => h('option', { value: x.version }, x.version === v.current ? `PHP ${x.version} (atual)` : x.label)));
      if (v.current) versionSel.value = v.current;
      status.textContent = v.current ? `Em uso: PHP ${v.currentFull || v.current}` : '';
      versionSel.disabled = false;
      save.disabled = false;
    } catch (err) {
      if (domainSel.value !== domain) return;
      clear(versionSel).append(h('option', {}, 'Indisponível'));
      status.textContent = err.message;
    } finally {
      if (domainSel.value === domain) domainSel.disabled = false;
    }
  }

  save.addEventListener('click', async () => {
    const domain = domainSel.value;
    const version = versionSel.value;
    save.disabled = true;
    save.setAttribute('aria-busy', 'true');
    try {
      await post('set-php-version', { orderId, domain, version });
      toast(`PHP ${version} ativado em ${domain}.`, 'ok');
      await load();
    } catch (err) {
      toast(err.message, 'danger');
      save.disabled = false;
    } finally {
      save.removeAttribute('aria-busy');
    }
  });
  domainSel.addEventListener('change', load);
  phpControls = { select: (d) => { if (domains.includes(d)) { domainSel.value = d; load(); } } };
  load();

  return section('Versão do PHP', h('div', { class: 'card stack' },
    h('div', { class: 'grid' }, h('div', {}, h('label', { class: 'label' }, 'Domínio'), domainSel), h('div', {}, h('label', { class: 'label' }, 'Versão'), versionSel)),
    h('div', { class: 'row' }, save, status)));
}

function sshCard() {
  const body = h('div', { class: 'card stack' }, ...skeletonLines(2));

  async function run(task) {
    clear(body).append(...skeletonLines(2));
    try {
      const { publicKey } = await task();
      show(publicKey);
    } catch (err) {
      clear(body).append(errorBlock(err, () => run(() => get('ssh-key', { orderId }))));
    }
  }

  function show(key) {
    if (!key) {
      clear(body).append(
        h('p', {}, 'Esta conta ainda não tem chave SSH para deploy via Git.'),
        h('p', { class: 'hint' }, 'Ao criar, o hPanel volta a exibir o deploy via SSH (em vez do GitHub App) para os sites desta conta.'),
        h('div', { class: 'row' }, h('button', { type: 'button', class: 'btn btn-primary', on: { click: () => run(() => post('ssh-key', { orderId, action: 'create' })) } }, icon('key'), 'Criar chave SSH')));
      return;
    }
    clear(body).append(
      h('pre', { class: 'code' }, key),
      h('div', { class: 'row' },
        h('button', { type: 'button', class: 'btn btn-secondary', on: { click: () => copy(key, 'Chave copiada.') } }, icon('copy'), 'Copiar'),
        h('button', {
          type: 'button', class: 'btn btn-danger-ghost',
          on: {
            click: async () => {
              const ok = await confirmDialog('Recriar a chave SSH?', 'A chave atual deixa de funcionar. Depois, atualize-a no GitHub/GitLab onde ela estiver cadastrada.', 'Recriar', 'danger');
              if (ok) run(() => post('ssh-key', { orderId, action: 'recreate' }));
            },
          },
        }, icon('refresh'), 'Recriar')));
  }

  run(() => get('ssh-key', { orderId }));
  return section('Chave SSH (Git)', body, detail.username ? `Conta ${detail.username}` : null);
}

const RENDER = { visao: renderVisao, sites: renderSites, bancos: renderBancos, ferramentas: renderFerramentas };

window.addEventListener('hashchange', showTab);
init();
