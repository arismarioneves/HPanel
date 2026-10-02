import { test } from 'node:test';
import assert from 'node:assert/strict';
import { attentionItems, attentionCount } from '../../assets/js/attention.js';

const use = (storage, inodes) => ({
  storage: storage == null ? undefined : { value: storage, limit: 100 },
  inodes: inodes == null ? undefined : { value: inodes, limit: 100 },
});

test('limiares: 79.9 fora, 80 warn, 90 danger', () => {
  const items = attentionItems(new Map([
    [1, { title: 'A', usage: use(79.9, null) }],
    [2, { title: 'B', usage: use(80, null) }],
    [3, { title: 'C', usage: use(null, 90) }],
  ]));
  assert.deepEqual(items, [
    { orderId: 3, title: 'C', metric: 'Inodes', percent: 90, level: 'danger' },
    { orderId: 2, title: 'B', metric: 'Disco', percent: 80, level: 'warn' },
  ]);
});

test('ordena danger antes de warn, depois maior %', () => {
  const items = attentionItems(new Map([
    [1, { title: 'A', usage: use(85, null) }],
    [2, { title: 'B', usage: use(91, null) }],
    [3, { title: 'C', usage: use(88, null) }],
    [4, { title: 'D', usage: use(99, null) }],
  ]));
  assert.deepEqual(items.map((i) => [i.orderId, i.level]), [[4, 'danger'], [2, 'danger'], [3, 'warn'], [1, 'warn']]);
});

test('disco e inodes altos no mesmo servidor: 2 itens, 1 servidor', () => {
  const items = attentionItems(new Map([
    [7, { title: 'X', usage: use(95, 82) }],
    [8, { title: 'Y', usage: use(10, 10) }],
  ]));
  assert.equal(items.length, 2);
  assert.deepEqual(items.map((i) => i.metric), ['Disco', 'Inodes']);
  assert.equal(attentionCount(items), 1);
});

test('sem limite ou sem uso não gera item', () => {
  const items = attentionItems(new Map([
    [1, { title: 'A', usage: { storage: { value: 50, limit: 0 } } }],
    [2, { title: 'B', usage: null }],
  ]));
  assert.deepEqual(items, []);
  assert.equal(attentionCount(items), 0);
});
