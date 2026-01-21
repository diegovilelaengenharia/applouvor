<?php
// admin/sidebar.php

// Buscar dados do usuário logado para o topo
// Fallback para usuário ID 1 caso a sessão falhe
$userId = $_SESSION['user_id'] ?? 1;

// Query ajustada para colunas que sabemos que existem (baseado em estrutura padrão simples)
// Removido email e photo para evitar erros se não existirem
try {
    $stmtUser = $pdo->prepare("SELECT name, phone FROM users WHERE id = ?");
    $stmtUser->execute([$userId]);
    $currentUser = $stmtUser->fetch(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    // Se der erro, usa dados dummy de segurança
    $currentUser = ['name' => 'Usuário', 'phone' => ''];
}

if (!$currentUser) {
    $currentUser = ['name' => 'Visitante', 'phone' => ''];
}

// Fallback image (Sempre usa UI Avatars se não tiver foto no banco)
$userPhoto = 'https://ui-avatars.com/api/?name=' . urlencode($currentUser['name']) . '&background=047857&color=fff';

// Define subtítulo (Telefone ou texto padrão)
$subTitle = $currentUser['phone'] ? $currentUser['phone'] : 'Membro da Equipe';
?>

<div id="app-sidebar" class="sidebar">
    <!-- 1. Topo: Perfil -->
    <a href="perfil.php" class="sidebar-profile ripple">
        <img src="<?= $userPhoto ?>" alt="Foto Perfil" class="profile-img">
        <div class="profile-info">
            <div class="profile-name"><?= htmlspecialchars($currentUser['name']) ?></div>
            <div class="profile-meta">
                <?= htmlspecialchars($subTitle) ?>
            </div>
        </div>
        <i data-lucide="chevron-right" class="profile-arrow"></i>
    </a>

    <!-- 2. Menu Navegação -->
    <nav class="sidebar-nav">
        <!-- Itens Principais -->
        <a href="index.php" class="nav-item <?= basename($_SERVER['PHP_SELF']) == 'index.php' ? 'active' : '' ?>">
            <i data-lucide="layout-dashboard"></i> <span>Visão Geral</span>
        </a>
        <a href="escalas.php" class="nav-item <?= basename($_SERVER['PHP_SELF']) == 'escalas.php' ? 'active' : '' ?>">
            <i data-lucide="calendar-days"></i> <span>Escalas</span>
        </a>
        <a href="repertorio.php" class="nav-item <?= basename($_SERVER['PHP_SELF']) == 'repertorio.php' ? 'active' : '' ?>">
            <i data-lucide="music-2"></i> <span>Repertório</span>
        </a>
        <a href="membros.php" class="nav-item <?= basename($_SERVER['PHP_SELF']) == 'membros.php' ? 'active' : '' ?>">
            <i data-lucide="users"></i> <span>Ministério/Membros</span>
        </a>

        <!-- Itens Secundários (Agrupados ou Separados) -->
        <div class="nav-divider"></div>

        <a href="avisos.php" class="nav-item <?= basename($_SERVER['PHP_SELF']) == 'avisos.php' ? 'active' : '' ?>">
            <i data-lucide="bell"></i> <span>Avisos</span>
        </a>
        <a href="indisponibilidade.php" class="nav-item <?= basename($_SERVER['PHP_SELF']) == 'indisponibilidade.php' ? 'active' : '' ?>">
            <i data-lucide="calendar-off"></i> <span>Indisponibilidades</span>
        </a>
        <a href="aniversarios.php" class="nav-item <?= basename($_SERVER['PHP_SELF']) == 'aniversarios.php' ? 'active' : '' ?>">
            <i data-lucide="cake"></i> <span>Aniversariantes</span>
        </a>
        <a href="leitura.php" class="nav-item <?= basename($_SERVER['PHP_SELF']) == 'leitura.php' ? 'active' : '' ?>">
            <i data-lucide="book-open"></i> <span>Leitura Bíblica</span>
        </a>
    </nav>

    <!-- 3. Rodapé: Configurações -->
    <div class="sidebar-footer">
        <a href="configuracoes.php" class="nav-item <?= basename($_SERVER['PHP_SELF']) == 'configuracoes.php' ? 'active' : '' ?>">
            <i data-lucide="settings"></i> <span>Configurações</span>
        </a>

        <div class="nav-item" onclick="toggleThemeMode()" style="cursor: pointer;">
            <i data-lucide="moon"></i> <span>Modo Escuro</span>
        </div>

        <a href="../logout.php" class="nav-item" style="color: #ef4444;">
            <i data-lucide="log-out"></i> <span>Sair</span>
        </a>
    </div>
</div>

<!-- Overlay para mobile -->
<div id="sidebar-overlay" class="sidebar-overlay" onclick="toggleSidebar()"></div>

<style>
    /* Variáveis da Sidebar */
    :root {
        --sidebar-width: 280px;
        --sidebar-bg: #1e293b;
        /* Azul escuro estilo LouveApp */
        --sidebar-text: #e2e8f0;
        --sidebar-active: #0f172a;
        --sidebar-accent: #3b82f6;
        /* Azul destaque */
        --sidebar-header-bg: rgba(255, 255, 255, 0.05);
    }

    /* Layout Base */
    .sidebar {
        position: fixed;
        left: 0;
        top: 0;
        bottom: 0;
        width: var(--sidebar-width);
        background: var(--sidebar-bg);
        color: var(--sidebar-text);
        display: flex;
        flex-direction: column;
        z-index: 1000;
        box-shadow: 4px 0 24px rgba(0, 0, 0, 0.15);
        transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        overflow-y: auto;
    }

    /* 1. Perfil Header */
    .sidebar-profile {
        padding: 24px 20px;
        background: #0f172a;
        /* Mais escuro */
        display: flex;
        align-items: center;
        gap: 12px;
        text-decoration: none;
        color: white;
        margin: 16px;
        border-radius: 16px;
        transition: background 0.2s;
        border: 1px solid rgba(255, 255, 255, 0.05);
    }

    .sidebar-profile:hover {
        background: #1e293b;
    }

    .profile-img {
        width: 48px;
        height: 48px;
        border-radius: 50%;
        object-fit: cover;
        border: 2px solid var(--sidebar-accent);
    }

    .profile-info {
        flex: 1;
        overflow: hidden;
    }

    .profile-name {
        font-weight: 700;
        font-size: 0.95rem;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .profile-meta {
        font-size: 0.75rem;
        opacity: 0.7;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .profile-arrow {
        width: 18px;
        opacity: 0.5;
    }

    /* 2. Navegação */
    .sidebar-nav {
        flex: 1;
        padding: 0 16px;
        display: flex;
        flex-direction: column;
        gap: 4px;
    }

    .nav-item {
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 12px 16px;
        border-radius: 12px;
        color: #94a3b8;
        /* Cinza claro */
        text-decoration: none;
        font-weight: 600;
        font-size: 0.9rem;
        transition: all 0.2s;
        cursor: pointer;
    }

    .nav-item:hover {
        background: rgba(255, 255, 255, 0.05);
        color: white;
    }

    .nav-item.active {
        background: var(--sidebar-accent);
        color: white;
        box-shadow: 0 4px 12px rgba(59, 130, 246, 0.3);
    }

    .nav-item i {
        width: 20px;
        height: 20px;
    }

    .nav-divider {
        height: 1px;
        background: rgba(255, 255, 255, 0.1);
        margin: 8px 16px;
    }

    /* 3. Rodapé */
    .sidebar-footer {
        padding: 16px;
        border-top: 1px solid rgba(255, 255, 255, 0.05);
        background: #0f172a;
    }

    /* Overlay Mobile */
    .sidebar-overlay {
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: rgba(0, 0, 0, 0.6);
        z-index: 999;
        opacity: 0;
        visibility: hidden;
        /* Inicialmente escondido */
        transition: opacity 0.3s;
        backdrop-filter: blur(2px);
    }

    .sidebar-overlay.active {
        opacity: 1;
        visibility: visible;
    }

    /* Responsividade */
    @media (max-width: 1024px) {
        .sidebar {
            transform: translateX(-100%);
            /* Escondido por padrão no mobile/tablet */
        }

        .sidebar.open {
            transform: translateX(0);
        }
    }

    @media (min-width: 1025px) {

        /* Desktop: Sidebar fixa, empurra conteúdo */
        /* O conteúdo principal precisa ter margin-left: 280px */
        #app-content {
            margin-left: var(--sidebar-width);
            transition: margin-left 0.3s;
        }
    }
</style>

<script>
    function toggleSidebar() {
        const sidebar = document.getElementById('app-sidebar');
        const overlay = document.getElementById('sidebar-overlay');
        sidebar.classList.toggle('open');
        overlay.classList.toggle('active');
    }

    function toggleThemeMode() {
        // Implementação futura do modo escuro
        alert('Modo escuro em desenvolvimento! 🌙');
    }
</script>