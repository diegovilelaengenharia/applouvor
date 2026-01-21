<?php
// admin/sidebar.php

$userId = $_SESSION['user_id'] ?? 1;

try {
    $stmtUser = $pdo->prepare("SELECT name, phone FROM users WHERE id = ?");
    $stmtUser->execute([$userId]);
    $currentUser = $stmtUser->fetch(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $currentUser = null;
}

if (!$currentUser) {
    $currentUser = ['name' => 'Usuário', 'phone' => ''];
}
if (!$currentUser['phone']) $currentUser['phone'] = 'Membro da Equipe';

// Avatar
$userPhoto = 'https://ui-avatars.com/api/?name=' . urlencode($currentUser['name']) . '&background=eff6ff&color=047857';

?>

<div id="app-sidebar" class="sidebar">
    <!-- Cabeçalho Sidebar com Toggle -->
    <div style="padding: 16px; display: flex; align-items: center; justify-content: space-between;">
        <div class="logo-area" style="font-weight: 800; color: #047857; font-size: 1.2rem; display: flex; align-items: center; gap: 8px;">
            <i data-lucide="music" style="width: 24px;"></i>
            <span class="sidebar-text">App Louvor</span>
        </div>
        <button onclick="toggleSidebarDesktop()" class="btn-toggle-desktop">
            <i data-lucide="panel-left-close"></i>
        </button>
    </div>

    <!-- 1. Perfil -->
    <a href="perfil.php" class="sidebar-profile ripple">
        <img src="<?= $userPhoto ?>" alt="Foto" class="profile-img">
        <div class="profile-info sidebar-text">
            <div class="profile-name"><?= htmlspecialchars($currentUser['name']) ?></div>
            <div class="profile-meta"><?= htmlspecialchars($currentUser['phone']) ?></div>
        </div>
        <i data-lucide="chevron-right" class="profile-arrow sidebar-text" style="width: 16px;"></i>
    </a>

    <!-- 2. Menu -->
    <nav class="sidebar-nav">
        <a href="index.php" class="nav-item <?= basename($_SERVER['PHP_SELF']) == 'index.php' ? 'active' : '' ?>">
            <i data-lucide="layout-dashboard"></i> <span class="sidebar-text">Visão Geral</span>
        </a>
        <a href="escalas.php" class="nav-item <?= basename($_SERVER['PHP_SELF']) == 'escalas.php' ? 'active' : '' ?>">
            <i data-lucide="calendar-days"></i> <span class="sidebar-text">Escalas</span>
        </a>
        <a href="repertorio.php" class="nav-item <?= basename($_SERVER['PHP_SELF']) == 'repertorio.php' ? 'active' : '' ?>">
            <i data-lucide="music-2"></i> <span class="sidebar-text">Repertório</span>
        </a>
        <a href="membros.php" class="nav-item <?= basename($_SERVER['PHP_SELF']) == 'membros.php' ? 'active' : '' ?>">
            <i data-lucide="users"></i> <span class="sidebar-text">Membros</span>
        </a>

        <div class="nav-divider"></div>

        <a href="avisos.php" class="nav-item <?= basename($_SERVER['PHP_SELF']) == 'avisos.php' ? 'active' : '' ?>">
            <i data-lucide="bell"></i> <span class="sidebar-text">Avisos</span>
        </a>
        <a href="indisponibilidade.php" class="nav-item <?= basename($_SERVER['PHP_SELF']) == 'indisponibilidade.php' ? 'active' : '' ?>">
            <i data-lucide="calendar-off"></i> <span class="sidebar-text">Indisponibilidade</span>
        </a>
        <a href="aniversarios.php" class="nav-item <?= basename($_SERVER['PHP_SELF']) == 'aniversarios.php' ? 'active' : '' ?>">
            <i data-lucide="cake"></i> <span class="sidebar-text">Aniversários</span>
        </a>
        <a href="leitura.php" class="nav-item <?= basename($_SERVER['PHP_SELF']) == 'leitura.php' ? 'active' : '' ?>">
            <i data-lucide="book-open"></i> <span class="sidebar-text">Leitura</span>
        </a>
    </nav>

    <!-- 3. Rodapé -->
    <div class="sidebar-footer">
        <a href="configuracoes.php" class="nav-item">
            <i data-lucide="settings"></i> <span class="sidebar-text">Configurações</span>
        </a>
        <div class="nav-item" onclick="toggleThemeMode()" style="cursor: pointer;">
            <i data-lucide="moon"></i> <span class="sidebar-text">Modo Escuro</span>
        </div>
        <a href="../logout.php" class="nav-item logout-item">
            <i data-lucide="log-out"></i> <span class="sidebar-text">Sair</span>
        </a>
    </div>
</div>

<!-- Overlay Mobile -->
<div id="sidebar-overlay" class="sidebar-overlay" onclick="toggleSidebarMobile()"></div>

<style>
    /* Variáveis CLARAS */
    :root {
        --sidebar-width: 280px;
        --sidebar-collapsed-width: 80px;
        --sidebar-bg: #ffffff;
        --sidebar-text: #475569;
        --sidebar-accent-bg: #ecfdf5;
        /* Verde claro */
        --sidebar-accent-text: #047857;
        /* Verde Vilela */
        --sidebar-hover: #f1f5f9;
        --sidebar-border: #e2e8f0;
    }

    body {
        background-color: #f8fafc;
        /* Fundo do app cinza claro */
    }

    /* Sidebar Base */
    .sidebar {
        position: fixed;
        left: 0;
        top: 0;
        bottom: 0;
        width: var(--sidebar-width);
        background: var(--sidebar-bg);
        border-right: 1px solid var(--sidebar-border);
        display: flex;
        flex-direction: column;
        z-index: 1000;
        transition: width 0.3s ease;
        overflow-x: hidden;
        /* Esconde texto ao recolher */
    }

    /* Perfil */
    .sidebar-profile {
        margin: 0 16px 16px 16px;
        padding: 12px;
        background: #f8fafc;
        border-radius: 12px;
        display: flex;
        align-items: center;
        gap: 12px;
        text-decoration: none;
        border: 1px solid var(--sidebar-border);
        transition: all 0.2s;
    }

    .profile-img {
        width: 40px;
        height: 40px;
        border-radius: 50%;
    }

    .profile-info {
        flex: 1;
        min-width: 0;
    }

    .profile-name {
        color: #1e293b;
        font-weight: 700;
        font-size: 0.9rem;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .profile-meta {
        color: #64748b;
        font-size: 0.75rem;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    /* Navegação */
    .sidebar-nav {
        flex: 1;
        padding: 0 12px;
        display: flex;
        flex-direction: column;
        gap: 4px;
        overflow-y: auto;
    }

    .nav-item {
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 12px;
        border-radius: 8px;
        color: var(--sidebar-text);
        text-decoration: none;
        font-weight: 600;
        font-size: 0.9rem;
        transition: all 0.2s;
        white-space: nowrap;
    }

    .nav-item:hover {
        background: var(--sidebar-hover);
        color: #1e293b;
    }

    .nav-item.active {
        background: var(--sidebar-accent-bg);
        color: var(--sidebar-accent-text);
    }

    .nav-item i {
        width: 20px;
        height: 20px;
        flex-shrink: 0;
    }

    .nav-divider {
        height: 1px;
        background: var(--sidebar-border);
        margin: 8px 12px;
    }

    .sidebar-footer {
        padding: 16px 12px;
        border-top: 1px solid var(--sidebar-border);
    }

    .logout-item {
        color: #ef4444;
    }

    .logout-item:hover {
        background: #fef2f2;
        color: #dc2626;
    }

    /* Botão Trocar Tamanho */
    .btn-toggle-desktop {
        background: transparent;
        border: none;
        cursor: pointer;
        color: #94a3b8;
        display: none;
        /* Só aparece no desktop via JS ou media query */
    }

    @media (min-width: 1025px) {
        .btn-toggle-desktop {
            display: block;
        }
    }

    /* --- ESTADO RECOLHIDO (COLLAPSED) --- */
    .sidebar.collapsed {
        width: var(--sidebar-collapsed-width);
    }

    .sidebar.collapsed .sidebar-text {
        display: none;
    }

    .sidebar.collapsed .logo-area span {
        display: none;
    }

    .sidebar.collapsed .profile-arrow {
        display: none;
    }

    .sidebar.collapsed .sidebar-profile {
        padding: 8px;
        justify-content: center;
    }

    .sidebar.collapsed .profile-img {
        margin: 0;
    }

    .sidebar.collapsed .nav-item {
        justify-content: center;
        padding: 16px 0;
    }

    .sidebar.collapsed .nav-item i {
        margin: 0;
    }

    /* --- MOBILE --- */
    @media (max-width: 1024px) {
        .sidebar {
            transform: translateX(-100%);
            width: 280px;
        }

        /* Sempre 280px no mobile quando aberta */
        .sidebar.open {
            transform: translateX(0);
            box-shadow: 4px 0 20px rgba(0, 0, 0, 0.1);
        }

        .sidebar-overlay.active {
            opacity: 1;
            visibility: visible;
        }

        .btn-toggle-desktop {
            display: none;
        }

        /* Sem toggle no mobile */
    }

    /* --- AJUSTE DO CONTEÚDO PRINCIPAL --- */
    /* Isso deve ser aplicado ao #app-content no layout.php, mas podemos forçar aqui via JS ou CSS global se possível */
</style>

<script>
    // Gerenciamento de Estado (Salvar preferência)
    const sidebar = document.getElementById('app-sidebar');
    const content = document.getElementById('app-content');

    function isDesktop() {
        return window.innerWidth > 1024;
    }

    // Toggle Desktop (Expandir/Recolher)
    function toggleSidebarDesktop() {
        if (!isDesktop()) return;

        sidebar.classList.toggle('collapsed');
        const isCollapsed = sidebar.classList.contains('collapsed');

        // Ajustar margem do conteúdo
        if (content) {
            content.style.marginLeft = isCollapsed ? '80px' : '280px';
        }

        // Salvar estado (opcional, localStorage)
        localStorage.setItem('sidebarCollapsed', isCollapsed);
    }

    // Toggle Mobile (Abrir/Fechar Offcanvas)
    function toggleSidebarMobile() {
        sidebar.classList.toggle('open');
        document.getElementById('sidebar-overlay').classList.toggle('active');
    }

    // Inicialização
    document.addEventListener('DOMContentLoaded', () => {
        if (isDesktop()) {
            const savedCollapsed = localStorage.getItem('sidebarCollapsed') === 'true';
            if (savedCollapsed) {
                sidebar.classList.add('collapsed');
                if (content) content.style.marginLeft = '80px';
            } else {
                if (content) content.style.marginLeft = '280px';
            }
        }
    });

    // Fallback para função antiga chamada no header mobile
    window.toggleSidebar = toggleSidebarMobile;
</script>