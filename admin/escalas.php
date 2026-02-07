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
} catch (PDOException $e) {
    die("Erro ao carregar escalas: " . $e->getMessage());
}

// Contar filtros ativos para Badge
$activeFilters = 0;
if ($filterMine) $activeFilters++;
if (!empty($filterType)) $activeFilters++;

renderAppHeader('Escalas');
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
        <!-- Botão Adicionar Escala (Admin Only) -->
        <?php if (isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin'): ?>
        <a href="escala_adicionar.php" class="btn-control-icon btn-add-new">
            <i data-lucide="plus"></i>
        </a>
        <?php endif; ?>

        <!-- Filter Button -->
        <button onclick="openSheet('filterSheet')" class="btn-control-icon btn-filter-trigger">
            <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <polygon points="22 3 2 3 10 12.46 10 19 14 21 14 12.46 22 3" />
            </svg>
        </button>
    </div>
</div>

<!-- Container Timeline -->
<div>

    <!-- TAB: FUTURAS -->
    <div id="tab-future">
        <?php if (empty($futureSchedules)): ?>
            <div class="empty-timeline">
                <div class="empty-icon-circle">
                    <i data-lucide="calendar" style="width: 32px;"></i>
                </div>
                <h3 style="color: var(--text-secondary); margin-bottom: 8px;">Tudo tranquilo por aqui</h3>
                <p style="color: var(--text-tertiary);">Nenhuma escala agendada para os próximos dias.</p>
            </div>
        <?php else: ?>

            <div id="view-timeline" class="timeline-container">
                <?php foreach ($futureSchedules as $schedule):
                    $date = new DateTime($schedule['event_date']);
                    $isToday = $date->format('Y-m-d') === date('Y-m-d');

                    // Definir Tema de Cor (Moderate Palette)
                    $type = mb_strtolower($schedule['event_type']);
                    // Valores para style inline apenas onde variável não alcança ou classes específicas
                    if (strpos($type, 'domingo') !== false) {
                        $themeColor = 'var(--slate-600)'; 
                        $themeLight = 'var(--slate-100)'; 
                    } elseif (strpos($type, 'ensaio') !== false) {
                        $themeColor = 'var(--amber-500)'; 
                        $themeLight = 'var(--amber-50)'; 
                    } elseif (strpos($type, 'jovem') !== false) {
                        $themeColor = '#8b5cf6'; // Violet 500
                        $themeLight = '#f5f3ff'; // Violet 50
                    } elseif (strpos($type, 'especial') !== false) {
                        $themeColor = 'var(--red-500)'; 
                        $themeLight = 'var(--red-50)'; 
                    } else {
                        $themeColor = 'var(--slate-600)';
                        $themeLight = 'var(--slate-100)';
                    }

                    if ($isToday) {
                        $themeColor = 'var(--blue-600)';
                        $themeLight = 'var(--blue-50)';
                    }

                    // Buscar participantes (Top 5)
                    $stmtUsers = $pdo->prepare("
                            SELECT u.name, u.photo, u.avatar_color 
                            FROM schedule_users su 
                            JOIN users u ON su.user_id = u.id 
                            WHERE su.schedule_id = ? 
                            LIMIT 5
                        ");
                    $stmtUsers->execute([$schedule['id']]);
                    $participants = $stmtUsers->fetchAll(PDO::FETCH_ASSOC);

                    // Contar total participantes
                    $stmtCount = $pdo->prepare("SELECT COUNT(*) FROM schedule_users WHERE schedule_id = ?");
                    $stmtCount->execute([$schedule['id']]);
                    $totalParticipants = $stmtCount->fetchColumn();
                    $extraCount = max(0, $totalParticipants - 5);

                    // Buscar ausências
                    $stmtAbsences = $pdo->prepare("
                        SELECT 1
                        FROM user_unavailability ua
                        WHERE :event_date BETWEEN ua.start_date AND ua.end_date
                    ");
                    $stmtAbsences->execute(['event_date' => $schedule['event_date']]);
                    $hasAbsences = $stmtAbsences->rowCount() > 0;
                    
                    // Calcular dias até o evento
                    $today = new DateTime('today');
                    $daysUntil = $today->diff($date)->days;
                    
                    // Card Background
                    $cardBg = $isToday ? 'var(--blue-50)' : 'var(--bg-surface)';
                    $borderColor = $isToday ? 'var(--blue-200)' : 'var(--border-subtle)';
                ?>

                    <!-- Timeline Card -->
                    <a href="escala_detalhe.php?id=<?= $schedule['id'] ?>" class="timeline-card" style="border-left-color: <?= $themeColor ?>; border-color: <?= $borderColor ?>; background: <?= $cardBg ?>;">
                        <div class="card-content-wrapper">

                            <!-- Date Box -->
                            <div class="date-box" style="background: <?= $themeLight ?>; color: <?= $themeColor ?>; border-color: <?= $themeColor ?>20;">
                                <div class="day"><?= $date->format('d') ?></div>
                                <div class="month"><?= strtoupper(strftime('%b', $date->getTimestamp())) ?></div>
                            </div>

                            <!-- Event Details -->
                            <div class="event-details-col">
                                <div class="event-header">
                                    <h3 class="event-title"><?= htmlspecialchars($schedule['event_type']) ?></h3>
                                    <?php if ($isToday): ?>
                                        <span class="event-badge badge-today">HOJE</span>
                                    <?php else: ?>
                                        <span class="event-badge badge-upcoming"><?= $daysUntil == 1 ? 'Amanhã' : 'em ' . $daysUntil . ' dias' ?></span>
                                    <?php endif; ?>
                                </div>

                                <div class="meta-row">
                                    <div class="meta-item">
                                        <i data-lucide="clock" width="14"></i> 
                                        <?= isset($schedule['event_time']) ? substr($schedule['event_time'], 0, 5) : '19:00' ?>
                                    </div>
                                    <?php if ($totalParticipants > 0): ?>
                                        <div class="meta-item">
                                            <i data-lucide="users" width="14"></i> 
                                            <?= $totalParticipants ?>
                                        </div>
                                    <?php endif; ?>
                                </div>

                                <!-- Avatares -->
                                <?php if (!empty($participants)): ?>
                                    <div class="avatar-stack">
                                        <?php foreach ($participants as $i => $p):
                                            $zIndex = 10 - $i;
                                            $photoUrl = $p['photo'] ? '../assets/img/' . $p['photo'] : '';
                                        ?>
                                            <div class="avatar-stack-item" style="background: <?= $p['avatar_color'] ?: $themeColor ?>; z-index: <?= $zIndex ?>;">
                                                <?php if ($photoUrl): ?>
                                                    <img src="<?= htmlspecialchars($photoUrl) ?>" style="width: 100%; height: 100%; object-fit: cover;">
                                                <?php else: ?>
                                                    <?= strtoupper(substr($p['name'], 0, 1)) ?>
                                                <?php endif; ?>
                                            </div>
                                        <?php endforeach; ?>
                                        <?php if ($extraCount > 0): ?>
                                            <div class="avatar-stack-more">+<?= $extraCount ?></div>
                                        <?php endif; ?>
                                    </div>
                                <?php else: ?>
                                    <span style="font-size: 0.8rem; color: var(--text-tertiary); font-style: italic;">Equipe não definida</span>
                                <?php endif; ?>

                                <!-- Badge de Ausências -->
                                <?php if ($hasAbsences): ?>
                                <div class="absence-badge">
                                    <i data-lucide="alert-circle" width="12"></i>
                                    Ausências
                                </div>
                                <?php endif; ?>
                            </div>

                            <!-- Arrow -->
                            <div class="card-arrow">
                                <i data-lucide="chevron-right" width="20"></i>
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
                <p style="color: var(--text-muted);">Nenhum histórico recente.</p>
            </div>
        <?php else: ?>
            <div id="view-timeline-past" class="timeline-container">
                <?php foreach ($pastSchedules as $schedule):
                    $date = new DateTime($schedule['event_date']);
                    $today = new DateTime('today');
                    $daysAgo = $date->diff($today)->days;
                    
                    $themeColor = 'var(--text-tertiary)'; 
                    $themeLight = 'var(--bg-surface-active)'; 
                    $cardBg = 'var(--bg-app)';
                    
                    // Count participants
                    $stmtCountPast = $pdo->prepare("SELECT COUNT(*) FROM schedule_users WHERE schedule_id = ?");
                    $stmtCountPast->execute([$schedule['id']]);
                    $totalParticipantsPast = $stmtCountPast->fetchColumn();
                ?>
                    <!-- Card Passado -->
                    <a href="escala_detalhe.php?id=<?= $schedule['id'] ?>" class="timeline-card-past">
                        <div class="card-content-wrapper">
                            
                            <div class="date-box" style="background: <?= $themeLight ?>; color: <?= $themeColor ?>; border-color: transparent;">
                                <div class="day"><?= $date->format('d') ?></div>
                                <div class="month"><?= strtoupper(strftime('%b', $date->getTimestamp())) ?></div>
                            </div>

                            <div class="event-details-col">
                                <div class="event-header">
                                    <h3 class="event-title" style="color: var(--text-secondary);"><?= htmlspecialchars($schedule['event_type']) ?></h3>
                                    <span class="event-badge" style="background: var(--bg-surface-active); color: var(--text-secondary);"><?= $daysAgo == 1 ? 'Ontem' : 'há ' . $daysAgo . ' dia(s)' ?></span>
                                </div>

                                <div class="meta-row">
                                    <div class="meta-item">
                                        <i data-lucide="clock" width="14"></i> <?= isset($schedule['event_time']) ? substr($schedule['event_time'], 0, 5) : '19:00' ?>
                                    </div>
                                    <?php if ($totalParticipantsPast > 0): ?>
                                    <div class="meta-item">
                                        <i data-lucide="users" width="14"></i> <?= $totalParticipantsPast ?>
                                    </div>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <div class="card-arrow">
                                <i data-lucide="chevron-right" width="20"></i>
                            </div>
                        </div>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- FILTER SHEET -->
<div id="filterSheet" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; z-index: 2000;">
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
                <input type="checkbox" name="mine" value="1" <?= $filterMine ? 'checked' : '' ?> style="transform: scale(1.3); accent-color: var(--slate-800);">
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
                            <div class="radio-pill">
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

    // Sheet Modal Logic
    function openSheet(id) {
        const sheet = document.getElementById(id);
        if (sheet) {
            sheet.style.display = 'block';
            document.body.style.overflow = 'hidden'; 
        }
    }

    function closeSheet(id) {
        const sheet = document.getElementById(id);
        if (sheet) {
            sheet.style.display = 'none';
            document.body.style.overflow = '';
        }
    }
</script>

<?php renderAppFooter(); ?>