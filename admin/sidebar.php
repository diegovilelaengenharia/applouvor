<?php
// admin/sidebar.php

$userId = $_SESSION['user_id'] ?? 1;

// Determine base path for links
$isAdminDir = strpos($_SERVER['PHP_SELF'], '/admin/') !== false;
$baseAdmin = $isAdminDir ? '' : '../admin/';
$baseApp = $isAdminDir ? '../app/' : '';

try {
    // Tenta buscar foto também
    $stmtUser = $pdo->prepare("SELECT name, phone, avatar FROM users WHERE id = ?");
    $stmtUser->execute([$userId]);
    $currentUser = $stmtUser->fetch(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    // Fallback se a coluna avatar não existir ainda
    try {
        $stmtUser = $pdo->prepare("SELECT name, phone FROM users WHERE id = ?");
        $stmtUser->execute([$userId]);
        $currentUser = $stmtUser->fetch(PDO::FETCH_ASSOC);
    } catch (Exception $ex) {
        $currentUser = null;
    }
}

if (!$currentUser) {
    $currentUser = ['name' => 'Usuário', 'phone' => '', 'avatar' => null];
}
if (!$currentUser['phone']) $currentUser['phone'] = 'Membro da Equipe';

// Avatar Logic
if (!empty($currentUser['avatar'])) {
    // Verifica se é path relativo ou url completa
    $userPhoto = $currentUser['avatar'];
    // Ajuste simples para path relativo se necessário (ex: ../uploads/...)
    if (strpos($userPhoto, 'http') === false && strpos($userPhoto, 'assets') === false && strpos($userPhoto, 'uploads') === false) {
        $userPhoto = '../assets/uploads/' . $userPhoto; // Path correto para uploads
    }
} else {
    $userPhoto = 'https://ui-avatars.com/api/?name=' . urlencode($currentUser['name']) . '&background=dcfce7&color=166534';
}
?>

<div id="app-sidebar" class="sidebar">
    <!-- Cabeçalho Sidebar com Logo (Clicável para Recolher) -->
    <div onclick="toggleSidebarDesktop()" style="padding: 18px 16px; display: flex; align-items: center; justify-content: space-between; cursor: pointer;" title="Expandir/Recolher Menu">
        <div class="logo-area" style="font-weight: 800; color: #1e293b; font-size: 1.1rem; display: flex; align-items: center; gap: 12px;">
            <!-- Logo Imagem -->
            <img src="../assets/img/logo_pib_black.png" alt="PIB Oliveira" style="height: 40px; width: auto; object-fit: contain;">

            <div style="display: flex; flex-direction: column; line-height: 1.1;">
                <span class="sidebar-text" style="color:#059669; font-size: 0.95rem;">PIB Oliveira</span>
                <span class="sidebar-text" style="font-size: 0.7rem; color: #64748b; font-weight: 600;">App Louvor</span>
            </div>
        </div>
    </div>

    <!-- 2. Menu -->
    <nav class="sidebar-nav">
        <!-- (Botão líder removido da sidebar e movido para header) -->

        <a href="<?= $baseAdmin ?>index.php" class="nav-item nav-primary <?= basename($_SERVER['PHP_SELF']) == 'index.php' ? 'active' : '' ?>">
            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <rect width="7" height="9" x="3" y="3" rx="1" />
                <rect width="7" height="5" x="14" y="3" rx="1" />
                <rect width="7" height="9" x="14" y="12" rx="1" />
                <rect width="7" height="5" x="3" y="16" rx="1" />
            </svg>
            <span class="sidebar-text">Visão Geral</span>
        </a>

        <div class="nav-divider"></div>
        <div class="sidebar-text" style="padding: 0 12px 4px 12px; font-size: 0.75rem; color: #94a3b8; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px;">Gestão de Ensaios</div>

        <a href="<?= $baseAdmin ?>escalas.php" class="nav-item nav-emerald <?= basename($_SERVER['PHP_SELF']) == 'escalas.php' ? 'active' : '' ?>">
            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M8 2v4" />
                <path d="M16 2v4" />
                <rect width="18" height="18" x="3" y="4" rx="2" />
                <path d="M3 10h18" />
                <path d="M8 14h.01" />
                <path d="M12 14h.01" />
                <path d="M16 14h.01" />
                <path d="M8 18h.01" />
                <path d="M12 18h.01" />
                <path d="M16 18h.01" />
            </svg>
            <span class="sidebar-text">Escalas</span>
        </a>
        <a href="<?= $baseAdmin ?>repertorio.php" class="nav-item nav-violet <?= basename($_SERVER['PHP_SELF']) == 'repertorio.php' ? 'active' : '' ?>">
            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <circle cx="8" cy="18" r="4" />
                <path d="M12 18V2l7 4" />
            </svg>
            <span class="sidebar-text">Repertório</span>
        </a>

        <a href="<?= $baseAdmin ?>historico.php" class="nav-item nav-violet <?= basename($_SERVER['PHP_SELF']) == 'historico.php' ? 'active' : '' ?>">
            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M3 3v5h5"/>
                <path d="M3.05 13A9 9 0 1 0 6 5.3L3 8"/>
                <path d="M12 7v5l4 2"/>
            </svg>
            <span class="sidebar-text">Histórico</span>
        </a>

        <a href="<?= $baseAdmin ?>membros.php" class="nav-item nav-blue <?= basename($_SERVER['PHP_SELF']) == 'membros.php' ? 'active' : '' ?>">
            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2" />
                <circle cx="9" cy="7" r="4" />
                <path d="M22 21v-2a4 4 0 0 0-3-3.87" />
                <path d="M16 3.13a4 4 0 0 1 0 7.75" />
            </svg>
            <span class="sidebar-text">Membros</span>
        </a>

        <a href="<?= $baseAdmin ?>indisponibilidade.php" class="nav-item nav-emerald <?= basename($_SERVER['PHP_SELF']) == 'indisponibilidade.php' ? 'active' : '' ?>">
            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M8 2v4" />
                <path d="M16 2v4" />
                <rect width="18" height="18" x="3" y="4" rx="2" />
                <path d="M3 10h18" />
                <path d="m2 2 20 20" />
            </svg>
            <span class="sidebar-text">Ausências</span>
        </a>

        <a href="<?= $baseAdmin ?>agenda.php" class="nav-item nav-emerald <?= basename($_SERVER['PHP_SELF']) == 'agenda.php' ? 'active' : '' ?>">
            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M8 2v4" />
                <path d="M16 2v4" />
                <rect width="18" height="18" x="3" y="4" rx="2" />
                <path d="M3 10h18" />
                <path d="M10 16h4" />
            </svg>
            <span class="sidebar-text">Agenda</span>
        </a>

        <div class="nav-divider"></div>
        <div class="sidebar-text" style="padding: 0 12px 4px 12px; font-size: 0.75rem; color: #94a3b8; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px;">Espiritual</div>

        <a href="<?= $baseAdmin ?>devocionais.php" class="nav-item nav-indigo <?= basename($_SERVER['PHP_SELF']) == 'devocionais.php' ? 'active' : '' ?>">
            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M4 19.5v-15A2.5 2.5 0 0 1 6.5 2H20v20H6.5a2.5 2.5 0 0 1 0-5H20" />
                <path d="M12 8a2 2 0 1 1-2.2 1.8" />
                <path d="M15 13a2.5 2.5 0 1 1-2.5-2.5" />
            </svg>
            <span class="sidebar-text">Devocional</span>
        </a>
        <a href="<?= $baseAdmin ?>oracao.php" class="nav-item nav-rose <?= basename($_SERVER['PHP_SELF']) == 'oracao.php' ? 'active' : '' ?>">
            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M19 14c1.49-1.46 3-3.21 3-5.5A5.5 5.5 0 0 0 16.5 3c-1.76 0-3 .5-4.5 2-1.5-1.5-2.74-2-4.5-2A5.5 5.5 0 0 0 2 8.5c0 2.3 1.5 4.05 3 5.5l7 7Z" />
            </svg>
            <span class="sidebar-text">Oração</span>
        </a>
        <a href="<?= $baseAdmin ?>leitura.php" class="nav-item nav-cyan <?= basename($_SERVER['PHP_SELF']) == 'leitura.php' ? 'active' : '' ?>">
            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M2 3h6a4 4 0 0 1 4 4v14a3 3 0 0 0-3-3H2z" />
                <path d="M22 3h-6a4 4 0 0 0-4 4v14a3 3 0 0 1 3-3h7z" />
            </svg>
            <span class="sidebar-text">Leitura Bíblica</span>
        </a>

        <div class="nav-divider"></div>
        <div class="sidebar-text" style="padding: 0 12px 4px 12px; font-size: 0.75rem; color: #94a3b8; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px;">Comunicação</div>

        <a href="<?= $baseAdmin ?>avisos.php" class="nav-item nav-amber <?= basename($_SERVER['PHP_SELF']) == 'avisos.php' ? 'active' : '' ?>">
            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M6 8a6 6 0 0 1 12 0c0 7 3 9 3 9H3s3-2 3-9" />
                <path d="M10.3 21a1.94 1.94 0 0 0 3.4 0" />
            </svg>
            <span class="sidebar-text">Avisos</span>
        </a>



    </nav>

    <!-- 3. Rodapé Integrado -->
    <!-- 3. Rodapé Integrado (REMOVIDO REQUISIÇÃO USUARIO) -->
    <!-- Perfil movido para o Header Superior -->


    <!-- Créditos do Desenvolvedor -->
    <div class="sidebar-credits" style="margin-top: auto; padding: 20px; text-align: center; font-size: 0.65rem; color: #94a3b8; border-top: 1px solid #f1f5f9;">
        <span class="sidebar-text" style="display: block; margin-bottom: 2px;">Desenvolvido por</span>
        <span class="sidebar-text" style="font-weight: 600; color: #64748b; font-size: 0.7rem;">Diego T. N. Vilela</span>
    </div>
</div>

<style>
    /* VARIÁVEIS LOCAIS (Compatíveis com Modo Moderate) */
    :root {
        --sidebar-width: 190px;
        --sidebar-collapsed-width: 88px;
        --sidebar-bg: #ffffff;
        --sidebar-text: #475569;
        --brand-primary: #059669;
        /* Emerald 600 - Vibrante */
        --brand-light: #d1fae5;
        /* Emerald 100 */
        --brand-hover: #ecfdf5;
        /* Emerald 50 */
    }

    /* Sidebar Layout */
    .sidebar {
        position: fixed;
        left: 0;
        top: 0;
        bottom: 0;
        width: var(--sidebar-width);
        background: var(--sidebar-bg);
        border-right: 1px solid #e2e8f0;
        display: flex;
        flex-direction: column;
        z-index: 1000;
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        overflow-y: auto;
        overflow-x: hidden;
    }

    /* Invisible Scrollbar */
    .sidebar::-webkit-scrollbar {
        width: 6px;
    }

    .sidebar::-webkit-scrollbar-track {
        background: transparent;
    }

    .sidebar::-webkit-scrollbar-thumb {
        background: transparent;
        border-radius: 3px;
    }

    .sidebar:hover::-webkit-scrollbar-thumb {
        background: rgba(0, 0, 0, 0.1);
    }

    /* Firefox */
    .sidebar {
        scrollbar-width: thin;
        scrollbar-color: transparent transparent;
    }

    .sidebar:hover {
        scrollbar-color: rgba(0, 0, 0, 0.1) transparent;
    }

    /* Navegação Item */
    .nav-item {
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 10px 12px;
        /* Touch target melhor */
        border-radius: 8px;
        color: var(--sidebar-text);
        text-decoration: none;
        font-weight: 500;
        font-size: 0.9375rem;
        /* 15px */
        transition: all 0.2s;
        margin-bottom: 2px;
    }

    /* Colors Classes restored */
    .nav-item.nav-primary:hover,
    .nav-item.nav-primary.active {
        background-color: #ecfdf5;
        color: #059669;
    }

    .nav-item.nav-primary.active svg {
        color: #059669;
    }

    /* GESTÃO → AZUL */
    .nav-item.nav-emerald svg, .nav-item.nav-blue svg, .nav-item.nav-violet svg { color: #3b82f6; }
    .nav-item.nav-emerald:hover, .nav-item.nav-emerald.active,
    .nav-item.nav-blue:hover, .nav-item.nav-blue.active,
    .nav-item.nav-violet:hover, .nav-item.nav-violet.active { background-color: #eff6ff; color: #2563eb; }
    .nav-item.nav-emerald:hover svg, .nav-item.nav-emerald.active svg,
    .nav-item.nav-blue:hover svg, .nav-item.nav-blue.active svg,
    .nav-item.nav-violet:hover svg, .nav-item.nav-violet.active svg { color: #2563eb; }

    /* ESPIRITUAL → VERDE VIBRANTE */
    .nav-item.nav-indigo svg, .nav-item.nav-rose svg, .nav-item.nav-cyan svg { color: #22c55e; }
    .nav-item.nav-indigo:hover, .nav-item.nav-indigo.active,
    .nav-item.nav-rose:hover, .nav-item.nav-rose.active,
    .nav-item.nav-cyan:hover, .nav-item.nav-cyan.active { background-color: #f0fdf4; color: #16a34a; }
    .nav-item.nav-indigo:hover svg, .nav-item.nav-indigo.active svg,
    .nav-item.nav-rose:hover svg, .nav-item.nav-rose.active svg,
    .nav-item.nav-cyan:hover svg, .nav-item.nav-cyan.active svg { color: #16a34a; }

    /* COMUNICAÇÃO → ROXO VIBRANTE */
    .nav-item.nav-amber svg, .nav-item.nav-pink svg { color: #a855f7; }
    .nav-item.nav-amber:hover, .nav-item.nav-amber.active,
    .nav-item.nav-pink:hover, .nav-item.nav-pink.active { background-color: #faf5ff; color: #9333ea; }
    .nav-item.nav-amber:hover svg, .nav-item.nav-amber.active svg,
    .nav-item.nav-pink:hover svg, .nav-item.nav-pink.active svg { color: #9333ea; }

    /* Indicadores (Blue) */
    .nav-item.nav-indicators svg {
        color: #3b82f6;
    }

    .nav-item.nav-indicators:hover,
    .nav-item.nav-indicators.active {
        background-color: #eff6ff;
        color: #1e40af;
    }

    .nav-item.nav-indicators:hover svg,
    .nav-item.nav-indicators.active svg {
        color: #1e40af;
    }


    .nav-item.active {
        font-weight: 700;
        box-shadow: 0 1px 2px rgba(0, 0, 0, 0.03);
    }

    .nav-item svg {
        width: 22px;
        height: 22px;
        transition: all 0.2s;
        /* Default fallback */
        color: #94a3b8;
    }


    .nav-divider {
        height: 1px;
        background: #e2e8f0;
        margin: 8px 16px;
    }

    /* CSS Extra para o Footer Integrado */
    .sidebar-footer {
        padding: 16px;
        border-top: 1px solid #e2e8f0;
        background: #f8fafc;
        /* Slate 50 */
    }

    .user-profile-integrated {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 8px;
    }

    .profile-link {
        display: flex;
        align-items: center;
        gap: 12px;
        text-decoration: none;
        flex: 1;
        min-width: 0;
        padding: 8px;
        border-radius: 8px;
        transition: background 0.2s;
    }

    .profile-link:hover {
        background: #e2e8f0;
    }

    .user-avatar-compact {
        width: 40px;
        height: 40px;
        border-radius: 10px;
        object-fit: cover;
        border: 2px solid #fff;
        box-shadow: 0 1px 2px rgba(0, 0, 0, 0.1);
    }

    .status-indicator {
        position: absolute;
        bottom: -2px;
        right: -2px;
        width: 12px;
        height: 12px;
        background: #22c55e;
        border: 2px solid #fff;
        border-radius: 50%;
    }

    .user-info-row {
        flex: 1;
        min-width: 0;
        display: flex;
        flex-direction: column;
        justify-content: center;
    }

    .u-name {
        font-size: 0.9375rem;
        /* 15px */
        font-weight: 600;
        color: #334155;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .u-role {
        font-size: 0.75rem;
        color: #64748b;
    }

    .actions-row {
        display: flex;
        align-items: center;
    }

    .action-icon-subtle {
        width: 40px;
        height: 40px;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 8px;
        color: #94a3b8;
        transition: all 0.2s;
    }

    .action-icon-subtle:hover {
        background: #fff;
        color: #0f172a;
        box-shadow: 0 1px 2px rgba(0, 0, 0, 0.05);
    }

    .action-icon-subtle.danger:hover {
        color: #ef4444;
        background: #fff;
    }

    /* MODO RECOLHIDO (Desktop) */
    .sidebar.collapsed {
        width: var(--sidebar-collapsed-width);
    }

    .sidebar.collapsed .sidebar-text,
    .sidebar.collapsed .u-name,
    .sidebar.collapsed .u-role,
    .sidebar.collapsed .user-info-row {
        display: none;
    }

    .sidebar.collapsed .nav-item {
        justify-content: center;
        padding: 12px;
    }

    .sidebar.collapsed .nav-item i {
        margin: 0;
    }

    .sidebar.collapsed .logo-area {
        justify-content: center;
    }

    .sidebar.collapsed .logo-area img {
        height: 32px;
    }

    .sidebar.collapsed .profile-link {
        justify-content: center;
        padding: 0;
    }

    .sidebar.collapsed .user-profile-integrated {
        flex-direction: column;
        gap: 16px;
    }

    .sidebar.collapsed .actions-row {
        flex-direction: column;
        width: 100%;
    }

    /* MOBILE OVERRIDES */
    @media (max-width: 1024px) {
        .sidebar {
            transform: translateX(-100%);
            width: 200px;
            /* Largura Ultra Compacta via USER FEEDBACK */
            max-width: 80%;
        }

        .sidebar.open {
            transform: translateX(0);
            box-shadow: 4px 0 24px rgba(0, 0, 0, 0.15);
        }

        .sidebar-collapser,
        .btn-toggle-desktop {
            display: none;
        }
    }

    /* Overlay */
    .sidebar-overlay {
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: rgba(15, 23, 42, 0.4);
        /* Slate 900 com opacidade */
        backdrop-filter: blur(2px);
        z-index: 999;
        opacity: 0;
        visibility: hidden;
        transition: all 0.3s;
    }

    .sidebar-overlay.active {
        opacity: 1;
        visibility: visible;
    }
</style>

<!-- Overlay para Fechar no Mobile -->
<div id="sidebar-overlay" class="sidebar-overlay" onclick="toggleSidebarMobile()"></div>

<script>
    const sidebar = document.getElementById('app-sidebar');
    const content = document.getElementById('app-content');

    // --- State Management ---
    function isDesktop() {
        return window.innerWidth > 1024;
    }

    function toggleSidebarDesktop() {
        if (!isDesktop()) return;
        sidebar.classList.toggle('collapsed');
        const isCollapsed = sidebar.classList.contains('collapsed');
        if (content) content.style.marginLeft = isCollapsed ? '88px' : '190px';
        localStorage.setItem('sidebarCollapsed', isCollapsed);
    }

    function toggleSidebarMobile() {
        sidebar.classList.toggle('open');
        document.getElementById('sidebar-overlay').classList.toggle('active');
    }

    // --- Init ---
    document.addEventListener('DOMContentLoaded', () => {
        if (isDesktop()) {
            const savedCollapsed = localStorage.getItem('sidebarCollapsed') === 'true';
            if (savedCollapsed) {
                sidebar.classList.add('collapsed');
                if (content) content.style.marginLeft = '88px';
            } else {
                sidebar.classList.remove('collapsed');
                if (content) content.style.marginLeft = '190px';
            }
        }

        // --- SWIPE LOGIC FIXED (PREVENT BACK) ---
        let touchStartX = 0;
        let touchEndX = 0;
        const widthTrigger = 35; // Pixels da borda

        document.addEventListener('touchstart', (e) => {
            if (isDesktop()) return;
            touchStartX = e.touches[0].clientX;
        }, {
            passive: false
        });

        document.addEventListener('touchmove', (e) => {
            if (isDesktop()) return;
            const currentX = e.touches[0].clientX;

            // Se o toque começou na zona de SWIPE e está movendo para a direita...
            if (touchStartX < widthTrigger && currentX > touchStartX) {
                // ...BLOQUEIA o comportamento padrão (que seria "Voltar" no navegador)
                e.preventDefault();
            }
        }, {
            passive: false
        });

        document.addEventListener('touchend', (e) => {
            if (isDesktop()) return;
            touchEndX = e.changedTouches[0].clientX;
            handleSwipe();
        }, {
            passive: true
        });

        function handleSwipe() {
            const threshold = 60; // Sensibilidade
            // Swipe Right -> Abrir
            if (touchStartX < widthTrigger && touchEndX > touchStartX + threshold) {
                if (!sidebar.classList.contains('open')) toggleSidebarMobile();
            }
            // Swipe Left -> Fechar
            if (touchEndX < touchStartX - threshold) {
                if (sidebar.classList.contains('open')) toggleSidebarMobile();
            }
        }
    });

    // Torna global
    window.toggleSidebar = toggleSidebarMobile;
</script>