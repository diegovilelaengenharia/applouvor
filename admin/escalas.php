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

renderAppHeader('Escalas', 'index.php');
?>
<style>
    /* FORÇANDO ESTILOS SMART FLEX (Dedup de Cache) */
    .card-content-wrapper.smart-flex {
        display: flex !important;
        flex-wrap: wrap !important;
        align-items: center !important;
        gap: 16px !important;
    }
    @media (max-width: 600px) {
        .card-content-wrapper.smart-flex {
            flex-direction: column !important;
            align-items: flex-start !important;
            gap: 10px !important;
        }
        .card-content-wrapper.smart-flex .card-people-col {
            width: 100% !important;
            justify-content: flex-end !important;
            margin-top: 4px !important;
        }
    }
    .smart-flex .card-people-col {
        display: flex !important;
        align-items: center !important;
        gap: 12px !important;
        margin-left: auto !important;
    }
</style>
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
                <h3 class="text-secondary mb-2">Tudo tranquilo por aqui</h3>
                <p class="text-tertiary">Nenhuma escala agendada para os próximos dias.</p>
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
                        $themeColor = 'var(--green-600)'; // Verde para hoje
                        $themeLight = 'var(--green-100)';
                    }

                    // Buscar participantes (Top 5)
                    $stmtUsers = $pdo->prepare("
                            SELECT u.id, u.name, u.photo, u.avatar_color 
                            FROM schedule_users su 
                            JOIN users u ON su.user_id = u.id 
                            WHERE su.schedule_id = ? 
                            LIMIT 5
                        ");
                    $stmtUsers->execute([$schedule['id']]);
                    $participants = $stmtUsers->fetchAll(PDO::FETCH_ASSOC);

                    // Verifica se o usuário logado está na escala (para destaque azul)
                    $isMine = false;
                    foreach ($participants as $p) {
                        if (isset($p['id']) && $p['id'] == $loggedUserId) {
                            $isMine = true;
                            break;
                        }
                    }
                    // Se não achou nos 5 primeiros, faz query rápida pra garantir
                    if (!$isMine) {
                        $stmtCheck = $pdo->prepare("SELECT 1 FROM schedule_users WHERE schedule_id = ? AND user_id = ?");
                        $stmtCheck->execute([$schedule['id'], $loggedUserId]);
                        if ($stmtCheck->fetchColumn()) {
                            $isMine = true;
                        }
                    }

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
                    
                    // Lógica de Cores de Fundo (Pedido do Usuário)
                    // 1. Hoje -> Verde Claro
                    // 2. Minha Escala -> Azul Claro
                    // 3. Outros (Culto/Ensaio) -> Cinza Claro
                    
                    if ($isToday) {
                        $cardBg = 'var(--green-50)';
                        $borderColor = 'var(--green-300)';
                    } elseif ($isMine) {
                        $cardBg = 'var(--blue-50)';
                        $borderColor = 'var(--blue-200)';
                    } else {
                        $cardBg = 'var(--slate-50)'; // Cinza leve para futuras
                        $borderColor = 'var(--border-subtle)';
                    }
                ?>

                    <!-- ESCALA CARD -->
                    <a href="escala_detalhe.php?id=<?= $schedule['id'] ?>" style="
                        display: flex;
                        align-items: center;
                        gap: 14px;
                        padding: 14px 16px;
                        background: <?= $cardBg ?>;
                        border: 1px solid <?= $borderColor ?>;
                        border-left: 4px solid <?= $themeColor ?>;
                        border-radius: 12px;
                        text-decoration: none;
                        color: inherit;
                        transition: all 0.2s;
                        margin-bottom: 10px;
                    " onmouseover="this.style.transform='translateY(-2px)'; this.style.boxShadow='0 4px 12px rgba(0,0,0,0.1)'" onmouseout="this.style.transform=''; this.style.boxShadow=''">
                        
                        <!-- Data -->
                        <div style="
                            min-width: 56px;
                            height: 56px;
                            background: <?= $themeLight ?>;
                            border: 1px solid <?= $themeColor ?>30;
                            border-radius: 10px;
                            display: flex;
                            flex-direction: column;
                            align-items: center;
                            justify-content: center;
                            flex-shrink: 0;
                        ">
                            <div style="font-size: 1.4rem; font-weight: 800; line-height: 1; color: <?= $themeColor ?>;"><?= $date->format('d') ?></div>
                            <div style="font-size: 0.65rem; font-weight: 700; text-transform: uppercase; color: <?= $themeColor ?>; opacity: 0.9; margin-top: 3px;"><?= strtoupper(strftime('%b', $date->getTimestamp())) ?></div>
                        </div>

                        <!-- Conteúdo Central -->
                        <div style="flex: 1; min-width: 0;">
                            <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 4px;">
                                <h3 style="font-size: 1.05rem; font-weight: 700; color: var(--text-primary); margin: 0; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                                    <?= htmlspecialchars($schedule['event_type']) ?>
                                </h3>
                                <?php if ($isToday): ?>
                                    <span style="background: var(--red-100); color: var(--red-700); padding: 3px 8px; border-radius: 6px; font-size: 0.7rem; font-weight: 700; text-transform: uppercase;">HOJE</span>
                                <?php else: ?>
                                    <span style="background: var(--blue-100); color: var(--blue-700); padding: 3px 8px; border-radius: 6px; font-size: 0.7rem; font-weight: 700;">
                                        <?= $daysUntil == 1 ? 'Amanhã' : 'em ' . $daysUntil . ' dias' ?>
                                    </span>
                                <?php endif; ?>
                            </div>

                            <?php
                                $stmtSongs = $pdo->prepare("SELECT COUNT(*) FROM schedule_songs WHERE schedule_id = ?");
                                $stmtSongs->execute([$schedule['id']]);
                                $songsCount = $stmtSongs->fetchColumn();
                            ?>

                            <div style="display: flex; align-items: center; gap: 10px; font-size: 0.8rem; color: var(--text-secondary); flex-wrap: wrap;">
                                <span style="display: flex; align-items: center; gap: 4px;">
                                    <i data-lucide="clock" width="13"></i>
                                    <?= isset($schedule['event_time']) ? substr($schedule['event_time'], 0, 5) : '19:00' ?>
                                </span>
                                <?php if ($totalParticipants > 0): ?>
                                    <span style="display: flex; align-items: center; gap: 4px;">
                                        <i data-lucide="users" width="13"></i>
                                        <?= $totalParticipants ?>
                                    </span>
                                <?php endif; ?>
                                <?php if ($songsCount > 0): ?>
                                    <span style="display: flex; align-items: center; gap: 4px; color: var(--blue-600);">
                                        <i data-lucide="music" width="13"></i>
                                        <?= $songsCount ?>
                                    </span>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- Avatares -->
                        <div style="display: flex; align-items: center; gap: 8px; flex-shrink: 0;">
                            <?php if (!empty($participants)): ?>
                                <div style="display: flex; align-items: center;">
                                    <?php foreach (array_slice($participants, 0, 3) as $i => $p):
                                        $photoUrl = $p['photo'] ? '../assets/img/' . $p['photo'] : '';
                                    ?>
                                        <div style="
                                            width: 28px;
                                            height: 28px;
                                            border-radius: 50%;
                                            background: <?= $p['avatar_color'] ?: $themeColor ?>;
                                            border: 2px solid var(--bg-surface);
                                            display: flex;
                                            align-items: center;
                                            justify-content: center;
                                            font-size: 0.7rem;
                                            font-weight: 700;
                                            color: white;
                                            margin-left: <?= $i > 0 ? '-10px' : '0' ?>;
                                            z-index: <?= 10 - $i ?>;
                                        ">
                                            <?php if ($photoUrl): ?>
                                                <img src="<?= htmlspecialchars($photoUrl) ?>" style="width: 100%; height: 100%; object-fit: cover; border-radius: 50%;">
                                            <?php else: ?>
                                                <?= strtoupper(substr($p['name'], 0, 1)) ?>
                                            <?php endif; ?>
                                        </div>
                                    <?php endforeach; ?>
                                    <?php if ($extraCount > 0): ?>
                                        <span style="font-size: 0.75rem; font-weight: 600; color: var(--text-tertiary); margin-left: 4px;">+<?= $extraCount ?></span>
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>
                            <i data-lucide="chevron-right" width="18" style="color: var(--text-tertiary); opacity: 0.5;"></i>
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
            <div id="view-timeline-past" class="timeline-container">
                <?php foreach ($pastSchedules as $schedule):
                    $date = new DateTime($schedule['event_date']);
                    // Past is never today, so $isToday is false
                    $isToday = false; 

                    // Definir Tema de Cor (Mesma lógica das futuras)
                    $type = mb_strtolower($schedule['event_type']);
                    if (strpos($type, 'domingo') !== false) {
                        $themeColor = 'var(--slate-600)'; 
                        $themeLight = 'var(--slate-100)'; 
                    } elseif (strpos($type, 'ensaio') !== false) {
                        $themeColor = 'var(--amber-500)'; 
                        $themeLight = 'var(--amber-50)'; 
                    } elseif (strpos($type, 'jovem') !== false) {
                        $themeColor = '#8b5cf6';
                        $themeLight = '#f5f3ff';
                    } elseif (strpos($type, 'especial') !== false) {
                        $themeColor = 'var(--red-500)'; 
                        $themeLight = 'var(--red-50)'; 
                    } else {
                        $themeColor = 'var(--slate-600)';
                        $themeLight = 'var(--slate-100)';
                    }

                    // Buscar participantes (Top 5) - Query Completa Agora
                    $stmtUsers = $pdo->prepare("
                            SELECT u.id, u.name, u.photo, u.avatar_color 
                            FROM schedule_users su 
                            JOIN users u ON su.user_id = u.id 
                            WHERE su.schedule_id = ? 
                            LIMIT 5
                        ");
                    $stmtUsers->execute([$schedule['id']]);
                    $participants = $stmtUsers->fetchAll(PDO::FETCH_ASSOC);

                    // Verifica se o usuário logado estava na escala
                    $isMine = false;
                    foreach ($participants as $p) {
                        if (isset($p['id']) && $p['id'] == $loggedUserId) {
                            $isMine = true;
                            break;
                        }
                    }
                    if (!$isMine) {
                        $stmtCheck = $pdo->prepare("SELECT 1 FROM schedule_users WHERE schedule_id = ? AND user_id = ?");
                        $stmtCheck->execute([$schedule['id'], $loggedUserId]);
                        if ($stmtCheck->fetchColumn()) {
                            $isMine = true;
                        }
                    }

                    // Contar total participantes
                    $stmtCount = $pdo->prepare("SELECT COUNT(*) FROM schedule_users WHERE schedule_id = ?");
                    $stmtCount->execute([$schedule['id']]);
                    $totalParticipants = $stmtCount->fetchColumn();
                    $extraCount = max(0, $totalParticipants - 5);
                    
                    // Cálculo de dias passados
                    $today = new DateTime('today');
                    $daysAgo = $date->diff($today)->days;

                    // Lógica de Cores de Fundo (Pedido do Usuário)
                    // 1. Hoje -> Verde Claro
                    // 2. Minha Escala -> Azul Claro
                    // 3. Outros (Culto/Ensaio) -> Cinza Claro
                    
                    if ($isToday) {
                        $cardBg = 'var(--green-50)';
                        $borderColor = 'var(--green-300)';
                    } elseif ($isMine) {
                        $cardBg = 'var(--blue-50)';
                        $borderColor = 'var(--blue-200)';
                    } else {
                        $cardBg = 'var(--slate-50)'; // Cinza leve para futuras
                        $borderColor = 'var(--border-subtle)';
                    }
                ?>
                    <!-- ESCALA CARD (PASSADA) -->
                    <a href="escala_detalhe.php?id=<?= $schedule['id'] ?>" style="
                        display: flex;
                        align-items: center;
                        gap: 14px;
                        padding: 14px 16px;
                        background: <?= $cardBg ?>;
                        border: 1px solid <?= $borderColor ?>;
                        border-left: 4px solid <?= $themeColor ?>;
                        border-radius: 12px;
                        text-decoration: none;
                        color: inherit;
                        transition: all 0.2s;
                        margin-bottom: 10px;
                        opacity: 0.7;
                        filter: saturate(0.6);
                    " onmouseover="this.style.opacity='1'; this.style.filter='saturate(1)'; this.style.transform='translateY(-2px)'; this.style.boxShadow='0 4px 12px rgba(0,0,0,0.1)'" onmouseout="this.style.opacity='0.7'; this.style.filter='saturate(0.6)'; this.style.transform=''; this.style.boxShadow=''">
                        
                        <!-- Data -->
                        <div style="
                            min-width: 56px;
                            height: 56px;
                            background: <?= $themeLight ?>;
                            border: 1px solid <?= $themeColor ?>30;
                            border-radius: 10px;
                            display: flex;
                            flex-direction: column;
                            align-items: center;
                            justify-content: center;
                            flex-shrink: 0;
                        ">
                            <div style="font-size: 1.4rem; font-weight: 800; line-height: 1; color: <?= $themeColor ?>;"><?= $date->format('d') ?></div>
                            <div style="font-size: 0.65rem; font-weight: 700; text-transform: uppercase; color: <?= $themeColor ?>; opacity: 0.9; margin-top: 3px;"><?= strtoupper(strftime('%b', $date->getTimestamp())) ?></div>
                        </div>

                        <!-- Conteúdo Central -->
                        <div style="flex: 1; min-width: 0;">
                            <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 4px;">
                                <h3 style="font-size: 1.05rem; font-weight: 700; color: var(--text-primary); margin: 0; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                                    <?= htmlspecialchars($schedule['event_type']) ?>
                                </h3>
                                <span style="background: var(--slate-100); color: var(--slate-600); padding: 3px 8px; border-radius: 6px; font-size: 0.7rem; font-weight: 700;">
                                    <?= $daysAgo == 1 ? 'Ontem' : 'há ' . $daysAgo . ' dias' ?>
                                </span>
                            </div>

                            <?php
                                $stmtSongsPast = $pdo->prepare("SELECT COUNT(*) FROM schedule_songs WHERE schedule_id = ?");
                                $stmtSongsPast->execute([$schedule['id']]);
                                $songsCountPast = $stmtSongsPast->fetchColumn();
                            ?>

                            <div style="display: flex; align-items: center; gap: 10px; font-size: 0.8rem; color: var(--text-secondary); flex-wrap: wrap;">
                                <span style="display: flex; align-items: center; gap: 4px;">
                                    <i data-lucide="clock" width="13"></i>
                                    <?= isset($schedule['event_time']) ? substr($schedule['event_time'], 0, 5) : '19:00' ?>
                                </span>
                                <?php if ($totalParticipants > 0): ?>
                                    <span style="display: flex; align-items: center; gap: 4px;">
                                        <i data-lucide="users" width="13"></i>
                                        <?= $totalParticipants ?>
                                    </span>
                                <?php endif; ?>
                                <?php if ($songsCountPast > 0): ?>
                                    <span style="display: flex; align-items: center; gap: 4px; color: var(--blue-600);">
                                        <i data-lucide="music" width="13"></i>
                                        <?= $songsCountPast ?>
                                    </span>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- Avatares -->
                        <div style="display: flex; align-items: center; gap: 8px; flex-shrink: 0;">
                            <?php if (!empty($participants)): ?>
                                <div style="display: flex; align-items: center;">
                                    <?php foreach (array_slice($participants, 0, 3) as $i => $p):
                                        $photoUrl = $p['photo'] ? '../assets/img/' . $p['photo'] : '';
                                    ?>
                                        <div style="
                                            width: 28px;
                                            height: 28px;
                                            border-radius: 50%;
                                            background: <?= $p['avatar_color'] ?: $themeColor ?>;
                                            border: 2px solid var(--bg-surface);
                                            display: flex;
                                            align-items: center;
                                            justify-content: center;
                                            font-size: 0.7rem;
                                            font-weight: 700;
                                            color: white;
                                            margin-left: <?= $i > 0 ? '-10px' : '0' ?>;
                                            z-index: <?= 10 - $i ?>;
                                        ">
                                            <?php if ($photoUrl): ?>
                                                <img src="<?= htmlspecialchars($photoUrl) ?>" style="width: 100%; height: 100%; object-fit: cover; border-radius: 50%;">
                                            <?php else: ?>
                                                <?= strtoupper(substr($p['name'], 0, 1)) ?>
                                            <?php endif; ?>
                                        </div>
                                    <?php endforeach; ?>
                                    <?php if ($extraCount > 0): ?>
                                        <span style="font-size: 0.75rem; font-weight: 600; color: var(--text-tertiary); margin-left: 4px;">+<?= $extraCount ?></span>
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>
                            <i data-lucide="chevron-right" width="18" style="color: var(--text-tertiary); opacity: 0.5;"></i>
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