<?php
// admin/sidebar.php

$userId = $_SESSION['user_id'] ?? 1;

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
    <!-- Cabeçalho Sidebar com Logo -->
    <div style="padding: 24px 20px; display: flex; align-items: center; justify-content: space-between;">
        <div class="logo-area" style="font-weight: 800; color: #1e293b; font-size: 1.1rem; display: flex; align-items: center; gap: 12px;">
            <!-- Logo Imagem -->
            <img src="../assets/img/logo_pib_black.png" alt="PIB Oliveira" style="height: 40px; width: auto; object-fit: contain;">

            <div style="display: flex; flex-direction: column; line-height: 1.1;">
                <span class="sidebar-text" style="color:#047857;">PIB Oliveira</span>
                <span class="sidebar-text" style="font-size: 0.75rem; color: #64748b; font-weight: 600;">App Louvor</span>
            </div>
        </div>

        <button onclick="toggleSidebarDesktop()" class="btn-toggle-desktop ripple" title="Recolher Menu">
            <i data-lucide="panel-left-close"></i>
        </button>
    </div>

    <!-- Botão de Recolher (Desktop) -->
    <div class="sidebar-collapser" onclick="toggleSidebarDesktop()">
        <i data-lucide="chevron-left"></i>
    </div>

    <!-- 2. Menu -->
    <nav class="sidebar-nav">
        <?php if (isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin'): ?>
            <a href="lider.php" class="nav-item <?= basename($_SERVER['PHP_SELF']) == 'lider.php' ? 'active' : '' ?>" style="color: #b45309; background: #fffbeb;">
                <i data-lucide="crown" style="color: #f59e0b;"></i> <span class="sidebar-text" style="color: #b45309;">Líder</span>
            </a>
            <div class="nav-divider"></div>
        <?php endif; ?>

        <a href="index.php" class="nav-item nav-primary <?= basename($_SERVER['PHP_SELF']) == 'index.php' ? 'active' : '' ?>">
            <i data-lucide="layout-dashboard"></i> <span class="sidebar-text">Visão Geral</span>
        </a>

        <div class="nav-divider"></div>
        <div class="sidebar-text" style="padding: 0 12px 4px 12px; font-size: 0.75rem; color: #94a3b8; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px;">Gestão de Ensaios</div>

        <a href="escalas.php" class="nav-item nav-emerald <?= basename($_SERVER['PHP_SELF']) == 'escalas.php' ? 'active' : '' ?>">
            <i data-lucide="calendar-days"></i> <span class="sidebar-text">Escalas</span>
        </a>
        <a href="repertorio.php" class="nav-item nav-emerald <?= basename($_SERVER['PHP_SELF']) == 'repertorio.php' ? 'active' : '' ?>">
            <i data-lucide="music-2"></i> <span class="sidebar-text">Repertório</span>
        </a>
        <a href="membros.php" class="nav-item nav-emerald <?= basename($_SERVER['PHP_SELF']) == 'membros.php' ? 'active' : '' ?>">
            <i data-lucide="users"></i> <span class="sidebar-text">Membros</span>
        </a>
        <a href="indisponibilidade.php" class="nav-item nav-emerald <?= basename($_SERVER['PHP_SELF']) == 'indisponibilidade.php' ? 'active' : '' ?>">
            <i data-lucide="calendar-off"></i> <span class="sidebar-text">Ausências de Escala</span>
        </a>

        <div class="nav-divider"></div>
        <div class="sidebar-text" style="padding: 0 12px 4px 12px; font-size: 0.75rem; color: #94a3b8; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px;">Espiritual</div>

        <a href="devocionais.php" class="nav-item nav-slate <?= basename($_SERVER['PHP_SELF']) == 'devocionais.php' ? 'active' : '' ?>">
            <i data-lucide="book-heart"></i> <span class="sidebar-text">Devocional</span>
        </a>
        <a href="oracao.php" class="nav-item nav-slate <?= basename($_SERVER['PHP_SELF']) == 'oracao.php' ? 'active' : '' ?>">
            <i data-lucide="heart-handshake"></i> <span class="sidebar-text">Oração</span>
        </a>
        <a href="leitura.php" class="nav-item nav-slate <?= basename($_SERVER['PHP_SELF']) == 'leitura.php' ? 'active' : '' ?>">
            <i data-lucide="book-open"></i> <span class="sidebar-text">Leitura Bíblica</span>
        </a>

        <div class="nav-divider"></div>
        <div class="sidebar-text" style="padding: 0 12px 4px 12px; font-size: 0.75rem; color: #94a3b8; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px;">Comunicação</div>

        <a href="avisos.php" class="nav-item nav-slate <?= basename($_SERVER['PHP_SELF']) == 'avisos.php' ? 'active' : '' ?>">
            <i data-lucide="bell"></i> <span class="sidebar-text">Avisos</span>
        </a>
        <a href="aniversarios.php" class="nav-item nav-slate <?= basename($_SERVER['PHP_SELF']) == 'aniversarios.php' ? 'active' : '' ?>">
            <i data-lucide="cake"></i> <span class="sidebar-text">Aniversários</span>
        </a>
    </nav>

    <!-- 3. Rodapé Integrado -->
    <div class="sidebar-footer">
        <div class="user-profile-integrated">
            <!-- Avatar & Info -->
            <a href="perfil.php" class="profile-link" title="Meu Perfil">
                <div class="avatar-wrapper">
                    <img src="<?= htmlspecialchars($userPhoto) ?>" alt="Foto" class="user-avatar-compact">
                    <span class="status-indicator"></span>
                </div>
                <div class="user-info-row">
                    <div class="u-name"><?= htmlspecialchars($currentUser['name']) ?></div>
                    <div class="u-role">Ver Perfil</div>
                </div>
            </a>

            <!-- Ações Rápidas -->
            <div class="actions-row">
                <a href="configuracoes.php" class="action-icon-subtle" title="Configurações">
                    <i data-lucide="settings-2"></i>
                </a>
                <a href="../logout.php" class="action-icon-subtle danger" title="Sair">
                    <i data-lucide="log-out"></i>
                </a>
            </div>
        </div>
    </div>
</div>

<style>
    /* VARIÁVEIS LOCAIS (Compatíveis com Modo Moderate) */
    :root {
        --sidebar-width: 280px;
        --sidebar-collapsed-width: 88px;
        --sidebar-bg: #ffffff;
        --sidebar-text: #475569;
        --brand-primary: #047857;
        /* Emerald 700 */
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
    }

    /* Navegação Item */
    .nav-item {
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 12px 16px;
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

    .nav-item:hover {
        background-color: var(--brand-hover);
        color: var(--brand-primary);
    }

    .nav-item.active {
        background-color: var(--brand-light);
        color: var(--brand-primary);
        font-weight: 600;
    }

    .nav-item i {
        width: 24px;
        height: 24px;
        color: #94a3b8;
        /* Slate 400 */
    }

    .nav-item.active i {
        color: var(--brand-primary);
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
            width: 85%;
            /* Largura mais confortável no mobile */
            max-width: 320px;
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
        if (content) content.style.marginLeft = isCollapsed ? '88px' : '280px';
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
                if (content) content.style.marginLeft = '280px';
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