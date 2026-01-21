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
                <span class="sidebar-text" style="color:#166534;">PIB Oliveira</span>
                <span class="sidebar-text" style="font-size: 0.75rem; color: #64748b; font-weight: 600;">App Louvor</span>
            </div>
        </div>

        <button onclick="toggleSidebarDesktop()" class="btn-toggle-desktop ripple" title="Recolher Menu">
            <i data-lucide="panel-left-close"></i>
        </button>
    </div>

    <!-- Botão de Recolher (Aparece quando expandido, estilo de aba lateral) -->
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

        <a href="index.php" class="nav-item nav-indigo <?= basename($_SERVER['PHP_SELF']) == 'index.php' ? 'active' : '' ?>">
            <i data-lucide="layout-dashboard"></i> <span class="sidebar-text">Visão Geral</span>
        </a>

        <div class="nav-divider"></div>
        <div class="sidebar-text" style="padding: 0 12px 4px 12px; font-size: 0.75rem; color: #94a3b8; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px;">Gestão de Ensaios</div>

        <a href="escalas.php" class="nav-item nav-blue <?= basename($_SERVER['PHP_SELF']) == 'escalas.php' ? 'active' : '' ?>">
            <i data-lucide="calendar-days"></i> <span class="sidebar-text">Escalas</span>
        </a>
        <a href="repertorio.php" class="nav-item nav-blue <?= basename($_SERVER['PHP_SELF']) == 'repertorio.php' ? 'active' : '' ?>">
            <i data-lucide="music-2"></i> <span class="sidebar-text">Repertório</span>
        </a>
        <a href="membros.php" class="nav-item nav-blue <?= basename($_SERVER['PHP_SELF']) == 'membros.php' ? 'active' : '' ?>">
            <i data-lucide="users"></i> <span class="sidebar-text">Membros</span>
        </a>
        <a href="indisponibilidade.php" class="nav-item nav-blue <?= basename($_SERVER['PHP_SELF']) == 'indisponibilidade.php' ? 'active' : '' ?>">
            <i data-lucide="calendar-off"></i> <span class="sidebar-text">Ausências de Escala</span>
        </a>

        <div class="nav-divider"></div>
        <div class="sidebar-text" style="padding: 0 12px 4px 12px; font-size: 0.75rem; color: #94a3b8; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px;">Espiritual</div>

        <a href="devocionais.php" class="nav-item nav-purple <?= basename($_SERVER['PHP_SELF']) == 'devocionais.php' ? 'active' : '' ?>">
            <i data-lucide="book-heart"></i> <span class="sidebar-text">Devocional</span>
        </a>
        <a href="oracao.php" class="nav-item nav-purple <?= basename($_SERVER['PHP_SELF']) == 'oracao.php' ? 'active' : '' ?>">
            <i data-lucide="heart-handshake"></i> <span class="sidebar-text">Oração</span>
        </a>
        <a href="leitura.php" class="nav-item nav-purple <?= basename($_SERVER['PHP_SELF']) == 'leitura.php' ? 'active' : '' ?>">
            <i data-lucide="book-open"></i> <span class="sidebar-text">Leitura Bíblica</span>
        </a>

        <div class="nav-divider"></div>
        <div class="sidebar-text" style="padding: 0 12px 4px 12px; font-size: 0.75rem; color: #94a3b8; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px;">Comunicação</div>

        <a href="avisos.php" class="nav-item nav-orange <?= basename($_SERVER['PHP_SELF']) == 'avisos.php' ? 'active' : '' ?>">
            <i data-lucide="bell"></i> <span class="sidebar-text">Avisos</span>
        </a>
        <a href="aniversarios.php" class="nav-item nav-orange <?= basename($_SERVER['PHP_SELF']) == 'aniversarios.php' ? 'active' : '' ?>">
            <i data-lucide="cake"></i> <span class="sidebar-text">Aniversários</span>
        </a>
    </nav>

    <!-- 3. Rodapé: Cartão de Usuário Premium -->
    <div class="sidebar-footer">
        <div class="user-profile-row">
            <img src="<?= htmlspecialchars($userPhoto) ?>" alt="Foto" class="user-avatar-sm">

            <div class="user-info-mini">
                <div class="u-name"><?= htmlspecialchars($currentUser['name']) ?></div>
                <div class="u-role">Ver Perfil</div>
            </div>

            <div class="user-actions">
                <a href="configuracoes.php" class="action-icon" title="Configurações">
                    <i data-lucide="settings-2"></i>
                </a>
                <a href="../logout.php" class="action-icon danger" title="Sair">
                    <i data-lucide="log-out"></i>
                </a>
            </div>
        </div>
    </div>
</div>

