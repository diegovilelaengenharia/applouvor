<?php
// admin/sidebar.php

$userId = $_SESSION['user_id'] ?? 1;

try {
    // Tenta buscar foto também
    $stmtUser = $pdo->prepare("SELECT name, phone, photo FROM users WHERE id = ?");
    $stmtUser->execute([$userId]);
    $currentUser = $stmtUser->fetch(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    // Fallback se a coluna photo não existir ainda
    try {
        $stmtUser = $pdo->prepare("SELECT name, phone FROM users WHERE id = ?");
        $stmtUser->execute([$userId]);
        $currentUser = $stmtUser->fetch(PDO::FETCH_ASSOC);
    } catch (Exception $ex) {
        $currentUser = null;
    }
}

if (!$currentUser) {
    $currentUser = ['name' => 'Usuário', 'phone' => '', 'photo' => null];
}
if (!$currentUser['phone']) $currentUser['phone'] = 'Membro da Equipe';

// Avatar Logic
if (!empty($currentUser['photo'])) {
    // Verifica se é path relativo ou url completa
    $userPhoto = $currentUser['photo'];
    // Ajuste simples para path relativo se necessário (ex: ../uploads/...)
    if (strpos($userPhoto, 'http') === false && strpos($userPhoto, 'assets') === false && strpos($userPhoto, 'uploads') === false) {
        $userPhoto = '../assets/img/' . $userPhoto; // Tentativa de path
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
                <span class="sidebar-text" style="color:#166534;">PIB Oliveira</span>
                <span class="sidebar-text" style="font-size: 0.75rem; color: #64748b; font-weight: 600;">App Louvor</span>
            </div>
        </div>

        <button onclick="toggleSidebarDesktop()" class="btn-toggle-desktop ripple">
            <i data-lucide="chevrons-left-right" style="width: 20px; height: 20px;"></i>
        </button>
    </div>

    <!-- 1. Perfil -->
    <a href="perfil.php" class="sidebar-profile ripple">
        <img src="<?= htmlspecialchars($userPhoto) ?>" alt="Foto" class="profile-img" style="object-fit: cover;">
        <div class="profile-info sidebar-text">
            <div class="profile-name"><?= htmlspecialchars($currentUser['name']) ?></div>
            <div class="profile-meta"><?= htmlspecialchars($currentUser['phone']) ?></div>
        </div>
        <i data-lucide="chevron-right" class="profile-arrow sidebar-text" style="width: 16px; color: #cbd5e1;"></i>
    </a>

    <!-- 2. Menu -->
    <nav class="sidebar-nav">
        <a href="index.php" class="nav-item <?= basename($_SERVER['PHP_SELF']) == 'index.php' ? 'active' : '' ?>">
            <i data-lucide="layout-dashboard"></i> <span class="sidebar-text">Visão Geral</span>
        </a>

        <div class="nav-divider"></div>
        <div class="sidebar-text" style="padding: 0 12px 4px 12px; font-size: 0.75rem; color: #94a3b8; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px;">Gestão de Ensaios</div>

        <a href="escalas.php" class="nav-item <?= basename($_SERVER['PHP_SELF']) == 'escalas.php' ? 'active' : '' ?>">
            <i data-lucide="calendar-days"></i> <span class="sidebar-text">Escalas</span>
        </a>
        <a href="repertorio.php" class="nav-item <?= basename($_SERVER['PHP_SELF']) == 'repertorio.php' ? 'active' : '' ?>">
            <i data-lucide="music-2"></i> <span class="sidebar-text">Repertório</span>
        </a>
        <a href="membros.php" class="nav-item <?= basename($_SERVER['PHP_SELF']) == 'membros.php' ? 'active' : '' ?>">
            <i data-lucide="users"></i> <span class="sidebar-text">Membros</span>
        </a>
        <a href="indisponibilidade.php" class="nav-item <?= basename($_SERVER['PHP_SELF']) == 'indisponibilidade.php' ? 'active' : '' ?>">
            <i data-lucide="calendar-off"></i> <span class="sidebar-text">Indisponibilidade</span>
        </a>

        <div class="nav-divider"></div>
        <div class="sidebar-text" style="padding: 0 12px 4px 12px; font-size: 0.75rem; color: #94a3b8; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px;">Espiritual</div>

        <a href="devocionais.php" class="nav-item <?= basename($_SERVER['PHP_SELF']) == 'devocionais.php' ? 'active' : '' ?>">
            <i data-lucide="book-heart"></i> <span class="sidebar-text">Devocional</span>
        </a>
        <a href="oracao.php" class="nav-item <?= basename($_SERVER['PHP_SELF']) == 'oracao.php' ? 'active' : '' ?>">
            <i data-lucide="hands-praying"></i> <span class="sidebar-text">Oração</span>
        </a>
        <a href="leitura.php" class="nav-item <?= basename($_SERVER['PHP_SELF']) == 'leitura.php' ? 'active' : '' ?>">
            <i data-lucide="book-open"></i> <span class="sidebar-text">Leitura Bíblica</span>
        </a>

        <div class="nav-divider"></div>
        <div class="sidebar-text" style="padding: 0 12px 4px 12px; font-size: 0.75rem; color: #94a3b8; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px;">Comunicação</div>

        <a href="avisos.php" class="nav-item <?= basename($_SERVER['PHP_SELF']) == 'avisos.php' ? 'active' : '' ?>">
            <i data-lucide="bell"></i> <span class="sidebar-text">Avisos</span>
        </a>
        <a href="aniversarios.php" class="nav-item <?= basename($_SERVER['PHP_SELF']) == 'aniversarios.php' ? 'active' : '' ?>">
            <i data-lucide="cake"></i> <span class="sidebar-text">Aniversários</span>
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
    /* Variáveis CLARAS + VERDES */
    :root {
        --sidebar-width: 280px;
        --sidebar-collapsed-width: 88px;
        /* Um pouco mais larga recolhida */
        --sidebar-bg: #ffffff;
        --sidebar-text: #475569;
        --sidebar-hover: #f1f5f9;
        --sidebar-border: #e2e8f0;

        /* Tema Verde */
        --brand-green: #166534;
        --brand-green-light: #dcfce7;
        --brand-border: #22c55e;
    }

    body {
        background-color: #f8fafc;
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
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        overflow-x: hidden;
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

    .sidebar-profile:hover {
        border-color: var(--brand-border);
        background: #fff;
        box-shadow: 0 4px 12px rgba(22, 101, 52, 0.1);
    }

    .profile-img {
        width: 44px;
        height: 44px;
        border-radius: 50%;
        object-fit: cover;
        border: 2px solid var(--brand-green);
        /* Borda Verde */
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
        overflow-x: hidden;
    }

    .nav-item {
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 12px 14px;
        border-radius: 10px;
        color: var(--sidebar-text);
        text-decoration: none;
        font-weight: 600;
        font-size: 0.9rem;
        transition: all 0.2s;
        white-space: nowrap;
        position: relative;
    }

    .nav-item:hover {
        background: #f0fdf4;
        color: var(--brand-green);
    }

    .nav-item.active {
        background: var(--brand-green-light);
        color: var(--brand-green);
    }

    .nav-item.active:before {
        /* Indicador lateral */
        content: '';
        position: absolute;
        left: 0;
        top: 10%;
        bottom: 10%;
        width: 4px;
        background: var(--brand-green);
        border-radius: 0 4px 4px 0;
        display: none;
        /* Opcional, removi para ficar mais clean */
    }

    .nav-item i {
        width: 22px;
        height: 22px;
        flex-shrink: 0;
        transition: color 0.2s;
    }

    .nav-divider {
        height: 1px;
        background: var(--sidebar-border);
        margin: 8px 12px;
    }

    .sidebar-footer {
        padding: 16px 12px;
        border-top: 1px solid var(--sidebar-border);
        background: #fcfcfc;
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
        padding: 8px;
        border-radius: 8px;
        transition: all 0.2s;
        display: none;
    }

    .btn-toggle-desktop:hover {
        background: #f1f5f9;
        color: var(--brand-green);
    }

    @media (min-width: 1025px) {
        .btn-toggle-desktop {
            display: flex;
            align-items: center;
            justify-content: center;
        }
    }

    /* --- ESTADO RECOLHIDO (COLLAPSED) --- */
    /* Agora controlado pela classe .collapsed */
    .sidebar.collapsed {
        width: var(--sidebar-collapsed-width);
    }

    .sidebar.collapsed .sidebar-text {
        opacity: 0;
        visibility: hidden;
        width: 0;
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
        margin-left: 10px;
        margin-right: 10px;
    }

    .sidebar.collapsed .profile-img {
        margin: 0;
        width: 40px;
        height: 40px;
    }

    .sidebar.collapsed .profile-info {
        display: none;
    }

    .sidebar.collapsed .nav-item {
        justify-content: center;
        padding: 14px 0;
    }

    .sidebar.collapsed .nav-item i {
        margin: 0;
    }

    .sidebar.collapsed .btn-toggle-desktop i {
        transform: rotate(180deg);
    }

    /* --- MOBILE --- */
    @media (max-width: 1024px) {
        .sidebar {
            transform: translateX(-100%);
            width: 280px;
        }

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

        .sidebar-profile {
            margin-top: 24px;
        }
    }
</style>

<script>
    const sidebar = document.getElementById('app-sidebar');
    const content = document.getElementById('app-content');

    function isDesktop() {
        return window.innerWidth > 1024;
    }

    function toggleSidebarDesktop() {
        if (!isDesktop()) return;

        sidebar.classList.toggle('collapsed');
        const isCollapsed = sidebar.classList.contains('collapsed');

        if (content) {
            content.style.marginLeft = isCollapsed ? '88px' : '280px';
        }
        localStorage.setItem('sidebarCollapsed', isCollapsed);
    }

    function toggleSidebarMobile() {
        sidebar.classList.toggle('open');
        document.getElementById('sidebar-overlay').classList.toggle('active');
    }

    document.addEventListener('DOMContentLoaded', () => {
        if (isDesktop()) {
            // Padrão: EXPANDIDO (false) se não houver registro
            const savedCollapsed = localStorage.getItem('sidebarCollapsed') === 'true';

            if (savedCollapsed) {
                sidebar.classList.add('collapsed');
                if (content) content.style.marginLeft = '88px';
            } else {
                sidebar.classList.remove('collapsed');
                if (content) content.style.marginLeft = '280px';
            }
        }
    });

    window.toggleSidebar = toggleSidebarMobile;
</script>