<?php
// admin/agenda.php
require_once '../includes/auth.php';
require_once '../includes/db.php';
require_once '../includes/layout.php';

// Parâmetros de visualização
$viewMode = $_GET['view'] ?? 'calendar'; // calendar ou list
$currentMonth = $_GET['month'] ?? date('Y-m');
$currentYear = date('Y', strtotime($currentMonth . '-01'));
$currentMonthNum = date('m', strtotime($currentMonth . '-01'));

// Buscar eventos do mês atual
$startDate = $currentMonth . '-01';
$endDate = date('Y-m-t', strtotime($startDate));

$stmtEvents = $pdo->prepare("
    SELECT e.*, 
           u.name as creator_name,
           COUNT(DISTINCT ep.id) as participant_count,
           COUNT(DISTINCT CASE WHEN ep.status = 'confirmed' THEN ep.id END) as confirmed_count
    FROM events e
    LEFT JOIN users u ON e.created_by = u.id
    LEFT JOIN event_participants ep ON e.id = ep.event_id
    WHERE DATE(e.start_datetime) BETWEEN ? AND ?
    GROUP BY e.id
    ORDER BY e.start_datetime ASC
");
$stmtEvents->execute([$startDate, $endDate]);
$events = $stmtEvents->fetchAll(PDO::FETCH_ASSOC);

// Buscar escalas do mês (integração)
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
    $day = date('j', strtotime($event['start_datetime']));
    if (!isset($eventsByDay[$day])) $eventsByDay[$day] = [];
    $eventsByDay[$day][] = array_merge($event, ['source' => 'event']);
}

