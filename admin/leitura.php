<?php
// admin/leitura.php


require_once '../includes/auth.php';
require_once '../includes/layout.php';

checkLogin(); 

// AUTOLOAD: Título na Tabela
try {
    $check = $pdo->query("SHOW COLUMNS FROM reading_progress LIKE 'note_title'");
    if ($check->rowCount() == 0) $pdo->exec("ALTER TABLE reading_progress ADD COLUMN note_title VARCHAR(255) DEFAULT NULL");
} catch(Exception $e) { /* Ignore */ }

// BACKEND: Logic
$userId = $_SESSION['user_id'];
$now = new DateTime();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
    $action = $_POST['action'] ?? '';

    if ($action === 'save_settings') {
        try {
            $pdo->prepare("INSERT INTO user_settings (user_id, setting_key, setting_value) VALUES (?, 'reading_plan_start_date', ?) ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)")->execute([$userId, $_POST['start_date']]);
            echo json_encode(['success' => true]);
        } catch (Exception $e) { echo json_encode(['success'=>false]); }
        exit;
    }
    if ($action === 'save_progress') {
        $m = (int)$_POST['month']; 
        $d = (int)$_POST['day']; 
        $comment = $_POST['comment'] ?? null; 
        $title = $_POST['note_title'] ?? null; 
        $versesRaw = $_POST['verses'] ?? '[]';

        // Validate JSON
        $decoded = json_decode($versesRaw);
        if ($decoded === null && json_last_error() !== JSON_ERROR_NONE) {
            echo json_encode(['success'=>false, 'error'=>'Invalid JSON data']);
            exit;
        }

        try {
            // Check if verses is empty and there are no comments/title
            $versesArray = is_array($decoded) ? $decoded : [];
            $hasVerses = count($versesArray) > 0;
            $hasComment = !empty($comment);
            $hasTitle = !empty($title);
            
            // If nothing is marked and no comments, DELETE the record
            if (!$hasVerses && !$hasComment && !$hasTitle) {
                $pdo->prepare("DELETE FROM reading_progress WHERE user_id = ? AND month_num = ? AND day_num = ?")
                    ->execute([$userId, $m, $d]);
                echo json_encode(['success' => true, 'deleted' => true]);
                exit;
            }
            
            // Otherwise, save/update the record
            if ($comment !== null || $title !== null) {
                // Ensure text fields are not excessively long (basic sanity check)
                if ($comment && strlen($comment) > 5000) $comment = substr($comment, 0, 5000);
                if ($title && strlen($title) > 255) $title = substr($title, 0, 255);

                $sql = "INSERT INTO reading_progress (user_id, month_num, day_num, verses_read, completed_at"; 
                $vals = "VALUES (?, ?, ?, ?, NOW()"; 
                $updates = "verses_read = VALUES(verses_read), completed_at = NOW()"; 
                $params = [$userId, $m, $d, $versesRaw];
                
                if($comment !== null) { $sql .= ", comment"; $vals .= ", ?"; $updates .= ", comment = VALUES(comment)"; $params[] = $comment; }
                if($title !== null) { $sql .= ", note_title"; $vals .= ", ?"; $updates .= ", note_title = VALUES(note_title)"; $params[] = $title; }
                
                $sql .= ") $vals) ON DUPLICATE KEY UPDATE $updates";
            } else {
                 $sql = "INSERT INTO reading_progress (user_id, month_num, day_num, verses_read, completed_at) VALUES (?, ?, ?, ?, NOW()) ON DUPLICATE KEY UPDATE verses_read = VALUES(verses_read), completed_at = NOW()";
                $params = [$userId, $m, $d, $versesRaw];
            }
            $pdo->prepare($sql)->execute($params);
            echo json_encode(['success' => true]);
        } catch (Exception $e) { 
            // Return actual error only if strictly necessary, otherwise generic
            error_log($e->getMessage()); // Log error on server
            echo json_encode(['success'=>false, 'error'=>'Database error']); 
        }
        exit;
    }

    if ($action === 'reset_plan') { 
        // Delete reading progress
        $pdo->prepare("DELETE FROM reading_progress WHERE user_id = ?")->execute([$userId]); 
        // Delete user settings (pauses the plan)
        $pdo->prepare("DELETE FROM user_settings WHERE user_id = ?")->execute([$userId]); 
        echo json_encode(['success'=>true]); 
        exit; 
    }
    
    if ($action === 'start_plan') {
        // Set start date to today
        $today = date('Y-m-d');
        $pdo->prepare("INSERT INTO user_settings (user_id, setting_key, setting_value) VALUES (?, 'reading_plan_start_date', ?) ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)")->execute([$userId, $today]);
        echo json_encode(['success'=>true, 'date'=>$today]); 
        exit;
    }
}

$stmt = $pdo->prepare("SELECT setting_key, setting_value FROM user_settings WHERE user_id = ?"); $stmt->execute([$userId]); $settings = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
$startDateStr = $settings['reading_plan_start_date'] ?? null;
$planStarted = !empty($startDateStr); // Check if plan has been started


// Only calculate if plan has started
if ($planStarted) {
    $start = new DateTime($startDateStr); $start->setTime(0,0,0); $now->setTime(0,0,0);
    $diff = $start->diff($now); $daysPassed = $diff->invert ? -1*$diff->days : $diff->days;
    $planDayIndex = max(1, $daysPassed + 1);
    $currentPlanMonth = floor(($planDayIndex - 1) / 25) + 1; $currentPlanDay = (($planDayIndex - 1) % 25) + 1;
    if($currentPlanMonth>12){ $currentPlanMonth=12; $currentPlanDay=25; }
} else {
    // Plan not started - default to month 1, day 1
    $currentPlanMonth = 1;
    $currentPlanDay = 1;
}

$stmt = $pdo->prepare("SELECT month_num, day_num, verses_read, comment, note_title, completed_at FROM reading_progress WHERE user_id = ? ORDER BY completed_at DESC");
$stmt->execute([$userId]); $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

$progressMap = []; $totalChaptersRead = 0; $totalDaysRead = 0; $reportData = [];
foreach($rows as $r) {
    $verses = json_decode($r['verses_read'] ?? '[]', true); if(!is_array($verses)) $verses=[];
    
    // Count individual chapters read
    $chaptersInThisDay = count($verses);
    $totalChaptersRead += $chaptersInThisDay;
    
    // Count days that have at least one chapter read
    if($chaptersInThisDay > 0 || !empty($r['completed_at'])) $totalDaysRead++;
    
    $k = "{$r['month_num']}_{$r['day_num']}";
    $progressMap[$k] = ['verses'=>$verses, 'comment'=>$r['comment']??'', 'title'=>$r['note_title']??'', 'date'=>$r['completed_at']];
    if(count($verses)>0 || !empty($r['comment']) || !empty($r['note_title'])) {
        $reportData[] = ['m'=>(int)$r['month_num'], 'd'=>(int)$r['day_num'], 'date'=>$r['completed_at'], 'comment'=>$r['comment'], 'title'=>$r['note_title']??''];
    }
}
$completionPercent = min(100, round(($totalDaysRead / 300) * 100));

// --- CALCULAR STREAK E ESTATÍSTICAS MOTIVACIONAIS ---
$currentStreak = 0;
$bestStreak = 0;
$today = new DateTime(); 
$today->setTime(0,0,0);

// Verificar streak retroativo e calcular melhor sequência
$checkDate = clone $today;
$streakCount = 0;
$tempStreak = 0;
$allStreaks = [];

// Verifica até 365 dias para trás
for($i=0; $i<365; $i++) {
    $foundReading = false;
    foreach($reportData as $rep) {
        $rDate = new DateTime($rep['date']);
        $rDate->setTime(0,0,0);
        if($rDate == $checkDate) {
            $foundReading = true;
            break;
        }
    }
    
    if($foundReading) {
        $streakCount++;
        $tempStreak++;
        $checkDate->modify('-1 day');
    } else {
        // Se for hoje e não leu, não quebra o streak de ontem
        if($i === 0) {
            $checkDate->modify('-1 day');
            continue;
        }
        // Salvar streak atual e resetar
        if($tempStreak > 0) {
            $allStreaks[] = $tempStreak;
            $tempStreak = 0;
        }
        break;
    }
}
// Adicionar último streak se existir
if($tempStreak > 0) $allStreaks[] = $tempStreak;

$currentStreak = $streakCount;
$bestStreak = !empty($allStreaks) ? max($allStreaks) : $currentStreak;

// --- PROJEÇÕES E RITMO DE LEITURA ---
$daysInPlan = $planStarted ? max(1, $daysPassed) : 1;
$avgChaptersPerDay = $totalChaptersRead / $daysInPlan;
$avgDaysPerDay = $totalDaysRead / $daysInPlan;

// Meta ideal: 300 dias em 365 dias = ~0.82 dias/dia, ou ~3 capítulos/dia
$idealDaysPerDay = 300 / 365;
$idealChaptersPerDay = 3.0;

// Dias restantes para completar
$daysRemaining = 300 - $totalDaysRead;

// Projeção de conclusão (baseada no ritmo atual)
$estimatedDaysToComplete = $avgDaysPerDay > 0 ? ceil($daysRemaining / $avgDaysPerDay) : 999;
$estimatedCompletionDate = null;
if($planStarted && $avgDaysPerDay > 0 && $estimatedDaysToComplete < 999) {
    $estimatedCompletionDate = (new DateTime())->modify("+{$estimatedDaysToComplete} days")->format('d/m/Y');
}

// Comparação com meta
$paceComparison = $avgDaysPerDay >= $idealDaysPerDay ? 'adiantado' : 'no ritmo';
if($avgDaysPerDay < ($idealDaysPerDay * 0.7)) $paceComparison = 'pode acelerar';

// --- MENSAGENS MOTIVACIONAIS DINÂMICAS ---
$motivationalMessages = [
    0 => "🌱 Você começou! Cada jornada começa com um passo. Continue firme!",
    10 => "💪 Incrível! Você já leu {$totalChaptersRead} capítulos. A persistência está valendo a pena!",
    25 => "🌟 Você está no caminho certo! Já completou 1/4 da jornada!",
    50 => "✨ Mais da metade concluída! Sua dedicação é inspiradora!",
    75 => "🎯 Quase lá! Você está tão perto de completar esta jornada!",
    90 => "🏆 Reta final! Você é um exemplo de perseverança!"
];

$currentMessage = $motivationalMessages[0];
foreach($motivationalMessages as $threshold => $message) {
    if($completionPercent >= $threshold) {
        $currentMessage = $message;
    }
}

