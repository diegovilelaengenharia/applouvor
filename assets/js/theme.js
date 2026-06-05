// assets/js/theme.js - Gerenciador de Tema (Light/Dark Mode)

(function () {
  const getThemePreference = () => {
    const savedTheme = localStorage.getItem('theme');
    if (savedTheme) {
      return savedTheme;
    }
    const systemPrefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
    return systemPrefersDark ? 'dark' : 'light';
  };

  const applyTheme = (theme) => {
    const root = document.documentElement;
    if (theme === 'dark') {
      root.classList.add('dark');
    } else {
      root.classList.remove('dark');
    }
    localStorage.setItem('theme', theme);
  };

  // Inicializa o tema imediatamente
  const currentTheme = getThemePreference();
  applyTheme(currentTheme);

  // Expõe a função para alternar tema globalmente
  window.toggleTheme = () => {
    const activeTheme = document.documentElement.classList.contains('dark') ? 'light' : 'dark';
    applyTheme(activeTheme);
    window.dispatchEvent(new CustomEvent('theme-changed', { detail: { theme: activeTheme } }));
  };

  window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', (e) => {
    if (!localStorage.getItem('theme')) {
      applyTheme(e.matches ? 'dark' : 'light');
    }
  });
})();
