<?php
// admin/escalas.php
require_once '../src/helpers/auth.php';
checkLogin();
require_once '../src/config/db.php';
require_once '../src/layout/layout.php';

// --- Lógica de Filtros ---
$filterMine = isset($_GET['mine']) && $_GET['mine'] == '1';
$filterType = $_GET['type'] ?? '';

// ID do usuário logado (Assumindo sessão ou hardcoded 1 para dev se não tiver sessão ainda)
$loggedUserId = $_SESSION['user_id'] ?? 1;

// Instancia o repositório
require_once '../src/classes/ScheduleRepository.php';
$scheduleRepo = new \App\Repositories\ScheduleRepository($pdo);

try {
    // Buscar Escalas (usando o repository)
    $futureResults = $scheduleRepo->getFutureSchedules($filterType);
    $pastResults   = $scheduleRepo->getPastSchedules($filterType, 15);

    // Unir IDs para buscar dados complementares
    $allSchedulesTemp = array_merge($futureResults, $pastResults);
    $scheduleIds = array_column($allSchedulesTemp, 'id');
    
    // Buscar dependências em batch (Repository)
    $participantsMap = $scheduleRepo->getParticipantsByScheduleIds($scheduleIds);
    $songCountsMap   = $scheduleRepo->getSongCountsByScheduleIds($scheduleIds);
    
    // Processar "My Schedules" (mantendo a lógica PHP existente para evitar refazer queries pesadas)
    $mySchedulesMap = [];
    foreach ($participantsMap as $schId => $participants) {
        foreach ($participants as $p) {
            if ($p['user_id'] == $loggedUserId) {
                $mySchedulesMap[$schId] = true;
                break;
            }
        }
    }

    // Apply "My Schedules" Filter in PHP if needed
    $futureSchedules = [];
    foreach($futureResults as $s) {
        if ($filterMine && !isset($mySchedulesMap[$s['id']])) continue;
        $futureSchedules[] = $s;
    }

    $pastSchedules = [];
    foreach($pastResults as $s) {
        if ($filterMine && !isset($mySchedulesMap[$s['id']])) continue;
        $pastSchedules[] = $s;
    }

} catch (Exception $e) {
    die("Erro ao carregar escalas: " . $e->getMessage());
}

// Helpers
function getThemeColor($type) {
    $type = mb_strtolower($type);
    if (strpos($type, 'ensaio') !== false) return '#d97706'; // Âmbar sutil
    if (strpos($type, 'jovem') !== false) return '#0d9488'; // Ciano/Teal brilhante
    if (strpos($type, 'especial') !== false) return '#e11d48'; // Coral sofisticado
    return '#2563eb'; // Azul safira limpo (Culto principal)
}

function getMonthName($m) {
    $months = [
        1 => 'Janeiro', 2 => 'Fevereiro', 3 => 'Março', 4 => 'Abril',
        5 => 'Maio', 6 => 'Junho', 7 => 'Julho', 8 => 'Agosto',
        9 => 'Setembro', 10 => 'Outubro', 11 => 'Novembro', 12 => 'Dezembro'
    ];
    return $months[(int)$m];
}

// Contar filtros ativos para Badge
$activeFilters = 0;
if ($filterMine) $activeFilters++;
if (!empty($filterType)) $activeFilters++;

