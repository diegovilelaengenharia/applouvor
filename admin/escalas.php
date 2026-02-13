<?php
// admin/escalas.php
require_once '../includes/db.php';
require_once '../includes/layout.php';

// --- Lógica de Filtros ---
$filterMine = isset($_GET['mine']) && $_GET['mine'] == '1';
$filterType = $_GET['type'] ?? '';

// ID do usuário logado (Assumindo sessão ou hardcoded 1 para dev se não tiver sessão ainda)
$loggedUserId = $_SESSION['user_id'] ?? 1;

// Construção da Query FUTURA
$sqlFuture = "SELECT DISTINCT s.* FROM schedules s ";
if ($filterMine) {
    $sqlFuture .= " JOIN schedule_users su ON su.schedule_id = s.id ";
}
$sqlFuture .= " WHERE s.event_date >= CURDATE() ";

if ($filterMine) {
    $sqlFuture .= " AND su.user_id = :userId ";
}
if (!empty($filterType)) {
    $sqlFuture .= " AND s.event_type = :eventType ";
}
$sqlFuture .= " ORDER BY s.event_date ASC";

// Construção da Query PASSADA
$sqlPast = "SELECT DISTINCT s.* FROM schedules s ";
if ($filterMine) {
    $sqlPast .= " JOIN schedule_users su ON su.schedule_id = s.id ";
}
$sqlPast .= " WHERE s.event_date < CURDATE() ";

if ($filterMine) {
    $sqlPast .= " AND su.user_id = :userId ";
}
if (!empty($filterType)) {
    $sqlPast .= " AND s.event_type = :eventType ";
}
$sqlPast .= " ORDER BY s.event_date DESC LIMIT 20";


try {
    // Executar Futuras
    $stmtFuture = $pdo->prepare($sqlFuture);
    if ($filterMine) $stmtFuture->bindValue(':userId', $loggedUserId);
    if (!empty($filterType)) $stmtFuture->bindValue(':eventType', $filterType);
    $stmtFuture->execute();
    $futureSchedules = $stmtFuture->fetchAll(PDO::FETCH_ASSOC);

    // Executar Passadas
    $stmtPast = $pdo->prepare($sqlPast);
    if ($filterMine) $stmtPast->bindValue(':userId', $loggedUserId);
    if (!empty($filterType)) $stmtPast->bindValue(':eventType', $filterType);
    $stmtPast->execute();
    $pastSchedules = $stmtPast->fetchAll(PDO::FETCH_ASSOC);

    // --- Eager Loading (Optimization) ---
    $allSchedules = array_merge($futureSchedules, $pastSchedules);
    $scheduleIds = array_column($allSchedules, 'id');
    
    $participantsMap = [];
    $songCountsMap = [];
    $mySchedulesMap = [];

    if (!empty($scheduleIds)) {
        $inQuery = implode(',', array_fill(0, count($scheduleIds), '?'));

        // 1. Buscando participantes
        $sqlParts = "
            SELECT su.schedule_id, u.id, u.name, u.photo, u.avatar_color 
            FROM schedule_users su 
            JOIN users u ON su.user_id = u.id 
            WHERE su.schedule_id IN ($inQuery)
            ORDER BY su.schedule_id, u.name
        ";
        $stmtParts = $pdo->prepare($sqlParts);
        $stmtParts->execute($scheduleIds);
        while ($row = $stmtParts->fetch(PDO::FETCH_ASSOC)) {
            $participantsMap[$row['schedule_id']][] = [
                'id' => $row['id'],
                'name' => $row['name'],
                'photo' => $row['photo'],
                'avatar_color' => $row['avatar_color']
            ];
            
            if ($row['id'] == $loggedUserId) {
                $mySchedulesMap[$row['schedule_id']] = true;
            }
        }

        // 2. Buscando contagem de músicas
        $sqlSongs = "
            SELECT schedule_id, COUNT(*) as total 
            FROM schedule_songs 
            WHERE schedule_id IN ($inQuery) 
            GROUP BY schedule_id
        ";
        $stmtSongs = $pdo->prepare($sqlSongs);
        $stmtSongs->execute($scheduleIds);
        while ($row = $stmtSongs->fetch(PDO::FETCH_ASSOC)) {
            $songCountsMap[$row['schedule_id']] = $row['total'];
        }
    }

} catch (PDOException $e) {
    die("Erro ao carregar escalas: " . $e->getMessage());
}

// Helpers
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
<link rel="stylesheet" href="../assets/css/pages/escalas.css">

