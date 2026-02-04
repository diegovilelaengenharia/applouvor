// Main JS - Global Scripts
// Definição global para garantir acesso via onclick
window.toggleThemeMode = function (e) {
    if (e) e.preventDefault(); // Evitar comportamentos padrão se passado evento

    document.body.classList.toggle('dark-mode');
    const isDark = document.body.classList.contains('dark-mode');

    // Salvar preferência
    localStorage.setItem('theme', isDark ? 'dark' : 'light');

    // Atualizar UI
    syncAllToggles(isDark);
};

function syncAllToggles(isDark) {
    const dropdownToggle = document.getElementById('darkModeToggleDropdown');
    const mobileToggle = document.getElementById('darkModeToggleMobile');

    if (dropdownToggle) dropdownToggle.checked = isDark;
    if (mobileToggle) mobileToggle.checked = isDark;
}

// Inicialização
function initTheme() {
    const savedTheme = localStorage.getItem('theme');

    // Se não há preferência salva, detectar preferência do sistema
    if (!savedTheme) {
        const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
        if (prefersDark) {
            document.body.classList.add('dark-mode');
            syncAllToggles(true);
        } else {
            document.body.classList.remove('dark-mode');
            syncAllToggles(false);
        }
        return;
    }

    // Aplicar preferência salva
    if (savedTheme === 'dark') {
        document.body.classList.add('dark-mode');
        syncAllToggles(true);
    } else {
        document.body.classList.remove('dark-mode');
        syncAllToggles(false);
    }
}

// Executar ao carregar
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initTheme);
} else {
    initTheme();
}
