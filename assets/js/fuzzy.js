/** Busca aproximada da paleta: subsequência sem caixa/acentos, com bônus para início de palavra e trechos contíguos. */

const MATCH = 2;
const WORD_START = 3;
const FIRST_CHAR = 1;
const CONTIGUOUS = 3;
const GAP = 1;

const fold = (s) => String(s).normalize('NFD').replace(/[\u0300-\u036f]/g, '').toLowerCase();
const isWordChar = (c) => /[a-z0-9]/.test(c);

/**
 * Pontuação de `query` em `text` (0 = não casa). Espaços da consulta são ignorados.
 * Escolhe o melhor alinhamento (programação dinâmica), não o primeiro encontrado.
 */
export function score(query, text) {
  const q = fold(query).replace(/\s+/g, '');
  const t = fold(text);
  if (!q || q.length > t.length) return 0;

  const bonus = (j) => MATCH + (j === 0 ? FIRST_CHAR : 0) + (j === 0 || !isWordChar(t[j - 1]) ? WORD_START : 0);
  // prev[j]: melhor pontuação com q[i-1] casado em t[j] (-Infinity = impossível).
  let prev = Array.from(t, (c, j) => (c === q[0] ? bonus(j) : -Infinity));
  for (let i = 1; i < q.length; i++) {
    const cur = new Array(t.length).fill(-Infinity);
    let bestBefore = -Infinity; // melhor prev[k] com k < j - 1 (casamento com intervalo)
    for (let j = 1; j < t.length; j++) {
      if (j >= 2) bestBefore = Math.max(bestBefore, prev[j - 2]);
      if (t[j] !== q[i]) continue;
      cur[j] = bonus(j) + Math.max(prev[j - 1] + CONTIGUOUS, bestBefore - GAP);
    }
    prev = cur;
  }
  const best = Math.max(...prev);
  // Desempate: texto mais curto vence (fração < 1 não inverte diferenças de pontuação).
  return best === -Infinity ? 0 : best + 1 / (1 + t.length);
}

/**
 * Ordena `items` ({text}) por `score + boost(item)`, descartando os que não casam; empates mantêm a ordem original.
 * Consulta vazia: só os itens com boost > 0, do maior para o menor (favoritos e recentes).
 */
export function rank(query, items, { boost = () => 0 } = {}) {
  const empty = !String(query).trim();
  return items
    .map((item, index) => {
      const b = boost(item) || 0;
      const s = empty ? (b > 0 ? 1 : 0) : score(query, item.text);
      return { item, index, total: s > 0 ? s + b : 0 };
    })
    .filter((x) => x.total > 0)
    .sort((a, b) => b.total - a.total || a.index - b.index)
    .map((x) => x.item);
}
