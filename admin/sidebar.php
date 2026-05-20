<?php
// admin/sidebar.php
// Recomposição Premium de Alta Fidelidade para a Barra Lateral
// Mantém todos os links estáveis, mas adiciona badges numéricos, perfil no rodapé e migra o CSS.

$userId = $_SESSION['user_id'] ?? 1;

// Determine base path for links
$isAdminDir = strpos($_SERVER['PHP_SELF'], '/admin/') !== false;
$baseAdmin = $isAdminDir ? '' : '../admin/';
$baseApp = $isAdminDir ? '../app/' : 'app/';

if (!isset($pdo)) {
    require_once __DIR__ . '/../includes/db.php';
}

try {
    $stmtUser = $pdo->prepare("SELECT name, role, photo, avatar FROM users WHERE id = ?");
    $stmtUser->execute([$userId]);
    $sideUser = $stmtUser->fetch(PDO::FETCH_ASSOC);
} catch (Exception $e) { 
    $sideUser = null; 
}

$sideUserName = $sideUser['name'] ?? 'Músico';
$sideUserRole = $sideUser['role'] ?? 'user';
$sideUserPhoto = !empty($sideUser['avatar']) ? $sideUser['avatar'] : (!empty($sideUser['photo']) ? $sideUser['photo'] : '');

if (empty($sideUserPhoto)) {
    $sideUserPhoto = 'https://ui-avatars.com/api/?name=' . urlencode($sideUserName) . '&background=1e40af&color=fff';
} elseif (strpos($sideUserPhoto, 'http') === false) {
    if (strpos($sideUserPhoto, 'assets') === false && strpos($sideUserPhoto, 'uploads') === false) {
        $sideUserPhoto = '../assets/uploads/' . $sideUserPhoto;
    } else {
        $sideUserPhoto = '../' . $sideUserPhoto;
    }
}

// Queries para os Badges
$countUpcomingSchedules = 0;
$countUnreadAvisos = 0;
$countPendingSuggestions = 0;

try {
    // Total de escalas futuras atribuídas a este usuário
    $stmtCount = $pdo->prepare("SELECT COUNT(*) FROM schedule_users su JOIN schedules s ON s.id = su.schedule_id WHERE su.user_id = ? AND s.event_date >= CURDATE() AND su.status = 'pending'");
    $stmtCount->execute([$userId]);
    $countUpcomingSchedules = (int)$stmtCount->fetchColumn();
} catch (Exception $e) {}

try {
    // Avisos criados nos últimos 3 dias
    $stmtCountAvisos = $pdo->query("SELECT COUNT(*) FROM avisos WHERE created_at > DATE_SUB(NOW(), INTERVAL 3 DAY)");
    $countUnreadAvisos = (int)$stmtCountAvisos->fetchColumn();
} catch (Exception $e) {}

try {
    // Sugestões pendentes para o administrador
    if ($sideUserRole === 'admin') {
        $countPendingSuggestions = (int)$pdo->query("SELECT COUNT(*) FROM song_suggestions WHERE status = 'pending'")->fetchColumn();
    }
} catch (Exception $e) {}
?>

<div id="sidebar-overlay" class="sidebar-overlay" onclick="toggleSidebarMobile()"></div>

