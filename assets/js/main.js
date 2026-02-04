// Main JS - Global Scripts
// toggleThemeMode is now defined in theme-toggle.js (loaded in HEAD)

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
