import { test } from 'node:test';
import assert from 'node:assert/strict';
import { readCache, writeCache, CACHE_PREFIX } from '../../assets/js/swr.js';

function memStorage() {
  const m = new Map();
  return {
    getItem: (k) => (m.has(k) ? m.get(k) : null),
    setItem: (k, v) => { m.set(k, String(v)); },
    removeItem: (k) => { m.delete(k); },
    map: m,
  };
}

test('grava e lê o valor com a data de gravação, sob o prefixo hp_cache:', () => {
  const s = memStorage();
  writeCache(s, 'websites?', { a: 1 }, 1000);
  assert.ok(s.map.has(`${CACHE_PREFIX}websites?`));
  assert.equal(CACHE_PREFIX, 'hp_cache:');
  assert.deepEqual(readCache(s, 'websites?', 5000, 2000), { value: { a: 1 }, savedAt: 1000 });
});

test('expira por idade', () => {
  const s = memStorage();
  writeCache(s, 'k', 1, 1000);
  assert.notEqual(readCache(s, 'k', 5000, 6000), null);
  assert.equal(readCache(s, 'k', 5000, 6001), null);
  assert.equal(readCache(s, 'ausente', 5000, 1000), null);
});

test('JSON corrompido ou formato inesperado → null', () => {
  const s = memStorage();
  s.setItem(`${CACHE_PREFIX}k`, '{nao-json');
  assert.equal(readCache(s, 'k', 5000, 1000), null);
  s.setItem(`${CACHE_PREFIX}k`, '42');
  assert.equal(readCache(s, 'k', 5000, 1000), null);
});

test('setItem/getItem lançando não propaga', () => {
  const boom = { getItem() { throw new Error('x'); }, setItem() { throw new Error('QuotaExceededError'); } };
  assert.doesNotThrow(() => writeCache(boom, 'k', { a: 1 }, 1000));
  assert.equal(readCache(boom, 'k', 5000, 1000), null);
});
