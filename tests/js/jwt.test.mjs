import { test } from 'node:test';
import assert from 'node:assert/strict';
import { inspectToken } from '../../assets/js/jwt.js';

const b64 = (s) => Buffer.from(s).toString('base64url');
const make = (exp) => `${b64('{"typ":"JWT","alg":"RS256"}')}.${b64(JSON.stringify({ exp, sub: 'x' }))}.${b64('assinatura')}`;
const NOW = 1_800_000_000;

test('token bem-formado com exp futuro: ok com minutos restantes', () => {
  const token = make(NOW + 52 * 60 + 30);
  const r = inspectToken(token, NOW);
  assert.equal(r.ok, true);
  assert.equal(r.token, token);
  assert.equal(r.exp, NOW + 52 * 60 + 30);
  assert.equal(r.minutesLeft, 52);
});

test('exp no passado (ou agora): erro de token expirado', () => {
  for (const exp of [NOW - 1, NOW]) {
    const r = inspectToken(make(exp), NOW);
    assert.equal(r.ok, false);
    assert.equal(r.error, 'Esse token já expirou. Entre no hPanel e copie um novo.');
  }
});

test('lixo, prefixo jwt= e vazio: erro de formato', () => {
  const malformed = 'Isso não parece um token JWT. Ele começa com "eyJ" e tem três partes separadas por ponto.';
  for (const raw of ['abc', `jwt=${make(NOW + 3600)}`, '', 'eyJa.b', 'eyJa.b.c.d', 'eyJ+.b.c', null]) {
    const r = inspectToken(raw, NOW);
    assert.equal(r.ok, false, String(raw));
    assert.equal(r.error, malformed);
  }
});

test('token maior que 8192 caracteres é recusado como o servidor faz', () => {
  const r = inspectToken(`eyJ${'a'.repeat(8190)}.b.c`, NOW);
  assert.equal(r.ok, false);
});

test('espaços e quebras de linha ao redor são aceitos e removidos', () => {
  const token = make(NOW + 600);
  const r = inspectToken(`  \n${token}\r\n\t `, NOW);
  assert.equal(r.ok, true);
  assert.equal(r.token, token);
  assert.equal(r.minutesLeft, 10);
});

test('payload sem exp legível: aceito sem validade conhecida (o servidor decide)', () => {
  const r = inspectToken('eyJhbGciOiJIUzI1NiJ9.bm9wZQ.c2ln', NOW);
  assert.equal(r.ok, true);
  assert.equal(r.exp, undefined);
  assert.equal(r.minutesLeft, undefined);
});
