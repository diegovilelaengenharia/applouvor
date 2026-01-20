<?php
// includes/layout.php
// Este arquivo gerencia a estrutura principal (Shell) do App

// Desabilitar cache
require_once __DIR__ . '/no-cache.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function renderAppHeader($title = 'Louvor PIB')
{
    $avatar = $_SESSION['user_avatar'] ?? null;
    $userInitials = substr($_SESSION['user_name'] ?? 'U', 0, 1);

    // Buscar contagem de avisos não lidos (urgentes e importantes)
    global $pdo;
    $notificationCount = 0;
    try {
        if (isset($pdo)) {
            $stmt = $pdo->query("
                SELECT COUNT(*) 
                FROM avisos 
                WHERE archived_at IS NULL 
                  AND (priority = 'urgent' OR priority = 'important')
                  AND created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
            ");
            $notificationCount = $stmt->fetchColumn();
        }
    } catch (Exception $e) {
        // Silently fail
    }
?>
    <!DOCTYPE html>
    <html lang="pt-BR">

    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
        <meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate">
        <meta http-equiv="Pragma" content="no-cache">
        <meta http-equiv="Expires" content="0">

        <!-- PWA Fullscreen & Mobile -->
        <meta name="apple-mobile-web-app-capable" content="yes">
        <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
        <meta name="mobile-web-app-capable" content="yes">
        <meta name="apple-mobile-web-app-title" content="Louvor PIB">

        <title><?= htmlspecialchars($title) ?></title>
        <link rel="stylesheet" href="<?= asset('../assets/css/style.css') ?>">
        <!-- PWA Support -->
        <meta name="theme-color" content="#2D7A4F">
        <link rel="manifest" href="../manifest.json">
        <script>
            if ('serviceWorker' in navigator) {
                window.addEventListener('load', () => {
                    navigator.serviceWorker.register('../sw.js');
                });
            }
        </script>

        <!-- Ícones -->
        <script src="https://unpkg.com/lucide@latest"></script>
    </head>

    <body>
        <!-- Global Navigation Buttons (Fixed Top Right) -->
        <div style="
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 1000;
            display: flex;
            gap: 12px;
            align-items: center;
        ">
            <!-- WhatsApp Button -->
            <a href="https://chat.whatsapp.com/LmNlohl5XFiGGKQdONQMv2" target="_blank" class="ripple" style="
                width: 48px;
                height: 48px;
                border-radius: 50%;
                background: linear-gradient(135deg, #0D6EFD 0%, #0B5ED7 100%);
                display: flex;
                align-items: center;
                justify-content: center;
                text-decoration: none;
                box-shadow: 0 4px 12px rgba(13, 110, 253, 0.3);
                transition: all 0.3s ease;
            " onmouseover="this.style.transform='scale(1.1)'" onmouseout="this.style.transform='scale(1)'">
                <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="white">
                    <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413Z" />
                </svg>
            </a>

            <!-- Notification Bell (Maintenance Mode) -->
            <button onclick="showMaintenanceModal()" class="ripple" style="
                width: 48px;
                height: 48px;
                border-radius: 50%;
                background: linear-gradient(135deg, #FFC107 0%, #FFCA2C 100%);
                display: flex;
                align-items: center;
                justify-content: center;
                border: none;
                box-shadow: 0 4px 12px rgba(255, 193, 7, 0.3);
                transition: all 0.3s ease;
                cursor: pointer;
                position: relative;
            " onmouseover="this.style.transform='scale(1.1)'" onmouseout="this.style.transform='scale(1)'">
                <i data-lucide="bell" style="color: white; width: 22px; height: 22px;"></i>
                <?php if ($notificationCount > 0): ?>
                    <span style="
                        position: absolute;
                        top: -4px;
                        right: -4px;
                        background: #EF4444;
                        color: white;
                        font-size: 0.7rem;
                        font-weight: 700;
                        padding: 2px 6px;
                        border-radius: 10px;
                        min-width: 20px;
                        text-align: center;
                        box-shadow: 0 2px 8px rgba(239, 68, 68, 0.4);
                    "><?= $notificationCount ?></span>
                <?php else: ?>
                    <span style="
                        position: absolute;
                        top: 8px;
                        right: 8px;
                        width: 8px;
                        height: 8px;
                        background: #F59E0B;
                        border-radius: 50%;
                        border: 2px solid rgba(255,255,255,0.3);
                    "></span>
                <?php endif; ?>
            </button>

            <!-- User Avatar -->
            <div onclick="openSheet('sheet-perfil')" class="ripple" style="
                width: 48px;
                height: 48px;
                border-radius: 50%;
                background: linear-gradient(135deg, #047857 0%, #065f46 100%);
                display: flex;
                align-items: center;
                justify-content: center;
                overflow: hidden;
                cursor: pointer;
                border: 2px solid rgba(255,255,255,0.3);
                box-shadow: 0 4px 12px rgba(4, 120, 87, 0.3);
                transition: all 0.3s ease;
            " onmouseover="this.style.transform='scale(1.1)'" onmouseout="this.style.transform='scale(1)'">
                <?php if (!empty($avatar)): ?>
                    <img src="../assets/uploads/<?= htmlspecialchars($avatar) ?>" style="width: 100%; height: 100%; object-fit: cover;">
                <?php else: ?>
                    <span style="font-weight: 700; font-size: 1.1rem; color: white;">
                        <?= $userInitials ?>
                    </span>
                <?php endif; ?>
            </div>
        </div>

        <!-- Maintenance Modal (Global) -->
        <div id="maintenance-modal-global" class="bottom-sheet-overlay" onclick="closeMaintenanceModal()">
            <div class="bottom-sheet-content" onclick="event.stopPropagation()" style="max-width: 400px; margin: 0 auto;">
                <div style="text-align: center; padding: 40px 20px;">
                    <div style="
                        width: 80px;
                        height: 80px;
                        background: linear-gradient(135deg, #FFC107 0%, #FFCA2C 100%);
                        border-radius: 50%;
                        display: flex;
                        align-items: center;
                        justify-content: center;
                        margin: 0 auto 20px;
                        font-size: 3rem;
                    ">
                        🔧
                    </div>
                    <h3 style="margin: 0 0 12px; font-size: 1.5rem; font-weight: 800; color: var(--text-primary);">Funcionalidade em Desenvolvimento</h3>
                    <p style="color: var(--text-secondary); margin-bottom: 24px; line-height: 1.6;">
                        O sistema de notificações está sendo implementado e estará disponível em breve! Por enquanto, acesse o <strong>Quadro de Avisos</strong> para ver as atualizações.
                    </p>
                    <div style="display: flex; gap: 12px;">
                        <a href="avisos.php" class="btn-primary ripple" style="
                            flex: 1;
                            justify-content: center;
                            text-decoration: none;
                            padding: 14px;
                        ">
                            <i data-lucide="bell" style="width: 18px;"></i>
                            Ver Avisos
                        </a>
                        <button onclick="closeMaintenanceModal()" class="ripple" style="
                            flex: 1;
                            padding: 14px;
                            background: var(--bg-tertiary);
                            border: 1px solid var(--border-subtle);
                            border-radius: 12px;
                            color: var(--text-primary);
                            font-weight: 600;
                            cursor: pointer;
                            display: flex;
                            align-items: center;
                            justify-content: center;
                            gap: 8px;
                        ">
                            <i data-lucide="x" style="width: 18px;"></i>
                            Fechar
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <script>
            function showMaintenanceModal() {
                document.getElementById('maintenance-modal-global').classList.add('active');
                if (typeof lucide !== 'undefined') lucide.createIcons();
            }

            function closeMaintenanceModal() {
                document.getElementById('maintenance-modal-global').classList.remove('active');
            }
        </script>

        <!-- Main Content Wrapper -->
        <main class="app-content">
        <?php
    }

    /**
     * Renderiza um cabeçalho Hero padronizado
     * 
     * @param string $title Título principal do cabeçalho
     * @param string $subtitle Subtítulo (padrão: "Louvor PIB Oliveira")
     * @param string $backUrl URL do botão voltar (padrão: "index.php")
     * @param string $backIcon Ícone do botão voltar (padrão: "arrow-left")
     * @param bool $showProfile Se deve mostrar o avatar de perfil (padrão: true)
     * @param string $extraButton HTML extra para botões adicionais (opcional)
     */
    function renderHeroHeader($title, $subtitle = 'Louvor PIB Oliveira', $backUrl = 'index.php', $backIcon = 'arrow-left', $showProfile = true, $extraButton = '')
    {
        $avatar = $_SESSION['user_avatar'] ?? null;
        $userInitials = substr($_SESSION['user_name'] ?? 'U', 0, 1);
        ?>
            <!-- Hero Header -->
            <div style="
        background: linear-gradient(135deg, #047857 0%, #065f46 100%); 
        margin: -24px -16px 32px -16px; 
        padding: 32px 24px 64px 24px; 
        border-radius: 0 0 32px 32px; 
        box-shadow: var(--shadow-md);
        position: relative;
        overflow: visible;
    ">
                <!-- Navigation Row -->
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px;">
                    <a href="<?= $backUrl ?>" class="ripple" style="
                padding: 10px 20px;
                border-radius: 50px; 
                display: flex; 
                align-items: center; 
                justify-content: center; 
                gap: 8px;
                color: #047857; 
                background: white; 
                text-decoration: none;
                font-weight: 700;
                font-size: 0.9rem;
                box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            ">
                        <i data-lucide="<?= $backIcon ?>" style="width: 16px;"></i> Voltar
                    </a>

                    <div style="display: flex; gap: 12px; align-items: center;">
                        <?= $extraButton ?>

                        <?php if ($showProfile): ?>
                            <div onclick="openSheet('sheet-perfil')" class="ripple" style="
                        width: 40px; 
                        height: 40px; 
                        border-radius: 50%; 
                        background: rgba(255,255,255,0.2); 
                        display: flex; 
                        align-items: center; 
                        justify-content: center; 
                        overflow: hidden; 
                        cursor: pointer;
                        border: 2px solid rgba(255,255,255,0.3);
                    ">
                                <?php if (!empty($avatar)): ?>
                                    <img src="../assets/uploads/<?= htmlspecialchars($avatar) ?>" style="width: 100%; height: 100%; object-fit: cover;">
                                <?php else: ?>
                                    <span style="font-weight: 700; font-size: 0.9rem; color: white;">
                                        <?= $userInitials ?>
                                    </span>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <div style="display: flex; justify-content: space-between; align-items: flex-start;">
                    <div>
                        <h1 style="color: white; margin: 0; font-size: 2rem; font-weight: 800; letter-spacing: -0.5px;"><?= htmlspecialchars($title) ?></h1>
                        <p style="color: rgba(255,255,255,0.9); margin-top: 4px; font-weight: 500; font-size: 0.95rem;"><?= htmlspecialchars($subtitle) ?></p>
                    </div>
                </div>
            </div>
        <?php
    }

    function renderAppFooter()
    {
        $avatar = $_SESSION['user_avatar'] ?? null;
        $userInitials = substr($_SESSION['user_name'] ?? 'U', 0, 1);
        ?>
        </main>

        <!-- Bottom Navigation (5 Categories) -->
        <nav class="bottom-nav-categories">
            <a href="index.php" class="nav-cat-item ripple" id="nav-home">
                <i data-lucide="home"></i>
                <span>Início</span>
            </a>

            <button class="nav-cat-item ripple" onclick="openSheet('sheet-gestao')">
                <i data-lucide="layout-grid"></i>
                <span>Gestão</span>
            </button>
            <button class="nav-cat-item ripple" onclick="openSheet('sheet-espiritualidade')">
                <i data-lucide="heart-handshake"></i>
                <span>Espírito</span>
            </button>
            <button class="nav-cat-item ripple" onclick="openSheet('sheet-comunicacao')">
                <i data-lucide="message-circle"></i>
                <span>Comunica</span>
            </button>


        </nav>

        <!-- Bottom Sheets (Submenus) -->

        <!-- 1. Perfil / Configurações Dropdown -->
        <div id="sheet-perfil" class="profile-dropdown-overlay" onclick="closeSheet(this)">
            <div class="profile-dropdown-content" onclick="event.stopPropagation()">
                <!-- Styles for this dropdown specifically -->
                <style>
                    .profile-dropdown-overlay {
                        position: fixed;
                        top: 0;
                        left: 0;
                        width: 100%;
                        height: 100%;
                        z-index: 2000;
                        display: none;
                    }

                    .profile-dropdown-overlay.active {
                        display: block;
                    }

                    .profile-dropdown-content {
                        position: absolute;
                        top: 70px;
                        right: 20px;
                        width: 260px;
                        background: var(--bg-secondary);
                        border: 1px solid var(--border-subtle);
                        border-radius: 16px;
                        padding: 16px;
                        box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2);
                        transform-origin: top right;
                        animation: scaleIn 0.2s ease;
                    }

                    @keyframes scaleIn {
                        from {
                            opacity: 0;
                            transform: scale(0.95);
                        }

                        to {
                            opacity: 1;
                            transform: scale(1);
                        }
                    }
                </style>

                <!-- Profile Header Compact -->
                <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 20px;">
                    <div style="width: 50px; height: 50px; border-radius: 50%; overflow: hidden; background: var(--bg-tertiary); border: 2px solid var(--border-subtle); flex-shrink: 0; display: flex; align-items: center; justify-content: center;">
                        <?php if (!empty($_SESSION['user_avatar'])): ?>
                            <img src="../assets/uploads/<?= htmlspecialchars($_SESSION['user_avatar']) ?>" style="width: 100%; height: 100%; object-fit: cover;">
                        <?php else: ?>
                            <span style="font-size: 1.2rem; font-weight: 800; color: var(--text-secondary);"><?= substr($_SESSION['user_name'] ?? 'U', 0, 1) ?></span>
                        <?php endif; ?>
                    </div>
                    <div style="overflow: hidden;">
                        <h3 style="font-size: 1.1rem; font-weight: 700; color: var(--text-primary); margin: 0; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;"><?= htmlspecialchars($_SESSION['user_name'] ?? 'Visitante') ?></h3>
                        <p style="font-size: 0.85rem; color: var(--text-secondary); margin: 0; text-transform: capitalize;"><?= htmlspecialchars($_SESSION['user_role'] ?? 'Membro') ?></p>
                    </div>
                </div>

                <!-- Menu Options Compact -->
                <div style="display: flex; flex-direction: column; gap: 8px;">

                    <?php
                    // Botão Painel Líder (Restrito)
                    $allowedAdmins = ['Diego', 'Thalyta', 'diego', 'thalyta'];
                    $userName = $_SESSION['user_name'] ?? '';
                    $isAdminUser = false;
                    foreach ($allowedAdmins as $admin) {
                        if (stripos($userName, $admin) !== false) {
                            $isAdminUser = true;
                            break;
                        }
                    }

                    if ($isAdminUser):
                    ?>
                        <a href="lider.php" class="ripple" style="
                            display: flex; align-items: center; gap: 12px; 
                            padding: 14px; 
                            border-radius: 12px; 
                            background: rgba(220, 53, 69, 0.08); 
                            color: #DC3545; 
                            text-decoration: none; 
                            transition: background 0.1s;
                            margin-bottom: 8px;
                            border: 1px solid rgba(220, 53, 69, 0.1);
                        ">
                            <i data-lucide="shield-check" style="width: 20px;"></i>
                            <span style="flex: 1; font-weight: 700; font-size: 0.95rem;">Painel Líder</span>
                            <i data-lucide="chevron-right" style="width: 16px; opacity: 0.6;"></i>
                        </a>
                    <?php endif; ?>

                    <!-- Meus Dados -->
                    <a href="perfil.php" class="ripple" style="display: flex; align-items: center; gap: 12px; padding: 12px; border-radius: 12px; background: var(--bg-primary); color: var(--text-primary); text-decoration: none; transition: background 0.1s;">
                        <div style="width: 32px; height: 32px; border-radius: 8px; background: var(--bg-tertiary); display: flex; align-items: center; justify-content: center; color: var(--text-primary);">
                            <i data-lucide="user" style="width: 18px;"></i>
                        </div>
                        <span style="flex: 1; font-weight: 500; font-size: 0.95rem;">Meus Dados</span>
                    </a>

                    <!-- Trocar Foto -->
                    <a href="perfil.php" class="ripple" style="display: flex; align-items: center; gap: 12px; padding: 12px; border-radius: 12px; background: var(--bg-primary); color: var(--text-primary); text-decoration: none; transition: background 0.1s;">
                        <div style="width: 32px; height: 32px; border-radius: 8px; background: var(--bg-tertiary); display: flex; align-items: center; justify-content: center; color: var(--text-primary);">
                            <i data-lucide="camera" style="width: 18px;"></i>
                        </div>
                        <span style="flex: 1; font-weight: 500; font-size: 0.95rem;">Trocar Foto</span>
                    </a>

                    <!-- Tema Escuro -->
                    <div id="btn-theme-toggle" class="ripple" style="display: flex; align-items: center; gap: 12px; padding: 12px; border-radius: 12px; background: var(--bg-primary); color: var(--text-primary); cursor: pointer; transition: background 0.1s;">
                        <div style="width: 32px; height: 32px; border-radius: 8px; background: var(--bg-tertiary); display: flex; align-items: center; justify-content: center; color: var(--text-primary);">
                            <i data-lucide="moon" style="width: 18px;"></i>
                        </div>
                        <span style="flex: 1; font-weight: 500; font-size: 0.95rem;">Aparência</span>
                        <div style="font-size: 0.75rem; color: var(--text-muted); background: var(--bg-tertiary); padding: 4px 10px; border-radius: 20px; font-weight: 600;">Mudar</div>
                    </div>

                    <div style="height: 1px; background: var(--border-subtle); margin: 12px 0;"></div>

                    <!-- Sair -->
                    <a href="../includes/auth.php?logout=true" class="ripple" style="display: flex; align-items: center; gap: 12px; padding: 8px 12px; border-radius: 10px; color: var(--status-error); text-decoration: none; font-weight: 600; transition: background 0.1s;">
                        <i data-lucide="log-out" style="width: 18px;"></i>
                        Sair
                    </a>
                </div>
            </div>
        </div>

        <!-- 2. Gestão Sheet -->
        <div id="sheet-gestao" class="bottom-sheet-overlay" onclick="closeSheet(this)">
            <div class="bottom-sheet-content" onclick="event.stopPropagation()">
                <div class="sheet-header">Gestão</div>
                <div class="sheet-grid">
                    <a href="lider.php" class="sheet-item ripple">
                        <div class="sheet-icon-wrapper">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <circle cx="12" cy="12" r="3"></circle>
                                <path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"></path>
                            </svg>
                        </div>
                        <span>Líder</span>
                    </a>

                    <a href="escala.php" class="sheet-item ripple">
                        <div class="sheet-icon-wrapper">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
                                <line x1="16" y1="2" x2="16" y2="6"></line>
                                <line x1="8" y1="2" x2="8" y2="6"></line>
                                <line x1="3" y1="10" x2="21" y2="10"></line>
                            </svg>
                        </div>
                        <span>Escalas</span>
                    </a>
                    <a href="repertorio.php" class="sheet-item ripple">
                        <div class="sheet-icon-wrapper">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M9 18V5l12-2v13"></path>
                                <circle cx="6" cy="18" r="3"></circle>
                                <circle cx="18" cy="16" r="3"></circle>
                            </svg>
                        </div>
                        <span>Repertório</span>
                    </a>

                    <?php if (isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin'): ?>
                        <a href="membros.php" class="sheet-item ripple">
                            <div class="sheet-icon-wrapper">
                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                                    <circle cx="9" cy="7" r="4"></circle>
                                    <path d="M23 21v-2a4 4 0 0 0-3-3.87"></path>
                                    <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
                                </svg>
                            </div>
                            <span>Membros</span>
                        </a>
                        <a href="agenda.php" class="sheet-item ripple">
                            <div class="sheet-icon-wrapper">
                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <circle cx="12" cy="12" r="10"></circle>
                                    <polyline points="12 6 12 12 16 14"></polyline>
                                </svg>
                            </div>
                            <span>Agenda</span>
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- 3. Espiritualidade Sheet -->
        <div id="sheet-espiritualidade" class="bottom-sheet-overlay" onclick="closeSheet(this)">
            <div class="bottom-sheet-content" onclick="event.stopPropagation()">
                <div class="sheet-header">Espiritualidade</div>
                <div class="sheet-grid">
                    <a href="oracao.php" class="sheet-item ripple">
                        <div class="sheet-icon-wrapper">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M12 2L2 7l10 5 10-5-10-5z"></path>
                                <path d="M2 17l10 5 10-5"></path>
                                <path d="M2 12l10 5 10-5"></path>
                            </svg>
                        </div>
                        <span>Oração</span>
                    </a>
                    <a href="devocionais.php" class="sheet-item ripple">
                        <div class="sheet-icon-wrapper">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"></path>
                                <path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"></path>
                            </svg>
                        </div>
                        <span>Devocionais</span>
                    </a>
                    <a href="leitura.php" class="sheet-item ripple">
                        <div class="sheet-icon-wrapper">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M2 3h6a4 4 0 0 1 4 4v14a3 3 0 0 0-3-3H2z"></path>
                                <path d="M22 3h-6a4 4 0 0 0-4 4v14a3 3 0 0 1 3-3h7z"></path>
                            </svg>
                        </div>
                        <span>Leitura</span>
                    </a>
                </div>
            </div>
        </div>

        <!-- 4. Comunicação Sheet -->
        <div id="sheet-comunicacao" class="bottom-sheet-overlay" onclick="closeSheet(this)">
            <div class="bottom-sheet-content" onclick="event.stopPropagation()">
                <div class="sheet-header">Comunicação</div>
                <div class="sheet-grid">
                    <a href="avisos.php" class="sheet-item ripple">
                        <div class="sheet-icon-wrapper">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"></path>
                                <line x1="12" y1="9" x2="12" y2="13"></line>
                                <line x1="12" y1="17" x2="12.01" y2="17"></line>
                            </svg>
                        </div>
                        <span>Avisos</span>
                    </a>
                    <a href="indisponibilidade.php" class="sheet-item ripple">
                        <div class="sheet-icon-wrapper">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <circle cx="12" cy="12" r="10"></circle>
                                <line x1="4.93" y1="4.93" x2="19.07" y2="19.07"></line>
                            </svg>
                        </div>
                        <span>Indisponível</span>
                    </a>
                    <a href="aniversarios.php" class="sheet-item ripple">
                        <div class="sheet-icon-wrapper">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
                                <line x1="16" y1="2" x2="16" y2="6"></line>
                                <line x1="8" y1="2" x2="8" y2="6"></line>
                                <line x1="3" y1="10" x2="21" y2="10"></line>
                                <path d="M8 14h.01"></path>
                                <path d="M12 14h.01"></path>
                                <path d="M16 14h.01"></path>
                                <path d="M8 18h.01"></path>
                                <path d="M12 18h.01"></path>
                                <path d="M16 18h.01"></path>
                            </svg>
                        </div>
                        <span>Aniversários</span>
                    </a>
                </div>
            </div>
        </div>

        <!-- Script Global -->
        <script src="../assets/js/main.js"></script>
        <script>
            lucide.createIcons();

            // Theme Toggle Logic
            const themeBtn = document.getElementById('btn-theme-toggle');
            if (themeBtn) {
                const icon = themeBtn.querySelector('.emoji-icon');
                const text = themeBtn.querySelector('span');

                // Load saved theme
                if (localStorage.getItem('theme') === 'dark') {
                    document.body.classList.add('dark-mode');
                    if (icon) icon.textContent = '☀️';
                    if (text) text.textContent = 'Tema Claro';
                }

                themeBtn.addEventListener('click', () => {
                    document.body.classList.toggle('dark-mode');
                    if (document.body.classList.contains('dark-mode')) {
                        localStorage.setItem('theme', 'dark');
                        if (icon) icon.textContent = '☀️';
                        if (text) text.textContent = 'Tema Claro';
                    } else {
                        localStorage.setItem('theme', 'light');
                        if (icon) icon.textContent = '🌙';
                        if (text) text.textContent = 'Tema Escuro';
                    }
                });
            }

            // Bottom Sheets Logic
            function openSheet(id) {
                // Fechar todos
                document.querySelectorAll('.bottom-sheet-overlay').forEach(el => el.classList.remove('active'));
                document.querySelectorAll('.profile-dropdown-overlay').forEach(el => el.classList.remove('active'));

                // Abrir o solicitado
                const sheet = document.getElementById(id);
                if (sheet) {
                    sheet.classList.add('active');
                    // Add small vibration if mobile
                    if (navigator.vibrate) navigator.vibrate(50);
                } else {
                    console.error('Sheet not found:', id);
                }
            }

            function closeSheet(element) {
                if (element.classList.contains('bottom-sheet-overlay') || element.classList.contains('profile-dropdown-overlay')) {
                    element.classList.remove('active');
                }
            }
        </script>
    </body>

    </html>
<?php
    }
?>