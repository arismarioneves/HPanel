export class ApiError extends Error {
  constructor(code, message, status) {
    super(message);
    this.code = code;
    this.status = status;
  }
}

const inflight = new Map();
const RETRY_DELAY = 1500;
const sleep = (ms) => new Promise((r) => setTimeout(r, ms));

// Páginas que funcionam sem sessão: não redirecionam em `not_connected`.
const STAY_PAGES = new Set(['landing', 'connect']);

/** Apaga o cache de respostas da aba (chaves `hp_cache:`); usar ao conectar, sair ou entrar no demo. */
export function clearCache() {
  try {
    Object.keys(sessionStorage).filter((k) => k.startsWith('hp_cache:')).forEach((k) => sessionStorage.removeItem(k));
  } catch { /* armazenamento indisponível */ }
}

async function request(method, path, params) {
  let url = `api/${path}.php`;
  const init = { method, credentials: 'same-origin', headers: { Accept: 'application/json' } };
  if (method === 'GET' && params) url += `?${new URLSearchParams(params)}`;
  if (method === 'POST') {
    init.headers['Content-Type'] = 'application/json';
    init.headers['X-HPanel'] = '1';
    init.body = JSON.stringify(params || {});
  }

  let res;
  try {
    res = await fetch(url, init);
  } catch {
    throw new ApiError('offline', 'Sem conexão com a internet.', 0);
  }
  let json = null;
  try { json = await res.json(); } catch { /* corpo não-JSON */ }
  if (!json || typeof json.ok !== 'boolean') {
    throw new ApiError('upstream_unavailable', 'O servidor respondeu de forma inesperada. Tente novamente.', res.status);
  }
  if (json.ok) return json.data;

  const err = new ApiError(json.code, json.message, res.status);
  if (err.code === 'not_connected' && !STAY_PAGES.has(document.body.dataset.page)) location.assign('./');
  if (err.code === 'session_expired') window.dispatchEvent(new CustomEvent('hp:session-expired'));
  throw err;
}

/** GET com deduplicação e 1 nova tentativa se a Hostinger falhar. */
export function get(path, params) {
  const key = `${path}?${new URLSearchParams(params || {})}`;
  if (inflight.has(key)) return inflight.get(key);
  const p = request('GET', path, params)
    .catch(async (err) => {
      if (err.code !== 'upstream_unavailable') throw err;
      await sleep(RETRY_DELAY);
      return request('GET', path, params);
    })
    .finally(() => inflight.delete(key));
  inflight.set(key, p);
  return p;
}

export const post = (path, body) => request('POST', path, body);
