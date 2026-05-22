<?php
// src/layout/layout.php
header('Content-Type: text/html; charset=utf-8');

// Inicia sessão se não estiver iniciada
if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.gc_maxlifetime', 2592000);
    $isSecure = defined('APP_ENV') ? (APP_ENV === 'production') : true;
    session_set_cookie_params([
        'lifetime' => 2592000,
        'path' => '/',
        'secure' => $isSecure, // false em local, true em produção
        'httponly' => true,
        'samesite' => 'Strict'
    ]);
    session_start();
}

// Garante que o token CSRF esteja disponível em todas as páginas
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

require_once __DIR__ . '/../config/db.php';

function renderAppHeader($title, $backUrl = null)
{
    global $pdo;

    // --- Lógica de Usuário Global (Movida do Sidebar) ---
    $userId = $_SESSION['user_id'] ?? 1;
    $currentUser = null;
    $userPhoto = null;

    if ($userId) {
        try {
            // Tenta buscar foto tamb+®m
            $stmtUser = $pdo->prepare("SELECT name, phone, avatar FROM users WHERE id = ?");
            $stmtUser->execute([$userId]);
            $currentUser = $stmtUser->fetch(PDO::FETCH_ASSOC);
        } catch (Exception $e) { /* Ignorar erros de coluna */
        }

        if (!$currentUser) {
            $currentUser = ['name' => $_SESSION['user_name'] ?? 'Usuário', 'phone' => '', 'avatar' => null];
        }

        // Avatar Logic
        if (!empty($currentUser['avatar'])) {
            $userPhoto = $currentUser['avatar'];
            if (strpos($userPhoto, 'http') === false && strpos($userPhoto, 'assets') === false && strpos($userPhoto, 'uploads') === false) {
                $userPhoto = '../uploads/' . $userPhoto;
            }
        } else {
            $userNameForAvatar = $currentUser['name'] ?? 'U';
            $userPhoto = 'https://ui-avatars.com/api/?name=' . urlencode($userNameForAvatar) . '&background=dbeafe&color=1e40af';
        }
    }
    // Compartilhar com globais ou session para acesso no header
    // Uma forma suja mas eficaz para templates +® usar global ou re-passar. 
    // Vamos usar global $_layoutUser para acesso em renderPageHeader
    global $_layoutUser;
    $_layoutUser = [
        'name' => $currentUser['name'],
        'photo' => $userPhoto,
        'profile_link' => 'perfil.php'
    ];
?>
    <!DOCTYPE html>
    <html lang="pt-BR">

    <head>
        <?php require_once __DIR__ . '/head.php'; ?>
    </head>

    <body class="bg-surface text-on-surface font-body-md antialiased pb-24 md:pb-0 pt-16 <?= (basename($_SERVER['PHP_SELF']) === 'index.php') ? 'page-is-dashboard' : '' ?>">
        <?php 
        if (file_exists('sidebar.php')) {
            include_once 'sidebar.php';
        } elseif (file_exists('../admin/sidebar.php')) {
            include_once '../admin/sidebar.php';
        } elseif (file_exists('admin/sidebar.php')) {
            include_once 'admin/sidebar.php';
        }
        ?>

        <div id="app-content" class="app-content">
            <!-- Header Mobile Legacy REMOVED to avoid double header with Pro Max Design -->
            <?php /* Header Mobile Logic moved to renderPageHeader (Unification) */ ?>
            
            <!-- TopAppBar -->
            <?php 
            $isHome = basename($_SERVER['PHP_SELF']) === 'index.php'; 
            $inAdmin = strpos($_SERVER['PHP_SELF'], '/admin/') !== false;
            $inApp   = strpos($_SERVER['PHP_SELF'], '/app/')   !== false;
            $liderLink = $inAdmin ? 'lider.php' : ($inApp ? '../admin/lider.php' : 'admin/lider.php');
            $notifLink = $inAdmin ? 'notificacoes.php' : ($inApp ? '../admin/notificacoes.php' : 'admin/notificacoes.php');
            ?>
            <header class="<?= $isHome ? 'flex' : 'hidden lg:flex' ?> fixed top-0 justify-between items-center px-margin-mobile h-16 w-full z-40 lg:left-64 lg:w-[calc(100%-16rem)] bg-surface dark:bg-deep-navy border-b border-outline-variant dark:border-on-surface-variant transition-colors duration-200">
                <button class="lg:hidden text-primary dark:text-primary-fixed hover:bg-surface-container-low dark:hover:bg-surface-variant p-2 rounded-full flex items-center justify-center" onclick="toggleSidebarMobile ? toggleSidebarMobile() : (window.toggleSidebar && window.toggleSidebar())">
                    <span class="material-symbols-outlined">menu</span>
                </button>
                
                <div class="flex items-center gap-2">
                    <?php 
                    $pathPrefix = '';
                    if (file_exists('assets/images/logo_pib_black.png')) {
                        $pathPrefix = '';
                    } elseif (file_exists('../assets/images/logo_pib_black.png')) {
                        $pathPrefix = '../';
                    } elseif (file_exists('../../assets/images/logo_pib_black.png')) {
                        $pathPrefix = '../../';
                    }
                    
                    $logoBlack = $pathPrefix . 'assets/images/logo_pib_black.png';
                    $logoWhite = $pathPrefix . 'assets/images/logo_pib_white.png';

                    if (file_exists($logoBlack) || file_exists(__DIR__ . '/../assets/images/logo_pib_black.png')): 
                    ?>
                        <img src="<?= $logoBlack ?>" alt="PIB Oliveira" class="h-8 w-auto block dark:hidden">
                        <img src="<?= $logoWhite ?>" alt="PIB Oliveira" class="h-8 w-auto hidden dark:block">
                    <?php else: ?>
                        <div class="text-lg font-black tracking-tight text-primary dark:text-primary-fixed" style="font-family: var(--font-display); letter-spacing: -0.03em;">WorshipFlow</div>
                    <?php endif; ?>
                </div>

                <div class="flex items-center gap-2 relative">
                    <!-- Bell Button for Mobile/Header -->
                    <button onclick="toggleNotifications('headerNotificationDropdown')" class="text-primary dark:text-primary-fixed hover:bg-surface-container-low dark:hover:bg-surface-variant p-2 rounded-full flex items-center justify-center relative" id="notificationBtn" title="Notificações">
                        <span class="material-symbols-outlined">notifications</span>
                        <span class="badge-dot" id="notificationBadge" style="display:none;"></span>
                    </button>
                    <!-- Notification Dropdown -->
                    <div class="notification-dropdown" id="headerNotificationDropdown">
                        <div class="notification-header">
                            <div class="notification-title">
                                Notificações
                                <button onclick="requestNotificationPermission()" class="notification-enable-btn">
                                    <i data-lucide="bell-ring" style="width:12px;"></i> Ativar
                                </button>
                            </div>
                            <button class="mark-all-read" onclick="markAllAsRead()">Marcar lidas</button>
                        </div>
                        <div class="notification-list">
                            <div class="empty-state">
                                <i data-lucide="bell-off" style="width:24px;"></i>
                                <p>Carregando...</p>
                            </div>
                        </div>
                        <div class="notification-footer">
                            <a href="<?= $notifLink ?>">Ver todas <i data-lucide="arrow-right" style="width:14px;"></i></a>
                        </div>
                    </div>

                    <!-- Avatar Button -->
                    <button onclick="toggleProfileDropdown(event,'headerProfileDropdown')" class="text-primary dark:text-primary-fixed hover:bg-surface-container-low dark:hover:bg-surface-variant p-2 rounded-full flex items-center justify-center relative profile-avatar-btn">
                        <div class="w-8 h-8 rounded-full bg-surface-container-high overflow-hidden">
                            <img alt="Avatar" class="w-full h-full object-cover" src="<?= $_layoutUser['photo'] ?? 'https://ui-avatars.com/api/?name=U' ?>" />
                        </div>
                    </button>
                    <!-- Profile Dropdown -->
                    <div id="headerProfileDropdown" class="profile-dropdown">
                        <div class="profile-header">
                            <div class="profile-avatar-container" style="width: 44px; height: 44px; border-radius: 50%; overflow: hidden; border: 2px solid white; box-shadow: 0 2px 8px rgba(37, 99, 235, 0.2); flex-shrink: 0;">
                                <img src="<?= $_layoutUser['photo'] ?? 'https://ui-avatars.com/api/?name=U&background=cbd5e1&color=fff' ?>" style="width: 100%; height: 100%; object-fit: cover;" alt="Avatar">
                            </div>
                            <div class="profile-info">
                                <div class="profile-name"><?= htmlspecialchars($_layoutUser['name'] ?? '') ?></div>
                                <div class="profile-role">Membro da Equipe</div>
                            </div>
                        </div>
                        <div style="padding:8px;">
                            <?php
                            $qsLink     = $inAdmin ? '../app/quem_somos.php' : ($inApp ? 'quem_somos.php' : 'app/quem_somos.php');
                            $perfilLink = $inAdmin ? 'perfil.php' : ($inApp ? '../admin/perfil.php' : 'admin/perfil.php');
                            $logoutPath = $inAdmin ? '../logout.php' : ($inApp ? '../../logout.php' : 'logout.php');
                            ?>
                            <a href="<?= $qsLink ?>" class="profile-menu-item">
                                <div class="icon-wrapper" style="background:var(--primary-light);color:var(--primary);">
                                    <i data-lucide="circle-help" width="16"></i>
                                </div>
                                <span>Quem somos nós?</span>
                            </a>
                            <a href="<?= $perfilLink ?>" class="profile-menu-item">
                                <div class="icon-wrapper" style="background:var(--bg-surface-alt);color:var(--text-muted);">
                                    <i data-lucide="user" width="16"></i>
                                </div>
                                <span>Meu Perfil</span>
                            </a>
                            <a href="#" onclick="openDashboardCustomization();return false;" class="profile-menu-item">
                                <div class="icon-wrapper" style="background:var(--info-bg);color:var(--info-text);">
                                    <i data-lucide="layout" width="16"></i>
                                </div>
                                <span>Acesso Rápido</span>
                            </a>
                            <?php if (isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin'): ?>
                                <a href="<?= $liderLink ?>" class="profile-menu-item">
                                    <div class="icon-wrapper" style="background:var(--warning-bg);color:var(--warning-text);">
                                        <i data-lucide="crown" width="16"></i>
                                    </div>
                                    <span>Painel do Líder</span>
                                </a>
                            <?php endif; ?>

                            <!-- Dark Mode Toggle -->
                            <div onclick="toggleThemeMode()" class="profile-menu-item" style="cursor:pointer;">
                                <div class="icon-wrapper" style="background:var(--bg-surface-alt);color:var(--text-muted);">
                                    <i data-lucide="moon" width="16"></i>
                                </div>
                                <span>Modo Escuro</span>
                                <div style="margin-left:auto;">
                                    <label class="toggle-switch-mini">
                                        <input type="checkbox" id="darkModeToggleDropdown" onchange="toggleThemeMode()">
                                        <span class="slider-mini round"></span>
                                    </label>
                                </div>
                            </div>

                            <div style="height:1px;background:var(--border-color);margin:6px 12px;"></div>

                            <a href="<?= $logoutPath ?>" class="profile-menu-item" style="color:var(--danger);">
                                <div class="icon-wrapper" style="background:var(--danger-bg);color:var(--danger);">
                                    <i data-lucide="log-out" width="16"></i>
                                </div>
                                <span style="font-weight:600;">Sair da Conta</span>
                            </a>
                        </div>
                    </div>
                </div>
            </header>
        <?php
    }



    function renderAppFooter()
    {
        ?>
        </div> <!-- Fim #app-content -->

        

        <!-- Bottom Navigation (Mobile Only — oculta em desktop via CSS) -->
        <?php require_once __DIR__ . '/bottom-nav.php'; ?>

        <!-- Overlay de Fundo -->
        <div id="bs-overlay" class="bs-overlay" onclick="closeAllSheets()"></div>




        <!-- Inicializar Ícones -->
        <script>
            lucide.createIcons();
            // Animate cards on load
            document.addEventListener('DOMContentLoaded', () => {
                document.querySelectorAll('.card, .stats-card, .notice-card').forEach((card, i) => {
                    card.classList.add('animate-in');
                    card.style.animationDelay = `${i * 0.1}s`;
                });
            });
        </script>
        
        <!-- Sidebar & Gestures Script -->

        
        <!-- Main Script & Gestures (Legacy includes kept) -->
        <script src="<?= APP_URL ?>/assets/js/main.js"></script>
        <script src="<?= APP_URL ?>/assets/js/gestures.js"></script>
    <!-- PWA Install Script (Global) -->
    <script>
        // Service Worker Registration (single instance)
        if ('serviceWorker' in navigator) {
            navigator.serviceWorker.register('/sw.js')
                .then(reg => console.log('SW Registered:', reg.scope))
                .catch(err => console.warn('SW Failed:', err));
        }

        // toggleThemeMode is defined in theme-toggle.js (loaded in HEAD)
        // DO NOT define it here as it will overwrite the correct function


        // Install Button Logic
        let deferredPrompt;
        const btnInstall = document.getElementById('btnInstallSidebar');

        // Check if app is already installed (Standalone mode)
        const isStandalone = window.matchMedia('(display-mode: standalone)').matches || window.navigator.standalone;

        // Show button if NOT installed
        if (btnInstall && !isStandalone) {
             btnInstall.style.display = 'flex';
        }

        window.addEventListener('beforeinstallprompt', (e) => {
            e.preventDefault();
            deferredPrompt = e;
            console.log('Install prompt captured (Layout)');
            
            if (btnInstall) {
                 btnInstall.style.display = 'flex';
                 const textSpan = btnInstall.querySelector('.sidebar-text');
                 if(textSpan) textSpan.textContent = 'Instalar App';
            }
        });

        window.addEventListener('appinstalled', () => {
             console.log('App Installed');
             if (btnInstall) btnInstall.style.display = 'none';
        });

        window.installPWA = async function() {
            if (deferredPrompt) {
                deferredPrompt.prompt();
                const { outcome } = await deferredPrompt.userChoice;
                if (outcome === 'accepted') {
                    deferredPrompt = null;
                }
            } else {
                // Manual Instructions Validation
                const userAgent = navigator.userAgent.toLowerCase();
                 // iOS
                if (/iphone|ipad|ipod/.test(userAgent)) {
                     alert('?? Para instalar no iPhone:\n\n1. Toque no botão Compartilhar (quadrado com seta)\n2. Role para baixo e toque em "Adicionar à Tela de Início"');
                } else {
                    // Android / Other fallback
                    alert('?? Para instalar:\n\nToque no menu do navegador (3 pontinhos) e selecione "Instalar aplicativo" ou "Adicionar à tela inicial".');
                }
            }
        };
    </script>
    <script>
        // Configuração Global de Caminhos
        const NOTIFICATIONS_API_BASE = '<?= (strpos($_SERVER['PHP_SELF'], '/admin/') !== false ? '' : 'admin/') ?>';
    </script>
    
    <!-- Notification Modal (At body level for proper z-index) -->
    <div class="notification-dropdown" id="notificationDropdown">
        <div class="notification-header">
            <div class="notification-title">
                Notificações
                <button onclick="requestNotificationPermission()" id="btnEnableNotifications" class="notification-enable-btn" title="Ativar Notificações Push">
                    <i data-lucide="bell-ring" style="width: 12px;"></i> Ativar
                </button>
            </div>
            <button class="mark-all-read" onclick="markAllAsRead()">Marcar todas como lidas</button>
        </div>
        <div class="notification-list" id="notificationList">
            <div class="empty-state">
                <i data-lucide="bell-off" style="width: 24px; color: var(--text-muted); margin-bottom: 8px;"></i>
                <p>Nenhuma notificação nova</p>
            </div>
        </div>
        <div class="notification-footer">
            <a href="<?= (strpos($_SERVER['PHP_SELF'], '/admin/') !== false ? 'notificacoes.php' : 'admin/notificacoes.php') ?>">Ver todas as notificações</a>
        </div>
    </div>
</body>
</html>
<?php
    }

    // Renderiza cabeçalho padronizado das páginas
    function renderPageHeader($title, $subtitle = '', $rightAction = null)
    {
        global $_layoutUser;
        $isHome  = basename($_SERVER['PHP_SELF']) === 'index.php';
        $inAdmin = strpos($_SERVER['PHP_SELF'], '/admin/') !== false;
        $inApp   = strpos($_SERVER['PHP_SELF'], '/app/')   !== false;

        // Paths contextuais
        $homeLink  = $inAdmin ? 'index.php'           : ($inApp ? '../admin/index.php' : 'admin/index.php');
        $notifLink = $inAdmin ? 'notificacoes.php'    : ($inApp ? '../admin/notificacoes.php' : 'admin/notificacoes.php');
        $liderLink = $inAdmin ? 'lider.php'           : ($inApp ? '../admin/lider.php'  : 'admin/lider.php');
?>
    <!-- ===== PAGE HEADER ===== -->
    <?php if (!$isHome): ?>
    <div class="page-sub-header">
        <div class="page-sub-header-inner">

            <!-- NAV: Back / Menu -->
            <div class="page-sub-nav">
                <?php if (!$isHome): ?>
                    <button onclick="history.back()" class="page-nav-btn" title="Voltar">
                        <i data-lucide="arrow-left" width="18" height="18"></i>
                    </button>
                    <a href="<?= $homeLink ?>" class="page-nav-btn" title="Página Inicial">
                        <i data-lucide="home" width="17" height="17"></i>
                    </a>
                <?php else: ?>
                    <button onclick="window.innerWidth > 1024 ? toggleSidebarDesktop() : toggleSidebarMobile()"
                            class="page-nav-btn" title="Menu">
                        <i data-lucide="menu" width="18" height="18"></i>
                    </button>
                <?php endif; ?>
            </div>

            <!-- TITLE -->
            <div class="page-sub-title-block">
                <h1 class="page-sub-title"><?= htmlspecialchars($title) ?></h1>
                <?php if ($subtitle): ?>
                    <p class="page-sub-subtitle"><?= htmlspecialchars($subtitle) ?></p>
                <?php endif; ?>
            </div>

            <!-- ACTIONS -->
            <div class="page-sub-actions">

                <?php if ($rightAction): echo $rightAction; endif; ?>
                <?php if ($rightAction): ?><div class="header-actions-divider"></div><?php endif; ?>

                <!-- Crown: admin only -->
                <?php if (isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin'): ?>
                    <a href="<?= $liderLink ?>" class="header-action-btn btn-leader" title="Painel do Líder">
                        <i data-lucide="crown" width="17"></i>
                    </a>
                <?php endif; ?>

                <!-- Bell + Notification Dropdown -->
                <div style="position:relative;">
                    <button onclick="toggleNotifications('notifDropdown')"
                            class="header-action-btn" id="notificationBtnDesktop" title="Notificações">
                        <i data-lucide="bell" width="19"></i>
                        <span class="badge-dot" id="notificationBadgeDesktop" style="display:none;"></span>
                    </button>
                    <div class="notification-dropdown" id="notifDropdown">
                        <div class="notification-header">
                            <div class="notification-title">
                                Notificações
                                <button onclick="requestNotificationPermission()" class="notification-enable-btn"
                                        id="btnEnableNotifications">
                                    <i data-lucide="bell-ring" style="width:12px;"></i> Ativar
                                </button>
                            </div>
                            <button class="mark-all-read" onclick="markAllAsRead()">Marcar lidas</button>
                        </div>
                        <div class="notification-list">
                            <div class="empty-state">
                                <i data-lucide="bell-off" style="width:24px;"></i>
                                <p>Carregando...</p>
                            </div>
                        </div>
                        <div class="notification-footer">
                            <a href="<?= $notifLink ?>">Ver todas <i data-lucide="arrow-right" style="width:14px;"></i></a>
                        </div>
                    </div>
                </div>

                <div class="header-actions-divider"></div>

                <!-- Profile Avatar Pill + Dropdown -->
                <div style="position:relative;">
                    <button onclick="toggleProfileDropdown(event,'pageProfileDropdown')" class="user-profile-pill" title="Opções de Perfil">
                        <?php if (!empty($_layoutUser['photo'])): ?>
                            <img class="user-pill-avatar" src="<?= $_layoutUser['photo'] ?>" style="width: 28px; height: 28px; border-radius: 50%; object-fit: cover; border: 1.5px solid white; box-shadow: 0 2px 4px rgba(0, 0, 0, 0.08); flex-shrink: 0;" alt="<?= htmlspecialchars($_layoutUser['name'] ?? 'User') ?>">
                        <?php else: ?>
                            <div class="user-pill-avatar-placeholder" style="width: 28px; height: 28px; border-radius: 50%; background: linear-gradient(135deg, var(--blue-500), var(--cyan-400)); color: white; font-size: 0.75rem; font-weight: 800; display: flex; align-items: center; justify-content: center; border: 1.5px solid white; box-shadow: 0 2px 4px rgba(0, 0, 0, 0.08); flex-shrink: 0;">
                                <?= strtoupper(substr($_layoutUser['name'] ?? 'U', 0, 1)) ?>
                            </div>
                        <?php endif; ?>
                        <div class="user-pill-info">
                            <span class="user-pill-name"><?= htmlspecialchars(explode(' ', $_layoutUser['name'] ?? 'Usuário')[0]) ?></span>
                            <span class="user-pill-role"><?= (isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin') ? 'Líder' : 'Músico' ?></span>
                        </div>
                        <i data-lucide="chevron-down" class="user-pill-chevron" width="14" height="14"></i>
                    </button>
 
                    <!-- Profile Dropdown -->
                    <div id="pageProfileDropdown" class="profile-dropdown">
                        <div class="profile-header">
                            <div class="profile-avatar-container" style="width: 44px; height: 44px; border-radius: 50%; overflow: hidden; border: 2px solid white; box-shadow: 0 2px 8px rgba(37, 99, 235, 0.2); flex-shrink: 0;">
                                <img src="<?= $_layoutUser['photo'] ?? 'https://ui-avatars.com/api/?name=U&background=cbd5e1&color=fff' ?>" style="width: 100%; height: 100%; object-fit: cover;" alt="Avatar">
                            </div>
                            <div class="profile-info">
                                <div class="profile-name"><?= htmlspecialchars($_layoutUser['name'] ?? '') ?></div>
                                <div class="profile-role">Membro da Equipe</div>
                            </div>
                        </div>
                        <div style="padding:8px;">
                            <?php
                            $qsLink     = $inAdmin ? '../app/quem_somos.php' : ($inApp ? 'quem_somos.php' : 'app/quem_somos.php');
                            $perfilLink = $inAdmin ? 'perfil.php' : ($inApp ? '../admin/perfil.php' : 'admin/perfil.php');
                            $logoutPath = $inAdmin ? '../logout.php' : ($inApp ? '../../logout.php' : 'logout.php');
                            ?>
                            <a href="<?= $qsLink ?>" class="profile-menu-item">
                                <div class="icon-wrapper" style="background:var(--primary-light);color:var(--primary);">
                                    <i data-lucide="circle-help" width="16"></i>
                                </div>
                                <span>Quem somos nós?</span>
                            </a>
                            <a href="<?= $perfilLink ?>" class="profile-menu-item">
                                <div class="icon-wrapper" style="background:var(--bg-surface-alt);color:var(--text-muted);">
                                    <i data-lucide="user" width="16"></i>
                                </div>
                                <span>Meu Perfil</span>
                            </a>
                            <a href="#" onclick="openDashboardCustomization();return false;" class="profile-menu-item">
                                <div class="icon-wrapper" style="background:var(--info-bg);color:var(--info-text);">
                                    <i data-lucide="layout" width="16"></i>
                                </div>
                                <span>Acesso Rápido</span>
                            </a>
                            <?php if (isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin'): ?>
                                <a href="<?= $liderLink ?>" class="profile-menu-item">
                                    <div class="icon-wrapper" style="background:var(--warning-bg);color:var(--warning-text);">
                                        <i data-lucide="crown" width="16"></i>
                                    </div>
                                    <span>Painel do Líder</span>
                                </a>
                            <?php endif; ?>

                            <!-- Dark Mode Toggle -->
                            <div onclick="toggleThemeMode()" class="profile-menu-item" style="cursor:pointer;">
                                <div class="icon-wrapper" style="background:var(--bg-surface-alt);color:var(--text-muted);">
                                    <i data-lucide="moon" width="16"></i>
                                </div>
                                <span>Modo Escuro</span>
                                <div style="margin-left:auto;">
                                    <label class="toggle-switch-mini">
                                        <input type="checkbox" id="darkModeToggleDropdown" onchange="toggleThemeMode()">
                                        <span class="slider-mini round"></span>
                                    </label>
                                </div>
                            </div>

                            <div style="height:1px;background:var(--border-color);margin:6px 12px;"></div>

                            <a href="<?= $logoutPath ?>" class="profile-menu-item" style="color:var(--danger);">
                                <div class="icon-wrapper" style="background:var(--danger-bg);color:var(--danger);">
                                    <i data-lucide="log-out" width="16"></i>
                                </div>
                                <span style="font-weight:600;">Sair da Conta</span>
                            </a>
                        </div>
                    </div><!-- /profile-dropdown -->
                </div><!-- /profile-wrapper-container -->

            </div><!-- /page-sub-actions -->

        </div><!-- /page-sub-header-inner -->
    </div><!-- /page-sub-header -->
    <?php endif; ?>
    <!-- Dashboard Customization Modal -->
    <?php require_once __DIR__ . '/modals/dashboard-modal.php'; ?>

    <!-- MODAL DETALHES NOTIFICAÇÃO -->
    <?php require_once __DIR__ . '/modals/notification-modal.php'; ?>




        <!-- Notifications Script -->
        <script src="<?= APP_URL ?>/assets/js/notifications.js?v=<?= time() ?>"></script>
        <script src="<?= APP_URL ?>/assets/js/profile-dropdown.js?v=<?= time() ?>"></script>

        

    </body>
    </html>
<?php
}
?>