<aside id="app-sidebar" class="sidebar">
    <!-- 1. Cabeçalho Sidebar com Logo -->
    <div class="sidebar-logo-container">
        <div class="logo-area">
            <?php 
            $pathPrefix = '';
            if (file_exists('assets/img/logo_pib_black.png')) {
                $pathPrefix = '';
            } elseif (file_exists('../assets/img/logo_pib_black.png')) {
                $pathPrefix = '../';
            } elseif (file_exists('../../assets/img/logo_pib_black.png')) {
                $pathPrefix = '../../';
            }
            
            $logoBlack = $pathPrefix . 'assets/img/logo_pib_black.png';
            $logoWhite = $pathPrefix . 'assets/img/logo_pib_white.png';

            if (file_exists($logoBlack) || file_exists(__DIR__ . '/../assets/img/logo_pib_black.png')): 
            ?>
                <img src="<?= $logoBlack ?>" alt="PIB Oliveira" class="logo-img logo-light-only">
                <img src="<?= $logoWhite ?>" alt="PIB Oliveira" class="logo-img logo-dark-only">
            <?php else: ?>
                <div class="logo-fallback-icon"><i data-lucide="music-4"></i></div>
            <?php endif; ?>

            <div class="logo-text-block">
                <span class="main-brand-title">PIB Oliveira</span>
                <span class="sub-brand-title">App Louvor</span>
            </div>
        </div>
        <button class="side-close-mobile" onclick="toggleSidebarMobile()" aria-label="Fechar Menu">
            <i data-lucide="x"></i>
        </button>
    </div>

    <!-- 2. Menu de Navegação -->
    <div class="sidebar-scroll-area">
        <nav class="sidebar-nav">
            
            <!-- SEÇÃO: PRINCIPAL -->
            <div class="nav-section">
                <span class="section-title-label">Principal</span>
                
                <a href="<?= $baseAdmin ?>index.php" class="nav-item nav-primary <?= basename($_SERVER['PHP_SELF']) == 'index.php' ? 'active' : '' ?>">
                    <div class="side-icon-box"><i data-lucide="layout-dashboard"></i></div>
                    <span class="nav-item-text">Visão Geral</span>
                </a>
            </div>

            <!-- SEÇÃO: GESTÃO DE ENSAIOS -->
            <div class="nav-section">
                <span class="section-title-label">Gestão de Ensaios</span>
                
                <a href="<?= $baseAdmin ?>escalas.php" class="nav-item nav-blue <?= basename($_SERVER['PHP_SELF']) == 'escalas.php' ? 'active' : '' ?>">
                    <div class="side-icon-box"><i data-lucide="calendar"></i></div>
                    <span class="nav-item-text">Escalas</span>
                    <?php if ($countUpcomingSchedules > 0): ?>
                        <span class="nav-badge badge-blue"><?= $countUpcomingSchedules ?></span>
                    <?php endif; ?>
                </a>
                
                <a href="<?= $baseAdmin ?>repertorio.php" class="nav-item nav-blue <?= basename($_SERVER['PHP_SELF']) == 'repertorio.php' ? 'active' : '' ?>">
                    <div class="side-icon-box"><i data-lucide="music-2"></i></div>
                    <span class="nav-item-text">Repertório</span>
                </a>
                
                <a href="<?= $baseAdmin ?>historico.php" class="nav-item nav-blue <?= basename($_SERVER['PHP_SELF']) == 'historico.php' ? 'active' : '' ?>">
                    <div class="side-icon-box"><i data-lucide="history"></i></div>
                    <span class="nav-item-text">Histórico</span>
                </a>
                
                <a href="<?= $baseAdmin ?>membros.php" class="nav-item nav-blue <?= basename($_SERVER['PHP_SELF']) == 'membros.php' ? 'active' : '' ?>">
                    <div class="side-icon-box"><i data-lucide="users"></i></div>
                    <span class="nav-item-text">Membros</span>
                </a>
                
                <a href="<?= $baseAdmin ?>indisponibilidade.php" class="nav-item nav-blue <?= basename($_SERVER['PHP_SELF']) == 'indisponibilidade.php' ? 'active' : '' ?>">
                    <div class="side-icon-box"><i data-lucide="calendar-off"></i></div>
                    <span class="nav-item-text">Ausências</span>
                </a>

                <a href="<?= $baseAdmin ?>agenda.php" class="nav-item nav-blue <?= basename($_SERVER['PHP_SELF']) == 'agenda.php' ? 'active' : '' ?>">
                    <div class="side-icon-box"><i data-lucide="calendar-range"></i></div>
                    <span class="nav-item-text">Agenda</span>
                </a>
            </div>

            <!-- SEÇÃO: ESPIRITUAL -->
            <div class="nav-section">
                <span class="section-title-label">Espiritual</span>
                
                <a href="<?= $baseAdmin ?>devocionais.php" class="nav-item nav-green <?= basename($_SERVER['PHP_SELF']) == 'devocionais.php' ? 'active' : '' ?>">
                    <div class="side-icon-box"><i data-lucide="book-heart"></i></div>
                    <span class="nav-item-text">Devocional</span>
                </a>
                
                <a href="<?= $baseAdmin ?>oracao.php" class="nav-item nav-green <?= basename($_SERVER['PHP_SELF']) == 'oracao.php' ? 'active' : '' ?>">
                    <div class="side-icon-box"><i data-lucide="heart"></i></div>
                    <span class="nav-item-text">Oração</span>
                </a>
                
                <a href="<?= $baseAdmin ?>leitura.php" class="nav-item nav-green <?= basename($_SERVER['PHP_SELF']) == 'leitura.php' ? 'active' : '' ?>">
                    <div class="side-icon-box"><i data-lucide="book-open"></i></div>
                    <span class="nav-item-text">Leitura Bíblica</span>
                </a>
            </div>

            <!-- SEÇÃO: COMUNICAÇÃO -->
            <div class="nav-section">
                <span class="section-title-label">Comunicação</span>
                
                <a href="<?= $baseAdmin ?>avisos.php" class="nav-item nav-amber <?= basename($_SERVER['PHP_SELF']) == 'avisos.php' ? 'active' : '' ?>">
                    <div class="side-icon-box"><i data-lucide="megaphone"></i></div>
                    <span class="nav-item-text">Avisos</span>
                    <?php if ($countUnreadAvisos > 0): ?>
                        <span class="nav-badge badge-orange"><?= $countUnreadAvisos ?></span>
                    <?php endif; ?>
                </a>
                
                <a href="<?= $baseAdmin ?>aniversarios.php" class="nav-item nav-amber <?= basename($_SERVER['PHP_SELF']) == 'aniversarios.php' ? 'active' : '' ?>">
                    <div class="side-icon-box"><i data-lucide="cake"></i></div>
                    <span class="nav-item-text">Aniversariantes</span>
                </a>
            </div>

            <!-- SEÇÃO: ADMINISTRAÇÃO (Apenas Líderes) -->
            <?php if ($sideUserRole === 'admin'): ?>
            <div class="nav-section">
                <span class="section-title-label">Administração</span>
                
                <a href="<?= $baseAdmin ?>escalas_gestao.php" class="nav-item nav-red <?= basename($_SERVER['PHP_SELF']) == 'escalas_gestao.php' ? 'active' : '' ?>">
                    <div class="side-icon-box"><i data-lucide="sliders"></i></div>
                    <span class="nav-item-text">Gestão de Escalas</span>
                    <?php if ($countPendingSuggestions > 0): ?>
                        <span class="nav-badge badge-red"><?= $countPendingSuggestions ?></span>
                    <?php endif; ?>
                </a>
                
                <a href="<?= $baseAdmin ?>relatorios_gerais.php" class="nav-item nav-red <?= basename($_SERVER['PHP_SELF']) == 'relatorios_gerais.php' ? 'active' : '' ?>">
                    <div class="side-icon-box"><i data-lucide="trending-up"></i></div>
                    <span class="nav-item-text">Relatórios</span>
                </a>
                
                <a href="<?= $baseAdmin ?>manutencao.php" class="nav-item nav-red <?= basename($_SERVER['PHP_SELF']) == 'manutencao.php' ? 'active' : '' ?>">
                    <div class="side-icon-box"><i data-lucide="database"></i></div>
                    <span class="nav-item-text">Manutenção</span>
                </a>
            </div>
            <?php endif; ?>
        </nav>
    </div>

    <!-- 3. Rodapé com Perfil do Usuário e Alternador de Tema -->
    <div class="sidebar-footer">
        <div class="sidebar-profile-card">
            <div class="profile-avatar-wrapper">
                <img class="profile-avatar" src="<?= $sideUserPhoto ?>" alt="<?= htmlspecialchars($sideUserName) ?>">
            </div>
            <div class="profile-info">
                <span class="profile-name"><?= htmlspecialchars(explode(' ', $sideUserName)[0]) ?></span>
                <span class="profile-role-tag <?= $sideUserRole === 'admin' ? 'role-admin' : 'role-user' ?>">
                    <?= $sideUserRole === 'admin' ? 'Líder' : 'Músico' ?>
                </span>
            </div>
            <div class="profile-actions">
                <!-- Dark Mode Switcher -->
                <div class="theme-toggle-switch" title="Alternar Tema" onclick="toggleThemeMode()">
                    <input type="checkbox" id="darkModeToggleSidebar">
                    <label for="darkModeToggleSidebar" class="theme-toggle-label" onclick="event.stopPropagation()">
                        <i data-lucide="sun" class="sun-icon"></i>
                        <i data-lucide="moon" class="moon-icon"></i>
                    </label>
                </div>
                
                <!-- Logout -->
                <?php
                $logoutPath = $isAdminDir ? '../logout.php' : ($inApp ? '../../logout.php' : 'logout.php');
                ?>
                <a href="<?= $logoutPath ?>" class="profile-logout-btn" title="Sair da Conta">
                    <i data-lucide="log-out"></i>
                </a>
            </div>
        </div>
        
        <div class="sidebar-credits">
            <span>Desenvolvido por <strong>Diego T. N. Vilela</strong></span>
        </div>
    </div>
