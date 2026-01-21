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
<div style="max-width: 900px; margin: 0 auto 32px auto; padding: 0 16px; display: flex; justify-content: space-between; align-items: center;">

    <!-- Toggle Central -->
    <div style="background: #e2e8f0; padding: 4px; border-radius: 12px; display: flex; gap: 4px;">
        <button onclick="switchTab('future')" id="btn-future" class="ripple" style="
                border: none; background: white; color: #0f172a; 
                padding: 8px 24px; border-radius: 8px; font-weight: 600; font-size: 0.9rem;
                box-shadow: 0 1px 2px rgba(0,0,0,0.1); cursor: pointer; transition: all 0.2s;
            ">Próximas</button>
        <button onclick="switchTab('past')" id="btn-past" class="ripple" style="
                border: none; background: transparent; color: #64748b; 
                padding: 8px 24px; border-radius: 8px; font-weight: 600; font-size: 0.9rem;
                cursor: pointer; transition: all 0.2s;
            ">Anteriores</button>
    </div>

    <!-- View Toggles (Real) & Filter -->
    <div style="display: flex; gap: 8px;">
        <!-- Filter Button -->
        <button onclick="openSheet('filterSheet')" class="ripple" style="
            width: 36px; height: 36px; 
            background: <?= $activeFilters > 0 ? '#dcfce7' : '#white' ?>; 
            border: <?= $activeFilters > 0 ? '1px solid #166534' : '1px solid #e2e8f0' ?>; 
            color: <?= $activeFilters > 0 ? '#166534' : '#64748b' ?>; 
            border-radius: 12px; 
            display: flex; align-items: center; justify-content: center;
            cursor: pointer;
            position: relative;
            box-shadow: 0 1px 2px rgba(0,0,0,0.05);
        ">
            <i data-lucide="filter" style="width: 18px;"></i>
            <?php if ($activeFilters > 0): ?>
                <span style="
                    position: absolute; top: -4px; right: -4px; 
                    background: #166534; color: white; 
                    font-size: 0.6rem; font-weight: 700; 
                    width: 16px; height: 16px; border-radius: 50%; 
                    display: flex; align-items: center; justify-content: center;
                    border: 2px solid white;
                "><?= $activeFilters ?></span>
            <?php endif; ?>
        </button>

        <div style="background: #e2e8f0; padding: 4px; border-radius: 12px; display: flex; gap: 4px;">
            <button onclick="setView('timeline')" id="btn-view-timeline" title="Linha do Tempo" class="ripple" style="
                border: none; background: white; color: #0f172a; 
                width: 36px; height: 36px; border-radius: 8px; 
                display: flex; align-items: center; justify-content: center;
                box-shadow: 0 1px 2px rgba(0,0,0,0.1); cursor: pointer; transition: all 0.2s;
            ">
                <i data-lucide="calendar-clock" style="width: 20px;"></i>
            </button>
            <button onclick="setView('list')" id="btn-view-list" title="Modo Lista" class="ripple" style="
                border: none; background: transparent; color: #64748b; 
                width: 36px; height: 36px; border-radius: 8px; 
                display: flex; align-items: center; justify-content: center;
                cursor: pointer; transition: all 0.2s;
                box-shadow: none;
            ">
                <i data-lucide="list" style="width: 20px;"></i>
            </button>
        </div>
    </div>
</div>

