/**
 * Dark Mode Toggle - Ultra Simple Version
 * Synced with main.js localStorage keys
 */

(function () {
    'use strict';

    console.log('[THEME] Script loaded at:', new Date().toISOString());

    // Restore dark mode from localStorage IMMEDIATELY
    // Using 'theme' key to match main.js expectations
    var savedTheme = localStorage.getItem('theme');
    console.log('[THEME] Saved theme:', savedTheme);

    if (savedTheme === 'dark') {
        document.body.classList.add('dark-mode');
        console.log('[THEME] Dark mode restored from localStorage');
    } else if (savedTheme === 'light') {
        document.body.classList.remove('dark-mode');
        console.log('[THEME] Light mode restored from localStorage');
    }
    // If savedTheme is null, do nothing (let main.js handle system preference)

    // Helper function to sync all toggles
    function syncAllToggles(isDark) {
        var toggles = document.querySelectorAll('#darkModeToggle, #darkModeToggleMobile, #darkModeToggleDropdown');
        for (var i = 0; i < toggles.length; i++) {
            if (toggles[i] && toggles[i].type === 'checkbox') {
                toggles[i].checked = isDark;
            }
        }
    }

    // Define function globally
    window.toggleThemeMode = function (e) {
        if (e) e.preventDefault();

        console.log('[THEME] Toggle called');
        document.body.classList.toggle('dark-mode');
        var isDark = document.body.classList.contains('dark-mode');

        try {
            // Save using 'theme' key with 'dark'/'light' values to match main.js
            localStorage.setItem('theme', isDark ? 'dark' : 'light');
            console.log('[THEME] Saved to localStorage:', isDark ? 'dark' : 'light');
        } catch (e) {
            console.error('[THEME] LocalStorage error:', e);
        }

        // Sync all toggles
        syncAllToggles(isDark);

        console.log('[THEME] Theme is now:', isDark ? 'dark' : 'light');
    };

    console.log('[THEME] toggleThemeMode function defined:', typeof window.toggleThemeMode);

    // Initialize toggles on load
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function () {
            var isDark = document.body.classList.contains('dark-mode');
            syncAllToggles(isDark);
        });
    } else {
        var isDark = document.body.classList.contains('dark-mode');
        syncAllToggles(isDark);
    }
})();
