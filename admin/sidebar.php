<?php
// admin/sidebar.php
// Recomposição Clássico-Premium de Alta Fidelidade.
// Mantém TODOS os links originais, a geometria clássica e o estilo visual premium leve.

$userId = $_SESSION['user_id'] ?? 1;

// Determine base path for links
$isAdminDir = strpos($_SERVER['PHP_SELF'], '/admin/') !== false;
$baseAdmin = $isAdminDir ? '' : '../admin/';
$baseApp = $isAdminDir ? '../app/' : 'app/';

if (!isset($pdo)) {
    require_once __DIR__ . '/../includes/db.php';
}

try {
    $stmtUser = $pdo->prepare("SELECT name, role, photo FROM users WHERE id = ?");
    $stmtUser->execute([$userId]);
    $sideUser = $stmtUser->fetch(PDO::FETCH_ASSOC);
} catch (Exception $e) { 
    $sideUser = null; 
}

$sideUserName = $sideUser['name'] ?? 'Músico';
$sideUserRole = $sideUser['role'] ?? 'user';
$sideUserPhoto = !empty($sideUser['photo']) ? $sideUser['photo'] : '';

if (empty($sideUserPhoto)) {
    $sideUserPhoto = 'https://ui-avatars.com/api/?name=' . urlencode($sideUserName) . '&background=1e40af&color=fff';
} elseif (strpos($sideUserPhoto, 'http') === false) {
    $sideUserPhoto = '../' . $sideUserPhoto;
}
?>

<div id="sidebar-overlay" class="sidebar-overlay" onclick="toggleSidebarMobile()"></div>

