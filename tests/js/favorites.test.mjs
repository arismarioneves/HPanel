import { test } from 'node:test';
import assert from 'node:assert/strict';
import { createFavorites, inServers, FAVS_KEY, MAX_FAVS } from '../../assets/js/favorites.js';

function memStorage() {
  const m = new Map();
  return {
    getItem: (k) => (m.has(k) ? m.get(k) : null),
    setItem: (k, v) => { m.set(k, String(v)); },
    removeItem: (k) => { m.delete(k); },
    map: m,
  };
}

const fav = (n) => ({ domain: `site${n}.example`, orderId: 100 + n, serverTitle: `Servidor ${n}` });

test('toggle adiciona e depois remove, persistindo em hp_favs', () => {
  const s = memStorage();
  const f = createFavorites(s);
  assert.equal(FAVS_KEY, 'hp_favs');
  assert.equal(f.toggle(fav(1)), true);
  assert.equal(f.has('site1.example'), true);
  assert.deepEqual(JSON.parse(s.map.get(FAVS_KEY)), [fav(1)]);
  assert.equal(f.toggle(fav(1)), false);
  assert.equal(f.has('site1.example'), false);
  assert.deepEqual(f.list(), []);
});

test('sem duplicatas: o mesmo domínio guardado duas vezes vira uma entrada', () => {
  const s = memStorage();
  s.setItem(FAVS_KEY, JSON.stringify([fav(1), fav(1), fav(2)]));
  const f = createFavorites(s);
  assert.deepEqual(f.list().map((x) => x.domain), ['site1.example', 'site2.example']);
  f.toggle(fav(3));
  assert.deepEqual(f.list().map((x) => x.domain), ['site1.example', 'site2.example', 'site3.example']);
});

test('limite de 50: o 51º não entra, mas remover continua possível', () => {
  const f = createFavorites(memStorage());
  assert.equal(MAX_FAVS, 50);
  for (let i = 0; i < 50; i++) assert.equal(f.toggle(fav(i)), true);
  assert.equal(f.toggle(fav(50)), false);
  assert.equal(f.has('site50.example'), false);
  assert.equal(f.list().length, 50);
  assert.equal(f.toggle(fav(0)), false);
  assert.equal(f.toggle(fav(50)), true);
  assert.equal(f.list().length, 50);
});

test('JSON corrompido ou formato inesperado → lista vazia', () => {
  const s = memStorage();
  const f = createFavorites(s);
  s.setItem(FAVS_KEY, '{nao-json');
  assert.deepEqual(f.list(), []);
  s.setItem(FAVS_KEY, '{"domain":"a.example"}');
  assert.deepEqual(f.list(), []);
  s.setItem(FAVS_KEY, JSON.stringify([null, 3, { domain: 7 }, fav(1)]));
  assert.deepEqual(f.list(), [fav(1)]);
  assert.equal(f.toggle(fav(2)), true);
  assert.deepEqual(f.list(), [fav(1), fav(2)]);
});

test('storage indisponível ou lançando não propaga', () => {
  const boom = { getItem() { throw new Error('x'); }, setItem() { throw new Error('QuotaExceededError'); } };
  const f = createFavorites(boom);
  assert.deepEqual(f.list(), []);
  assert.doesNotThrow(() => f.toggle(fav(1)));
  assert.equal(f.toggle(fav(1)), false);
  assert.deepEqual(createFavorites(null).list(), []);
  assert.equal(createFavorites(null).toggle(fav(1)), false);
});

test('subscribe: avisa na mesma aba e em evento storage de hp_favs; cancelar para de avisar', () => {
  const target = new EventTarget();
  const s = memStorage();
  const f = createFavorites(s, target);
  let calls = 0;
  const off = f.subscribe(() => { calls++; });
  f.toggle(fav(1));
  assert.equal(calls, 1);
  const ev = (key) => Object.assign(new Event('storage'), { key });
  target.dispatchEvent(ev(FAVS_KEY));
  target.dispatchEvent(ev('hp_theme'));
  target.dispatchEvent(ev(null)); // localStorage.clear() em outra aba
  assert.equal(calls, 3);
  off();
  f.toggle(fav(1));
  target.dispatchEvent(ev(FAVS_KEY));
  assert.equal(calls, 3);
});

test('clear apaga todos os favoritos e avisa os inscritos', () => {
  const s = memStorage();
  const f = createFavorites(s);
  f.toggle(fav(1));
  let calls = 0;
  f.subscribe(() => { calls++; });
  f.clear();
  assert.deepEqual(f.list(), []);
  assert.equal(s.map.has(FAVS_KEY), false);
  assert.equal(calls, 1);
});

test('inServers mantém só favoritos de domínios presentes nos servidores carregados', () => {
  const servers = [{ orderId: 101, websites: [{ domain: 'site1.example' }] }, { orderId: 9, websites: [] }];
  assert.deepEqual(inServers([fav(1), fav(2)], servers), [fav(1)]);
  assert.deepEqual(inServers([fav(1)], null), []);
});
