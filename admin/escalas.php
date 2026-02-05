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
<!-- Top Controls: Toggle & View Options -->
<!-- Top Controls: Toggle & View Options -->
<div style="max-width: 900px; margin: 0 auto 32px auto; padding: 0 16px; display: flex; flex-wrap: wrap; gap: 16px; justify-content: space-between; align-items: center;">

    <!-- Toggle Central -->
    <div style="background: var(--slate-200); padding: 4px; border-radius: 14px; display: flex; gap: 2px;">
        <button onclick="switchTab('future')" id="btn-future" class="ripple" style="
                border: none; background: white; color: var(--slate-900); 
                padding: 10px 24px; border-radius: 10px; font-weight: 700; font-size: var(--font-body);
                box-shadow: 0 1px 3px rgba(0,0,0,0.1); cursor: pointer; transition: all 0.2s;
            ">Próximas</button>
        <button onclick="switchTab('past')" id="btn-past" class="ripple" style="
                border: none; background: transparent; color: var(--slate-500); 
                padding: 10px 24px; border-radius: 10px; font-weight: 600; font-size: var(--font-body);
                cursor: pointer; transition: all 0.2s;
            ">Anteriores</button>
    </div>

    <!-- Right Controls: Add Button, View Toggle & Filter -->
    <div style="display: flex; gap: 12px; align-items: center;">

        <!-- Botão Adicionar Escala (Admin Only) -->
        <?php if (isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin'): ?>
        <a href="escala_adicionar.php" class="ripple" style="
            width: 48px; height: 48px; 
            background: var(--slate-600); 
            color: white; 
            border-radius: 14px; 
            display: flex; align-items: center; justify-content: center;
            text-decoration: none;
            box-shadow: 0 4px 6px -1px rgba(55, 106, 200, 0.2);
            transition: all 0.2s;
        ">
            <i data-lucide="plus" style="width: 24px;"></i>
        </a>
        <?php endif; ?>



        <!-- Filter Button (Simplificado) -->
        <button onclick="openSheet('filterSheet')" class="ripple" style="
            width: 48px; height: 48px; 
            background: var(--slate-100); 
            border: 1px solid var(--slate-300); 
            color: var(--slate-600); 
            border-radius: 14px; 
            display: flex; align-items: center; justify-content: center;
            cursor: pointer;
            box-shadow: 0 1px 2px rgba(0,0,0,0.05);
            transition: all 0.2s;
        ">
            <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <polygon points="22 3 2 3 10 12.46 10 19 14 21 14 12.46 22 3" />
            </svg>
        </button>

    </div>
</div>

<!-- Container Timeline -->
<div style="max-width: 900px; margin: 0 auto; padding: 0 16px;">

    <!-- TAB: FUTURAS -->
    <div id="tab-future">
        <?php if (empty($futureSchedules)): ?>
            <div style="text-align: center; padding: 80px 20px;">
                <div style="background: var(--slate-100); width: 80px; height: 80px; border-radius: 50%; margin: 0 auto 24px auto; display: flex; align-items: center; justify-content: center;">
                    <i data-lucide="calendar" style="width: 32px; color: var(--slate-400);"></i>
                </div>
                <h3 style="color: var(--slate-700); margin-bottom: 8px;">Tudo tranquilo por aqui</h3>
                <p style="color: var(--slate-400);">Nenhuma escala agendada para os próximos dias.</p>

            </div>
        <?php else: ?>

            <!-- VIEW: TIMELINE (Compacto) -->
            <div id="view-timeline" class="timeline-container" style="display: flex; flex-direction: column; gap: 12px; padding-bottom: 100px;">
                <?php foreach ($futureSchedules as $schedule):
                    $date = new DateTime($schedule['event_date']);
                    $isToday = $date->format('Y-m-d') === date('Y-m-d');

                    // Definir Tema de Cor (Moderate Palette)
                    $type = mb_strtolower($schedule['event_type']);
                    if (strpos($type, 'domingo') !== false) {
                        $themeColor = 'var(--slate-600)'; // Smart Blue 500
                        $themeLight = 'var(--slate-100)'; // Smart Blue 50
                    } elseif (strpos($type, 'ensaio') !== false) {
                        $themeColor = 'var(--yellow-500)'; // Amber 500
                        $themeLight = 'var(--yellow-50)'; // Amber 50
                    } elseif (strpos($type, 'jovem') !== false) {
                        $themeColor = 'var(--lavender-500)'; // Purple 500
                        $themeLight = 'var(--lavender-50)'; // Purple 50
                    } elseif (strpos($type, 'especial') !== false) {
                        $themeColor = '#ec4899'; // Pink 500
                        $themeLight = '#fdf2f8'; // Pink 50
                    } else {
                        $themeColor = 'var(--slate-600)'; // Blue 500
                        $themeLight = 'var(--slate-100)'; // Blue 50
                    }

                    // Sobrescrever se for HOJE (Destaque sutil)
                    if ($isToday) {
                        $themeColor = 'var(--slate-600)'; // Smart Blue 500
                        $themeLight = 'var(--slate-100)'; // Smart Blue 50
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

                    // Buscar ausências que coincidem com esta data
                    $stmtAbsences = $pdo->prepare("
                        SELECT 
                            u.name as absent_member,
                            r.name as replacement_name
                        FROM user_unavailability ua
                        JOIN users u ON ua.user_id = u.id
                        LEFT JOIN users r ON ua.replacement_id = r.id
                        WHERE :event_date BETWEEN ua.start_date AND ua.end_date
                    ");
                    $stmtAbsences->execute(['event_date' => $schedule['event_date']]);
                    $absences = $stmtAbsences->fetchAll(PDO::FETCH_ASSOC);
                    $hasAbsences = count($absences) > 0;

                    // Calcular dias até o evento
                    $today = new DateTime('today');
                    $daysUntil = $today->diff($date)->days;
                    
                    // Cores de fundo do card baseado na data
                    if ($isToday) {
                        $cardBg = 'var(--slate-100)'; // Blue muito claro
                        $cardBorderColor = '#93c5fd'; // Blue claro
                    } else {
                        $cardBg = '#fefce8'; // Amarelo muito claro
                        $cardBorderColor = '#fde047'; // Amarelo claro
                    }
                ?>

                    <!-- Card de Evento REFINADO (Mais Sutil) -->
                    <a href="escala_detalhe.php?id=<?= $schedule['id'] ?>" class="timeline-card ripple" style="
                            display: block;
                            background: <?= $cardBg ?>;
                            border-radius: 12px; 
                            border-left: 4px solid <?= $themeColor ?>;
                            border-top: 1px solid <?= $cardBorderColor ?>;
                            border-right: 1px solid <?= $cardBorderColor ?>;
                            border-bottom: 1px solid <?= $cardBorderColor ?>;
                            padding: 14px; 
                            text-decoration: none; 
                            color: inherit;
                            box-shadow: 0 1px 3px rgba(0,0,0,0.08);
                            transition: all 0.2s ease;
                            position: relative;
                        ">

                        <div style="display: flex; gap: 14px; align-items: flex-start;">

                            <!-- Data Box SUTIL (Lateral Esquerda) -->
                            <div style="
                                background: <?= $themeLight ?>;
                                color: <?= $themeColor ?>;
                                min-width: 56px;
                                height: 56px;
                                padding: 6px;
                                border-radius: 10px; 
                                text-align: center;
                                border: 1px solid <?= $themeColor ?>20;
                                display: flex;
                                flex-direction: column;
                                justify-content: center;
                                align-items: center;
                            ">
                                <div style="font-weight: 700; font-size: 1.3rem; line-height: 1;"><?= $date->format('d') ?></div>
                                <div style="font-size: 0.65rem; font-weight: 600; text-transform: uppercase; margin-top: 2px; opacity: 0.8;"><?= strtoupper(strftime('%b', $date->getTimestamp())) ?></div>
                            </div>

                            <!-- Infos do Evento -->
                            <div style="flex: 1; min-width: 0;">
                                <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 4px;">
                                    <h3 style="margin: 0; font-size: 0.95rem; font-weight: 700; color: var(--slate-800); line-height: 1.3;">
                                        <?= htmlspecialchars($schedule['event_type']) ?>
                                    </h3>
                                    <?php if ($isToday): ?>
                                        <span style="
                                            font-size: 0.65rem;
                                            color: white;
                                            background: var(--slate-600);
                                            padding: 3px 8px;
                                            border-radius: 6px;
                                            font-weight: 700;
                                            text-transform: uppercase;
                                        ">HOJE</span>
                                    <?php else: ?>
                                        <span style="
                                            font-size: 0.65rem;
                                            color: #92400e;
                                            background: var(--yellow-100);
                                            padding: 3px 8px;
                                            border-radius: 6px;
                                            font-weight: 600;
                                        "><?= $daysUntil == 1 ? 'Amanhã' : 'em ' . $daysUntil . ' dias' ?></span>
                                    <?php endif; ?>
                                </div>

                                <div style="display: flex; align-items: center; gap: 12px; font-size: 0.8rem; color: var(--slate-500); margin-bottom: 8px; font-weight: 500;">
                                    <div style="display: flex; align-items: center; gap: 4px;">
                                        <i data-lucide="clock" style="width: 14px;"></i> <?= isset($schedule['event_time']) ? substr($schedule['event_time'], 0, 5) : '19:00' ?>
                                    </div>
                                    <?php if ($totalParticipants > 0): ?>
                                        <div style="display: flex; align-items: center; gap: 4px;">
                                            <i data-lucide="users" style="width: 14px;"></i> <?= $totalParticipants ?>
                                        </div>
                                    <?php endif; ?>
                                </div>

                                <!-- Avatares (Compactos) -->
                                <?php if (!empty($participants)): ?>
                                    <div style="display: flex; align-items: center;">
                                        <?php foreach ($participants as $i => $p):
                                            $zIndex = 10 - $i;
                                            $photoUrl = $p['photo'] ? '../assets/img/' . $p['photo'] : '';
                                        ?>
                                            <div style="
                                                width: 28px; height: 28px; border-radius: 50%; 
                                                border: 2px solid white;
                                                background: <?= $p['avatar_color'] ?: $themeColor ?>;
                                                margin-left: <?= $i > 0 ? '-8px' : '0' ?>;
                                                z-index: <?= $zIndex ?>;
                                                display: flex; align-items: center; justify-content: center;
                                                color: white; font-size: 0.7rem; font-weight: 700; overflow: hidden;
                                                box-shadow: 0 1px 3px rgba(0,0,0,0.1);
                                            ">
                                                <?php if ($photoUrl): ?>
                                                    <img src="<?= htmlspecialchars($photoUrl) ?>" style="width: 100%; height: 100%; object-fit: cover;">
                                                <?php else: ?>
                                                    <?= strtoupper(substr($p['name'], 0, 1)) ?>
                                                <?php endif; ?>
                                            </div>
                                        <?php endforeach; ?>
                                        <?php if ($extraCount > 0): ?>
                                            <div style="
                                                font-size: 0.75rem;
                                                color: var(--slate-500);
                                                font-weight: 600;
                                                margin-left: 6px;
                                            ">+<?= $extraCount ?></div>
                                        <?php endif; ?>
                                    </div>
                                <?php else: ?>
                                    <span style="font-size: 0.8rem; color: var(--slate-400); font-style: italic;">Equipe não definida</span>
                                <?php endif; ?>

                                <!-- Badge de Ausências -->
                                <?php if ($hasAbsences): ?>
                                <div style="
                                    display: inline-flex;
                                    align-items: center;
                                    gap: 4px;
                                    background: var(--rose-50);
                                    color: var(--rose-600);
                                    padding: 4px 8px;
                                    border-radius: 6px;
                                    font-size: 0.7rem;
                                    font-weight: 600;
                                    margin-top: 6px;
                                    border: 1px solid var(--rose-200);
                                ">
                                    <i data-lucide="alert-circle" style="width: 12px;"></i>
                                    <?= count($absences) ?> ausência<?= count($absences) > 1 ? 's' : '' ?>
                                </div>
                                <?php endif; ?>

                            </div>

                            <!-- Icon Setta -->
                            <div style="align-self: center; color: var(--slate-300);">
                                <i data-lucide="chevron-right" style="width: 20px;"></i>
                            </div>

                        </div>
                    </a>
                <?php endforeach; ?>
            </div>

            <!-- VIEW: LIST (Compact) -->
            <div id="view-list" style="display: none; flex-direction: column; gap: 12px; padding-bottom: 100px;">
                <?php foreach ($futureSchedules as $schedule):
                    $date = new DateTime($schedule['event_date']);
                    $isToday = $date->format('Y-m-d') === date('Y-m-d');
                ?>
                    <a href="escala_detalhe.php?id=<?= $schedule['id'] ?>" class="ripple" style="
                        display: flex; align-items: center; justify-content: space-between;
                        padding: 16px 20px; 
                        background: var(--bg-surface); 
                        border-radius: var(--radius-md); 
                        border: 1px solid var(--border-color);
                        text-decoration: none; color: inherit; transition: background 0.2s;
                    ">
                        <div style="display: flex; align-items: center; gap: 16px;">
                            <!-- Date Badge -->
                            <div style="
                                background: <?= $isToday ? 'var(--primary-subtle)' : 'var(--bg-body)' ?>; 
                                color: <?= $isToday ? 'var(--primary)' : 'var(--text-muted)' ?>;
                                padding: 8px 12px; border-radius: 8px; text-align: center; min-width: 50px;
                            ">
                                <div style="font-weight: 700; font-size: var(--font-h2); line-height: 1;"><?= $date->format('d') ?></div>
                                <div style="font-size: var(--font-caption); text-transform: uppercase;"><?= strtoupper($date->format('M')) ?></div>
                            </div>

                            <div>
                                <h3 style="margin: 0; font-size: var(--font-h3); font-weight: 600; color: var(--text-main);">
                                    <?= htmlspecialchars($schedule['event_type']) ?>
                                    <?php if ($isToday): ?><span style="font-size: var(--font-caption); color: var(--primary); background: var(--primary-subtle); padding: 2px 6px; border-radius: 4px; margin-left: 6px;">HOJE</span><?php endif; ?>
                                </h3>
                                <div style="font-size: var(--font-body-sm); color: var(--text-muted); margin-top: 2px;">
                                    <?= isset($schedule['event_time']) ? substr($schedule['event_time'], 0, 5) : '19:00' ?> • PIB Oliveira
                                </div>
                            </div>
                        </div>

                        <i data-lucide="chevron-right" style="color: var(--border-color); width: 20px;"></i>
                    </a>
                <?php endforeach; ?>
            </div>

        <?php endif; ?>
    </div>

    <!-- TAB: ANTERIORES -->
    <div id="tab-past" style="display: none;">
        <?php if (empty($pastSchedules)): ?>
            <div style="text-align: center; padding: 40px; color: var(--text-muted);">
                <p>Nenhum histórico recente.</p>
            </div>
        <?php else: ?>
            <div id="view-timeline-past" class="timeline-container" style="display: flex; flex-direction: column; gap: 12px; padding-bottom: 100px;">
                <?php foreach ($pastSchedules as $schedule):
                    $date = new DateTime($schedule['event_date']);
                    
                    // Calcular quantos dias atrás
                    $today = new DateTime('today');
                    $daysAgo = $date->diff($today)->days;
                    
                    // Cor cinza para escalas passadas
                    $themeColor = 'var(--slate-500)'; // Slate 500
                    $themeLight = 'var(--slate-100)'; // Slate 100
                    $cardBg = 'var(--slate-50)'; // Slate 50
                    $cardBorderColor = 'var(--slate-200)'; // Slate 200
                    
                    // Buscar participantes (Top 5)
                    $stmtUsersPast = $pdo->prepare("
                        SELECT u.name, u.photo, u.avatar_color 
                        FROM schedule_users su 
                        JOIN users u ON su.user_id = u.id 
                        WHERE su.schedule_id = ? 
                        LIMIT 5
                    ");
                    $stmtUsersPast->execute([$schedule['id']]);
                    $participantsPast = $stmtUsersPast->fetchAll(PDO::FETCH_ASSOC);
                    
                    // Contar total participantes
                    $stmtCountPast = $pdo->prepare("SELECT COUNT(*) FROM schedule_users WHERE schedule_id = ?");
                    $stmtCountPast->execute([$schedule['id']]);
                    $totalParticipantsPast = $stmtCountPast->fetchColumn();
                    $extraCountPast = max(0, $totalParticipantsPast - 5);
                ?>
                    <!-- Card de Evento Passado (Cinza) -->
                    <a href="escala_detalhe.php?id=<?= $schedule['id'] ?>" class="timeline-card-past ripple" style="
                            display: block;
                            background: <?= $cardBg ?>;
                            border-radius: 12px; 
                            border-left: 4px solid <?= $themeColor ?>;
                            border-top: 1px solid <?= $cardBorderColor ?>;
                            border-right: 1px solid <?= $cardBorderColor ?>;
                            border-bottom: 1px solid <?= $cardBorderColor ?>;
                            padding: 14px; 
                            text-decoration: none; 
                            color: inherit;
                            box-shadow: 0 1px 3px rgba(0,0,0,0.06);
                            transition: all 0.2s ease;
                            position: relative;
                        ">

                        <div style="display: flex; gap: 14px; align-items: flex-start;">

                            <!-- Data Box (Cinza) -->
                            <div style="
                                background: <?= $themeLight ?>;
                                color: <?= $themeColor ?>;
                                min-width: 56px;
                                height: 56px;
                                padding: 6px;
                                border-radius: 10px; 
                                text-align: center;
                                border: 1px solid <?= $themeColor ?>20;
                                display: flex;
                                flex-direction: column;
                                justify-content: center;
                                align-items: center;
                            ">
                                <div style="font-weight: 700; font-size: 1.3rem; line-height: 1;"><?= $date->format('d') ?></div>
                                <div style="font-size: 0.65rem; font-weight: 600; text-transform: uppercase; margin-top: 2px; opacity: 0.8;"><?= strtoupper(strftime('%b', $date->getTimestamp())) ?></div>
                            </div>

                            <!-- Infos do Evento -->
                            <div style="flex: 1; min-width: 0;">
                                <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 4px;">
                                    <h3 style="margin: 0; font-size: 0.95rem; font-weight: 700; color: var(--slate-500); line-height: 1.3;">
                                        <?= htmlspecialchars($schedule['event_type']) ?>
                                    </h3>
                                    <span style="
                                        font-size: 0.65rem;
                                        color: var(--slate-500);
                                        background: var(--slate-200);
                                        padding: 3px 8px;
                                        border-radius: 6px;
                                        font-weight: 600;
                                    "><?= $daysAgo == 1 ? 'Ontem' : 'há ' . $daysAgo . ' dias' ?></span>
                                </div>

                                <div style="display: flex; align-items: center; gap: 12px; font-size: 0.8rem; color: var(--slate-400); margin-bottom: 8px; font-weight: 500;">
                                    <div style="display: flex; align-items: center; gap: 4px;">
                                        <i data-lucide="clock" style="width: 14px;"></i> <?= isset($schedule['event_time']) ? substr($schedule['event_time'], 0, 5) : '19:00' ?>
                                    </div>
                                    <?php if ($totalParticipantsPast > 0): ?>
                                        <div style="display: flex; align-items: center; gap: 4px;">
                                            <i data-lucide="users" style="width: 14px;"></i> <?= $totalParticipantsPast ?>
                                        </div>
                                    <?php endif; ?>
                                </div>

                                <!-- Avatares (Cinza) -->
                                <?php if (!empty($participantsPast)): ?>
                                    <div style="display: flex; align-items: center;">
                                        <?php foreach ($participantsPast as $i => $p):
                                            $zIndex = 10 - $i;
                                            $photoUrl = $p['photo'] ? '../assets/img/' . $p['photo'] : '';
                                        ?>
                                            <div style="
                                                width: 28px; height: 28px; border-radius: 50%; 
                                                border: 2px solid white;
                                                background: <?= $p['avatar_color'] ?: 'var(--slate-400)' ?>;
                                                margin-left: <?= $i > 0 ? '-8px' : '0' ?>;
                                                z-index: <?= $zIndex ?>;
                                                display: flex; align-items: center; justify-content: center;
                                                color: white; font-size: 0.7rem; font-weight: 700; overflow: hidden;
                                                box-shadow: 0 1px 3px rgba(0,0,0,0.1);
                                                filter: grayscale(30%);
                                            ">
                                                <?php if ($photoUrl): ?>
                                                    <img src="<?= htmlspecialchars($photoUrl) ?>" style="width: 100%; height: 100%; object-fit: cover; filter: grayscale(30%);">
                                                <?php else: ?>
                                                    <?= strtoupper(substr($p['name'], 0, 1)) ?>
                                                <?php endif; ?>
                                            </div>
                                        <?php endforeach; ?>
                                        <?php if ($extraCountPast > 0): ?>
                                            <div style="
                                                font-size: 0.75rem;
                                                color: var(--slate-400);
                                                font-weight: 600;
                                                margin-left: 6px;
                                            ">+<?= $extraCountPast ?></div>
                                        <?php endif; ?>
                                    </div>
                                <?php else: ?>
                                    <span style="font-size: 0.8rem; color: var(--slate-400); font-style: italic;">Equipe não definida</span>
                                <?php endif; ?>

                            </div>

                            <!-- Icon Seta -->
                            <div style="align-self: center; color: var(--slate-300);">
                                <i data-lucide="chevron-right" style="width: 20px;"></i>
                            </div>

                        </div>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>




<!-- FILTER SHEET (CORRIGIDO E RESTAURADO) -->
<div id="filterSheet" style="
    display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%;
    z-index: 2000;
">
    <!-- Backdrop -->
    <div onclick="closeSheet('filterSheet')" style="
        position: absolute; width: 100%; height: 100%; 
        background: rgba(0,0,0,0.5); backdrop-filter: blur(2px);
    "></div>

    <!-- Sheet Content -->
    <div style="
        position: absolute; bottom: 0; left: 0; width: 100%;
        background: white; border-radius: 20px 20px 0 0;
        padding: 24px; padding-bottom: 40px; box-shadow: 0 -4px 20px rgba(0,0,0,0.1);
        animation: slideUp 0.3s ease-out;
    ">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px;">
            <h3 style="margin: 0; font-size: var(--font-h1); font-weight: 700; color: var(--slate-800);">Filtrar Escalas</h3>
            <button onclick="closeSheet('filterSheet')" style="background: none; border: none; padding: 4px; cursor: pointer; color: var(--slate-500);">
                <i data-lucide="x" style="width: 24px;"></i>
            </button>
        </div>

        <form method="GET" action="escalas.php">
            <!-- Toggle Minhas Escalas -->
            <label style="
                display: flex; align-items: center; justify-content: space-between;
                padding: 16px; background: var(--slate-50); border-radius: 12px;
                cursor: pointer; margin-bottom: 20px; border: 1px solid var(--slate-200);
            ">
                <span style="font-weight: 600; color: var(--slate-700);">Apenas em que participo</span>
                <input type="checkbox" name="mine" value="1" <?= $filterMine ? 'checked' : '' ?> style="transform: scale(1.5);">
            </label>

            <!-- Tipo de Evento -->
            <div style="margin-bottom: 24px;">
                <label style="display: block; font-size: var(--font-body); font-weight: 600; color: var(--slate-500); margin-bottom: 8px;">Tipo de Evento</label>
                <div style="display: flex; gap: 8px; flex-wrap: wrap;">
                    <?php
                    $types = ['Culto Domingo a Noite', 'Ensaio', 'Culto Jovem', 'Especial'];
                    foreach ($types as $t):
                        $active = $filterType === $t;
                    ?>
                        <label style="cursor: pointer;">
                            <input type="radio" name="type" value="<?= $t ?>" <?= $active ? 'checked' : '' ?> style="display: none;">
                            <div style="
                                padding: 8px 16px; border-radius: 20px; font-size: var(--font-body-sm); font-weight: 600;
                                background: <?= $active ? 'var(--sage-100)' : 'white' ?>;
                                color: <?= $active ? 'var(--sage-800)' : 'var(--slate-500)' ?>;
                                border: 1px solid <?= $active ? 'var(--sage-800)' : 'var(--slate-200)' ?>;
                                transition: all 0.2s;
                            ">
                                <?= $t ?>
                            </div>
                        </label>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Botões -->
            <div style="display: flex; gap: 12px;">
                <a href="escalas.php" style="
                    flex: 1; padding: 14px; text-align: center; text-decoration: none;
                    color: var(--slate-500); font-weight: 600; background: var(--slate-100); border-radius: 12px;
                ">Limpar</a>
                <button type="submit" style="
                    flex: 2; padding: 14px; border: none; background: var(--sage-800); 
                    color: white; font-weight: 700; border-radius: 12px; font-size: var(--font-h3);
                    box-shadow: 0 4px 6px -1px rgba(22, 101, 52, 0.2); cursor: pointer;
                ">Aplicar Filtros</button>
            </div>
        </form>
    </div>
</div>


<!-- Scripts -->
<script>
    // Tab Switching (Próximas vs Anteriores)
    function switchTab(tab) {
        const btnFuture = document.getElementById('btn-future');
        const btnPast = document.getElementById('btn-past');
        const tabFuture = document.getElementById('tab-future');
        const tabPast = document.getElementById('tab-past');

        if (tab === 'future') {
            tabFuture.style.display = 'block';
            tabPast.style.display = 'none';

            btnFuture.style.background = 'white';
            btnFuture.style.color = 'var(--slate-900)';
            btnFuture.style.boxShadow = '0 1px 2px rgba(0,0,0,0.1)';

            btnPast.style.background = 'transparent';
            btnPast.style.color = 'var(--slate-500)';
            btnPast.style.boxShadow = 'none';
        } else {
            tabFuture.style.display = 'none';
            tabPast.style.display = 'block';

            btnFuture.style.background = 'transparent';
            btnFuture.style.color = 'var(--slate-500)';
            btnFuture.style.boxShadow = 'none';

            btnPast.style.background = 'white';
            btnPast.style.color = 'var(--slate-900)';
            btnPast.style.boxShadow = '0 1px 2px rgba(0,0,0,0.1)';
        }
    }

    // View Toggle (Timeline vs List)
    function setView(view) {
        // Future Views
        const timeline = document.getElementById('view-timeline');
        const list = document.getElementById('view-list');

        // Past Views
        const timelinePast = document.getElementById('view-timeline-past');
        const listPast = document.getElementById('view-list-past');

        const btnTimeline = document.getElementById('btn-view-timeline');
        const btnList = document.getElementById('btn-view-list');

        // localStorage.setItem('escalasView', view);

        if (view === 'timeline') {
            // Show Timelines
            if (timeline) timeline.style.display = 'flex';
            if (timelinePast) timelinePast.style.display = 'flex';

            // Hide Lists
            if (list) list.style.display = 'none';
            if (listPast) listPast.style.display = 'none';

            if (btnTimeline) {
                btnTimeline.style.background = 'white';
                btnTimeline.style.color = 'var(--slate-900)';
                btnTimeline.style.boxShadow = '0 1px 2px rgba(0,0,0,0.1)';
            }
            if (btnList) {
                btnList.style.background = 'transparent';
                btnList.style.color = 'var(--slate-500)';
                btnList.style.boxShadow = 'none';
            }
        } else {
            // Hide Timelines
            if (timeline) timeline.style.display = 'none';
            if (timelinePast) timelinePast.style.display = 'none';

            // Show Lists
            if (list) list.style.display = 'flex';
            if (listPast) listPast.style.display = 'flex';

            if (btnTimeline) {
                btnTimeline.style.background = 'transparent';
                btnTimeline.style.color = 'var(--slate-500)';
                btnTimeline.style.boxShadow = 'none';
            }
            if (btnList) {
                btnList.style.background = 'white';
                btnList.style.color = 'var(--slate-900)';
                btnList.style.boxShadow = '0 1px 2px rgba(0,0,0,0.1)';
            }
        }
    }

    // Sheet Modal Logic
    function openSheet(id) {
        const sheet = document.getElementById(id);
        if (sheet) {
            sheet.style.display = 'block';
            document.body.style.overflow = 'hidden'; // Prevent scroll
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

<style>
    @keyframes slideUp {
        from {
            transform: translateY(100%);
        }

        to {
            transform: translateY(0);
        }
    }

    .timeline-card:hover {
        transform: translateY(-4px) scale(1.01);
        box-shadow: 0 12px 24px rgba(0, 0, 0, 0.12), 0 4px 8px rgba(0,0,0,0.08);
        border-color: currentColor;
    }

    @media(max-width: 640px) {
        .desktop-only {
            display: none;
        }
    }
</style>

<?php renderAppFooter(); ?>