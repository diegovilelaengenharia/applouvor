<?php
// admin/sidebar.php
// Recomposição Premium de Alta Fidelidade para a Barra Lateral baseada em Tailwind CSS
// Mantém todas as consultas e variáveis estáveis, mas migra a interface para o tema unificado do Stitch.

$userId = $_SESSION['user_id'] ?? 1;

// Determine base path for links
$isAdminDir = strpos($_SERVER['PHP_SELF'], '/admin/') !== false;
$baseAdmin = $isAdminDir ? '' : '../admin/';
$baseApp = $isAdminDir ? '../app/' : 'app/';

if (!isset($pdo)) {
    require_once __DIR__ . '/../src/config/db.php';
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
    $sideUserPhoto = 'https://ui-avatars.com/api/?name=' . urlencode($sideUserName) . '&background=0059b8&color=fff';
} elseif (strpos($sideUserPhoto, 'http') === false) {
    if (strpos($sideUserPhoto, 'assets') === false && strpos($sideUserPhoto, 'uploads') === false) {
        $sideUserPhoto = '../uploads/' . $sideUserPhoto;
    } else {
        $sideUserPhoto = '../' . $sideUserPhoto;
    }
}

// Queries para os Badges
$countUpcomingSchedules = 0;
$countUnreadAvisos = 0;
$countPendingSuggestions = 0;

try {
    $stmtCount = $pdo->prepare("SELECT COUNT(*) FROM schedule_users su JOIN schedules s ON s.id = su.schedule_id WHERE su.user_id = ? AND s.event_date >= CURDATE() AND su.status = 'pending'");
    $stmtCount->execute([$userId]);
    $countUpcomingSchedules = (int)$stmtCount->fetchColumn();
} catch (Exception $e) {}

try {
    $stmtCountAvisos = $pdo->query("SELECT COUNT(*) FROM avisos WHERE created_at > DATE_SUB(NOW(), INTERVAL 3 DAY)");
    $countUnreadAvisos = (int)$stmtCountAvisos->fetchColumn();
} catch (Exception $e) {}

try {
    if ($sideUserRole === 'admin') {
        $countPendingSuggestions = (int)$pdo->query("SELECT COUNT(*) FROM song_suggestions WHERE status = 'pending'")->fetchColumn();
    }
} catch (Exception $e) {}
?>

<div id="sidebar-overlay" class="fixed inset-0 bg-black/40 z-40 lg:hidden hidden opacity-0 transition-opacity duration-300" onclick="toggleSidebarMobile()"></div>

