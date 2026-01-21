// Main JS - Global Scripts
document.addEventListener('DOMContentLoaded', () => {
    // 1. Dark Mode Logic
    const savedTheme = localStorage.getItem('theme');
    
    if (savedTheme === 'dark') {
        document.body.classList.add('dark-mode');
        syncAllToggles(true);
    } else {
        syncAllToggles(false);
    }

    window.toggleThemeMode = function() {
        document.body.classList.toggle('dark-mode');
        const isDark = document.body.classList.contains('dark-mode');
        
        // Salvar preferência
        localStorage.setItem('theme', isDark ? 'dark' : 'light');
        
        // Sincronizar todos os toggles
        syncAllToggles(isDark);
    }

    function syncAllToggles(isDark) {
        // Sincronizar checkboxes dos dropdowns
        const dropdownToggle = document.getElementById('darkModeToggleDropdown');
        const mobileToggle = document.getElementById('darkModeToggleMobile');
        
        if (dropdownToggle) dropdownToggle.checked = isDark;
        if (mobileToggle) mobileToggle.checked = isDark;
    }
});
