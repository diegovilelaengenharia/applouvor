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
        <meta name="theme-color" content="#059669">
        <meta name="apple-mobile-web-app-capable" content="yes">
        <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
        <meta name="apple-mobile-web-app-title" content="App Louvor PIB">
        <meta name="mobile-web-app-capable" content="yes">
        <meta name="view-transition" content="same-origin">
        <link rel="manifest" href="/manifest.json">
        <link rel="apple-touch-icon" href="../assets/img/logo_pib_black.png">

<!-- Google Material Icons -->
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200" />

        <!-- ├ìcones Lucide -->
        <script src="https://unpkg.com/lucide@latest"></script>

        <!-- Dark Mode CSS -->
        <link rel="stylesheet" href="../assets/css/dark-mode.css">

        <!-- Semantic Design System -->
        <link rel="stylesheet" href="../assets/css/design-system.css">
        <link rel="stylesheet" href="../assets/css/components/buttons.css">
        <link rel="stylesheet" href="../assets/css/components/cards.css">
        <link rel="stylesheet" href="../assets/css/components/badges.css">
        <link rel="stylesheet" href="../assets/css/components/icons.css">
        <link rel="stylesheet" href="../assets/css/components/sidebar.css">
        <link rel="stylesheet" href="../assets/css/components/page-headers.css">
        <link rel="stylesheet" href="../assets/css/components/animations.css">

        <!-- Theme Toggle Script (Critical: Must load immediately) -->
        <script src="../assets/js/theme-toggle.js?v=<?= time() ?>"></script>

        <style>
            /* --- DESIGN SYSTEM 3.0 (Paleta Slate Refinada) --- */
            :root {
                /* --- PALETA SMART BLUE (Tech Identity) --- */
                --primary-50:  #ebf0fa;
                --primary-100: #d7e1f4;
                --primary-200: #afc3e9;
                --primary-300: #87a5de;
                --primary-400: #5f88d3;
                --primary-500: #376ac8; /* BRAND PRINCIPAL */
                --primary-600: #2c55a0;
                --primary-700: #213f78;
                --primary-800: #162a50;
                --primary-900: #0b1528;
                --primary-950: #080f1c;

                /* Variáveis Semânticas de Marca */
                --primary: var(--primary-500);
                --primary-hover: var(--primary-600);
                --primary-active: var(--primary-700);
                --primary-light: var(--primary-100);
                --primary-subtle: var(--primary-50);

                /* Paleta Slate (Neutros) - Mantida para suporte */
                --slate-50: #f8fafc;
                --slate-100: #f1f5f9;
                --slate-200: #e2e8f0;
                --slate-300: #cbd5e1;
                --slate-400: #94a3b8;
                --slate-500: #64748b;
                --slate-600: #475569;
                --slate-700: #334155;
                --slate-800: #1e293b;
                --slate-900: #0f172a;

                /* Configurações de Tema (Light Mode Default) */
                --bg-body: var(--slate-50);
                --bg-surface: #ffffff;
                --bg-muted: var(--slate-100);
                
                --text-main: var(--slate-900);     /* Mais contraste */
                --text-secondary: var(--slate-600);
                --text-muted: var(--slate-500);
                
                --border-color: var(--slate-200);
                --border-subtle: var(--slate-100);

                /* Espaçamento e Design */
                --touch-target: 44px;
                --radius-md: 8px;
                --radius-lg: 12px;
                --shadow-sm: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
                --shadow-md: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
                
                /* Semântica (Independentes da Marca) */
                --success: #22c55e;
                --warning: #f59e0b;
                --danger: #ef4444;
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

            /* Global Dark Mode - Now handled by dark-mode.css */

            /* ========================================
               SISTEMA DE BOTÕES PADRONIZADOS
               ======================================== */
            
            /* Base para todos os botões */
            .btn, .btn-success, .btn-warning, .btn-danger, .btn-secondary, .btn-primary {
                padding: 10px 20px;
                border-radius: 8px;
                font-weight: 600;
                font-size: 0.9375rem;
                cursor: pointer;
                transition: all 0.2s ease;
                border: none;
                display: inline-flex;
                align-items: center;
                justify-content: center;
                gap: 8px;
                text-decoration: none;
                box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
            }

            .btn:disabled, .btn-success:disabled, .btn-warning:disabled, .btn-danger:disabled {
                opacity: 0.5;
                cursor: not-allowed;
            }

            /* CONFIRMAR/SALVAR → VERDE */
            .btn-success {
                background: linear-gradient(135deg, #059669 0%, #047857 100%);
                color: white;
                border: 1px solid #059669;
            }
            .btn-success:hover:not(:disabled) {
                background: #059669;
                box-shadow: 0 4px 12px rgba(5, 150, 105, 0.3);
                transform: translateY(-1px);
            }
            .btn-success:active:not(:disabled) {
                transform: translateY(0);
                box-shadow: 0 2px 4px rgba(5, 150, 105, 0.2);
            }

            /* CANCELAR/VOLTAR → AMARELO/DOURADO */
            .btn-warning {
                background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
                color: white;
                border: 1px solid #d97706;
            }
            .btn-warning:hover:not(:disabled) {
                background: #d97706;
                box-shadow: 0 4px 12px rgba(245, 158, 11, 0.3);
                transform: translateY(-1px);
            }
            .btn-warning:active:not(:disabled) {
                transform: translateY(0);
                box-shadow: 0 2px 4px rgba(245, 158, 11, 0.2);
            }

            /* DELETAR/EXCLUIR/SAIR → VERMELHO */
            .btn-danger {
                background: linear-gradient(135deg, #dc2626 0%, #b91c1c 100%);
                color: white;
                border: 1px solid #b91c1c;
            }
            .btn-danger:hover:not(:disabled) {
                background: #b91c1c;
                box-shadow: 0 4px 12px rgba(220, 38, 38, 0.3);
                transform: translateY(-1px);
            }
            .btn-danger:active:not(:disabled) {
                transform: translateY(0);
                box-shadow: 0 2px 4px rgba(220, 38, 38, 0.2);
            }

            /* NEUTRO/SECUNDÁRIO → CINZA */
            .btn-secondary {
                background: linear-gradient(135deg, #64748b 0%, #475569 100%);
                color: white;
                border: 1px solid #475569;
            }
            .btn-secondary:hover:not(:disabled) {
                background: #475569;
                box-shadow: 0 4px 12px rgba(100, 116, 139, 0.3);
                transform: translateY(-1px);
            }
            .btn-secondary:active:not(:disabled) {
                transform: translateY(0);
                box-shadow: 0 2px 4px rgba(100, 116, 139, 0.2);
            }

            /* PRIMARY → AZUL (para ações principais neutras) */
            .btn-primary {
                background: linear-gradient(135deg, #2563eb 0%, #1e40af 100%);
                color: white;
                border: 1px solid #1e40af;
            }
            .btn-primary:hover:not(:disabled) {
                background: #1e40af;
                box-shadow: 0 4px 12px rgba(37, 99, 235, 0.3);
                transform: translateY(-1px);
            }
            .btn-primary:active:not(:disabled) {
                transform: translateY(0);
                box-shadow: 0 2px 4px rgba(37, 99, 235, 0.2);
            }

            /* Tamanhos de botões */
            .btn-sm {
                padding: 6px 12px;
                font-size: 0.8125rem;
            }
            .btn-lg {
                padding: 14px 28px;
                font-size: 1rem;
            }

            /* Botão outline (variante) */
            .btn-outline-success {
                background: transparent;
                color: #16a34a;
                border: 2px solid #16a34a;
            }
            .btn-outline-success:hover {
                background: #16a34a;
                color: white;
            }

            .btn-outline-danger {
                background: transparent;
                color: #dc2626;
                border: 2px solid #dc2626;
            }
            .btn-outline-danger:hover {
                background: #dc2626;
                color: white;
            }

            /* ======================================== */

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
            
            /* ========================================
               HEADER BUTTONS - ELEGANT UNIFIED DESIGN
               ======================================== */
            
            /* Base style for all header action buttons */
            .header-btn-base {
                position: relative;
                display: flex;
                align-items: center;
                justify-content: center;
                width: 40px;
                height: 40px;
                background: var(--bg-surface);
                border: 1px solid var(--border-color);
                border-radius: 10px;
                cursor: pointer;
                transition: all 0.2s ease;
                box-shadow: 0 1px 2px rgba(0, 0, 0, 0.04);
            }
            
            .header-btn-base:hover {
                transform: translateY(-1px);
                box-shadow: 0 3px 8px rgba(0, 0, 0, 0.08);
            }
            
            .header-btn-base:active {
                transform: translateY(0);
                box-shadow: 0 1px 2px rgba(0, 0, 0, 0.04);
            }
            
            /* Líder Button - Subtle red accent */
            .admin-crown-btn {
                position: relative;
                display: flex;
                align-items: center;
                justify-content: center;
                width: 40px;
                height: 40px;
                background: #fef2f2;
                border: 1px solid #fca5a5;
                border-radius: 10px;
                text-decoration: none;
                cursor: pointer;
                color: #dc2626;
                box-shadow: 0 1px 2px rgba(0, 0, 0, 0.04);
                transition: all 0.2s ease;
            }
            
            .admin-crown-btn:hover {
                background: #fee2e2;
                border-color: #f87171;
                transform: translateY(-1px);
                box-shadow: 0 3px 8px rgba(239, 68, 68, 0.15);
            }
            
            .admin-crown-btn:hover i,
            .admin-crown-btn:hover svg {
                animation: crown-bounce 0.4s ease;
            }
            
            @keyframes crown-bounce {
                0%, 100% { transform: translateY(0); }
                50% { transform: translateY(-2px); }
            }
            
            .admin-crown-btn:active {
                transform: translateY(0);
            }
            
            /* Notification Button - Blue accent */
            .notification-btn {
                position: relative;
                display: flex;
                align-items: center;
                justify-content: center;
                width: 40px;
                height: 40px;
                background: #eff6ff;
                border: 1px solid #bfdbfe;
                border-radius: 10px;
                cursor: pointer;
                color: #2563eb;
                box-shadow: 0 1px 2px rgba(0, 0, 0, 0.04);
                transition: all 0.2s ease;
                overflow: visible;
            }
            
            .notification-btn:hover {
                background: #dbeafe;
                color: #1d4ed8;
                border-color: #93c5fd;
                transform: translateY(-1px);
                box-shadow: 0 3px 8px rgba(37, 99, 235, 0.15);
            }
            
            .notification-btn:hover svg,
            .notification-btn:hover i {
                animation: bell-swing 0.5s ease;
            }
            
            @keyframes bell-swing {
                0%, 100% { transform: rotate(0deg); }
                25% { transform: rotate(8deg); }
                50% { transform: rotate(-6deg); }
                75% { transform: rotate(4deg); }
            }
            
            .notification-btn:active {
                transform: translateY(0);
            }
            
            /* Notification Badge - Elegant red dot */
            .notification-badge {
                position: absolute;
                top: -4px;
                right: -4px;
                background: #ef4444;
                color: white;
                font-size: 10px;
                font-weight: 700;
                padding: 2px 5px;
                border-radius: 8px;
                min-width: 16px;
                text-align: center;
                box-shadow: 0 1px 3px rgba(239, 68, 68, 0.3),
                           0 0 0 2px var(--bg-surface);
                z-index: 10;
            }

            /* Config Button - Slate accent */
            .config-btn {
                position: relative;
                display: flex;
                align-items: center;
                justify-content: center;
                width: 40px;
                height: 40px;
                background: #f1f5f9;
                border: 1px solid #cbd5e1;
                border-radius: 10px;
                flex-shrink: 0;
                cursor: pointer;
                color: #475569;
                box-shadow: 0 1px 2px rgba(0, 0, 0, 0.04);
                transition: all 0.2s ease;
            }
            
            .config-btn:hover {
                background: #e2e8f0;
                color: #334155;
                border-color: #94a3b8;
                transform: translateY(-1px);
                box-shadow: 0 3px 8px rgba(71, 85, 105, 0.1);
            }
            
            .config-btn:hover i,
            .config-btn:hover svg {
                animation: gear-spin 0.5s ease;
            }
            
            @keyframes gear-spin {
                0% { transform: rotate(0deg); }
                100% { transform: rotate(45deg); }
            }

            /* Profile Avatar Button - Clean circular style with green accent */
            .profile-avatar-btn {
                width: 40px;
                height: 40px;
                padding: 0;
                background: #f0fdf4;
                border: 2px solid #86efac;
                cursor: pointer;
                border-radius: 50%;
                overflow: hidden;
                display: flex;
                align-items: center;
                justify-content: center;
                transition: all 0.2s ease;
                position: relative;
                color: #64748b;
                box-shadow: 0 1px 2px rgba(0, 0, 0, 0.04);
            }
            
            .profile-avatar-btn:hover {
                border-color: #10b981;
                transform: translateY(-1px);
                box-shadow: 0 3px 8px rgba(16, 185, 129, 0.12);
            }
            
            .profile-avatar-btn:active {
                transform: translateY(0);
            }
            
            
            .notification-dropdown {
                display: none;
                position: absolute;
                top: 50px;
                right: 0;
                width: 320px;
                max-width: calc(100vw - 24px);
                background: var(--bg-surface);
                border: 1px solid var(--border-color);
                border-radius: 14px;
                box-shadow: 0 8px 24px rgba(0,0,0,0.12);
                z-index: 1000;
                overflow: hidden;
            }
            
            .notification-dropdown.active {
                display: block;
                animation: fadeInDown 0.2s ease-out;
            }
            
            @keyframes fadeInDown {
                from {
                    opacity: 0;
                    transform: translateY(-10px);
                }
                to {
                    opacity: 1;
                    transform: translateY(0);
                }
            }
            
            .notification-header {
                padding: 12px 14px;
                border-bottom: 1px solid var(--border-color);
                display: flex;
                justify-content: space-between;
                align-items: center;
            }
            
            .notification-header h3 {
                font-size: var(--font-body);
                font-weight: 700;
                color: var(--text-main);
                margin: 0;
            }
            
            .notification-mark-all {
                font-size: var(--font-caption);
                color: var(--primary);
                cursor: pointer;
                font-weight: 600;
            }
            
            .notification-mark-all:hover {
                text-decoration: underline;
            }
            
            .notification-list {
                max-height: 320px;
                overflow-y: auto;
            }
            
            .notification-item {
                padding: 10px 12px;
                border-bottom: 1px solid var(--border-color);
                cursor: pointer;
                transition: background 0.2s;
                display: flex;
                gap: 10px;
            }
            
            .notification-item:hover {
                background: var(--bg-body);
            }
            
            .notification-item.unread {
                background: #eff6ff;
            }
            
            .notification-icon {
                width: 32px;
                height: 32px;
                border-radius: 8px;
                display: flex;
                align-items: center;
                justify-content: center;
                flex-shrink: 0;
            }
            
            .notification-content {
                flex: 1;
                min-width: 0;
            }
            
            .notification-title {
                font-size: var(--font-body-sm);
                font-weight: 600;
                color: var(--text-main);
                margin-bottom: 2px;
            }
            
            .notification-message {
                font-size: var(--font-caption);
                color: var(--text-muted);
                line-height: 1.4;
            }
            
            .notification-time {
                font-size: 11px;
                color: var(--text-muted);
                margin-top: 4px;
            }
            
            .notification-footer {
                padding: 10px 14px;
                border-top: 1px solid var(--border-color);
                text-align: center;
            }
            
            .notification-view-all {
                color: var(--primary);
                font-size: var(--font-body-sm);
                font-weight: 600;
                text-decoration: none;
            }
            
            .notification-view-all:hover {
                text-decoration: underline;
            }
            
            .notification-empty {
                padding: 40px 20px;
                text-align: center;
                color: var(--text-muted);
            }
            
            /* Mobile: Convert dropdown to fullscreen modal */
            @media (max-width: 768px) {
                .notification-dropdown {
                    position: fixed !important;
                    top: 50% !important;
                    left: 50% !important;
                    transform: translate(-50%, -50%) !important;
                    right: auto !important;
                    width: 90vw !important;
                    max-width: 400px !important;
                    max-height: 80vh !important;
                    border-radius: 16px !important;
                    z-index: 9999 !important;
                }
                
                /* Quando visível, force flex display */
                .notification-dropdown[style*="display: block"],
                .notification-dropdown[style*="display:block"] {
                    display: flex !important;
                    flex-direction: column !important;
                    visibility: visible !important;
                    opacity: 1 !important;
                }
                
                .notification-list {
                    max-height: calc(80vh - 140px) !important;
                }
            }
            
            /* Overlay para mobile */
            .notification-overlay {
                display: none;
                position: fixed;
                top: 0;
                left: 0;
                right: 0;
                bottom: 0;
                background: rgba(0, 0, 0, 0.5);
                z-index: 9998;
                backdrop-filter: blur(4px);
            }
            
            .notification-overlay.active {
                display: block;
            }
            
            /* DESKTOP: Dropdown positioned near button */
            .notification-dropdown {
                display: none;
                position: absolute;
                top: 50px;
                right: 0;
                width: 360px;
                max-width: calc(100vw - 32px);
                background: var(--bg-surface);
                border: 1px solid var(--border-color);
                border-radius: 14px;
                box-shadow: 0 8px 24px rgba(0,0,0,0.12);
                z-index: 1000;
                overflow: hidden;
            }
            
            /* MOBILE: Modal centered */
            .notification-modal {
                padding: 0;
                border: none;
                border-radius: 20px;
                width: 92vw;
                max-width: 420px;
                max-height: 85vh;
                background: var(--bg-surface);
                box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            }
            
            .notification-modal::backdrop {
                background: rgba(0, 0, 0, 0.6);
                backdrop-filter: blur(8px);
            }
            
            .modal-header {
                padding: 20px;
                border-bottom: 1px solid var(--border-color);
                display: flex;
                justify-content: space-between;
                align-items: center;
            }
            
            .modal-header h3 {
                font-size: 18px;
                font-weight: 700;
                margin: 0;
                color: var(--text-main);
            }
            
            .modal-close {
                background: var(--bg-body);
                border: none;
                width: 32px;
                height: 32px;
                border-radius: 50%;
                cursor: pointer;
                font-size: 20px;
                display: flex;
                align-items: center;
                justify-content: center;
                color: var(--text-muted);
                transition: all 0.2s;
            }
            
            .modal-close:hover {
                background: var(--border-color);
                color: var(--text-main);
            }
            
            .modal-footer {
                padding: 16px 20px;
                border-top: 1px solid var(--border-color);
                text-align: center;
            }
            
            .modal-footer a {
                color: var(--primary);
                font-weight: 600;
                text-decoration: none;
                font-size: 14px;
            }
        
        </style>
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
                    <!-- Leitura Config Button (Leitura Only) -->
                    <?php if (strpos($_SERVER['PHP_SELF'], 'leitura.php') !== false): ?>
                        <button onclick="openConfig()" class="config-btn">
                            <i data-lucide="settings" style="width: 20px;"></i>
                        </button>
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

        <style>
            /* OCULTAR BARRA DE NAVEGAÇÃO INFERIOR */
            .bottom-nav-container {
                display: none !important;
            }
        </style>

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
                padding: 6px 12px;
                /* Reduced bottom padding even more (-15%) */
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
                gap: 2px; /* Reduzido de 6px */
                background: none;
                border: none;
                color: var(--text-muted);
                font-size: 0.75rem;
                font-weight: 600;
                cursor: pointer;
                transition: transform 0.2s, color 0.2s;
                padding: 8px 20px; /* Aumentado de 4px 12px para área de clique maior */
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

                <!-- Botão GERAL (Gestão → AZUL) -->
                <button class="b-nav-item" onclick="toggleSheet('sheet-gestao', this)" style="color: #2563eb;">
                    <div class="b-nav-icon-wrapper" style="background: #eff6ff;">
                        <i data-lucide="layout-grid"></i>
                    </div>
                    <span>Geral</span>
                </button>

                <!-- Botão ESPÍRITO (Espiritual → VERDE) -->
                <button class="b-nav-item" onclick="toggleSheet('sheet-espirito', this)" style="color: #059669;">
                    <div class="b-nav-icon-wrapper" style="background: #ecfdf5;">
                        <i data-lucide="flame"></i>
                    </div>
                    <span>Espírito</span>
                </button>

                <!-- Botão AVISOS (Comunicação → ROXO) -->
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
                     alert('📱 Para instalar no iPhone:\n\n1. Toque no botão Compartilhar (quadrado com seta)\n2. Role para baixo e toque em "Adicionar à Tela de Início"');
                } else {
                    // Android / Other fallback
                    alert('📱 Para instalar:\n\nToque no menu do navegador (3 pontinhos) e selecione "Instalar aplicativo" ou "Adicionar à tela inicial".');
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

        <!-- Direita: Ações + Líder + Perfil -->
        <div style="display: flex; align-items: center; justify-content: flex-end; gap: 8px; min-width: 88px;">

            <!-- Líder Button (Admin only) - Desktop -->
            <?php if (isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin'): ?>
                <a href="lider.php" class="admin-crown-btn ripple" title="Painel do Líder">
                    <i data-lucide="crown" style="width: 20px;"></i>
                </a>
            <?php endif; ?>

            <!-- Ação da Página (se houver) -->
            <?php if (isset($rightAction) && $rightAction): ?>
                <?= $rightAction ?>
            <?php endif; ?>

            <!-- Leitura Config Button (Leitura Only - Desktop) -->
            <?php if (strpos($_SERVER['PHP_SELF'], 'leitura.php') !== false): ?>
                <button onclick="openConfig()" class="header-action-btn ripple" title="Configurações">
                    <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M12.22 2h-.44a2 2 0 0 0-2 2v.18a2 2 0 0 1-1 1.73l-.43.25a2 2 0 0 1-2 0l-.15-.08a2 2 0 0 0-2.73.73l-.22.38a2 2 0 0 0 .73 2.73l.15.1a2 2 0 0 1 1 1.72v.51a2 2 0 0 1-1 1.74l-.15.09a2 2 0 0 0-.73 2.73l.22.38a2 2 0 0 0 2.73.73l.15-.08a2 2 0 0 1 2 0l.43.25a2 2 0 0 1 1 1.73V20a2 2 0 0 0 2 2h.44a2 2 0 0 0 2-2v-.18a2 2 0 0 1 1-1.73l.43-.25a2 2 0 0 1 2 0l.15.08a2 2 0 0 0 2.73-.73l.22-.38a2 2 0 0 0-.73-2.73l-.15-.1a2 2 0 0 1-1-1.72v-.51a2 2 0 0 1 1-1.74l.15-.09a2 2 0 0 0 .73-2.73l-.22-.38a2 2 0 0 0-2.73-.73l-.15.08a2 2 0 0 1-2 0l-.43-.25a2 2 0 0 1-1-1.73V4a2 2 0 0 0-2-2z"></path>
                        <circle cx="12" cy="12" r="3"></circle>
                    </svg>
                </button>
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

            <style>
            /* NOTIFICATION SYSTEM CSS - PROFESSIONAL */
            /* UNIFIED HEADER ACTION BUTTONS */
            .header-action-btn {
                width: 44px; height: 44px;
                background: var(--bg-surface);
                border: 1px solid var(--border-color);
                border-radius: 12px;
                display: flex; align-items: center; justify-content: center;
                cursor: pointer;
                color: var(--text-muted);
                position: relative;
                transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
                /* Remove overflow hidden to allow badge */
            }
            .header-action-btn:hover {
                background: var(--bg-body);
                color: var(--primary);
                border-color: var(--primary-light);
                transform: translateY(-1px);
                box-shadow: var(--shadow-sm);
            }

            .notification-badge {
                position: absolute;
                top: -6px; right: -6px;
                background: #ef4444; 
                color: white;
                font-size: 11px; 
                font-weight: 700;
                min-width: 20px; 
                height: 20px;
                padding: 0 4px;
                border-radius: 10px;
                display: flex; 
                align-items: center; 
                justify-content: center;
                border: 2px solid var(--bg-surface); /* Match bg instead of white */
                box-shadow: 0 2px 5px rgba(239, 68, 68, 0.3);
                transform-origin: center;
                animation: popIn 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275);
                z-index: 10;
                pointer-events: none;
            }
            @keyframes popIn { from { transform: scale(0); } to { transform: scale(1); } }
            
            .notification-dropdown {
                display: none;
                position: absolute;
                right: 0;
                top: 54px;
                width: 380px;
                background: var(--bg-surface);
                border: 1px solid var(--border-color);
                border-radius: 16px;
                box-shadow: 0 10px 30px -10px rgba(0,0,0,0.15);
                z-index: 1000;
                overflow: hidden;
                transform-origin: top right;
                animation: dropdownIn 0.2s ease-out;
            }
            @keyframes dropdownIn { from { opacity: 0; transform: scale(0.95); } to { opacity: 1; transform: scale(1); } }

            @media(max-width: 640px) {
                .notification-dropdown {
                    position: fixed;
                    top: 60px; left: 16px; right: 16px; width: auto;
                }
            }
            .notification-header {
                padding: 16px;
                border-bottom: 1px solid var(--border-color);
                display: flex; justify-content: space-between; align-items: center;
                background: #f8fafc;
            }
            .notification-title { 
                font-weight: 700; color: var(--text-main); font-size: 0.95rem; 
                display: flex; align-items: center; gap: 10px; 
            }
            .notification-enable-btn {
                background: var(--primary); color: white; border: none; padding: 6px 12px; 
                border-radius: 6px; font-size: 0.75rem; cursor: pointer; display: none; 
                font-weight: 600; align-items: center; gap: 6px;
                transition: background 0.2s;
            }
            .notification-enable-btn:hover { background: var(--primary-hover); }

            .mark-all-read { 
                background: none; border: none; color: var(--primary); 
                font-size: 0.8rem; font-weight: 600; cursor: pointer; padding: 6px 10px;
                border-radius: 6px; transition: background 0.1s;
            }
            .mark-all-read:hover { background: var(--primary-light); }

            .notification-list { max-height: 400px; overflow-y: auto; }
            .notification-item {
                padding: 16px;
                border-bottom: 1px solid var(--border-color);
                display: flex; gap: 16px;
                cursor: pointer; transition: background 0.1s;
                text-decoration: none; color: inherit;
                position: relative;
            }
            .notification-item:hover { background: #f8fafc; }
            .notification-item.unread { background: #f0fdf4; }
            .notification-item.unread:before {
                content: ''; position: absolute; left: 0; top: 0; bottom: 0; width: 4px; background: var(--primary);
            }
            .notification-item:last-child { border-bottom: none; }
            
            .notification-icon {
                width: 40px; height: 40px; border-radius: 12px;
                display: flex; align-items: center; justify-content: center;
                flex-shrink: 0;
            }
            .notification-content { flex: 1; min-width: 0; }
            .notification-title-text { 
                font-size: 0.9rem; font-weight: 600; color: var(--text-main); margin-bottom: 4px;
                white-space: nowrap; overflow: hidden; text-overflow: ellipsis; 
            }
            .notification-text { font-size: 0.85rem; color: var(--text-muted); line-height: 1.4; margin-bottom: 6px; }
            .notification-time { font-size: 0.75rem; color: #94a3b8; display: flex; align-items: center; gap: 4px; }
            
            .notification-footer {
                padding: 12px; text-align: center; border-top: 1px solid var(--border-color);
                background: #f8fafc;
            }
            .notification-footer a {
                color: var(--primary); font-weight: 600; font-size: 0.85rem; text-decoration: none;
                display: inline-flex; align-items: center; gap: 6px;
            }
            .notification-footer a:hover { text-decoration: underline; }
            
            .empty-state { padding: 40px 20px; text-align: center; color: var(--text-muted); font-size: 0.95rem; }
            </style>

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
                    <div style="padding: 12px 16px; display: flex; align-items: center; gap: 12px; background: linear-gradient(to bottom, #f8fafc, #ffffff); border-bottom: 1px solid var(--border-color);">
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

                                <a href="perfil.php" style="display: flex; align-items: center; gap: 10px; padding: 8px 12px; text-decoration: none; color: var(--text-main); font-size: 0.85rem; border-radius: 8px; transition: background 0.2s;" onmouseover="this.style.background='var(--bg-body)'" onmouseout="this.style.background='transparent'">
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
                                    <a href="lider.php" style="display: flex; align-items: center; gap: 10px; padding: 8px 12px; text-decoration: none; color: var(--text-main); font-size: 0.85rem; border-radius: 8px; transition: background 0.2s;" onmouseover="this.style.background='var(--bg-body)'" onmouseout="this.style.background='transparent'">
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
                        padding: 10px 20px; border: 1px solid var(--border-color); 
                        background: transparent; border-radius: 8px; cursor: pointer; 
                        color: var(--text-main); font-weight: 500;
                    ">Cancelar</button>
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
                // Reiniciar Lucide icons se necess├írio
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
                alert('Erro na comunica├º├úo com o servidor.');
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

        <style>
            /* Notification Button - Green Outline (Dark Mode) */
            body.dark-mode #notificationBtn,
            body.dark-mode #notificationBtnDesktop {
                border: 1px solid #10b981 !important; /* Emerald 500 */
                color: #10b981 !important;
                background: transparent !important;
                box-shadow: none !important;
                transition: all 0.2s;
            }

            body.dark-mode #notificationBtn:hover,
            body.dark-mode #notificationBtnDesktop:hover {
                background: rgba(16, 185, 129, 0.1) !important;
                box-shadow: 0 0 12px rgba(16, 185, 129, 0.2) !important;
                transform: translateY(-2px);
            }

            /* --- Leader Button Styles (Header) --- */
            /* Light Mode (Original) */
            .admin-crown-btn {
                position: relative;
                display: flex;
                align-items: center;
                justify-content: center;
                width: 44px;
                height: 44px;
                background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%);
                border: 2px solid transparent;
                border-radius: 12px;
                text-decoration: none;
                box-shadow: 0 4px 12px rgba(245, 158, 11, 0.25), 0 0 0 1px rgba(245, 158, 11, 0.1);
                transition: all 0.3s;
                color: #f59e0b;
                cursor: pointer;
            }
            .admin-crown-btn i {
                width: 20px;
                position: relative;
                z-index: 1;
            }
            
            /* Dark Mode - Líder: Teal Premium (Autoridade + Harmonia) */
            body.dark-mode .admin-crown-btn {
                background: linear-gradient(145deg, rgba(13, 148, 136, 0.2), rgba(20, 184, 166, 0.15)) !important;
                border: 1.5px solid rgba(45, 212, 191, 0.5) !important;
                color: #5eead4 !important;
                box-shadow: 0 0 0 1px rgba(13, 148, 136, 0.1), 
                            0 4px 12px rgba(13, 148, 136, 0.2),
                            inset 0 1px 0 rgba(94, 234, 212, 0.1) !important;
            }
            body.dark-mode .admin-crown-btn:hover {
                background: linear-gradient(145deg, rgba(13, 148, 136, 0.35), rgba(20, 184, 166, 0.25)) !important;
                border-color: #5eead4 !important;
                box-shadow: 0 0 0 1px rgba(13, 148, 136, 0.15),
                            0 8px 20px rgba(13, 148, 136, 0.35),
                            inset 0 1px 0 rgba(94, 234, 212, 0.15) !important;
                transform: translateY(-2px);
            }

            /* --- Leader Menu Item (Dropdown) --- */
            .lider-menu-item {
                display: flex;
                align-items: center;
                gap: 10px;
                padding: 8px 12px;
                text-decoration: none;
                font-size: 0.85rem;
                border-radius: 8px;
                transition: background 0.2s;
                color: var(--text-main);
            }
            .lider-menu-item .icon-wrapper {
                background: #fff7ed;
                padding: 6px;
                border-radius: 6px;
                display: flex;
                color: #d97706;
                transition: all 0.2s;
                width: 28px; height: 28px; justify-content: center; align-items: center;
            }
            .lider-menu-item:hover {
                background: var(--bg-body);
            }
            
            /* Dark Mode Lider Item (GREEN OUTLINE) */
            body.dark-mode .lider-menu-item .icon-wrapper {
                background: transparent !important;
                border: 1px solid #10b981;
                color: #10b981;
            }
            body.dark-mode .lider-menu-item span {
                color: #10b981 !important;
                font-weight: 600;
            }
            body.dark-mode .lider-menu-item:hover .icon-wrapper {
                background: rgba(16, 185, 129, 0.1) !important;
            }

            /* Dropdown Notificações & Footer FIX */
            body.dark-mode .notification-dropdown {
                background: #0f172a !important;
                border: 1px solid #1e293b !important;
                box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.5) !important;
            }
            body.dark-mode .notification-header {
                border-bottom: 1px solid #1e293b !important;
                background: #0f172a !important;
            }
            body.dark-mode .notification-header h3 { color: #f1f5f9 !important; }
            body.dark-mode .mark-all-read { color: #34d399 !important; font-weight: 600; }
            
            /* Footer Fix - Remover Fundo Branco */
            body.dark-mode .notification-footer,
            body.dark-mode .modal-footer {
                background: #0f172a !important;
                border-top: 1px solid #1e293b !important;
            }
            
            body.dark-mode .notification-view-all,
            body.dark-mode .view-all-btn, 
            body.dark-mode .modal-footer a { 
                color: #34d399 !important; 
                background: transparent !important;
                border-top: none !important;
            }
            
            body.dark-mode .notification-view-all:hover,
            body.dark-mode .view-all-btn:hover { 
                background: rgba(52, 211, 153, 0.1) !important; 
                text-decoration: none !important;
            }

            body.dark-mode .notification-empty { color: #94a3b8 !important; }
            body.dark-mode .notification-empty i { opacity: 0.5 !important; color: #64748b !important; }

            /* Profile Dropdown Container */
            .profile-dropdown {
                display: none;
                position: absolute;
                top: 54px;
                right: 0;
                background: var(--bg-surface);
                border-radius: 16px;
                box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1), 0 8px 10px -6px rgba(0, 0, 0, 0.1);
                min-width: 200px;
                z-index: 2000;
                border: 1px solid var(--border-color);
                overflow: hidden;
                animation: fadeInUp 0.2s cubic-bezier(0.16, 1, 0.3, 1);
                transform-origin: top right;
            }

            /* Profile Header */
            .profile-header {
                padding: 12px 16px;
                display: flex;
                align-items: center;
                gap: 12px;
                background: linear-gradient(to bottom, #f8fafc, #ffffff);
                border-bottom: 1px solid var(--border-color);
            }

            .profile-avatar-container {
                width: 42px;
                height: 42px;
                border-radius: 50%;
                overflow: hidden;
                border: 2px solid white;
                box-shadow: 0 2px 4px rgba(0,0,0,0.05);
                flex-shrink: 0;
            }

            .profile-avatar-container img {
                width: 100%;
                height: 100%;
                object-fit: cover;
            }

            .profile-info {
                flex: 1;
                min-width: 0;
            }

            .profile-name {
                font-weight: 700;
                color: var(--text-main);
                font-size: 0.95rem;
                white-space: nowrap;
                overflow: hidden;
                text-overflow: ellipsis;
            }

            .profile-role {
                font-size: 0.75rem;
                color: #047857;
                font-weight: 500;
            }

            /* Profile Menu Item - Base Styles */
            .profile-menu-item {
                display: flex;
                align-items: center;
                gap: 10px;
                padding: 8px 12px;
                text-decoration: none;
                color: var(--text-main);
                font-size: 0.85rem;
                border-radius: 8px;
                transition: all 0.2s;
            }

            .profile-menu-item .icon-wrapper {
                padding: 6px;
                border-radius: 6px;
                display: flex;
                align-items: center;
                justify-content: center;
                width: 28px;
                height: 28px;
                transition: all 0.2s;
            }

            .profile-menu-item span {
                font-weight: 500;
            }

            .profile-menu-item:hover {
                background: var(--bg-body);
            }

            /* Light Mode Icon Colors */
            .profile-menu-item:nth-child(1) .icon-wrapper { background: #e0e7ff; color: #4338ca; }
            .profile-menu-item:nth-child(2) .icon-wrapper { background: #f1f5f9; color: #64748b; }
            .profile-menu-item:nth-child(3) .icon-wrapper { background: #eef2ff; color: #4338ca; }
            .profile-menu-item:nth-child(5) .icon-wrapper { background: #f1f5f9; color: #64748b; }

            .logout-item {
                color: #ef4444 !important;
            }
            .logout-item .icon-wrapper {
                background: #fee2e2;
                color: #ef4444;
            }
            .logout-item:hover {
                background: #fef2f2 !important;
            }

            /* ===== DARK MODE ===== */

            /* Profile Dropdown - Dark Mode */
            body.dark-mode .profile-dropdown {
                background: #1e293b !important;
                border: 1px solid #334155 !important;
                box-shadow: 0 20px 40px -10px rgba(0, 0, 0, 0.6) !important;
            }

            /* Profile Header - Premium Gradient */
            body.dark-mode .profile-header {
                background: linear-gradient(135deg, #0f766e 0%, #047857 100%) !important;
                border-bottom: 1px solid rgba(16, 185, 129, 0.2) !important;
            }

            body.dark-mode .profile-avatar-container {
                border-color: rgba(255, 255, 255, 0.1) !important;
            }

            body.dark-mode .profile-name {
                color: #ffffff !important;
            }

            body.dark-mode .profile-role {
                color: #d1fae5 !important;
            }

            /* Profile Menu Items - Dark Mode */
            body.dark-mode .profile-menu-item {
                color: #f1f5f9 !important;
            }

            body.dark-mode .profile-menu-item:hover {
                background: rgba(51, 65, 85, 0.5) !important;
            }

            /* Icon Wrappers - Professional Dark Look */
            body.dark-mode .profile-menu-item .icon-wrapper {
                background: rgba(148, 163, 184, 0.15) !important;
                border: 1px solid rgba(148, 163, 184, 0.25) !important;
                color: #94a3b8 !important;
            }

            /* Líder Item (Dropdown) - Teal Premium */
            body.dark-mode .lider-menu-item .icon-wrapper {
                background: linear-gradient(145deg, rgba(13, 148, 136, 0.25), rgba(20, 184, 166, 0.2)) !important;
                border: 1.5px solid rgba(45, 212, 191, 0.4) !important;
                color: #5eead4 !important;
                box-shadow: 0 3px 10px rgba(13, 148, 136, 0.2),
                            inset 0 1px 0 rgba(94, 234, 212, 0.1) !important;
            }

            body.dark-mode .lider-menu-item span {
                color: #5eead4 !important;
                font-weight: 600;
            }

            body.dark-mode .lider-menu-item:hover {
                background: rgba(13, 148, 136, 0.1) !important;
            }

            body.dark-mode .lider-menu-item:hover .icon-wrapper {
                background: linear-gradient(145deg, rgba(13, 148, 136, 0.4), rgba(20, 184, 166, 0.3)) !important;
                border-color: #5eead4 !important;
                box-shadow: 0 5px 15px rgba(13, 148, 136, 0.3),
                            inset 0 1px 0 rgba(94, 234, 212, 0.15) !important;
            }

            /* Logout Button - Red Accent Dark Mode */
            body.dark-mode .logout-item {
                color: #fca5a5 !important;
            }

            body.dark-mode .logout-item .icon-wrapper {
                background: rgba(239, 68, 68, 0.2) !important;
                border: 1px solid rgba(239, 68, 68, 0.3) !important;
                color: #f87171 !important;
            }

            body.dark-mode .logout-item:hover {
                background: rgba(127, 29, 29, 0.3) !important;
            }
        </style>
    </body>
    </html>
<?php
}
?>
