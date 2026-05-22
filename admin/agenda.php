<?php
// admin/agenda.php - Redesign Premium Sacred Minimalist
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

$monthNames = ['', 'Janeiro', 'Fevereiro', 'Março', 'Abril', 'Maio', 'Junho', 
              'Julho', 'Agosto', 'Setembro', 'Outubro', 'Novembro', 'Dezembro'];

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
    $eventsByDay[$dayKey][] = array_merge($event, ['source' => 'event']);
}

foreach ($schedules as $schedule) {
    $dayKey = $schedule['event_date'];
    if (!isset($eventsByDay[$dayKey])) $eventsByDay[$dayKey] = [];
    $eventsByDay[$dayKey][] = array_merge($schedule, [
        'source' => 'schedule',
        'title' => $schedule['event_type'],
        'event_type' => 'escala',
        'start_datetime' => $schedule['event_date'] . ' 19:30:00' // Horário fictício se não tiver
    ]);
}

renderAppHeader('Agenda');
?>

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6 pb-28 space-y-6">
    
    <!-- Hero Section (Bento Card Destaque - Sacred Minimalist) -->
    <div class="bg-white dark:bg-[#18191D] border border-gray-100 dark:border-[#26272B] rounded-[2px] p-6 sm:p-8 text-center shadow-sm relative overflow-hidden group reveal-item">
        <div class="absolute -right-12 -top-12 w-48 h-48 bg-[#2E7EED]/5 rounded-none blur-xl pointer-events-none group-hover:scale-110 transition-transform duration-700"></div>
        
        <div class="bg-[#121316] border border-gray-100 dark:border-[#26272B] w-14 h-14 rounded-[2px] flex items-center justify-center mx-auto mb-4 shadow-sm group-hover:border-[#2E7EED] transition-colors duration-300">
            <i data-lucide="calendar" class="text-[#2E7EED] w-6 h-6"></i>
        </div>
        <h2 class="text-xl sm:text-2xl font-black text-gray-800 dark:text-white font-outfit tracking-tight uppercase">Agenda do Ministério</h2>
        <p class="text-xs text-gray-500 dark:text-gray-400 max-w-sm mx-auto mt-2 font-medium leading-relaxed">
            Acompanhe ensaios, cultos, escalas e eventos do nosso ministério de louvor. Organize seu tempo para servir ao Senhor com excelência.
        </p>
    </div>
    
    <!-- CONTROLES DA AGENDA (Bento Box Horizontal - Sacred Minimalist) -->
    <div class="bg-white dark:bg-[#18191D] border border-gray-100 dark:border-[#26272B] rounded-[2px] p-3.5 flex flex-col sm:flex-row sm:items-center justify-between gap-4 shadow-sm reveal-item">
        
        <!-- Navegação de Período -->
        <div class="flex items-center gap-1 bg-gray-50 dark:bg-[#121316] rounded-[2px] p-1 border border-gray-100 dark:border-[#26272B] shadow-inner justify-between sm:justify-start w-full sm:w-auto">
            <button class="p-2 rounded-[2px] text-gray-500 dark:text-gray-400 hover:text-[#2E7EED] dark:hover:text-white hover:bg-white dark:hover:bg-[#18191D] border border-transparent hover:border-gray-100 dark:hover:border-[#26272B] transition-all duration-200 active:scale-[0.97] will-change-transform cursor-pointer" onclick="navigate(-1)" title="Anterior">
                <i data-lucide="chevron-left" class="w-4 h-4"></i>
            </button>
            <div class="text-xs sm:text-sm font-extrabold uppercase tracking-wider text-gray-800 dark:text-white font-outfit px-4 min-w-[140px] text-center">
                <?= $displayTitle ?>
            </div>
            <button class="p-2 rounded-[2px] text-gray-500 dark:text-gray-400 hover:text-[#2E7EED] dark:hover:text-white hover:bg-white dark:hover:bg-[#18191D] border border-transparent hover:border-gray-100 dark:hover:border-[#26272B] transition-all duration-200 active:scale-[0.97] will-change-transform cursor-pointer" onclick="navigate(1)" title="Próximo">
                <i data-lucide="chevron-right" class="w-4 h-4"></i>
            </button>
        </div>
        
        <!-- Alternador de Visualização -->
        <div class="flex items-center bg-gray-50 dark:bg-[#121316] border border-gray-100 dark:border-[#26272B] p-1 rounded-[2px] shadow-inner justify-center w-full sm:w-auto">
            <button class="flex-1 sm:flex-none flex items-center justify-center gap-1.5 px-4 py-2 rounded-[2px] text-[10px] font-bold uppercase tracking-wider transition-all duration-200 cursor-pointer active:scale-[0.97] will-change-transform <?= $viewMode === 'month' ? 'bg-[#2E7EED] text-white shadow-sm border border-[#2E7EED]' : 'text-gray-500 dark:text-gray-400 hover:text-gray-800 dark:hover:text-white hover:bg-white dark:hover:bg-[#18191D] border border-transparent hover:border-gray-100 dark:hover:border-[#26272B]' ?>" onclick="switchView('month')">
                <i data-lucide="calendar" class="w-3.5 h-3.5"></i> Mês
            </button>
            <button class="flex-1 sm:flex-none flex items-center justify-center gap-1.5 px-4 py-2 rounded-[2px] text-[10px] font-bold uppercase tracking-wider transition-all duration-200 cursor-pointer active:scale-[0.97] will-change-transform <?= $viewMode === 'week' ? 'bg-[#2E7EED] text-white shadow-sm border border-[#2E7EED]' : 'text-gray-500 dark:text-gray-400 hover:text-gray-800 dark:hover:text-white hover:bg-white dark:hover:bg-[#18191D] border border-transparent hover:border-gray-100 dark:hover:border-[#26272B]' ?>" onclick="switchView('week')">
                <i data-lucide="columns" class="w-3.5 h-3.5"></i> Semana
            </button>
            <button class="flex-1 sm:flex-none flex items-center justify-center gap-1.5 px-4 py-2 rounded-[2px] text-[10px] font-bold uppercase tracking-wider transition-all duration-200 cursor-pointer active:scale-[0.97] will-change-transform <?= $viewMode === 'list' ? 'bg-[#2E7EED] text-white shadow-sm border border-[#2E7EED]' : 'text-gray-500 dark:text-gray-400 hover:text-gray-800 dark:hover:text-white hover:bg-white dark:hover:bg-[#18191D] border border-transparent hover:border-gray-100 dark:hover:border-[#26272B]' ?>" onclick="switchView('list')">
                <i data-lucide="list" class="w-3.5 h-3.5"></i> Lista
            </button>
        </div>
    </div>

    <!-- MODO LISTA / LINHA DO TEMPO CONTÍNUA ASSIMÉTRICA -->
    <?php if ($viewMode === 'list'): ?>
        <?php 
        // Mescla e ordena todos os eventos para a listagem
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
            <div class="bg-white dark:bg-[#18191D] border border-gray-100 dark:border-[#26272B] rounded-[2px] p-12 text-center text-gray-400 dark:text-gray-500 font-bold reveal-item">
                <i data-lucide="calendar-off" class="w-10 h-10 mx-auto mb-3 text-gray-300 dark:text-gray-600"></i>
                Nenhum evento registrado para este período.
            </div>
        <?php else: ?>
            <!-- Eixo Vertical da Linha do Tempo -->
            <div class="relative border-l border-gray-100 dark:border-[#26272B] ml-3.5 sm:ml-7 my-6 pl-6 sm:pl-10 space-y-6">
                <?php 
                $index = 0;
                foreach ($allItems as $item): 
                    $isSchedule = $item['source'] === 'schedule';
                    $badgeBg = $isSchedule 
                        ? 'bg-[#2E7EED]/10 text-[#2E7EED] border border-[#2E7EED]/20' 
                        : 'bg-amber-500/10 text-amber-600 dark:text-amber-500 border border-amber-500/20';
                    $url = $isSchedule ? 'escala_detalhe.php?id='.$item['id'] : 'evento_detalhe.php?id='.$item['id'];
                    $index++;
                    $itemDate = strtotime($item['start_datetime']);
                ?>
                    <div class="relative group reveal-item" style="animation-delay: <?= $index * 0.05 ?>s">
                        <!-- Ponto Sharp no Eixo (Sacred Style) -->
                        <div class="absolute -left-[31px] sm:-left-[47px] top-4 w-2 h-2 bg-gray-300 dark:bg-[#26272B] group-hover:bg-[#2E7EED] border-2 border-white dark:border-[#121316] rounded-none transition-all duration-300 group-hover:scale-125"></div>
                        
                        <!-- Bento Card de Conteúdo -->
                        <a href="<?= $url ?>" class="block bg-white dark:bg-[#18191D] border border-gray-100 dark:border-[#26272B] rounded-[2px] p-4.5 hover:border-[#2E7EED]/50 transition-all duration-200 active:scale-[0.97] will-change-transform shadow-sm">
                            <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
                                <div class="flex items-start gap-4">
                                    <!-- Data Brutalista -->
                                    <div class="bg-gray-50 dark:bg-[#121316] border border-gray-100 dark:border-[#26272B] rounded-[2px] p-2 text-center min-w-[55px] flex flex-col justify-center shadow-inner">
                                        <div class="text-base font-black text-gray-800 dark:text-white font-outfit leading-none"><?= date('d', $itemDate) ?></div>
                                        <div class="text-[9px] font-extrabold uppercase tracking-wider text-gray-400 dark:text-gray-500 mt-1 leading-none"><?= substr($monthNames[(int)date('m', $itemDate)], 0, 3) ?></div>
                                    </div>
                                    
                                    <!-- Informações Textuais -->
                                    <div>
                                        <div class="flex items-center gap-2 flex-wrap">
                                            <span class="text-[8px] font-extrabold uppercase tracking-widest px-2 py-0.5 rounded-[2px] <?= $badgeBg ?>">
                                                <?= $isSchedule ? 'Escala' : 'Evento' ?>
                                            </span>
                                            <?php if ($item['is_urgent'] ?? false): ?>
                                                <span class="text-[8px] font-extrabold uppercase tracking-widest px-2 py-0.5 rounded-[2px] bg-red-950/20 text-red-500 border border-red-900/30">
                                                    🔥 Urgente
                                                </span>
                                            <?php endif; ?>
                                        </div>
                                        <h4 class="text-sm sm:text-base font-extrabold text-gray-800 dark:text-white font-outfit tracking-tight mt-1.5 group-hover:text-[#2E7EED] transition-colors">
                                            <?= htmlspecialchars($item['title']) ?>
                                        </h4>
                                        <p class="text-xs text-gray-400 dark:text-gray-500 font-medium mt-1">
                                            <?= htmlspecialchars($item['description'] ?? 'Clique para conferir os detalhes desta atividade.') ?>
                                        </p>
                                    </div>
                                </div>
                                
                                <!-- Meta Informações Assimétricas -->
                                <div class="flex md:flex-col items-center md:items-end gap-3 md:gap-1.5 text-xs text-gray-400 dark:text-gray-500 font-bold border-t md:border-t-0 border-gray-100 dark:border-[#26272B] pt-3 md:pt-0">
                                    <span class="flex items-center gap-1.5">
                                        <i data-lucide="clock" class="w-3.5 h-3.5"></i>
                                        <?= date('H:i', $itemDate) ?>
                                    </span>
                                    <?php if(!empty($item['participant_count'])): ?>
                                        <span class="flex items-center gap-1.5">
                                            <i data-lucide="users" class="w-3.5 h-3.5"></i>
                                            <?= $item['participant_count'] ?> <?= $isSchedule ? 'escalados' : 'participantes' ?>
                                        </span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </a>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    
    <!-- MODO CALENDÁRIO / SEMANA GRID -->
    <?php else: ?>
        <div class="bg-white dark:bg-[#18191D] border border-gray-100 dark:border-[#26272B] rounded-[2px] p-4 shadow-sm overflow-hidden reveal-item">
            
            <!-- Dias da Semana Header (Brutalista Sharp) -->
            <div class="grid grid-cols-7 text-center border-b border-gray-100 dark:border-[#26272B] pb-3 mb-3">
                <div class="text-[9px] font-black uppercase tracking-widest text-[#2E7EED]">Dom</div>
                <div class="text-[9px] font-black uppercase tracking-widest text-gray-400 dark:text-gray-500">Seg</div>
                <div class="text-[9px] font-black uppercase tracking-widest text-gray-400 dark:text-gray-500">Ter</div>
                <div class="text-[9px] font-black uppercase tracking-widest text-gray-400 dark:text-gray-500">Qua</div>
                <div class="text-[9px] font-black uppercase tracking-widest text-gray-400 dark:text-gray-500">Qui</div>
                <div class="text-[9px] font-black uppercase tracking-widest text-gray-400 dark:text-gray-500">Sex</div>
                <div class="text-[9px] font-black uppercase tracking-widest text-gray-400 dark:text-gray-500">Sáb</div>
            </div>
            
            <!-- Corpo do Calendário (Grid afiado) -->
            <div class="grid grid-cols-7 gap-1.5">
                <?php
                if ($viewMode === 'month') {
                    $firstDayMonth = date('w', strtotime($startDate));
                    $daysInMonth = date('t', strtotime($startDate));
                    
                    // Dias do mês anterior (padding)
                    for($i=0; $i < $firstDayMonth; $i++) {
                        echo '<div class="aspect-square bg-gray-50/20 dark:bg-[#121316]/10 rounded-[2px] border border-gray-100/10 dark:border-[#26272B]/20 opacity-25"></div>';
                    }
                    
                    // Dias do mês atual
                    for($d=1; $d <= $daysInMonth; $d++) {
                        $currentDate = sprintf('%s-%02d', date('Y-m', strtotime($startDate)), $d);
                        $isToday = ($currentDate === date('Y-m-d'));
                        $dayEvents = $eventsByDay[$currentDate] ?? [];
                        
                        $todayClass = $isToday 
                            ? 'border-[#2E7EED] bg-[#2E7EED]/5 dark:bg-[#2E7EED]/5 ring-1 ring-[#2E7EED]' 
                            : 'border-gray-100 dark:border-[#26272B] bg-white dark:bg-[#18191D]';
                        
                        echo '<div class="min-h-[75px] sm:min-h-[105px] border rounded-[2px] p-1.5 flex flex-col justify-between transition-all duration-200 hover:bg-gray-50 dark:hover:bg-[#121316] hover:border-gray-300 dark:hover:border-[#26272B] '.$todayClass.'">';
                        
                        // Número do dia
                        $numColor = $isToday 
                            ? 'text-white font-black bg-[#2E7EED] shadow-sm' 
                            : 'text-gray-800 dark:text-gray-200 font-extrabold';
                        echo '<div class="text-[10px] sm:text-xs '.$numColor.' w-5 h-5 rounded-none flex items-center justify-center font-outfit">'.$d.'</div>';
                        
                        // Lista de Chips de Eventos
                        echo '<div class="space-y-1 mt-1 sm:mt-2 overflow-y-auto max-h-[48px] sm:max-h-[70px] pr-0.5 custom-scrollbar">';
                        foreach($dayEvents as $evt) {
                            $isSchedule = $evt['source'] === 'schedule';
                            $chipBg = $isSchedule 
                                ? 'bg-[#2E7EED]/10 text-[#2E7EED] border-[#2E7EED]/15 hover:bg-[#2E7EED]/20 dark:border-[#2E7EED]/20' 
                                : 'bg-amber-500/10 text-amber-600 dark:text-amber-500 border-amber-500/15 hover:bg-amber-500/20 dark:border-amber-500/20';
                            $iconName = $isSchedule ? 'music' : 'calendar';
                            $url = $isSchedule ? 'escala_detalhe.php?id='.$evt['id'] : 'evento_detalhe.php?id='.$evt['id'];
                            
                            echo "<div onclick=\"window.location='$url'\" class='event-chip flex items-center gap-1 px-1 py-0.5 rounded-[2px] text-[8px] font-bold border truncate transition-colors cursor-pointer $chipBg' title=\"".htmlspecialchars($evt['title'])."\">";
                            echo "<i data-lucide='$iconName' class='w-2 h-2 flex-shrink-0'></i>";
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
                            echo '<div class="aspect-square bg-gray-50/20 dark:bg-[#121316]/10 rounded-[2px] border border-gray-100/10 dark:border-[#26272B]/20 opacity-25"></div>';
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
                        
                        $todayClass = $isToday 
                            ? 'border-[#2E7EED] bg-[#2E7EED]/5 dark:bg-[#2E7EED]/5 ring-1 ring-[#2E7EED]' 
                            : 'border-gray-100 dark:border-[#26272B] bg-white dark:bg-[#18191D]';
                        
                        echo '<div class="min-h-[140px] border rounded-[2px] p-2 flex flex-col justify-between transition-all duration-200 hover:bg-gray-50 dark:hover:bg-[#121316] '.$todayClass.'">';
                        
                        $numColor = $isToday 
                            ? 'text-white font-black bg-[#2E7EED] shadow-sm' 
                            : 'text-gray-800 dark:text-gray-200 font-extrabold';
                        echo '<div class="text-xs '.$numColor.' w-6 h-6 rounded-none flex items-center justify-center font-outfit">'.$dayNum.'</div>';
                        
                        echo '<div class="space-y-1.5 mt-2 overflow-y-auto max-h-[100px] custom-scrollbar">';
                        foreach($dayEvents as $evt) {
                            $isSchedule = $evt['source'] === 'schedule';
                            $chipBg = $isSchedule 
                                ? 'bg-[#2E7EED]/10 text-[#2E7EED] border-[#2E7EED]/15 hover:bg-[#2E7EED]/20 dark:border-[#2E7EED]/20' 
                                : 'bg-amber-500/10 text-amber-600 dark:text-amber-500 border-amber-500/15 hover:bg-amber-500/20 dark:border-amber-500/20';
                            $iconName = $isSchedule ? 'music' : 'calendar';
                            $url = $isSchedule ? 'escala_detalhe.php?id='.$evt['id'] : 'evento_detalhe.php?id='.$evt['id'];
                            $startTime = date('H:i', strtotime($evt['start_datetime']));
                            
                            echo "<div onclick=\"window.location='$url'\" class='event-chip flex flex-col gap-0.5 p-1.5 rounded-[2px] text-[8px] font-bold border transition-colors cursor-pointer $chipBg' title=\"".htmlspecialchars($evt['title'])."\">";
                            echo "<div class='text-[8px] font-extrabold opacity-75'>$startTime</div>";
                            echo "<div class='flex items-center gap-1 mt-0.5'>";
                            echo "<i data-lucide='$iconName' class='w-2 h-2 flex-shrink-0'></i>";
                            echo "<span class='truncate leading-none text-[8px]'>".htmlspecialchars($evt['title'])."</span>";
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

<!-- BOTÃO ADICIONAR (FAB Premium - Sacred Minimalist) -->
<?php if (isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin'): ?>
<a href="evento_adicionar.php" class="fixed bottom-8 right-8 z-40 bg-[#2E7EED] hover:bg-[#1A6FD6] text-white rounded-[2px] p-4 shadow-lg transition-all duration-300 hover:scale-105 active:scale-[0.97] will-change-transform flex items-center justify-center border border-white/10 group cursor-pointer" title="Adicionar Evento">
    <i data-lucide="plus" class="w-5 h-5 group-hover:rotate-90 transition-transform duration-300"></i>
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
    
    const calendarWrapper = document.querySelector('.grid');
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