<style>
    /* CSS Extra para o Footer Redesenhado */
    .sidebar-footer {
        padding: 16px;
        background: #f8fafc;
        border-top: 1px solid #e2e8f0;
    }

    .user-profile-row {
        display: flex;
        align-items: center;
        gap: 12px;
        background: white;
        padding: 10px;
        border-radius: 12px;
        border: 1px solid #e2e8f0;
        box-shadow: 0 1px 2px rgba(0, 0, 0, 0.02);
        transition: all 0.2s;
    }

    .user-profile-row:hover {
        border-color: #cbd5e1;
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
    }

    .user-avatar-sm {
        width: 36px;
        height: 36px;
        border-radius: 10px;
        /* Quadrado arredondado moderno */
        object-fit: cover;
    }

    .user-info-mini {
        flex: 1;
        min-width: 0;
        display: flex;
        flex-direction: column;
        justify-content: center;
    }

    .u-name {
        font-size: 0.85rem;
        font-weight: 700;
        color: #1e293b;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
        line-height: 1.2;
    }

    .u-role {
        font-size: 0.7rem;
        color: #94a3b8;
        font-weight: 500;
    }

    .user-actions {
        display: flex;
        gap: 4px;
    }

    .action-icon {
        width: 28px;
        height: 28px;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 6px;
        color: #64748b;
        transition: all 0.2s;
        background: transparent;
    }

    .action-icon:hover {
        background: #f1f5f9;
        color: #3b82f6;
    }

    .action-icon.danger:hover {
        background: #fef2f2;
        color: #ef4444;
    }

    .action-icon i {
        width: 16px;
        height: 16px;
    }

    /* Modo Recolhido */
    .sidebar.collapsed .sidebar-footer {
        padding: 12px;
    }

    .sidebar.collapsed .user-profile-row {
        padding: 0;
        border: none;
        box-shadow: none;
        background: transparent;
        justify-content: center;
    }

    .sidebar.collapsed .user-info-mini,
    .sidebar.collapsed .user-actions {
        display: none;
    }

    .sidebar.collapsed .user-avatar-sm {
        width: 40px;
        height: 40px;
        border-radius: 12px;
    }

    /* Dark Mode */
    body.dark-mode .sidebar-footer {
        background: #1e293b;
        border-color: #334155;
    }

    body.dark-mode .user-profile-row {
        background: #0f172a;
        border-color: #334155;
    }

    body.dark-mode .u-name {
        color: #f1f5f9;
    }

    body.dark-mode .action-icon {
        color: #94a3b8;
    }

    body.dark-mode .action-icon:hover {
        background: #1e293b;
        color: #fff;
    }
</style>

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
        scrollbar-width: none;
        /* Firefox */
        -ms-overflow-style: none;
        /* IE/Edge */
    }

    .sidebar-nav::-webkit-scrollbar {
        display: none;
        /* Chrome/Safari/Opera */
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
        width: 32px;
        height: 32px;
        padding: 6px;
        border-radius: 8px;
        background: rgba(0, 0, 0, 0.03);
        /* Fundo padrão sutil */
        flex-shrink: 0;
        transition: all 0.2s;
        color: #94a3b8;
    }

    /* Cores Temáticas */
    .nav-blue i {
        color: #3b82f6;
        background: #eff6ff;
    }

    .nav-blue:hover i {
        background: #3b82f6;
        color: white;
    }

    .nav-purple i {
        color: #8b5cf6;
        background: #f5f3ff;
    }

    .nav-purple:hover i {
        background: #8b5cf6;
        color: white;
    }

    .nav-orange i {
        color: #f59e0b;
        background: #fffbeb;
    }

    .nav-orange:hover i {
        background: #f59e0b;
        color: white;
    }

    .nav-indigo i {
        color: #6366f1;
        background: #eef2ff;
    }

    .nav-indigo:hover i {
        background: #6366f1;
        color: white;
    }

    /* Estado Ativo */
    .nav-item.active i {
        background: transparent;
        /* Remove fundo individual */
        color: inherit;
    }

    /* Cores Temáticas - Adaptação Dark Mode via body.dark-mode */
    body.dark-mode .nav-blue i {
        color: #60a5fa;
        background: rgba(59, 130, 246, 0.15);
    }

    body.dark-mode .nav-purple i {
        color: #a78bfa;
        background: rgba(139, 92, 246, 0.15);
    }

    body.dark-mode .nav-orange i {
        color: #fbbf24;
        background: rgba(245, 158, 11, 0.15);
    }

    body.dark-mode .nav-indigo i {
        color: #818cf8;
        background: rgba(99, 102, 241, 0.15);
    }

    /* Estado Ativo no Dark Mode */
    body.dark-mode .nav-item.active {
        background: rgba(22, 101, 52, 0.2);
        color: #4ade80;
    }

    body.dark-mode .nav-item.active i {
        background: transparent;
        color: #4ade80;
    }

    /* Divisórias */
    body.dark-mode .nav-divider {
        border-color: #334155;
    }

    /* Logout */
    body.dark-mode .logout-item:hover {
        background: rgba(239, 68, 68, 0.1);
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

    /* Botão Toggle no Header da Sidebar */
    .btn-toggle-desktop {
        background: #f1f5f9;
        border: none;
        cursor: pointer;
        color: #64748b;
        padding: 8px;
        border-radius: 8px;
        transition: all 0.2s;
        display: none;
        /* Controlado via media query */
    }

    .btn-toggle-desktop:hover {
        background: #e2e8f0;
        color: #1e293b;
    }

    /* Faixa de Recolhimento (Borda clicável) */
    .sidebar-collapser {
        position: absolute;
        top: 50%;
        right: -12px;
        transform: translateY(-50%);
        width: 24px;
        height: 48px;
        background: white;
        border: 1px solid #e2e8f0;
        border-left: none;
        border-radius: 0 12px 12px 0;
        display: none;
        /* Desktop only */
        align-items: center;
        justify-content: center;
        cursor: pointer;
        box-shadow: 2px 0 6px rgba(0, 0, 0, 0.05);
        z-index: 1001;
        color: #94a3b8;
    }

    .sidebar-collapser:hover {
        color: var(--brand-green);
        background: #f0fdf4;
    }

    @media (min-width: 1025px) {
        .btn-toggle-desktop {
            display: flex;
        }

        /* .sidebar-collapser { display: flex; }  <-- Se quiser usar estilo flutuante fora da barra */

        .sidebar.collapsed .sidebar-collapser i {
            transform: rotate(180deg);
        }

        /* Ajuste do ícone quando recolhido */
        .sidebar.collapsed .btn-toggle-desktop i {
            transform: rotate(180deg);
            /* Vira o icone */
        }
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

        .sidebar-collapser {
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