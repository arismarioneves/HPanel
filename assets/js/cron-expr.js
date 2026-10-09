/** Expressões cron de 5 campos: presets, validação (mesma regra do servidor) e descrição em português. */

export const FIELDS = [
  ['minute', 'Minuto', '0–59'],
  ['hour', 'Hora', '0–23'],
  ['day', 'Dia', '1–31'],
  ['month', 'Mês', '1–12'],
  ['weekday', 'Dia da semana', '0–6 (0 = domingo)'],
];

export const PRESETS = [
  ['*/5 * * * *', 'A cada 5 minutos'],
  ['*/15 * * * *', 'A cada 15 minutos'],
  ['0 * * * *', 'Uma vez por hora'],
  ['0,30 * * * *', 'Duas vezes por hora'],
  ['0 0,12 * * *', 'Duas vezes por dia'],
  ['0 0 * * *', 'Uma vez por dia'],
  ['0 0 * * 0', 'Uma vez por semana'],
  ['0 0 1,15 * *', 'Dias 1 e 15'],
  ['0 0 1 * *', 'Uma vez por mês'],
  ['0 0 1 1 *', 'Uma vez por ano'],
];

const PART = /^[0-9*,/-]+$/;
const WEEKDAYS = ['Todo domingo', 'Toda segunda', 'Toda terça', 'Toda quarta', 'Toda quinta', 'Toda sexta', 'Todo sábado', 'Todo domingo'];
const INT = /^\d+$/;

export const normalizeCron = (expr) => String(expr ?? '').trim().split(/\s+/).join(' ');

export function isValidCron(expr) {
  const parts = normalizeCron(expr).split(' ');
  return parts.length === 5 && parts.every((p) => PART.test(p)) && normalizeCron(expr).length <= 100;
}

const pad = (n) => String(n).padStart(2, '0');

/** Texto curto para os padrões comuns; null quando a expressão foge deles (a expressão crua basta). */
export function describeCron(expr) {
  if (!isValidCron(expr)) return null;
  const [min, hour, day, month, weekday] = normalizeCron(expr).split(' ');
  const rest = [day, month, weekday];
  const everyDay = rest.every((p) => p === '*');

  if (min === '*' && hour === '*' && everyDay) return 'A cada minuto';
  const step = /^\*\/(\d+)$/.exec(min);
  if (INT.test(min) && hour === '*' && everyDay) return min === '0' ? 'De hora em hora' : `De hora em hora, às :${pad(min)}`;
  if (/^\d+(,\d+)+$/.test(min) && hour === '*' && everyDay) return `De hora em hora, às ${min.split(',').map((m) => `:${pad(m)}`).join(' e ')}`;
  if (step && hour === '*' && everyDay) return `A cada ${step[1]} min`;

  if (!INT.test(min) || !/^\d+(,\d+)*$/.test(hour)) return null;
  const at = hour.split(',').map((h) => `${pad(h)}:${pad(min)}`).join(' e ');
  if (everyDay) return `Todo dia às ${at}`;
  if (day === '*' && month === '*' && /^[0-7]$/.test(weekday)) return `${WEEKDAYS[weekday]} às ${at}`;
  if (day === '*' && month === '*' && weekday === '1-5') return `Dias úteis às ${at}`;
  if (/^\d+(,\d+)*$/.test(day) && month === '*' && weekday === '*') return `Todo mês, dia ${day.split(',').join(' e ')}, às ${at}`;
  if (INT.test(day) && INT.test(month) && weekday === '*') return `Todo ano em ${pad(day)}/${pad(month)} às ${at}`;
  return null;
}
