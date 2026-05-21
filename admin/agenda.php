<?php
// admin/agenda.php
require_once '../src/helpers/auth.php';
checkLogin();
require_once '../src/config/db.php';
require_once '../src/layout/layout.php';

// Parâmetros de visualização
// Detectar Mobile
$isMobile = preg_match('/(android|bb\d+|meego).+mobile|avantgo|bada\/|blackberry|blazer|compal|elaine|fennec|hiptop|iemobile|ip(hone|od)|iris|kindle|lge |maemo|midp|mmp|mobile.+firefox|netfront|opera m(ob|in)i|palm( os)?|phone|p(ixi|re)\/|plucker|pocket|psp|series(4|6)0|symbian|treo|up\.(browser|link)|vodafone|wap|windows ce|xda|xiino/i', $_SERVER['HTTP_USER_AGENT'] ?? '');

$defaultView = $isMobile ? 'list' : 'month';
$viewMode = $_GET['view'] ?? $defaultView;
// Normaliza view antiga 'calendar' para 'month'
if ($viewMode === 'calendar') $viewMode = 'month';

// Determinar datas de início e fim baseado na view
if ($viewMode === 'week') {
    // Se view = week, esperamos ?week=2026-W05
    // Se não vier, pega semana atual
    $currentWeekStr = $_GET['week'] ?? date('o-\WW'); // YYYY-Www (ISO-8601 week number of year, weeks starting on Monday)
    
    // Calcular start e end da semana
    try {
        $dto = new DateTime();
        $dto->setISODate((int)substr($currentWeekStr, 0, 4), (int)substr($currentWeekStr, 6)); // Year, Week
        $startDate = $dto->format('Y-m-d'); // Segunda-feira
        $dto->modify('+6 days');
        $endDate = $dto->format('Y-m-d'); // Domingo
    } catch (Exception $e) {
        // Fallback p/ hoje
        $startDate = date('Y-m-d', strtotime('monday this week'));
        $endDate = date('Y-m-d', strtotime('sunday this week'));
    }
    
    // Título para exibição
    $displayTitle = "Semana " . substr($currentWeekStr, 6) . " (" . date('d/m', strtotime($startDate)) . " - " . date('d/m', strtotime($endDate)) . ")";
    $currentDateParam = $currentWeekStr; // Para navegação
    
} else {
    // Padrão: Mês (serve para 'month' e 'list')
    $currentMonth = $_GET['month'] ?? date('Y-m');
    $startDate = $currentMonth . '-01';
    $endDate = date('Y-m-t', strtotime($startDate));
    
    $monthNames = ['', 'Janeiro', 'Fevereiro', 'Março', 'Abril', 'Maio', 'Junho', 
                  'Julho', 'Agosto', 'Setembro', 'Outubro', 'Novembro', 'Dezembro'];
    $mNum = (int)date('m', strtotime($startDate));
    $displayTitle = $monthNames[$mNum] . ' ' . date('Y', strtotime($startDate));
    $currentDateParam = $currentMonth;
}

