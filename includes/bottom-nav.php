<?php
// includes/bottom-nav.php
?>
<!-- Bottom Navigation (Mobile Only — oculta em desktop via CSS) -->
<nav class="bottom-nav" id="bottom-nav" role="navigation" aria-label="Menu principal">
    <div class="bottom-nav-items">
        <!-- Início -->
        <a href="<?= (strpos($_SERVER['PHP_SELF'], '/admin/') !== false) ? '' : '../admin/' ?>index.php"
           class="bottom-nav-item <?= basename($_SERVER['PHP_SELF']) === 'index.php' ? 'active' : '' ?>">
            <div class="bnav-icon-wrap">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m3 9 9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>
            </div>
            <span class="bnav-label">Início</span>
        </a>

        <!-- Escalas -->
        <a href="<?= (strpos($_SERVER['PHP_SELF'], '/admin/') !== false) ? '' : '../admin/' ?>escalas.php"
           class="bottom-nav-item <?= basename($_SERVER['PHP_SELF']) === 'escalas.php' ? 'active' : '' ?>">
            <div class="bnav-icon-wrap">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
            </div>
            <span class="bnav-label">Escalas</span>
        </a>

        <!-- Repertório -->
        <a href="<?= (strpos($_SERVER['PHP_SELF'], '/admin/') !== false) ? '' : '../admin/' ?>repertorio.php"
           class="bottom-nav-item <?= basename($_SERVER['PHP_SELF']) === 'repertorio.php' ? 'active' : '' ?>">
            <div class="bnav-icon-wrap">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 18V5l12-2v13"/><circle cx="6" cy="18" r="3"/><circle cx="18" cy="16" r="3"/></svg>
            </div>
            <span class="bnav-label">Repertório</span>
        </a>

        <!-- Leitura -->
        <a href="<?= (strpos($_SERVER['PHP_SELF'], '/admin/') !== false) ? '' : '../admin/' ?>leitura.php"
           class="bottom-nav-item <?= basename($_SERVER['PHP_SELF']) === 'leitura.php' ? 'active' : '' ?>">
            <div class="bnav-icon-wrap">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M2 3h6a4 4 0 0 1 4 4v14a3 3 0 0 0-3-3H2z"/><path d="M22 3h-6a4 4 0 0 0-4 4v14a3 3 0 0 1 3-3h7z"/></svg>
            </div>
            <span class="bnav-label">Leitura</span>
        </a>

        <!-- Menu (abre sidebar) -->
        <button class="bottom-nav-item bnav-menu-btn" onclick="toggleSidebarMobile ? toggleSidebarMobile() : (window.toggleSidebar && window.toggleSidebar())" aria-label="Menu">
            <div class="bnav-icon-wrap">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="18" x2="21" y2="18"/></svg>
            </div>
            <span class="bnav-label">Menu</span>
        </button>
    </div>
</nav>
