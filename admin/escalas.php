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
require_once '../includes/classes/ScheduleRepository.php';
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
<link rel="stylesheet" href="../assets/css/pages/escalas.css">

<?php
renderPageHeader('Escalas', 'Louvor PIB Oliveira');
?>

<!-- TOP CONTROLS -->
<div class="page-controls-container">
    <div class="toggle-switch-container">
        <button onclick="switchTab('future')" id="btn-future" class="interactive-scale btn-toggle active">Próximas</button>
        <button onclick="switchTab('past')" id="btn-past" class="interactive-scale btn-toggle">Anteriores</button>
    </div>

    <div class="controls-right">
        <?php if (isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin'): ?>
        <a href="escala_adicionar.php" class="interactive-scale btn-control-icon btn-add-new" title="Nova Escala">
            <i data-lucide="plus"></i>
        </a>
        <?php endif; ?>

        <button onclick="openSheet('filterSheet')" class="interactive-scale btn-control-icon btn-filter-trigger">
            <i data-lucide="filter" width="18"></i>
            <?php if($activeFilters > 0): ?>
            <span class="badge-dot"></span>
            <?php endif; ?>
        </button>
    </div>
</div>

    <!-- Container Principal do Conteúdo -->
    <div class="scales-wrapper">

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

            <div id="view-timeline" class="scales-vertical-list" style="display: flex; flex-direction: column; gap: var(--space-md); padding-bottom: 40px;">
                <?php 
                $currentMonth = '';
                $delay = 0.1;
                foreach ($futureSchedules as $schedule):
                    $date = new DateTime($schedule['event_date']);
                    $monthYear = getMonthName($date->format('n')) . ' ' . $date->format('Y');
                    
                    // Month Divider
                    if ($monthYear !== $currentMonth):
                        $currentMonth = $monthYear;
                ?>
                        <div class="reveal-item" style="margin: var(--space-md) 0 var(--space-xs);">
                            <span style="font-size: 0.75rem; font-weight: 800; color: var(--color-text-muted); text-transform: uppercase; letter-spacing: 1px;"><?= $monthYear ?></span>
                        </div>
                <?php 
                        $delay += 0.05;
                    endif;

                    $isToday = $date->format('Y-m-d') === date('Y-m-d');
                    $songsCount = $songCountsMap[$schedule['id']] ?? 0;
                    $isMine = $mySchedulesMap[$schedule['id']] ?? false;
                    
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

                    <?php 
                    $staggerClass = 'reveal-stagger-' . min(4, max(1, (int)($delay * 10)));
                    ?>
                    <a href="escala_detalhe.php?id=<?= $schedule['id'] ?>" class="interactive-scale scale-card reveal-item <?= $staggerClass ?> <?= $isToday ? 'today' : '' ?>" style="--card-accent-color: <?= getThemeColor($schedule['event_type']) ?>;">
                        <div class="scale-card-main">
                            <!-- Bloco de Data Premium -->
                            <div class="date-box-premium">
                                <span class="date-day"><?= $date->format('d') ?></span>
                                <span class="date-month"><?= substr(getMonthName($date->format('n')), 0, 3) ?></span>
                            </div>

                            <!-- Conteúdo do Card -->
                            <div class="scale-info-col">
                                <div class="scale-card-meta">
                                    <span class="scale-time">
                                        <i data-lucide="clock" style="width: 12px; height: 12px; margin-right: 4px; display: inline-block; vertical-align: middle;"></i>
                                        <?= $isToday ? 'HOJE • ' : '' ?><?= $date->format('H:i') ?>
                                    </span>
                                    <?php if ($isMine): ?>
                                        <span class="badge-presence <?= $myStatus == 'confirmed' ? 'confirmed' : ($myStatus == 'declined' ? 'declined' : 'pending') ?>">
                                            <?= $myStatus == 'confirmed' ? 'Confirmado' : ($myStatus == 'declined' ? 'Recusado' : 'Pendente') ?>
                                        </span>
                                    <?php endif; ?>
                                </div>

                                <h3 class="event-title"><?= htmlspecialchars($schedule['event_type']) ?></h3>
                                
                                <div class="scale-footer-details">
                                    <!-- Avatares empilhados dos participantes -->
                                    <div class="avatar-stack">
                                        <?php 
                                        $parts = $participantsMap[$schedule['id']] ?? [];
                                        $count = 0;
                                        foreach ($parts as $p): 
                                            if ($count++ >= 4) break;
                                            $pAvatar = !empty($p['photo']) ? $p['photo'] : 'https://ui-avatars.com/api/?name='.urlencode($p['name']).'&background=random';
                                            if (strpos($pAvatar, 'http') === false) $pAvatar = '../' . $pAvatar;
                                        ?>
                                            <img src="<?= $pAvatar ?>" alt="<?= htmlspecialchars($p['name']) ?>" title="<?= htmlspecialchars($p['name']) ?>">
                                        <?php endforeach; ?>
                                        <?php if (count($parts) > 4): ?>
                                            <div class="avatar-stack-more">+<?= count($parts) - 4 ?></div>
                                        <?php endif; ?>
                                    </div>

                                    <!-- Indicadores e Badges de confirmação -->
                                    <div class="scale-stats-info">
                                        <span class="music-count-pill">
                                            <i data-lucide="music" style="width: 12px; height: 12px; margin-right: 3px; display: inline-block; vertical-align: middle;"></i>
                                            <?= $songsCount ?>
                                        </span>
                                        <?php if ($totalParticipants > 0): ?>
                                            <span class="confirmed-badge <?= $confirmedCount === $totalParticipants ? 'all-confirmed' : ($confirmedCount > 0 ? 'some-confirmed' : '') ?>">
                                                <?= $confirmedCount ?>/<?= $totalParticipants ?> Conf.
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>

                            <!-- Indicação visual lateral (Chevron) -->
                            <div class="scale-card-chevron">
                                <i data-lucide="chevron-right" style="width: 16px;"></i>
                            </div>
                        </div>
                    </a>
                <?php 
                    $delay += 0.05;
                endforeach; ?>
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
                    
                    // Month Divider
                    if ($monthYear !== $currentMonth) {
                        echo '<div class="month-divider-container">
                                <span class="month-divider-label">' . $monthYear . '</span>
                                <div class="month-divider-line"></div>
                              </div>';
                        $currentMonth = $monthYear;
                    }

                    $themeColor = 'var(--text-tertiary)';

                    // Dados
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
                    <div class="scale-card-wrapper">
                    <a href="escala_detalhe.php?id=<?= $schedule['id'] ?>" class="scale-card" style="--card-accent-color: <?= getThemeColor($schedule['event_type']) ?>; opacity: 0.85;">
                        <div class="scale-card-main">
                            <!-- Bloco de Data Premium -->
                            <div class="date-box-premium">
                                <span class="date-day"><?= $date->format('d') ?></span>
                                <span class="date-month"><?= substr(getMonthName($date->format('n')), 0, 3) ?></span>
                            </div>

                            <!-- Conteúdo do Card -->
                            <div class="scale-info-col">
                                <div class="scale-card-meta">
                                    <span class="scale-time">
                                        <i data-lucide="clock" style="width: 12px; height: 12px; margin-right: 4px; display: inline-block; vertical-align: middle;"></i>
                                        <?= $date->format('H:i') ?>
                                    </span>
                                </div>

                                <h3 class="event-title"><?= htmlspecialchars($schedule['event_type']) ?></h3>
                                
                                <div class="scale-footer-details">
                                    <div class="scale-stats-info">
                                        <span class="music-count-pill">
                                            <i data-lucide="music" style="width: 12px; height: 12px; margin-right: 3px; display: inline-block; vertical-align: middle;"></i>
                                            <?= $songsCount ?> Músicas
                                        </span>
                                        <?php if ($totalParticipants > 0): ?>
                                            <span class="confirmed-badge <?= $confirmedCount === $totalParticipants ? 'all-confirmed' : ($confirmedCount > 0 ? 'some-confirmed' : '') ?>">
                                                <?= $confirmedCount ?>/<?= $totalParticipants ?> Confirmados
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>

                            <!-- Indicação visual lateral (Chevron) -->
                            <div class="scale-card-chevron">
                                <i data-lucide="chevron-right" style="width: 16px;"></i>
                            </div>
                        </div>
                    </a>
                    <?php if ($_SESSION['user_role'] === 'admin'): ?>
                    <a href="registrar_faltas.php?id=<?= $schedule['id'] ?>"
                       class="btn-registrar-faltas"
                       style="display:inline-flex;align-items:center;gap:6px;font-size:0.75rem;font-weight:600;
                              color:var(--orange-500,#f97316);text-decoration:none;padding:6px 10px;
                              border:1px solid var(--orange-500,#f97316);border-radius:8px;margin-top:6px;">
                      <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24"
                           fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                           stroke-linejoin="round"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/>
                        <circle cx="9" cy="7" r="4"/><line x1="17" y1="8" x2="23" y2="8"/>
                        <line x1="20" y1="5" x2="20" y2="11"/></svg>
                      Registrar Faltas
                    </a>
                    <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
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