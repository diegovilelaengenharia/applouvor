<?php
// admin/sidebar.php

$userId = $_SESSION['user_id'] ?? 1;

// Determine base path for links
$isAdminDir = strpos($_SERVER['PHP_SELF'], '/admin/') !== false;
$baseAdmin = $isAdminDir ? '' : '../admin/';
$baseApp = $isAdminDir ? '../app/' : '';

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
    <!-- Cabeçalho Sidebar com Logo (Clicável para Recolher) -->
    <div onclick="toggleSidebarDesktop()" style="padding: 18px 16px; display: flex; align-items: center; justify-content: space-between; cursor: pointer; transition: all 0.3s;" title="Expandir/Recolher Menu" class="sidebar-header-hover">
        <div class="logo-area" style="font-weight: 800; color: var(--slate-900); font-size: 1.1rem; display: flex; align-items: center; gap: 12px;">
            <!-- Logo Imagem -->
            <img src="../assets/img/logo_pib_black.png" alt="PIB Oliveira" class="sidebar-logo" style="height: 40px; width: auto; object-fit: contain; transition: all 0.3s; filter: drop-shadow(0 2px 4px rgba(0,0,0,0.1));">

            <div style="display: flex; flex-direction: column; line-height: 1.1;">
                <span class="sidebar-text sidebar-church-name" style="
                    color: var(--slate-700);
                    font-size: 0.95rem; 
                    font-weight: 700;
                    transition: all 0.3s;
                ">PIB Oliveira</span>
                <span class="sidebar-text sidebar-app-name" style="
                    color: var(--slate-500);
                    font-size: 0.7rem; 
                    font-weight: 600;
                    transition: all 0.3s;
                ">App Louvor</span>
            </div>
        </div>
    </div>

    <style>
        /* Efeito hover no header da sidebar */
        .sidebar-header-hover:hover {
            background: linear-gradient(90deg, transparent 0%, rgba(147, 51, 234, 0.05) 100%);
        }

        .sidebar-header-hover:hover .sidebar-logo {
            transform: scale(1.05) rotate(-2deg);
            filter: drop-shadow(0 4px 8px rgba(147, 51, 234, 0.3));
        }

        .sidebar-header-hover:hover .sidebar-church-name {
            color: var(--purple-600);
        }

        .sidebar-header-hover:hover .sidebar-app-name {
            color: var(--green-600);
        }
    </style>

    <!-- 2. Menu -->
    <nav class="sidebar-nav">
        <!-- (Botão líder removido da sidebar e movido para header) -->

        <a href="<?= $baseAdmin ?>index.php" class="nav-item nav-multicolor <?= basename($_SERVER['PHP_SELF']) == 'index.php' ? 'active' : '' ?>">
            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <rect width="7" height="9" x="3" y="3" rx="1" fill="#3b82f6" stroke="#3b82f6" />
                <rect width="7" height="5" x="14" y="3" rx="1" fill="#10b981" stroke="#10b981" />
                <rect width="7" height="9" x="14" y="12" rx="1" fill="#fbbf24" stroke="#fbbf24" />
                <rect width="7" height="5" x="3" y="16" rx="1" fill="#ef4444" stroke="#ef4444" />
            </svg>
            <span class="sidebar-text">Visão Geral</span>
        </a>

        <div class="nav-divider"></div>
        <div class="sidebar-text" style="padding: 0 12px 4px 12px; font-size: 0.75rem; color: var(--slate-400); font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px;">Gestão de Ensaios</div>

        <a href="<?= $baseAdmin ?>escalas.php" class="nav-item nav-blue <?= basename($_SERVER['PHP_SELF']) == 'escalas.php' ? 'active' : '' ?>">
            <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="currentColor">
                <path d="M19 4h-1V2h-2v2H8V2H6v2H5c-1.11 0-1.99.9-1.99 2L3 20c0 1.1.89 2 2 2h14c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2zm0 16H5V10h14v10zM9 14H7v-2h2v2zm4 0h-2v-2h2v2zm4 0h-2v-2h2v2zm-8 4H7v-2h2v2zm4 0h-2v-2h2v2zm4 0h-2v-2h2v2z"/>
            </svg>
            <span class="sidebar-text">Escalas</span>
        </a>
        <a href="<?= $baseAdmin ?>repertorio.php" class="nav-item nav-blue <?= basename($_SERVER['PHP_SELF']) == 'repertorio.php' ? 'active' : '' ?>">
            <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="currentColor">
                <path d="M12 3v10.55c-.59-.34-1.27-.55-2-.55-2.21 0-4 1.79-4 4s1.79 4 4 4 4-1.79 4-4V7h4V3h-6z"/>
            </svg>
            <span class="sidebar-text">Repertório</span>
        </a>

        <a href="<?= $baseAdmin ?>historico.php" class="nav-item nav-blue <?= basename($_SERVER['PHP_SELF']) == 'historico.php' ? 'active' : '' ?>">
            <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="currentColor">
                <path d="M13 3c-4.97 0-9 4.03-9 9H1l3.89 3.89.07.14L9 12H6c0-3.87 3.13-7 7-7s7 3.13 7 7-3.13 7-7 7c-1.93 0-3.68-.79-4.94-2.06l-1.42 1.42C8.27 19.99 10.51 21 13 21c4.97 0 9-4.03 9-9s-4.03-9-9-9zm-1 5v5l4.28 2.54.72-1.21-3.5-2.08V8H12z"/>
            </svg>
            <span class="sidebar-text">Histórico</span>
        </a>

        <a href="<?= $baseAdmin ?>membros.php" class="nav-item nav-blue <?= basename($_SERVER['PHP_SELF']) == 'membros.php' ? 'active' : '' ?>">
            <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="currentColor">
                <path d="M16 11c1.66 0 2.99-1.34 2.99-3S17.66 5 16 5c-1.66 0-3 1.34-3 3s1.34 3 3 3zm-8 0c1.66 0 2.99-1.34 2.99-3S9.66 5 8 5C6.34 5 5 6.34 5 8s1.34 3 3 3zm0 2c-2.33 0-7 1.17-7 3.5V19h14v-2.5c0-2.33-4.67-3.5-7-3.5zm8 0c-.29 0-.62.02-.97.05 1.16.84 1.97 1.97 1.97 3.45V19h6v-2.5c0-2.33-4.67-3.5-7-3.5z"/>
            </svg>
            <span class="sidebar-text">Membros</span>
        </a>

        <a href="<?= $baseAdmin ?>indisponibilidade.php" class="nav-item nav-blue <?= basename($_SERVER['PHP_SELF']) == 'indisponibilidade.php' ? 'active' : '' ?>">
            <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="currentColor">
                <path d="M19 4h-1V2h-2v2H8V2H6v2H5c-1.11 0-1.99.9-1.99 2L3 20c0 1.1.89 2 2 2h14c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2zm0 16H5V10h14v10z"/>
                <path d="M7 12h10v2H7z" fill="#ef4444"/>
            </svg>
            <span class="sidebar-text">Ausências</span>
        </a>

        <a href="<?= $baseAdmin ?>agenda.php" class="nav-item nav-blue <?= basename($_SERVER['PHP_SELF']) == 'agenda.php' ? 'active' : '' ?>">
            <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="currentColor">
                <path d="M19 4h-1V2h-2v2H8V2H6v2H5c-1.11 0-1.99.9-1.99 2L3 20c0 1.1.89 2 2 2h14c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2zm0 16H5V10h14v10zm-7-8h5v5h-5z"/>
            </svg>
            <span class="sidebar-text">Agenda</span>
        </a>

        <div class="nav-divider"></div>
        <div class="sidebar-text" style="padding: 0 12px 4px 12px; font-size: 0.75rem; color: var(--slate-400); font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px;">Espiritual</div>

        <a href="<?= $baseAdmin ?>devocionais.php" class="nav-item nav-green <?= basename($_SERVER['PHP_SELF']) == 'devocionais.php' ? 'active' : '' ?>">
            <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="currentColor">
                <path d="M21 5c-1.11-.35-2.33-.5-3.5-.5-1.95 0-4.05.4-5.5 1.5-1.45-1.1-3.55-1.5-5.5-1.5S2.45 4.9 1 6v14.65c0 .25.25.5.5.5.1 0 .15-.05.25-.05C3.1 20.45 5.05 20 6.5 20c1.95 0 4.05.4 5.5 1.5 1.35-.85 3.8-1.5 5.5-1.5 1.65 0 3.35.3 4.75 1.05.1.05.15.05.25.05.25 0 .5-.25.5-.5V6c-.6-.45-1.25-.75-2-1zm0 13.5c-1.1-.35-2.3-.5-3.5-.5-1.7 0-4.15.65-5.5 1.5V8c1.35-.85 3.8-1.5 5.5-1.5 1.2 0 2.4.15 3.5.5v11.5z"/>
            </svg>
            <span class="sidebar-text">Espiritualidade</span>
        </a>
        <a href="<?= $baseAdmin ?>leitura.php" class="nav-item nav-green <?= basename($_SERVER['PHP_SELF']) == 'leitura.php' ? 'active' : '' ?>">
            <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="currentColor">
                <path d="M21 5c-1.11-.35-2.33-.5-3.5-.5-1.95 0-4.05.4-5.5 1.5-1.45-1.1-3.55-1.5-5.5-1.5S2.45 4.9 1 6v14.65c0 .25.25.5.5.5.1 0 .15-.05.25-.05C3.1 20.45 5.05 20 6.5 20c1.95 0 4.05.4 5.5 1.5 1.35-.85 3.8-1.5 5.5-1.5 1.65 0 3.35.3 4.75 1.05.1.05.15.05.25.05.25 0 .5-.25.5-.5V6c-.6-.45-1.25-.75-2-1zM11 17.08c-1.43-.59-3.34-1.08-4.5-1.08-1.16 0-2.65.3-3.5.6V7.28c.91-.3 2.34-.58 3.5-.58 1.16 0 3.07.49 4.5 1.08v9.3z"/>
            </svg>
            <span class="sidebar-text">Leitura Bíblica</span>
        </a>

        <div class="nav-divider"></div>
        <div class="sidebar-text" style="padding: 0 12px 4px 12px; font-size: 0.75rem; color: var(--slate-400); font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px;">Comunicação</div>

        <a href="<?= $baseAdmin ?>avisos.php" class="nav-item nav-yellow <?= basename($_SERVER['PHP_SELF']) == 'avisos.php' ? 'active' : '' ?>">
            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M6 8a6 6 0 0 1 12 0c0 7 3 9 3 9H3s3-2 3-9" />
                <path d="M10.3 21a1.94 1.94 0 0 0 3.4 0" />
            </svg>
            <span class="sidebar-text">Avisos</span>
        </a>

        <!-- Botão Especial: Reunião 08/02 -->
        <a href="<?= $baseAdmin ?>reuniao_fev_2026.php" class="nav-item nav-meeting <?= basename($_SERVER['PHP_SELF']) == 'reuniao_fev_2026.php' ? 'active' : '' ?>">
            <div style="position: relative; display: flex; align-items: center; gap: 12px; width: 100%;">
                <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <rect x="2" y="3" width="20" height="14" rx="2" ry="2"/>
                    <line x1="8" y1="21" x2="16" y2="21"/>
                    <line x1="12" y1="17" x2="12" y2="21"/>
                </svg>
                <span class="sidebar-text" style="flex: 1;">Reunião 08/02</span>
                <span class="meeting-badge sidebar-text" style="background: rgba(255,255,255,0.25); padding: 2px 8px; border-radius: 6px; font-size: 0.7rem; font-weight: 700;">NOVO</span>
            </div>
        </a>
        
        <!-- Botão Backup Offline -->
        <a href="<?= $baseAdmin ?>apresentacao_offline.html" target="_blank" class="nav-item nav-meeting" style="background: var(--slate-700); border-color: var(--slate-600); margin-top: 4px;">
            <div style="position: relative; display: flex; align-items: center; gap: 12px; width: 100%;">
                <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/>
                    <polyline points="7 10 12 15 17 10"/>
                    <line x1="12" y1="15" x2="12" y2="3"/>
                </svg>
                <span class="sidebar-text" style="flex: 1;">Backup Offline</span>
            </div>
        </a>



    </nav>

    <!-- 3. Rodapé Integrado -->
    <!-- 3. Rodapé Integrado (REMOVIDO REQUISIÇÃO USUARIO) -->
    <!-- Perfil movido para o Header Superior -->


    <!-- Créditos do Desenvolvedor -->
    <div class="sidebar-credits" style="margin-top: auto; padding: 20px; text-align: center; font-size: 0.65rem; color: var(--slate-400); border-top: 1px solid var(--slate-100);">
        <span class="sidebar-text" style="display: block; margin-bottom: 2px;">Desenvolvido por</span>
        <span class="sidebar-text" style="font-weight: 600; color: var(--slate-500); font-size: 0.7rem;">Diego T. N. Vilela</span>
    </div>
