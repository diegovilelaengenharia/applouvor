<?php
// admin/sidebar.php
// Recomposição Premium: Mantém TODOS os links originais com visual Drawer moderno.

$userId = $_SESSION['user_id'] ?? 1;

// Determine base path for links
$isAdminDir = strpos($_SERVER['PHP_SELF'], '/admin/') !== false;
$baseAdmin = $isAdminDir ? '' : '../admin/';
$baseApp = $isAdminDir ? '../app/' : 'app/';

if (!isset($pdo)) {
    require_once '../includes/db.php';
}
try {
    $stmt = $pdo->prepare("SELECT name, role, photo FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    $sideUser = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (Exception $e) { $sideUser = null; }

$sideUserName = $sideUser['name'] ?? 'Músico';
$sideUserPhoto = !empty($sideUser['photo']) ? $sideUser['photo'] : '';
if (empty($sideUserPhoto)) {
    $sideUserPhoto = 'https://ui-avatars.com/api/?name=' . urlencode($sideUserName) . '&background=3B82F6&color=fff';
} elseif (strpos($sideUserPhoto, 'http') === false) {
    $sideUserPhoto = '../' . $sideUserPhoto;
}
?>

<div id="sidebar-overlay" class="sidebar-overlay" onclick="toggleSidebarMobile()"></div>

<aside id="app-sidebar" class="sidebar-drawer">
    
    <!-- User Profile Header -->
    <div class="sidebar-header-premium">
        <div class="side-profile">
            <img src="<?= $sideUserPhoto ?>" class="side-avatar">
            <div class="side-user-info">
                <span class="side-name"><?= explode(' ', $sideUserName)[0] ?></span>
                <span class="side-role"><?= ($sideUser['role'] ?? '') === 'admin' ? 'Administrador' : 'Músico' ?></span>
            </div>
        </div>
        <button class="side-close" onclick="toggleSidebarMobile()">
            <i data-lucide="x"></i>
        </button>
    </div>

    <div class="sidebar-scroll-area">
        <nav class="side-nav">
            
            <div class="side-nav-group">
                <span class="side-group-title">Principal</span>
                <a href="<?= $baseAdmin ?>index.php" class="side-link active">
                    <div class="side-icon-box" style="--icon-bg: #dbeafe; --icon-color: #2563eb;"><i data-lucide="layout-dashboard"></i></div>
                    <span>Visão Geral</span>
                </a>
                <a href="<?= $baseAdmin ?>escalas.php" class="side-link">
                    <div class="side-icon-box" style="--icon-bg: #fef3c7; --icon-color: #d97706;"><i data-lucide="calendar"></i></div>
                    <span>Escalas</span>
                </a>
                <a href="<?= $baseAdmin ?>repertorio.php" class="side-link">
                    <div class="side-icon-box" style="--icon-bg: #ede9fe; --icon-color: #7c3aed;"><i data-lucide="music"></i></div>
                    <span>Repertório</span>
                </a>
                <a href="<?= $baseAdmin ?>agenda.php" class="side-link">
                    <div class="side-icon-box" style="--icon-bg: #e0e7ff; --icon-color: #4f46e5;"><i data-lucide="calendar-days"></i></div>
                    <span>Agenda</span>
                </a>
            </div>

            <div class="side-nav-group">
                <span class="side-group-title">Minha Área</span>
                <a href="<?= $baseAdmin ?>perfil.php" class="side-link">
                    <div class="side-icon-box" style="--icon-bg: #f1f5f9; --icon-color: #475569;"><i data-lucide="user"></i></div>
                    <span>Meu Perfil</span>
                </a>
                <a href="<?= $baseAdmin ?>indisponibilidade.php" class="side-link">
                    <div class="side-icon-box" style="--icon-bg: #fee2e2; --icon-color: #dc2626;"><i data-lucide="calendar-off"></i></div>
                    <span>Indisponibilidade</span>
                </a>
                <a href="<?= $baseAdmin ?>leitura.php" class="side-link">
                    <div class="side-icon-box" style="--icon-bg: #dcfce7; --icon-color: #16a34a;"><i data-lucide="book-open"></i></div>
                    <span>Bíblia / Devocional</span>
                </a>
            </div>

            <div class="side-nav-group">
                <span class="side-group-title">Comunicação</span>
                <a href="<?= $baseAdmin ?>membros.php" class="side-link">
                    <div class="side-icon-box" style="--icon-bg: #dcfce7; --icon-color: #16a34a;"><i data-lucide="users"></i></div>
                    <span>Equipe</span>
                </a>
                <a href="<?= $baseAdmin ?>avisos.php" class="side-link">
                    <div class="side-icon-box" style="--icon-bg: #fee2e2; --icon-color: #dc2626;"><i data-lucide="megaphone"></i></div>
                    <span>Avisos</span>
                </a>
                <a href="<?= $baseAdmin ?>aniversarios.php" class="side-link">
                    <div class="side-icon-box" style="--icon-bg: #fdf2f8; --icon-color: #db2777;"><i data-lucide="cake"></i></div>
                    <span>Aniversariantes</span>
                </a>
            </div>

            <?php if (($sideUser['role'] ?? '') === 'admin'): ?>
            <div class="side-nav-group">
                <span class="side-group-title">Administração</span>
                <a href="<?= $baseAdmin ?>escalas_gestao.php" class="side-link">
                    <div class="side-icon-box" style="--icon-bg: #f1f5f9; --icon-color: #475569;"><i data-lucide="settings-2"></i></div>
                    <span>Gestão de Escalas</span>
                </a>
                <a href="<?= $baseAdmin ?>relatorios_gerais.php" class="side-link">
                    <div class="side-icon-box" style="--icon-bg: #f1f5f9; --icon-color: #475569;"><i data-lucide="bar-chart-3"></i></div>
                    <span>Relatórios</span>
                </a>
                <a href="<?= $baseAdmin ?>manutencao.php" class="side-link">
                    <div class="side-icon-box" style="--icon-bg: #f1f5f9; --icon-color: #475569;"><i data-lucide="database"></i></div>
                    <span>Manutenção</span>
                </a>
            </div>
            <?php endif; ?>

            <div class="side-nav-group" style="margin-top: 40px;">
                <a href="../logout.php" class="side-link" style="color: #ef4444;">
                    <div class="side-icon-box" style="--icon-bg: #fef2f2; --icon-color: #ef4444;"><i data-lucide="log-out"></i></div>
                    <span>Sair do App</span>
                </a>
            </div>
        </nav>
    </div>
</aside>