// --- CONQUISTAS E BADGES ---
$achievements = [];
if($currentStreak >= 7) $achievements[] = ['icon' => '🔥', 'text' => '7 dias seguidos!'];
if($currentStreak >= 30) $achievements[] = ['icon' => '💎', 'text' => '30 dias seguidos!'];
if($totalChaptersRead >= 50) $achievements[] = ['icon' => '📚', 'text' => '50 capítulos!'];
if($totalChaptersRead >= 100) $achievements[] = ['icon' => '⭐', 'text' => '100 capítulos!'];
if($totalDaysRead >= 30) $achievements[] = ['icon' => '📅', 'text' => '1 mês completo!'];
if($totalDaysRead >= 90) $achievements[] = ['icon' => '🎊', 'text' => '3 meses!'];
if($completionPercent >= 25) $achievements[] = ['icon' => '🥉', 'text' => '25% concluído!'];
if($completionPercent >= 50) $achievements[] = ['icon' => '🥈', 'text' => '50% concluído!'];
if($completionPercent >= 75) $achievements[] = ['icon' => '🥇', 'text' => '75% concluído!'];

// Média de Leitura (Capítulos / Dias passados desde o início)
$avgChapters = round($avgChaptersPerDay, 1);

// Média de Leitura (Capítulos / Dias passados desde o início)
$daysSinceStart = max(1, $daysPassed);
$avgChapters = round($totalChaptersRead / $daysSinceStart, 1);
// --------------------------------------

// RENDER: Mobile Header & Layout
renderAppHeader('Leitura Bíblica'); 
renderPageHeader('Plano de Leitura Bíblica Anual', 'Louvor PIB Oliveira');
?>

<!-- FRONTEND -->
<script src="../assets/js/reading_plan_data.js?v=<?= time() ?>"></script>
<script src="https://unpkg.com/lucide@latest"></script>

