// Pré-validação local do token (mesmas regras e mensagens de lib/Validate.php + api/connect.php).
const SHAPE = /^eyJ[A-Za-z0-9_-]+\.[A-Za-z0-9_-]+\.[A-Za-z0-9_-]+$/;
const MAX_LENGTH = 8192;
const MALFORMED = 'Isso não parece um token JWT. Ele começa com "eyJ" e tem três partes separadas por ponto.';
const EXPIRED = 'Esse token já expirou. Entre no hPanel e copie um novo.';

function expiry(token) {
  try {
    const part = token.split('.')[1].replace(/-/g, '+').replace(/_/g, '/');
    const payload = JSON.parse(atob(part.padEnd(part.length + ((4 - (part.length % 4)) % 4), '=')));
    const exp = Number(payload?.exp);
    return payload?.exp != null && Number.isFinite(exp) ? Math.trunc(exp) : undefined;
  } catch {
    return undefined; // payload ilegível: o servidor decide
  }
}

/**
 * @param {unknown} raw texto colado pelo usuário
 * @param {number} [now] segundos desde a época (padrão: agora)
 * @returns {{ok: boolean, token?: string, exp?: number, minutesLeft?: number, error?: string}}
 */
export function inspectToken(raw, now = Date.now() / 1000) {
  const token = typeof raw === 'string' ? raw.trim() : '';
  if (token.length > MAX_LENGTH || !SHAPE.test(token)) return { ok: false, error: MALFORMED };
  const exp = expiry(token);
  if (exp === undefined) return { ok: true, token };
  if (exp <= now) return { ok: false, error: EXPIRED };
  return { ok: true, token, exp, minutesLeft: Math.floor((exp - now) / 60) };
}
