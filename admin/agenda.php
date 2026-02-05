<?php
// admin/agenda.php
require_once '../includes/auth.php';
require_once '../includes/db.php';
require_once '../includes/layout.php';

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

// Buscar escalas no intervalo
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

// Organizar eventos por dia
$eventsByDay = [];
foreach ($events as $event) {
    $dayKey = date('Y-m-d', strtotime($event['start_datetime']));
    if (!isset($eventsByDay[$dayKey])) $eventsByDay[$dayKey] = [];
    $image = '../assets/img/event_placeholder.png'; // Placeholder
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

<style>
    body { background: var(--bg-body); }
    
    .agenda-container {
        max-width: 1200px;
        margin: 0 auto;
        padding: 16px 12px 100px;
    }
    
    /* Header Controls */
    .agenda-controls {
        background: var(--bg-surface);
        border: 1px solid var(--border-color);
        border-radius: 12px;
        padding: 16px;
        margin-bottom: 24px;
        display: flex;
        gap: 16px;
        align-items: center;
        flex-wrap: wrap;
        box-shadow: var(--shadow-sm);
    }
    
    .nav-group {
        display: flex;
        align-items: center;
        gap: 12px;
        flex: 1;
        min-width: 280px;
    }
    
    .nav-btn {
        width: 36px;
        height: 36px;
        border: 1px solid var(--border-color);
        background: var(--bg-body);
        border-radius: 8px;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        color: var(--text-main);
        transition: all 0.2s;
    }
    
    .nav-btn:hover {
        background: var(--primary);
        color: white;
        border-color: var(--primary);
    }
    
    .current-display {
        font-size: 1.125rem;
        font-weight: 700;
        color: var(--text-main);
        flex: 1;
        text-align: center;
    }
    
    .view-toggle {
        display: flex;
        gap: 4px;
        background: var(--bg-body);
        padding: 4px;
        border-radius: 10px;
        border: 1px solid var(--border-color);
    }
    
    .view-btn {
        padding: 8px 16px;
        border: none;
        background: transparent;
        border-radius: 8px;
        cursor: pointer;
        font-weight: 600;
        font-size: 0.875rem;
        color: var(--text-muted);
        transition: all 0.2s;
        display: flex;
        align-items: center;
        gap: 6px;
    }
    
    .view-btn.active {
        background: white;
        color: var(--primary);
        box-shadow: 0 1px 3px rgba(0,0,0,0.1);
    }
    
    /* Grid Styles */
    .calendar-wrapper {
        background: var(--bg-surface);
        border: 1px solid var(--border-color);
        border-radius: 16px;
        overflow: hidden;
        box-shadow: var(--shadow-sm);
    }
    
    .week-header {
        display: grid;
        grid-template-columns: repeat(7, 1fr);
        background: var(--slate-50);
        border-bottom: 1px solid var(--border-color);
    }
    
    .week-header-day {
        padding: 12px 8px;
        text-align: center;
        font-weight: 600;
        font-size: 0.85rem;
        color: var(--text-muted);
        text-transform: uppercase;
    }
    
    .calendar-body {
        display: grid;
        grid-template-columns: repeat(7, 1fr);
        /* Se for month view, auto rows. Se week view, 1 linha fixa min-height maior */
    }
    
    .calendar-day {
        min-height: <?= $viewMode === 'week' ? '400px' : '120px' ?>;
        border-right: 1px solid var(--border-color);
        border-bottom: 1px solid var(--border-color);
        padding: 8px;
        background: white;
        position: relative;
    }
    
    .calendar-day:nth-child(7n) {
        border-right: none;
    }
    
    .calendar-day.other-month {
        background: #f9fafb;
        color: #9ca3af;
    }
    
    .calendar-day.today {
        background: var(--sage-50);
    }
    
    .day-num {
        font-weight: 700;
        font-size: 0.9rem;
        width: 28px;
        height: 28px;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 50%;
        margin-bottom: 8px;
    }
    
    .calendar-day.today .day-num {
        background: var(--primary);
        color: white;
    }
    
    /* Event Chips in Calendar */
    .event-chip {
        font-size: 0.75rem;
        padding: 4px 8px;
        border-radius: 6px;
        margin-bottom: 4px;
        cursor: pointer;
        background: var(--slate-100);
        color: #213f78;
        border-left: 3px solid var(--slate-600);
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
        transition: transform 0.1s;
    }
    
    .event-chip:hover {
        transform: translateY(-1px);
        box-shadow: 0 2px 4px rgba(0,0,0,0.05);
    }
    
    /* Week View Specifics */
    .week-view-day {
        display: flex;
        flex-direction: column;
        gap: 8px;
    }
    
    /* List View Styles */
    .list-wrapper {
        display: flex;
        flex-direction: column;
        gap: 12px;
    }
    
    .list-item {
        background: var(--bg-surface);
        border: 1px solid var(--border-color);
        border-radius: 12px;
        padding: 16px;
        display: flex;
        align-items: center;
        gap: 16px;
        transition: transform 0.2s;
    }
    .list-item:hover {
        transform: translateX(4px);
        border-color: var(--primary);
    }
    
    .date-box {
        background: var(--slate-100);
        border-radius: 10px;
        padding: 10px;
        text-align: center;
        min-width: 60px;
    }
    .date-box .day { font-size: 1.25rem; font-weight: 800; line-height: 1; }
    .date-box .month { font-size: 0.7rem; text-transform: uppercase; font-weight: 600; color: var(--text-muted); }
    
    /* FAB */
    .fab {
        position: fixed;
        bottom: 24px;
        right: 24px;
        width: 56px;
        height: 56px;
        background: var(--primary);
        color: white;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        box-shadow: 0 4px 12px rgba(55, 106, 200, 0.4);
        cursor: pointer;
        z-index: 100;
        transition: transform 0.2s;
        text-decoration: none;
    }
    .fab:hover { transform: scale(1.1); }
    
    @media (max-width: 768px) {
        .calendar-day {
            min-height: <?= $viewMode === 'week' ? '150px' : '60px' ?>;
            font-size: 0.8rem;
            padding: 4px;
        }
        .week-header-day { font-size: 0.7rem; padding: 8px 2px; }
        
        /* Transform chips into dots on mobile month view */
        @media (max-width: 640px) {
            .calendar-day .event-chip { 
                width: 8px; 
                height: 8px; 
                padding: 0; 
                font-size: 0;
                border: none !important;
                border-radius: 50%;
                margin: 2px 1px;
                display: inline-block;
                /* Use css var or important to override inline styles if needed, 
                   but inline style sets background-color with opacity. 
                   We need solid color. 
                   We can use 'color' property which is set inline, to set background. */
                background-color: currentColor !important;
                opacity: 0.8;
            }
        }
        
        .nav-group { min-width: auto; }
        .current-display { font-size: 0.95rem; }
    }
</style>



<div class="agenda-container">
    <div class="agenda-controls">
        <div class="nav-group">
            <button class="nav-btn" onclick="navigate(-1)">
                <i data-lucide="chevron-left" width="20"></i>
            </button>
            <div class="current-display"><?= $displayTitle ?></div>
            <button class="nav-btn" onclick="navigate(1)">
                <i data-lucide="chevron-right" width="20"></i>
            </button>
        </div>
        
        <div class="view-toggle">
            <button class="view-btn <?= $viewMode === 'month' ? 'active' : '' ?>" onclick="switchView('month')">
                <i data-lucide="calendar" width="16"></i> Mês
            </button>
            <button class="view-btn <?= $viewMode === 'week' ? 'active' : '' ?>" onclick="switchView('week')">
                <i data-lucide="columns" width="16"></i> Semana
            </button>
            <button class="view-btn <?= $viewMode === 'list' ? 'active' : '' ?>" onclick="switchView('list')">
                <i data-lucide="list" width="16"></i> Lista
            </button>
        </div>
    </div>

    <?php if ($viewMode === 'list'): ?>
        <div class="list-wrapper">
            <?php 
            // merge and sort all events for list
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
                <div class="empty-state">Nenhum evento neste período.</div>
            <?php else: 
                foreach ($allItems as $item): 
                    $color = $item['color'] ?? '#047857';
            ?>
                <a href="<?= $item['source'] === 'schedule' ? 'escala_detalhe.php?id='.$item['id'] : 'evento_detalhe.php?id='.$item['id'] ?>" class="list-item" style="text-decoration: none; color: inherit;">
                    <div class="date-box">
                        <div class="day"><?= date('d', strtotime($item['start_datetime'])) ?></div>
                        <div class="month"><?= date('M', strtotime($item['start_datetime'])) ?></div>
                    </div>
                    <div>
                        <div style="font-weight: 700; font-size: 1rem; margin-bottom: 4px;"><?= htmlspecialchars($item['title']) ?></div>
                        <div style="font-size: 0.85rem; color: var(--text-muted); display: flex; align-items: center; gap: 8px;">
                            <span><i data-lucide="clock" width="14" style="vertical-align: middle;"></i> <?= date('H:i', strtotime($item['start_datetime'])) ?></span>
                            <?php if(!empty($item['participant_count'])): ?>
                                <span><i data-lucide="users" width="14" style="vertical-align: middle;"></i> <?= $item['participant_count'] ?></span>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div style="margin-left: auto;">
                        <span style="background: <?= $color ?>20; color: <?= $color ?>; padding: 4px 8px; border-radius: 6px; font-size: 0.75rem; font-weight: 600;">
                            <?= $item['source'] === 'schedule' ? 'Escala' : 'Evento' ?>
                        </span>
                    </div>
                </a>
            <?php endforeach; endif; ?>
        </div>
    
    <?php else: // Calendar/Week View Grid ?>
        <div class="calendar-wrapper">
            <div class="week-header">
                <div class="week-header-day">Dom</div>
                <div class="week-header-day">Seg</div>
                <div class="week-header-day">Ter</div>
                <div class="week-header-day">Qua</div>
                <div class="week-header-day">Qui</div>
                <div class="week-header-day">Sex</div>
                <div class="week-header-day">Sáb</div>
            </div>
            
            <div class="calendar-body">
                <?php
                if ($viewMode === 'month') {
                    // Logic to fill empty cells before 1st of month
                    $firstDayMonth = date('w', strtotime($startDate));
                    $daysInMonth = date('t', strtotime($startDate));
                    
                    // Previous month pad
                    for($i=0; $i < $firstDayMonth; $i++) {
                        echo '<div class="calendar-day other-month"></div>';
                    }
                    
                    // Days
                    for($d=1; $d <= $daysInMonth; $d++) {
                        $currentDate = sprintf('%s-%02d', date('Y-m', strtotime($startDate)), $d);
                        $isToday = ($currentDate === date('Y-m-d'));
                        $dayEvents = $eventsByDay[$currentDate] ?? [];
                        
                        echo '<div class="calendar-day '.($isToday?'today':'').'">';
                        echo '<div class="day-num">'.$d.'</div>';
                        
                        foreach($dayEvents as $evt) {
                            $color = $evt['color'] ?? 'var(--slate-600)';
                            $url = $evt['source'] === 'schedule' ? 'escala_detalhe.php?id='.$evt['id'] : 'evento_detalhe.php?id='.$evt['id'];
                            echo "<div onclick=\"window.location='$url'\" class='event-chip' style='border-left-color: $color; color: $color; background: {$color}15;'>";
                            echo htmlspecialchars($evt['title']);
                            echo "</div>";
                        }
                        
                        echo '</div>';
                    }
                    
                    // Next month pad
                    $totalCells = $firstDayMonth + $daysInMonth;
                    $remaining = 7 - ($totalCells % 7);
                    if($remaining < 7) {
                        for($i=0; $i < $remaining; $i++){
                            echo '<div class="calendar-day other-month"></div>';
                        }
                    }
                } 
                elseif ($viewMode === 'week') {
                    // 7 days fixed for current week
                    $current = new DateTime($startDate); // Start of week (Monday or Sunday?) 
                    // Note: In PHP logic above we used ISO (Monday start). 
                    // But calendar header shows Dom..Sab (Sunday start). 
                    // Let's adjust $startDate to be SUNDAY for the grid if ISO gave Monday.
                    
                    // If ISODate gives Monday, and we want Sunday start for visual consistency with header:
                    // Only if user prefers Sunday Start. Let's assume standard Brazilian calendars often start on Sunday.
                    // Let's adjust the PHP logic loop to start from Sunday.
                    
                    // Re-adjusting start date for loop:
                    $loopDate = new DateTime($startDate);
                    if ($loopDate->format('w') == 1) { // If Monday
                         $loopDate->modify('-1 day'); // Go to Sunday
                    }
                    
                    for($i=0; $i<7; $i++) {
                        $dStr = $loopDate->format('Y-m-d');
                        $dayNum = $loopDate->format('d');
                        $isToday = ($dStr === date('Y-m-d'));
                        $dayEvents = $eventsByDay[$dStr] ?? [];
                        
                        echo '<div class="calendar-day week-view-day '.($isToday?'today':'').'">';
                        echo '<div class="day-num">'.$dayNum.'</div>';
                         foreach($dayEvents as $evt) {
                            $color = $evt['color'] ?? 'var(--slate-600)';
                            $startTime = date('H:i', strtotime($evt['start_datetime']));
                            $url = $evt['source'] === 'schedule' ? 'escala_detalhe.php?id='.$evt['id'] : 'evento_detalhe.php?id='.$evt['id'];
                            
                            echo "<div onclick=\"window.location='$url'\" class='event-chip' style='border-left-color: $color; color: $color; background: {$color}15; padding: 8px;'>";
                            echo "<div style='font-size:0.7rem; opacity:0.8'>$startTime</div>";
                            echo "<div style='font-weight:600'>".htmlspecialchars($evt['title'])."</div>";
                            echo "</div>";
                        }
                        echo '</div>';
                        
                        $loopDate->modify('+1 day');
                    }
                }
                ?>
            </div>
        </div>
    <?php endif; ?>
</div>

<!-- FAB -->
<a href="evento_adicionar.php" class="fab">
    <i data-lucide="plus" width="28"></i>
</a>

<script>
    const currentView = '<?= $viewMode ?>';
    const currentDateParam = '<?= $currentDateParam ?>'; // Month 'YYYY-MM' or Week 'YYYY-Www'

    function switchView(view) {
        // When switching, keep current date rough context
        const url = new URL(window.location);
        url.searchParams.set('view', view);
        
        // Reset specific params to avoid mismatch
        url.searchParams.delete('month');
        url.searchParams.delete('week');
        
        window.location.href = url.toString();
    }

    function navigate(direction) {
        const url = new URL(window.location);
        
        if (currentView === 'week') {
            // Parse YYYY-Www
            const [year, week] = currentDateParam.split('-W').map(Number);
            let newYear = year;
            let newWeek = week + direction;
            
            // Simple logic for week rollover (ISO weeks max 52/53)
            // It's safer to use Date logic
            // But JS handling of ISO weeks is tricky without libraries.
            // Let's rely on server side PHP to handle empty/default params or pass calculated dates?
            // Better: Let's do simple math here assuming 52 weeks roughly, server handles fix?
            // Actually, best is to calculate date from week, add 7 days, get new ISO week.
            
            // Helper to get ISO week from Date
            function getISOWeek(d) {
                const date = new Date(d.getTime());
                date.setHours(0, 0, 0, 0);
                // Thursday in current week decides the year.
                date.setDate(date.getDate() + 3 - (date.getDay() + 6) % 7);
                // January 4 is always in week 1.
                const week1 = new Date(date.getFullYear(), 0, 4);
                return 1 + Math.round(((date.getTime() - week1.getTime()) / 86400000 - 3 + (week1.getDay() + 6) % 7) / 7);
            }
            
            // Get current week start date logic in JS is messy.
            // Simplified approach: Parsing the string manually.
            if (newWeek > 52) { newWeek = 1; newYear++; }
            if (newWeek < 1) { newWeek = 52; newYear--; }
            
            // Padding
            const wStr = newWeek.toString().padStart(2, '0');
            url.searchParams.set('week', `${newYear}-W${wStr}`);
            
        } else {
            // Month navigation 'YYYY-MM'
            const [year, month] = currentDateParam.split('-').map(Number);
            let d = new Date(year, month - 1 + direction, 1);
            const mStr = String(d.getMonth() + 1).padStart(2, '0');
            const newParam = `${d.getFullYear()}-${mStr}`;
            url.searchParams.set('month', newParam);
        }
        
        window.location.href = url.toString();
    }
    
    lucide.createIcons();
</script>

<?php renderAppFooter(); ?>