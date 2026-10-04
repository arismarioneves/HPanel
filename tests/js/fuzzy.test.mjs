import { test } from 'node:test';
import assert from 'node:assert/strict';
import { score, rank } from '../../assets/js/fuzzy.js';

const item = (text) => ({ id: text, text });
const texts = (list) => list.map((x) => x.text);

test('casa por subsequência e devolve 0 sem match', () => {
  assert.ok(score('lje', 'loja.example') > 0);
  assert.ok(score('lojaex', 'loja.example') > 0);
  assert.equal(score('xj', 'loja.example'), 0, 'fora de ordem não casa');
  assert.equal(score('lojas', 'loja.example'), 0);
  assert.equal(score('abc', ''), 0);
});

test('ignora caixa, acentos e espaços da consulta', () => {
  assert.ok(score('CONFIGURACOES', 'Configurações') > 0);
  assert.ok(score('configurações', 'CONFIGURACOES') > 0);
  assert.ok(score('alt tema', 'Alternar tema') > 0);
  assert.equal(score('configuracoes', 'Configurações'), score('Configurações', 'configuracoes'));
});

test('início de palavra vence meio de palavra', () => {
  assert.ok(score('tem', 'tema.example') > score('tem', 'sistema.example'));
  assert.ok(score('ex', 'loja.example') > score('ex', 'texto.com'), 'depois do ponto conta como início de palavra');
  assert.ok(score('blog', 'blog.loja.example') > score('blog', 'b-l-o-g.example'), 'contíguo vence espalhado');
});

test('rank filtra sem match e ordena pela pontuação', () => {
  const items = ['sistema.example', 'tema.example', 'loja.com'].map(item);
  assert.deepEqual(texts(rank('tem', items)), ['tema.example', 'sistema.example']);
});

test('boost desempata a favor de favoritos e recentes', () => {
  const items = ['a-loja.example', 'b-loja.example', 'c-loja.example'].map(item);
  const boost = (x) => ({ 'c-loja.example': 3, 'b-loja.example': 1 })[x.text] || 0;
  assert.deepEqual(texts(rank('loja', items, { boost })), ['c-loja.example', 'b-loja.example', 'a-loja.example']);
  assert.deepEqual(texts(rank('loja', items)), ['a-loja.example', 'b-loja.example', 'c-loja.example'], 'sem boost mantém a ordem original nos empates');
});

test('consulta vazia devolve só os itens com boost, do maior para o menor', () => {
  const items = ['a.example', 'b.example', 'c.example', 'd.example'].map(item);
  const boost = (x) => ({ 'b.example': 1, 'd.example': 2.5 })[x.text] || 0;
  assert.deepEqual(texts(rank('', items, { boost })), ['d.example', 'b.example']);
  assert.deepEqual(texts(rank('   ', items, { boost })), ['d.example', 'b.example']);
  assert.deepEqual(rank('', items), []);
});
