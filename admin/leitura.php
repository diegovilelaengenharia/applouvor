<?php
// admin/leitura.php (Refatorado Premium 2026)
require_once '../src/helpers/auth.php';
require_once '../src/config/db.php';
require_once '../src/layout/layout.php';
require_once '../src/helpers/reading_plans_data.php';
require_once '../src/helpers/reading_plan.php';

checkLogin();

$userId = $_SESSION['user_id'];

// --- BACKEND: Processar Requisições AJAX POST ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
    $action = $_POST['action'] ?? '';
    
    if ($action === 'select_plan') {
        $planType = $_POST['plan_type'] ?? 'navigators';
        $startDate = $_POST['start_date'] ?? date('Y-m-d');
        
        try {
            $pdo->prepare("INSERT INTO user_settings (user_id, setting_key, setting_value) 
                VALUES (?, 'reading_plan_type', ?) 
                ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)")
                ->execute([$userId, $planType]);
                
            $pdo->prepare("INSERT INTO user_settings (user_id, setting_key, setting_value) 
                VALUES (?, 'reading_plan_start_date', ?) 
                ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)")
                ->execute([$userId, $startDate]);
                
            echo json_encode(['success' => true]);
            exit;
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
            exit;
        }
    }
    
    if ($action === 'save_reading_passage') {
        $planDay = (int)($_POST['plan_day'] ?? 1);
        $passageIndex = (int)($_POST['passage_index'] ?? 0);
        $completed = ($_POST['completed'] ?? 'false') === 'true';
        
        try {
            // Buscar progresso atual deste dia (mapeando planDay para month_num e day_num)
            $m = ceil($planDay / 25);
            $d = $planDay - ($m - 1) * 25;
            
            $stmt = $pdo->prepare("SELECT id, verses_read FROM reading_progress WHERE user_id = ? AND month_num = ? AND day_num = ?");
            $stmt->execute([$userId, $m, $d]);
            $row = $stmt->fetch();
            
            $completedPassages = [];
            if ($row) {
                $completedPassages = json_decode($row['verses_read'] ?? '[]', true) ?: [];
            }
            
            if ($completed) {
                if (!in_array($passageIndex, $completedPassages)) {
                    $completedPassages[] = $passageIndex;
                }
            } else {
                $completedPassages = array_values(array_diff($completedPassages, [$passageIndex]));
            }
            
            $versesReadJson = json_encode($completedPassages);
            
            if ($row) {
                $pdo->prepare("UPDATE reading_progress SET verses_read = ?, completed_at = NOW() WHERE id = ?")
                    ->execute([$versesReadJson, $row['id']]);
            } else {
                $pdo->prepare("INSERT INTO reading_progress (user_id, month_num, day_num, verses_read, comment, note_title, completed_at) 
                    VALUES (?, ?, ?, ?, '', '', NOW())")
                    ->execute([$userId, $m, $d, $versesReadJson]);
            }
            
            echo json_encode(['success' => true]);
            exit;
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
            exit;
        }
    }
    
    if ($action === 'save_diary_note') {
        $planDay = (int)($_POST['plan_day'] ?? 1);
        $comment = trim($_POST['comment'] ?? '');
        
        try {
            $m = ceil($planDay / 25);
            $d = $planDay - ($m - 1) * 25;
            
            $stmt = $pdo->prepare("SELECT id FROM reading_progress WHERE user_id = ? AND month_num = ? AND day_num = ?");
            $stmt->execute([$userId, $m, $d]);
            $row = $stmt->fetch();
            
            if ($row) {
                $pdo->prepare("UPDATE reading_progress SET comment = ?, completed_at = NOW() WHERE id = ?")
                    ->execute([$comment, $row['id']]);
            } else {
                $pdo->prepare("INSERT INTO reading_progress (user_id, month_num, day_num, verses_read, comment, note_title, completed_at) 
                    VALUES (?, ?, ?, '[]', ?, '', NOW())")
                    ->execute([$userId, $m, $d, $comment]);
            }
            
            echo json_encode(['success' => true]);
            exit;
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
            exit;
        }
    }
}

$tab = $_GET['tab'] ?? 'reading';

// --- BACKEND: Carregar Configurações ---
$stmt = $pdo->prepare("SELECT setting_key, setting_value FROM user_settings WHERE user_id = ?");
$stmt->execute([$userId]);
$settings = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

$startDateStr = $settings['reading_plan_start_date'] ?? null;
$selectedPlanType = $settings['reading_plan_type'] ?? null;

// Se não começou o plano, inicializar automaticamente para o plano Navigators (300 dias)
if (empty($startDateStr) || empty($selectedPlanType)) {
    $startDateStr = date('Y-m-d');
    $selectedPlanType = 'navigators';
    try {
        $pdo->prepare("INSERT INTO user_settings (user_id, setting_key, setting_value) 
            VALUES (?, 'reading_plan_type', 'navigators') 
            ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)")
            ->execute([$userId]);
            
        $pdo->prepare("INSERT INTO user_settings (user_id, setting_key, setting_value) 
            VALUES (?, 'reading_plan_start_date', ?) 
            ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)")
            ->execute([$userId, $startDateStr]);
    } catch (Exception $e) {
        // Ignorar
    }
}
$planStarted = true;

// Se não começou o plano, redirecionar logicamente (ou incluir tela de seleção)
if (!$planStarted) {
    // Tela de seleção (simplificada para o novo design)
    renderAppHeader('Novo Plano');
    ?>
    <div class="reading-container">
        <div class="reading-header">
            <h2 class="reading-title">Escolha sua Jornada</h2>
            <p class="reading-subtitle">Selecione um roteiro para guiar sua leitura bíblica.</p>
        </div>
        
        <div class="passages-list">
            <div class="pib-card animate-card" style="animation-delay: 0.1s;" onclick="selectPlan('navigators')">
                <div class="pib-card-header">
                    <span class="pib-card-date">Equilibrado</span>
                </div>
                <h3 class="pib-card-title">Navigators (300 dias)</h3>
                <p class="pib-card-body">25 dias por mês. Ideal para quem tem rotina corrida.</p>
            </div>
            <div class="pib-card animate-card" style="animation-delay: 0.2s;" onclick="selectPlan('chronological')">
                <div class="pib-card-header">
                    <span class="pib-card-date">Histórico</span>
                </div>
                <h3 class="pib-card-title">Cronológico (365 dias)</h3>
                <p class="pib-card-body">Leia a Bíblia na ordem em que os fatos aconteceram.</p>
            </div>
        </div>

        <div style="margin-top: var(--space-xl);">
             <label style="font-weight: 800; font-size: 0.8rem; text-transform: uppercase;">Data de Início</label>
             <input type="date" id="start-date" class="pib-input" value="<?= date('Y-m-d') ?>" style="width: 100%; padding: 12px; border-radius: var(--radius-md); border: 1px solid var(--color-border); margin-top: 8px;">
             <button onclick="confirmStart()" class="btn-hero btn-hero-confirm" style="width: 100%; margin-top: var(--space-md); padding: 16px;">
                 Começar Agora
             </button>
        </div>
    </div>
    <script>
        let selected = 'navigators';
        function selectPlan(p) { selected = p; alert('Selecionado: ' + p); }
        async function confirmStart() {
            const date = document.getElementById('start-date').value;
            const f = new FormData();
            f.append('action', 'select_plan');
            f.append('plan_type', selected);
            f.append('start_date', date);
            const r = await fetch('leitura.php', { method: 'POST', body: f });
            const d = await r.json();
            if(d.success) location.reload();
        }
    </script>
    <?php
    renderAppFooter();
    exit;
}

// --- CALCULATE PROGRESS ---
$start = new DateTime($startDateStr);
$now = new DateTime(); $now->setTime(0,0,0);
$diff = $start->diff($now);
$daysPassed = $diff->invert ? 0 : $diff->days;
$planDayIndex = isset($_GET['day']) ? (int)$_GET['day'] : ($daysPassed + 1);

$planInfo = getPlanInfo($selectedPlanType);
$planDayIndex = max(1, min($planDayIndex, $planInfo['total_days']));

// Fetch Progress Data
$stmt = $pdo->prepare("SELECT month_num, day_num, verses_read, comment, note_title FROM reading_progress WHERE user_id = ?");
$stmt->execute([$userId]);
$allProgress = $stmt->fetchAll(PDO::FETCH_ASSOC);

$totalDaysRead = 0;
$totalChaptersRead = 0;
$currentDayProgress = null;
$progressDays = [];

foreach($allProgress as $p) {
    $m = (int)$p['month_num'];
    $d = (int)$p['day_num'];
    
    // Mapear month_num e day_num para o índice linear de 1 a 300
    if ($m > 0) {
        $computedDay = ($m - 1) * 25 + $d;
    } else {
        // Fallback para registros antigos onde month_num era 0
        $computedDay = $d;
    }
    
    // Obter passagens para esse dia específico para verificar se está completo
    $m_plan = ceil($computedDay / 25);
    $d_plan = $computedDay - ($m_plan - 1) * 25;
    $dayVersesList = $bibleReadingPlan[$m_plan][$d_plan - 1] ?? [];
    
    $verses = json_decode($p['verses_read'] ?? '[]', true) ?: [];
    if (empty($verses) && !empty($dayVersesList)) {
        // Se existe o registro de progresso mas verses_read está em branco (ex: marcado via celular/voluntário),
        // assumimos que todas as passagens daquele dia foram lidas.
        $verses = array_keys($dayVersesList);
    }
    
    if (count($verses) > 0) {
        $totalDaysRead++;
        $totalChaptersRead += count($verses);
        $progressDays[$computedDay] = true;
    }
    
    if ($computedDay == $planDayIndex) {
        $currentDayProgress = $p;
        $currentDayProgress['verses_read'] = json_encode($verses); // Atualiza localmente para marcar os checkboxes
    }
}

$completionPercent = round(($totalDaysRead / $planInfo['total_days']) * 100);

// Streak: dias consecutivos com pelo menos 1 passagem lida (walk back do plan day atual)
$currentStreak = 0;
$todayPlanDay = $daysPassed + 1;
$startCheck = isset($progressDays[$todayPlanDay]) ? $todayPlanDay : $todayPlanDay - 1;
for ($d = $startCheck; $d >= 1; $d--) {
    if (isset($progressDays[$d])) {
        $currentStreak++;
    } else {
        break;
    }
}

// Obter passagens do dia atual usando o plano unificado
$todayReadings = [];
$m_curr = ceil($planDayIndex / 25);
$d_curr = $planDayIndex - ($m_curr - 1) * 25;
$passagesList = $bibleReadingPlan[$m_curr][$d_curr - 1] ?? [];
foreach ($passagesList as $passage) {
    $todayReadings[] = [
        'reference' => $passage,
        'link' => getBibleLink($passage)
    ];
}
$completedPassages = json_decode($currentDayProgress['verses_read'] ?? '[]', true) ?: [];

renderAppHeader('Leitura Bíblica');
?>

<link rel="stylesheet" href="../assets/css/pages/leitura.css?v=<?= time() ?>">

<!-- Tabs Navegação Premium -->
<div class="repertorio-controls">
    <div class="tabs-container" style="display: flex; align-items: center; gap: 0.75rem; overflow-x: auto; padding-bottom: 4px; -webkit-overflow-scrolling: touch;">
        <a href="?tab=reading" class="tab-link <?= $tab == 'reading' ? 'active' : '' ?>" style="white-space: nowrap; display: flex; align-items: center; gap: 6px;">
            <i data-lucide="book-open" width="16"></i> Texto Bíblico
        </a>
        <a href="?tab=dashboard" class="tab-link <?= $tab == 'dashboard' ? 'active' : '' ?>" style="white-space: nowrap; display: flex; align-items: center; gap: 6px;">
            <i data-lucide="bar-chart-3" width="16"></i> Estatísticas
        </a>
        <a href="?tab=achievements" class="tab-link <?= $tab == 'achievements' ? 'active' : '' ?>" style="white-space: nowrap; display: flex; align-items: center; gap: 6px;">
            <i data-lucide="award" width="16"></i> Conquistas
        </a>
    </div>
    
    <button onclick="appSettingsOpen()" class="btn-settings-floating" title="Configurações">
        <i data-lucide="settings" width="20"></i>
    </button>
</div>

<div class="reading-container">

    <?php if ($tab == 'reading'): ?>
        <!-- HEADER DO DIA -->
        <div class="reading-header animate-card">
            <div class="reading-title">
                <i data-lucide="calendar-days" style="color: var(--reading-primary);"></i>
                Dia <?= $planDayIndex ?> de <?= $planInfo['total_days'] ?>
            </div>
            <p class="reading-subtitle"><?= $planInfo['title'] ?></p>
        </div>

        <!-- NAVEGADOR DE DIAS -->
        <div class="day-navigator animate-card" style="animation-delay: 0.1s;">
            <a href="?tab=reading&day=<?= $planDayIndex - 1 ?>" class="nav-btn <?= $planDayIndex <= 1 ? 'disabled' : '' ?>">
                <i data-lucide="chevron-left"></i>
            </a>
            <div class="current-day">HOJE</div>
            <a href="?tab=reading&day=<?= $planDayIndex + 1 ?>" class="nav-btn <?= $planDayIndex >= $planInfo['total_days'] ? 'disabled' : '' ?>">
                <i data-lucide="chevron-right"></i>
            </a>
        </div>

        <!-- LISTA DE PASSAGENS -->
        <div class="passages-list">
            <?php 
            $delay = 0.2;
            foreach ($todayReadings as $idx => $reading): 
                $isDone = in_array($idx, $completedPassages);
            ?>
            <div class="animate-card" style="animation-delay: <?= $delay ?>s;">
                <div class="pib-card <?= $isDone ? 'status-complete' : '' ?>" style="border-left: 5px solid <?= $isDone ? 'var(--reading-primary)' : 'var(--color-border)' ?>;">
                    <div class="passage-header">
                        <input type="checkbox" class="passage-checkbox" <?= $isDone ? 'checked' : '' ?> 
                               onchange="togglePassage(<?= $planDayIndex ?>, <?= $idx ?>, this.checked)">
                        <div class="passage-title"><?= htmlspecialchars($reading['reference']) ?></div>
                        <a href="<?= htmlspecialchars($reading['link']) ?>" target="_blank" class="btn-passage btn-passage-primary">
                            <i data-lucide="external-link" width="14"></i> Ler
                        </a>
                    </div>
                </div>
            </div>
            <?php $delay += 0.05; endforeach; ?>
        </div>

        <!-- ANOTAÇÕES RÁPIDAS -->
        <div class="animate-card" style="animation-delay: 0.4s; margin-top: var(--space-xl);">
            <div class="pib-card" style="background: var(--color-surface-alt);">
                <div class="pib-card-header">
                    <h3 class="pib-card-title" style="font-size: 0.9rem;">Minhas Reflexões</h3>
                    <i data-lucide="edit-3" width="16" style="color: var(--reading-primary);"></i>
                </div>
                <textarea id="quick-note" class="pib-input" style="width: 100%; border: none; background: transparent; resize: none;" rows="3" placeholder="O que Deus falou com você hoje?" onblur="saveNote(<?= $planDayIndex ?>)"><?= htmlspecialchars($currentDayProgress['comment'] ?? '') ?></textarea>
            </div>
        </div>

    <?php elseif ($tab == 'dashboard'): ?>
        <!-- ESTATÍSTICAS -->
        <div class="reading-stats-grid">
            <div class="animate-card" style="animation-delay: 0.1s;">
                <div class="stat-card-compact">
                    <div class="stat-header">
                        <div class="stat-icon-compact"><i data-lucide="flame"></i></div>
                        <span class="stat-title-compact">Sequência</span>
                    </div>
                    <div class="stat-value-compact"><?= $currentStreak ?></div>
                    <div class="stat-label-compact">dias seguidos</div>
                </div>
            </div>
            <div class="animate-card" style="animation-delay: 0.15s;">
                <div class="stat-card-compact">
                    <div class="stat-header">
                        <div class="stat-icon-compact" style="color: #8b5cf6;"><i data-lucide="book-open"></i></div>
                        <span class="stat-title-compact">Capítulos</span>
                    </div>
                    <div class="stat-value-compact"><?= $totalChaptersRead ?></div>
                    <div class="stat-label-compact">lidos no total</div>
                </div>
            </div>
            <div class="animate-card" style="animation-delay: 0.2s;">
                <div class="stat-card-compact">
                    <div class="stat-header">
                        <div class="stat-icon-compact" style="color: var(--reading-primary);"><i data-lucide="target"></i></div>
                        <span class="stat-title-compact">Progresso</span>
                    </div>
                    <div class="stat-value-compact"><?= $completionPercent ?>%</div>
                    <div class="stat-label-compact">da Bíblia</div>
                </div>
            </div>
        </div>
    <?php endif; ?>

</div>

<script>
    async function togglePassage(day, idx, checked) {
        const f = new FormData();
        f.append('action', 'save_reading_passage');
        f.append('plan_day', day);
        f.append('passage_index', idx);
        f.append('completed', checked);
        await fetch('leitura.php', { method: 'POST', body: f });
        // Feedback visual imediato já tratado pelo checkbox, mas podemos recarregar stats se quiser
    }

    async function saveNote(day) {
        const note = document.getElementById('quick-note').value;
        const f = new FormData();
        f.append('action', 'save_diary_note');
        f.append('plan_day', day);
        f.append('comment', note);
        await fetch('leitura.php', { method: 'POST', body: f });
    }
</script>

<style>
    .pib-input {
        font-family: inherit;
        font-size: 0.95rem;
        color: var(--color-text);
        outline: none;
    }
    .btn-settings-floating {
        position: absolute; right: 12px; top: 50%; transform: translateY(-50%); 
        background: var(--color-surface); border: 1px solid var(--color-border); 
        width: 38px; height: 38px; border-radius: 50%; color: var(--color-text-muted); 
        display: flex; align-items: center; justify-content: center; box-shadow: var(--shadow-sm);
    }
</style>

<?php renderAppFooter(); ?>