<style>
    :root {
        --primary: #6366f1; --primary-soft: #e0e7ff; --success: #10b981; --success-soft: #d1fae5;
        --warning: #f59e0b; --warning-soft: #fef3c7; --surface: #ffffff; --bg: #f8fafc;
        --text: #1e293b; --text-light: #64748b; --border: #e2e8f0;
    }
    body { background-color: var(--gray-50, #f8fafc); color: var(--gray-900, #1e293b); padding-bottom: 70px; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; }

    /* Calendar Strip */
    .cal-strip {
        display: flex !important; flex-direction: row !important; flex-wrap: nowrap !important;
        gap: 8px; overflow-x: auto; padding: 10px 12px;
        background: white; border-bottom: 1px solid var(--gray-200, #e5e7eb);
        scrollbar-width: none;
    }
    .cal-strip::-webkit-scrollbar { display: none; }
    .cal-item {
        min-width: 56px; height: 68px; border-radius: 12px; background: var(--gray-100, #f3f4f6); border: 2px solid transparent; 
        display: flex; flex-direction: column; align-items: center; justify-content: center; flex-shrink: 0; cursor: pointer; transition: all 0.2s;
    }
    .cal-month { font-size: 0.65rem; font-weight: 700; text-transform: uppercase; color: var(--gray-500, #6b7280); letter-spacing: 0.5px; }
    .cal-num { font-size: 1.25rem; font-weight: 800; color: var(--gray-800, #1f2937); }
    /* ACTIVE STATE */
    .cal-item.active { background: white; border-color: var(--primary-500, #047857); box-shadow: 0 2px 8px rgba(4, 120, 87, 0.15); }
    .cal-item.active .cal-num { color: var(--primary-600, #065f46); }
    .cal-item.active .cal-month { color: var(--primary-600, #065f46); }
    
    /* DONE STATE */
    .cal-item.done { background: var(--success-light, #d1fae5); border-color: transparent !important; }
    .cal-item.done .cal-num { color: var(--success-dark, #047857); }
    .cal-item.done .cal-month { color: var(--success-dark, #047857); }
    .cal-item.active.done { border-color: var(--success, #10b981) !important; box-shadow: 0 2px 8px rgba(16, 185, 129, 0.2); }

    /* PARTIAL/PENDING STATE (Yellow) */
    .cal-item.partial { background: var(--warning-light, #fef3c7); border-color: transparent; }
    .cal-item.partial .cal-num { color: var(--warning-dark, #d97706); }
    .cal-item.partial .cal-month { color: var(--warning-dark, #d97706); }
    
    /* ACTIVE AND PENDING (Fix priority) */
    .cal-item.active.partial { 
        background: #fffbeb !important; 
        border-color: #f59e0b !important; 
        box-shadow: 0 4px 12px rgba(245, 158, 11, 0.3); 
    }
    .cal-item.active.partial .cal-num { color: #d97706; }
    
    /* Progress Indicator */
    .cal-progress {
        font-size: 0.65rem;
        color: #64748b;
        font-weight: 600;
        margin-top: 4px;
        line-height: 1;
    }
    .cal-item.partial .cal-progress {
        color: #d97706;
    }
    .cal-item.done .cal-progress {
        color: #047857;
    }
    
    .main-area { max-width: 800px; margin: 0 auto; padding: 16px 12px; }

    /* DAY HEADER CARD (Refined for Project Consistency) */
    .day-header-card {
        background: white;
        border: 1px solid var(--gray-200, #e5e7eb);
        border-radius: 12px;
        padding: 16px 20px;
        margin-bottom: 16px;
        display: flex;
        align-items: center;
        justify-content: space-between;
        box-shadow: 0 1px 3px rgba(0,0,0,0.05);
    }
    .day-header-info { display: flex; flex-direction: column; gap: 4px; }
    
    .day-header-label { 
        font-size: 0.7rem; 
        text-transform: uppercase; 
        letter-spacing: 0.5px; 
        color: var(--gray-500, #6b7280);
        font-weight: 700;
        margin-bottom: 4px;
    }
    
    .day-header-title { 
        font-size: 1.5rem;
        font-weight: 800; 
        color: var(--gray-900, #111827); 
        line-height: 1.1; 
        letter-spacing: -0.5px;
    }
    
    /* Status Badge Refined */
    .status-badge { 
        font-size: 0.7rem; font-weight: 700; padding: 6px 10px; border-radius: 8px;
        text-transform: uppercase; letter-spacing: 0.5px; display: inline-flex; align-items: center; gap: 4px;
    }
    .status-badge.success { background: var(--success-light, #d1fae5); color: var(--success-dark, #047857); }
    .status-badge.pending { background: var(--warning-light, #fef3c7); color: var(--warning-dark, #d97706); }
    
    .verse-card {
        background: white; border: 1px solid var(--gray-200, #e5e7eb); border-radius: 12px; padding: 12px 14px; margin-bottom: 10px;
        display: flex; align-items: center; justify-content: space-between; cursor: pointer; transition: all 0.2s;
    }
    .verse-card:active { transform: scale(0.98); }
    .verse-card.read { background: var(--success-light, #d1fae5); border-color: var(--success-light, #d1fae5); }
    .verse-card.read .check-icon { background: var(--success); border-color: var(--success); color: white; }
    .check-icon { width: 22px; height: 22px; border-radius: 50%; border: 2px solid var(--gray-300, #d1d5db); color: transparent; display: flex; align-items: center; justify-content: center; margin-right: 10px; flex-shrink: 0; }
    .btn-read-link { background: var(--primary-500, #047857); color: white; padding: 6px 12px; border-radius: 8px; text-decoration: none; font-weight: 700; font-size: 0.7rem; display: flex; align-items: center; gap: 4px; transition: all 0.2s; }
    .btn-read-link:hover { background: var(--primary-600, #065f46); }

    /* Modais Fixed */
    .modal-overlay {
        position: fixed !important; top: 0 !important; left: 0 !important; right: 0 !important; bottom: 0 !important;
        width: 100vw; height: 100vh; background: rgba(0,0,0,0.5); backdrop-filter: blur(4px); z-index: 9999;
        display: none; align-items: center; justify-content: center;
    }
    .config-fullscreen {
        position: fixed !important; top: 0 !important; left: 0 !important; right: 0 !important; bottom: 0 !important;
        width: 100vw; height: 100vh; background: var(--bg-body); z-index: 99999;
        display: none; flex-direction: column; overflow-y: auto;
    }
    .config-header { background: var(--surface); padding: 16px 20px; border-bottom: 1px solid var(--border); display: flex; justify-content: space-between; align-items: center; }
    .config-tabs { display: flex; background: var(--surface); border-bottom: 1px solid var(--border); padding: 0 20px; }
    .tab-btn { padding: 16px 20px; font-weight: 600; color: var(--text-light); border-bottom: 2px solid transparent; cursor: pointer; }
    .tab-btn.active { color: var(--primary); border-bottom-color: var(--primary); }
    .config-content { padding: 20px; max-width: 800px; margin: 0 auto; width: 100%; }
    .report-item { background: var(--surface); border-radius: 12px; padding: 16px; margin-bottom: 12px; border: 1px solid var(--border); }
    .bottom-bar { position: fixed; bottom: 0; left: 0; right: 0; background: rgba(255,255,255,0.95); backdrop-filter: blur(12px); border-top: 1px solid var(--gray-200, #e5e7eb); padding: 10px 12px; padding-bottom: calc(10px + env(safe-area-inset-bottom)); z-index: 200; display: grid; grid-template-columns: 1fr 1fr; gap: 10px; max-width: 800px; margin: 0 auto; }
    @media (min-width: 1024px) { .bottom-bar { left: 280px; } }
    .action-btn { background: white; border: 1px solid var(--gray-200, #e5e7eb); padding: 10px; border-radius: 10px; display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 4px; cursor: pointer; transition: all 0.2s; }
    .action-btn:active { transform: scale(0.95); background: var(--gray-50, #f9fafb); }
    .action-btn span { font-size: 0.7rem; font-weight: 600; color: var(--gray-700, #374151); }
    .icon-box { width: 32px; height: 32px; border-radius: 10px; display: flex; align-items: center; justify-content: center; }
    .icon-box.purple { background: #f3e8ff; color: #9333ea; } .icon-box.blue { background: #e0f2fe; color: #0284c7; }

    /* STATS DASHBOARD */
    .stats-dashboard {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(130px, 1fr));
        gap: 12px;
        padding: 0 20px 20px 20px; /* Padding ajustado para ficar dentro do fluxo */
        background: var(--bg-surface);
        border-bottom: 1px solid var(--border);
        margin-bottom: 0;
    }
    .stat-card {
        background: white;
        border: 1px solid var(--border);
        border-radius: 16px;
        padding: 16px;
        display: flex; flex-direction: column; align-items: center; justify-content: center;
        text-align: center;
        transition: all 0.2s;
        box-shadow: 0 2px 4px rgba(0,0,0,0.02);
    }
    .stat-card:hover { transform: translateY(-2px); box-shadow: 0 4px 12px rgba(0,0,0,0.06); border-color: var(--primary-soft); }
    .stat-icon { font-size: 1.5rem; margin-bottom: 4px; }
    .stat-value { font-size: 1.5rem; font-weight: 800; color: var(--text); line-height: 1; margin-bottom: 2px; }
    .stat-label { font-size: 0.7rem; color: var(--text-light); text-transform: uppercase; font-weight: 600; letter-spacing: 0.5px; }
    .stat-value.highlight { color: var(--primary); }
    .stat-value.fire { color: #f59e0b; }
</style>

<!-- INFO BAR - BARRA DE PROGRESSO COMPACTA -->
<div style="background: white; border-bottom: 1px solid var(--gray-200, #e5e7eb); padding: 12px 16px;">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px;">
        <div style="flex: 1;">
            <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 8px;">
                <span style="font-size:0.7rem; text-transform:uppercase; color:var(--gray-500, #6b7280); font-weight:700; letter-spacing:0.5px;">Progresso Anual</span>
                <div style="font-size: 0.85rem; color: var(--primary-700, #064e3b); font-weight: 600;">
                    <?= $currentMessage ?>
                </div>
            </div>
            <div style="display: flex; align-items: center; gap: 16px;">
                <!-- Dias Lidos -->
                <div>
                    <div style="color:var(--gray-900, #111827); font-weight:800; font-size:1rem; line-height:1;">
                        <span style="color:var(--primary-600, #065f46);"><?= $totalDaysRead ?></span><span style="color:var(--gray-400, #9ca3af); font-size:0.85rem; font-weight:600;">/300</span>
                    </div>
                    <div style="font-size:0.65rem; color:var(--gray-500, #6b7280); font-weight:600; text-transform:uppercase; letter-spacing:0.3px;">Dias (<?= $completionPercent ?>%)</div>
                </div>
                <!-- Capítulos -->
                <div style="border-left: 2px solid var(--gray-200, #e5e7eb); padding-left: 16px;">
                    <div style="color:var(--gray-900, #111827); font-weight:800; font-size:1rem; line-height:1;">
                        <span style="color:var(--success, #10b981);"><?= $totalChaptersRead ?></span>
                    </div>
                    <div style="font-size:0.65rem; color:var(--gray-500, #6b7280); font-weight:600; text-transform:uppercase; letter-spacing:0.3px;">Capítulos</div>
                </div>
                <!-- Sequência -->
                <div style="border-left: 2px solid var(--gray-200, #e5e7eb); padding-left: 16px;">
                    <div style="color:var(--gray-900, #111827); font-weight:800; font-size:1rem; line-height:1;">
                        <span style="color: #ea580c;">🔥 <?= $currentStreak ?></span>
                    </div>
                    <div style="font-size:0.65rem; color:var(--gray-500, #6b7280); font-weight:600; text-transform:uppercase; letter-spacing:0.3px;">Sequência</div>
                </div>
            </div>
        </div>
        
        <!-- Botão de Estatísticas -->
        <button onclick="document.getElementById('modal-detailed-stats').style.display='flex'" class="ripple" style="
            background: linear-gradient(135deg, #047857, #059669); 
            color: white; 
            border: none; 
            padding: 12px 16px; 
            border-radius: 10px; 
            cursor: pointer; 
            display: flex; 
            align-items: center; 
            gap: 8px;
            font-weight: 700;
            font-size: 0.85rem;
            box-shadow: 0 2px 8px rgba(4, 120, 87, 0.3);
            transition: all 0.2s;
            flex-shrink: 0;
            margin-left: 16px;
        " onmouseover="this.style.transform='translateY(-2px)'; this.style.boxShadow='0 4px 12px rgba(4, 120, 87, 0.4)'"
            onmouseout="this.style.transform='none'; this.style.boxShadow='0 2px 8px rgba(4, 120, 87, 0.3)'">
            <i data-lucide="bar-chart-2" style="width: 18px;"></i>
            <span>Estatísticas</span>
        </button>
    </div>
    
    <!-- Barra de Progresso -->
    <div style="height: 6px; background: var(--gray-100, #f3f4f6); width: 100%; border-radius: 10px; overflow: hidden; position: relative;">
        <div style="height: 100%; background: linear-gradient(90deg, #10b981, #047857); width: <?= $completionPercent ?>%; border-radius: 10px; transition: width 0.5s ease;"></div>
        <!-- Marcos visuais -->
        <div style="position: absolute; left: 25%; top: 0; bottom: 0; width: 1px; background: rgba(0,0,0,0.1);"></div>
        <div style="position: absolute; left: 50%; top: 0; bottom: 0; width: 1px; background: rgba(0,0,0,0.1);"></div>
        <div style="position: absolute; left: 75%; top: 0; bottom: 0; width: 1px; background: rgba(0,0,0,0.1);"></div>
    </div>
</div>

<!-- MODAL DE ESTATÍSTICAS DETALHADAS -->
<div id="modal-detailed-stats" class="modal-overlay" onclick="if(event.target===this) document.getElementById('modal-detailed-stats').style.display='none'">
    <div style="background: white; width: 95%; max-width: 600px; border-radius: 20px; box-shadow: 0 25px 50px -12px rgba(0,0,0,0.25); max-height: 90vh; overflow-y: auto;">
        <!-- Header -->
        <div style="background: linear-gradient(135deg, #047857, #059669); padding: 20px 24px; border-radius: 20px 20px 0 0; display: flex; justify-content: space-between; align-items: center;">
            <h2 style="margin:0; color: white; font-size:1.3rem; display:flex; align-items:center; gap:10px; font-weight:800;">
                <i data-lucide="bar-chart-2" style="width: 24px;"></i> Estatísticas de Leitura
            </h2>
            <button onclick="document.getElementById('modal-detailed-stats').style.display='none'" style="background:none; border:none; cursor:pointer; color: white; padding: 4px; display: flex; align-items: center;">
                <i data-lucide="x" style="width: 24px;"></i>
            </button>
        </div>
        
        <!-- Content -->
        <div style="padding: 24px;">
            <!-- Mensagem Motivacional -->
            <div style="background: linear-gradient(135deg, #ecfdf5, #d1fae5); padding: 16px; border-radius: 12px; margin-bottom: 20px; border: 1px solid #a7f3d0;">
                <div style="font-size: 0.95rem; color: #065f46; font-weight: 600; line-height: 1.5; text-align: center;">
                    <?= $currentMessage ?>
                </div>
            </div>
            
            <!-- Grid de Métricas -->
            <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 12px; margin-bottom: 20px;">
                <!-- Dias Lidos -->
                <div style="background: linear-gradient(135deg, #ecfdf5, #d1fae5); padding: 16px; border-radius: 12px; text-align: center; border: 1px solid #a7f3d0;">
                    <div style="font-size: 2rem; font-weight: 800; color: #065f46; line-height: 1;">
                        <?= $totalDaysRead ?><span style="font-size: 1rem; color: #9ca3af; font-weight: 600;">/300</span>
                    </div>
                    <div style="font-size: 0.7rem; color: #4b5563; font-weight: 600; text-transform: uppercase; letter-spacing: 0.3px; margin-top: 6px;">
                        📅 Dias Lidos
                    </div>
                    <div style="font-size: 0.85rem; color: #065f46; font-weight: 700; margin-top: 4px;">
                        <?= $completionPercent ?>% completo
                    </div>
                </div>

                <!-- Capítulos -->
                <div style="background: linear-gradient(135deg, #f0fdf4, #dcfce7); padding: 16px; border-radius: 12px; text-align: center; border: 1px solid #bbf7d0;">
                    <div style="font-size: 2rem; font-weight: 800; color: #16a34a; line-height: 1;">
                        <?= $totalChaptersRead ?>
                    </div>
                    <div style="font-size: 0.7rem; color: #4b5563; font-weight: 600; text-transform: uppercase; letter-spacing: 0.3px; margin-top: 6px;">
                        📖 Capítulos Lidos
                    </div>
                    <div style="font-size: 0.85rem; color: #16a34a; font-weight: 700; margin-top: 4px;">
                        <?= $avgChapters ?> cap/dia
                    </div>
                </div>

                <!-- Sequência Atual -->
                <div style="background: linear-gradient(135deg, #fff7ed, #ffedd5); padding: 16px; border-radius: 12px; text-align: center; border: 1px solid #fed7aa;">
                    <div style="font-size: 2rem; font-weight: 800; color: #ea580c; line-height: 1;">
                        <?= $currentStreak ?>
                    </div>
                    <div style="font-size: 0.7rem; color: #4b5563; font-weight: 600; text-transform: uppercase; letter-spacing: 0.3px; margin-top: 6px;">
                        🔥 Sequência Atual
                    </div>
                    <div style="font-size: 0.85rem; color: #ea580c; font-weight: 700; margin-top: 4px;">
                        dias seguidos
                    </div>
                </div>

                <!-- Melhor Sequência -->
                <div style="background: linear-gradient(135deg, #fefce8, #fef9c3); padding: 16px; border-radius: 12px; text-align: center; border: 1px solid #fde68a;">
                    <div style="font-size: 2rem; font-weight: 800; color: #d97706; line-height: 1;">
                        <?= $bestStreak ?>
                    </div>
                    <div style="font-size: 0.7rem; color: #4b5563; font-weight: 600; text-transform: uppercase; letter-spacing: 0.3px; margin-top: 6px;">
                        ⭐ Melhor Sequência
                    </div>
                    <div style="font-size: 0.85rem; color: #d97706; font-weight: 700; margin-top: 4px;">
                        recorde pessoal
                    </div>
                </div>
            </div>

            <!-- Projeções -->
            <?php if($estimatedCompletionDate): ?>
            <div style="background: linear-gradient(135deg, #faf5ff, #f3e8ff); padding: 16px; border-radius: 12px; margin-bottom: 20px; border: 1px solid #ddd6fe;">
                <div style="font-size: 0.75rem; color: #6b7280; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 12px;">
                    📊 Projeções
                </div>
                <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 12px;">
                    <div>
                        <div style="font-size: 0.7rem; color: #6b7280; font-weight: 600; text-transform: uppercase; letter-spacing: 0.3px;">
                            📅 Conclusão Estimada
                        </div>
                        <div style="font-size: 1.1rem; color: #7c3aed; font-weight: 800; margin-top: 4px;">
                            <?= $estimatedCompletionDate ?>
                        </div>
                    </div>
                    <div>
                        <div style="font-size: 0.7rem; color: #6b7280; font-weight: 600; text-transform: uppercase; letter-spacing: 0.3px;">
                            ⏱️ Dias Restantes
                        </div>
                        <div style="font-size: 1.1rem; color: #7c3aed; font-weight: 800; margin-top: 4px;">
                            <?= $daysRemaining ?> dias
                        </div>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <!-- Conquistas -->
            <?php if(!empty($achievements)): ?>
            <div style="background: linear-gradient(135deg, #fffbeb, #fef3c7); padding: 16px; border-radius: 12px; border: 1px solid #fde68a;">
                <div style="font-size: 0.75rem; color: #6b7280; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 12px;">
                    🏆 Conquistas Desbloqueadas
                </div>
                <div style="display: flex; flex-wrap: wrap; gap: 8px;">
                    <?php foreach($achievements as $achievement): ?>
                    <div style="background: white; padding: 8px 12px; border-radius: 20px; font-size: 0.75rem; font-weight: 600; color: #92400e; border: 1px solid #fbbf24; display: flex; align-items: center; gap: 6px; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
                        <span style="font-size: 1.1rem;"><?= $achievement['icon'] ?></span>
                        <span><?= $achievement['text'] ?></span>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- STATS MODAL (Hidden by default) -->
<div id="modal-stats" class="modal-overlay" onclick="if(event.target===this) document.getElementById('modal-stats').style.display='none'">
    <div class="config-content" style="max-width: 600px; padding: 0;">
        <div class="config-header" style="border-radius: 12px 12px 0 0;">
            <h2 style="margin:0; font-size:1.1rem; display:flex; align-items:center; gap:8px;"><i data-lucide="bar-chart-2"></i> Estatísticas de Leitura</h2>
            <button onclick="document.getElementById('modal-stats').style.display='none'" style="background:none; border:none; cursor:pointer;"><i data-lucide="x"></i></button>
        </div>
        <div style="background: var(--bg); padding: 20px; border-radius: 0 0 12px 12px;">
            <div class="stats-dashboard" style="background: transparent; border: none; padding: 0; box-shadow: none;">
                <!-- Streak -->
                <div class="stat-card">
                    <div class="stat-icon">🔥</div>
                    <div class="stat-value fire"><?= $currentStreak ?></div>
                    <div class="stat-label">Dias Seguidos</div>
                </div>
                <!-- Média -->
                <div class="stat-card">
                    <div class="stat-icon">📊</div>
                    <div class="stat-value"><?= $avgChapters ?></div>
                    <div class="stat-label">Cap./Dia</div>
                </div>
                <!-- Dias Restantes -->
                <div class="stat-card">
                    <div class="stat-icon">⏳</div>
                    <div class="stat-value"><?= max(0, 300 - $totalChaptersRead) ?></div>
                    <div class="stat-label">Restantes</div>
                </div>
                <!-- Conclusão -->
                <div class="stat-card">
                    <div class="stat-icon">🎯</div>
                    <div class="stat-value highlight"><?= $completionPercent ?>%</div>
                    <div class="stat-label">Conclusão</div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Calendar Strip with Navigation -->
<div style="position: relative; background: white; border-bottom: 1px solid var(--gray-200, #e5e7eb);">
    <!-- Left Arrow -->
    <button id="scroll-left" onclick="scrollCalendar('left')" style="position: absolute; left: 0; top: 50%; transform: translateY(-50%); z-index: 10; background: white; border: 1px solid var(--gray-300, #d1d5db); border-radius: 50%; width: 32px; height: 32px; display: flex; align-items: center; justify-content: center; cursor: pointer; box-shadow: 0 2px 4px rgba(0,0,0,0.1); transition: all 0.2s; margin-left: 4px;">
        <i data-lucide="chevron-left" width="18" style="color: var(--gray-600, #4b5563);"></i>
    </button>
    
    <!-- Calendar Strip -->
    <div class="cal-strip" id="calendar-strip"></div>
    
    <!-- Right Arrow -->
    <button id="scroll-right" onclick="scrollCalendar('right')" style="position: absolute; right: 0; top: 50%; transform: translateY(-50%); z-index: 10; background: white; border: 1px solid var(--gray-300, #d1d5db); border-radius: 50%; width: 32px; height: 32px; display: flex; align-items: center; justify-content: center; cursor: pointer; box-shadow: 0 2px 4px rgba(0,0,0,0.1); transition: all 0.2s; margin-right: 4px;">
        <i data-lucide="chevron-right" width="18" style="color: var(--gray-600, #4b5563);"></i>
    </button>
</div>

<style>
    #scroll-left:hover, #scroll-right:hover {
        background: var(--gray-50, #f9fafb);
        border-color: var(--primary-500, #047857);
    }
    #scroll-left:hover i, #scroll-right:hover i {
        color: var(--primary-600, #065f46);
    }
    #scroll-left:active, #scroll-right:active {
        transform: translateY(-50%) scale(0.95);
    }
</style>

<div class="main-area">
    <!-- NEW HEADER CARD -->
    <div class="day-header-card">
        <div class="day-header-info">
            <span class="day-header-label">Leitura de Hoje</span>
            <h1 id="day-title" class="day-header-title">Carregando...</h1>
        </div>
        
        <!-- Right Side: Stats Button + Status Badge -->
        <div style="display: flex; align-items: center; gap: 12px;">
            <button onclick="document.getElementById('modal-stats').style.display='flex'" class="ripple" title="Ver Estatísticas" style="
                border: 1px solid #bae6fd; background: #e0f2fe; color: #0284c7;
                width: 38px; height: 38px; border-radius: 10px; display: flex; align-items: center; justify-content: center;
                cursor: pointer; transition: all 0.2s;
            ">
                <i data-lucide="bar-chart-2" width="18"></i>
            </button>
            <div id="status-badge-container"></div>
        </div>
    </div>
    
    <div id="verses-list"></div>
</div>

<div class="bottom-bar">
    <button class="action-btn" onclick="openNoteModal()"><div class="icon-box purple"><i data-lucide="pen-line" width="18"></i></div><span>Anotação</span></button>
    <button class="action-btn" onclick="openGroupComments()"><div class="icon-box blue"><i data-lucide="message-circle" width="18"></i></div><span>Comentários</span></button>
</div>
<div id="save-toast" class="auto-save-feedback" style="position:fixed; top:90px; left:50%; transform:translateX(-50%); background:#1e293b; color:white; padding:8px 16px; border-radius:20px; opacity:0; pointer-events:none; z-index:2000; transition:opacity 0.3s; display:flex; gap:8px;"><i data-lucide="check" width="14"></i> Salvo auto</div>

<!-- NOTE MODAL (RICH) -->
<div id="modal-note" class="modal-overlay">
    <div style="background: white; width: 95%; max-width: 600px; border-radius: 20px; box-shadow: 0 25px 50px -12px rgba(0,0,0,0.25); display: flex; flex-direction: column; overflow: hidden; animation: scaleUp 0.25s;">
        <div style="background: #fff7ed; padding: 20px 24px; border-bottom: 1px solid #fed7aa; display: flex; justify-content: space-between; align-items: center;">
            <h3 style="margin: 0; color: #ea580c; font-size: 1.15rem; display: flex; align-items: center; gap: 10px; font-weight:700;"><i data-lucide="pen-line" width="20"></i> Minha Anotação</h3>
            <button onclick="document.getElementById('modal-note').style.display='none'" style="border: none; background: none; color: #c2410c; cursor: pointer;"><i data-lucide="x" width="22"></i></button>
        </div>
        <div style="padding: 24px; overflow-y:auto; max-height: 70vh;">
            <div style="margin-bottom: 20px;">
                <label style="display: block; font-size: 0.85rem; font-weight: 700; color: #334155; margin-bottom: 8px;">Título</label>
                <input type="text" id="note-title-input" placeholder="Ex: Reflexão sobre Gênesis..." style="width: 100%; padding: 12px 16px; border: 1px solid #cbd5e1; border-radius: 10px; font-size: 0.95rem; outline: none;">
            </div>
            <div style="margin-bottom: 10px;">
                <label style="display: block; font-size: 0.85rem; font-weight: 700; color: #334155; margin-bottom: 8px;">Descrição Detalhada</label>
                
                <!-- Rich Text Editor Toolbar -->
                <div id="editor-toolbar" style="border: 1px solid #cbd5e1; border-bottom:none; border-radius: 10px 10px 0 0; background: white; padding: 8px 12px; display:flex; gap:4px; flex-wrap:wrap; align-items:center;">
                    <!-- Text Formatting -->
                    <button type="button" onclick="formatText('bold')" class="editor-btn" title="Negrito (Ctrl+B)">
                        <i data-lucide="bold" width="16"></i>
                    </button>
                    <button type="button" onclick="formatText('italic')" class="editor-btn" title="Itálico (Ctrl+I)">
                        <i data-lucide="italic" width="16"></i>
                    </button>
                    <button type="button" onclick="formatText('underline')" class="editor-btn" title="Sublinhado (Ctrl+U)">
                        <i data-lucide="underline" width="16"></i>
                    </button>
                    <button type="button" onclick="formatText('strikeThrough')" class="editor-btn" title="Tachado">
                        <i data-lucide="strikethrough" width="16"></i>
                    </button>
                    
                    <div style="width:1px; height:20px; background:#e2e8f0; margin:0 4px;"></div>
                    
                    <!-- Link Button -->
                    <button type="button" onclick="insertLink()" class="editor-btn" title="Inserir link">
                        <i data-lucide="link" width="16"></i>
                    </button>
                    
                    <div style="width:1px; height:20px; background:#e2e8f0; margin:0 4px;"></div>
                    
                    <!-- Lists -->
                    <button type="button" onclick="formatText('insertUnorderedList')" class="editor-btn" title="Lista com marcadores">
                        <i data-lucide="list" width="16"></i>
                    </button>
                    <button type="button" onclick="formatText('insertOrderedList')" class="editor-btn" title="Lista numerada">
                        <i data-lucide="list-ordered" width="16"></i>
                    </button>
                    
                    <div style="width:1px; height:20px; background:#e2e8f0; margin:0 4px;"></div>
                    
                    <!-- Emoji Picker -->
                    <div style="position:relative;">
                        <button type="button" onclick="toggleEmojiPicker()" class="editor-btn" title="Inserir emoji" id="emoji-btn">
                            😊
                        </button>
                        <div id="emoji-picker" style="display:none; position:absolute; top:100%; left:0; margin-top:4px; background:white; border:1px solid #cbd5e1; border-radius:8px; padding:8px; box-shadow:0 4px 12px rgba(0,0,0,0.1); z-index:1000; width:280px;">
                            <div style="font-size:0.75rem; font-weight:700; color:#64748b; margin-bottom:6px; text-transform:uppercase; letter-spacing:0.5px;">Emojis</div>
                            <div style="display:grid; grid-template-columns:repeat(8, 1fr); gap:4px; max-height:200px; overflow-y:auto;">
                                <!-- Smileys & Emotion -->
                                <button type="button" onclick="insertEmoji('😊')" class="emoji-btn">😊</button>
                                <button type="button" onclick="insertEmoji('😂')" class="emoji-btn">😂</button>
                                <button type="button" onclick="insertEmoji('❤️')" class="emoji-btn">❤️</button>
                                <button type="button" onclick="insertEmoji('😍')" class="emoji-btn">😍</button>
                                <button type="button" onclick="insertEmoji('🥰')" class="emoji-btn">🥰</button>
                                <button type="button" onclick="insertEmoji('😘')" class="emoji-btn">😘</button>
                                <button type="button" onclick="insertEmoji('😁')" class="emoji-btn">😁</button>
                                <button type="button" onclick="insertEmoji('😎')" class="emoji-btn">😎</button>
                                <button type="button" onclick="insertEmoji('🤗')" class="emoji-btn">🤗</button>
                                <button type="button" onclick="insertEmoji('🤔')" class="emoji-btn">🤔</button>
                                <button type="button" onclick="insertEmoji('😇')" class="emoji-btn">😇</button>
                                <button type="button" onclick="insertEmoji('🙏')" class="emoji-btn">🙏</button>
                                <button type="button" onclick="insertEmoji('✨')" class="emoji-btn">✨</button>
                                <button type="button" onclick="insertEmoji('⭐')" class="emoji-btn">⭐</button>
                                <button type="button" onclick="insertEmoji('🌟')" class="emoji-btn">🌟</button>
                                <button type="button" onclick="insertEmoji('💫')" class="emoji-btn">💫</button>
                                <!-- Gestures -->
                                <button type="button" onclick="insertEmoji('👍')" class="emoji-btn">👍</button>
                                <button type="button" onclick="insertEmoji('👏')" class="emoji-btn">👏</button>
                                <button type="button" onclick="insertEmoji('🙌')" class="emoji-btn">🙌</button>
                                <button type="button" onclick="insertEmoji('👌')" class="emoji-btn">👌</button>
                                <button type="button" onclick="insertEmoji('✌️')" class="emoji-btn">✌️</button>
                                <button type="button" onclick="insertEmoji('🤝')" class="emoji-btn">🤝</button>
                                <button type="button" onclick="insertEmoji('💪')" class="emoji-btn">💪</button>
                                <button type="button" onclick="insertEmoji('🤲')" class="emoji-btn">🤲</button>
                                <!-- Religious & Spiritual -->
                                <button type="button" onclick="insertEmoji('✝️')" class="emoji-btn">✝️</button>
                                <button type="button" onclick="insertEmoji('⛪')" class="emoji-btn">⛪</button>
                                <button type="button" onclick="insertEmoji('📖')" class="emoji-btn">📖</button>
                                <button type="button" onclick="insertEmoji('📿')" class="emoji-btn">📿</button>
                                <button type="button" onclick="insertEmoji('🕊️')" class="emoji-btn">🕊️</button>
                                <button type="button" onclick="insertEmoji('🌈')" class="emoji-btn">🌈</button>
                                <button type="button" onclick="insertEmoji('☀️')" class="emoji-btn">☀️</button>
                                <button type="button" onclick="insertEmoji('🌙')" class="emoji-btn">🌙</button>
                                <!-- Music & Worship -->
                                <button type="button" onclick="insertEmoji('🎵')" class="emoji-btn">🎵</button>
                                <button type="button" onclick="insertEmoji('🎶')" class="emoji-btn">🎶</button>
                                <button type="button" onclick="insertEmoji('🎤')" class="emoji-btn">🎤</button>
                                <button type="button" onclick="insertEmoji('🎸')" class="emoji-btn">🎸</button>
                                <button type="button" onclick="insertEmoji('🎹')" class="emoji-btn">🎹</button>
                                <button type="button" onclick="insertEmoji('🥁')" class="emoji-btn">🥁</button>
                                <button type="button" onclick="insertEmoji('🎺')" class="emoji-btn">🎺</button>
                                <button type="button" onclick="insertEmoji('🎼')" class="emoji-btn">🎼</button>
                                <!-- Nature -->
                                <button type="button" onclick="insertEmoji('🌺')" class="emoji-btn">🌺</button>
                                <button type="button" onclick="insertEmoji('🌸')" class="emoji-btn">🌸</button>
                                <button type="button" onclick="insertEmoji('🌼')" class="emoji-btn">🌼</button>
                                <button type="button" onclick="insertEmoji('🌻')" class="emoji-btn">🌻</button>
                                <button type="button" onclick="insertEmoji('🌹')" class="emoji-btn">🌹</button>
                                <button type="button" onclick="insertEmoji('🌿')" class="emoji-btn">🌿</button>
                                <button type="button" onclick="insertEmoji('🍃')" class="emoji-btn">🍃</button>
                                <button type="button" onclick="insertEmoji('🌱')" class="emoji-btn">🌱</button>
                            </div>
                        </div>
                    </div>
                    
                    <div style="width:1px; height:20px; background:#e2e8f0; margin:0 4px;"></div>
                    
                    <!-- Clear Formatting -->
                    <button type="button" onclick="formatText('removeFormat')" class="editor-btn" title="Limpar formatação">
                        <i data-lucide="eraser" width="16"></i>
                    </button>
                </div>
                
                <!-- Rich Text Editor Content -->
                <div 
                    id="note-desc-input" 
                    contenteditable="true" 
                    style="width: 100%; min-height: 180px; max-height: 300px; padding: 16px; border: 1px solid #cbd5e1; border-top:none; border-radius: 0 0 10px 10px; outline: none; overflow-y: auto; font-size:0.95rem; line-height:1.6; color:#334155;"
                    placeholder="Digite aqui... Use a barra de ferramentas para formatar o texto."
                    data-placeholder="Digite aqui... Use a barra de ferramentas para formatar o texto."
                ></div>
            </div>
            
            <style>
                .editor-btn {
                    background: white;
                    border: 1px solid #e2e8f0;
                    border-radius: 6px;
                    padding: 6px 8px;
                    cursor: pointer;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    transition: all 0.2s;
                    color: #64748b;
                }
                .editor-btn:hover {
                    background: #f8fafc;
                    border-color: #cbd5e1;
                    color: #334155;
                }
                .editor-btn:active {
                    transform: scale(0.95);
                    background: #f1f5f9;
                }
                
                .emoji-btn {
                    background: white;
                    border: 1px solid transparent;
                    border-radius: 4px;
                    padding: 6px;
                    cursor: pointer;
                    font-size: 1.2rem;
                    transition: all 0.2s;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                }
                .emoji-btn:hover {
                    background: #f8fafc;
                    border-color: #e2e8f0;
                    transform: scale(1.1);
                }
                
                /* Placeholder for contenteditable */
                #note-desc-input:empty:before {
                    content: attr(data-placeholder);
                    color: #94a3b8;
                    font-style: italic;
                }
                
                /* Styling for formatted content */
                #note-desc-input b, #note-desc-input strong { font-weight: 700; }
                #note-desc-input i, #note-desc-input em { font-style: italic; }
                #note-desc-input u { text-decoration: underline; }
                #note-desc-input strike { text-decoration: line-through; }
                #note-desc-input ul, #note-desc-input ol { margin-left: 20px; margin-top: 8px; margin-bottom: 8px; }
                #note-desc-input li { margin-bottom: 4px; }
                #note-desc-input a { color: #047857; text-decoration: underline; cursor: pointer; }
                #note-desc-input a:hover { color: #065f46; }
            </style>
        </div>
        <div style="padding: 16px 24px; background: #fff; border-top: 1px solid #f1f5f9; display: flex; justify-content: flex-end; gap: 12px;">
            <button onclick="document.getElementById('modal-note').style.display='none'" style="padding: 12px 20px; border: 1px solid #e2e8f0; background: white; color: #64748b; border-radius: 10px; font-weight: 600; cursor: pointer;">Cancelar</button>
            <button onclick="saveNote()" style="padding: 12px 24px; border: none; background: #f97316; color: white; border-radius: 10px; font-weight: 700; cursor: pointer;">Salvar Anotação</button>
        </div>
    </div>
</div>

<!-- CONFIG MODAL (FULLSCREEN) -->
<div id="modal-config" class="config-fullscreen">
    <div class="config-header" style="background: white; padding: 16px 20px; border-bottom: 1px solid var(--gray-200, #e5e7eb); display: flex; justify-content: space-between; align-items: center;">
        <h2 style="font-size: 1.25rem; font-weight: 800; color: var(--gray-900, #111827); margin: 0; display: flex; align-items: center; gap: 8px;">
            <i data-lucide="settings" width="24"></i> Configurações & Diário
        </h2>
        <button onclick="document.getElementById('modal-config').style.display='none'" style="border:none; background:none; cursor:pointer; color: var(--gray-500, #6b7280); padding: 4px;">
            <i data-lucide="x" width="24"></i>
        </button>
    </div>
    
    <div class="config-tabs" style="display: flex; background: white; border-bottom: 1px solid var(--gray-200, #e5e7eb); padding: 0 20px;">
        <div class="tab-btn active" onclick="switchTab('general')" id="tab-general" style="padding: 16px 20px; font-weight: 600; color: var(--gray-500, #6b7280); border-bottom: 2px solid transparent; cursor: pointer; transition: all 0.2s;">
            <i data-lucide="calendar" width="16" style="display: inline; margin-right: 6px;"></i> Geral
        </div>
        <div class="tab-btn" onclick="switchTab('diary')" id="tab-diary" style="padding: 16px 20px; font-weight: 600; color: var(--gray-500, #6b7280); border-bottom: 2px solid transparent; cursor: pointer; transition: all 0.2s;">
            <i data-lucide="book-open" width="16" style="display: inline; margin-right: 6px;"></i> Meu Diário
        </div>
    </div>
    
    <!-- GENERAL TAB -->
    <div id="content-general" class="config-content" style="padding: 20px; max-width: 600px; margin: 0 auto; width: 100%;">
        
        <?php if (!$planStarted): ?>
        <!-- Plan Not Started - Show Start Button -->
        <div style="background: linear-gradient(135deg, #dcfce7 0%, #d1fae5 100%); border: 2px solid var(--primary-500, #047857); border-radius: 12px; padding: 30px 20px; margin-bottom: 20px; text-align: center; box-shadow: 0 4px 12px rgba(4, 120, 87, 0.15);">
            <div style="background: white; width: 80px; height: 80px; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 20px; box-shadow: 0 4px 8px rgba(0,0,0,0.1);">
                <i data-lucide="play-circle" width="40" style="color: var(--primary-600, #065f46);"></i>
            </div>
            <h3 style="margin: 0 0 12px 0; font-size: 1.3rem; font-weight: 800; color: var(--primary-700, #064e3b);">Plano Pausado</h3>
            <p style="margin: 0 0 24px 0; font-size: 0.95rem; color: var(--primary-700, #064e3b); line-height: 1.6;">Clique no botão abaixo para iniciar seu plano de leitura bíblica. O Dia 1 será definido como hoje!</p>
            <button onclick="startPlan()" class="ripple" style="padding: 14px 32px; background: var(--primary-600, #065f46); color: white; border: none; border-radius: 10px; font-weight: 700; cursor: pointer; font-size: 1rem; display: inline-flex; align-items: center; gap: 10px; box-shadow: 0 4px 12px rgba(4, 120, 87, 0.3); transition: all 0.2s;">
                <i data-lucide="rocket" width="20"></i> Iniciar Plano de Leitura
            </button>
        </div>
        <?php else: ?>
        <!-- Plan Settings Card -->
        <div style="background: white; border: 1px solid var(--gray-200, #e5e7eb); border-radius: 12px; padding: 20px; margin-bottom: 20px; box-shadow: 0 1px 3px rgba(0,0,0,0.05);">
            <div style="display: flex; align-items: start; gap: 12px; margin-bottom: 16px;">
                <div style="background: var(--primary-100, #dcfce7); padding: 10px; border-radius: 10px; color: var(--primary-600, #065f46);">
                    <i data-lucide="calendar-days" width="24"></i>
                </div>
                <div style="flex: 1;">
                    <h3 style="margin: 0 0 4px 0; font-size: 1rem; font-weight: 700; color: var(--gray-900, #111827);">Configurar Plano de Leitura</h3>
                    <p style="margin: 0; font-size: 0.85rem; color: var(--gray-600, #4b5563); line-height: 1.5;">Defina a data de início do seu plano anual de leitura bíblica. O sistema calculará automaticamente seu progresso.</p>
                </div>
            </div>
            
            <div style="background: var(--gray-50, #f9fafb); padding: 12px; border-radius: 8px; margin-bottom: 16px;">
                <div style="font-size: 0.75rem; font-weight: 700; color: var(--gray-600, #4b5563); text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 8px;">Data de Início (Dia 1)</div>
                <input type="date" id="start-date-input" value="<?= $startDateStr ?>" style="width: 100%; padding: 12px 14px; border: 1px solid var(--gray-300, #d1d5db); border-radius: 8px; font-size: 0.95rem; outline: none; font-weight: 600;">
            </div>
            
            <button onclick="saveStartDate()" class="ripple" style="width: 100%; padding: 12px 20px; background: var(--primary-500, #047857); color: white; border: none; border-radius: 8px; font-weight: 700; cursor: pointer; font-size: 0.95rem; display: flex; align-items: center; justify-content: center; gap: 8px; transition: all 0.2s;">
                <i data-lucide="check" width="18"></i> Atualizar Plano
            </button>
        </div>
        <?php endif; ?>
        
        <!-- Danger Zone Card -->
        <div style="background: var(--error-light, #fee2e2); border: 1px solid var(--error, #ef4444); border-radius: 12px; padding: 20px; box-shadow: 0 1px 3px rgba(239,68,68,0.1);">
            <div style="display: flex; align-items: start; gap: 12px; margin-bottom: 16px;">
                <div style="background: white; padding: 10px; border-radius: 10px; color: var(--error, #ef4444);">
                    <i data-lucide="alert-triangle" width="24"></i>
                </div>
                <div style="flex: 1;">
                    <h3 style="margin: 0 0 4px 0; font-size: 1rem; font-weight: 700; color: var(--error-dark, #dc2626);">Zona de Perigo</h3>
                    <p style="margin: 0; font-size: 0.85rem; color: var(--error-dark, #dc2626); line-height: 1.5;">Esta ação é irreversível e apagará todo seu progresso de leitura e anotações.</p>
                </div>
            </div>
            
            <button onclick="resetPlan()" style="width: 100%; padding: 12px 20px; background: var(--error, #ef4444); color: white; border: none; border-radius: 8px; font-weight: 700; cursor: pointer; font-size: 0.95rem; display: flex; align-items: center; justify-content: center; gap: 8px; transition: all 0.2s;">
                <i data-lucide="trash-2" width="18"></i> Resetar Todo Progresso
            </button>
        </div>
    </div>
    
    <!-- DIARY TAB -->
    <div id="content-diary" class="config-content" style="display:none; padding: 20px; max-width: 800px; margin: 0 auto; width: 100%;">
        <!-- Export Button with Dropdown -->
        <div style="margin-bottom: 20px; display: flex; justify-content: space-between; align-items: center;">
            <h3 style="margin: 0; font-size: 1.1rem; font-weight: 700; color: var(--gray-900, #111827);">Minhas Anotações</h3>
            <?php if(!empty($reportData)): ?>
            <div style="position: relative;">
                <button onclick="toggleExportMenu()" id="export-btn" class="ripple" style="padding: 10px 16px; background: var(--primary-500, #047857); color: white; border: none; border-radius: 8px; font-weight: 600; cursor: pointer; font-size: 0.85rem; display: flex; align-items: center; gap: 6px; transition: all 0.2s;">
                    <i data-lucide="download" width="16"></i> Exportar Diário <i data-lucide="chevron-down" width="14"></i>
                </button>
                
                <!-- Dropdown Menu -->
                <div id="export-menu" style="display: none; position: absolute; top: 100%; right: 0; margin-top: 4px; background: white; border: 1px solid var(--gray-200, #e5e7eb); border-radius: 8px; box-shadow: 0 4px 12px rgba(0,0,0,0.1); z-index: 1000; min-width: 180px; overflow: hidden;">
                    <button onclick="exportDiary('txt')" style="width: 100%; padding: 10px 16px; border: none; background: white; text-align: left; cursor: pointer; display: flex; align-items: center; gap: 8px; font-size: 0.85rem; color: var(--gray-700, #374151); transition: all 0.2s; border-bottom: 1px solid var(--gray-100, #f3f4f6);">
                        <i data-lucide="file-text" width="16" style="color: var(--gray-500, #6b7280);"></i>
                        <span style="font-weight: 600;">Exportar como TXT</span>
                    </button>
                    <button onclick="exportDiary('doc')" style="width: 100%; padding: 10px 16px; border: none; background: white; text-align: left; cursor: pointer; display: flex; align-items: center; gap: 8px; font-size: 0.85rem; color: var(--gray-700, #374151); transition: all 0.2s; border-bottom: 1px solid var(--gray-100, #f3f4f6);">
                        <i data-lucide="file-type" width="16" style="color: #2563eb;"></i>
                        <span style="font-weight: 600;">Exportar como DOC</span>
                    </button>
                    <button onclick="exportDiary('pdf')" style="width: 100%; padding: 10px 16px; border: none; background: white; text-align: left; cursor: pointer; display: flex; align-items: center; gap: 8px; font-size: 0.85rem; color: var(--gray-700, #374151); transition: all 0.2s;">
                        <i data-lucide="file" width="16" style="color: #dc2626;"></i>
                        <span style="font-weight: 600;">Exportar como PDF</span>
                    </button>
                </div>
            </div>
            <?php endif; ?>
        </div>
        
        <!-- Search Filter -->
        <?php if(!empty($reportData)): ?>
        <div style="margin-bottom: 20px; position: relative;">
            <i data-lucide="search" width="18" style="position: absolute; left: 14px; top: 50%; transform: translateY(-50%); color: var(--gray-400, #9ca3af);"></i>
            <input type="text" id="diary-search" onkeyup="filterDiary()" placeholder="Buscar anotações (título, conteúdo, data)..." style="width: 100%; padding: 12px 14px 12px 42px; border: 1px solid var(--gray-300, #d1d5db); border-radius: 8px; font-size: 0.95rem; outline: none; transition: all 0.2s;">
        </div>
        <?php endif; ?>
        
        <style>
            #export-menu button:hover {
                background: var(--gray-50, #f9fafb);
            }
        </style>
        
        <?php if(empty($reportData)): ?>
        <!-- Empty State -->
        <div style="text-align: center; padding: 60px 20px; background: white; border-radius: 12px; border: 1px solid var(--gray-200, #e5e7eb);">
            <div style="background: var(--gray-100, #f3f4f6); width: 80px; height: 80px; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 16px; color: var(--gray-400, #9ca3af);">
                <i data-lucide="book-open" width="40"></i>
            </div>
            <h3 style="margin: 0 0 8px 0; font-size: 1.1rem; font-weight: 700; color: var(--gray-900, #111827);">Nenhuma anotação ainda</h3>
            <p style="margin: 0; font-size: 0.9rem; color: var(--gray-600, #4b5563);">Comece a registrar suas reflexões sobre as leituras bíblicas!</p>
        </div>
        <?php else: ?>
        <!-- Diary Entries Organized by Weeks -->
        <div style="display: flex; flex-direction: column; gap: 12px;">
        <?php 
        // Group entries by week (5 days per week, 5 weeks per month)
        $entriesByWeek = [];
        foreach($reportData as $rep) {
            $month = $rep['m'];
            $day = $rep['d'];
            $week = ceil($day / 5); // 5 days per week
            $weekKey = "Mês {$month} - Semana {$week}";
            if (!isset($entriesByWeek[$weekKey])) {
                $entriesByWeek[$weekKey] = [];
            }
            $entriesByWeek[$weekKey][] = $rep;
        }
        
        $weekIndex = 0;
        foreach($entriesByWeek as $weekLabel => $entries):
            $weekIndex++;
        ?>
            <!-- Week Accordion -->
            <div style="background: white; border: 1px solid var(--gray-200, #e5e7eb); border-radius: 12px; overflow: hidden; box-shadow: 0 1px 3px rgba(0,0,0,0.05);">
                <!-- Week Header (Toggle Button) -->
                <button onclick="toggleWeek(<?= $weekIndex ?>)" style="width: 100%; padding: 16px 20px; border: none; background: var(--gray-50, #f9fafb); text-align: left; cursor: pointer; display: flex; align-items: center; justify-content: space-between; transition: all 0.2s;">
                    <div style="display: flex; align-items: center; gap: 12px;">
                        <div style="background: var(--primary-100, #dcfce7); padding: 8px; border-radius: 8px; color: var(--primary-600, #065f46);">
                            <i data-lucide="calendar-range" width="20"></i>
                        </div>
                        <div>
                            <h4 style="margin: 0; font-size: 1rem; font-weight: 700; color: var(--gray-900, #111827);"><?= $weekLabel ?></h4>
                            <p style="margin: 0; font-size: 0.8rem; color: var(--gray-600, #4b5563);"><?= count($entries) ?> anotaç<?= count($entries) > 1 ? 'ões' : 'ão' ?></p>
                        </div>
                    </div>
                    <i data-lucide="chevron-down" width="20" id="week-icon-<?= $weekIndex ?>" style="color: var(--gray-500, #6b7280); transition: transform 0.3s;"></i>
                </button>
                
                <!-- Week Content (Collapsible) -->
                <div id="week-content-<?= $weekIndex ?>" style="display: none; padding: 0;">
                    <?php foreach($entries as $rep): ?>
                    <div style="padding: 16px 20px; border-top: 1px solid var(--gray-100, #f3f4f6);">
                        <!-- Header -->
                        <div style="display: flex; justify-content: space-between; align-items: start; margin-bottom: 12px;">
                            <div>
                                <div style="font-size: 0.75rem; font-weight: 700; color: var(--gray-500, #6b7280); text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 4px;">
                                    <?= date('d/m/Y às H:i', strtotime($rep['date'])) ?>
                                </div>
                                <div style="display: inline-flex; align-items: center; gap: 4px; background: var(--primary-100, #dcfce7); color: var(--primary-700, #064e3b); padding: 4px 8px; border-radius: 6px; font-size: 0.75rem; font-weight: 600;">
                                    <i data-lucide="bookmark" width="12"></i> Dia <?= $rep['d'] ?>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Title -->
                        <?php if($rep['title']): ?>
                        <h4 style="margin: 0 0 12px 0; font-size: 1rem; font-weight: 700; color: var(--gray-900, #111827);">
                            <?= htmlspecialchars($rep['title']) ?>
                        </h4>
                        <?php endif; ?>
                        
                        <!-- Content with HTML rendering -->
                        <?php if($rep['comment']): ?>
                        <div class="diary-content" style="color: var(--gray-700, #374151); line-height: 1.6; font-size: 0.9rem;">
                            <?= $rep['comment'] ?>
                        </div>
                        <?php endif; ?>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>
</div>

<style>
    /* Tab styling */
    .tab-btn.active {
        color: var(--primary-600, #065f46) !important;
        border-bottom-color: var(--primary-600, #065f46) !important;
    }
    .tab-btn:hover {
        color: var(--gray-700, #374151);
    }
    
    /* Diary content styling */
    .diary-content b, .diary-content strong { font-weight: 700; }
    .diary-content i, .diary-content em { font-style: italic; }
    .diary-content u { text-decoration: underline; }
    .diary-content strike { text-decoration: line-through; }
    .diary-content ul, .diary-content ol { margin-left: 20px; margin-top: 8px; margin-bottom: 8px; }
    .diary-content li { margin-bottom: 4px; }
    .diary-content a { color: var(--primary-600, #065f46); text-decoration: underline; }
    .diary-content a:hover { color: var(--primary-700, #064e3b); }
</style>

<script>
const serverData = <?= json_encode($progressMap) ?>;
const currentPlanMonth = <?= json_encode($currentPlanMonth) ?>;
const currentPlanDay = <?= json_encode($currentPlanDay) ?>;
const state = { m: currentPlanMonth, d: currentPlanDay, data: serverData, saveTimer: null };

function init() { 
    // FIX: Move Modals to Body to prevent layout clipping
    document.body.appendChild(document.getElementById('modal-note'));
    document.body.appendChild(document.getElementById('modal-config'));
    document.body.appendChild(document.getElementById('modal-stats')); // New Stats Modal
    document.body.appendChild(document.getElementById('save-toast'));

    renderCalendar(); 
    loadDay(state.m, state.d); 
    lucide.createIcons(); 
}

function scrollCalendar(direction) {
    const strip = document.getElementById('calendar-strip');
    const scrollAmount = 300; // pixels to scroll
    
    if (direction === 'left') {
        strip.scrollBy({ left: -scrollAmount, behavior: 'smooth' });
    } else {
        strip.scrollBy({ left: scrollAmount, behavior: 'smooth' });
    }
}

function renderCalendar() {
    const el = document.getElementById('calendar-strip'); el.innerHTML = '';
    const months = ["", "JAN", "FEV", "MAR", "ABR", "MAI", "JUN", "JUL", "AGO", "SET", "OUT", "NOV", "DEZ"];
    
    // Obter dia atual real do plano para cálculo de atraso
    const actualPlanDay = currentPlanDay; 
    const actualPlanMonth = currentPlanMonth;

    for(let d=1; d<=25; d++) {
        const key = `${state.m}_${d}`; const info = state.data[key];
        const isDone = info && info.verses && info.verses.length > 0 && isDayComplete(state.m,d); 
        
        // Lógica de Atraso: Se o mês é anterior, ou mês atual mas dia anterior ao dia do plano
        const isPast = (state.m < actualPlanMonth) || (state.m === actualPlanMonth && d < actualPlanDay);
        
        // Partial/Warning: Tem progresso incompleto OU está atrasado
        // Apenas marca como parcial se não estiver feito
        const hasProgress = info && info.verses && info.verses.length > 0;
        const isPartial = !isDone && (hasProgress || isPast);

        const div = document.createElement('div');
        // REMOVED '&& state.d !== d' from partial check so Active Item can also be Yellow
        div.className = `cal-item ${state.d === d ? 'active' : ''} ${isDone ? 'done' : ''} ${isPartial ? 'partial' : ''}`;
        div.onclick = () => { state.d = d; renderCalendar(); loadDay(state.m, d); };
        
        // Build HTML with progress indicator
        let html = `<div class="cal-month">${months[state.m]}</div><div class="cal-num">${d}</div>`;
        
        // Add progress indicator if there's a reading plan for this day
        if (bibleReadingPlan && bibleReadingPlan[state.m] && bibleReadingPlan[state.m][d-1]) {
            const totalVerses = bibleReadingPlan[state.m][d-1].length;
            const readVerses = info?.verses?.length || 0;
            
            // Show progress if user has started reading (even if not complete)
            if (readVerses > 0) {
                html += `<div class="cal-progress">${readVerses}/${totalVerses}</div>`;
            }
        }
        
        div.innerHTML = html;
        el.appendChild(div);
        if(state.d === d) setTimeout(() => div.scrollIntoView({behavior:'smooth', inline:'center'}), 100);
    }
}

function loadDay(m, d) {
    const list = document.getElementById('verses-list');
    const title = document.getElementById('day-title');
    const badge = document.getElementById('status-badge-container');
    title.innerText = `Dia ${d}`;
    
    // Status Badge with Icons
    if (isDayComplete(m, d)) {
        badge.innerHTML = '<span class="status-badge success"><i data-lucide="check-circle" width="14"></i> Concluído</span>';
    } else {
        badge.innerHTML = '<span class="status-badge pending"><i data-lucide="clock" width="14"></i> Pendente</span>';
    }
    
    if (!bibleReadingPlan || !bibleReadingPlan[m] || !bibleReadingPlan[m][d-1]) { list.innerHTML = '<div style="text-align:center; padding:40px; color:#94a3b8;">Sem leitura.</div>'; return; }
    const verses = bibleReadingPlan[m][d-1];
    const key = `${m}_${d}`;
    const savedVerses = (state.data[key] && state.data[key].verses) ? state.data[key].verses : [];
    
    list.innerHTML = '';
    verses.forEach((vText, idx) => {
        const isRead = savedVerses.includes(idx);
        const card = document.createElement('div');
        card.className = `verse-card ${isRead ? 'read' : ''}`;
        card.onclick = (e) => { if(e.target.closest('a')) return; toggleVerse(m, d, idx); };
        card.innerHTML = `<div style="display:flex; align-items:center;"><div class="check-icon"><i data-lucide="check" width="14"></i></div><span style="font-weight:600; color:var(--gray-800, #1f2937); font-size:0.9rem;">${vText}</span></div><a href="https://www.bible.com/pt/bible/1608/${vText.replace(/\s/g,'.').replace(/:/g,'.')}" target="_blank" class="btn-read-link">LER <i data-lucide="external-link" width="12"></i></a>`;
        list.appendChild(card);
    });
    lucide.createIcons();
}

function toggleVerse(m, d, idx) {
    const key = `${m}_${d}`;
    if (!state.data[key]) state.data[key] = { verses: [], comment: "", title: "" };
    const list = state.data[key].verses;
    const exists = list.indexOf(idx);
    if (exists === -1) list.push(idx); else list.splice(exists, 1);
    loadDay(m, d); renderCalendar(); showToast();
    clearTimeout(state.saveTimer); state.saveTimer = setTimeout(() => saveToServer(m, d), 1000);
}

function isDayComplete(m, d) {
    if (!bibleReadingPlan || !bibleReadingPlan[m]) return false;
    return (state.data[`${m}_${d}`]?.verses?.length || 0) >= bibleReadingPlan[m][d-1].length;
}

function saveToServer(m, d) {
    const key = `${m}_${d}`; const data = state.data[key];
    const form = new FormData();
    form.append('action', 'save_progress'); form.append('month', m); form.append('day', d);
    form.append('verses', JSON.stringify(data.verses));
    if(data.comment) form.append('comment', data.comment);
    if(data.title) form.append('note_title', data.title);
    fetch('leitura.php', { method: 'POST', body: form });
}

// Rich Text Editor Functions
function formatText(command) {
    document.execCommand(command, false, null);
    document.getElementById('note-desc-input').focus();
}

function toggleEmojiPicker() {
    const picker = document.getElementById('emoji-picker');
    picker.style.display = picker.style.display === 'none' ? 'block' : 'none';
}

function insertEmoji(emoji) {
    const editor = document.getElementById('note-desc-input');
    editor.focus();
    document.execCommand('insertText', false, emoji);
    toggleEmojiPicker();
}

function insertLink() {
    const url = prompt('Digite o URL do link:', 'https://');
    if (url && url !== 'https://') {
        const selection = window.getSelection();
        if (selection.toString()) {
            // Se há texto selecionado, cria link com o texto
            document.execCommand('createLink', false, url);
        } else {
            // Se não há texto selecionado, insere o URL como texto e link
            document.execCommand('insertHTML', false, `<a href="${url}" target="_blank" style="color:#047857; text-decoration:underline;">${url}</a>`);
        }
        document.getElementById('note-desc-input').focus();
    }
}

// Close emoji picker when clicking outside
document.addEventListener('click', function(e) {
    const picker = document.getElementById('emoji-picker');
    const emojiBtn = document.getElementById('emoji-btn');
    if (picker && !picker.contains(e.target) && e.target !== emojiBtn && !emojiBtn.contains(e.target)) {
        picker.style.display = 'none';
    }
});

function openNoteModal() {
    const key = `${state.m}_${state.d}`;
    document.getElementById('note-title-input').value = state.data[key]?.title || "";
    // Set HTML content for contenteditable div
    document.getElementById('note-desc-input').innerHTML = state.data[key]?.comment || "";
    document.getElementById('modal-note').style.display = 'flex';
    lucide.createIcons(); // Refresh icons
}
function saveNote() {
    const title = document.getElementById('note-title-input').value;
    // Get HTML content from contenteditable div
    const desc = document.getElementById('note-desc-input').innerHTML;
    const key = `${state.m}_${state.d}`;
    if(!state.data[key]) state.data[key] = { verses: [], comment: "", title: "" };
    state.data[key].title = title;
    state.data[key].comment = desc;
    showToast();
    saveToServer(state.m, state.d);
    document.getElementById('modal-note').style.display = 'none';
}
function openConfig() { document.getElementById('modal-config').style.display = 'flex'; }
function switchTab(t) {
    document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
    document.querySelectorAll('.config-content').forEach(c => c.style.display = 'none');
    document.getElementById(`tab-${t}`).classList.add('active'); 
    document.getElementById(`content-${t}`).style.display = 'block';
    lucide.createIcons(); // Refresh icons when switching tabs
}

function startPlan() {
    const f = new FormData();
    f.append('action', 'start_plan');
    fetch('leitura.php', { method: 'POST', body: f })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert(`✅ Plano iniciado com sucesso!\n\nData de início: ${data.date}\n\nA página será recarregada.`);
                window.location.reload();
            } else {
                alert('❌ Erro ao iniciar o plano. Tente novamente.');
            }
        })
        .catch(error => {
            alert('❌ Erro de conexão. Tente novamente.');
        });
}

function saveStartDate() {
    const f = new FormData(); 
    f.append('action', 'save_settings'); 
    f.append('start_date', document.getElementById('start-date-input').value);
    fetch('leitura.php', { method:'POST', body:f }).then(() => window.location.reload());
}

function toggleWeek(weekIndex) {
    const content = document.getElementById(`week-content-${weekIndex}`);
    const icon = document.getElementById(`week-icon-${weekIndex}`);
    
    if (content.style.display === 'none') {
        content.style.display = 'block';
        icon.style.transform = 'rotate(180deg)';
    } else {
        content.style.display = 'none';
        icon.style.transform = 'rotate(0deg)';
    }
    
    lucide.createIcons();
}

function toggleExportMenu() {
    const menu = document.getElementById('export-menu');
    menu.style.display = menu.style.display === 'none' ? 'block' : 'none';
    lucide.createIcons();
}

function filterDiary() {
    const input = document.getElementById('diary-search');
    const filter = input.value.toLowerCase();
    const weeks = document.querySelectorAll('[id^="week-content-"]').length;
    
    // Get all weeks
    for (let i = 1; i <= weeks; i++) {
        const weekContent = document.getElementById(`week-content-${i}`);
        const weekButton = weekContent.previousElementSibling;
        const entries = weekContent.children;
        let hasVisibleEntry = false;
        
        // Check each entry
        for (let entry of entries) {
            const text = entry.textContent.toLowerCase();
            if (text.includes(filter)) {
                entry.style.display = 'block';
                hasVisibleEntry = true;
            } else {
                entry.style.display = 'none';
            }
        }
        
        // Show/hide week based on matching entries
        if (hasVisibleEntry) {
            weekButton.style.display = 'flex';
            // Auto expand if searching
            if (filter.length > 0) {
                weekContent.style.display = 'block';
                const icon = document.getElementById(`week-icon-${i}`);
                if (icon) icon.style.transform = 'rotate(180deg)';
            }
        } else {
            weekButton.style.display = 'none';
            weekContent.style.display = 'none';
        }
    }
}

// Close export menu when clicking outside
document.addEventListener('click', function(e) {
    const menu = document.getElementById('export-menu');
    const btn = document.getElementById('export-btn');
    if (menu && btn && !menu.contains(e.target) && !btn.contains(e.target)) {
        menu.style.display = 'none';
    }
});

function exportDiary(format = 'txt') {
    // Close menu
    document.getElementById('export-menu').style.display = 'none';
    
    // Get all diary entries
    const entries = document.querySelectorAll('#content-diary .diary-content');
    if (entries.length === 0) {
        alert('Nenhuma anotação para exportar.');
        return;
    }
    
    const cards = document.querySelectorAll('#content-diary > div > div');
    const dateStr = new Date().toISOString().split('T')[0];
    
    if (format === 'txt') {
        exportAsTXT(cards, dateStr);
    } else if (format === 'doc') {
        exportAsDOC(cards, dateStr);
    } else if (format === 'pdf') {
        exportAsPDF(cards, dateStr);
    }
}

function exportAsTXT(cards, dateStr) {
    let content = '='.repeat(60) + '\n';
    content += 'DIÁRIO DE LEITURA BÍBLICA\n';
    content += 'Louvor PIB Oliveira\n';
    content += '='.repeat(60) + '\n\n';
    
    cards.forEach((card) => {
        const dateEl = card.querySelector('div[style*="text-transform: uppercase"]');
        const dayEl = card.querySelector('div[style*="background: var(--primary-100"]');
        const titleEl = card.querySelector('h4');
        const contentEl = card.querySelector('.diary-content');
        
        if (dateEl) content += dateEl.textContent.trim() + '\n';
        if (dayEl) content += dayEl.textContent.trim() + '\n';
        content += '-'.repeat(60) + '\n';
        if (titleEl) content += '\n' + titleEl.textContent.trim() + '\n\n';
        if (contentEl) {
            const tempDiv = document.createElement('div');
            tempDiv.innerHTML = contentEl.innerHTML;
            content += tempDiv.textContent.trim() + '\n';
        }
        content += '\n' + '='.repeat(60) + '\n\n';
    });
    
    downloadFile(content, `diario-leitura-biblica-${dateStr}.txt`, 'text/plain');
    showExportSuccess('TXT');
}

function exportAsDOC(cards, dateStr) {
    let html = '<html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:w="urn:schemas-microsoft-com:office:word" xmlns="http://www.w3.org/TR/REC-html40">';
    html += '<head><meta charset="utf-8"><title>Diário de Leitura Bíblica</title>';
    html += '<style>body{font-family:Arial,sans-serif;line-height:1.6;padding:20px;}h1{color:#047857;border-bottom:3px solid #047857;padding-bottom:10px;}h2{color:#065f46;margin-top:30px;}.entry{margin-bottom:30px;padding:15px;border:1px solid #e5e7eb;border-radius:8px;}.date{color:#6b7280;font-size:12px;text-transform:uppercase;font-weight:bold;}.day-badge{background:#dcfce7;color:#064e3b;padding:4px 8px;border-radius:4px;font-size:12px;display:inline-block;margin:5px 0;}.title{font-weight:bold;font-size:16px;margin:10px 0;}.content{color:#374151;margin-top:10px;}a{color:#047857;text-decoration:underline;}</style></head>';
    html += '<body>';
    html += '<h1>📖 DIÁRIO DE LEITURA BÍBLICA</h1>';
    html += '<p style="color:#6b7280;margin-bottom:30px;">Louvor PIB Oliveira</p>';
    
    cards.forEach((card) => {
        const dateEl = card.querySelector('div[style*="text-transform: uppercase"]');
        const dayEl = card.querySelector('div[style*="background: var(--primary-100"]');
        const titleEl = card.querySelector('h4');
        const contentEl = card.querySelector('.diary-content');
        
        html += '<div class="entry">';
        if (dateEl) html += `<div class="date">${dateEl.textContent.trim()}</div>`;
        if (dayEl) html += `<div class="day-badge">${dayEl.textContent.trim()}</div>`;
        if (titleEl) html += `<div class="title">${titleEl.textContent.trim()}</div>`;
        if (contentEl) html += `<div class="content">${contentEl.innerHTML}</div>`;
        html += '</div>';
    });
    
    html += '</body></html>';
    
    downloadFile(html, `diario-leitura-biblica-${dateStr}.doc`, 'application/msword');
    showExportSuccess('DOC');
}

function exportAsPDF(cards, dateStr) {
    // For PDF, we'll use print functionality with custom styles
    let printWindow = window.open('', '_blank');
    let html = '<html><head><meta charset="utf-8"><title>Diário de Leitura Bíblica</title>';
    html += '<style>@media print{@page{margin:20mm;}}body{font-family:Arial,sans-serif;line-height:1.6;color:#333;max-width:800px;margin:0 auto;padding:20px;}h1{color:#047857;border-bottom:3px solid #047857;padding-bottom:10px;margin-bottom:20px;}h2{color:#065f46;margin-top:30px;}.entry{margin-bottom:25px;padding:15px;border:1px solid #e5e7eb;border-radius:8px;page-break-inside:avoid;}.date{color:#6b7280;font-size:11px;text-transform:uppercase;font-weight:bold;letter-spacing:0.5px;}.day-badge{background:#dcfce7;color:#064e3b;padding:4px 8px;border-radius:4px;font-size:11px;display:inline-block;margin:5px 0;font-weight:600;}.title{font-weight:bold;font-size:15px;margin:10px 0;color:#111827;}.content{color:#374151;margin-top:10px;font-size:14px;}a{color:#047857;text-decoration:underline;}strong,b{font-weight:700;}em,i{font-style:italic;}u{text-decoration:underline;}strike{text-decoration:line-through;}ul,ol{margin-left:20px;}li{margin-bottom:4px;}</style></head>';
    html += '<body>';
    html += '<h1>📖 DIÁRIO DE LEITURA BÍBLICA</h1>';
    html += '<p style="color:#6b7280;margin-bottom:30px;font-size:14px;">Louvor PIB Oliveira</p>';
    
    cards.forEach((card) => {
        const dateEl = card.querySelector('div[style*="text-transform: uppercase"]');
        const dayEl = card.querySelector('div[style*="background: var(--primary-100"]');
        const titleEl = card.querySelector('h4');
        const contentEl = card.querySelector('.diary-content');
        
        html += '<div class="entry">';
        if (dateEl) html += `<div class="date">${dateEl.textContent.trim()}</div>`;
        if (dayEl) html += `<div class="day-badge">${dayEl.textContent.trim()}</div>`;
        if (titleEl) html += `<div class="title">${titleEl.textContent.trim()}</div>`;
        if (contentEl) html += `<div class="content">${contentEl.innerHTML}</div>`;
        html += '</div>';
    });
    
    html += '</body></html>';
    
    printWindow.document.write(html);
    printWindow.document.close();
    
    // Wait for content to load then trigger print
    printWindow.onload = function() {
        printWindow.print();
        // Show success message
        showExportSuccess('PDF');
    };
}

function downloadFile(content, filename, mimeType) {
    const blob = new Blob([content], { type: `${mimeType};charset=utf-8` });
    const url = URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = filename;
    document.body.appendChild(a);
    a.click();
    document.body.removeChild(a);
    URL.revokeObjectURL(url);
}

function showExportSuccess(format) {
    const el = document.getElementById('save-toast');
    el.innerHTML = `<i data-lucide="check" width="14"></i> Diário exportado como ${format} com sucesso!`;
    el.classList.add('show');
    setTimeout(() => {
        el.classList.remove('show');
        setTimeout(() => el.innerHTML = '<i data-lucide="check" width="14"></i> Salvo auto', 300);
    }, 2500);
    lucide.createIcons();
}
function saveStartDate() {
    const f = new FormData(); f.append('action', 'save_settings'); f.append('start_date', document.getElementById('start-date-input').value);
    fetch('leitura.php', { method:'POST', body:f }).then(() => window.location.reload());
}
function resetPlan() { 
    // First confirmation
    if(!confirm("⚠️ ATENÇÃO: Esta ação é IRREVERSÍVEL!\n\nVocê está prestes a:\n• Apagar TODO seu progresso de leitura\n• Apagar TODAS suas anotações\n• Resetar a data de início do plano\n\nDeseja realmente continuar?")) {
        return;
    }
    
    // Second confirmation (double check)
    const confirmText = prompt("Para confirmar, digite 'RESETAR' (em maiúsculas):");
    if(confirmText !== 'RESETAR') {
        alert('Operação cancelada. Seu progresso está seguro! ✅');
        return;
    }
    
    // Proceed with reset
    const f = new FormData(); 
    f.append('action', 'reset_plan'); 
    fetch('leitura.php', { method:'POST', body:f })
        .then(response => response.json())
        .then(data => {
            if(data.success) {
                alert('✅ Plano resetado com sucesso!\n\nA página será recarregada.');
                window.location.reload();
            } else {
                alert('❌ Erro ao resetar o plano. Tente novamente.');
            }
        })
        .catch(error => {
            alert('❌ Erro de conexão. Tente novamente.');
        });
}
function showToast() { const el = document.getElementById('save-toast'); el.classList.add('show'); setTimeout(() => el.classList.remove('show'), 2000); }
function openGroupComments() { 
    const el = document.getElementById('save-toast'); 
    el.innerHTML = '<i data-lucide="info" width="14"></i> Em breve: Comentários em Grupo!';
    el.classList.add('show'); 
    setTimeout(() => {
        el.classList.remove('show');
        // Reset toast text
        setTimeout(() => el.innerHTML = '<i data-lucide="check" width="14"></i> Salvo auto', 300);
    }, 2500); 
}
init();
</script>
<style> @keyframes scaleUp { from {transform:scale(0.95); opacity:0;} to {transform:scale(1); opacity:1;} } .auto-save-feedback.show { opacity:1; } </style>