<!-- Container Timeline -->
<div style="max-width: 900px; margin: 0 auto; padding: 0 16px;">

    <!-- TAB: FUTURAS -->
    <div id="tab-future">
        <?php if (empty($futureSchedules)): ?>
            <div style="text-align: center; padding: 80px 20px;">
                <div style="background: #f1f5f9; width: 80px; height: 80px; border-radius: 50%; margin: 0 auto 24px auto; display: flex; align-items: center; justify-content: center;">
                    <i data-lucide="calendar" style="width: 32px; color: #94a3b8;"></i>
                </div>
                <h3 style="color: #334155; margin-bottom: 8px;">Tudo tranquilo por aqui</h3>
                <p style="color: #94a3b8;">Nenhuma escala agendada para os próximos dias.</p>
                <a href="escala_adicionar.php" style="display: inline-block; margin-top: 24px; color: #166534; font-weight: 600; text-decoration: none;">+ Adicionar Escala</a>
            </div>
        <?php else: ?>

            <!-- VIEW: TIMELINE -->
            <div id="view-timeline" class="timeline-container" style="display: flex; flex-direction: column; gap: 24px; padding-bottom: 100px;">
                <?php foreach ($futureSchedules as $schedule):
                    $date = new DateTime($schedule['event_date']);
                    $isToday = $date->format('Y-m-d') === date('Y-m-d');

                    // Definir Tema de Cor baseado no Tipo
                    $type = mb_strtolower($schedule['event_type']);
                    if (strpos($type, 'domingo') !== false) {
                        $themeColor = '#059669'; // Emerald 600
                        $themeLight = '#ecfdf5'; // Emerald 50
                    } elseif (strpos($type, 'ensaio') !== false) {
                        $themeColor = '#d97706'; // Amber 600
                        $themeLight = '#fffbeb'; // Amber 50
                    } elseif (strpos($type, 'jovem') !== false) {
                        $themeColor = '#7c3aed'; // Violet 600
                        $themeLight = '#f5f3ff'; // Violet 50
                    } elseif (strpos($type, 'especial') !== false) {
                        $themeColor = '#db2777'; // Pink 600
                        $themeLight = '#fdf2f8'; // Pink 50
                    } else {
                        $themeColor = '#2563eb'; // Blue 600
                        $themeLight = '#eff6ff'; // Blue 50
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

                    // Buscar Músicas (Top 3)
                    $stmtSongs = $pdo->prepare("
                        SELECT s.title 
                        FROM schedule_songs ss
                        JOIN songs s ON ss.song_id = s.id
                        WHERE ss.schedule_id = ?
                        ORDER BY ss.order_index ASC
                        LIMIT 3
                    ");
                    $stmtSongs->execute([$schedule['id']]);
                    $songs = $stmtSongs->fetchAll(PDO::FETCH_ASSOC);

                    // Contar total músicas
                    $stmtSongCount = $pdo->prepare("SELECT COUNT(*) FROM schedule_songs WHERE schedule_id = ?");
                    $stmtSongCount->execute([$schedule['id']]);
                    $totalSongs = $stmtSongCount->fetchColumn();
                    $extraSongs = max(0, $totalSongs - 3);
                ?>
                    <div class="timeline-row" style="display: flex; gap: 24px;">
                        <!-- Coluna Data Colorida -->
                        <div class="timeline-date" style="text-align: right; min-width: 60px; padding-top: 8px;">
                            <div style="font-size: 1.8rem; font-weight: 700; color: <?= $themeColor ?>; line-height: 1;"><?= $date->format('d') ?></div>
                            <div style="font-size: 0.75rem; font-weight: 700; color: #94a3b8; text-transform: uppercase;"><?= strtoupper(strftime('%b', $date->getTimestamp())) ?></div>
                            <div style="font-size: 0.7rem; color: #cbd5e1; margin-top: 4px;"><?= $date->format('D') ?></div>
                        </div>

                        <!-- Card Evento Rico -->
                        <a href="escala_detalhe.php?id=<?= $schedule['id'] ?>" class="timeline-card ripple" style="
                                flex: 1; 
                                background: white; 
                                border-radius: 16px; 
                                border: 1px solid #e2e8f0;
                                border-left: 5px solid <?= $themeColor ?>;
                                padding: 0; 
                                text-decoration: none; 
                                color: inherit;
                                box-shadow: 0 4px 6px -1px rgba(0,0,0,0.02);
                                transition: all 0.2s;
                                position: relative;
                                overflow: hidden;
                            ">

                            <!-- Header do Card -->
                            <div style="padding: 16px 20px; border-bottom: 1px solid #f1f5f9; background: linear-gradient(to right, <?= $themeLight ?>30, transparent);">
                                <?php if ($isToday): ?>
                                    <div style="float: right; background: #dcfce7; color: #166534; padding: 4px 10px; border-radius: 20px; font-size: 0.7rem; font-weight: 700; margin-left: 12px; box-shadow: 0 1px 2px rgba(0,0,0,0.05);">
                                        HOJE
                                    </div>
                                <?php endif; ?>

                                <h3 style="margin: 0 0 6px 0; font-size: 1.1rem; font-weight: 700; color: #1e293b;">
                                    <?= htmlspecialchars($schedule['event_type']) ?>
                                </h3>
                                <div style="display: flex; align-items: center; gap: 16px; font-size: 0.85rem; color: #64748b;">
                                    <div style="display: flex; align-items: center; gap: 6px;">
                                        <i data-lucide="clock" style="width: 14px; color: <?= $themeColor ?>;"></i>
                                        <span style="font-weight: 500;"><?= $date->format('H:i') ?></span>
                                    </div>
                                    <div style="display: flex; align-items: center; gap: 6px;">
                                        <i data-lucide="map-pin" style="width: 14px; color: <?= $themeColor ?>;"></i>
                                        <span>PIB Oliveira</span>
                                    </div>
                                </div>
                            </div>

                            <!-- Corpo do Card: Infos Resumidas -->
                            <div style="padding: 16px 20px; display: flex; flex-direction: column; gap: 16px;">

                                <!-- Seção Músicas -->
                                <?php if (!empty($songs)): ?>
                                    <div style="display: flex; gap: 12px;">
                                        <div style="padding-top: 2px;">
                                            <i data-lucide="music" style="width: 16px; color: <?= $themeColor ?>;"></i>
                                        </div>
                                        <div style="flex: 1;">
                                            <div style="font-size: 0.75rem; font-weight: 800; color: <?= $themeColor ?>; margin-bottom: 6px; text-transform: uppercase; letter-spacing: 0.5px; opacity: 0.8;">Repertório</div>
                                            <div style="display: flex; flex-wrap: wrap; gap: 6px;">
                                                <?php foreach ($songs as $s): ?>
                                                    <span style="
                                                        font-size: 0.85rem; 
                                                        color: #334155; 
                                                        background: <?= $themeLight ?>; 
                                                        padding: 4px 10px; 
                                                        border-radius: 6px; 
                                                        border: 1px solid <?= $themeColor ?>20;
                                                        font-weight: 500;
                                                    ">
                                                        <?= htmlspecialchars($s['title']) ?>
                                                    </span>
                                                <?php endforeach; ?>
                                                <?php if ($extraSongs > 0): ?>
                                                    <span style="font-size: 0.8rem; color: #64748b; padding: 2px 4px; align-self: center;">+<?= $extraSongs ?></span>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                <?php endif; ?>

                                <!-- Seção Equipe -->
                                <div style="display: flex; gap: 12px; align-items: center;">
                                    <div>
                                        <i data-lucide="users" style="width: 16px; color: #64748b;"></i>
                                    </div>
                                    <div style="flex: 1; display: flex; align-items: center; justify-content: space-between;">
                                        <div style="display: flex; align-items: center;">
                                            <div style="display: flex; padding-left: 8px;">
                                                <?php if (empty($participants)): ?>
                                                    <span style="font-size: 0.85rem; color: #94a3b8; font-style: italic;">Nenhum participante definido</span>
                                                <?php else: ?>
                                                    <?php foreach ($participants as $i => $p):
                                                        $zIndex = 10 - $i;
                                                        // Foto ou Initials
                                                        $hasPhoto = $p['photo'] && file_exists(__DIR__ . '/../assets/img/' . $p['photo']); // Check absoluto simples ou relativo melhorado
                                                        // Fallback visual no front se path quebrar
                                                        $photoUrl = $p['photo'] ? '../assets/img/' . $p['photo'] : '';
                                                    ?>
                                                        <div style="
                                                                width: 28px; height: 28px; 
                                                                border-radius: 50%; 
                                                                border: 2px solid white; 
                                                                background: <?= $p['avatar_color'] ?: '#cbd5e1' ?>;
                                                                margin-left: -8px; 
                                                                z-index: <?= $zIndex ?>;
                                                                display: flex; align-items: center; justify-content: center;
                                                                color: white; font-size: 0.65rem; font-weight: 700;
                                                                overflow: hidden;
                                                            ">
                                                            <?php if ($photoUrl): ?>
                                                                <img src="<?= htmlspecialchars($photoUrl) ?>" onerror="this.style.display='none'; this.parentElement.innerText='<?= strtoupper(substr($p['name'], 0, 1)) ?>'" style="width: 100%; height: 100%; object-fit: cover;">
                                                            <?php else: ?>
                                                                <?= strtoupper(substr($p['name'], 0, 1)) ?>
                                                            <?php endif; ?>
                                                        </div>
                                                    <?php endforeach; ?>

                                                    <?php if ($extraCount > 0): ?>
                                                        <div style="
                                                                width: 28px; height: 28px; 
                                                                border-radius: 50%; 
                                                                border: 2px solid white; 
                                                                background: #f1f5f9; 
                                                                margin-left: -8px; 
                                                                z-index: 0;
                                                                display: flex; align-items: center; justify-content: center;
                                                                color: #64748b; font-size: 0.65rem; font-weight: 700;
                                                            ">
                                                            +<?= $extraCount ?>
                                                        </div>
                                                    <?php endif; ?>

                                                    <div style="margin-left: 12px; font-size: 0.85rem; color: #64748b;">
                                                        <span style="font-weight: 600; color: #334155;"><?= $totalParticipants ?></span> na equipe
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                            </div>
                        </a>
                    </div>
                <?php endforeach; ?>
            </div>

            <!-- VIEW: LIST (Compact) -->
            <div id="view-list" style="display: none; flex-direction: column; gap: 8px; padding-bottom: 100px;">
                <?php foreach ($futureSchedules as $schedule):
                    $date = new DateTime($schedule['event_date']);
                    $isToday = $date->format('Y-m-d') === date('Y-m-d');

                    // Contagens
                    $stmtCount = $pdo->prepare("SELECT COUNT(*) FROM schedule_users WHERE schedule_id = ?");
                    $stmtCount->execute([$schedule['id']]);
                    $teamCount = $stmtCount->fetchColumn();

                    $stmtSongs = $pdo->prepare("SELECT COUNT(*) FROM schedule_songs WHERE schedule_id = ?");
                    $stmtSongs->execute([$schedule['id']]);
                    $songCount = $stmtSongs->fetchColumn();
                ?>
                    <a href="escala_detalhe.php?id=<?= $schedule['id'] ?>" class="ripple" style="
                        display: flex; align-items: center; justify-content: space-between;
                        padding: 16px 20px; background: white; border-radius: 12px; border: 1px solid #e2e8f0;
                        text-decoration: none; color: inherit; transition: background 0.2s;
                    ">
                        <div style="display: flex; align-items: center; gap: 16px;">
                            <!-- Date Badge -->
                            <div style="
                                background: <?= $isToday ? '#dcfce7' : '#f1f5f9' ?>; 
                                color: <?= $isToday ? '#166534' : '#64748b' ?>;
                                padding: 8px 12px; border-radius: 8px; text-align: center; min-width: 50px;
                            ">
                                <div style="font-weight: 700; font-size: 1.1rem; line-height: 1;"><?= $date->format('d') ?></div>
                                <div style="font-size: 0.65rem; text-transform: uppercase;"><?= strtoupper($date->format('M')) ?></div>
                            </div>

                            <div>
                                <h3 style="margin: 0; font-size: 1rem; font-weight: 600; color: #1e293b;">
                                    <?= htmlspecialchars($schedule['event_type']) ?>
                                    <?php if ($isToday): ?><span style="font-size: 0.7rem; color: #166534; background: #dcfce7; padding: 2px 6px; border-radius: 4px; margin-left: 6px;">HOJE</span><?php endif; ?>
                                </h3>
                                <div style="font-size: 0.8rem; color: #64748b; margin-top: 2px;">
                                    <?= $teamCount ?> participantes • <?= $songCount ?> músicas
                                </div>
                            </div>
                        </div>

                        <i data-lucide="chevron-right" style="color: #cbd5e1; width: 18px;"></i>
                    </a>
                <?php endforeach; ?>
            </div>

        <?php endif; ?>
    </div>

    <!-- TAB: ANTERIORES -->
    <div id="tab-past" style="display: none;">
        <?php if (empty($pastSchedules)): ?>
            <div style="text-align: center; padding: 40px; color: #94a3b8;">
                <p>Nenhum histórico recente.</p>
            </div>
        <?php else: ?>
            <div class="timeline-container" style="display: flex; flex-direction: column; gap: 16px; padding-bottom: 100px;">
                <?php foreach ($pastSchedules as $schedule):
                    $date = new DateTime($schedule['event_date']);

                    // Definir Tema de Cor (Mesma lógica do Future)
                    $type = mb_strtolower($schedule['event_type']);
                    if (strpos($type, 'domingo') !== false) {
                        $themeColor = '#059669';
                        $themeLight = '#ecfdf5';
                    } elseif (strpos($type, 'ensaio') !== false) {
                        $themeColor = '#d97706';
                        $themeLight = '#fffbeb';
                    } elseif (strpos($type, 'jovem') !== false) {
                        $themeColor = '#7c3aed';
                        $themeLight = '#f5f3ff';
                    } elseif (strpos($type, 'especial') !== false) {
                        $themeColor = '#db2777';
                        $themeLight = '#fdf2f8';
                    } else {
                        $themeColor = '#2563eb';
                        $themeLight = '#eff6ff';
                    }

                    // Buscar dados (Cópia simplificada)
                    $stmtUsers = $pdo->prepare("SELECT u.name, u.photo, u.avatar_color FROM schedule_users su JOIN users u ON su.user_id = u.id WHERE su.schedule_id = ? LIMIT 5");
                    $stmtUsers->execute([$schedule['id']]);
                    $participants = $stmtUsers->fetchAll(PDO::FETCH_ASSOC);

                    $stmtCount = $pdo->prepare("SELECT COUNT(*) FROM schedule_users WHERE schedule_id = ?");
                    $stmtCount->execute([$schedule['id']]);
                    $totalParticipants = $stmtCount->fetchColumn();
                    $extraCount = max(0, $totalParticipants - 5);

                    $stmtSongs = $pdo->prepare("SELECT s.title FROM schedule_songs ss JOIN songs s ON ss.song_id = s.id WHERE ss.schedule_id = ? ORDER BY ss.order_index ASC LIMIT 3");
                    $stmtSongs->execute([$schedule['id']]);
                    $songs = $stmtSongs->fetchAll(PDO::FETCH_ASSOC);

                    $stmtSongCount = $pdo->prepare("SELECT COUNT(*) FROM schedule_songs WHERE schedule_id = ?");
                    $stmtSongCount->execute([$schedule['id']]);
                    $totalSongs = $stmtSongCount->fetchColumn();
                    $extraSongs = max(0, $totalSongs - 3);
                ?>
                    <div class="timeline-row" style="display: flex; gap: 24px;">
                        <!-- Coluna Data (Apagada) -->
                        <div class="timeline-date" style="text-align: right; min-width: 60px; padding-top: 8px; opacity: 0.5; filter: grayscale(1);">
                            <div style="font-size: 1.8rem; font-weight: 700; color: <?= $themeColor ?>; line-height: 1;"><?= $date->format('d') ?></div>
                            <div style="font-size: 0.75rem; font-weight: 700; color: #94a3b8; text-transform: uppercase;"><?= strtoupper(strftime('%b', $date->getTimestamp())) ?></div>
                            <div style="font-size: 0.7rem; color: #cbd5e1; margin-top: 4px;"><?= $date->format('Y') ?></div>
                        </div>

                        <!-- Card Evento (Passado/Faded) -->
                        <a href="escala_detalhe.php?id=<?= $schedule['id'] ?>" class="timeline-card ripple" style="
                                flex: 1; 
                                background: #fdfdfd; 
                                border-radius: 16px; 
                                border: 1px solid #e2e8f0;
                                border-left: 5px solid <?= $themeColor ?>;
                                padding: 0; 
                                text-decoration: none; 
                                color: inherit;
                                box-shadow: none;
                                transition: all 0.2s;
                                position: relative;
                                overflow: hidden;
                                filter: grayscale(1) opacity(0.8); /* EFEITO DE PASSADO */
                            ">

                            <!-- Header do Card -->
                            <div style="padding: 16px 20px; border-bottom: 1px solid #f1f5f9; background: linear-gradient(to right, <?= $themeLight ?>30, transparent);">
                                <div style="float: right; border: 1px solid #cbd5e1; color: #64748b; padding: 2px 8px; border-radius: 6px; font-size: 0.65rem; font-weight: 600; text-transform: uppercase;">
                                    Realizado
                                </div>

                                <h3 style="margin: 0 0 6px 0; font-size: 1.1rem; font-weight: 700; color: #1e293b; text-decoration: line-through #cbd5e1;">
                                    <?= htmlspecialchars($schedule['event_type']) ?>
                                </h3>
                                <div style="display: flex; align-items: center; gap: 16px; font-size: 0.85rem; color: #64748b;">
                                    <div style="display: flex; align-items: center; gap: 6px;">
                                        <i data-lucide="clock" style="width: 14px;"></i>
                                        <span><?= $date->format('H:i') ?></span>
                                    </div>
                                    <div style="display: flex; align-items: center; gap: 6px;">
                                        <i data-lucide="map-pin" style="width: 14px;"></i>
                                        <span>PIB Oliveira</span>
                                    </div>
                                </div>
                            </div>

                            <!-- Corpo Resumido -->
                            <div style="padding: 16px 20px; display: flex; flex-direction: column; gap: 12px;">
                                <?php if (!empty($songs)): ?>
                                    <div style="display: flex; gap: 12px; opacity: 0.8;">
                                        <div style="padding-top: 2px;">
                                            <i data-lucide="music" style="width: 16px; color: #64748b;"></i>
                                        </div>
                                        <div style="flex: 1;">
                                            <div style="display: flex; flex-wrap: wrap; gap: 6px;">
                                                <?php foreach ($songs as $s): ?>
                                                    <span style="font-size: 0.8rem; color: #64748b; background: #f1f5f9; padding: 2px 8px; border-radius: 4px;">
                                                        <?= htmlspecialchars($s['title']) ?>
                                                    </span>
                                                <?php endforeach; ?>
                                                <?php if ($extraSongs > 0): ?>
                                                    <span style="font-size: 0.75rem; color: #94a3b8;">+<?= $extraSongs ?></span>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </a>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Floating Action Button -->
<a href="escala_adicionar.php" class="ripple" style="
        position: fixed; bottom: 32px; right: 24px;
        background: #166534; color: white; padding: 16px; border-radius: 50%;
        display: flex; align-items: center; justify-content: center;
        box-shadow: 0 4px 12px rgba(22, 101, 52, 0.4);
        text-decoration: none; z-index: 50; transition: transform 0.2s;
    " onmouseover="this.style.transform='scale(1.1)'" onmouseout="this.style.transform='scale(1)'">
    <i data-lucide="plus" style="width: 28px; height: 28px;"></i>
</a>


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
            <h3 style="margin: 0; font-size: 1.25rem; font-weight: 700; color: #1e293b;">Filtrar Escalas</h3>
            <button onclick="closeSheet('filterSheet')" style="background: none; border: none; padding: 4px; cursor: pointer; color: #64748b;">
                <i data-lucide="x" style="width: 24px;"></i>
            </button>
        </div>

        <form method="GET" action="escalas.php">
            <!-- Toggle Minhas Escalas -->
            <label style="
                display: flex; align-items: center; justify-content: space-between;
                padding: 16px; background: #f8fafc; border-radius: 12px;
                cursor: pointer; margin-bottom: 20px; border: 1px solid #e2e8f0;
            ">
                <span style="font-weight: 600; color: #334155;">Apenas em que participo</span>
                <input type="checkbox" name="mine" value="1" <?= $filterMine ? 'checked' : '' ?> style="transform: scale(1.5);">
            </label>

            <!-- Tipo de Evento -->
            <div style="margin-bottom: 24px;">
                <label style="display: block; font-size: 0.9rem; font-weight: 600; color: #64748b; margin-bottom: 8px;">Tipo de Evento</label>
                <div style="display: flex; gap: 8px; flex-wrap: wrap;">
                    <?php
                    $types = ['Culto Domingo a Noite', 'Ensaio', 'Culto Jovem', 'Especial'];
                    foreach ($types as $t):
                        $active = $filterType === $t;
                    ?>
                        <label style="cursor: pointer;">
                            <input type="radio" name="type" value="<?= $t ?>" <?= $active ? 'checked' : '' ?> style="display: none;">
                            <div style="
                                padding: 8px 16px; border-radius: 20px; font-size: 0.85rem; font-weight: 600;
                                background: <?= $active ? '#dcfce7' : 'white' ?>;
                                color: <?= $active ? '#166534' : '#64748b' ?>;
                                border: 1px solid <?= $active ? '#166534' : '#e2e8f0' ?>;
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
                    color: #64748b; font-weight: 600; background: #f1f5f9; border-radius: 12px;
                ">Limpar</a>
                <button type="submit" style="
                    flex: 2; padding: 14px; border: none; background: #166534; 
                    color: white; font-weight: 700; border-radius: 12px; font-size: 1rem;
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
            btnFuture.style.color = '#0f172a';
            btnFuture.style.boxShadow = '0 1px 2px rgba(0,0,0,0.1)';

            btnPast.style.background = 'transparent';
            btnPast.style.color = '#64748b';
            btnPast.style.boxShadow = 'none';
        } else {
            tabFuture.style.display = 'none';
            tabPast.style.display = 'block';

            btnFuture.style.background = 'transparent';
            btnFuture.style.color = '#64748b';
            btnFuture.style.boxShadow = 'none';

            btnPast.style.background = 'white';
            btnPast.style.color = '#0f172a';
            btnPast.style.boxShadow = '0 1px 2px rgba(0,0,0,0.1)';
        }
    }

    // View Toggle (Timeline vs List)
    function setView(view) {
        const timeline = document.getElementById('view-timeline');
        const list = document.getElementById('view-list');
        const btnTimeline = document.getElementById('btn-view-timeline');
        const btnList = document.getElementById('btn-view-list');

        // Salvar preferência se quiser (opcional)
        // localStorage.setItem('escalasView', view);

        if (view === 'timeline') {
            if (timeline) timeline.style.display = 'flex';
            if (list) list.style.display = 'none';

            if (btnTimeline) {
                btnTimeline.style.background = 'white';
                btnTimeline.style.color = '#0f172a';
                btnTimeline.style.boxShadow = '0 1px 2px rgba(0,0,0,0.1)';
            }
            if (btnList) {
                btnList.style.background = 'transparent';
                btnList.style.color = '#64748b';
                btnList.style.boxShadow = 'none';
            }
        } else {
            if (timeline) timeline.style.display = 'none';
            if (list) list.style.display = 'flex';

            if (btnTimeline) {
                btnTimeline.style.background = 'transparent';
                btnTimeline.style.color = '#64748b';
                btnTimeline.style.boxShadow = 'none';
            }
            if (btnList) {
                btnList.style.background = 'white';
                btnList.style.color = '#0f172a';
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
        transform: translateY(-2px);
        box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.05);
        border-color: #cbd5e1;
    }

    @media(max-width: 640px) {
        .desktop-only {
            display: none;
        }
    }
</style>

<?php renderAppFooter(); ?>