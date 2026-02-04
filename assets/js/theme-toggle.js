/**
 * Dark Mode Toggle - ULTRA SIMPLIFIED
 * No complexity, just save and restore
 */

(function () {
    'use strict';

    console.log('[THEME] Script loaded');

    // Restore theme immediately
    var saved = localStorage.getItem('theme');
    console.log('[THEME] Restored theme:', saved);

    if (saved === 'dark') {
        document.body.classList.add('dark-mode');
    } else if (saved === 'light') {
        document.body.classList.remove('dark-mode');
    }

    // Global toggle function
    window.toggleThemeMode = function () {
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

    console.log('[THEME] Function ready');
})();