<aside id="app-sidebar" class="sidebar fixed inset-y-0 left-0 z-50 w-64 bg-surface border-r border-surface-container-highest flex flex-col transform -translate-x-full lg:translate-x-0 transition-transform duration-300 ease-in-out shadow-lg lg:shadow-none">
    
    <!-- 1. Cabeçalho Sidebar com Logo -->
    <div class="h-16 flex items-center justify-between px-6 border-b border-surface-container-highest flex-shrink-0">
        <div class="flex items-center gap-3">
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
                <img src="<?= $logoBlack ?>" alt="PIB Oliveira" class="h-9 w-auto block dark:hidden">
                <img src="<?= $logoWhite ?>" alt="PIB Oliveira" class="h-9 w-auto hidden dark:block">
            <?php else: ?>
                <div class="bg-primary/10 p-2 rounded-xl text-primary flex items-center justify-center">
                    <i data-lucide="music-4" class="w-5 h-5"></i>
                </div>
            <?php endif; ?>

            <div class="flex flex-col">
                <span class="text-sm font-extrabold text-surface-on-surface font-outfit leading-tight">PIB Oliveira</span>
                <span class="text-[10px] text-muted font-bold tracking-wider uppercase leading-none mt-0.5">App Louvor</span>
            </div>
        </div>
        <button class="lg:hidden text-muted hover:text-surface-on-surface p-1.5 rounded-lg hover:bg-surface-container-lowest transition-colors" onclick="toggleSidebarMobile()" aria-label="Fechar Menu">
            <i data-lucide="x" class="w-5 h-5"></i>
        </button>
    </div>

    <!-- 2. Menu de Navegação -->
    <div class="flex-1 overflow-y-auto px-4 py-6 space-y-7">
        <nav class="space-y-6">
            
            <!-- SEÇÃO: PRINCIPAL -->
            <div class="space-y-2">
                <span class="px-3 text-[10px] font-bold text-muted uppercase tracking-widest block">Principal</span>
                
                <a href="<?= $baseAdmin ?>index.php" class="flex items-center justify-between px-3 py-2.5 rounded-xl text-sm font-bold transition-all duration-200 group <?= basename($_SERVER['PHP_SELF']) == 'index.php' ? 'bg-primary/10 text-primary' : 'text-surface-on-surface hover:bg-surface-container-lowest' ?>">
                    <div class="flex items-center gap-3">
                        <div class="p-1 rounded-lg group-hover:scale-110 transition-transform">
                            <i data-lucide="layout-dashboard" class="w-5 h-5"></i>
                        </div>
                        <span class="font-outfit">Visão Geral</span>
                    </div>
                </a>
            </div>

            <!-- SEÇÃO: GESTÃO DE ENSAIOS -->
            <div class="space-y-2">
                <span class="px-3 text-[10px] font-bold text-muted uppercase tracking-widest block">Gestão de Ensaios</span>
                
                <a href="<?= $baseAdmin ?>escalas.php" class="flex items-center justify-between px-3 py-2.5 rounded-xl text-sm font-bold transition-all duration-200 group <?= basename($_SERVER['PHP_SELF']) == 'escalas.php' ? 'bg-primary/10 text-primary' : 'text-surface-on-surface hover:bg-surface-container-lowest' ?>">
                    <div class="flex items-center gap-3">
                        <div class="p-1 rounded-lg group-hover:scale-110 transition-transform">
                            <i data-lucide="calendar" class="w-5 h-5"></i>
                        </div>
                        <span class="font-outfit">Escalas</span>
                    </div>
                    <?php if ($countUpcomingSchedules > 0): ?>
                        <span class="bg-primary/10 text-primary text-[10px] font-extrabold px-2 py-0.5 rounded-full border border-primary/20"><?= $countUpcomingSchedules ?></span>
                    <?php endif; ?>
                </a>
                
                <a href="<?= $baseAdmin ?>repertorio.php" class="flex items-center justify-between px-3 py-2.5 rounded-xl text-sm font-bold transition-all duration-200 group <?= basename($_SERVER['PHP_SELF']) == 'repertorio.php' ? 'bg-primary/10 text-primary' : 'text-surface-on-surface hover:bg-surface-container-lowest' ?>">
                    <div class="flex items-center gap-3">
                        <div class="p-1 rounded-lg group-hover:scale-110 transition-transform">
                            <i data-lucide="music-2" class="w-5 h-5"></i>
                        </div>
                        <span class="font-outfit">Repertório</span>
                    </div>
                </a>
                
                <a href="<?= $baseAdmin ?>historico.php" class="flex items-center justify-between px-3 py-2.5 rounded-xl text-sm font-bold transition-all duration-200 group <?= basename($_SERVER['PHP_SELF']) == 'historico.php' ? 'bg-primary/10 text-primary' : 'text-surface-on-surface hover:bg-surface-container-lowest' ?>">
                    <div class="flex items-center gap-3">
                        <div class="p-1 rounded-lg group-hover:scale-110 transition-transform">
                            <i data-lucide="history" class="w-5 h-5"></i>
                        </div>
                        <span class="font-outfit">Histórico</span>
                    </div>
                </a>
                <?php if ($sideUserRole === 'admin'): ?>
                <a href="<?= $baseAdmin ?>membros.php" class="flex items-center justify-between px-3 py-2.5 rounded-xl text-sm font-bold transition-all duration-200 group <?= basename($_SERVER['PHP_SELF']) == 'membros.php' ? 'bg-primary/10 text-primary' : 'text-surface-on-surface hover:bg-surface-container-lowest' ?>">
                    <div class="flex items-center gap-3">
                        <div class="p-1 rounded-lg group-hover:scale-110 transition-transform">
                            <i data-lucide="users" class="w-5 h-5"></i>
                        </div>
                        <span class="font-outfit">Membros</span>
                    </div>
                </a>
                <?php endif; ?>
                
                <a href="<?= $baseAdmin ?>indisponibilidade.php" class="flex items-center justify-between px-3 py-2.5 rounded-xl text-sm font-bold transition-all duration-200 group <?= basename($_SERVER['PHP_SELF']) == 'indisponibilidade.php' ? 'bg-primary/10 text-primary' : 'text-surface-on-surface hover:bg-surface-container-lowest' ?>">
                    <div class="flex items-center gap-3">
                        <div class="p-1 rounded-lg group-hover:scale-110 transition-transform">
                            <i data-lucide="calendar-off" class="w-5 h-5"></i>
                        </div>
                        <span class="font-outfit">Ausências</span>
                    </div>
                </a>

                <a href="<?= $baseAdmin ?>agenda.php" class="flex items-center justify-between px-3 py-2.5 rounded-xl text-sm font-bold transition-all duration-200 group <?= basename($_SERVER['PHP_SELF']) == 'agenda.php' ? 'bg-primary/10 text-primary' : 'text-surface-on-surface hover:bg-surface-container-lowest' ?>">
                    <div class="flex items-center gap-3">
                        <div class="p-1 rounded-lg group-hover:scale-110 transition-transform">
                            <i data-lucide="calendar-range" class="w-5 h-5"></i>
                        </div>
                        <span class="font-outfit">Agenda</span>
                    </div>
                </a>
            </div>

            <!-- SEÇÃO: ESPIRITUAL -->
            <div class="space-y-2">
                <span class="px-3 text-[10px] font-bold text-muted uppercase tracking-widest block">Espiritual</span>
                
                <a href="<?= $baseAdmin ?>devocionais.php" class="flex items-center justify-between px-3 py-2.5 rounded-xl text-sm font-bold transition-all duration-200 group <?= basename($_SERVER['PHP_SELF']) == 'devocionais.php' ? 'bg-primary/10 text-primary' : 'text-surface-on-surface hover:bg-surface-container-lowest' ?>">
                    <div class="flex items-center gap-3">
                        <div class="p-1 rounded-lg group-hover:scale-110 transition-transform">
                            <i data-lucide="book-heart" class="w-5 h-5"></i>
                        </div>
                        <span class="font-outfit">Devocional</span>
                    </div>
                </a>
                
                <a href="<?= $baseAdmin ?>oracao.php" class="flex items-center justify-between px-3 py-2.5 rounded-xl text-sm font-bold transition-all duration-200 group <?= basename($_SERVER['PHP_SELF']) == 'oracao.php' ? 'bg-primary/10 text-primary' : 'text-surface-on-surface hover:bg-surface-container-lowest' ?>">
                    <div class="flex items-center gap-3">
                        <div class="p-1 rounded-lg group-hover:scale-110 transition-transform">
                            <i data-lucide="heart" class="w-5 h-5"></i>
                        </div>
                        <span class="font-outfit">Oração</span>
                    </div>
                </a>
                
                <a href="<?= $baseAdmin ?>leitura.php" class="flex items-center justify-between px-3 py-2.5 rounded-xl text-sm font-bold transition-all duration-200 group <?= basename($_SERVER['PHP_SELF']) == 'leitura.php' ? 'bg-primary/10 text-primary' : 'text-surface-on-surface hover:bg-surface-container-lowest' ?>">
                    <div class="flex items-center gap-3">
                        <div class="p-1 rounded-lg group-hover:scale-110 transition-transform">
                            <i data-lucide="book-open" class="w-5 h-5"></i>
                        </div>
                        <span class="font-outfit">Leitura Bíblica</span>
                    </div>
                </a>
            </div>

            <!-- SEÇÃO: COMUNICAÇÃO -->
            <div class="space-y-2">
                <span class="px-3 text-[10px] font-bold text-muted uppercase tracking-widest block">Comunicação</span>
                
                <a href="<?= $baseAdmin ?>avisos.php" class="flex items-center justify-between px-3 py-2.5 rounded-xl text-sm font-bold transition-all duration-200 group <?= basename($_SERVER['PHP_SELF']) == 'avisos.php' ? 'bg-primary/10 text-primary' : 'text-surface-on-surface hover:bg-surface-container-lowest' ?>">
                    <div class="flex items-center gap-3">
                        <div class="p-1 rounded-lg group-hover:scale-110 transition-transform">
                            <i data-lucide="megaphone" class="w-5 h-5"></i>
                        </div>
                        <span class="font-outfit">Avisos</span>
                    </div>
                    <?php if ($countUnreadAvisos > 0): ?>
                        <span class="bg-amber-500/10 text-amber-500 text-[10px] font-extrabold px-2 py-0.5 rounded-full border border-amber-500/20"><?= $countUnreadAvisos ?></span>
                    <?php endif; ?>
                </a>
                
                <a href="<?= $baseAdmin ?>aniversarios.php" class="flex items-center justify-between px-3 py-2.5 rounded-xl text-sm font-bold transition-all duration-200 group <?= basename($_SERVER['PHP_SELF']) == 'aniversarios.php' ? 'bg-primary/10 text-primary' : 'text-surface-on-surface hover:bg-surface-container-lowest' ?>">
                    <div class="flex items-center gap-3">
                        <div class="p-1 rounded-lg group-hover:scale-110 transition-transform">
                            <i data-lucide="cake" class="w-5 h-5"></i>
                        </div>
                        <span class="font-outfit">Aniversariantes</span>
                    </div>
                </a>
            </div>

            <!-- SEÇÃO: ADMINISTRAÇÃO (Apenas Líderes) -->
            <?php if ($sideUserRole === 'admin'): ?>
            <div class="space-y-2">
                <span class="px-3 text-[10px] font-bold text-muted uppercase tracking-widest block">Administração</span>
                
                <a href="<?= $baseAdmin ?>escalas_gestao.php" class="flex items-center justify-between px-3 py-2.5 rounded-xl text-sm font-bold transition-all duration-200 group <?= basename($_SERVER['PHP_SELF']) == 'escalas_gestao.php' ? 'bg-primary/10 text-primary' : 'text-surface-on-surface hover:bg-surface-container-lowest' ?>">
                    <div class="flex items-center gap-3">
                        <div class="p-1 rounded-lg group-hover:scale-110 transition-transform">
                            <i data-lucide="sliders" class="w-5 h-5"></i>
                        </div>
                        <span class="font-outfit">Gestão de Escalas</span>
                    </div>
                    <?php if ($countPendingSuggestions > 0): ?>
                        <span class="bg-red-500/10 text-red-500 text-[10px] font-extrabold px-2 py-0.5 rounded-full border border-red-500/20"><?= $countPendingSuggestions ?></span>
                    <?php endif; ?>
                </a>
                
                <a href="<?= $baseAdmin ?>relatorios_gerais.php" class="flex items-center justify-between px-3 py-2.5 rounded-xl text-sm font-bold transition-all duration-200 group <?= basename($_SERVER['PHP_SELF']) == 'relatorios_gerais.php' ? 'bg-primary/10 text-primary' : 'text-surface-on-surface hover:bg-surface-container-lowest' ?>">
                    <div class="flex items-center gap-3">
                        <div class="p-1 rounded-lg group-hover:scale-110 transition-transform">
                            <i data-lucide="trending-up" class="w-5 h-5"></i>
                        </div>
                        <span class="font-outfit">Relatórios</span>
                    </div>
                </a>
                
                <a href="<?= $baseAdmin ?>manutencao.php" class="flex items-center justify-between px-3 py-2.5 rounded-xl text-sm font-bold transition-all duration-200 group <?= basename($_SERVER['PHP_SELF']) == 'manutencao.php' ? 'bg-primary/10 text-primary' : 'text-surface-on-surface hover:bg-surface-container-lowest' ?>">
                    <div class="flex items-center gap-3">
                        <div class="p-1 rounded-lg group-hover:scale-110 transition-transform">
                            <i data-lucide="database" class="w-5 h-5"></i>
                        </div>
                        <span class="font-outfit">Manutenção</span>
                    </div>
                </a>
            </div>
            <?php endif; ?>
        </nav>
    </div>

    <!-- 3. Rodapé com Perfil do Usuário e Alternador de Tema -->
    <div class="p-4 border-t border-surface-container-highest bg-surface-container-lowest flex-shrink-0">
        <div class="flex items-center justify-between gap-3 bg-surface p-3 rounded-2xl border border-surface-container-highest">
            <div class="flex items-center gap-2.5 overflow-hidden">
                <img class="w-10 h-10 rounded-full object-cover border border-surface-container-highest flex-shrink-0" src="<?= $sideUserPhoto ?>" alt="<?= htmlspecialchars($sideUserName) ?>">
                <div class="flex flex-col overflow-hidden">
                    <span class="text-xs font-bold text-surface-on-surface truncate font-outfit leading-tight"><?= htmlspecialchars(explode(' ', $sideUserName)[0]) ?></span>
                    <span class="text-[9px] font-extrabold uppercase tracking-wider px-1.5 py-0.5 rounded bg-primary/10 text-primary border border-primary/10 text-center mt-1 w-max leading-none">
                        <?= $sideUserRole === 'admin' ? 'Líder' : 'Músico' ?>
                    </span>
                </div>
            </div>
            <div class="flex items-center gap-1.5 flex-shrink-0">
                <!-- Toggle Tema -->
                <button onclick="toggleThemeMode()" class="p-1.5 rounded-xl hover:bg-surface-container-lowest text-muted hover:text-surface-on-surface transition-colors cursor-pointer" title="Alternar Tema">
                    <i data-lucide="sun" class="w-4 h-4 block dark:hidden"></i>
                    <i data-lucide="moon" class="w-4 h-4 hidden dark:block"></i>
                </button>
                
                <!-- Logout -->
                <?php
                $logoutPath = $isAdminDir ? '../logout.php' : ($inApp ? '../../logout.php' : 'logout.php');
                ?>
                <a href="<?= $logoutPath ?>" class="p-1.5 rounded-xl hover:bg-red-500/10 text-muted hover:text-red-500 transition-colors" title="Sair da Conta">
                    <i data-lucide="log-out" class="w-4 h-4"></i>
                </a>
            </div>
        </div>
        
        <div class="text-[9px] text-muted text-center mt-3 font-semibold">
            Desenvolvido por <strong>Diego T. N. Vilela</strong>
        </div>
    </div>
