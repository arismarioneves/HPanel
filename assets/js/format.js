export const percent = (value, limit) => (limit > 0 ? Math.min(100, Math.round((Number(value) / Number(limit)) * 1000) / 10) : 0);

export const level = (p) => (p >= 90 ? 'danger' : p >= 80 ? 'warn' : 'ok');

export function formatMb(mb) {
  const n = Number(mb) || 0;
  if (n >= 1024 * 1024) return `${(n / 1048576).toFixed(1)} TB`;
  if (n >= 1024) return `${(n / 1024).toFixed(1)} GB`;
  return `${Math.round(n)} MB`;
}

const compact = new Intl.NumberFormat('pt-BR', { notation: 'compact', maximumFractionDigits: 1 });
export const formatNumber = (n) => compact.format(Number(n) || 0);

export function formatDate(iso) {
  const d = iso ? new Date(iso) : null;
  return d && !Number.isNaN(d.getTime()) ? d.toLocaleDateString('pt-BR') : '—';
}

export function relTime(unix) {
  if (!unix) return '';
  const s = Math.max(0, Math.round(Date.now() / 1000 - unix));
  if (s < 60) return 'agora';
  const m = Math.round(s / 60);
  return m < 60 ? `há ${m} min` : `há ${Math.round(m / 60)} h`;
}