// Adicionar escalas ao calendário
foreach ($schedules as $schedule) {
    $day = date('j', strtotime($schedule['event_date']));
    if (!isset($eventsByDay[$day])) $eventsByDay[$day] = [];
    $eventsByDay[$day][] = array_merge($schedule, [
        'source' => 'schedule',
        'title' => $schedule['event_type'],
        'color' => '#047857',
        'event_type' => 'escala'
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
        margin-bottom: 16px;
        display: flex;
        gap: 12px;
        align-items: center;
        flex-wrap: wrap;
        box-shadow: var(--shadow-sm);
    }
    
    .month-nav {
        display: flex;
        align-items: center;
        gap: 12px;
        flex: 1;
        min-width: 200px;
    }
    
    .month-nav button {
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
    
    .month-nav button:hover {
        background: var(--primary);
        color: white;
        border-color: var(--primary);
    }
    
    .month-display {
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
        border-radius: 8px;
    }
    
    .view-toggle button {
        padding: 8px 16px;
        border: none;
        background: transparent;
        border-radius: 6px;
        cursor: pointer;
        font-weight: 600;
        font-size: 0.875rem;
        color: var(--text-muted);
        transition: all 0.2s;
    }
    
    .view-toggle button.active {
        background: var(--primary);
        color: white;
        box-shadow: 0 2px 4px rgba(4, 120, 87, 0.2);
    }
    
    /* Calendar Grid */
    .calendar-grid {
        background: var(--bg-surface);
        border: 1px solid var(--border-color);
        border-radius: 12px;
        overflow: hidden;
        box-shadow: var(--shadow-sm);
    }
    
    .calendar-header {
        display: grid;
        grid-template-columns: repeat(7, 1fr);
        background: var(--primary);
        color: white;
    }
    
    .calendar-header-day {
        padding: 12px 8px;
        text-align: center;
        font-weight: 700;
        font-size: 0.75rem;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }
    
    .calendar-body {
        display: grid;
        grid-template-columns: repeat(7, 1fr);
        gap: 1px;
        background: var(--border-color);
    }
    
    .calendar-day {
        min-height: 100px;
        background: var(--bg-surface);
        padding: 8px;
        position: relative;
        cursor: pointer;
        transition: background 0.2s;
    }
    
    .calendar-day:hover {
        background: var(--bg-body);
    }
    
    .calendar-day.other-month {
        background: var(--bg-body);
        opacity: 0.5;
    }
    
    .calendar-day.today {
        background: var(--primary-subtle);
        border: 2px solid var(--primary);
    }
    
    .day-number {
        font-weight: 700;
        font-size: 0.875rem;
        color: var(--text-main);
        margin-bottom: 4px;
    }
    
    .day-events {
        display: flex;
        flex-direction: column;
        gap: 2px;
    }
    
    .event-pill {
        font-size: 10px;
        padding: 3px 6px;
        border-radius: 4px;
        font-weight: 600;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
        cursor: pointer;
        transition: all 0.2s;
    }
    
    .event-pill:hover {
        transform: translateX(2px);
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    }
    
    /* List View */
    .event-list {
        display: flex;
        flex-direction: column;
        gap: 12px;
    }
    
    .event-card {
        background: var(--bg-surface);
        border: 1px solid var(--border-color);
        border-radius: 12px;
        padding: 16px;
        box-shadow: var(--shadow-sm);
        display: flex;
        gap: 12px;
        cursor: pointer;
        transition: all 0.2s;
        position: relative;
        overflow: hidden;
    }
    
    .event-card::before {
        content: '';
        position: absolute;
        left: 0;
        top: 0;
        bottom: 0;
        width: 4px;
        background: var(--event-color, var(--primary));
    }
    
    .event-card:hover {
        box-shadow: var(--shadow-md);
        transform: translateY(-2px);
    }
    
    .event-date-badge {
        background: var(--event-color, var(--primary));
        color: white;
        border-radius: 10px;
        padding: 12px;
        text-align: center;
        min-width: 60px;
        height: fit-content;
    }
    
    .event-date-day {
        font-size: 1.5rem;
        font-weight: 800;
        line-height: 1;
    }
    
    .event-date-month {
        font-size: 0.75rem;
        font-weight: 600;
        text-transform: uppercase;
        opacity: 0.9;
    }
    
    .event-content {
        flex: 1;
    }
    
    .event-title {
        font-size: 1rem;
        font-weight: 700;
        color: var(--text-main);
        margin-bottom: 4px;
    }
    
    .event-meta {
        display: flex;
        gap: 16px;
        flex-wrap: wrap;
        font-size: 0.8125rem;
        color: var(--text-muted);
        margin-bottom: 8px;
    }
    
    .event-meta-item {
        display: flex;
        align-items: center;
        gap: 4px;
    }
    
    .event-type-badge {
        display: inline-block;
        padding: 3px 8px;
        border-radius: 6px;
        font-size: 0.75rem;
        font-weight: 600;
    }
    
    /* Floating Action Button */
    .fab {
        position: fixed;
        bottom: 90px;
        right: 20px;
        width: 56px;
        height: 56px;
        background: linear-gradient(135deg, var(--primary), var(--primary-hover));
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        box-shadow: 0 4px 12px rgba(4, 120, 87, 0.4);
        cursor: pointer;
        color: white;
        border: none;
        transition: all 0.3s;
        z-index: 100;
    }
    
    .fab:hover {
        transform: scale(1.1);
        box-shadow: 0 6px 20px rgba(4, 120, 87, 0.5);
    }
    
    .fab:active {
        transform: scale(0.95);
    }
    
    /* Empty State */
    .empty-state {
        text-align: center;
        padding: 60px 20px;
        color: var(--text-muted);
    }
    
    .empty-state i {
        width: 48px;
        height: 48px;
        margin-bottom: 16px;
    }
    
    @media (max-width: 768px) {
        .calendar-day {
            min-height: 70px;
            padding: 4px;
        }
        
        .day-number {
            font-size: 0.75rem;
        }
        
        .event-pill {
            font-size: 9px;
            padding: 2px 4px;
        }
        
        .calendar-header-day {
            font-size: 0.65rem;
            padding: 8px 4px;
        }
    }
</style>

<div class="agenda-container">
    <!-- Controls -->
    <div class="agenda-controls">
        <div class="month-nav">
            <button onclick="changeMonth(-1)" title="Mês anterior">
                <i data-lucide="chevron-left" style="width: 20px;"></i>
            </button>
            <div class="month-display" id="monthDisplay">
                <?php
                $monthNames = ['', 'Janeiro', 'Fevereiro', 'Março', 'Abril', 'Maio', 'Junho', 
                              'Julho', 'Agosto', 'Setembro', 'Outubro', 'Novembro', 'Dezembro'];
                echo $monthNames[(int)$currentMonthNum] . ' ' . $currentYear;
                ?>
            </div>
            <button onclick="changeMonth(1)" title="Próximo mês">
                <i data-lucide="chevron-right" style="width: 20px;"></i>
            </button>
        </div>
        
        <div class="view-toggle">
            <button class="<?= $viewMode === 'calendar' ? 'active' : '' ?>" onclick="changeView('calendar')">
                <i data-lucide="calendar" style="width: 14px; display: inline-block; vertical-align: middle; margin-right: 4px;"></i>
                Calendário
            </button>
            <button class="<?= $viewMode === 'list' ? 'active' : '' ?>" onclick="changeView('list')">
                <i data-lucide="list" style="width: 14px; display: inline-block; vertical-align: middle; margin-right: 4px;"></i>
                Lista
            </button>
        </div>
    </div>
    
    <?php if ($viewMode === 'calendar'): ?>
        <!-- Calendar View -->
        <div class="calendar-grid">
            <div class="calendar-header">
                <div class="calendar-header-day">Dom</div>
                <div class="calendar-header-day">Seg</div>
                <div class="calendar-header-day">Ter</div>
                <div class="calendar-header-day">Qua</div>
                <div class="calendar-header-day">Qui</div>
                <div class="calendar-header-day">Sex</div>
                <div class="calendar-header-day">Sáb</div>
            </div>
            <div class="calendar-body">
                <?php
                $firstDay = date('w', strtotime($startDate));
                $totalDays = date('t', strtotime($startDate));
                $today = date('Y-m-d');
                
                // Dias do mês anterior
                $prevMonthDays = date('t', strtotime($currentMonth . '-01 -1 month'));
                for ($i = $firstDay - 1; $i >= 0; $i--) {
                    $day = $prevMonthDays - $i;
                    echo "<div class='calendar-day other-month'><div class='day-number'>$day</div></div>";
                }
                
                // Dias do mês atual
                for ($day = 1; $day <= $totalDays; $day++) {
                    $dateStr = sprintf('%s-%02d', $currentMonth, $day);
                    $isToday = $dateStr === $today;
                    $dayEvents = $eventsByDay[$day] ?? [];
                    
                    echo "<div class='calendar-day" . ($isToday ? ' today' : '') . "' onclick='openDay(\"$dateStr\")'>";
                    echo "<div class='day-number'>$day</div>";
                    
                    if (!empty($dayEvents)) {
                        echo "<div class='day-events'>";
                        foreach (array_slice($dayEvents, 0, 3) as $evt) {
                            $bgColor = $evt['color'] ?? '#047857';
                            $title = htmlspecialchars($evt['title']);
                            $icon = $evt['source'] === 'schedule' ? '♪' : '•';
                            echo "<div class='event-pill' style='background: {$bgColor}20; color: {$bgColor};' onclick='event.stopPropagation(); openEvent({$evt['id']}, \"{$evt['source']}\")'>$icon $title</div>";
                        }
                        if (count($dayEvents) > 3) {
                            $remaining = count($dayEvents) - 3;
                            echo "<div style='font-size: 10px; color: var(--text-muted); margin-top: 2px;'>+$remaining mais</div>";
                        }
                        echo "</div>";
                    }
                    
                    echo "</div>";
                }
                
                // Completar com dias do próximo mês
                $totalCells = $firstDay + $totalDays;
                $remainingCells = 7 - ($totalCells % 7);
                if ($remainingCells < 7) {
                    for ($day = 1; $day <= $remainingCells; $day++) {
                        echo "<div class='calendar-day other-month'><div class='day-number'>$day</div></div>";
                    }
                }
                ?>
            </div>
        </div>
        
    <?php else: ?>
        <!-- List View -->
        <div class="event-list">
            <?php
            $allItems = array_merge($events, array_map(function($s) {
                return array_merge($s, [
                    'source' => 'schedule',
                    'title' => $s['event_type'],
                    'start_datetime' => $s['event_date'] . ' 19:00:00',
                    'color' => '#047857',
                    'location' => 'Templo Principal'
                ]);
            }, $schedules));
            
            usort($allItems, function($a, $b) {
                return strtotime($a['start_datetime']) - strtotime($b['start_datetime']);
            });
            
            if (empty($allItems)): ?>
                <div class="empty-state">
                    <i data-lucide="calendar-off"></i>
                    <h3 style="margin: 0 0 8px; color: var(--text-main);">Nenhum evento neste mês</h3>
                    <p>Clique no botão + para adicionar um novo evento</p>
                </div>
            <?php else:
                foreach ($allItems as $item):
                    $day = date('d', strtotime($item['start_datetime']));
                    $month = date('M', strtotime($item['start_datetime']));
                    $time = date('H:i', strtotime($item['start_datetime']));
                    $color = $item['color'] ?? '#047857';
                    $source = $item['source'] ?? 'event';
                    $typeBadge = $source === 'schedule' ? 'Escala Musical' : ucfirst(str_replace('_', ' ', $item['event_type'] ?? 'evento'));
            ?>
                <div class="event-card" style="--event-color: <?= $color ?>" onclick="openEvent(<?= $item['id'] ?>, '<?= $source ?>')">
                    <div class="event-date-badge" style="background: <?= $color ?>">
                        <div class="event-date-day"><?= $day ?></div>
                        <div class="event-date-month"><?= strtoupper($month) ?></div>
                    </div>
                    <div class="event-content">
                        <div class="event-title"><?= htmlspecialchars($item['title']) ?></div>
                        <div class="event-meta">
                            <div class="event-meta-item">
                                <i data-lucide="clock" style="width: 14px;"></i>
                                <?= $time ?>
                            </div>
                            <?php if (!empty($item['location'])): ?>
                            <div class="event-meta-item">
                                <i data-lucide="map-pin" style="width: 14px;"></i>
                                <?= htmlspecialchars($item['location']) ?>
                            </div>
                            <?php endif; ?>
                            <div class="event-meta-item">
                                <i data-lucide="users" style="width: 14px;"></i>
                                <?= $item['participant_count'] ?? 0 ?> participantes
                            </div>
                        </div>
                        <span class="event-type-badge" style="background: <?= $color ?>20; color: <?= $color ?>">
                            <?= $typeBadge ?>
                        </span>
                    </div>
                </div>
            <?php endforeach; endif; ?>
        </div>
    <?php endif; ?>
</div>

<!-- Floating Action Button -->
<a href="evento_adicionar.php" class="fab ripple" title="Adicionar evento">
    <i data-lucide="plus" style="width: 28px; height: 28px;"></i>
</a>

<script>
function changeMonth(direction) {
    const currentUrl = new URL(window.location.href);
    const currentMonth = currentUrl.searchParams.get('month') || '<?= $currentMonth ?>';
    const date = new Date(currentMonth + '-01');
    date.setMonth(date.getMonth() + direction);
    const newMonth = date.getFullYear() + '-' + String(date.getMonth() + 1).padStart(2, '0');
    currentUrl.searchParams.set('month', newMonth);
    window.location.href = currentUrl.toString();
}

function changeView(view) {
    const currentUrl = new URL(window.location.href);
    currentUrl.searchParams.set('view', view);
    window.location.href = currentUrl.toString();
}

function openDay(date) {
    // Futura implementação: modal com eventos do dia
    console.log('Abrir dia:', date);
}

function openEvent(id, source) {
    if (source === 'schedule') {
        window.location.href = 'escala_detalhe.php?id=' + id;
    } else {
        window.location.href = 'evento_detalhe.php?id=' + id;
    }
}

lucide.createIcons();
</script>

<?php renderAppFooter(); ?>