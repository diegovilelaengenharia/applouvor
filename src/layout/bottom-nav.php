<?php
// src/layout/bottom-nav.php
?>
<!-- BottomNavBar (Mobile Only) -->
<nav class="md:hidden fixed bottom-0 left-0 w-full z-[100] flex justify-around items-center h-20 bg-surface dark:bg-deep-navy px-4 pb-safe border-t border-outline-variant dark:border-on-surface-variant shadow-sm transition-transform pb-2" id="bottom-nav" role="navigation" aria-label="Menu principal">
    
    <!-- Início -->
    <a href="<?= (strpos($_SERVER['PHP_SELF'], '/admin/') !== false) ? '' : '../admin/' ?>index.php" 
       class="flex flex-col items-center justify-center <?= basename($_SERVER['PHP_SELF']) === 'index.php' ? 'text-primary dark:text-primary-fixed font-bold' : 'text-on-surface-variant dark:text-secondary-fixed-dim hover:bg-ghost-gray dark:hover:bg-surface-variant' ?> rounded-full scale-95 active:scale-90 transition-transform p-2 w-16" style="text-decoration: none;">
        <span class="material-symbols-outlined mb-1 <?= basename($_SERVER['PHP_SELF']) === 'index.php' ? 'fill' : '' ?>">home</span>
        <span class="font-label-sm text-label-sm">Início</span>
    </a>

    <!-- Escalas -->
    <a href="<?= (strpos($_SERVER['PHP_SELF'], '/admin/') !== false) ? '' : '../admin/' ?>escalas.php" 
       class="flex flex-col items-center justify-center <?= basename($_SERVER['PHP_SELF']) === 'escalas.php' ? 'text-primary dark:text-primary-fixed font-bold' : 'text-on-surface-variant dark:text-secondary-fixed-dim hover:bg-ghost-gray dark:hover:bg-surface-variant' ?> rounded-full scale-95 active:scale-90 transition-transform p-2 w-16" style="text-decoration: none;">
        <span class="material-symbols-outlined mb-1 <?= basename($_SERVER['PHP_SELF']) === 'escalas.php' ? 'fill' : '' ?>">event_repeat</span>
        <span class="font-label-sm text-label-sm">Escalas</span>
    </a>

    <!-- Repertório -->
    <a href="<?= (strpos($_SERVER['PHP_SELF'], '/admin/') !== false) ? '' : '../admin/' ?>repertorio.php" 
       class="flex flex-col items-center justify-center <?= basename($_SERVER['PHP_SELF']) === 'repertorio.php' ? 'text-primary dark:text-primary-fixed font-bold' : 'text-on-surface-variant dark:text-secondary-fixed-dim hover:bg-ghost-gray dark:hover:bg-surface-variant' ?> rounded-full scale-95 active:scale-90 transition-transform p-2 w-16" style="text-decoration: none;">
        <span class="material-symbols-outlined mb-1 <?= basename($_SERVER['PHP_SELF']) === 'repertorio.php' ? 'fill' : '' ?>">music_note</span>
        <span class="font-label-sm text-label-sm">Repertório</span>
    </a>

    <!-- Leitura -->
    <a href="<?= (strpos($_SERVER['PHP_SELF'], '/admin/') !== false) ? '' : '../admin/' ?>leitura.php" 
       class="flex flex-col items-center justify-center <?= basename($_SERVER['PHP_SELF']) === 'leitura.php' ? 'text-primary dark:text-primary-fixed font-bold' : 'text-on-surface-variant dark:text-secondary-fixed-dim hover:bg-ghost-gray dark:hover:bg-surface-variant' ?> rounded-full scale-95 active:scale-90 transition-transform p-2 w-16" style="text-decoration: none;">
        <span class="material-symbols-outlined mb-1 <?= basename($_SERVER['PHP_SELF']) === 'leitura.php' ? 'fill' : '' ?>">menu_book</span>
        <span class="font-label-sm text-label-sm">Leitura</span>
    </a>

    <!-- Menu (abre sidebar) -->
    <button class="flex flex-col items-center justify-center text-on-surface-variant dark:text-secondary-fixed-dim hover:bg-ghost-gray dark:hover:bg-surface-variant rounded-full scale-95 active:scale-90 transition-transform p-2 w-16" onclick="toggleSidebarMobile ? toggleSidebarMobile() : (window.toggleSidebar && window.toggleSidebar())" aria-label="Menu" style="background: none; border: none;">
        <span class="material-symbols-outlined mb-1">menu</span>
        <span class="font-label-sm text-label-sm">Menu</span>
    </button>
</nav>
