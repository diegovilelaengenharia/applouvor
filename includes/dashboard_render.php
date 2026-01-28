<?php
/**
 * Funções helper para renderizar cards dinamicamente no dashboard
 */

// Renderizar card de Escalas
function renderCardEscalas($nextSchedule, $totalSchedules) {
    ?>
    <a href="escalas.php" class="access-card card-blue">
        <div>
            <div class="card-icon">
                <i data-lucide="calendar" style="width: 24px; height: 24px;"></i>
            </div>
            <h3 class="card-title">Escalas</h3>
            <p class="card-info">
                <?php if ($nextSchedule): 
                    $date = new DateTime($nextSchedule['event_date']);
                    echo $date->format('d/m') . ' - ' . htmlspecialchars($nextSchedule['event_type']);
                else: ?>
                    Nenhuma escala próxima
                <?php endif; ?>
            </p>
        </div>
        <?php if ($totalSchedules > 0): ?>
            <span class="card-badge"><?= $totalSchedules ?></span>
        <?php endif; ?>
    </a>
    <?php
}

// Renderizar card de Repertório
function renderCardRepertorio($ultimaMusica, $totalMusicas) {
    ?>
    <a href="repertorio.php" class="access-card card-purple">
        <div>
            <div class="card-icon">
                <i data-lucide="music" style="width: 24px; height: 24px;"></i>
            </div>
            <h3 class="card-title">Repertório</h3>
            <p class="card-info">
                <?php if ($ultimaMusica): ?>
                    <?= htmlspecialchars($ultimaMusica['title']) ?>
                <?php else: ?>
                    Nenhuma música cadastrada
                <?php endif; ?>
            </p>
        </div>
        <?php if ($totalMusicas > 0): ?>
            <span class="card-badge"><?= $totalMusicas ?></span>
        <?php endif; ?>
    </a>
    <?php
}

// Renderizar card de Leitura
function renderCardLeitura($pdo, $userId) {
    require_once '../includes/reading_plan.php';
    
    $stmtSet = $pdo->prepare("SELECT setting_value FROM user_settings WHERE user_id = ? AND setting_key = 'reading_plan_start_date'");
    $stmtSet->execute([$userId]);
    $sDate = $stmtSet->fetchColumn() ?: date('Y-01-01');
    
    $startDateTime = new DateTime($sDate);
    $nowDateTime = new DateTime();
    $startDateTime->setTime(0,0,0); 
    $nowDateTime->setTime(0,0,0);
    
    $daysSinceStart = (int)$startDateTime->diff($nowDateTime)->format('%r%a');
    $planDayExpected = max(1, $daysSinceStart + 1); 
    
    $stmtProg = $pdo->prepare("SELECT month_num, day_num, verses_read FROM reading_progress WHERE user_id = ?");
    $stmtProg->execute([$userId]);
    $userProgress = [];
    while($row = $stmtProg->fetch(PDO::FETCH_ASSOC)) {
        $userProgress["{$row['month_num']}-{$row['day_num']}"] = json_decode($row['verses_read'], true) ?? [];
    }
    
    $displayDayGlobal = 1;
    for ($d = 1; $d <= 365; $d++) {
        $m = floor(($d - 1) / 25) + 1;
        $dayInMonth = (($d - 1) % 25) + 1;
        $key = "$m-$dayInMonth";
        $versesRead = $userProgress[$key] ?? [];
        $totalVersesForDay = count($READING_PLAN[$m][$dayInMonth] ?? []);
        $readVersesCount = count($versesRead);
        if ($readVersesCount < $totalVersesForDay) {
            $displayDayGlobal = $d;
            break;
        }
    }
    
    $currentMonth = floor(($displayDayGlobal - 1) / 25) + 1;
    $currentDayInMonth = (($displayDayGlobal - 1) % 25) + 1;
    $todayVerses = $READING_PLAN[$currentMonth][$currentDayInMonth] ?? [];
    $todayKey = "$currentMonth-$currentDayInMonth";
    $todayRead = $userProgress[$todayKey] ?? [];
    $todayProgress = count($todayRead);
    $todayTotal = count($todayVerses);
    $percentToday = $todayTotal > 0 ? round(($todayProgress / $todayTotal) * 100) : 0;
    ?>
    <a href="leitura.php" class="access-card card-cyan">
        <div>
            <div class="card-icon">
                <i data-lucide="book-open" style="width: 24px; height: 24px;"></i>
            </div>
            <h3 class="card-title">Leitura</h3>
            <p class="card-info">Dia <?= $displayDayGlobal ?> • <?= $percentToday ?>% concluído</p>
        </div>
    </a>
    <?php
}