<aside id="app-sidebar" class="sidebar">
    <!-- 1. Cabeçalho Sidebar com Logo (Clicável para Recolher no Desktop) -->
    <div onclick="toggleSidebarDesktop()" class="sidebar-logo-container" title="Expandir/Recolher Menu">
        <div class="logo-area">
            <?php 
            $logoPath = '../assets/img/logo_pib_black.png';
            if (file_exists(__DIR__ . '/../assets/img/logo_pib_black.png')): 
            ?>
                <img src="<?= $logoPath ?>" alt="PIB Oliveira" class="logo-img">
            <?php else: ?>
                <div class="logo-fallback-icon"><i data-lucide="music-4"></i></div>
            <?php endif; ?>

            <div class="logo-text-block">
                <span class="sidebar-text main-brand-title">PIB Oliveira</span>
                <span class="sidebar-text sub-brand-title">App Louvor</span>
            </div>
        </div>
        <button class="side-close-mobile" onclick="toggleSidebarMobile(); event.stopPropagation();">
            <i data-lucide="x"></i>
        </button>
    </div>

    <!-- 2. Menu de Navegação -->
    <div class="sidebar-scroll-area">
        <nav class="sidebar-nav">
            
            <!-- SEÇÃO: PRINCIPAL -->
            <div class="nav-section">
                <span class="sidebar-text section-title-label">Principal</span>
                
                <a href="<?= $baseAdmin ?>index.php" class="nav-item nav-primary <?= basename($_SERVER['PHP_SELF']) == 'index.php' ? 'active' : '' ?>">
                    <div class="side-icon-box"><i data-lucide="layout-dashboard"></i></div>
                    <span class="sidebar-text">Visão Geral</span>
                </a>
            </div>

            <!-- SEÇÃO: GESTÃO DE ENSAIOS -->
            <div class="nav-section">
                <span class="sidebar-text section-title-label">Gestão de Ensaios</span>
                
                <a href="<?= $baseAdmin ?>escalas.php" class="nav-item nav-blue <?= basename($_SERVER['PHP_SELF']) == 'escalas.php' ? 'active' : '' ?>">
                    <div class="side-icon-box"><i data-lucide="calendar"></i></div>
                    <span class="sidebar-text">Escalas</span>
                </a>
                
                <a href="<?= $baseAdmin ?>repertorio.php" class="nav-item nav-blue <?= basename($_SERVER['PHP_SELF']) == 'repertorio.php' ? 'active' : '' ?>">
                    <div class="side-icon-box"><i data-lucide="music-2"></i></div>
                    <span class="sidebar-text">Repertório</span>
                </a>
                
                <a href="<?= $baseAdmin ?>historico.php" class="nav-item nav-blue <?= basename($_SERVER['PHP_SELF']) == 'historico.php' ? 'active' : '' ?>">
                    <div class="side-icon-box"><i data-lucide="history"></i></div>
                    <span class="sidebar-text">Histórico</span>
                </a>
                
                <a href="<?= $baseAdmin ?>membros.php" class="nav-item nav-blue <?= basename($_SERVER['PHP_SELF']) == 'membros.php' ? 'active' : '' ?>">
                    <div class="side-icon-box"><i data-lucide="users"></i></div>
                    <span class="sidebar-text">Membros</span>
                </a>
                
                <a href="<?= $baseAdmin ?>indisponibilidade.php" class="nav-item nav-blue <?= basename($_SERVER['PHP_SELF']) == 'indisponibilidade.php' ? 'active' : '' ?>">
                    <div class="side-icon-box"><i data-lucide="calendar-off"></i></div>
                    <span class="sidebar-text">Ausências</span>
                </a>

                <a href="<?= $baseAdmin ?>agenda.php" class="nav-item nav-blue <?= basename($_SERVER['PHP_SELF']) == 'agenda.php' ? 'active' : '' ?>">
                    <div class="side-icon-box"><i data-lucide="calendar-range"></i></div>
                    <span class="sidebar-text">Agenda</span>
                </a>
            </div>

            <!-- SEÇÃO: ESPIRITUAL -->
            <div class="nav-section">
                <span class="sidebar-text section-title-label">Espiritual</span>
                
                <a href="<?= $baseAdmin ?>devocionais.php" class="nav-item nav-green <?= basename($_SERVER['PHP_SELF']) == 'devocionais.php' ? 'active' : '' ?>">
                    <div class="side-icon-box"><i data-lucide="book-heart"></i></div>
                    <span class="sidebar-text">Devocional</span>
                </a>
                
                <a href="<?= $baseAdmin ?>oracao.php" class="nav-item nav-green <?= basename($_SERVER['PHP_SELF']) == 'oracao.php' ? 'active' : '' ?>">
                    <div class="side-icon-box"><i data-lucide="heart"></i></div>
                    <span class="sidebar-text">Oração</span>
                </a>
                
                <a href="<?= $baseAdmin ?>leitura.php" class="nav-item nav-green <?= basename($_SERVER['PHP_SELF']) == 'leitura.php' ? 'active' : '' ?>">
                    <div class="side-icon-box"><i data-lucide="book-open"></i></div>
                    <span class="sidebar-text">Leitura Bíblica</span>
                </a>
            </div>

            <!-- SEÇÃO: COMUNICAÇÃO -->
            <div class="nav-section">
                <span class="sidebar-text section-title-label">Comunicação</span>
                
                <a href="<?= $baseAdmin ?>avisos.php" class="nav-item nav-amber <?= basename($_SERVER['PHP_SELF']) == 'avisos.php' ? 'active' : '' ?>">
                    <div class="side-icon-box"><i data-lucide="megaphone"></i></div>
                    <span class="sidebar-text">Avisos</span>
                </a>
                
                <a href="<?= $baseAdmin ?>aniversarios.php" class="nav-item nav-amber <?= basename($_SERVER['PHP_SELF']) == 'aniversarios.php' ? 'active' : '' ?>">
                    <div class="side-icon-box"><i data-lucide="cake"></i></div>
                    <span class="sidebar-text">Aniversariantes</span>
                </a>
            </div>

            <!-- SEÇÃO: ADMINISTRAÇÃO (Apenas Líderes) -->
            <?php if ($sideUserRole === 'admin'): ?>
            <div class="nav-section">
                <span class="sidebar-text section-title-label">Administração</span>
                
                <a href="<?= $baseAdmin ?>escalas_gestao.php" class="nav-item nav-red <?= basename($_SERVER['PHP_SELF']) == 'escalas_gestao.php' ? 'active' : '' ?>">
                    <div class="side-icon-box"><i data-lucide="sliders"></i></div>
                    <span class="sidebar-text">Gestão de Escalas</span>
                </a>
                
                <a href="<?= $baseAdmin ?>relatorios_gerais.php" class="nav-item nav-red <?= basename($_SERVER['PHP_SELF']) == 'relatorios_gerais.php' ? 'active' : '' ?>">
                    <div class="side-icon-box"><i data-lucide="trending-up"></i></div>
                    <span class="sidebar-text">Relatórios</span>
                </a>
                
                <a href="<?= $baseAdmin ?>manutencao.php" class="nav-item nav-red <?= basename($_SERVER['PHP_SELF']) == 'manutencao.php' ? 'active' : '' ?>">
                    <div class="side-icon-box"><i data-lucide="database"></i></div>
                    <span class="sidebar-text">Manutenção</span>
                </a>
            </div>
            <?php endif; ?>

            <div class="nav-divider"></div>

            <!-- BOTÃO SAIR -->
            <div class="nav-section logout-section">
                <a href="../logout.php" class="nav-item nav-logout">
                    <div class="side-icon-box"><i data-lucide="log-out"></i></div>
                    <span class="sidebar-text">Sair do App</span>
                </a>
            </div>
        </nav>

        <!-- 3. Rodapé Créditos do Desenvolvedor -->
        <div class="sidebar-credits">
            <span class="sidebar-text credits-label">Desenvolvido por</span>
            <span class="sidebar-text developer-name">Diego T. N. Vilela</span>
        </div>
    </div>
