<?php
// includes/layout.php

// Inicia sess├úo se n├úo estiver iniciada
if (session_status() === PHP_SESSION_NONE) {
    // Configurar sess├úo para 30 dias (backup, idealmente auth.php deve ser chamado antes)
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

    // --- L├│gica de Usu├írio Global (Movida do Sidebar) ---
    $userId = $_SESSION['user_id'] ?? 1;
    $currentUser = null;
    $userPhoto = null;

    if ($userId) {
        try {
            // Tenta buscar foto tamb├®m
            $stmtUser = $pdo->prepare("SELECT name, phone, avatar FROM users WHERE id = ?");
            $stmtUser->execute([$userId]);
            $currentUser = $stmtUser->fetch(PDO::FETCH_ASSOC);
        } catch (Exception $e) { /* Ignorar erros de coluna */
        }

        if (!$currentUser) {
            $currentUser = ['name' => $_SESSION['user_name'] ?? 'Usu├írio', 'phone' => '', 'avatar' => null];
        }

        // Avatar Logic
        if (!empty($currentUser['avatar'])) {
            $userPhoto = $currentUser['avatar'];
            if (strpos($userPhoto, 'http') === false && strpos($userPhoto, 'assets') === false && strpos($userPhoto, 'uploads') === false) {
                $userPhoto = '../assets/uploads/' . $userPhoto;
            }
        } else {
            $userNameForAvatar = $currentUser['name'] ?? 'U';
            $userPhoto = 'https://ui-avatars.com/api/?name=' . urlencode($userNameForAvatar) . '&background=dcfce7&color=166534';
        }
    }
    // Compartilhar com globais ou session para acesso no header
    // Uma forma suja mas eficaz para templates ├® usar global ou re-passar. 
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
        <meta property="og:description" content="Gest├úo de escalas, repert├│rio e minist├®rio de louvor da PIB Oliveira.">
        <meta property="og:image" content="https://app.piboliveira.com.br/assets/img/logo_pib_black.png"> <!-- Ajuste para URL absoluta real quando poss├¡vel -->
        <meta property="og:url" content="https://app.piboliveira.com.br/">
        
        <!-- PWA Meta Tags -->
        <meta name="theme-color" content="#047857">
        <meta name="apple-mobile-web-app-capable" content="yes">
        <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
        <meta name="apple-mobile-web-app-title" content="App Louvor PIB">
        <meta name="mobile-web-app-capable" content="yes">
        <meta name="view-transition" content="same-origin">
        <link rel="manifest" href="/manifest.json">
        <link rel="apple-touch-icon" href="assets/images/logo-black.png">

        <!-- ├ìcones Lucide -->
        <script src="https://unpkg.com/lucide@latest"></script>

        <style>
            /* --- DESIGN SYSTEM 2.5 (Compact & Mobile First) --- */
            :root {
                /* Cores Principais - Emerald (Sofisticado) */
                --primary: #047857;
                --primary-hover: #065f46;
                --primary-light: #d1fae5;
                --primary-subtle: #ecfdf5;

                /* Tons Neutros */
                --bg-body: #f8fafc;
                --bg-surface: #ffffff;
                --text-main: #334155;
                --text-muted: #64748b;
                --border-color: #e2e8f0;

                /* Espa├ºamento Compacto Mobile First */
                --touch-target: 44px;
                /* M├¡nimo aceit├ível reduzido */
                --radius-md: 8px;
                --radius-lg: 12px;
                /* Mais quadrado, mais moderno */
                --shadow-sm: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
                --shadow-md: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
            }

            /* ... Keyframes anteriores ... */
            @keyframes fadeInUp {
                from {
                    opacity: 0;
                    transform: translateY(10px);
                }

                to {
                    opacity: 1;
                    transform: translateY(0);
                }
            }

            .animate-in {
                animation: fadeInUp 0.5s cubic-bezier(0.16, 1, 0.3, 1) forwards;
                opacity: 0;
            }

            .animate-in:nth-child(1) {
                animation-delay: 0.05s;
            }

            .animate-in:nth-child(2) {
                animation-delay: 0.1s;
            }

            .animate-in:nth-child(3) {
                animation-delay: 0.15s;
            }

            .animate-in:nth-child(4) {
                animation-delay: 0.2s;
            }

            /* View Transition Fixes */
            ::view-transition-old(root),
            ::view-transition-new(root) {
                animation-duration: 0.3s;
            }

            /* Global Dark Mode */
            body.dark-mode {
                --bg-body: #0f172a;
                --bg-surface: #1e293b;
                --text-main: #f1f5f9;
                --text-muted: #94a3b8;
                --border-color: #334155;
                --primary-light: #064e3b;
                --primary-subtle: #064e3b;
            }

            * {
                box-sizing: border-box;
                -webkit-tap-highlight-color: transparent;
            }

            body {
                font-family: 'Inter', sans-serif;
                margin: 0;
                background-color: var(--bg-body);
                color: var(--text-main);
                font-size: 0.85rem;
                /* ~13.6px - Leitura densa e profissional */
                line-height: 1.5;
                padding-bottom: 80px;
                /* Prote├º├úo Floating Button */
            }

            /* Tipografia Compacta */
            h1,
            h2,
            h3,
            h4 {
                color: var(--text-main);
                margin: 0;
                letter-spacing: -0.025em;
                /* T├¡tulos mais tight */
            }

            h1 {
                font-size: 1.35rem;
                font-weight: 700;
            }

            h2 {
                font-size: 1.15rem;
                font-weight: 700;
            }

            h3 {
                font-size: 1rem;
                font-weight: 600;
            }

            h4 {
                font-size: 0.9rem;
                font-weight: 600;
            }

            /* Main Content Ajustado */
            #app-content {
                padding: 12px;
                /* Padding reduzido mobile */
                min-height: 100vh;
                margin-left: 0;
                transition: margin-left 0.3s ease;
            }

            @media (min-width: 1025px) {
                #app-content {
                    margin-left: 190px;
                }
            }

            /* Header Mobile */
            .mobile-header {
                display: none;
                align-items: center;
                gap: 12px;
                padding: 10px 14px;
                background: var(--bg-surface);
                position: sticky;
                top: 0;
                z-index: 90;
                border-bottom: 1px solid var(--border-color);
                box-shadow: var(--shadow-sm);
                margin: -20px -20px 24px -20px;
            }

            .btn-menu-trigger {
                width: 44px;
                height: 44px;
                /* Touch Target Grande */
                background: transparent;
                border: none;
                padding: 0;
                margin-left: -12px;
                cursor: pointer;
                color: var(--text-main);
                display: flex;
                align-items: center;
                justify-content: center;
                border-radius: 50%;
                transition: background 0.2s;
            }

            .btn-menu-trigger:active {
                background: var(--border-color);
            }

            .page-title {
                font-size: 1.125rem;
                /* 18px */
                font-weight: 700;
                color: var(--text-main);
                flex: 1;
            }

            @media (max-width: 1024px) {
                .mobile-header {
                    display: flex;
                }

                #app-content {
                    margin-left: 0 !important;
                }
            }

            /* Utilit├írios Universais */
            .ripple {
                position: relative;
                overflow: hidden;
                transform: translate3d(0, 0, 0);
            }

            .ripple:after {
                content: "";
                display: block;
                position: absolute;
                width: 100%;
                height: 100%;
                top: 0;
                left: 0;
                pointer-events: none;
                background-image: radial-gradient(circle, #fff 10%, transparent 10.01%);
                background-repeat: no-repeat;
                background-position: 50%;
                transform: scale(10, 10);
                opacity: 0;
                transition: transform .5s, opacity 1s;
            }

            .ripple:active:after {
                transform: scale(0, 0);
                opacity: 0.2;
                transition: 0s;
            }

            /* Animations (Vibe Coding) */
            @keyframes fadeInUp {
                from {
                    opacity: 0;
                    transform: translateY(10px);
                }

                to {
                    opacity: 1;
                    transform: translateY(0);
                }
            }

            .animate-in {
                animation: fadeInUp 0.5s cubic-bezier(0.16, 1, 0.3, 1) forwards;
                opacity: 0;
                /* Come├ºa invis├¡vel */
            }

            /* Stagger delays for multiple items */
            .animate-in:nth-child(1) {
                animation-delay: 0.1s;
            }

            .animate-in:nth-child(2) {
                animation-delay: 0.15s;
            }

            .animate-in:nth-child(3) {
                animation-delay: 0.2s;
            }

            .animate-in:nth-child(4) {
                animation-delay: 0.25s;
            }

            /* View Transition Fixes */
            ::view-transition-old(root),
            ::view-transition-new(root) {
                animation-duration: 0.3s;
            }

            /* Mini Toggle Switch for Profile Dropdown */
            .toggle-switch-mini {
                position: relative;
                display: inline-block;
                width: 36px;
                height: 20px;
            }

            .toggle-switch-mini input {
                opacity: 0;
                width: 0;
                height: 0;
            }

            .slider-mini {
                position: absolute;
                cursor: pointer;
                top: 0;
                left: 0;
                right: 0;
                bottom: 0;
                background-color: #cbd5e1;
                transition: .3s;
                border-radius: 34px;
            }

            .slider-mini:before {
                position: absolute;
                content: "";
                height: 14px;
                width: 14px;
                left: 3px;
                bottom: 3px;
                background-color: white;
                transition: .3s;
                border-radius: 50%;
            }

            input:checked+.slider-mini {
                background-color: var(--primary);
            }

            input:checked+.slider-mini:before {
                transform: translateX(16px);
            }
        </style>
    </head>

    <body>

        <!-- Incluir Sidebar -->
        <?php include_once 'sidebar.php'; ?>

        <div id="app-content">
            <!-- Header Mobile (S├│ vis├¡vel em telas menores) -->
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
                        <button onclick="history.back()" class="btn-menu-trigger" style="margin-left: 0;" title="Voltar">
                            <i data-lucide="arrow-left" style="width: 24px; height: 24px;"></i>
                        </button>
                        <a href="index.php" class="btn-menu-trigger" style="margin-left: 0; text-decoration: none;" title="Início">
                            <i data-lucide="home" style="width: 24px; height: 24px;"></i>
                        </a>
                    </div>
                <?php endif; ?>
                <div class="page-title"><?= htmlspecialchars($title) ?></div>

                <!-- Right Side: Stats + L├¡der + Avatar -->
                <div style="display: flex; align-items: center; gap: 8px;">
                    <!-- Stats Button (Repertorio only) -->


                    <!-- Stats Button (Escalas only) -->




                    <!-- L├¡der Button (Admin only) -->
                    <?php if (isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin'): ?>
                        <a href="lider.php" class="ripple" style="display: flex; align-items: center; justify-content: center; width: 40px; height: 40px; background: linear-gradient(135deg, #dc2626, #ef4444); border-radius: 10px; text-decoration: none; box-shadow: 0 2px 8px rgba(220, 38, 38, 0.3);">
                            <i data-lucide="crown" style="color: white; width: 20px;"></i>
                        </a>
                    <?php endif; ?>

                    <!-- Settings Button (Home only) -->
                    <?php if ($isHome): ?>
                        <button onclick="openCustomizationModal()" class="ripple" style="display: flex; align-items: center; justify-content: center; width: 40px; height: 40px; background: var(--bg-surface); border: 1px solid var(--border-color); border-radius: 10px; flex-shrink:0; cursor: pointer; color: var(--text-muted); box-shadow: var(--shadow-sm);">
                            <i data-lucide="settings" style="width: 20px;"></i>
                        </button>
                    <?php endif; ?>

                    <!-- Leitura Config Button (Leitura Only) -->
                    <?php if (strpos($_SERVER['PHP_SELF'], 'leitura.php') !== false): ?>
                        <button onclick="openConfig()" class="ripple" style="display: flex; align-items: center; justify-content: center; width: 40px; height: 40px; background: var(--bg-surface); border: 1px solid var(--border-color); border-radius: 10px; flex-shrink:0; cursor: pointer; color: var(--text-muted);">
                            <i data-lucide="settings" style="width: 20px;"></i>
                        </button>
                    <?php endif; ?>

                    <!-- Mobile Profile Avatar -->
                    <div style="position: relative;">
                        <button onclick="toggleProfileDropdown(event, 'mobileProfileDropdown')" style="width: 40px; height: 40px; padding: 0; border: 2px solid white; box-shadow: 0 0 0 3px rgba(34, 197, 94, 0.25), 0 2px 8px rgba(34, 197, 94, 0.2); background: var(--bg-surface); cursor: pointer; border-radius: 50%; overflow: hidden; display: flex; align-items: center; justify-content: center; transition: transform 0.2s;">
                            <?php if (isset($_layoutUser['photo']) && $_layoutUser['photo']): ?>
                                <img src="<?= $_layoutUser['photo'] ?>" alt="User" style="width: 100%; height: 100%; object-fit: cover;">
                            <?php else: ?>
                                <i data-lucide="user" style="width: 20px; height: 20px; color: var(--text-muted);"></i>
                            <?php endif; ?>
                        </button>

                        <!-- Mobile Dropdown -->
                        <div id="mobileProfileDropdown" style="
                            display: none; position: absolute; top: 54px; right: 0; 
                            background: var(--bg-surface); border-radius: 16px; 
                            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1), 0 8px 10px -6px rgba(0, 0, 0, 0.1); 
                            min-width: 260px; z-index: 2000; border: 1px solid var(--border-color); overflow: hidden;
                            animation: fadeInUp 0.2s cubic-bezier(0.16, 1, 0.3, 1);
                            transform-origin: top right;
                        ">
                            <!-- Header do Card -->
                            <div style="padding: 24px 20px; text-align: center; background: linear-gradient(to bottom, #f8fafc, #ffffff); border-bottom: 1px solid var(--border-color);">
                                <div style="width: 72px; height: 72px; margin: 0 auto 12px auto; border-radius: 50%; overflow: hidden; border: 3px solid white; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);">
                                    <img src="<?= $_layoutUser['photo'] ?? 'https://ui-avatars.com/api/?name=U&background=cbd5e1&color=fff' ?>" alt="Avatar" style="width: 100%; height: 100%; object-fit: cover;">
                                </div>
                                <div style="font-weight: 800; color: var(--text-main); font-size: 1.05rem; margin-bottom: 4px;"><?= htmlspecialchars($_layoutUser['name']) ?></div>
                                <span style="background: #d1fae5; color: #065f46; font-size: 0.7rem; padding: 2px 10px; border-radius: 12px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px;">Membro da Equipe</span>
                            </div>

                            <!-- Menu Itens -->
                            <div style="padding: 12px;">
                                <a href="perfil.php" style="display: flex; align-items: center; gap: 12px; padding: 12px 16px; text-decoration: none; color: var(--text-main); font-size: 0.9rem; border-radius: 8px; transition: background 0.2s;" onmouseover="this.style.background='var(--bg-body)'" onmouseout="this.style.background='transparent'">
                                    <div style="background: #f1f5f9; padding: 8px; border-radius: 8px; display: flex; color: #64748b;">
                                        <i data-lucide="user" style="width: 18px; height: 18px;"></i>
                                    </div>
                                    <span style="font-weight: 500;">Meu Perfil</span>
                                </a>

                                <?php if (isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin'): ?>
                                    <a href="lider.php" style="display: flex; align-items: center; gap: 12px; padding: 12px 16px; text-decoration: none; color: var(--text-main); font-size: 0.9rem; border-radius: 8px; transition: background 0.2s;" onmouseover="this.style.background='var(--bg-body)'" onmouseout="this.style.background='transparent'">
                                        <div style="background: #fff7ed; padding: 8px; border-radius: 8px; display: flex; color: #d97706;">
                                            <i data-lucide="crown" style="width: 18px; height: 18px;"></i>
                                        </div>
                                        <span style="font-weight: 500;">Painel do Líder</span>
                                    </a>
                                <?php endif; ?>

                                <!-- Dark Mode Toggle -->
                                <div onclick="toggleThemeMode()" style="display: flex; align-items: center; gap: 12px; padding: 12px 16px; cursor: pointer; color: var(--text-main); font-size: 0.9rem; border-radius: 8px; transition: background 0.2s;" onmouseover="this.style.background='var(--bg-body)'" onmouseout="this.style.background='transparent'">
                                    <div style="background: #f1f5f9; padding: 8px; border-radius: 8px; display: flex; color: #64748b;">
                                        <i data-lucide="moon" style="width: 18px; height: 18px;"></i>
                                    </div>
                                    <span style="font-weight: 500;">Modo Escuro</span>
                                    <div style="margin-left: auto;">
                                        <label class="toggle-switch-mini">
                                            <input type="checkbox" id="darkModeToggleMobile" onchange="toggleThemeMode()">
                                            <span class="slider-mini round"></span>
                                        </label>
                                    </div>
                                </div>

                                <div style="height: 1px; background: var(--border-color); margin: 8px 12px;"></div>

                                <a href="../logout.php" style="display: flex; align-items: center; gap: 12px; padding: 12px 16px; text-decoration: none; color: #ef4444; font-size: 0.9rem; border-radius: 8px; transition: background 0.2s;" onmouseover="this.style.background='#fef2f2'" onmouseout="this.style.background='transparent'">
                                    <div style="background: #fee2e2; padding: 8px; border-radius: 8px; display: flex; color: #ef4444;">
                                        <i data-lucide="log-out" style="width: 18px; height: 18px;"></i>
                                    </div>
                                    <span style="font-weight: 600;">Sair da Conta</span>
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
        <style>
            .bottom-nav-container {
                position: fixed;
                bottom: 0;
                left: 0;
                right: 0;
                display: flex;
                flex-direction: column;
                z-index: 1000;
                pointer-events: none;
                /* Permite clicar no conte├║do atr├ís quando menus est├úo fechados */
            }

            /* Main Bar */
            .bottom-nav-bar {
                background: rgba(255, 255, 255, 0.9);
                backdrop-filter: blur(12px);
                -webkit-backdrop-filter: blur(12px);
                border-top: 1px solid var(--border-color);
                padding: 8px 12px 8px 12px;
                /* Reduced bottom padding */
                padding-bottom: max(12px, env(safe-area-inset-bottom));
                /* Respect notch but default to tight */
                display: flex;
                justify-content: space-around;
                align-items: center;
                pointer-events: auto;
                box-shadow: 0 -4px 20px rgba(0, 0, 0, 0.05);
            }

            /* Nav Items */
            .b-nav-item {
                display: flex;
                flex-direction: column;
                align-items: center;
                gap: 6px;
                background: none;
                border: none;
                color: var(--text-muted);
                font-size: 0.75rem;
                font-weight: 600;
                cursor: pointer;
                transition: transform 0.2s, color 0.2s;
                padding: 4px 12px;
                border-radius: 12px;
            }

            .b-nav-item.active {
                color: var(--primary);
            }

            .b-nav-item:active {
                transform: scale(0.95);
            }

            .b-nav-icon-wrapper {
                position: relative;
                width: 28px;
                height: 28px;
                display: flex;
                align-items: center;
                justify-content: center;
                border-radius: 10px;
                transition: background 0.3s;
            }

            .b-nav-item.active .b-nav-icon-wrapper {
                background: var(--primary-light);
            }

            .b-nav-item svg {
                width: 22px;
                height: 22px;
                stroke-width: 2px;
            }

            /* Bottom Sheet / Submenu */
            .bottom-sheet {
                position: fixed;
                bottom: 0;
                /* Fixa no fundo */
                left: 0;
                right: 0;
                background: var(--bg-surface);
                border-radius: 24px 24px 0 0;
                padding: 24px 20px 100px 20px;
                /* Padding bottom extra para n├úo ficar escondido atr├ís da barra */
                box-shadow: 0 -10px 40px rgba(0, 0, 0, 0.1);
                transform: translateY(110%);
                transition: transform 0.3s cubic-bezier(0.2, 0.8, 0.2, 1);
                pointer-events: auto;
                z-index: 999;
                /* Fica atr├ís da barra de navega├º├úo (1000) mas na frente do conte├║do */
                max-height: 80vh;
                overflow-y: auto;
            }

            .bottom-sheet.open {
                transform: translateY(0);
            }

            /* Sheet Header */
            .sheet-header {
                display: flex;
                align-items: center;
                gap: 12px;
                margin-bottom: 20px;
                padding-bottom: 16px;
                border-bottom: 1px solid var(--border-color);
            }

            .sheet-title {
                font-size: 1.1rem;
                font-weight: 700;
                color: var(--text-main);
            }

            /* Sheet Grid */
            .sheet-grid {
                display: grid;
                grid-template-columns: repeat(2, 1fr);
                gap: 12px;
            }

            .sheet-btn {
                display: flex;
                flex-direction: column;
                align-items: center;
                justify-content: center;
                gap: 8px;
                background: var(--bg-body);
                border: 1px solid var(--border-color);
                border-radius: 16px;
                padding: 16px;
                text-decoration: none;
                color: var(--text-main);
                font-weight: 600;
                font-size: 0.9rem;
                transition: all 0.2s;
            }

            .sheet-btn:active {
                transform: scale(0.98);
                background: var(--border-color);
            }

            .sheet-btn-icon {
                width: 40px;
                height: 40px;
                border-radius: 50%;
                display: flex;
                align-items: center;
                justify-content: center;
                margin-bottom: 4px;
            }

            /* Overlay */
            .bs-overlay {
                position: fixed;
                top: 0;
                left: 0;
                right: 0;
                bottom: 0;
                background: rgba(0, 0, 0, 0.4);
                backdrop-filter: blur(4px);
                z-index: 998;
                opacity: 0;
                visibility: hidden;
                transition: opacity 0.3s;
                pointer-events: auto;
            }

            .bs-overlay.active {
                opacity: 1;
                visibility: visible;
            }

            @media (min-width: 1025px) {
                .bottom-nav-container {
                    display: none;
                }

                .bs-overlay {
                    display: none;
                }
            }
        </style>

        <!-- Overlay de Fundo -->
        <div id="bs-overlay" class="bs-overlay" onclick="closeAllSheets()"></div>

        <!-- 1. Sheet GEST├âO -->
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

        <!-- 2. Sheet ESP├ìRITO -->
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
            <div class="sheet-grid" style="grid-template-columns: 1fr;"> <!-- Lista ├║nica para destaque -->
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

        <!-- 3. Sheet COMUNICA -->
        <div id="sheet-comunica" class="bottom-sheet">
            <div class="sheet-header">
                <div style="background: #fff7ed; padding: 10px; border-radius: 12px; color: #c2410c;">
                    <i data-lucide="megaphone"></i>
                </div>
                <div>
                    <div class="sheet-title">Comunica</div>
                    <div style="font-size: 0.85rem; color: var(--text-muted);">Mural e Interações</div>
                </div>
            </div>
            <div class="sheet-grid">
                <a href="avisos.php" class="sheet-btn">
                    <div class="sheet-btn-icon" style="background: #fed7aa; color: #ea580c;">
                        <i data-lucide="bell"></i>
                    </div>
                    Avisos
                </a>
                <a href="aniversarios.php" class="sheet-btn">
                    <div class="sheet-btn-icon" style="background: #fed7aa; color: #ea580c;">
                        <i data-lucide="cake"></i>
                    </div>
                    Aniversários
                </a>
                <a href="chat.php" class="sheet-btn">
                    <div class="sheet-btn-icon" style="background: #fed7aa; color: #ea580c;">
                        <i data-lucide="message-circle"></i>
                    </div>
                    Chat
                </a>
            </div>
        </div>


        <!-- Barra de Navega├º├úo Fixa -->
        <div class="bottom-nav-container">
            <nav class="bottom-nav-bar">

                <style>
                    @keyframes pulse-blue-3d {
                        0% { box-shadow: 0 0 0 0 rgba(37, 99, 235, 0.7); }
                        70% { box-shadow: 0 0 0 6px rgba(37, 99, 235, 0); }
                        100% { box-shadow: 0 0 0 0 rgba(37, 99, 235, 0); }
                    }
                    .b-nav-item.home-3d .b-nav-icon-wrapper {
                        background: linear-gradient(135deg, #3b82f6, #2563eb) !important;
                        color: white !important;
                        border: 1px solid rgba(255,255,255,0.3);
                        animation: pulse-blue-3d 2s infinite;
                        /* Mantendo o tamanho padrão dos outros ícones */
                        width: 28px;
                        height: 28px;
                    }
                    .b-nav-item.home-3d span {
                        font-weight: 700 !important;
                        color: var(--primary);
                        /* Tamanho de fonte padrão */
                        font-size: 0.75rem !important;
                    }
                </style>

                <!-- Botão HOME (Primeiro) com Efeito 3D Pulsante -->
                <a href="index.php" class="b-nav-item home-3d" onclick="closeAllSheets()">
                    <div class="b-nav-icon-wrapper">
                        <i data-lucide="home"></i>
                    </div>
                    <span>Início</span>
                </a>

                <!-- Bot├úo GERAL (ex-Gest├úo) -->
                <button class="b-nav-item" onclick="toggleSheet('sheet-gestao', this)" style="color: #059669;">
                    <div class="b-nav-icon-wrapper" style="background: #ecfdf5;">
                        <i data-lucide="layout-grid"></i>
                    </div>
                    <span>Geral</span>
                </button>

                <!-- Bot├úo ESP├ìRITO -->
                <button class="b-nav-item" onclick="toggleSheet('sheet-espirito', this)" style="color: #4f46e5;">
                    <div class="b-nav-icon-wrapper" style="background: #eef2ff;">
                        <i data-lucide="flame"></i>
                    </div>
                    <span>Espírito</span>
                </button>

                <!-- Bot├úo COMUNICA -->
                <button class="b-nav-item" onclick="toggleSheet('sheet-comunica', this)" style="color: #ea580c;">
                    <div class="b-nav-icon-wrapper" style="background: #fff7ed;">
                        <i data-lucide="megaphone"></i>
                    </div>
                    <span>Comunica</span>
                </button>

                <!-- Botão CHAT -->
                <a href="chat.php" class="b-nav-item" onclick="closeAllSheets()" style="color: #8b5cf6;">
                    <div class="b-nav-icon-wrapper" style="background: #f5f3ff;">
                        <i data-lucide="message-circle"></i>
                    </div>
                    <span>Chat</span>
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

                // 2. Se n├úo estava aberto, abrir o clicado
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


        <!-- Inicializar ├ìcones -->
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

            // ... (Restante do script mantido, apenas adicionando verifica├º├úo para evitar duplicidade de listeners se necess├írio)

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
                if (!sidebar) return; // Seguran├ºa

                let touchStartX = 0;
                let touchEndX = 0;

                // ... (Mantendo a l├│gica de swipe anterior) ...

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
                    const swipeThreshold = 100; // Swipe mais longo para evitar acidentes
                    const diff = touchEndX - touchStartX;
                    const isSidebarOpen = sidebar.classList.contains('active');

                    if (diff > swipeThreshold && touchStartX < 50 && !isSidebarOpen) {
                        toggleSidebar();
                    }
                    if (diff < -swipeThreshold && isSidebarOpen) {
                        toggleSidebar();
                    }
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

        <!-- Main Script (Dark Mode & Global Logic) -->
        <script src="/assets/js/main.js"></script>

        <!-- Gestures Script -->
        <script src="/assets/js/gestures.js"></script>
    </body>

    </html>
<?php
    }

    // Nova fun├º├úo para cabe├ºalhos padronizados (Clean Header)
    function renderPageHeader($title, $subtitle = 'Louvor PIB Oliveira', $rightAction = null)
    {
        global $_layoutUser;
        $isHome = basename($_SERVER['PHP_SELF']) == 'index.php';
?>
    <header class="desktop-only-header" style="
            background: var(--bg-surface); 
            padding: 12px 16px; 
            margin: -20px -20px 24px -20px; 
            display: flex; 
            align-items: center; 
            justify-content: space-between;
            position: sticky; top: 0; z-index: 40;
            border-bottom: 1px solid var(--border-color);
            box-shadow: var(--shadow-sm);
        ">
        <style>
            @media (max-width: 1024px) {
                .desktop-only-header {
                    display: none !important;
                }
            }
        </style>

        <div style="display: flex; align-items: center; gap: 4px;">
            <?php if (!$isHome): ?>
                <button onclick="history.back()" class="ripple" title="Voltar" style="
                width: 40px; height: 40px; border-radius: 50%; border: none; background: transparent; 
                display: flex; align-items: center; justify-content: center; color: var(--text-muted); cursor: pointer;
            ">
                    <i data-lucide="arrow-left" style="width: 22px;"></i>
                </button>

                <a href="index.php" class="ripple" title="Navega├º├úo Principal" style="
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

        <!-- Direita: A├º├Áes + L├¡der + Perfil -->
        <div style="display: flex; align-items: center; justify-content: flex-end; gap: 8px; min-width: 88px;">

            <!-- L├¡der Button (Admin only) - Desktop -->
            <?php if (isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin'): ?>
                <a href="lider.php" class="ripple" style="display: flex; align-items: center; justify-content: center; width: 40px; height: 40px; background: linear-gradient(135deg, #dc2626, #ef4444); border-radius: 10px; text-decoration: none; box-shadow: 0 2px 8px rgba(220, 38, 38, 0.3);">
                    <i data-lucide="crown" style="color: white; width: 20px;"></i>
                </a>
            <?php endif; ?>

            <!-- A├º├úo da P├ígina (se houver) -->
            <?php if (isset($rightAction) && $rightAction): ?>
                <?= $rightAction ?>
            <?php endif; ?>

            <!-- Leitura Config Button (Leitura Only - Desktop) -->
            <?php if (strpos($_SERVER['PHP_SELF'], 'leitura.php') !== false): ?>
                <button onclick="openConfig()" class="ripple" style="
                    display: flex; align-items: center; justify-content: center; width: 40px; height: 40px; 
                    background: var(--bg-surface); border: 1px solid var(--border-color); border-radius: 10px; 
                    cursor: pointer; color: var(--text-muted); margin-left: 8px;
                ">
                    <i data-lucide="settings" style="width: 20px;"></i>
                </button>
            <?php endif; ?>

            <!-- Perfil Dropdown (Card Moderno) -->
            <div style="position: relative; margin-left: 4px;">
                <button onclick="toggleProfileDropdown(event, 'headerProfileDropdown')"
                    class="ripple" style="
                    width: 52px; height: 52px; padding: 0; 
                    border: 2px solid white; 
                    box-shadow: 0 0 0 4px rgba(34, 197, 94, 0.25), 0 4px 12px rgba(34, 197, 94, 0.2);
                    border-radius: 50%; overflow: hidden; cursor: pointer; background: var(--bg-surface);
                    display: flex; align-items: center; justify-content: center;
                    transition: transform 0.2s;
                " onmouseover="this.style.transform='scale(1.05)'" onmouseout="this.style.transform='scale(1)'">
                    <?php if (isset($_layoutUser['photo']) && $_layoutUser['photo']): ?>
                        <img src="<?= $_layoutUser['photo'] ?>" alt="User" style="width: 100%; height: 100%; object-fit: cover;">
                    <?php else: ?>
                        <div style="width: 100%; height: 100%; background: #e2e8f0; display: flex; align-items: center; justify-content: center; color: #64748b;">
                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M19 21v-2a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v2" />
                                <circle cx="12" cy="7" r="4" />
                            </svg>
                        </div>
                    <?php endif; ?>
                </button>

                <!-- Dropdown Card -->
                <div id="headerProfileDropdown" style="
                    display: none; position: absolute; top: 54px; right: 0; 
                    background: var(--bg-surface); border-radius: 16px; 
                    box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1), 0 8px 10px -6px rgba(0, 0, 0, 0.1); 
                    min-width: 260px; z-index: 100; border: 1px solid var(--border-color); overflow: hidden;
                    animation: fadeInUp 0.2s cubic-bezier(0.16, 1, 0.3, 1);
                    transform-origin: top right;
                ">
                    <!-- Header do Card -->
                    <div style="padding: 24px 20px; text-align: center; background: linear-gradient(to bottom, #f8fafc, #ffffff); border-bottom: 1px solid var(--border-color);">
                        <div style="width: 72px; height: 72px; margin: 0 auto 12px auto; border-radius: 50%; overflow: hidden; border: 3px solid white; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);">
                            <img src="<?= $_layoutUser['photo'] ?? 'https://ui-avatars.com/api/?name=U&background=cbd5e1&color=fff' ?>" alt="Avatar" style="width: 100%; height: 100%; object-fit: cover;">
                        </div>
                        <div style="font-weight: 800; color: var(--text-main); font-size: 1.05rem; margin-bottom: 4px;"><?= htmlspecialchars($_layoutUser['name']) ?></div>
                        <span style="background: #d1fae5; color: #065f46; font-size: 0.7rem; padding: 2px 10px; border-radius: 12px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px;">Membro da Equipe</span>
                    </div>

                    <!-- Menu Itens -->
                    <div style="padding: 12px;">
                        <a href="perfil.php" style="display: flex; align-items: center; gap: 12px; padding: 12px 16px; text-decoration: none; color: var(--text-main); font-size: 0.9rem; border-radius: 8px; transition: background 0.2s;" onmouseover="this.style.background='var(--bg-body)'" onmouseout="this.style.background='transparent'">
                            <div style="background: #f1f5f9; padding: 8px; border-radius: 8px; display: flex; color: #64748b;">
                                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M19 21v-2a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v2" />
                                    <circle cx="12" cy="7" r="4" />
                                </svg>
                            </div>
                            <span style="font-weight: 500;">Meu Perfil</span>
                        </a>

                        <?php if (isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin'): ?>
                            <a href="lider.php" style="display: flex; align-items: center; gap: 12px; padding: 12px 16px; text-decoration: none; color: var(--text-main); font-size: 0.9rem; border-radius: 8px; transition: background 0.2s;" onmouseover="this.style.background='var(--bg-body)'" onmouseout="this.style.background='transparent'">
                                <div style="background: #fff7ed; padding: 8px; border-radius: 8px; display: flex; color: #d97706;">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="m2 4 3 12h14l3-12-6 7-4-3-4 3-6-7z" />
                                        <path d="M5 16v4h14v-4" />
                                    </svg>
                                </div>
                                <span style="font-weight: 500;">Painel do Líder</span>
                            </a>
                        <?php endif; ?>



                        <!-- Dark Mode Toggle -->
                        <div onclick="toggleThemeMode()" style="display: flex; align-items: center; gap: 12px; padding: 12px 16px; cursor: pointer; color: var(--text-main); font-size: 0.9rem; border-radius: 8px; transition: background 0.2s;" onmouseover="this.style.background='var(--bg-body)'" onmouseout="this.style.background='transparent'">
                            <div style="background: #f1f5f9; padding: 8px; border-radius: 8px; display: flex; color: #64748b;">
                                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M12 3a6 6 0 0 0 9 9 9 9 0 1 1-9-9Z" />
                                </svg>
                            </div>
                            <span style="font-weight: 500;">Modo Escuro</span>
                            <div style="margin-left: auto;">
                                <label class="toggle-switch-mini">
                                    <input type="checkbox" id="darkModeToggleDropdown" onchange="toggleThemeMode()">
                                    <span class="slider-mini round"></span>
                                </label>
                            </div>
                        </div>

                        <div style="height: 1px; background: var(--border-color); margin: 8px 12px;"></div>

                        <a href="../logout.php" style="display: flex; align-items: center; gap: 12px; padding: 12px 16px; text-decoration: none; color: #ef4444; font-size: 0.9rem; border-radius: 8px; transition: background 0.2s;" onmouseover="this.style.background='#fef2f2'" onmouseout="this.style.background='transparent'">
                            <div style="background: #fee2e2; padding: 8px; border-radius: 8px; display: flex; color: #ef4444;">
                                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
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
<?php
    }
?>
