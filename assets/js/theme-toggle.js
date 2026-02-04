/**
 * Dark Mode Toggle - Ultra Simple Version
 * No dependencies, no fancy features, just works
 */

(function () {
    'use strict';

    console.log('[THEME] Script loaded at:', new Date().toISOString());

    // Restore dark mode from localStorage IMMEDIATELY
    var savedMode = localStorage.getItem('darkMode');
    console.log('[THEME] Saved mode:', savedMode);

    if (savedMode === 'enabled') {
        document.body.classList.add('dark-mode');
        console.log('[THEME] Dark mode restored from localStorage');
    }

    // Define function globally
    window.toggleThemeMode = function () {
        console.log('[THEME] Toggle called');
        document.body.classList.toggle('dark-mode');
        var isDark = document.body.classList.contains('dark-mode');

        try {
            localStorage.setItem('darkMode', isDark ? 'enabled' : 'disabled');
        } catch (e) {
            console.error('[THEME] LocalStorage error:', e);
        }

        console.log('[THEME] Dark mode is now:', isDark);
    };

    console.log('[THEME] toggleThemeMode function defined:', typeof window.toggleThemeMode);
})();