// Buscar eventos no intervalo calculado
try {
    $stmtEvents = $pdo->prepare("
        SELECT e.*, 
               u.name as creator_name,
               COUNT(DISTINCT ep.id) as participant_count
        FROM events e
        LEFT JOIN users u ON e.created_by = u.id
        LEFT JOIN event_participants ep ON e.id = ep.event_id
        WHERE DATE(e.start_datetime) BETWEEN ? AND ?
        GROUP BY e.id
        ORDER BY e.start_datetime ASC
    ");
    $stmtEvents->execute([$startDate, $endDate]);
    $events = $stmtEvents->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $events = []; // Fallback em caso de banco incompleto
}

// Buscar escalas no intervalo
try {
    $stmtSchedules = $pdo->prepare("
        SELECT s.*, COUNT(DISTINCT su.id) as participant_count
        FROM schedules s
        LEFT JOIN schedule_users su ON s.id = su.schedule_id
        WHERE s.event_date BETWEEN ? AND ?
        GROUP BY s.id
        ORDER BY s.event_date ASC
    ");
    $stmtSchedules->execute([$startDate, $endDate]);
    $schedules = $stmtSchedules->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $schedules = [];
}

// Organizar eventos por dia
$eventsByDay = [];
foreach ($events as $event) {
    $dayKey = date('Y-m-d', strtotime($event['start_datetime']));
    if (!isset($eventsByDay[$dayKey])) $eventsByDay[$dayKey] = [];
    $image = '../assets/images/event_placeholder.png'; // Placeholder
    $eventsByDay[$dayKey][] = array_merge($event, ['source' => 'event']);
}

foreach ($schedules as $schedule) {
    $dayKey = $schedule['event_date'];
    if (!isset($eventsByDay[$dayKey])) $eventsByDay[$dayKey] = [];
    $color = 'var(--slate-600)'; // Smart Blue padrão para escalas
    $eventsByDay[$dayKey][] = array_merge($schedule, [
        'source' => 'schedule',
        'title' => $schedule['event_type'],
        'color' => $color,
        'event_type' => 'escala',
        'start_datetime' => $schedule['event_date'] . ' 19:30:00' // Horário fictício se não tiver
    ]);
}

renderAppHeader('Agenda');
renderPageHeader('Agenda', 'Calendário e eventos do ministério');
?>

<!-- Estilos movidos para assets/css/pages/agenda.css -->


<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6 pb-28 space-y-6">
    
    <!-- CONTROLES DA AGENDA (Bento Box Horizontal) -->
    <div class="bg-surface-container-low border border-surface-container-highest rounded-3xl p-4 flex flex-col md:flex-row md:items-center justify-between gap-4 shadow-sm">
        
        <!-- Navegação de Período -->
        <div class="flex items-center gap-4 bg-surface rounded-2xl p-2 border border-surface-container-highest shadow-sm self-start md:self-auto">
            <button class="p-2 rounded-xl text-muted hover:text-surface-on-surface hover:bg-surface-container-lowest transition-all duration-200 active:scale-95 cursor-pointer" onclick="navigate(-1)" title="Anterior">
                <i data-lucide="chevron-left" class="w-5 h-5"></i>
            </button>
            <div class="text-base font-extrabold text-surface-on-surface font-outfit px-2 min-w-[140px] text-center">
                <?= $displayTitle ?>
            </div>
            <button class="p-2 rounded-xl text-muted hover:text-surface-on-surface hover:bg-surface-container-lowest transition-all duration-200 active:scale-95 cursor-pointer" onclick="navigate(1)" title="Próximo">
                <i data-lucide="chevron-right" class="w-5 h-5"></i>
            </button>
        </div>
        
        <!-- Alternador de Visualização -->
        <div class="flex items-center bg-surface border border-surface-container-highest p-1 rounded-2xl shadow-sm self-start md:self-auto">
            <button class="flex items-center gap-2 px-4 py-2.5 rounded-xl text-xs font-bold transition-all duration-200 cursor-pointer <?= $viewMode === 'month' ? 'bg-primary text-white shadow-sm' : 'text-muted hover:text-surface-on-surface hover:bg-surface-container-lowest' ?>" onclick="switchView('month')">
                <i data-lucide="calendar" class="w-4 h-4"></i> Mês
            </button>
            <button class="flex items-center gap-2 px-4 py-2.5 rounded-xl text-xs font-bold transition-all duration-200 cursor-pointer <?= $viewMode === 'week' ? 'bg-primary text-white shadow-sm' : 'text-muted hover:text-surface-on-surface hover:bg-surface-container-lowest' ?>" onclick="switchView('week')">
                <i data-lucide="columns" class="w-4 h-4"></i> Semana
            </button>
            <button class="flex items-center gap-2 px-4 py-2.5 rounded-xl text-xs font-bold transition-all duration-200 cursor-pointer <?= $viewMode === 'list' ? 'bg-primary text-white shadow-sm' : 'text-muted hover:text-surface-on-surface hover:bg-surface-container-lowest' ?>" onclick="switchView('list')">
                <i data-lucide="list" class="w-4 h-4"></i> Lista
            </button>
        </div>
    </div>

    <!-- MODO LISTA -->
    <?php if ($viewMode === 'list'): ?>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            <?php 
            // mescla e ordena todos os eventos para a listagem
            $allItems = [];
            foreach ($eventsByDay as $day => $evts) {
                foreach ($evts as $e) {
                    $allItems[] = $e;
                }
            }
            usort($allItems, function($a, $b) {
                return strtotime($a['start_datetime']) - strtotime($b['start_datetime']);
            });
            
            if (empty($allItems)): ?>
                <div class="col-span-full bg-surface-container-low border border-surface-container-highest rounded-3xl p-12 text-center text-muted font-bold">
                    <i data-lucide="calendar-off" class="w-12 h-12 mx-auto mb-4 text-muted/60"></i>
                    Nenhum evento neste período.
                </div>
            <?php else: 
                foreach ($allItems as $item): 
                    $isSchedule = $item['source'] === 'schedule';
                    $colorClass = $isSchedule ? 'border-l-primary' : 'border-l-emerald-500';
                    $badgeBg = $isSchedule ? 'bg-primary/10 text-primary border border-primary/20' : 'bg-emerald-500/10 text-emerald-500 border border-emerald-500/20';
                    $url = $isSchedule ? 'escala_detalhe.php?id='.$item['id'] : 'evento_detalhe.php?id='.$item['id'];
            ?>
                <a href="<?= $url ?>" class="bg-surface-container-lowest border border-surface-container-highest border-l-4 <?= $colorClass ?> rounded-2xl p-4 flex items-center gap-4 transition-all duration-200 hover:shadow-md hover:-translate-y-0.5 group">
                    <div class="bg-surface-container-low border border-surface-container-highest rounded-xl p-2.5 text-center min-w-[55px] flex flex-col justify-center shadow-sm">
                        <div class="text-lg font-black text-surface-on-surface font-outfit leading-none"><?= date('d', strtotime($item['start_datetime'])) ?></div>
                        <div class="text-[10px] font-extrabold uppercase tracking-wider text-muted mt-1 leading-none"><?= date('M', strtotime($item['start_datetime'])) ?></div>
                    </div>
                    <div class="flex-1 min-w-0">
                        <div class="text-sm font-extrabold text-surface-on-surface font-outfit truncate group-hover:text-primary transition-colors"><?= htmlspecialchars($item['title']) ?></div>
                        <div class="flex items-center gap-3 text-xs text-muted font-semibold mt-1">
                            <span class="flex items-center gap-1"><i data-lucide="clock" class="w-3.5 h-3.5"></i> <?= date('H:i', strtotime($item['start_datetime'])) ?></span>
                            <?php if(!empty($item['participant_count'])): ?>
                                <span class="flex items-center gap-1"><i data-lucide="users" class="w-3.5 h-3.5"></i> <?= $item['participant_count'] ?></span>
                            <?php endif; ?>
                        </div>
                    </div>
                    <span class="text-[9px] font-extrabold uppercase tracking-wider px-2 py-0.5 rounded-full <?= $badgeBg ?> flex-shrink-0">
                        <?= $isSchedule ? 'Escala' : 'Evento' ?>
                    </span>
                </a>
            <?php endforeach; endif; ?>
        </div>
    
    <!-- MODO CALENDÁRIO / SEMANA GRID -->
    <?php else: ?>
        <div class="bg-surface-container-low border border-surface-container-highest rounded-3xl p-4 sm:p-5 shadow-sm overflow-hidden">
            
            <!-- Dias da Semana Header -->
            <div class="grid grid-cols-7 text-center border-b border-surface-container-highest pb-3 mb-3">
                <div class="text-[11px] font-extrabold uppercase tracking-widest text-red-500">Dom</div>
                <div class="text-[11px] font-extrabold uppercase tracking-widest text-muted">Seg</div>
                <div class="text-[11px] font-extrabold uppercase tracking-widest text-muted">Ter</div>
                <div class="text-[11px] font-extrabold uppercase tracking-widest text-muted">Qua</div>
                <div class="text-[11px] font-extrabold uppercase tracking-widest text-muted">Qui</div>
                <div class="text-[11px] font-extrabold uppercase tracking-widest text-muted">Sex</div>
                <div class="text-[11px] font-extrabold uppercase tracking-widest text-muted">Sáb</div>
            </div>
            
            <!-- Corpo do Calendário -->
            <div class="grid grid-cols-7 gap-2">
                <?php
                if ($viewMode === 'month') {
                    $firstDayMonth = date('w', strtotime($startDate));
                    $daysInMonth = date('t', strtotime($startDate));
                    
                    // Dias do mês anterior (padding)
                    for($i=0; $i < $firstDayMonth; $i++) {
                        echo '<div class="aspect-square bg-surface/30 rounded-2xl border border-surface-container-highest/40 opacity-40"></div>';
                    }
                    
                    // Dias do mês atual
                    for($d=1; $d <= $daysInMonth; $d++) {
                        $currentDate = sprintf('%s-%02d', date('Y-m', strtotime($startDate)), $d);
                        $isToday = ($currentDate === date('Y-m-d'));
                        $dayEvents = $eventsByDay[$currentDate] ?? [];
                        
                        $todayClass = $isToday ? 'border-primary bg-primary/5 ring-1 ring-primary' : 'border-surface-container-highest bg-surface-container-lowest';
                        
                        echo '<div class="min-h-[75px] sm:min-h-[105px] border rounded-2xl p-1.5 sm:p-2 flex flex-col justify-between transition-all duration-200 hover:bg-surface hover:shadow-sm '.$todayClass.'">';
                        
                        // Número do dia
                        $numColor = $isToday ? 'text-primary font-black bg-primary/10' : 'text-surface-on-surface font-extrabold';
                        echo '<div class="text-xs sm:text-sm '.$numColor.' w-6 h-6 rounded-full flex items-center justify-center font-outfit">'.$d.'</div>';
                        
                        // Lista de Chips de Eventos
                        echo '<div class="space-y-1 mt-1 sm:mt-2 overflow-y-auto max-h-[48px] sm:max-h-[70px] pr-0.5 custom-scrollbar">';
                        foreach($dayEvents as $evt) {
                            $isSchedule = $evt['source'] === 'schedule';
                            $chipBg = $isSchedule ? 'bg-primary/10 text-primary border-primary/15 hover:bg-primary/20' : 'bg-emerald-500/10 text-emerald-500 border-emerald-500/15 hover:bg-emerald-500/20';
                            $iconName = $isSchedule ? 'music' : 'calendar';
                            $url = $isSchedule ? 'escala_detalhe.php?id='.$evt['id'] : 'evento_detalhe.php?id='.$evt['id'];
                            
                            echo "<div onclick=\"window.location='$url'\" class='event-chip flex items-center gap-1 px-1.5 py-0.5 rounded-lg text-[9px] font-bold border truncate transition-colors cursor-pointer $chipBg' title=\"".htmlspecialchars($evt['title'])."\">";
                            echo "<i data-lucide='$iconName' class='w-2.5 h-2.5 flex-shrink-0'></i>";
                            echo "<span class='truncate leading-none'>".htmlspecialchars($evt['title'])."</span>";
                            echo "</div>";
                        }
                        echo '</div>'; // chips
                        
                        echo '</div>'; // dia
                    }
                    
                    // Dias do próximo mês (padding)
                    $totalCells = $firstDayMonth + $daysInMonth;
                    $remaining = 7 - ($totalCells % 7);
                    if($remaining < 7) {
                        for($i=0; $i < $remaining; $i++){
                            echo '<div class="aspect-square bg-surface/30 rounded-2xl border border-surface-container-highest/40 opacity-40"></div>';
                        }
                    }
                } 
                elseif ($viewMode === 'week') {
                    $loopDate = new DateTime($startDate);
                    if ($loopDate->format('w') == 1) { // Ajuste visual para iniciar no Domingo se a ISO deu Segunda
                         $loopDate->modify('-1 day');
                    }
                    
                    for($i=0; $i<7; $i++) {
                        $dStr = $loopDate->format('Y-m-d');
                        $dayNum = $loopDate->format('d');
                        $isToday = ($dStr === date('Y-m-d'));
                        $dayEvents = $eventsByDay[$dStr] ?? [];
                        
                        $todayClass = $isToday ? 'border-primary bg-primary/5 ring-1 ring-primary' : 'border-surface-container-highest bg-surface-container-lowest';
                        
                        echo '<div class="min-h-[140px] border rounded-2xl p-2 flex flex-col justify-between transition-all duration-200 hover:bg-surface hover:shadow-sm '.$todayClass.'">';
                        
                        $numColor = $isToday ? 'text-primary font-black bg-primary/10' : 'text-surface-on-surface font-extrabold';
                        echo '<div class="text-sm '.$numColor.' w-7 h-7 rounded-full flex items-center justify-center font-outfit">'.$dayNum.'</div>';
                        
                        echo '<div class="space-y-1.5 mt-2 overflow-y-auto max-h-[100px] custom-scrollbar">';
                        foreach($dayEvents as $evt) {
                            $isSchedule = $evt['source'] === 'schedule';
                            $chipBg = $isSchedule ? 'bg-primary/10 text-primary border-primary/15 hover:bg-primary/20' : 'bg-emerald-500/10 text-emerald-500 border-emerald-500/15 hover:bg-emerald-500/20';
                            $iconName = $isSchedule ? 'music' : 'calendar';
                            $url = $isSchedule ? 'escala_detalhe.php?id='.$evt['id'] : 'evento_detalhe.php?id='.$evt['id'];
                            $startTime = date('H:i', strtotime($evt['start_datetime']));
                            
                            echo "<div onclick=\"window.location='$url'\" class='event-chip flex flex-col gap-0.5 p-1.5 rounded-xl text-[9px] font-bold border transition-colors cursor-pointer $chipBg' title=\"".htmlspecialchars($evt['title'])."\">";
                            echo "<div class='text-[8px] font-extrabold opacity-75'>$startTime</div>";
                            echo "<div class='flex items-center gap-1 mt-0.5'>";
                            echo "<i data-lucide='$iconName' class='w-2.5 h-2.5 flex-shrink-0'></i>";
                            echo "<span class='truncate leading-none'>".htmlspecialchars($evt['title'])."</span>";
                            echo "</div>";
                            echo "</div>";
                        }
                        echo '</div>';
                        
                        echo '</div>';
                        
                        $loopDate->modify('+1 day');
                    }
                }
                ?>
            </div>
        </div>
    <?php endif; ?>
</div>

<!-- BOTÃO ADICIONAR (FAB Premium) -->
<?php if (isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin'): ?>
<a href="evento_adicionar.php" class="fixed bottom-8 right-8 z-40 bg-gradient-to-r from-primary to-blue-600 hover:from-blue-600 hover:to-blue-700 text-white rounded-full p-4.5 shadow-xl hover:shadow-2xl transition-all duration-300 hover:scale-105 active:scale-95 flex items-center justify-center border border-white/10 group cursor-pointer" title="Adicionar Evento">
    <i data-lucide="plus" class="w-6 h-6 group-hover:rotate-90 transition-transform duration-300"></i>
</a>
<?php endif; ?>

<script>
    const currentView = '<?= $viewMode ?>';
    const currentDateParam = '<?= $currentDateParam ?>';

    function switchView(view) {
        const url = new URL(window.location);
        url.searchParams.set('view', view);
        url.searchParams.delete('month');
        url.searchParams.delete('week');
        window.location.href = url.toString();
    }

    function navigate(direction) {
        const url = new URL(window.location);
        
        if (currentView === 'week') {
            const [year, week] = currentDateParam.split('-W').map(Number);
            let newYear = year;
            let newWeek = week + direction;
            
            if (newWeek > 52) { newWeek = 1; newYear++; }
            if (newWeek < 1) { newWeek = 52; newYear--; }
            
            const wStr = newWeek.toString().padStart(2, '0');
            url.searchParams.set('week', `${newYear}-W${wStr}`);
        } else {
            const [year, month] = currentDateParam.split('-').map(Number);
            let d = new Date(year, month - 1 + direction, 1);
            const mStr = String(d.getMonth() + 1).padStart(2, '0');
            const newParam = `${d.getFullYear()}-${mStr}`;
            url.searchParams.set('month', newParam);
        }
        
        window.location.href = url.toString();
    }
    
    lucide.createIcons();
    
    // Swipe Gestures para Dispositivos Móveis
    let touchStartX = 0;
    let touchEndX = 0;
    let isNavigating = false;
    
    const calendarWrapper = document.querySelector('.calendar-body, .grid');
    if (calendarWrapper && window.innerWidth <= 768) {
        calendarWrapper.addEventListener('touchstart', e => {
            touchStartX = e.changedTouches[0].screenX;
        }, { passive: true });
        
        calendarWrapper.addEventListener('touchend', e => {
            touchEndX = e.changedTouches[0].screenX;
            handleSwipe();
        }, { passive: true });
        
        function handleSwipe() {
            if(isNavigating) return;
            const swipeThreshold = 60;
            const diff = touchStartX - touchEndX;
            
            if (Math.abs(diff) > swipeThreshold) {
                isNavigating = true;
                if (diff > 0) {
                    navigate(1);
                } else {
                    navigate(-1);
                }
            }
        }
    }
</script>

<?php renderAppFooter(); ?>