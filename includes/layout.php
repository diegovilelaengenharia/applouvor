<?php
// includes/layout.php

// Inicia sess+úo se n+úo estiver iniciada
if (session_status() === PHP_SESSION_NONE) {
    // Configurar sess+úo para 30 dias (backup, idealmente auth.php deve ser chamado antes)
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

    // --- L+¦gica de Usu+írio Global (Movida do Sidebar) ---
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
            $currentUser = ['name' => $_SESSION['user_name'] ?? 'Usu+írio', 'phone' => '', 'avatar' => null];
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
        <meta property="og:description" content="Gest+úo de escalas, repert+¦rio e minist+®rio de louvor da PIB Oliveira.">
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
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200" />

        <!-- +ìcones Lucide -->
        <script src="https://unpkg.com/lucide@latest"></script>

        <!-- Semantic Design System & App Main CSS -->
        <link rel="stylesheet" href="../assets/css/app-main.css?v=<?= time() ?>">

        <!-- Theme Toggle Script (Critical: Must load immediately) -->
        <script src="../assets/js/theme-toggle.js?v=<?= time() ?>"></script>

        
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

        <div id="app-content">
            <!-- Header Mobile (S+¦ vis+¡vel em telas menores) -->
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




                    <!-- L+¡der Button (Admin only) -->
                    <!-- Notification Button -->
                    <div style="position: relative;">
                        <button class="notification-btn ripple" onclick="toggleNotifications('notificationDropdown')" id="notificationBtn">
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
                        <button onclick="toggleProfileDropdown(event, 'mobileProfileDropdown')" class="profile-avatar-btn">
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

        <!-- 1. Sheet GEST+âO -->
        <div id="sheet-gestao" class="bottom-sheet">
            <div class="sheet-header">
                <div style="background: #ecfdf5; padding: 10px; border-radius: 12px; color: #047857;">
                    <i data-lucide="layout-grid"></i>
                </div>
                <div>
                    <div class="sheet-title">Gestão</div>
                    <div style="font-size: 0.85rem; color: var(--text-muted);">Administração do Ministério</div>
                </div>
            </div>
            <div class="sheet-grid">
                <a href="escalas.php" class="sheet-btn">
                    <div class="sheet-btn-icon" style="background: #d1fae5; color: #047857;">
                        <i data-lucide="calendar"></i>
                    </div>
                    Escalas
                </a>
                <a href="repertorio.php" class="sheet-btn">
                    <div class="sheet-btn-icon" style="background: #d1fae5; color: #047857;">
                        <i data-lucide="music"></i>
                    </div>
                    Repertório
                </a>
                <a href="indisponibilidade.php" class="sheet-btn">
                    <div class="sheet-btn-icon" style="background: #d1fae5; color: #047857;">
                        <i data-lucide="calendar-x"></i>
                    </div>
                    Indisponibilidades
                </a>
                <a href="agenda.php" class="sheet-btn">
                    <div class="sheet-btn-icon" style="background: #d1fae5; color: #047857;">
                        <i data-lucide="calendar-clock"></i>
                    </div>
                    Agenda
                </a>
            </div>
        </div>

        <!-- 2. Sheet ESP+ìRITO -->
        <div id="sheet-espirito" class="bottom-sheet">
            <div class="sheet-header">
                <div style="background: #eef2ff; padding: 10px; border-radius: 12px; color: #4338ca;">
                    <i data-lucide="flame"></i>
                </div>
                <div>
                    <div class="sheet-title">Espírito</div>
                    <div style="font-size: 0.85rem; color: var(--text-muted);">Vida Devocional</div>
                </div>
            </div>
            <div class="sheet-grid" style="grid-template-columns: 1fr;"> <!-- Lista +¦nica para destaque -->
                <a href="devocionais.php" class="sheet-btn" style="flex-direction: row; justify-content: start; text-align: left;">
                    <div class="sheet-btn-icon" style="background: #e0e7ff; color: #4338ca;">
                        <i data-lucide="book-open"></i>
                    </div>
                    <div>
                        <div>Devocional</div>
                        <div style="font-size: 0.75rem; font-weight: 400; color: var(--text-muted);">Sua conexão diária</div>
                    </div>
                </a>
                <a href="oracao.php" class="sheet-btn" style="flex-direction: row; justify-content: start; text-align: left;">
                    <div class="sheet-btn-icon" style="background: #e0e7ff; color: #4338ca;">
                        <i data-lucide="heart-handshake"></i>
                    </div>
                    <div>
                        <div>Oração</div>
                        <div style="font-size: 0.75rem; font-weight: 400; color: var(--text-muted);">Intercessão e gratidão</div>
                    </div>
                </a>
                <a href="leitura.php" class="sheet-btn" style="flex-direction: row; justify-content: start; text-align: left;">
                    <div class="sheet-btn-icon" style="background: #e0e7ff; color: #4338ca;">
                        <i data-lucide="scroll"></i>
                    </div>
                    <div>
                        <div>Leitura Bíblica</div>
                        <div style="font-size: 0.75rem; font-weight: 400; color: var(--text-muted);">Plano anual</div>
                    </div>
                </a>
            </div>
        </div>




        <!-- Barra de Navega+º+úo Fixa -->
        <div class="bottom-nav-container">
            <nav class="bottom-nav-bar">

                

                <!-- Botão HOME (Primeiro) com Efeito 3D Pulsante -->
                <a href="index.php" class="b-nav-item home-3d" onclick="closeAllSheets()">
                    <div class="b-nav-icon-wrapper">
                        <i data-lucide="home"></i>
                    </div>
                    <span>Início</span>
                </a>

                <!-- Botão GERAL (Gestão ? AZUL) -->
                <button class="b-nav-item" onclick="toggleSheet('sheet-gestao', this)" style="color: #2563eb;">
                    <div class="b-nav-icon-wrapper" style="background: #eff6ff;">
                        <i data-lucide="layout-grid"></i>
                    </div>
                    <span>Geral</span>
                </button>

                <!-- Botão ESPÍRITO (Espiritual ? VERDE) -->
                <button class="b-nav-item" onclick="toggleSheet('sheet-espirito', this)" style="color: #059669;">
                    <div class="b-nav-icon-wrapper" style="background: #ecfdf5;">
                        <i data-lucide="flame"></i>
                    </div>
                    <span>Espírito</span>
                </button>

                <!-- Botão AVISOS (Comunicação ? ROXO) -->
                <a href="avisos.php" class="b-nav-item" onclick="closeAllSheets()" style="color: #7c3aed;">
                    <div class="b-nav-icon-wrapper" style="background: #f5f3ff;">
                        <i data-lucide="bell"></i>
                    </div>
                    <span>Avisos</span>
                </a>



            </nav>
        </div>

        <script>
            function toggleSheet(sheetId, btn) {
                const sheet = document.getElementById(sheetId);
                const overlay = document.getElementById('bs-overlay');
                const isOpen = sheet.classList.contains('open');

                // 1. Fechar todos primeiro
                closeAllSheets();

                // 2. Se n+úo estava aberto, abrir o clicado
                if (!isOpen) {
                    sheet.classList.add('open');
                    overlay.classList.add('active');

                    // Highlight Active Button
                    if (btn) btn.classList.add('active');

                    // Haptic Feedback (Vibe)
                    if (navigator.vibrate) navigator.vibrate(10);
                }
            }

            function closeAllSheets() {
                document.querySelectorAll('.bottom-sheet').forEach(el => el.classList.remove('open'));
                document.getElementById('bs-overlay').classList.remove('active');
                document.querySelectorAll('.b-nav-item').forEach(el => el.classList.remove('active'));
            }
        </script>


        <!-- Inicializar +ìcones -->
        <script>
            lucide.createIcons();

            // Registrar PWA Service Worker
            if ('serviceWorker' in navigator) {
                window.addEventListener('load', () => {
                    navigator.serviceWorker.register('/sw.js')
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
        <script src="../assets/js/main.js"></script>
        <script src="../assets/js/gestures.js"></script>
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

    // Nova fun+º+úo para cabe+ºalhos padronizados (Clean Header)
    function renderPageHeader($title, $subtitle = 'Louvor PIB Oliveira', $rightAction = null)
    {
        global $_layoutUser;
        $isHome = basename($_SERVER['PHP_SELF']) == 'index.php';
?>
    <header class="desktop-only-header app-page-header">
        

        <div style="display: flex; align-items: center; gap: 4px;">
            <?php if (!$isHome): ?>
                <button onclick="history.back()" class="ripple" title="Voltar" style="
                width: 40px; height: 40px; border-radius: 50%; border: none; background: transparent; 
                display: flex; align-items: center; justify-content: center; color: var(--text-muted); cursor: pointer;
            ">
                    <i data-lucide="arrow-left" style="width: 22px;"></i>
                </button>

                <a href="index.php" class="ripple" title="Navega+º+úo Principal" style="
                width: 40px; height: 40px; border-radius: 50%; border: none; background: transparent; 
                display: flex; align-items: center; justify-content: center; color: var(--primary); cursor: pointer;
            ">
                    <i data-lucide="home" style="width: 22px;"></i>
                </a>
            <?php endif; ?>
        </div>

        <div style="flex: 1; text-align: center; overflow: hidden; white-space: nowrap; text-overflow: ellipsis;">
            <h1 style="margin: 0; font-size: 1.1rem; font-weight: 700; color: var(--text-main);"><?= htmlspecialchars($title) ?></h1>
            <?php if ($subtitle): ?>
                <p style="margin: 0; font-size: 0.75rem; color: var(--text-muted);"><?= htmlspecialchars($subtitle) ?></p>
            <?php endif; ?>
        </div>

        <!-- Direita: Ações + Líder + Perfil -->
        <div style="display: flex; align-items: center; justify-content: flex-end; gap: 8px; min-width: 88px;">

            <!-- Líder Button (Admin only) - Desktop -->
            <?php if (isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin'): ?>
                <?php
                    // Recalculate if not in same scope (though header is included, vars scope might vary if inside function)
                    // renderPageHeader is a function, so we need to define logic inside it
                    $inAdminHead = (strpos($_SERVER['PHP_SELF'], '/admin/') !== false);
                    $inAppHead   = (strpos($_SERVER['PHP_SELF'], '/app/') !== false);
                    $liderLinkHead = $inAdminHead ? 'lider.php' : ($inAppHead ? '../admin/lider.php' : 'admin/lider.php');
                ?>
                <a href="<?= $liderLinkHead ?>" class="admin-crown-btn ripple" title="Painel do Líder">
                    <i data-lucide="crown" style="width: 20px;"></i>
                </a>
            <?php endif; ?>

            <!-- Ação da Página (se houver) -->
            <?php if (isset($rightAction) && $rightAction): ?>
                <?= $rightAction ?>
            <?php endif; ?>

            <!-- Notification Button (Bell) -->
            <div style="position: relative;">
                <button onclick="toggleNotifications('notificationDropdownDesktop')" class="notification-btn ripple" id="notificationBtnDesktop" title="Notificações">
                    <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M6 8a6 6 0 0 1 12 0c0 7 3 9 3 9H3s3-2 3-9"></path>
                        <path d="M10.3 21a1.94 1.94 0 0 0 3.4 0"></path>
                    </svg>
                    <span class="notification-badge" id="notificationBadgeDesktop" style="display: none;">0</span>
                </button>
                
                <!-- Desktop Dropdown -->
                
                <!-- Desktop Dropdown -->
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
                        <!-- JS vai preencher aqui -->
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

            

            <!-- Perfil Dropdown (Card Moderno) -->
            <div style="position: relative; margin-left: 4px;">
                <button onclick="toggleProfileDropdown(event, 'headerProfileDropdown')" class="profile-avatar-btn ripple">
                    <?php if (isset($_layoutUser['photo']) && $_layoutUser['photo']): ?>
                        <img src="<?= $_layoutUser['photo'] ?>" alt="User" style="width: 100%; height: 100%; object-fit: cover;">
                    <?php else: ?>
                        <i data-lucide="user" style="width: 20px; height: 20px;"></i>
                    <?php endif; ?>
                </button>

                <!-- Dropdown Card -->
                <div id="headerProfileDropdown" style="
                    display: none; position: absolute; top: 60px; right: 0; 
                    background: var(--bg-surface); border-radius: 16px; 
                    box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1), 0 8px 10px -6px rgba(0, 0, 0, 0.1); 
                    min-width: 220px; z-index: 100; border: 1px solid var(--border-color); overflow: hidden;
                    animation: fadeInUp 0.2s cubic-bezier(0.16, 1, 0.3, 1);
                    transform-origin: top right;
                ">
                    <!-- Header do Card -->
                    <div style="padding: 12px 16px; display: flex; align-items: center; gap: 12px; background: #ffffff; border-bottom: 1px solid var(--border-color);">
                        <div style="width: 42px; height: 42px; border-radius: 50%; overflow: hidden; border: 2px solid white; box-shadow: 0 2px 4px rgba(0,0,0,0.05); flex-shrink: 0;">
                            <img src="<?= $_layoutUser['photo'] ?? 'https://ui-avatars.com/api/?name=U&background=cbd5e1&color=fff' ?>" alt="Avatar" style="width: 100%; height: 100%; object-fit: cover;">
                        </div>
                        <div style="flex: 1; min-width: 0;">
                            <div style="font-weight: 700; color: var(--text-main); font-size: 0.95rem; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;"><?= htmlspecialchars($_layoutUser['name']) ?></div>
                            <div style="font-size: 0.75rem; color: #047857; font-weight: 500;">Membro da Equipe</div>
                        </div>
                    </div>
                    <!-- Compacted Header Desktop -->

                            <div style="padding: 8px;">
                                <?php
                                $qsLink = (strpos($_SERVER['PHP_SELF'], '/admin/') !== false) ? '../app/quem_somos.php' : 'quem_somos.php';
                                if (strpos($_SERVER['PHP_SELF'], '/app/') !== false) {
                                     // Default works
                                } else if (strpos($_SERVER['PHP_SELF'], '/admin/') === false) {
                                     // Probably root
                                     if(file_exists('app/quem_somos.php')) $qsLink = 'app/quem_somos.php';
                                }
                                ?>
                                <a href="<?= $qsLink ?>" style="display: flex; align-items: center; gap: 10px; padding: 8px 12px; text-decoration: none; color: var(--text-main); font-size: 0.85rem; border-radius: 8px; transition: background 0.2s;" onmouseover="this.style.background='var(--bg-body)'" onmouseout="this.style.background='transparent'">
                                    <div style="background: #e0e7ff; padding: 6px; border-radius: 6px; display: flex; color: #4338ca;">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                            <circle cx="12" cy="12" r="10"></circle>
                                            <path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3"></path>
                                            <path d="M12 17h.01"></path>
                                        </svg>
                                    </div>
                                    <span style="font-weight: 500;">Quem somos nós?</span>
                                </a>

                                <?php
                                // Logic for dynamic links based on context (admin vs app vs root)
                                $inAdmin = (strpos($_SERVER['PHP_SELF'], '/admin/') !== false);
                                $inApp   = (strpos($_SERVER['PHP_SELF'], '/app/') !== false);
                                
                                $perfilLink = $inAdmin ? 'perfil.php' : ($inApp ? '../admin/perfil.php' : 'admin/perfil.php');
                                $liderLink  = $inAdmin ? 'lider.php'  : ($inApp ? '../admin/lider.php'  : 'admin/lider.php');
                                ?>

                                <a href="<?= $perfilLink ?>" style="display: flex; align-items: center; gap: 10px; padding: 8px 12px; text-decoration: none; color: var(--text-main); font-size: 0.85rem; border-radius: 8px; transition: background 0.2s;" onmouseover="this.style.background='var(--bg-body)'" onmouseout="this.style.background='transparent'">
                                    <div style="background: #f1f5f9; padding: 6px; border-radius: 6px; display: flex; color: #64748b;">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                            <path d="M19 21v-2a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v2" />
                                            <circle cx="12" cy="7" r="4" />
                                        </svg>
                                    </div>
                                    <span style="font-weight: 500;">Meu Perfil</span>
                                </a>

                                <a href="#" onclick="openDashboardCustomization(); return false;" style="display: flex; align-items: center; gap: 10px; padding: 8px 12px; text-decoration: none; color: var(--text-main); font-size: 0.85rem; border-radius: 8px; transition: background 0.2s;" onmouseover="this.style.background='var(--bg-body)'" onmouseout="this.style.background='transparent'">
                                    <div style="background: #eef2ff; padding: 6px; border-radius: 6px; display: flex; color: #4338ca;">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                            <rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect>
                                            <line x1="3" y1="9" x2="21" y2="9"></line>
                                            <line x1="9" y1="21" x2="9" y2="9"></line>
                                        </svg>
                                    </div>
                                    <span style="font-weight: 500;">Acesso Rápido</span>
                                </a>

                                <?php if (isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin'): ?>
                                    <a href="<?= $liderLink ?>" style="display: flex; align-items: center; gap: 10px; padding: 8px 12px; text-decoration: none; color: var(--text-main); font-size: 0.85rem; border-radius: 8px; transition: background 0.2s;" onmouseover="this.style.background='var(--bg-body)'" onmouseout="this.style.background='transparent'">
                                        <div style="background: #fff7ed; padding: 6px; border-radius: 6px; display: flex; color: #d97706;">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                <path d="m2 4 3 12h14l3-12-6 7-4-3-4 3-6-7z" />
                                                <path d="M5 16v4h14v-4" />
                                            </svg>
                                        </div>
                                        <span style="font-weight: 500;">Painel do Líder</span>
                                    </a>
                                <?php endif; ?>

                                <!-- Dark Mode Toggle -->
                                <div onclick="toggleThemeMode()" style="display: flex; align-items: center; gap: 10px; padding: 8px 12px; cursor: pointer; color: var(--text-main); font-size: 0.85rem; border-radius: 8px; transition: background 0.2s;" onmouseover="this.style.background='var(--bg-body)'" onmouseout="this.style.background='transparent'">
                                    <div style="background: #f1f5f9; padding: 6px; border-radius: 6px; display: flex; color: #64748b;">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                            <path d="M12 3a6 6 0 0 0 9 9 9 9 0 1 1-9-9Z" />
                                        </svg>
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

                                <a href="../logout.php" style="display: flex; align-items: center; gap: 10px; padding: 8px 12px; text-decoration: none; color: #ef4444; font-size: 0.85rem; border-radius: 8px; transition: background 0.2s;" onmouseover="this.style.background='#fef2f2'" onmouseout="this.style.background='transparent'">
                                    <div style="background: #fee2e2; padding: 6px; border-radius: 6px; display: flex; color: #ef4444;">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                            <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4" />
                                            <polyline points="16 17 21 12 16 7" />
                                            <line x1="21" x2="9" y1="12" y2="12" />
                                        </svg>
                                    </div>
                                    <span style="font-weight: 600;">Sair da Conta</span>
                                </a>
                            </div>
                </div>
            </div>

            <script>
                function toggleProfileDropdown(e, dropdownId = 'headerProfileDropdown') {
                    e.stopPropagation();
                    const dropdown = document.getElementById(dropdownId);
                    if (!dropdown) return;

                    const isVisible = dropdown.style.display === 'block';

                    // Fechar outros
                    document.querySelectorAll('[id$="Dropdown"]').forEach(d => d.style.display = 'none');

                    if (!isVisible) {
                        dropdown.style.display = 'block';
                    }
                }

                document.addEventListener('click', function(e) {
                    const headerDropdown = document.getElementById('headerProfileDropdown');
                    const mobileDropdown = document.getElementById('mobileProfileDropdown');
                    
                    if (headerDropdown && headerDropdown.style.display === 'block') {
                        headerDropdown.style.display = 'none';
                    }
                    if (mobileDropdown && mobileDropdown.style.display === 'block') {
                        mobileDropdown.style.display = 'none';
                    }
                });

            </script>

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
        <script src="../assets/js/notifications.js?v=<?= time() ?>"></script>

        
    </body>
    </html>
<?php
}
?>