</aside>

<style>
    /* ==========================================================================
       ESTILOS PREMIUM DA SIDEBAR CLÁSSICA (Lógica e Geometria Estáveis)
       ========================================================================== */
    :root {
        --sidebar-width: 200px;
        --sidebar-collapsed-width: 80px;
        --sidebar-bg: #0f172a; /* Slate 900 */
        --sidebar-text: #94a3b8; /* Slate 400 */
        --sidebar-border: rgba(255, 255, 255, 0.06);
        
        /* Paleta Clássica Premium (Sem Roxos) */
        --color-primary: #1e40af; /* Azul Profundo */
        --color-primary-light: rgba(30, 64, 175, 0.15);
        --color-blue-item: #3b82f6; /* Gestão */
        --color-blue-bg: rgba(59, 130, 246, 0.1);
        --color-green-item: #10b981; /* Espiritual */
        --color-green-bg: rgba(16, 185, 129, 0.1);
        --color-amber-item: #f59e0b; /* Comunicação */
        --color-amber-bg: rgba(245, 158, 11, 0.1);
        --color-red-item: #ef4444; /* Admin */
        --color-red-bg: rgba(239, 68, 68, 0.1);
    }

    body.dark-mode {
        --sidebar-bg: #0b0f19;
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
        overflow: hidden;
    }

    /* Scroll Area */
    .sidebar-scroll-area {
        flex: 1;
        overflow-y: auto;
        overflow-x: hidden;
        display: flex;
        flex-direction: column;
        justify-content: space-between;
    }

    .sidebar-scroll-area::-webkit-scrollbar {
        width: 4px;
    }
    .sidebar-scroll-area::-webkit-scrollbar-thumb {
        background: rgba(255, 255, 255, 0.1);
        border-radius: 2px;
    }

    /* Header Logo */
    .sidebar-logo-container {
        padding: 16px;
        display: flex;
        align-items: center;
        justify-content: space-between;
        cursor: pointer;
        border-bottom: 1px solid var(--sidebar-border);
        min-height: 72px;
        background: rgba(0, 0, 0, 0.15);
    }

    .logo-area {
        display: flex;
        align-items: center;
        gap: 12px;
        transition: all 0.3s;
    }

    .logo-img {
        height: 40px;
        width: auto;
        object-fit: contain;
        transition: transform 0.3s;
    }
    
    .logo-fallback-icon {
        width: 40px;
        height: 40px;
        background: var(--color-primary-light);
        color: var(--color-blue-item);
        border-radius: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
    }
    .logo-fallback-icon i {
        width: 20px;
        height: 20px;
    }

    .logo-text-block {
        display: flex;
        flex-direction: column;
        line-height: 1.1;
        transition: opacity 0.2s;
    }

    .main-brand-title {
        color: #f8fafc;
        font-weight: 800;
        font-size: 0.95rem;
    }

    .sub-brand-title {
        font-size: 0.72rem;
        color: var(--sidebar-text);
        font-weight: 600;
    }

    .side-close-mobile {
        display: none;
        background: transparent;
        border: none;
        color: var(--sidebar-text);
        cursor: pointer;
    }

    /* Navegação */
    .sidebar-nav {
        padding: 16px 12px;
    }

    .nav-section {
        margin-bottom: 16px;
    }

    .section-title-label {
        display: block;
        padding: 0 12px 6px;
        font-size: 0.7rem;
        color: rgba(255, 255, 255, 0.3);
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 1px;
        transition: opacity 0.2s;
    }

    .nav-item {
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 10px 12px;
        border-radius: 10px;
        color: var(--sidebar-text);
        text-decoration: none;
        font-weight: 500;
        font-size: 0.9rem;
        transition: all 0.2s ease;
        margin-bottom: 2px;
    }

    .side-icon-box {
        width: 32px;
        height: 32px;
        border-radius: 8px;
        display: flex;
        align-items: center;
        justify-content: center;
        transition: all 0.2s ease;
        flex-shrink: 0;
        background: rgba(255, 255, 255, 0.03);
    }
    
    .side-icon-box i {
        width: 17px;
        height: 17px;
        stroke-width: 2.2;
    }

    /* --- Temas de Cores Específicas nos Itens --- */
    
    /* 1. Principal (Azul Profundo) */
    .nav-item.nav-primary .side-icon-box { color: #60a5fa; }
    .nav-item.nav-primary:hover,
    .nav-item.nav-primary.active {
        background: var(--color-primary-light);
        color: #60a5fa;
    }
    .nav-item.nav-primary.active .side-icon-box {
        background: var(--color-primary);
        color: #ffffff;
    }

    /* 2. Gestão (Azul/Ciano) */
    .nav-item.nav-blue .side-icon-box { color: var(--color-blue-item); }
    .nav-item.nav-blue:hover,
    .nav-item.nav-blue.active {
        background: var(--color-blue-bg);
        color: #ffffff;
    }
    .nav-item.nav-blue.active .side-icon-box {
        background: var(--color-blue-item);
        color: #ffffff;
    }

    /* 3. Espiritual (Verde) */
    .nav-item.nav-green .side-icon-box { color: var(--color-green-item); }
    .nav-item.nav-green:hover,
    .nav-item.nav-green.active {
        background: var(--color-green-bg);
        color: #ffffff;
    }
    .nav-item.nav-green.active .side-icon-box {
        background: var(--color-green-item);
        color: #ffffff;
    }

    /* 4. Comunicação (Laranja/Âmbar) */
    .nav-item.nav-amber .side-icon-box { color: var(--color-amber-item); }
    .nav-item.nav-amber:hover,
    .nav-item.nav-amber.active {
        background: var(--color-amber-bg);
        color: #ffffff;
    }
    .nav-item.nav-amber.active .side-icon-box {
        background: var(--color-amber-item);
        color: #ffffff;
    }

    /* 5. Admin (Vermelho) */
    .nav-item.nav-red .side-icon-box { color: var(--color-red-item); }
    .nav-item.nav-red:hover,
    .nav-item.nav-red.active {
        background: var(--color-red-bg);
        color: #ffffff;
    }
    .nav-item.nav-red.active .side-icon-box {
        background: var(--color-red-item);
        color: #ffffff;
    }

    /* 6. Logout (Vermelho Suave) */
    .nav-item.nav-logout {
        color: #ef4444;
        background: rgba(239, 68, 68, 0.05);
        margin-top: 10px;
    }
    .nav-item.nav-logout .side-icon-box {
        color: #ef4444;
        background: transparent;
    }
    .nav-item.nav-logout:hover {
        background: #ef4444;
        color: #ffffff;
    }
    .nav-item.nav-logout:hover .side-icon-box {
        color: #ffffff;
    }

    .nav-divider {
        height: 1px;
        background: var(--sidebar-border);
        margin: 8px 12px 16px;
    }

    /* Créditos do Desenvolvedor */
    .sidebar-credits {
        padding: 24px 16px;
        text-align: center;
        border-top: 1px solid var(--sidebar-border);
        background: rgba(0, 0, 0, 0.1);
        margin-top: auto;
    }

    .credits-label {
        display: block;
        font-size: 0.65rem;
        color: rgba(255, 255, 255, 0.25);
        margin-bottom: 2px;
        font-weight: 500;
    }

    .developer-name {
        font-size: 0.72rem;
        font-weight: 700;
        color: #cbd5e1;
    }

    /* ==========================================================================
       MODO RECOLHIDO (Desktop)
       ========================================================================== */
    .sidebar.collapsed {
        width: var(--sidebar-collapsed-width);
    }

    .sidebar.collapsed .sidebar-text {
        opacity: 0;
        pointer-events: none;
        width: 0;
        display: inline-block;
        overflow: hidden;
    }
    
    .sidebar.collapsed .section-title-label {
        height: 0;
        padding: 0;
        margin: 0;
        opacity: 0;
        overflow: hidden;
    }

    .sidebar.collapsed .logo-text-block {
        display: none;
    }

    .sidebar.collapsed .nav-item {
        justify-content: center;
        padding: 10px;
    }

    .sidebar.collapsed .logo-area {
        justify-content: center;
        width: 100%;
    }

    .sidebar.collapsed .sidebar-credits {
        display: none;
    }

    /* ==========================================================================
       MOBILE RESPONSIVO
       ========================================================================== */
    @media (max-width: 1024px) {
        .sidebar {
            transform: translateX(-100%);
            width: 240px;
            max-width: 80%;
            background: #0f172a;
            box-shadow: none;
        }

        .sidebar.open {
            transform: translateX(0);
            box-shadow: 10px 0 30px rgba(0, 0, 0, 0.5);
        }

        .side-close-mobile {
            display: flex;
        }
        
        .sidebar-logo-container {
            cursor: default;
        }
    }

    /* Overlay Mobile */
    .sidebar-overlay {
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: rgba(15, 23, 42, 0.6);
        backdrop-filter: blur(4px);
        z-index: 999;
        opacity: 0;
        visibility: hidden;
        transition: all 0.3s ease;
    }

    .sidebar-overlay.active {
        opacity: 1;
        visibility: visible;
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
            content.style.marginLeft = isCollapsed ? 'var(--sidebar-collapsed-width)' : 'var(--sidebar-width)';
        }
        localStorage.setItem('sidebarCollapsed', isCollapsed);
    }

    function toggleSidebarMobile() {
        sidebar.classList.toggle('open');
        document.getElementById('sidebar-overlay').classList.toggle('active');
    }

    // Inicialização geométrica no carregamento
    document.addEventListener('DOMContentLoaded', () => {
        if (isDesktop()) {
            const savedCollapsed = localStorage.getItem('sidebarCollapsed') === 'true';
            if (savedCollapsed) {
                sidebar.classList.add('collapsed');
                if (content) content.style.marginLeft = 'var(--sidebar-collapsed-width)';
            } else {
                sidebar.classList.remove('collapsed');
                if (content) content.style.marginLeft = 'var(--sidebar-width)';
            }
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
