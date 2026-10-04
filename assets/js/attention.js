import { percent, level } from './format.js';

const METRICS = [['storage', 'Disco'], ['inodes', 'Inodes']];
const RANK = { danger: 0, warn: 1 };

/**
 * Itens de uso ≥ 80 % (disco/inodes) a partir de Map<orderId, {title, usage}>.
 * Ordem: danger antes de warn, depois maior percentual.
 */
export function attentionItems(usageByServer) {
  const items = [];
  for (const [orderId, { title, usage }] of usageByServer) {
    for (const [key, metric] of METRICS) {
      const m = usage?.[key];
      if (!(m?.limit > 0)) continue;
      const p = percent(m.value, m.limit);
      const lv = level(p);
      if (lv !== 'ok') items.push({ orderId, title, metric, percent: p, level: lv });
    }
  }
  return items.sort((a, b) => RANK[a.level] - RANK[b.level] || b.percent - a.percent);
}

/** Nº de servidores distintos na lista. */
export const attentionCount = (items) => new Set(items.map((i) => i.orderId)).size;
