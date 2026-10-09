import { h } from './h.js';

const NS = 'http://www.w3.org/2000/svg';
const W = 300;
const H = 72;

function svg(tag, attrs = {}) {
  const el = document.createElementNS(NS, tag);
  for (const [k, v] of Object.entries(attrs)) el.setAttribute(k, String(v));
  return el;
}

/** Topo do eixo Y: folga sobre o pico, sem achatar tudo contra o limite (CPU a 1 % de 100 % vira uma linha). */
export function scaleTop(peak, limit) {
  const top = Math.max(peak * 1.25, limit * 0.05, 1);
  return limit > 0 ? Math.min(top, limit) : top;
}

/**
 * Gráfico de área em SVG puro (sem biblioteca).
 * points = [[unix, uso, faults]]; faults > 0 vira marca vermelha no rodapé.
 * `format(v)` formata valores; `formatTime(unix)` o horário do tooltip.
 */
export function areaChart(points, { limit = 0, format = String, formatTime = String, label = '' } = {}) {
  const n = points.length;
  const top = scaleTop(Math.max(0, ...points.map((p) => p[1])), limit);
  const x = (i) => (n > 1 ? (i / (n - 1)) * W : W / 2);
  const y = (v) => H - (Math.min(v, top) / top) * (H - 2);

  const line = points.map((p, i) => `${i ? 'L' : 'M'}${x(i).toFixed(1)},${y(p[1]).toFixed(1)}`).join('');
  const chart = svg('svg', { viewBox: `0 0 ${W} ${H}`, preserveAspectRatio: 'none', class: 'chart-svg', 'aria-hidden': 'true' });
  if (n) {
    chart.append(
      svg('path', { d: `${line}L${x(n - 1)},${H}L${x(0)},${H}Z`, class: 'chart-area' }),
      svg('path', { d: line, class: 'chart-line', 'vector-effect': 'non-scaling-stroke' }));
  }
  if (limit > 0 && limit <= top) {
    chart.append(svg('line', { x1: 0, x2: W, y1: y(limit), y2: y(limit), class: 'chart-limit', 'vector-effect': 'non-scaling-stroke' }));
  }
  points.forEach((p, i) => {
    if (p[2] > 0) chart.append(svg('rect', { x: Math.max(0, x(i) - 1.5), y: H - 6, width: 3, height: 6, class: 'chart-fault' }));
  });

  const cursor = h('div', { class: 'chart-cursor', hidden: true });
  const tip = h('div', { class: 'chart-tip', hidden: true });
  const box = h('div', { class: 'chart', role: 'img', 'aria-label': label }, chart, cursor, tip);
  if (!n) return box;

  const hide = () => { cursor.hidden = true; tip.hidden = true; };
  box.addEventListener('pointermove', (event) => {
    const rect = box.getBoundingClientRect();
    const i = Math.round(Math.min(1, Math.max(0, (event.clientX - rect.left) / rect.width)) * (n - 1));
    const [t, v, faults] = points[i];
    const left = (x(i) / W) * rect.width;
    cursor.hidden = false;
    tip.hidden = false;
    cursor.style.left = `${left}px`;
    tip.textContent = `${formatTime(t)} · ${format(v)}${faults > 0 ? ` · limite atingido ${faults}×` : ''}`;
    tip.classList.toggle('chart-tip-left', left > rect.width / 2);
    tip.style.left = `${left}px`;
  });
  box.addEventListener('pointerleave', hide);
  return box;
}

/** Anel de porcentagem (0–100) com o nível de cor dos medidores. */
export function donut(pct, tone) {
  const r = 26;
  const c = 2 * Math.PI * r;
  const ring = svg('svg', { viewBox: '0 0 64 64', class: `donut donut-${tone}`, 'aria-hidden': 'true' });
  const value = svg('circle', { cx: 32, cy: 32, r, class: 'donut-value', 'stroke-dasharray': `${(c * Math.min(100, pct)) / 100} ${c}` });
  ring.append(svg('circle', { cx: 32, cy: 32, r, class: 'donut-track' }), value);
  return ring;
}