renderAppHeader('Escalas', 'index.php');
?>
<style>
    .bento-card {
        background: var(--surface-bright, #ffffff);
        border: 1px solid var(--outline-variant, #E0E2E7);
        box-shadow: 0 1px 3px rgba(0,0,0,0.02);
        transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
    }
    .bento-card:hover {
        border-color: var(--worship-blue, #2E7EED);
        box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.05), 0 8px 10px -6px rgba(0, 0, 0, 0.05);
    }
</style>

<!-- TOP CONTROLS & BENTO HEADER -->
<main class="max-w-[1200px] mx-auto px-margin-mobile md:px-margin-desktop py-4 mb-24">
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-8">
        <!-- Tab Selector Premium -->
        <div class="flex bg-ghost-gray dark:bg-surface-variant/20 p-1.5 rounded-full border border-outline-variant/30 w-fit">
            <button onclick="switchTab('future')" id="btn-future" class="px-6 py-2 rounded-full font-semibold text-xs uppercase tracking-wider transition-all duration-200 bg-worship-blue text-white border border-worship-blue shadow-sm">
                Próximas
            </button>
            <button onclick="switchTab('past')" id="btn-past" class="px-6 py-2 rounded-full font-semibold text-xs uppercase tracking-wider transition-all duration-200 bg-transparent text-secondary dark:text-on-surface-variant hover:text-worship-blue">
                Anteriores
            </button>
        </div>

        <!-- Right Controls -->
        <div class="flex items-center gap-3">
            <?php if (isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin'): ?>
                <a href="escala_adicionar.php" class="bg-worship-blue text-white px-5 py-2.5 rounded-full font-semibold text-xs uppercase tracking-wider hover:brightness-110 transition-all flex items-center justify-center gap-1.5 shadow-sm">
                    <i data-lucide="plus" class="w-4 h-4"></i> Nova Escala
                </a>
            <?php endif; ?>

            <button onclick="openSheet('filterSheet')" class="relative p-2.5 rounded-full bg-white dark:bg-deep-navy border border-outline-variant/60 text-secondary dark:text-on-surface-variant hover:bg-ghost-gray dark:hover:bg-surface-variant/40 hover:border-worship-blue transition-all flex items-center justify-center shadow-sm">
                <i data-lucide="filter" class="w-4 h-4"></i>
                <?php if($activeFilters > 0): ?>
                    <span class="absolute top-1 right-1 w-2.5 h-2.5 bg-altar-gold rounded-full ring-2 ring-white dark:ring-deep-navy"></span>
                <?php endif; ?>
            </button>
        </div>
    </div>

    <!-- Container Principal do Conteúdo -->
    <div class="grid grid-cols-1 gap-6">

        <!-- TAB: FUTURAS -->
        <div id="tab-future" class="space-y-4 transition-all duration-300">
            <?php if (empty($futureSchedules)): ?>
                <div class="bg-white dark:bg-deep-navy border border-dashed border-outline-variant/60 rounded-2xl p-16 text-center flex flex-col items-center max-w-lg mx-auto">
                    <div class="w-16 h-16 rounded-full bg-ghost-gray dark:bg-surface-variant/30 flex items-center justify-center mb-4 border border-outline-variant/40">
                        <i data-lucide="calendar" class="w-8 h-8 text-secondary"></i>
                    </div>
                    <h3 class="font-headline-md text-xl font-bold text-on-background mb-2">Tudo tranquilo por aqui</h3>
                    <p class="font-body-md text-secondary text-sm">Nenhuma escala agendada para os próximos dias.</p>
                </div>
            <?php else: ?>
                <div class="space-y-4">
                    <?php 
                    $currentMonth = '';
                    $delay = 0.1;
                    $weekdays = [
                        0 => 'Dom', 1 => 'Seg', 2 => 'Ter', 3 => 'Qua',
                        4 => 'Qui', 5 => 'Sex', 6 => 'Sáb'
                    ];

                    foreach ($futureSchedules as $schedule):
                        $date = new DateTime($schedule['event_date']);
                        $monthYear = getMonthName($date->format('n')) . ' ' . $date->format('Y');
                        
                        // Month Divider Premium
                        if ($monthYear !== $currentMonth):
                            $currentMonth = $monthYear;
                    ?>
                            <div class="pt-6 pb-2 flex items-center gap-4">
                                <span class="text-xs font-bold text-secondary dark:text-on-surface-variant tracking-widest uppercase"><?= $monthYear ?></span>
                                <div class="flex-grow h-px bg-outline-variant/40 dark:bg-outline-variant/10"></div>
                            </div>
                    <?php 
                        endif;

                        $isToday = $date->format('Y-m-d') === date('Y-m-d');
                        $songsCount = $songCountsMap[$schedule['id']] ?? 0;
                        $isMine = $mySchedulesMap[$schedule['id']] ?? false;
                        $dayOfWeek = $weekdays[(int)$date->format('w')];
                        
                        // Buscar meu status específico nesta escala
                        $myStatus = 'pending';
                        if (isset($participantsMap[$schedule['id']])) {
                            foreach($participantsMap[$schedule['id']] as $p) {
                                if ($p['user_id'] == $loggedUserId) {
                                    $myStatus = $p['status'];
                                    break;
                                }
                            }
                        }

                        // Contador de confirmações (ESC-04)
                        $confirmedCount = 0;
                        $totalParticipants = 0;
                        if (isset($participantsMap[$schedule['id']])) {
                            $totalParticipants = count($participantsMap[$schedule['id']]);
                            foreach ($participantsMap[$schedule['id']] as $p) {
                                if ($p['status'] === 'confirmed') $confirmedCount++;
                            }
                        }
                    ?>
                        <a href="escala_detalhe.php?id=<?= $schedule['id'] ?>" class="block bento-card rounded-2xl overflow-hidden p-5 border-l-4 hover:scale-[1.005] duration-250 <?= $isToday ? 'ring-1 ring-worship-blue/30 bg-worship-blue/[0.01]' : '' ?>" style="border-left-color: <?= getThemeColor($schedule['event_type']) ?>;">
                            <div class="flex flex-col sm:flex-row justify-between items-start gap-4">
                                <div class="flex flex-col">
                                    <div class="flex items-center gap-2 mb-1.5">
                                        <span class="font-bold font-label-sm tracking-wider uppercase text-xs" style="color: <?= getThemeColor($schedule['event_type']) ?>;">
                                            <?= $dayOfWeek ?> • <?= $date->format('H:i') ?>
                                        </span>
                                        <span class="w-1 h-1 bg-outline-variant rounded-full"></span>
                                        <span class="font-semibold text-on-background text-sm"><?= $date->format('d') ?> <?= substr(getMonthName($date->format('n')), 0, 3) ?></span>
                                        <?php if ($isToday): ?>
                                            <span class="ml-1 px-2.5 py-0.5 text-[9px] font-bold text-white bg-error rounded-full animate-pulse uppercase tracking-wider">Hoje</span>
                                        <?php endif; ?>
                                    </div>
                                    <h3 class="font-headline-md text-xl text-on-background font-bold tracking-tight"><?= htmlspecialchars($schedule['event_type']) ?></h3>
                                    
                                    <?php if (!empty($schedule['notes'])): ?>
                                        <p class="text-secondary text-sm mt-1 line-clamp-1 italic font-light">"<?= htmlspecialchars($schedule['notes']) ?>"</p>
                                    <?php endif; ?>
                                </div>
                                
                                <div class="flex items-center gap-2.5 self-end sm:self-start">
                                    <?php if ($isMine): ?>
                                        <?php
                                        $statusColors = [
                                            'confirmed' => 'bg-emerald-50 text-emerald-700 border-emerald-100 dark:bg-emerald-950/20 dark:text-emerald-400 dark:border-emerald-900/30',
                                            'declined' => 'bg-rose-50 text-rose-700 border-rose-100 dark:bg-rose-950/20 dark:text-rose-400 dark:border-rose-900/30',
                                            'pending' => 'bg-amber-50 text-amber-700 border-amber-100 dark:bg-amber-950/20 dark:text-amber-400 dark:border-amber-900/30'
                                        ];
                                        $statusTexts = [
                                            'confirmed' => 'Confirmado',
                                            'declined' => 'Recusado',
                                            'pending' => 'Pendente'
                                        ];
                                        $statusClass = $statusColors[$myStatus] ?? $statusColors['pending'];
                                        $statusText = $statusTexts[$myStatus] ?? $statusTexts['pending'];
                                        ?>
                                        <span class="px-2.5 py-1 text-xs font-bold rounded-full border <?= $statusClass ?>">
                                            <?= $statusText ?>
                                        </span>
                                    <?php endif; ?>
                                    
                                    <span class="text-worship-blue font-semibold text-xs hover:bg-blue-50 dark:hover:bg-blue-950/30 px-3 py-1.5 rounded-full transition-colors border border-blue-100 dark:border-blue-900/40 uppercase tracking-wider">DETALHES</span>
                                </div>
                            </div>

                            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 pt-4 mt-4 border-t border-ghost-gray dark:border-outline-variant/10">
                                <!-- Avatares empilhados dos participantes -->
                                <div class="flex items-center gap-3">
                                    <div class="avatar-stack flex -space-x-2 overflow-hidden">
                                        <?php 
                                        $parts = $participantsMap[$schedule['id']] ?? [];
                                        $count = 0;
                                        foreach ($parts as $p): 
                                            if ($count++ >= 5) break;
                                            $pAvatar = !empty($p['photo']) ? $p['photo'] : 'https://ui-avatars.com/api/?name='.urlencode($p['name']).'&background=random';
                                            if (strpos($pAvatar, 'http') === false) $pAvatar = '../' . $pAvatar;
                                        ?>
                                            <img class="inline-block h-8 w-8 rounded-full ring-2 ring-white dark:ring-deep-navy object-cover" src="<?= $pAvatar ?>" alt="<?= htmlspecialchars($p['name']) ?>" title="<?= htmlspecialchars($p['name']) ?>">
                                        <?php endforeach; ?>
                                        <?php if (count($parts) > 5): ?>
                                            <div class="inline-flex items-center justify-center h-8 w-8 rounded-full ring-2 ring-white dark:ring-deep-navy bg-surface-container-highest text-secondary text-xs font-bold">+<?= count($parts) - 5 ?></div>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <?php if ($totalParticipants > 0): ?>
                                        <?php
                                        $allConfirmed = $confirmedCount === $totalParticipants;
                                        $someConfirmed = $confirmedCount > 0 && !$allConfirmed;
                                        $confClass = $allConfirmed ? 'text-emerald-600 dark:text-emerald-400 font-semibold' : ($someConfirmed ? 'text-worship-blue dark:text-primary-fixed' : 'text-secondary');
                                        ?>
                                        <span class="text-xs <?= $confClass ?>">
                                            <?= $confirmedCount ?>/<?= $totalParticipants ?> Confirmados
                                        </span>
                                    <?php endif; ?>
                                </div>

                                <!-- Indicadores e Badges de música -->
                                <div class="flex items-center gap-1.5 text-secondary dark:text-on-surface-variant text-xs bg-ghost-gray dark:bg-surface-variant/40 px-3 py-1.5 rounded-full border border-outline-variant/30 self-start sm:self-auto">
                                    <i data-lucide="music" class="w-3.5 h-3.5"></i>
                                    <span class="font-semibold"><?= $songsCount ?> <?= $songsCount === 1 ? 'Música' : 'Músicas' ?></span>
                                </div>
                            </div>
                        </a>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- TAB: ANTERIORES -->
        <div id="tab-past" class="space-y-4 hidden transition-all duration-300">
            <?php if (empty($pastSchedules)): ?>
                <div class="bg-white dark:bg-deep-navy border border-dashed border-outline-variant/60 rounded-2xl p-16 text-center flex flex-col items-center max-w-lg mx-auto">
                    <p class="text-secondary">Nenhum histórico recente de escalas.</p>
                </div>
            <?php else: ?>
                <div class="space-y-4">
                     <?php 
                    $currentMonth = '';
                    $weekdays = [
                        0 => 'Dom', 1 => 'Seg', 2 => 'Ter', 3 => 'Qua',
                        4 => 'Qui', 5 => 'Sex', 6 => 'Sáb'
                    ];

                    foreach ($pastSchedules as $schedule):
                        $date = new DateTime($schedule['event_date']);
                        $monthYear = getMonthName($date->format('n')) . ' ' . $date->format('Y');
                        $dayOfWeek = $weekdays[(int)$date->format('w')];
                        
                        // Month Divider
                        if ($monthYear !== $currentMonth):
                            $currentMonth = $monthYear;
                    ?>
                            <div class="pt-6 pb-2 flex items-center gap-4">
                                <span class="text-xs font-bold text-secondary dark:text-on-surface-variant tracking-widest uppercase"><?= $monthYear ?></span>
                                <div class="flex-grow h-px bg-outline-variant/40 dark:bg-outline-variant/10"></div>
                            </div>
                    <?php 
                        endif;

                        $songsCount = $songCountsMap[$schedule['id']] ?? 0;

                        // Contador de confirmações (ESC-04)
                        $confirmedCount = 0;
                        $totalParticipants = 0;
                        if (isset($participantsMap[$schedule['id']])) {
                            $totalParticipants = count($participantsMap[$schedule['id']]);
                            foreach ($participantsMap[$schedule['id']] as $p) {
                                if ($p['status'] === 'confirmed') $confirmedCount++;
                            }
                        }
                    ?>
                        <div class="flex flex-col">
                            <a href="escala_detalhe.php?id=<?= $schedule['id'] ?>" class="block bento-card rounded-2xl overflow-hidden p-5 border-l-4 opacity-80 hover:opacity-100 duration-200" style="border-left-color: <?= getThemeColor($schedule['event_type']) ?>;">
                                <div class="flex flex-col sm:flex-row justify-between items-start gap-4">
                                    <div class="flex flex-col">
                                        <div class="flex items-center gap-2 mb-1.5">
                                            <span class="font-bold tracking-wider uppercase text-xs text-secondary">
                                                <?= $dayOfWeek ?> • <?= $date->format('H:i') ?>
                                            </span>
                                            <span class="w-1 h-1 bg-outline-variant rounded-full"></span>
                                            <span class="font-semibold text-on-background text-sm"><?= $date->format('d') ?> <?= substr(getMonthName($date->format('n')), 0, 3) ?></span>
                                        </div>
                                        <h3 class="font-headline-md text-lg text-on-background font-bold tracking-tight"><?= htmlspecialchars($schedule['event_type']) ?></h3>
                                    </div>
                                    
                                    <div class="flex items-center gap-2 self-end sm:self-start">
                                        <span class="text-secondary font-semibold text-xs hover:bg-ghost-gray dark:hover:bg-surface-variant/40 px-3 py-1.5 rounded-full transition-colors border border-outline-variant/40 uppercase tracking-wider">VER DETALHES</span>
                                    </div>
                                </div>

                                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 pt-4 mt-4 border-t border-ghost-gray dark:border-outline-variant/10">
                                    <div class="text-xs text-secondary">
                                        <?php if ($totalParticipants > 0): ?>
                                            <span><?= $confirmedCount ?>/<?= $totalParticipants ?> Presenças Confirmadas</span>
                                        <?php endif; ?>
                                    </div>

                                    <div class="flex items-center gap-1.5 text-secondary dark:text-on-surface-variant text-xs bg-ghost-gray dark:bg-surface-variant/40 px-3 py-1.5 rounded-full border border-outline-variant/30 self-start sm:self-auto">
                                        <i data-lucide="music" class="w-3.5 h-3.5"></i>
                                        <span class="font-semibold"><?= $songsCount ?> <?= $songsCount === 1 ? 'Música' : 'Músicas' ?></span>
                                    </div>
                                </div>
                            </a>

                            <?php if ($_SESSION['user_role'] === 'admin'): ?>
                                <div class="mt-2 self-start">
                                    <a href="registrar_faltas.php?id=<?= $schedule['id'] ?>" class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-semibold text-amber-600 hover:text-amber-700 bg-amber-50 hover:bg-amber-100 dark:bg-amber-950/10 dark:text-amber-400 dark:hover:bg-amber-950/20 border border-amber-200 dark:border-amber-900/40 rounded-lg transition-colors shadow-xs">
                                        <i data-lucide="user-minus" class="w-3.5 h-3.5"></i>
                                        Registrar Faltas/Presenças
                                    </a>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</main>

<!-- FILTER GAVETA SHEET -->
<div id="filterSheet" class="fixed inset-0 z-50 hidden transition-opacity duration-300">
    <div class="absolute inset-0 bg-black/40 backdrop-blur-xs" onclick="closeSheet('filterSheet')"></div>
    <div class="absolute bottom-0 inset-x-0 max-w-lg mx-auto bg-white dark:bg-deep-navy rounded-t-2xl shadow-2xl transform translate-y-full transition-transform duration-300 filter-sheet-content">
        <div class="flex justify-between items-center px-6 py-4 border-b border-outline-variant/40 dark:border-outline-variant/10">
            <h3 class="font-headline-md text-lg font-bold text-on-background">Filtrar Escalas</h3>
            <button onclick="closeSheet('filterSheet')" class="p-1 rounded-full text-secondary hover:bg-ghost-gray dark:hover:bg-surface-variant transition-colors">
                <i data-lucide="x" class="w-5 h-5"></i>
            </button>
        </div>

        <form method="GET" action="escalas.php" class="p-6 space-y-6">
            <!-- Toggle Minhas Escalas -->
            <label class="flex justify-between items-center cursor-pointer py-2">
                <span class="font-semibold text-secondary dark:text-on-surface-variant text-sm">Apenas escalas em que participo</span>
                <div class="relative inline-flex items-center">
                    <input type="checkbox" name="mine" value="1" <?= $filterMine ? 'checked' : '' ?> class="sr-only peer">
                    <div class="w-11 h-6 bg-surface-container-highest dark:bg-surface-variant peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-worship-blue"></div>
                </div>
            </label>

            <!-- Tipo de Evento -->
            <div class="space-y-3">
                <label class="block font-label-sm text-[11px] font-bold text-secondary uppercase tracking-wider">Tipo de Evento</label>
                <div class="flex flex-wrap gap-2">
                    <?php
                    $types = ['Culto Domingo a Noite', 'Ensaio', 'Culto Jovem', 'Especial'];
                    foreach ($types as $t):
                        $active = $filterType === $t;
                    ?>
                        <label class="cursor-pointer">
                            <input type="radio" name="type" value="<?= $t ?>" <?= $active ? 'checked' : '' ?> class="sr-only" onchange="this.form.submit()">
                            <span class="inline-block px-4 py-2 rounded-full text-xs font-semibold border transition-all <?= $active ? 'bg-worship-blue text-white border-worship-blue shadow-sm' : 'bg-transparent text-secondary border-outline-variant/60 hover:border-worship-blue/40' ?>">
                                <?= $t ?>
                            </span>
                        </label>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Botões -->
            <div class="flex gap-3 pt-4 border-t border-outline-variant/30 dark:border-outline-variant/10">
                <a href="escalas.php" class="flex-1 py-3 text-center text-sm font-bold text-secondary hover:bg-ghost-gray dark:hover:bg-surface-variant rounded-full transition-colors border border-outline-variant/40">Limpar</a>
                <button type="submit" class="flex-1 py-3 text-center text-sm font-bold text-white bg-worship-blue hover:brightness-110 rounded-full transition-colors shadow-sm">Aplicar Filtros</button>
            </div>
        </form>
    </div>
</div>

<!-- Scripts -->
<script>
    function switchTab(tab) {
        const btnFuture = document.getElementById('btn-future');
        const btnPast = document.getElementById('btn-past');
        const tabFuture = document.getElementById('tab-future');
        const tabPast = document.getElementById('tab-past');

        if (tab === 'future') {
            tabFuture.classList.remove('hidden');
            tabPast.classList.add('hidden');

            btnFuture.className = "px-6 py-2 rounded-full font-semibold text-xs uppercase tracking-wider transition-all duration-200 bg-worship-blue text-white border border-worship-blue shadow-sm";
            btnPast.className = "px-6 py-2 rounded-full font-semibold text-xs uppercase tracking-wider transition-all duration-200 bg-transparent text-secondary dark:text-on-surface-variant hover:text-worship-blue";
        } else {
            tabFuture.classList.add('hidden');
            tabPast.classList.remove('hidden');

            btnFuture.className = "px-6 py-2 rounded-full font-semibold text-xs uppercase tracking-wider transition-all duration-200 bg-transparent text-secondary dark:text-on-surface-variant hover:text-worship-blue";
            btnPast.className = "px-6 py-2 rounded-full font-semibold text-xs uppercase tracking-wider transition-all duration-200 bg-worship-blue text-white border border-worship-blue shadow-sm";
        }
    }

    // Modal Logic (Standardized Drawer)
    function openSheet(id) {
        const modal = document.getElementById(id);
        if (modal) {
            modal.classList.remove('hidden');
            const content = modal.querySelector('.filter-sheet-content');
            setTimeout(() => {
                modal.classList.add('opacity-100');
                if(content) content.classList.remove('translate-y-full');
            }, 10);
            document.body.style.overflow = 'hidden'; 
        }
    }

    function closeSheet(id) {
        const modal = document.getElementById(id);
        if (modal) {
            const content = modal.querySelector('.filter-sheet-content');
            if(content) content.classList.add('translate-y-full');
            modal.classList.remove('opacity-100');
            setTimeout(() => {
                 modal.classList.add('hidden');
            }, 300); // Wait for transition
            document.body.style.overflow = '';
        }
    }
</script>

<?php renderAppFooter(); ?>