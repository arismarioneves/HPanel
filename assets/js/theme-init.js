(function () {
  var t = null;
  try { t = localStorage.getItem('hp_theme'); } catch (e) { /* armazenamento indisponível */ }
  if (t !== 'light' && t !== 'dark') {
    t = window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
  }
  document.documentElement.dataset.theme = t;
})();
