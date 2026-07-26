// Hell/Dunkel-Modus für Unterseiten: folgt Systemeinstellung, manuell umschaltbar, merkt sich die Wahl
(function () {
  const root = document.documentElement;
  const btn = document.getElementById('themeToggle');
  const saved = localStorage.getItem('theme');
  const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
  function apply(theme) {
    root.setAttribute('data-theme', theme);
    if (btn) btn.textContent = theme === 'dark' ? '☀️' : '🌙';
  }
  apply(saved || (prefersDark ? 'dark' : 'light'));
  if (btn) {
    btn.addEventListener('click', () => {
      const next = root.getAttribute('data-theme') === 'dark' ? 'light' : 'dark';
      localStorage.setItem('theme', next);
      apply(next);
    });
  }
})();
