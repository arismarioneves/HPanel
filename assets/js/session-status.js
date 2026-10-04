/** Minutos restantes a partir dos quais a pill avisa que a sessão vai expirar. */
export const WARN_MINUTES = 15;

/**
 * Estado visual da pill de sessão a partir da resposta de `api/session`.
 * `null` = sem sessão (a pill não aparece). `short` é o texto para telas estreitas.
 */
export function pillState(s) {
  if (!s || !s.connected) return null;
  if (s.demo) {
    return { kind: 'demo', tone: 'info', label: 'Demo', short: 'Demo', hint: 'Dados fictícios', canRenew: false };
  }
  if (s.expired) {
    return { kind: 'expired', tone: 'danger', label: 'Sessão encerrada', short: 'Encerrada', hint: 'Clique para reconectar', canRenew: false };
  }
  const minutes = s.minutesLeft;
  if (minutes != null && minutes <= WARN_MINUTES) {
    return { kind: 'expiring', tone: 'warn', label: `Expira em ${minutes} min`, short: `${minutes} min`, hint: 'Renove para continuar', canRenew: true };
  }
  return { kind: 'ok', tone: 'ok', label: 'Conectado', short: 'Ativa', hint: 'Renova sozinho', canRenew: true };
}
