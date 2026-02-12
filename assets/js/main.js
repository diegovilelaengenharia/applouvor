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

// Profile Dropdown Logic
function toggleProfileDropdown(event, id) {
    event.stopPropagation();
    const dropdown = document.getElementById(id);
    if (!dropdown) return;
    
    // Close others
    document.querySelectorAll('.profile-dropdown').forEach(d => {
        if (d.id !== id) {
            d.style.display = 'none';
            d.classList.remove('active');
        }
    });

    if (dropdown.style.display === 'block') {
        dropdown.style.display = 'none';
        dropdown.classList.remove('active');
    } else {
        dropdown.style.display = 'block';
        // Small delay to allow display:block to render before adding opacity class
        requestAnimationFrame(() => {
            dropdown.classList.add('active');
        });
    }
}

// Close on click outside
window.addEventListener('click', (e) => {
    // Check if the click is inside a dropdown or a toggle button
    if (!e.target.closest('.profile-dropdown') && !e.target.closest('.profile-avatar-btn')) {
        document.querySelectorAll('.profile-dropdown').forEach(d => {
            d.style.display = 'none';
            d.classList.remove('active');
        });
    }
});