// Renderizar card de Avisos
function renderCardAvisos($ultimoAviso, $unreadCount) {
    ?>
    <a href="avisos.php" class="access-card card-orange">
        <div>
            <div class="card-icon">
                <i data-lucide="bell" style="width: 24px; height: 24px;"></i>
            </div>
            <h3 class="card-title">Avisos</h3>
            <p class="card-info"><?= htmlspecialchars($ultimoAviso) ?></p>
        </div>
        <?php if ($unreadCount > 0): ?>
            <span class="card-badge"><?= $unreadCount ?></span>
        <?php endif; ?>
    </a>
    <?php
}

// Renderizar card de Aniversariantes
function renderCardAniversariantes($niverCount) {
    ?>
    <a href="aniversarios.php" class="access-card card-pink">
        <div>
            <div class="card-icon">
                <i data-lucide="cake" style="width: 24px; height: 24px;"></i>
            </div>
            <h3 class="card-title">Aniversariantes</h3>
            <p class="card-info"><?= $niverCount ?> neste mês</p>
        </div>
    </a>
    <?php
}

// Renderizar card de Devocional
function renderCardDevocional() {
    ?>
    <a href="devocionais.php" class="access-card card-violet">
        <div>
            <div class="card-icon">
                <i data-lucide="sunrise" style="width: 24px; height: 24px;"></i>
            </div>
            <h3 class="card-title">Devocional</h3>
            <p class="card-info">Reflexão diária</p>
        </div>
    </a>
    <?php
}

// Renderizar card de Oração
function renderCardOracao() {
    ?>
    <a href="oracao.php" class="access-card card-rose">
        <div>
            <div class="card-icon">
                <i data-lucide="heart" style="width: 24px; height: 24px;"></i>
            </div>
            <h3 class="card-title">Oração</h3>
            <p class="card-info">Pedidos e intercessão</p>
        </div>
    </a>
    <?php
}

// Renderizar card genérico para cards ainda não implementados
function renderCardGeneric($cardId, $cardDef) {
    $colorClass = match($cardDef['category']) {
        'Gestão' => 'card-blue',
        'Espírito' => 'card-cyan',
        'Comunica' => 'card-orange',
        'Admin' => 'card-red',
        'Extras' => 'card-gray',
        default => 'card-blue'
    };
    ?>
    <a href="<?= $cardDef['url'] ?>" class="access-card <?= $colorClass ?>">
        <div>
            <div class="card-icon">
                <i data-lucide="<?= $cardDef['icon'] ?>" style="width: 24px; height: 24px;"></i>
            </div>
            <h3 class="card-title"><?= htmlspecialchars($cardDef['title']) ?></h3>
            <p class="card-info"><?= htmlspecialchars($cardDef['category']) ?></p>
        </div>
    </a>
    <?php
}

// Função principal para renderizar card baseado no ID
function renderDashboardCard($cardId, $data) {
    global $allCardsDefinitions;
    
    switch($cardId) {
        case 'escalas':
            renderCardEscalas($data['nextSchedule'], $data['totalSchedules']);
            break;
        case 'repertorio':
            renderCardRepertorio($data['ultimaMusica'], $data['totalMusicas']);
            break;
        case 'leitura':
            renderCardLeitura($data['pdo'], $data['userId']);
            break;
        case 'avisos':
            renderCardAvisos($data['ultimoAviso'], $data['unreadCount']);
            break;
        case 'aniversariantes':
            renderCardAniversariantes($data['niverCount']);
            break;
        case 'devocional':
            renderCardDevocional();
            break;
        case 'oracao':
            renderCardOracao();
            break;
        default:
            // Para cards ainda não implementados, renderizar card genérico
            if (isset($allCardsDefinitions[$cardId])) {
                renderCardGeneric($cardId, $allCardsDefinitions[$cardId]);
            }
            break;
    }
}
