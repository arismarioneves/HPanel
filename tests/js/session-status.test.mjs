import { test } from 'node:test';
import assert from 'node:assert/strict';
import { pillState, WARN_MINUTES } from '../../assets/js/session-status.js';

test('sem sessão: pill não aparece', () => {
  assert.equal(pillState({ connected: false, demo: false }), null);
  assert.equal(pillState(null), null);
});

test('demo: ponto azul, sem renovação', () => {
  const s = pillState({ connected: true, demo: true, expired: false, expiresAt: null, minutesLeft: null });
  assert.equal(s.tone, 'info');
  assert.equal(s.label, 'Demo');
  assert.equal(s.canRenew, false);
});

test('expirada: vermelho e "Sessão encerrada" (expired vence minutos)', () => {
  const s = pillState({ connected: true, demo: false, expired: true, minutesLeft: 0 });
  assert.equal(s.tone, 'danger');
  assert.equal(s.label, 'Sessão encerrada');
  assert.equal(s.kind, 'expired');
});

test('limiar de 15 min: ≤ 15 âmbar com minutos, 16 verde', () => {
  assert.equal(WARN_MINUTES, 15);
  const near = pillState({ connected: true, demo: false, expired: false, minutesLeft: 15 });
  assert.equal(near.tone, 'warn');
  assert.equal(near.label, 'Expira em 15 min');
  assert.equal(near.short, '15 min');
  const ok = pillState({ connected: true, demo: false, expired: false, minutesLeft: 16 });
  assert.equal(ok.tone, 'ok');
  assert.equal(ok.label, 'Conectado');
  assert.equal(ok.canRenew, true);
});

test('validade desconhecida (minutesLeft null): conectado', () => {
  assert.equal(pillState({ connected: true, demo: false, expired: false, minutesLeft: null }).tone, 'ok');
});

test('textos curtos cabem no header estreito', () => {
  const all = [
    { connected: true, demo: true },
    { connected: true, demo: false, expired: true },
    { connected: true, demo: false, expired: false, minutesLeft: 3 },
    { connected: true, demo: false, expired: false, minutesLeft: 50 },
  ].map(pillState);
  for (const s of all) assert.ok(s.short.length <= 9, s.short);
});
