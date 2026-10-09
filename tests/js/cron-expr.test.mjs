import { test } from 'node:test';
import assert from 'node:assert/strict';
import { isValidCron, describeCron, normalizeCron, PRESETS } from '../../assets/js/cron-expr.js';

test('validação: 5 campos com números, *, vírgula, hífen e barra (mesma regra do servidor)', () => {
  assert.equal(isValidCron('*/5 * * * *'), true);
  assert.equal(isValidCron('  0   0,12 1-15 * 1-5 '), true);
  for (const bad of ['', '0 0 * *', '0 0 * * * *', '@daily', '0 0 * * *;rm', 'a b c d e']) {
    assert.equal(isValidCron(bad), false, bad);
  }
});

test('normaliza espaços para gravar e comparar com presets', () => {
  assert.equal(normalizeCron(' 0\t0  * * * '), '0 0 * * *');
});

test('descreve os horários mais comuns', () => {
  assert.equal(describeCron('* * * * *'), 'A cada minuto');
  assert.equal(describeCron('*/5 * * * *'), 'A cada 5 min');
  assert.equal(describeCron('0 * * * *'), 'De hora em hora');
  assert.equal(describeCron('0,30 * * * *'), 'De hora em hora, às :00 e :30');
  assert.equal(describeCron('5 * * * *'), 'De hora em hora, às :05');
  assert.equal(describeCron('0 0 * * *'), 'Todo dia às 00:00');
  assert.equal(describeCron('30 6 * * 1'), 'Toda segunda às 06:30');
  assert.equal(describeCron('0 0 * * 0'), 'Todo domingo às 00:00');
  assert.equal(describeCron('5 3,15 * * *'), 'Todo dia às 03:05 e 15:05');
  assert.equal(describeCron('0 8 * * 1-5'), 'Dias úteis às 08:00');
  assert.equal(describeCron('0 0 1,15 * *'), 'Todo mês, dia 1 e 15, às 00:00');
  assert.equal(describeCron('0 0 1 1 *'), 'Todo ano em 01/01 às 00:00');
});

test('padrões fora do comum ou inválidos não ganham descrição', () => {
  assert.equal(describeCron('*/10 2-4 * * *'), null);
  assert.equal(describeCron('0 0 * 6 *'), null);
  assert.equal(describeCron('lixo'), null);
});

test('todo preset é válido e descrito', () => {
  for (const [expr, label] of PRESETS) {
    assert.equal(isValidCron(expr), true, label);
    assert.notEqual(describeCron(expr), null, label);
  }
});
