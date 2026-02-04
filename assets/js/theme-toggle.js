/**
 * Dark Mode Toggle - DIAGNOSTIC VERSION
 * Extra logging to debug localStorage issues
 */

(function () {
    'use strict';

    console.log('[THEME] ========== SCRIPT START ==========');
    console.log('[THEME] Script loaded at:', new Date().toISOString());
    console.log('[THEME] Document ready state:', document.readyState);

    // Test localStorage availability
    try {
        localStorage.setItem('test', 'test');
        localStorage.removeItem('test');
        console.log('[THEME] localStorage is AVAILABLE');
    } catch (e) {
        console.error('[THEME] localStorage is NOT AVAILABLE:', e);
    }

    // Restore dark mode from localStorage IMMEDIATELY
    var savedTheme = localStorage.getItem('theme');
    console.log('[THEME] Saved theme from localStorage:', savedTheme);
    console.log('[THEME] Type of savedTheme:', typeof savedTheme);

    if (savedTheme === 'dark') {
        document.body.classList.add('dark-mode');
        console.log('[THEME] ✅ Dark mode RESTORED from localStorage');
    } else if (savedTheme === 'light') {
        document.body.classList.remove('dark-mode');
        console.log('[THEME] ✅ Light mode RESTORED from localStorage');
    } else {
        console.log('[THEME] ⚠️ No saved theme, using default');
    }

    // Helper function to sync all toggles
    function syncAllToggles(isDark) {
        console.log('[THEME] Syncing toggles to:', isDark);
        var toggles = document.querySelectorAll('#darkModeToggle, #darkModeToggleMobile, #darkModeToggleDropdown');
        console.log('[THEME] Found', toggles.length, 'toggles');
        for (var i = 0; i < toggles.length; i++) {
            if (toggles[i] && toggles[i].type === 'checkbox') {
                toggles[i].checked = isDark;
            }
        }
    }

    // Define function globally
    window.toggleThemeMode = function (e) {
        console.log('[THEME] ========== TOGGLE CALLED ==========');
        console.log('[THEME] Event:', e);

        if (e) {
            console.log('[THEME] Preventing default');
            e.preventDefault();
        }

        console.log('[THEME] Before toggle - body classes:', document.body.className);
        document.body.classList.toggle('dark-mode');
        console.log('[THEME] After toggle - body classes:', document.body.className);

        var isDark = document.body.classList.contains('dark-mode');
        console.log('[THEME] isDark:', isDark);

        var themeValue = isDark ? 'dark' : 'light';
        console.log('[THEME] Will save to localStorage:', themeValue);

        try {
            localStorage.setItem('theme', themeValue);
            console.log('[THEME] ✅ SAVED to localStorage successfully');

            // Verify it was saved
            var verification = localStorage.getItem('theme');
            console.log('[THEME] Verification - read back from localStorage:', verification);

            if (verification !== themeValue) {
                console.error('[THEME] ❌ VERIFICATION FAILED! Expected:', themeValue, 'Got:', verification);
            }
        } catch (err) {
            console.error('[THEME] ❌ localStorage.setItem FAILED:', err);
        }

        // Sync all toggles
        syncAllToggles(isDark);

        console.log('[THEME] ========== TOGGLE COMPLETE ==========');
    };

    console.log('[THEME] toggleThemeMode function defined:', typeof window.toggleThemeMode);
    console.log('[THEME] Function reference:', window.toggleThemeMode);

    // Initialize toggles on load
    if (document.readyState === 'loading') {
        console.log('[THEME] Waiting for DOMContentLoaded...');
        document.addEventListener('DOMContentLoaded', function () {
            console.log('[THEME] DOMContentLoaded fired');
            var isDark = document.body.classList.contains('dark-mode');
            syncAllToggles(isDark);
        });
    } else {
        console.log('[THEME] DOM already loaded, syncing now');
        var isDark = document.body.classList.contains('dark-mode');
        syncAllToggles(isDark);
    }

    console.log('[THEME] ========== SCRIPT END ==========');
})();
