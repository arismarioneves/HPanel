const KEY = 'hp_theme';
const darkQuery = window.matchMedia('(prefers-color-scheme: dark)');

const systemTheme = () => (darkQuery.matches ? 'dark' : 'light');
const apply = (theme) => { document.documentElement.dataset.theme = theme; };

/** 'light' | 'dark' | 'system' (sem `hp_theme` salvo = segue o sistema). */
export function themeMode() {
  let saved = null;
  try { saved = localStorage.getItem(KEY); } catch { /* ignora */ }
  return saved === 'light' || saved === 'dark' ? saved : 'system';
}

export function setThemeMode(mode) {
  try {
    if (mode === 'light' || mode === 'dark') localStorage.setItem(KEY, mode);
    else localStorage.removeItem(KEY);
  } catch { /* ignora */ }
  apply(mode === 'light' || mode === 'dark' ? mode : systemTheme());
  document.dispatchEvent(new CustomEvent('hp:theme', { detail: { mode: themeMode() } }));
}

export function toggleTheme() {
  setThemeMode(document.documentElement.dataset.theme === 'dark' ? 'light' : 'dark');
}

// No modo sistema, acompanha a troca de tema do sistema operacional sem recarregar.
darkQuery.addEventListener('change', () => {
  if (themeMode() === 'system') apply(systemTheme());
});
