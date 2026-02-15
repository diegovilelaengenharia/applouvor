<?php
// includes/layout.php
header('Content-Type: text/html; charset=utf-8');

// Inicia sessão se não estiver iniciada
if (session_status() === PHP_SESSION_NONE) {
    // Configurar sessão para 30 dias (backup, idealmente auth.php deve ser chamado antes)
    ini_set('session.gc_maxlifetime', 2592000);
    session_set_cookie_params([
        'lifetime' => 2592000,
        'path' => '/',
        'secure' => true,
        'httponly' => true,
        'samesite' => 'Strict'
    ]);
    session_start();
}

require_once 'db.php';

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
                $userPhoto = '../assets/uploads/' . $userPhoto;
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
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
        <title>App Louvor PIB</title>

        <!-- Fonte Inter (Google Fonts) -->
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">

        <!-- Open Graph / WhatsApp Sharing -->
        <meta property="og:type" content="website">
        <meta property="og:title" content="App Louvor PIB Oliveira">
        <meta property="og:description" content="Gestão de escalas, repertório e ministério de louvor da PIB Oliveira.">
        <meta property="og:image" content="https://app.piboliveira.com.br/assets/img/logo_pib_black.png"> <!-- Ajuste para URL absoluta real quando poss+¡vel -->
        <meta property="og:url" content="https://app.piboliveira.com.br/">
        
        <!-- PWA Meta Tags -->
        <meta name="theme-color" content="#376ac8">
        <meta name="apple-mobile-web-app-capable" content="yes">
        <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
        <meta name="apple-mobile-web-app-title" content="App Louvor PIB">
        <meta name="mobile-web-app-capable" content="yes">
        <meta name="view-transition" content="same-origin">
        <link rel="manifest" href="/manifest.json">
        <link rel="apple-touch-icon" href="../assets/img/logo_pib_black.png">

        <!-- Google Material Icons -->
        <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200" />
        
        <!-- Font Awesome (Legacy Support) -->
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

        <!-- +ìcones Lucide -->
        <script src="https://unpkg.com/lucide@latest"></script>

        <!-- Semantic Design System & App Main CSS -->
        <!-- Semantic Design System & App Main CSS -->
        <!-- APP URL for JS logic -->
        <script>const APP_URL = '<?= APP_URL ?>';</script>

        <?php
        // Cache Busting Inteligente
        $pathMain = __DIR__ . '/../assets/css/app-main.css';
        $pathTheme = __DIR__ . '/../assets/css/theme-premium.css';
        $verMain = file_exists($pathMain) ? filemtime($pathMain) : time();
        $verTheme = file_exists($pathTheme) ? filemtime($pathTheme) : time();
        ?>

        <!-- Main CSS (Absolute Path) -->
        <link rel="stylesheet" href="<?= APP_URL ?>/assets/css/app-main.css?v=<?= $verMain ?>">
        <link rel="stylesheet" href="<?= APP_URL ?>/assets/css/theme-premium.css?v=<?= $verTheme ?>">

        <!-- CRITICAL INLINE CSS - Header Premium Design -->
        <style>
            /* === HEADER PRO MAX DESIGN SYSTEM === */
            
            /* Root Variables for Header */
            :root {
                --header-height-desktop: 72px;
                --header-height-mobile: 64px;
                --header-bg-glass: rgba(255, 255, 255, 0.85);
                --header-border: rgba(226, 232, 240, 0.6);
                --header-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.02), 0 2px 4px -1px rgba(0, 0, 0, 0.02);
                --primary-gradient: linear-gradient(135deg, #4f46e5 0%, #7c3aed 100%);
            }

            body.dark-mode {
                --header-bg-glass: rgba(15, 23, 42, 0.85);
                --header-border: rgba(51, 65, 85, 0.5);
                --header-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.3);
            }

            /* Main Header Container */
            header.app-page-header,
            .app-page-header,
            .modern-header {
                height: var(--header-height-desktop) !important;
                background: var(--header-bg-glass) !important;
                backdrop-filter: blur(16px) saturate(180%) !important;
                -webkit-backdrop-filter: blur(16px) saturate(180%) !important;
                border-bottom: 1px solid var(--header-border) !important;
                box-shadow: var(--header-shadow) !important;
                
                position: sticky !important;
                top: 0 !important;
                z-index: 1000 !important;
                width: 100% !important;
                display: flex !important;
                align-items: center !important;
                justify-content: center !important;
                transition: all 0.3s ease !important;
            }

            /* Inner Content Container */
            .header-container {
                display: flex !important;
                align-items: center !important;
                justify-content: space-between !important;
                width: 100% !important;
                max-width: 1400px !important;
                margin: 0 auto !important;
                padding: 0 24px !important;
                height: 100% !important;
            }

            /* Layout Sections */
            .header-left, 
            .header-actions {
                display: flex !important;
                align-items: center !important;
                gap: 12px !important;
            }

            .header-center {
                position: absolute;
                left: 50%;
                transform: translateX(-50%);
                text-align: center;
                pointer-events: none; /* Deixa cliques passarem se sobrepor algo */
            }

            /* Typography */
            .header-title-group {
                display: flex;
                flex-direction: column;
                align-items: center;
                justify-content: center;
                pointer-events: auto;
            }

            .header-title {
                margin: 0 !important;
                font-family: 'Inter Tight', 'Inter', sans-serif !important;
                font-weight: 800 !important;
                font-size: 1.25rem !important;
                line-height: 1.2 !important;
                letter-spacing: -0.02em !important;
                
                /* Gradient Text */
                background: var(--primary-gradient) !important;
                -webkit-background-clip: text !important;
                -webkit-text-fill-color: transparent !important;
                background-clip: text !important;
            }

            .header-subtitle {
                margin: 0 !important;
                font-size: 0.75rem !important;
                font-weight: 600 !important;
                color: #64748b !important;
                letter-spacing: 0.05em !important;
                text-transform: uppercase !important;
            }

            body.dark-mode .header-subtitle { color: #94a3b8 !important; }

            /* Pro Buttons */
            .header-action-btn {
                display: flex !important;
                align-items: center !important;
                justify-content: center !important;
                width: 40px !important;
                height: 40px !important;
                border-radius: 50% !important;
                background: transparent !important;
                border: 1px solid transparent !important;
                color: #64748b !important;
                cursor: pointer !important;
                transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1) !important;
                position: relative !important;
            }

            .header-action-btn:hover {
                background: rgba(241, 245, 249, 0.8) !important;
                color: #334155 !important;
                transform: translateY(-1px) !important;
                box-shadow: 0 2px 4px rgba(0,0,0,0.05) !important;
            }

            body.dark-mode .header-action-btn { color: #cbd5e1 !important; }
            body.dark-mode .header-action-btn:hover {
                background: rgba(51, 65, 85, 0.5) !important;
                color: #f1f5f9 !important;
            }

            /* Special Buttons */
            .btn-leader {
                color: #d97706 !important; /* Amber 600 */
                background: rgba(254, 243, 199, 0.5) !important; /* Amber 50 */
            }
            .btn-leader:hover {
                background: rgba(254, 243, 199, 1) !important;
                transform: scale(1.05) !important;
            }

            /* Nav Buttons (Left) */
            .header-nav-btn {
                display: flex !important;
                align-items: center !important;
                justify-content: center !important;
                width: 36px !important;
                height: 36px !important;
                border-radius: 10px !important; /* Squircle */
                color: #64748b !important;
                transition: all 0.2s ease !important;
            }
            .header-nav-btn:hover {
                background: rgba(241, 245, 249, 1) !important;
                color: #0f172a !important;
            }

            /* Avatar Button */
            .profile-avatar-btn {
                padding: 0 !important;
                width: 42px !important;
                height: 42px !important;
                border-radius: 50% !important;
                border: 2px solid rgba(255, 255, 255, 0.5) !important;
                box-shadow: 0 2px 5px rgba(0,0,0,0.05) !important;
                overflow: hidden !important;
                transition: all 0.2s ease !important;
                cursor: pointer !important;
            }

            .profile-avatar-btn:hover {
                transform: scale(1.05) !important;
                box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.2) !important;
                border-color: #6366f1 !important;
            }
            
            .profile-avatar-btn img {
                width: 100% !important;
                height: 100% !important;
                object-fit: cover !important;
            }

            /* Notification Badge */
            .badge-dot {
                position: absolute !important;
                top: 10px !important;
                right: 10px !important;
                width: 8px !important;
                height: 8px !important;
                background: #ef4444 !important;
                border-radius: 50% !important;
                border: 1.5px solid #fff !important;
                box-shadow: 0 0 0 1px rgba(239, 68, 68, 0.2) !important;
                display: block !important; /* Ensure it is block, JS toggles display:none */
            }
            
            /* Mobile Header Specifics */
            header.mobile-header,
            .mobile-header {
                height: var(--header-height-mobile) !important;
                background: var(--header-bg-glass) !important;
                backdrop-filter: blur(16px) saturate(180%) !important;
                -webkit-backdrop-filter: blur(16px) saturate(180%) !important;
                border-bottom: 1px solid var(--header-border) !important;
                display: flex !important;
                align-items: center !important;
                justify-content: space-between !important;
                padding: 0 16px !important;
            }

            .mobile-header .page-title {
                font-family: 'Inter Tight', sans-serif !important;
                font-weight: 700 !important;
                font-size: 1.1rem !important;
                background: var(--primary-gradient) !important;
                -webkit-background-clip: text !important;
                -webkit-text-fill-color: transparent !important;
                background-clip: text !important;
            }
        </style>

        <!-- Theme Toggle Script (Critical: Must load immediately) -->
        <script src="<?= APP_URL ?>/assets/js/theme-toggle.js?v=<?= time() ?>"></script>

        <!-- INLINE FUNCTIONS - Garantia de Funcionamento -->
        <script>
            // === TOGGLE NOTIFICATIONS ===
            window.toggleNotifications = function(dropdownId) {
                const dropdown = document.getElementById(dropdownId);
                if (!dropdown) {
                    console.error('[ERROR] Dropdown não encontrado:', dropdownId);
                    return;
                }
                
                // Toggle visibility
                const isVisible = dropdown.classList.contains('active');
                
                // Fechar todos os dropdowns primeiro
                document.querySelectorAll('.notification-dropdown, .profile-dropdown').forEach(d => {
                    d.classList.remove('active');
                });
                
                // Abrir se estava fechado
                if (!isVisible) {
                    dropdown.classList.add('active');
                }
            };
            
            // === TOGGLE PROFILE ===
            window.toggleProfileDropdown = function(event, dropdownId) {
                if (event) event.stopPropagation();
                
                const dropdown = document.getElementById(dropdownId);
                if (!dropdown) {
                    console.error('[ERROR] Dropdown perfil não encontrado:', dropdownId);
                    return;
                }
                
                // Toggle visibility
                const isVisible = dropdown.classList.contains('active');
                
                // Fechar todos os dropdowns primeiro
                document.querySelectorAll('.notification-dropdown, .profile-dropdown').forEach(d => {
                    d.classList.remove('active');
                });
                
                // Abrir se estava fechado
                if (!isVisible) {
                    dropdown.classList.add('active');
                }
            };
            
            // === FECHAR AO CLICAR FORA ===
            document.addEventListener('click', function(e) {
                // Se clicou fora de qualquer dropdown ou botão, fechar tudo
                if (!e.target.closest('.notification-dropdown') && 
                    !e.target.closest('.profile-dropdown') &&
                    !e.target.closest('.notification-btn') &&
                    !e.target.closest('.profile-avatar-btn') &&
                    !e.target.closest('.header-action-btn')) {
                    
                    document.querySelectorAll('.notification-dropdown, .profile-dropdown').forEach(d => {
                        d.classList.remove('active');
                    });
                }
            });
        </script>
        
    </head>

    <body>

        <!-- Incluir Sidebar -->
        <!-- Incluir Sidebar -->
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
            <!-- Header Mobile (Só visível em telas menores) -->
            <header class="mobile-header">
                <?php
                // Logic to determine if it's the home page
                $isHome = basename($_SERVER['PHP_SELF']) == 'index.php';
                ?>
                
                <?php if ($isHome): ?>
                    <button class="btn-menu-trigger" onclick="toggleSidebar()">
                        <i data-lucide="menu" style="width: 24px; height: 24px;"></i>
                    </button>
                <?php else: ?>
                    <div style="display: flex; gap: 4px; align-items: center; margin-left: -12px;">
                        <?php if($backUrl): ?>
                            <a href="<?= $backUrl ?>" class="btn-menu-trigger" style="margin-left: 0; display:flex; align-items:center; justify-content:center; text-decoration:none; color:inherit;" title="Voltar">
                                <i data-lucide="arrow-left" style="width: 24px; height: 24px;"></i>
                            </a>
                        <?php else: ?>
                            <button onclick="history.back()" class="btn-menu-trigger" style="margin-left: 0;" title="Voltar">
                                <i data-lucide="arrow-left" style="width: 24px; height: 24px;"></i>
                            </button>
                        <?php endif; ?>
                        <a href="index.php" class="btn-menu-trigger" style="margin-left: 0; text-decoration: none;" title="Início">
                            <i data-lucide="home" style="width: 24px; height: 24px;"></i>
                        </a>
                    </div>
                <?php endif; ?>
                <div class="page-title"><?= htmlspecialchars($title) ?></div>

                <!-- Right Side: Stats + L+¡der + Avatar -->
                <div style="display: flex; align-items: center; gap: 8px;">
                    <!-- Stats Button (Repertorio only) -->


                    <!-- Stats Button (Escalas only) -->




                    <!-- Notification Button -->
                    <div style="position: relative;">
                        <button 
                            class="notification-btn" 
                            onclick="toggleNotifications('notificationDropdown')" 
                            id="notificationBtn">
                            <i data-lucide="bell"></i>
                            <span class="notification-badge" id="notificationBadge" style="display: none;">0</span>
                        </button>
                    </div>

                    <?php if (isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin'): ?>
                        <a href="lider.php" class="admin-crown-btn">
                            <i data-lucide="crown"></i>
                        </a>
                    <?php endif; ?>
                    

                    <!-- Mobile Profile Avatar -->
                    <div style="position: relative;">
                        <button 
                            onclick="toggleProfileDropdown(event, 'mobileProfileDropdown')" 
                            class="profile-avatar-btn">
                            <?php if (isset($_layoutUser['photo']) && $_layoutUser['photo']): ?>
                                <img src="<?= $_layoutUser['photo'] ?>" alt="User" style="width: 100%; height: 100%; object-fit: cover;">
                            <?php else: ?>
                                <i data-lucide="user" style="width: 20px; height: 20px;"></i>
                            <?php endif; ?>
                        </button>

                        <!-- Mobile Dropdown -->
                        <div id="mobileProfileDropdown" class="profile-dropdown">
                            <!-- Header do Card -->
                            <div class="profile-header">
                                <div class="profile-avatar-container">
                                    <img src="<?= $_layoutUser['photo'] ?? 'https://ui-avatars.com/api/?name=U&background=cbd5e1&color=fff' ?>" alt="Avatar">
                                </div>
                                <div class="profile-info">
                                    <div class="profile-name"><?= htmlspecialchars($_layoutUser['name']) ?></div>
                                    <div class="profile-role">Membro da Equipe</div>
                                </div>
                            </div>
                            <!-- Compacted Header Mobile -->

                            <div style="padding: 8px;">
                                <?php
                                $qsLink = (strpos($_SERVER['PHP_SELF'], '/admin/') !== false) ? '../app/quem_somos.php' : 'quem_somos.php';
                                if (strpos($_SERVER['PHP_SELF'], '/app/') !== false) {
                                     // Already in app, so just quem_somos.php works. 
                                     // The previous check covers admin. If in root, 'app/quem_somos.php'?
                                     // If we are in root index.php, we are likely redirected or included.
                                     // simpler: 
                                     if(file_exists('app/quem_somos.php')) $qsLink = 'app/quem_somos.php';
                                     elseif(file_exists('../app/quem_somos.php')) $qsLink = '../app/quem_somos.php';
                                     else $qsLink = 'quem_somos.php'; // fallback for app dir
                                }
                                ?>
                                <a href="<?= $qsLink ?>" class="profile-menu-item">
                                    <div class="icon-wrapper">
                                        <i data-lucide="circle-help" style="width: 16px; height: 16px;"></i>
                                    </div>
                                    <span>Quem somos nós?</span>
                                </a>

                                <a href="perfil.php" class="profile-menu-item">
                                    <div class="icon-wrapper">
                                        <i data-lucide="user" style="width: 16px; height: 16px;"></i>
                                    </div>
                                    <span>Meu Perfil</span>
                                </a>

                                <a href="#" onclick="openDashboardCustomization(); return false;" class="profile-menu-item">
                                    <div class="icon-wrapper">
                                        <i data-lucide="layout" style="width: 16px; height: 16px;"></i>
                                    </div>
                                    <span>Acesso Rápido</span>
                                </a>

                                <?php if (isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin'): ?>
                                    <a href="lider.php" class="lider-menu-item">
                                        <div class="icon-wrapper">
                                            <i data-lucide="crown" style="width: 16px; height: 16px;"></i>
                                        </div>
                                        <span>Painel do Líder</span>
                                    </a>
                                <?php endif; ?>

                                <!-- Dark Mode Toggle -->
                                <div onclick="toggleThemeMode()" class="profile-menu-item" style="cursor: pointer;">
                                    <div class="icon-wrapper">
                                        <i data-lucide="moon" style="width: 16px; height: 16px;"></i>
                                    </div>
                                    <span>Modo Escuro</span>
                                    <div style="margin-left: auto;">
                                        <label class="toggle-switch-mini" style="width: 30px; height: 16px;">
                                            <input type="checkbox" id="darkModeToggleMobile" onchange="toggleThemeMode()">
                                            <span class="slider-mini round"></span>
                                        </label>
                                    </div>
                                </div>

                                <div style="height: 1px; background: var(--border-color); margin: 6px 12px;"></div>

                                <a href="../logout.php" class="profile-menu-item logout-item">
                                    <div class="icon-wrapper">
                                        <i data-lucide="log-out" style="width: 16px; height: 16px;"></i>
                                    </div>
                                    <span>Sair da Conta</span>
                                </a>
                            </div>
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

        

        <!-- Bottom Navigation & Submenus (Mobile Only) -->
        

        <!-- Overlay de Fundo -->
        <div id="bs-overlay" class="bs-overlay" onclick="closeAllSheets()"></div>

        <script>
            function closeAllSheets() {
                const overlay = document.getElementById('bs-overlay');
                if (overlay) overlay.classList.remove('active');
                
                // Fecha menus
                document.querySelectorAll('.bottom-sheet').forEach(el => el.classList.remove('active'));

                // Remove active
                document.querySelectorAll('.b-nav-item').forEach(el => el.classList.remove('active'));
            }
        </script>


        <!-- Inicializar +ìcones -->
        <script>
            lucide.createIcons();

            // Registrar PWA Service Worker
            if ('serviceWorker' in navigator) {
                window.addEventListener('load', () => {
                    navigator.serviceWorker.register('<?= APP_URL ?>/sw.js')
                        .then(registration => console.log('SW registrado com sucesso:', registration.scope))
                        .catch(err => console.log('Falha ao registrar SW:', err));
                });
            }

            // ... (Restante do script mantido, apenas adicionando verifica+º+úo para evitar duplicidade de listeners se necess+írio)

            // Adicionar classe animate-in aos cards principais automaticamente
            document.addEventListener('DOMContentLoaded', () => {
                const cards = document.querySelectorAll('.card, .stats-card, .notice-card');
                cards.forEach((card, index) => {
                    card.classList.add('animate-in');
                    card.style.animationDelay = `${index * 0.1}s`;
                });
                // Sidebar Swipe Logic (Vibe Coding)
                const sidebar = document.getElementById('app-sidebar');
                const appContent = document.getElementById('app-content');
                if (!sidebar) return; // Seguran+ºa

                let touchStartX = 0;
                let touchEndX = 0;

                // ... (Mantendo a l+¦gica de swipe anterior) ...

                document.addEventListener('touchstart', e => {
                    touchStartX = e.changedTouches[0].screenX;
                }, {
                    passive: true
                });

                document.addEventListener('touchend', e => {
                    touchEndX = e.changedTouches[0].screenX;
                    handleSidebarSwipe();
                }, {
                    passive: true
                });

                function handleSidebarSwipe() {
                    const swipeThreshold = 80; // Sensibilidade do swipe
                    const diff = touchEndX - touchStartX;
                    const isSidebarOpen = sidebar.classList.contains('active');
                    const isChatPage = window.location.pathname.includes('chat.php');

                    // Swipe Right (Esquerda -> Direita): Abrir Sidebar
                    // Apenas se começar perto da borda esquerda (< 50px) e sidebar fechada
                    if (diff > swipeThreshold && touchStartX < 50 && !isSidebarOpen) {
                        toggleSidebar();
                    }
                    
                    // Swipe Left (Direita -> Esquerda): Fechar Sidebar se aberta...
                    if (diff < -swipeThreshold && isSidebarOpen) {
                        toggleSidebar();
                        return;
                    }

                    // Se sidebar fechada, deixar o Drawer Logic (mais abaixo) lidar com o Chat
                }

                function toggleSidebar() {
                    sidebar.classList.toggle('active');
                    let overlay = document.getElementById('sidebar-overlay');
                    if (!overlay) {
                        overlay = document.createElement('div');
                        overlay.id = 'sidebar-overlay';
                        overlay.style.cssText = 'position:fixed;top:0;left:0;right:0;bottom:0;background:rgba(0,0,0,0.5);z-index:99;opacity:0;transition:opacity 0.3s;';
                        overlay.onclick = toggleSidebar;
                        document.body.appendChild(overlay);
                        setTimeout(() => overlay.style.opacity = '1', 10);
                    } else {
                        if (sidebar.classList.contains('active')) {
                            overlay.style.display = 'block';
                            setTimeout(() => overlay.style.opacity = '1', 10);
                        } else {
                            overlay.style.opacity = '0';
                            setTimeout(() => overlay.style.display = 'none', 300);
                        }
                    }
                }

            });
        </script>
        
        <!-- Sidebar & Gestures Script -->

        
        <!-- Main Script & Gestures (Legacy includes kept) -->
        <script src="<?= APP_URL ?>/assets/js/main.js"></script>
        <script src="<?= APP_URL ?>/assets/js/gestures.js"></script>
    <!-- PWA Install Script (Global) -->
    <script>
        // Check for Service Worker Support
        if ('serviceWorker' in navigator) {
            window.addEventListener('load', () => {
                navigator.serviceWorker.register('/sw.js')
                    .then(reg => console.log('SW Registered!', reg))
                    .catch(err => console.log('SW Registration Failed', err));
            });
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

    // Nova função para cabeçalhos padronizados (Clean Header)
    function renderPageHeader($title, $subtitle = 'Louvor PIB Oliveira', $rightAction = null)
    {
        global $_layoutUser;
        $isHome = basename($_SERVER['PHP_SELF']) == 'index.php';
?>
    <!-- Modern Page Header (Pro Max) -->
    <header class="desktop-only-header app-page-header modern-header">
        
        <div class="header-container">
            <!-- Left: Navigation (Buttons) -->
            <div class="header-left">
                <?php if (!$isHome): ?>
                    <button onclick="history.back()" class="header-nav-btn" title="Voltar">
                        <i data-lucide="arrow-left" width="20"></i>
                    </button>
                    <a href="index.php" class="header-nav-btn header-home-btn" title="Início">
                        <i data-lucide="home" width="20"></i>
                    </a>
                <?php endif; ?>
            </div>

            <!-- Center: Title & Subtitle (Absolute Centered) -->
            <div class="header-center">
                <div class="header-title-group">
                    <h1 class="header-title"><?= htmlspecialchars($title) ?></h1>
                    <?php if ($subtitle): ?>
                        <p class="header-subtitle"><?= htmlspecialchars($subtitle) ?></p>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Right: Actions (Buttons) -->
            <div class="header-actions">
                
                <!-- Líder Button (Admin only) -->
                <?php if (isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin'): ?>
                    <?php
                        // Determine path relative to current location
                        $paths = [
                            'admin' => (strpos($_SERVER['PHP_SELF'], '/admin/') !== false) ? 'lider.php' : 'admin/lider.php',
                            'app' => (strpos($_SERVER['PHP_SELF'], '/app/') !== false) ? '../admin/lider.php' : 'admin/lider.php'
                        ];
                        $liderPath = $paths['admin']; // default approximation
                        if(strpos($_SERVER['PHP_SELF'], '/admin/') !== false) $liderPath = 'lider.php';
                        elseif(strpos($_SERVER['PHP_SELF'], '/app/') !== false) $liderPath = '../admin/lider.php';
                    ?>
                    <a href="<?= $liderPath ?>" class="header-action-btn btn-leader" title="Painel do Líder">
                        <i data-lucide="crown" width="18"></i>
                    </a>
                <?php endif; ?>

                <!-- Custom Action (if provided) -->
                <?php if (isset($rightAction) && $rightAction): ?>
                    <?= $rightAction ?>
                <?php endif; ?>

                <!-- Notification Button -->
                <div style="position: relative;">
                    <button onclick="toggleNotifications('notificationDropdownDesktop')" class="header-action-btn" id="notificationBtnDesktop" title="Notificações">
                        <i data-lucide="bell" width="20"></i>
                        <span class="badge-dot" id="notificationBadgeDesktop" style="display: none;"></span>
                    </button>
                    
                    <!-- Desktop Dropdown (Maintained relative structure) -->
                    <div class="notification-dropdown" id="notificationDropdownDesktop">
                        <div class="notification-header">
                            <div class="notification-title">
                                Notificações
                                <button onclick="requestNotificationPermission()" class="notification-enable-btn" title="Ativar Notificações Push" id="btnEnableNotifications">
                                    <i data-lucide="bell-ring" style="width: 12px;"></i> Ativar
                                </button>
                            </div>
                            <button class="mark-all-read" onclick="markAllAsRead()">Marcar todas como lidas</button>
                        </div>
                        <div class="notification-list">
                        <div class="empty-state">
                            <i data-lucide="bell-off" style="width: 24px; color: var(--text-muted); margin-bottom: 8px;"></i>
                            <p>Carregando...</p>
                        </div>
                    </div>
                    <div class="notification-footer">
                        <a href="<?= (strpos($_SERVER['PHP_SELF'], '/admin/') !== false ? 'notificacoes.php' : 'admin/notificacoes.php') ?>">
                            Ver central completa
                            <i data-lucide="arrow-right" style="width: 14px;"></i>
                        </a>
                    </div>
                </div>
            </div>

            <!-- Perfil Avatar (Button) -->
            <div style="position: relative;">
                <button onclick="toggleProfileDropdown(event, 'headerProfileDropdown')" class="profile-avatar-btn">
                    <?php if (isset($_layoutUser['photo']) && $_layoutUser['photo']): ?>
                        <img src="<?= $_layoutUser['photo'] ?>" alt="User" class="img-fluid">
                    <?php else: ?>
                        <div style="width:100%; height:100%; background:#e2e8f0; display:flex; align-items:center; justify-content:center; color:#64748b;">
                            <i data-lucide="user" width="20"></i>
                        </div>
                    <?php endif; ?>
                </button>

                <!-- Dropdown Card -->
                <div id="headerProfileDropdown" class="profile-dropdown">
                    <!-- Header do Card -->
                    <div class="profile-header">
                        <div class="profile-avatar-container">
                            <img src="<?= $_layoutUser['photo'] ?? 'https://ui-avatars.com/api/?name=U&background=cbd5e1&color=fff' ?>" alt="Avatar">
                        </div>
                        <div class="profile-info">
                            <div class="profile-name"><?= htmlspecialchars($_layoutUser['name']) ?></div>
                            <div class="profile-role">Membro da Equipe</div>
                        </div>
                    </div>

                    <div style="padding: 8px;">
                        <?php
                        $qsLink = (strpos($_SERVER['PHP_SELF'], '/admin/') !== false) ? '../app/quem_somos.php' : 'quem_somos.php';
                        if (strpos($_SERVER['PHP_SELF'], '/app/') !== false) {
                            // Default works
                        } else if (strpos($_SERVER['PHP_SELF'], '/admin/') === false) {
                            if(file_exists('app/quem_somos.php')) $qsLink = 'app/quem_somos.php';
                        }
                        
                        // Logic for dynamic links based on context (admin vs app vs root)
                        $inAdmin = (strpos($_SERVER['PHP_SELF'], '/admin/') !== false);
                        $inApp   = (strpos($_SERVER['PHP_SELF'], '/app/') !== false);
                        
                        $perfilLink = $inAdmin ? 'perfil.php' : ($inApp ? '../admin/perfil.php' : 'admin/perfil.php');
                        $liderLink  = $inAdmin ? 'lider.php'  : ($inApp ? '../admin/lider.php'  : 'admin/lider.php');
                        ?>
                        
                        <a href="<?= $qsLink ?>" class="profile-menu-item">
                            <div class="icon-wrapper" style="background: var(--primary-light); color: var(--primary);">
                                <i data-lucide="circle-help" width="16"></i>
                            </div>
                            <span style="font-weight: 500;">Quem somos nós?</span>
                        </a>

                        <a href="<?= $perfilLink ?>" class="profile-menu-item">
                            <div class="icon-wrapper" style="background: var(--bg-surface-alt); color: var(--text-muted);">
                                <i data-lucide="user" width="16"></i>
                            </div>
                            <span style="font-weight: 500;">Meu Perfil</span>
                        </a>

                        <a href="#" onclick="openDashboardCustomization(); return false;" class="profile-menu-item">
                            <div class="icon-wrapper" style="background: var(--info-bg); color: var(--info-text);">
                                <i data-lucide="layout" width="16"></i>
                            </div>
                            <span style="font-weight: 500;">Acesso Rápido</span>
                        </a>

                        <?php if (isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin'): ?>
                            <a href="<?= $liderLink ?>" class="profile-menu-item">
                                <div class="icon-wrapper" style="background: var(--warning-bg); color: var(--warning-text);">
                                    <i data-lucide="crown" width="16"></i>
                                </div>
                                <span style="font-weight: 500;">Painel do Líder</span>
                            </a>
                        <?php endif; ?>

                        <!-- Dark Mode Toggle -->
                        <div onclick="toggleThemeMode()" class="profile-menu-item" style="cursor: pointer;">
                            <div class="icon-wrapper" style="background: var(--bg-surface-alt); color: var(--text-muted);">
                                <i data-lucide="moon" width="16"></i>
                            </div>
                            <span style="font-weight: 500;">Modo Escuro</span>
                            <div style="margin-left: auto;">
                                <label class="toggle-switch-mini" style="width: 30px; height: 16px;">
                                    <input type="checkbox" id="darkModeToggleDropdown" onchange="toggleThemeMode()">
                                    <span class="slider-mini round"></span>
                                </label>
                            </div>
                        </div>

                        <div style="height: 1px; background: var(--border-color); margin: 6px 12px;"></div>

                        <a href="../logout.php" class="profile-menu-item" style="color: var(--danger);">
                            <div class="icon-wrapper" style="background: var(--danger-bg); color: var(--danger);">
                                <i data-lucide="log-out" width="16"></i>
                            </div>
                            <span style="font-weight: 600;">Sair da Conta</span>
                        </a>
                    </div>
                </div>
            </div>


        </div>
    </header>
    <!-- Dashboard Customization Modal -->
    <div id="dashboardCustomizationModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; z-index: 3000; background: rgba(0,0,0,0.5); backdrop-filter: blur(4px); align-items: center; justify-content: center;">
        <div style="background: var(--bg-surface); padding: 24px; border-radius: 16px; width: 90%; max-width: 500px; max-height: 80vh; overflow-y: auto; box-shadow: var(--shadow-lg); animation: fadeInUp 0.2s cubic-bezier(0.16, 1, 0.3, 1);">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                <h3 style="margin: 0; font-size: 1.25rem;">Personalizar Acesso Rápido</h3>
                <button onclick="closeDashboardCustomization()" style="background: transparent; border: none; cursor: pointer; color: var(--text-muted);">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
                </button>
            </div>
            
            <p style="color: var(--text-muted); font-size: 0.9rem; margin-bottom: 24px;">
                Selecione os atalhos que deseja exibir no seu painel.
            </p>
            
            <form id="dashboardCustomizationForm">
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 24px;">
                    <?php
                        // Ensure dashboard_cards is loaded
                        if (file_exists(__DIR__ . '/dashboard_cards.php')) {
                            require_once __DIR__ . '/dashboard_cards.php';
                        } elseif (file_exists(__DIR__ . '/../includes/dashboard_cards.php')) {
                            require_once __DIR__ . '/../includes/dashboard_cards.php';
                        }
                        
                        if (function_exists('getAllAvailableCards')):
                            $allCards = getAllAvailableCards();
                            
                            // Tentar buscar configurações do usuário
                            $enabledCards = [];
                            if (isset($_SESSION['user_id'])) {
                                global $pdo;
                                if ($pdo) {
                                    try {
                                        $stmt = $pdo->prepare("SELECT card_id FROM user_dashboard_settings WHERE user_id = ? AND is_visible = 1");
                                        $stmt->execute([$_SESSION['user_id']]);
                                        $enabledCards = $stmt->fetchAll(PDO::FETCH_COLUMN);
                                    } catch (Exception $e) {}
                                }
                            }
                            
                            // Default if empty
                            if (empty($enabledCards)) {
                                $enabledCards = array_keys($allCards); 
                            }
                            
                            foreach($allCards as $id => $card):
                                $checked = in_array($id, $enabledCards) ? 'checked' : '';
                    ?>
                    <label style="
                        display: flex; align-items: center; gap: 10px; padding: 12px; 
                        border: 1px solid var(--border-color); border-radius: 12px; 
                        cursor: pointer; transition: all 0.2s; background: var(--bg-body);
                    ">
                        <input type="checkbox" name="cards[]" value="<?= $id ?>" <?= $checked ?> style="width: 16px; height: 16px; accent-color: var(--primary);">
                        <div style="
                            width: 28px; height: 28px; border-radius: 8px; 
                            background: <?= $card['bg'] ?>; color: <?= $card['color'] ?>;
                            display: flex; align-items: center; justify-content: center;
                        ">
                            <i data-lucide="<?= $card['icon'] ?>" style="width: 16px;"></i>
                        </div>
                        <span style="font-size: 0.85rem; font-weight: 500; color: var(--text-main);"><?= $card['title'] ?></span>
                    </label>
                    <?php 
                            endforeach;
                        endif; 
                    ?>
                </div>
                
                <div style="display: flex; gap: 12px; justify-content: flex-end; padding-top: 16px; border-top: 1px solid var(--border-color);">
                    <button type="button" onclick="closeDashboardCustomization()" style="
                        padding: 10px 20px; border: 1px solid var(--red-300); 
                        background: var(--red-50); border-radius: 8px; cursor: pointer; 
                        color: var(--red-700); font-weight: 600; transition: all 0.2s;
                    " onmouseover="this.style.background='var(--red-100)'" onmouseout="this.style.background='var(--red-50)'">Cancelar</button>
                    <button type="submit" style="
                        padding: 10px 20px; background: var(--primary); 
                        color: white; border: none; border-radius: 8px; 
                        cursor: pointer; font-weight: 600; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
                    ">Salvar Alterações</button>
                </div>
            </form>
        </div>
    </div>

    <!-- MODAL DETALHES NOTIFICAÇÃO -->
    <div id="notificationDetailModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; z-index: 3050; background: rgba(0,0,0,0.5); backdrop-filter: blur(4px); align-items: center; justify-content: center;">
        <div style="background: var(--bg-surface); border-radius: 16px; width: 90%; max-width: 500px; box-shadow: var(--shadow-lg); animation: fadeInUp 0.2s cubic-bezier(0.16, 1, 0.3, 1); overflow: hidden; display: flex; flex-direction: column;">
            <div style="padding: 16px 20px; border-bottom: 0px solid var(--border-color); display: flex; justify-content: space-between; align-items: center; background: var(--bg-surface);">
                <h3 style="margin: 0; font-size: 0.75rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase; letter-spacing: 1px;">Notificação</h3>
                <button onclick="closeNotificationDetail()" style="background: transparent; border: none; cursor: pointer; color: var(--text-muted); display: flex;">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
                </button>
            </div>
            <div style="padding: 0 24px 24px 24px;">
                <div style="display: flex; gap: 16px; margin-bottom: 20px; align-items: flex-start;">
                    <div id="notifDetailIcon" style="width: 48px; height: 48px; border-radius: 14px; display: flex; align-items: center; justify-content: center; flex-shrink: 0;"></div>
                    <div>
                        <h4 id="notifDetailTitle" style="margin: 0 0 6px 0; font-size: 1.1rem; font-weight: 700; color: var(--text-main); line-height: 1.3;"></h4>
                        <span id="notifDetailDate" style="font-size: 0.85rem; color: var(--text-muted);"></span>
                    </div>
                </div>
                <div style="background: var(--bg-body); padding: 16px; border-radius: 12px; margin-bottom: 20px;">
                    <div id="notifDetailMessage" style="font-size: 0.95rem; line-height: 1.6; color: var(--text-main); white-space: pre-wrap;"></div>
                </div>
                <div style="display: flex; gap: 12px; justify-content: flex-end;">
                     <button onclick="closeNotificationDetail()" style="padding: 12px 24px; border: 1px solid var(--border-color); background: transparent; border-radius: 10px; cursor: pointer; color: var(--text-main); font-weight: 600;">Fechar</button>
                     <a id="notifDetailLink" href="#" style="padding: 12px 24px; background: var(--primary); color: white; border-radius: 10px; text-decoration: none; font-weight: 600; display: none; align-items: center; gap: 8px; box-shadow: var(--shadow-sm);">
                        Ver Completo <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"></path><polyline points="15 3 21 3 21 9"></polyline><line x1="10" y1="14" x2="21" y2="3"></line></svg>
                     </a>
                </div>
            </div>
        </div>
    </div>

    <script>
        function openDashboardCustomization() {
            const modal = document.getElementById('dashboardCustomizationModal');
            if (modal) {
                modal.style.display = 'flex';
                // Reiniciar Lucide icons se necess+írio
                if (window.lucide) lucide.createIcons();
            }
        }

        function closeDashboardCustomization() {
            const modal = document.getElementById('dashboardCustomizationModal');
            if (modal) modal.style.display = 'none';
        }

        document.getElementById('dashboardCustomizationForm')?.addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const btnSubmit = this.querySelector('button[type="submit"]');
            const originalText = btnSubmit.textContent;
            btnSubmit.textContent = 'Salvando...';
            btnSubmit.disabled = true;
            
            const formData = new FormData(this);
            const selectedCards = [];
            
            formData.getAll('cards[]').forEach((id, index) => {
                selectedCards.push({
                    card_id: id,
                    is_visible: true,
                    display_order: index + 1
                });
            });
            
            // Determinar API URL correto
            const isAdmin = window.location.pathname.includes('/admin/');
            const apiUrl = isAdmin ? 'api/save_dashboard_settings.php' : 'admin/api/save_dashboard_settings.php';
            
            try {
                const response = await fetch(apiUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({ cards: selectedCards })
                });
                
                const result = await response.json();
                
                if (result.success) {
                    location.reload();
                } else {
                    alert('Erro ao salvar: ' + (result.message || 'Erro desconhecido'));
                    btnSubmit.textContent = originalText;
                    btnSubmit.disabled = false;
                }
            } catch (error) {
                console.error(error);
                alert('Erro na comunica+º+úo com o servidor.');
                btnSubmit.textContent = originalText;
                btnSubmit.disabled = false;
            }
        });
        
        // Close on click outside
        document.getElementById('dashboardCustomizationModal')?.addEventListener('click', function(e) {
            if (e.target === this) closeDashboardCustomization();
        });
    </script>


        <!-- Notifications Script -->
        <script src="<?= APP_URL ?>/assets/js/notifications.js?v=<?= time() ?>"></script>
        <script src="<?= APP_URL ?>/assets/js/profile-dropdown.js?v=<?= time() ?>"></script>

        

    </body>
    </html>
<?php
}
?>