</aside>

<script>
    const sidebar = document.getElementById('app-sidebar');
    const overlay = document.getElementById('sidebar-overlay');
    const content = document.getElementById('app-content');

    function isDesktop() {
        return window.innerWidth >= 1024;
    }

    function toggleSidebarMobile() {
        if (sidebar.classList.contains('-translate-x-full')) {
            sidebar.classList.remove('-translate-x-full');
            sidebar.classList.add('translate-x-0');
            overlay.classList.remove('hidden');
            setTimeout(() => overlay.classList.add('opacity-100'), 10);
        } else {
            sidebar.classList.add('-translate-x-full');
            sidebar.classList.remove('translate-x-0');
            overlay.classList.remove('opacity-100');
            setTimeout(() => overlay.classList.add('hidden'), 300);
        }
    }

    function toggleSidebarDesktop() {
        console.log("Desktop sidebar is fixed.");
    }

    // Inicialização geométrica no carregamento
    document.addEventListener('DOMContentLoaded', () => {
        if (isDesktop()) {
            if (content) content.style.marginLeft = '16rem'; // w-64
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
            const threshold = 60; // Sensibilidade
            if (touchStartX < widthTrigger && touchEndX > touchStartX + threshold) {
                if (sidebar.classList.contains('-translate-x-full')) toggleSidebarMobile();
            }
            if (touchEndX < touchStartX - threshold) {
                if (!sidebar.classList.contains('-translate-x-full')) toggleSidebarMobile();
            }
        }
    });

    window.toggleSidebar = toggleSidebarMobile;
    window.toggleSidebarMobile = toggleSidebarMobile;
    window.toggleSidebarDesktop = toggleSidebarDesktop;
</script>
