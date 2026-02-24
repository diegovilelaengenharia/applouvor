// main.js - Global Scripts
// toggleThemeMode is defined in theme-toggle.js (loaded in HEAD)
// toggleProfileDropdown is defined in profile-dropdown.js (loaded in footer)

// Sync theme toggles across dropdowns
function syncAllToggles(isDark) {
    const dropdownToggle = document.getElementById('darkModeToggleDropdown');
    const mobileToggle   = document.getElementById('darkModeToggleMobile');
    if (dropdownToggle) dropdownToggle.checked = isDark;
    if (mobileToggle)   mobileToggle.checked   = isDark;
}

// Theme Initialization
function initTheme() {
    const savedTheme  = localStorage.getItem('theme');
    const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
    const isDark      = savedTheme ? savedTheme === 'dark' : prefersDark;

    document.body.classList.toggle('dark-mode', isDark);
    syncAllToggles(isDark);
}

// Run as early as possible
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initTheme);
} else {
    initTheme();
}
