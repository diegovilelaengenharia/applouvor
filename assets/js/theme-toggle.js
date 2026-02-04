/**
 * Dark Mode Toggle - Fixed for HEAD loading
 * Defines function immediately, but defers body access
 */

(function () {
    'use strict';

    console.log('[THEME] Script loaded');

    // Prevent double-toggle flag
    var isToggling = false;

    // Global toggle function - MUST be defined immediately
    window.toggleThemeMode = function () {
        // Prevent double-toggle
        if (isToggling) {
            console.log('[THEME] Prevented double toggle');
            return;
        }

        isToggling = true;
        setTimeout(function () { isToggling = false; }, 100);

        console.log('[THEME] TOGGLE CLICKED!');

        // Toggle the class
        var wasDark = document.body.classList.contains('dark-mode');
        console.log('[THEME] Was dark before toggle:', wasDark);

        document.body.classList.toggle('dark-mode');

        var isDark = document.body.classList.contains('dark-mode');
        console.log('[THEME] Is dark after toggle:', isDark);

        // Save to localStorage
        var value = isDark ? 'dark' : 'light';
        localStorage.setItem('theme', value);
        console.log('[THEME] SAVED to localStorage:', value);

        // Verify save
        var verify = localStorage.getItem('theme');
        console.log('[THEME] Verification read:', verify);

        // Sync toggles
        var toggles = document.querySelectorAll('input[type="checkbox"][id*="darkMode"]');
        for (var i = 0; i < toggles.length; i++) {
            toggles[i].checked = isDark;
        }
    };

    console.log('[THEME] Function defined');

    // Restore theme - MUST wait for body to exist
    function restoreTheme() {
        var saved = localStorage.getItem('theme');
        console.log('[THEME] Restored theme:', saved);

        if (saved === 'dark') {
            document.body.classList.add('dark-mode');
        } else if (saved === 'light') {
            document.body.classList.remove('dark-mode');
        }

        // Sync toggles
        var isDark = document.body.classList.contains('dark-mode');
        var toggles = document.querySelectorAll('input[type="checkbox"][id*="darkMode"]');
        for (var i = 0; i < toggles.length; i++) {
            toggles[i].checked = isDark;
        }
    }

    // Wait for DOM to be ready before accessing body
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', restoreTheme);
    } else {
        // DOM already loaded
        restoreTheme();
    }

    console.log('[THEME] Script ready');
})();
