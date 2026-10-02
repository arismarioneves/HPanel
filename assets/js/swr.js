/** Cache de respostas (stale-while-revalidate) — lógica pura, recebe o storage por parâmetro. */
export const CACHE_PREFIX = 'hp_cache:';

/** Lê `{value, savedAt}` se existir e tiver no máximo `maxAgeMs`; qualquer falha → null. */
export function readCache(storage, key, maxAgeMs, now = Date.now()) {
  try {
    const raw = storage.getItem(CACHE_PREFIX + key);
    if (raw == null) return null;
    const entry = JSON.parse(raw);
    if (!entry || typeof entry !== 'object' || typeof entry.savedAt !== 'number' || !('value' in entry)) return null;
    if (now - entry.savedAt > maxAgeMs) return null;
    return { value: entry.value, savedAt: entry.savedAt };
  } catch {
    return null;
  }
}

/** Grava o valor; armazenamento cheio/indisponível é ignorado. */
export function writeCache(storage, key, value, now = Date.now()) {
  try {
    storage.setItem(CACHE_PREFIX + key, JSON.stringify({ savedAt: now, value }));
  } catch { /* quota excedida ou storage indisponível */ }
}