</div>

<style>
    /* VARIÁVEIS LOCAIS (Compatíveis com Modo Moderate) */
    :root {
        --sidebar-width: 190px;
        --sidebar-collapsed-width: 88px;
        --sidebar-bg: #ffffff;
        --sidebar-text: var(--slate-600);
        --brand-primary: var(--slate-600);
        --brand-light: var(--slate-100);
        --brand-hover: var(--slate-100);
    }

    /* Sidebar Layout */
    .sidebar {
        position: fixed;
        left: 0;
        top: 0;
        bottom: 0;
        width: var(--sidebar-width);
        background: var(--sidebar-bg);
        border-right: 1px solid var(--slate-200);
        display: flex;
        flex-direction: column;
        z-index: 1000;
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        overflow-y: auto;
        overflow-x: hidden;
    }

    /* Invisible Scrollbar */
    .sidebar::-webkit-scrollbar {
        width: 6px;
    }

    .sidebar::-webkit-scrollbar-track {
        background: transparent;
    }

    .sidebar::-webkit-scrollbar-thumb {
        background: transparent;
        border-radius: 3px;
    }

    .sidebar:hover::-webkit-scrollbar-thumb {
        background: rgba(0, 0, 0, 0.1);
    }

    /* Firefox */
    .sidebar {
        scrollbar-width: thin;
        scrollbar-color: transparent transparent;
    }

    .sidebar:hover {
        scrollbar-color: rgba(0, 0, 0, 0.1) transparent;
    }

    /* Navegação Item */
    .nav-item {
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 10px 12px;
        /* Touch target melhor */
        border-radius: 8px;
        color: var(--sidebar-text);
        text-decoration: none;
        font-weight: 500;
        font-size: 0.9375rem;
        /* 15px */
        transition: all 0.2s;
        margin-bottom: 2px;
    }

    /* Colors Classes restored */
    .nav-item.nav-primary:hover,
    .nav-item.nav-primary.active {
        background-color: var(--slate-100);
        color: var(--slate-700);
    }

    .nav-item.nav-primary.active svg {
        color: var(--slate-600);
    }

    /* GESTÃO → NEUTRO COM ACENTO AZUL */
    .nav-item.nav-emerald svg, .nav-item.nav-blue svg, .nav-item.nav-violet svg { color: var(--slate-400); }
    .nav-item.nav-emerald:hover, .nav-item.nav-emerald.active,
    .nav-item.nav-blue:hover, .nav-item.nav-blue.active,
    .nav-item.nav-violet:hover, .nav-item.nav-violet.active { background-color: var(--slate-100); color: var(--slate-700); }
    
    .nav-item.nav-emerald.active svg,
    .nav-item.nav-blue.active svg,
    .nav-item.nav-violet.active svg { color: var(--slate-600); }

    /* ESPIRITUAL → NEUTRO COM ACENTO VERDE */
    .nav-item.nav-indigo svg, .nav-item.nav-rose svg, .nav-item.nav-cyan svg { color: var(--slate-400); }
    .nav-item.nav-indigo:hover, .nav-item.nav-indigo.active,
    .nav-item.nav-rose:hover, .nav-item.nav-rose.active,
    .nav-item.nav-cyan:hover, .nav-item.nav-cyan.active { background-color: var(--sage-100); color: var(--sage-700); }
    
    .nav-item.nav-indigo.active svg,
    .nav-item.nav-rose.active svg,
    .nav-item.nav-cyan.active svg { color: var(--sage-600); }

    /* COMUNICAÇÃO → NEUTRO COM ACENTO ROXO */
    .nav-item.nav-amber svg, .nav-item.nav-pink svg { color: var(--slate-400); }
    .nav-item.nav-amber:hover, .nav-item.nav-amber.active,
    .nav-item.nav-pink:hover, .nav-item.nav-pink.active { background-color: var(--lavender-100); color: var(--lavender-700); }
    
    .nav-item.nav-amber.active svg,
    .nav-item.nav-pink.active svg { color: var(--lavender-600); }

    /* Indicadores (Blue) */
    .nav-item.nav-indicators svg {
        color: var(--slate-500);
    }

    .nav-item.nav-indicators:hover,
    .nav-item.nav-indicators.active {
        background-color: var(--slate-50);
        color: var(--slate-700);
    }

    .nav-item.nav-indicators:hover svg,
    .nav-item.nav-indicators.active svg {
        color: var(--slate-700);
    }


    .nav-item.active {
        font-weight: 700;
        box-shadow: 0 1px 2px rgba(0, 0, 0, 0.03);
    }

    .nav-item svg {
        width: 22px;
        height: 22px;
        transition: all 0.2s;
        /* Default fallback */
        color: var(--slate-400);
    }


    .nav-divider {
        height: 1px;
        background: var(--slate-200);
        margin: 8px 16px;
    }

    /* CSS Extra para o Footer Integrado */
    .sidebar-footer {
        padding: 16px;
        border-top: 1px solid var(--slate-200);
        background: var(--slate-50);
        /* Slate 50 */
    }

    .user-profile-integrated {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 8px;
    }

    .profile-link {
        display: flex;
        align-items: center;
        gap: 12px;
        text-decoration: none;
        flex: 1;
        min-width: 0;
        padding: 8px;
        border-radius: 8px;
        transition: background 0.2s;
    }

    .profile-link:hover {
        background: var(--slate-200);
    }

    .user-avatar-compact {
        width: 40px;
        height: 40px;
        border-radius: 10px;
        object-fit: cover;
        border: 2px solid #fff;
        box-shadow: 0 1px 2px rgba(0, 0, 0, 0.1);
    }

    .status-indicator {
        position: absolute;
        bottom: -2px;
        right: -2px;
        width: 12px;
        height: 12px;
        background: var(--sage-500);
        border: 2px solid #fff;
        border-radius: 50%;
    }

    .user-info-row {
        flex: 1;
        min-width: 0;
        display: flex;
        flex-direction: column;
        justify-content: center;
    }

    .u-name {
        font-size: 0.9375rem;
        /* 15px */
        font-weight: 600;
        color: var(--slate-700);
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .u-role {
        font-size: 0.75rem;
        color: var(--slate-500);
    }

    .actions-row {
        display: flex;
        align-items: center;
    }

    .action-icon-subtle {
        width: 40px;
        height: 40px;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 8px;
        color: var(--slate-400);
        transition: all 0.2s;
    }

    .action-icon-subtle:hover {
        background: #fff;
        color: var(--slate-900);
        box-shadow: 0 1px 2px rgba(0, 0, 0, 0.05);
    }

    .action-icon-subtle.danger:hover {
        color: var(--rose-500);
        background: #fff;
    }

    /* MODO RECOLHIDO (Desktop) */
    .sidebar.collapsed {
        width: var(--sidebar-collapsed-width);
    }

    .sidebar.collapsed .sidebar-text,
    .sidebar.collapsed .u-name,
    .sidebar.collapsed .u-role,
    .sidebar.collapsed .user-info-row {
        display: none;
    }

    .sidebar.collapsed .nav-item {
        justify-content: center;
        padding: 12px;
    }

    .sidebar.collapsed .nav-item i {
        margin: 0;
    }

    .sidebar.collapsed .logo-area {
        justify-content: center;
    }

    .sidebar.collapsed .logo-area img {
        height: 32px;
    }

    .sidebar.collapsed .profile-link {
        justify-content: center;
        padding: 0;
    }

    .sidebar.collapsed .user-profile-integrated {
        flex-direction: column;
        gap: 16px;
    }

    .sidebar.collapsed .actions-row {
        flex-direction: column;
        width: 100%;
    }

    /* MOBILE OVERRIDES */
    @media (max-width: 1024px) {
        .sidebar {
            transform: translateX(-100%);
            width: 200px;
            /* Largura Ultra Compacta via USER FEEDBACK */
            max-width: 80%;
        }

        .sidebar.open {
            transform: translateX(0);
            box-shadow: 4px 0 24px rgba(0, 0, 0, 0.15);
        }

        .sidebar-collapser,
        .btn-toggle-desktop {
            display: none;
        }
    }

    /* Overlay */
    .sidebar-overlay {
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: rgba(15, 23, 42, 0.4);
        /* Slate 900 com opacidade */
        backdrop-filter: blur(2px);
        z-index: 999;
        opacity: 0;
        visibility: hidden;
        transition: all 0.3s;
    }

    .sidebar-overlay.active {
        opacity: 1;
        visibility: visible;
    }

    /* Botão Especial Reunião */
    .nav-item.nav-meeting {
        background: linear-gradient(135deg, #10b981 0%, #059669 100%);
        color: white;
        margin: 8px 0;
        box-shadow: 0 4px 12px rgba(16, 185, 129, 0.4);
        border: 2px solid rgba(255, 255, 255, 0.3);
        position: relative;
        overflow: hidden;
    }

    .nav-item.nav-meeting::before {
        content: '';
        position: absolute;
        top: 0;
        left: -100%;
        width: 100%;
        height: 100%;
        background: linear-gradient(90deg, transparent, rgba(255,255,255,0.3), transparent);
        transition: left 0.5s;
    }

    .nav-item.nav-meeting:hover::before {
        left: 100%;
    }

    .nav-item.nav-meeting:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 20px rgba(16, 185, 129, 0.6);
        border-color: rgba(255, 255, 255, 0.5);
    }

    .nav-item.nav-meeting.active {
        box-shadow: 0 6px 24px rgba(16, 185, 129, 0.7);
        transform: scale(1.02);
        border-color: rgba(255, 255, 255, 0.6);
    }

    .nav-item.nav-meeting svg {
        color: white;
        stroke-width: 2.5px;
    }

    .meeting-badge {
        animation: pulse 2s ease-in-out infinite;
        background: rgba(255,255,255,0.3) !important;
        font-weight: 800 !important;
    }

    @keyframes pulse {
        0%, 100% {
            opacity: 1;
            transform: scale(1);
        }
        50% {
            opacity: 0.8;
            transform: scale(1.05);
        }
    }

    /* Sidebar collapsed - esconder badge */
    .sidebar.collapsed .meeting-badge {
        display: none;
    }
</style>

<!-- Overlay para Fechar no Mobile -->
<div id="sidebar-overlay" class="sidebar-overlay" onclick="toggleSidebarMobile()"></div>

<script>
    const sidebar = document.getElementById('app-sidebar');
    const content = document.getElementById('app-content');

    // --- State Management ---
    function isDesktop() {
        return window.innerWidth > 1024;
    }

    function toggleSidebarDesktop() {
        if (!isDesktop()) return;
        sidebar.classList.toggle('collapsed');
        const isCollapsed = sidebar.classList.contains('collapsed');
        if (content) content.style.marginLeft = isCollapsed ? '88px' : '190px';
        localStorage.setItem('sidebarCollapsed', isCollapsed);
    }

    function toggleSidebarMobile() {
        sidebar.classList.toggle('open');
        document.getElementById('sidebar-overlay').classList.toggle('active');
    }

    // --- Init ---
    document.addEventListener('DOMContentLoaded', () => {
        if (isDesktop()) {
            const savedCollapsed = localStorage.getItem('sidebarCollapsed') === 'true';
            if (savedCollapsed) {
                sidebar.classList.add('collapsed');
                if (content) content.style.marginLeft = '88px';
            } else {
                sidebar.classList.remove('collapsed');
                if (content) content.style.marginLeft = '190px';
            }
        }

        // --- SWIPE LOGIC FIXED (PREVENT BACK) ---
        let touchStartX = 0;
        let touchEndX = 0;
        const widthTrigger = 35; // Pixels da borda

        document.addEventListener('touchstart', (e) => {
            if (isDesktop()) return;
            touchStartX = e.touches[0].clientX;
        }, {
            passive: false
        });

        document.addEventListener('touchmove', (e) => {
            if (isDesktop()) return;
            const currentX = e.touches[0].clientX;

            // Se o toque começou na zona de SWIPE e está movendo para a direita...
            if (touchStartX < widthTrigger && currentX > touchStartX) {
                // ...BLOQUEIA o comportamento padrão (que seria "Voltar" no navegador)
                e.preventDefault();
            }
        }, {
            passive: false
        });

        document.addEventListener('touchend', (e) => {
            if (isDesktop()) return;
            touchEndX = e.changedTouches[0].clientX;
            handleSwipe();
        }, {
            passive: true
        });

        function handleSwipe() {
            const threshold = 60; // Sensibilidade
            // Swipe Right -> Abrir
            if (touchStartX < widthTrigger && touchEndX > touchStartX + threshold) {
                if (!sidebar.classList.contains('open')) toggleSidebarMobile();
            }
            // Swipe Left -> Fechar
            if (touchEndX < touchStartX - threshold) {
                if (sidebar.classList.contains('open')) toggleSidebarMobile();
            }
        }
    });

    // Torna global
    window.toggleSidebar = toggleSidebarMobile;
</script>