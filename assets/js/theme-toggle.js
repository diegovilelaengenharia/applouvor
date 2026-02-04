/**
 * Dark Mode Toggle Script
 * Dedicated file to ensure this logic is loaded regardless of layout.php caching
 */

console.log('Theme Toggle Script Loaded');

window.toggleThemeMode = function () {
    console.log('Toggling theme mode...');
    document.body.classList.toggle('dark-mode');
    const isDark = document.body.classList.contains('dark-mode');

    // Save to local storage
    localStorage.setItem('darkMode', isDark ? 'enabled' : 'disabled');

    // Dispatch global event
    window.dispatchEvent(new CustomEvent('themeChanged', { detail: { isDark } }));

    // Update all toggles in DOM
    document.querySelectorAll('#darkModeToggle, #darkModeToggleMobile').forEach(el => {
        if (el.type === 'checkbox') el.checked = isDark;
    });
};

// Initialize toggles on load
document.addEventListener('DOMContentLoaded', () => {
    const isDark = document.body.classList.contains('dark-mode');
    document.querySelectorAll('#darkModeToggle, #darkModeToggleMobile').forEach(el => {
        if (el.type === 'checkbox') el.checked = isDark;
    });
});