<?php
renderPageHeader('Escalas', 'Louvor PIB Oliveira');
?>

<!-- TOP CONTROLS -->
<div class="page-controls-container">

    <!-- Toggle Central -->
    <div class="toggle-switch-container">
        <button onclick="switchTab('future')" id="btn-future" class="btn-toggle active">Próximas</button>
        <button onclick="switchTab('past')" id="btn-past" class="btn-toggle">Anteriores</button>
    </div>

    <!-- Right Controls -->
    <div class="controls-right">
        <?php if (isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin'): ?>
        <a href="escala_adicionar.php" class="btn-control-icon btn-add-new" title="Nova Escala">
            <i data-lucide="plus"></i>
        </a>
        <?php endif; ?>

        <button onclick="openSheet('filterSheet')" class="btn-control-icon btn-filter-trigger">
            <i data-lucide="filter" width="18"></i>
            <?php if($activeFilters > 0): ?>
            <span class="badge-dot"></span>
            <?php endif; ?>
        </button>
    </div>
</div>

<!-- Container Grid -->
<div>

    <!-- TAB: FUTURAS -->
    <div id="tab-future">
        <?php if (empty($futureSchedules)): ?>
            <div class="empty-timeline">
                <div class="empty-icon-circle">
                    <i data-lucide="calendar" style="width: 32px;"></i>
                </div>
                <h3 class="text-secondary mb-2">Tudo tranquilo por aqui</h3>
                <p class="text-tertiary">Nenhuma escala agendada para os próximos dias.</p>
            </div>
        <?php else: ?>

            <div id="view-timeline" class="scales-vertical-list">
                <?php 
                $currentMonth = '';
                foreach ($futureSchedules as $schedule):
                    $date = new DateTime($schedule['event_date']);
                    $monthYear = getMonthName($date->format('n')) . ' ' . $date->format('Y');
                    
                    // MONTH SEPARATOR
                    if ($monthYear !== $currentMonth) {
                        echo '<h3 class="month-divider">' . strtoupper($monthYear) . '</h3>';
                        $currentMonth = $monthYear;
                    }

                    $isToday = $date->format('Y-m-d') === date('Y-m-d');
                    $type = mb_strtolower($schedule['event_type']);
                    
                    // Theme Logic
                    $accentColor = 'var(--slate-600)';
                    $statusBadgeBg = 'var(--slate-100)';
                    $statusBadgeColor = 'var(--slate-600)';
                    
                    if (strpos($type, 'ensaio') !== false) {
                        $accentColor = 'var(--amber-500)';
                        $statusBadgeBg = 'var(--amber-50)';
                        $statusBadgeColor = 'var(--amber-700)';
                    } elseif (strpos($type, 'jovem') !== false) {
                        $accentColor = '#8b5cf6';
                        $statusBadgeBg = '#f5f3ff';
                        $statusBadgeColor = '#7c3aed';
                    } elseif (strpos($type, 'especial') !== false) {
                        $accentColor = 'var(--red-500)';
                        $statusBadgeBg = 'var(--red-50)';
                        $statusBadgeColor = 'var(--red-700)';
                    }

                    // Dias Restantes
                    $today = new DateTime('today');
                    $daysUntil = $today->diff($date)->days;
                    
                    // Dados
                    $allParticipants = $participantsMap[$schedule['id']] ?? [];
                    $participants = array_slice($allParticipants, 0, 5);
                    $extraCount = max(0, count($allParticipants) - 5);
                    $songsCount = $songCountsMap[$schedule['id']] ?? 0;
                    $isMine = $mySchedulesMap[$schedule['id']] ?? false;
                ?>

                    <!-- CARD ITEM -->
                    <a href="escala_detalhe.php?id=<?= $schedule['id'] ?>" class="scale-card" style="--card-accent-color: <?= $accentColor ?>">
                        
                        <div class="scale-card-header">
                            <div class="date-badge-modern">
                                <span class="date-day"><?= $date->format('d') ?></span>
                                <span class="date-month"><?= strtoupper(strftime('%b', $date->getTimestamp())) ?></span>
                            </div>
                            
                            <div style="display: flex; flex-direction: column; align-items: flex-end; gap: 6px;">
                                <?php if($isToday): ?>
                                    <span class="status-badge-pill" style="background: var(--red-100); color: var(--red-600);">Hoje</span>
                                <?php else: ?>
                                    <span class="status-badge-pill" style="background: <?= $statusBadgeBg ?>; color: <?= $statusBadgeColor ?>;">
                                        <?= $daysUntil == 1 ? 'Amanhã' : 'Em ' . $daysUntil . ' dias' ?>
                                    </span>
                                <?php endif; ?>
                                
                                <?php if($isMine): ?>
                                    <span style="font-size: 0.7rem; font-weight: 700; color: var(--blue-600); display: flex; align-items: center; gap: 4px;">
                                        <i data-lucide="check-circle-2" width="12"></i> Você participa
                                    </span>
                                <?php endif; ?>
                            </div>
                        </div>

                        <div class="scale-card-body">
                            <h3 class="event-title"><?= htmlspecialchars($schedule['event_type']) ?></h3>
                            
                            <div class="event-meta-row">
                                <span class="meta-item">
                                    <i data-lucide="clock" width="14"></i>
                                    <?= isset($schedule['event_time']) ? substr($schedule['event_time'], 0, 5) : '19:00' ?>
                                </span>
                                <?php if($songsCount > 0): ?>
                                <span class="meta-item meta-music-count">
                                    <i data-lucide="music" width="12"></i> <?= $songsCount ?>
                                </span>
                                <?php endif; ?>
                            </div>
                            
                            <?php if(!empty($schedule['notes'])): ?>
                                <div style="font-size: 0.8rem; color: var(--text-tertiary); margin-top: 4px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                                    <?= htmlspecialchars($schedule['notes']) ?>
                                </div>
                            <?php endif; ?>
                        </div>

                        <div class="scale-card-footer">
                            <div class="avatar-stack">
                                <?php if(empty($participants)): ?>
                                    <span style="font-size: 0.8rem; color: var(--text-tertiary);">Nenhum participante</span>
                                <?php else: ?>
                                    <?php foreach ($participants as $p):
                                        $photoUrl = $p['photo'] ? '../assets/img/' . $p['photo'] : '';
                                    ?>
                                        <div class="avatar-stack-item text-xs" style="background: <?= $p['avatar_color'] ?: 'var(--slate-400)' ?>;" title="<?= htmlspecialchars($p['name']) ?>">
                                            <?php if ($photoUrl): ?>
                                                <img src="<?= htmlspecialchars($photoUrl) ?>" style="width: 100%; height: 100%; object-fit: cover; border-radius: 50%;">
                                            <?php else: ?>
                                                <?= strtoupper(substr($p['name'], 0, 1)) ?>
                                            <?php endif; ?>
                                        </div>
                                    <?php endforeach; ?>
                                    <?php if ($extraCount > 0): ?>
                                        <div class="avatar-stack-more">+<?= $extraCount ?></div>
                                    <?php endif; ?>
                                <?php endif; ?>
                            </div>
                            
                            <div class="btn-arrow-action">
                                <i data-lucide="arrow-right" width="16"></i>
                            </div>
                        </div>

                    </a>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- TAB: ANTERIORES -->
    <div id="tab-past" style="display: none;">
        <?php if (empty($pastSchedules)): ?>
            <div class="empty-timeline">
                <p class="text-muted">Nenhum histórico recente.</p>
            </div>
        <?php else: ?>
            <div class="scales-vertical-list">
                <?php 
                $currentMonth = '';
                foreach ($pastSchedules as $schedule):
                    $date = new DateTime($schedule['event_date']);
                    $monthYear = getMonthName($date->format('n')) . ' ' . $date->format('Y');

                     // MONTH SEPARATOR
                    if ($monthYear !== $currentMonth) {
                        echo '<h3 class="month-divider">' . strtoupper($monthYear) . '</h3>';
                        $currentMonth = $monthYear;
                    }

                    $type = mb_strtolower($schedule['event_type']);
                    
                    // Theme Logic
                    $accentColor = 'var(--text-tertiary)';
                    
                    // Dados
                    $songsCount = $songCountsMap[$schedule['id']] ?? 0;
                    $allParticipants = $participantsMap[$schedule['id']] ?? [];
                    // Mostramos menos avatares no passado para limpar
                    $participants = array_slice($allParticipants, 0, 3);
                    $extraCount = max(0, count($allParticipants) - 3);

                    // Dias passados
                    $today = new DateTime('today');
                    $daysAgo = $date->diff($today)->days;
                ?>
                    <!-- CARD ITEM (PASSADO) -->
                    <a href="escala_detalhe.php?id=<?= $schedule['id'] ?>" class="scale-card past-card">
                        
                        <div class="scale-card-header">
                            <div class="date-badge-modern" style="opacity: 0.7;">
                                <span class="date-day"><?= $date->format('d') ?></span>
                                <span class="date-month"><?= strtoupper(strftime('%b', $date->getTimestamp())) ?></span>
                            </div>
                            
                            <span class="status-badge-pill" style="background: var(--slate-100); color: var(--text-muted); font-size: 0.65rem;">
                                <?= $daysAgo == 1 ? 'Ontem' : $daysAgo . ' dias atrás' ?>
                            </span>
                        </div>

                        <div class="scale-card-body">
                            <h3 class="event-title" style="font-size: 1rem; color: var(--text-secondary);"><?= htmlspecialchars($schedule['event_type']) ?></h3>
                             <div class="event-meta-row">
                                <?php if($songsCount > 0): ?>
                                <span class="meta-item">
                                    <i data-lucide="music" width="12"></i> <?= $songsCount ?> músicas
                                </span>
                                <?php endif; ?>
                            </div>
                        </div>

                        <div class="scale-card-footer" style="padding-top: 12px;">
                             <div class="avatar-stack">
                                 <?php foreach ($participants as $p): 
                                     $photoUrl = $p['photo'] ? '../assets/img/' . $p['photo'] : '';
                                 ?>
                                     <div class="avatar-stack-item" style="width: 24px; height: 24px; font-size: 0.6rem; opacity: 0.8; background: <?= $p['avatar_color'] ?: '#ccc' ?>;">
                                        <?php if ($photoUrl): ?>
                                            <img src="<?= htmlspecialchars($photoUrl) ?>" style="width: 100%; height: 100%; object-fit: cover; border-radius: 50%;">
                                        <?php else: ?>
                                            <?= strtoupper(substr($p['name'], 0, 1)) ?>
                                        <?php endif; ?>
                                     </div>
                                 <?php endforeach; ?>
                             </div>
                             <div class="btn-arrow-action" style="width: 28px; height: 28px;">
                                <i data-lucide="chevron-right" width="14"></i>
                            </div>
                        </div>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- FILTER SHEET (Mantido igual) -->
<div id="filterSheet" class="filter-sheet-container">
    <div class="filter-sheet-overlay" onclick="closeSheet('filterSheet')"></div>
    <div class="filter-sheet-modal">
        <div class="sheet-header-row">
            <h3>Filtrar Escalas</h3>
            <button onclick="closeSheet('filterSheet')" class="btn-close-sheet">
                <i data-lucide="x" width="24"></i>
            </button>
        </div>

        <form method="GET" action="escalas.php">
            <!-- Toggle Minhas Escalas -->
            <label class="filter-toggle-row">
                <span style="font-weight: 600; color: var(--text-secondary);">Apenas em que participo</span>
                <input type="checkbox" name="mine" value="1" <?= $filterMine ? 'checked' : '' ?> class="filter-toggle-checkbox">
            </label>

            <!-- Tipo de Evento -->
            <div class="radio-group-container">
                <label class="radio-group-label">Tipo de Evento</label>
                <div class="radio-options-wrapper">
                    <?php
                    $types = ['Culto Domingo a Noite', 'Ensaio', 'Culto Jovem', 'Especial'];
                    foreach ($types as $t):
                        $active = $filterType === $t;
                    ?>
                        <label class="radio-option-label">
                            <input type="radio" name="type" value="<?= $t ?>" <?= $active ? 'checked' : '' ?> style="display: none;">
                            <div class="radio-pill <?= $active ? 'active' : '' ?>">
                                <?= $t ?>
                            </div>
                        </label>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Botões -->
            <div class="filter-actions">
                <a href="escalas.php" class="btn-reset">Limpar</a>
                <button type="submit" class="btn-apply">Aplicar Filtros</button>
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
            tabFuture.style.display = 'block';
            tabPast.style.display = 'none';

            btnFuture.classList.add('active');
            btnPast.classList.remove('active');
        } else {
            tabFuture.style.display = 'none';
            tabPast.style.display = 'block';

            btnFuture.classList.remove('active');
            btnPast.classList.add('active');
        }
    }

    // Modal Logic (Standardized)
    function openSheet(id) {
        const modal = document.getElementById(id);
        if (modal) {
            modal.style.display = 'block'; // Ensure visibility
            requestAnimationFrame(() => {
                modal.classList.add('active');
            });
            document.body.style.overflow = 'hidden'; 
        }
    }

    function closeSheet(id) {
        const modal = document.getElementById(id);
        if (modal) {
            modal.classList.remove('active');
            setTimeout(() => {
                 modal.style.display = 'none';
            }, 300); // Wait for transition
            document.body.style.overflow = '';
        }
    }
</script>

<?php renderAppFooter(); ?>