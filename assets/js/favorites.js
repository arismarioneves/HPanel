/** Sites favoritos em localStorage (`hp_favs`): lista de {domain, orderId, serverTitle}, única por domínio. */
export const FAVS_KEY = 'hp_favs';
export const MAX_FAVS = 50;

const valid = (x) => x && typeof x === 'object' && typeof x.domain === 'string' && x.domain !== '';

/** `storage` e `target` (onde chega o evento `storage` de outras abas) por parâmetro, para testar sem navegador. */
export function createFavorites(storage, target) {
  const listeners = new Set();

  function list() {
    let raw;
    try { raw = JSON.parse(storage?.getItem(FAVS_KEY) ?? '[]'); } catch { return []; }
    if (!Array.isArray(raw)) return [];
    const seen = new Set();
    const out = [];
    for (const x of raw) {
      if (!valid(x) || seen.has(x.domain)) continue;
      seen.add(x.domain);
      out.push({ domain: x.domain, orderId: x.orderId, serverTitle: typeof x.serverTitle === 'string' ? x.serverTitle : '' });
      if (out.length === MAX_FAVS) break;
    }
    return out;
  }

  const has = (domain) => list().some((x) => x.domain === domain);

  /** Adiciona ou remove; devolve se o site está favorito depois da chamada (lista cheia ou falha ao gravar: nada muda). */
  function toggle({ domain, orderId, serverTitle }) {
    const favs = list();
    const i = favs.findIndex((x) => x.domain === domain);
    if (i >= 0) favs.splice(i, 1);
    else if (favs.length >= MAX_FAVS) return false;
    else favs.push({ domain, orderId, serverTitle: serverTitle || '' });
    try {
      storage.setItem(FAVS_KEY, JSON.stringify(favs));
    } catch { // quota excedida ou storage indisponível
      return i >= 0;
    }
    listeners.forEach((fn) => fn());
    return i < 0;
  }

  function clear() {
    try { storage.removeItem(FAVS_KEY); } catch { return; }
    listeners.forEach((fn) => fn());
  }

  // key null = localStorage.clear() em outra aba.
  target?.addEventListener('storage', (e) => {
    if (e.key === FAVS_KEY || e.key === null) listeners.forEach((fn) => fn());
  });

  /** Chama `fn` quando os favoritos mudam (nesta aba ou em outra); devolve a função de cancelar. */
  function subscribe(fn) {
    listeners.add(fn);
    return () => listeners.delete(fn);
  }

  return { list, has, toggle, clear, subscribe };
}

/** Só os favoritos cujo domínio está nos servidores carregados (conta atual ou demo); `null` = nada carregado. */
export function inServers(items, servers) {
  const domains = new Set((servers || []).flatMap((s) => (s.websites || []).map((w) => w.domain)));
  return items.filter((f) => domains.has(f.domain));
}

function localStore() {
  try { return globalThis.localStorage ?? null; } catch { return null; } // acesso pode lançar (SecurityError)
}

export const { list, has, toggle, clear, subscribe } = createFavorites(localStore(), globalThis.window);