</aside>

<script>
    const sidebar = document.getElementById('app-sidebar');
    const content = document.getElementById('app-content');

    function isDesktop() {
        return window.innerWidth > 1024;
    }

    // A sidebar desktop agora é 100% fixa e não recolhível.
    function toggleSidebarDesktop() {
        console.log("Desktop sidebar is fixed.");
    }

    function toggleSidebarMobile() {
        sidebar.classList.toggle('open');
        document.getElementById('sidebar-overlay').classList.toggle('active');
    }

    // Inicialização geométrica no carregamento
    document.addEventListener('DOMContentLoaded', () => {
        if (isDesktop()) {
            if (content) content.style.marginLeft = 'var(--sidebar-width)';
        } else {
            if (content) content.style.marginLeft = '0';
        }

        // --- GESTOS DE TOUCH (SWIPE TO OPEN/CLOSE) ---
        let touchStartX = 0;
        let touchEndX = 0;
        const widthTrigger = 35; // Pixels da borda para acionar

        document.addEventListener('touchstart', (e) => {
            if (isDesktop()) return;
            touchStartX = e.touches[0].clientX;
        }, { passive: false });

        document.addEventListener('touchmove', (e) => {
            if (isDesktop()) return;
            const currentX = e.touches[0].clientX;
            // Impede navegação traseira nativa no swipe
            if (touchStartX < widthTrigger && currentX > touchStartX) {
                e.preventDefault();
            }
        }, { passive: false });

        document.addEventListener('touchend', (e) => {
            if (isDesktop()) return;
            touchEndX = e.changedTouches[0].clientX;
            handleSwipe();
        }, { passive: true });

        function handleSwipe() {
            const threshold = 60; // Sensibilidade do swipe
            if (touchStartX < widthTrigger && touchEndX > touchStartX + threshold) {
                if (!sidebar.classList.contains('open')) toggleSidebarMobile();
            }
            if (touchEndX < touchStartX - threshold) {
                if (sidebar.classList.contains('open')) toggleSidebarMobile();
            }
        }
    });

    // Registra globalmente para ser invocada pelo botão de hambúrguer
    window.toggleSidebar = toggleSidebarMobile;
    window.toggleSidebarMobile = toggleSidebarMobile;
    window.toggleSidebarDesktop = toggleSidebarDesktop;
</script>
