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

// --- ACTION HANDLER ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
    $action = $_POST['action'] ?? '';

    if ($action === 'select_plan') {
         $planType = $_POST['plan_type']; // 'navigators', 'chronological', 'mcheyne'
         $startDate = $_POST['start_date'];
         
         try {
             $pdo->beginTransaction();
             // Salvar tipo de plano
             $pdo->prepare("INSERT INTO user_settings (user_id, setting_key, setting_value) VALUES (?, 'reading_plan_type', ?) ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)")->execute([$userId, $planType]);
             // Salvar data de início
             $pdo->prepare("INSERT INTO user_settings (user_id, setting_key, setting_value) VALUES (?, 'reading_plan_start_date', ?) ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)")->execute([$userId, $startDate]);
             $pdo->commit();
             echo json_encode(['success' => true]);
         } catch (Exception $e) {
             $pdo->rollBack();
             echo json_encode(['success'=>false, 'error'=>$e->getMessage()]);
         }
         exit;
    }

    if ($action === 'save_settings') {
        try {
            $pdo->prepare("INSERT INTO user_settings (user_id, setting_key, setting_value) VALUES (?, 'reading_plan_start_date', ?) ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)")->execute([$userId, $_POST['start_date']]);
            if(isset($_POST['plan_type'])) {
                $pdo->prepare("INSERT INTO user_settings (user_id, setting_key, setting_value) VALUES (?, 'reading_plan_type', ?) ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)")->execute([$userId, $_POST['plan_type']]);
            }
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
            $sql = "INSERT INTO reading_progress (user_id, month_num, day_num, verses_read, completed_at"; 
            $vals = "VALUES (?, ?, ?, ?, NOW()"; 
            $updates = "verses_read = VALUES(verses_read), completed_at = NOW()"; 
            $params = [$userId, $m, $d, $versesRaw];
            
            if($comment !== null) { $sql .= ", comment"; $vals .= ", ?"; $updates .= ", comment = VALUES(comment)"; $params[] = $comment; }
            if($title !== null) { $sql .= ", note_title"; $vals .= ", ?"; $updates .= ", note_title = VALUES(note_title)"; $params[] = $title; }
            
            $sql .= ") $vals) ON DUPLICATE KEY UPDATE $updates";
            
            $pdo->prepare($sql)->execute($params);
            echo json_encode(['success' => true]);
        } catch (Exception $e) { 
            error_log($e->getMessage()); 
            echo json_encode(['success'=>false, 'error'=>'Database error']); 
        }
        exit;
    }

    if ($action === 'reset_plan') { 
        $pdo->prepare("DELETE FROM reading_progress WHERE user_id = ?")->execute([$userId]); 
        $pdo->prepare("DELETE FROM user_settings WHERE user_id = ?")->execute([$userId]); 
        echo json_encode(['success'=>true]); 
        exit; 
    }
}

// --- LOAD SETTINGS & STATE ---
$stmt = $pdo->prepare("SELECT setting_key, setting_value FROM user_settings WHERE user_id = ?"); $stmt->execute([$userId]); $settings = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

$startDateStr = $settings['reading_plan_start_date'] ?? null;
$selectedPlanType = $settings['reading_plan_type'] ?? null;
$planStarted = !empty($startDateStr) && !empty($selectedPlanType);

// --- SELECTION SCREEN IF NO PLAN ---
if (!$planStarted) {
    renderAppHeader('Novo Plano');
    ?>
    <style>
        body { background: #f8fafc; font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; }
        .welcome-container { max-width: 600px; margin: 40px auto; padding: 20px; }
        .plan-card {
            background: white; border: 1px solid #e2e8f0; border-radius: 16px; padding: 20px; margin-bottom: 16px;
            cursor: pointer; transition: all 0.2s; position: relative; overflow: hidden;
        }
        .plan-card:hover { transform: translateY(-2px); box-shadow: 0 10px 25px -5px rgba(0,0,0,0.1); border-color: #6366f1; }
        .plan-card.selected { border: 2px solid #6366f1; background: #e0e7ff; }
        .plan-icon { width: 48px; height: 48px; background: #e0e7ff; color: #4338ca; border-radius: 12px; display: flex; align-items: center; justify-content: center; margin-bottom: 12px; }
        .plan-title { font-size: 1.1rem; font-weight: 800; color: #1e293b; margin-bottom: 4px; }
        .plan-desc { font-size: 0.85rem; color: #64748b; line-height: 1.5; }
        .badge { font-size: 0.7rem; font-weight: 700; text-transform: uppercase; padding: 4px 8px; border-radius: 6px; background: #f1f5f9; color: #475569; display: inline-block; margin-top: 8px; }
    </style>
    <div class="welcome-container">
        <h1 style="font-size: 1.8rem; font-weight: 800; color: #1e293b; margin-bottom: 8px;">Bem-vindo(a)! 👋</h1>
        <p style="color: #64748b; margin-bottom: 32px;">Escolha um plano de leitura para guiar sua jornada bíblica este ano.</p>
        
        <div id="plan-selection">
            <!-- Navigators -->
            <div class="plan-card" onclick="selectPlan('navigators', this)">
                <div class="plan-icon"><i data-lucide="compass" width="24"></i></div>
                <div class="plan-title">Plano Navigators</div>
                <div class="plan-desc">Plano equilibrado de 300 dias (25 dias/mês). Cobre toda a Bíblia com flexibilidade para dias livres.</div>
                <div class="badge">Mais Popular</div>
            </div>
            
            <!-- Cronológico -->
            <div class="plan-card" onclick="selectPlan('chronological', this)">
                <div class="plan-icon" style="background: #dcfce7; color: #15803d;"><i data-lucide="clock" width="24"></i></div>
                <div class="plan-title">Plano Cronológico</div>
                <div class="plan-desc">Leia os eventos na ordem em que aconteceram. Uma jornada histórica de 365 dias pela narrativa bíblica.</div>
                <div class="badge">Histórico</div>
            </div>
            
            <!-- M'Cheyne -->
            <div class="plan-card" onclick="selectPlan('mcheyne', this)">
                <div class="plan-icon" style="background: #fef3c7; color: #b45309;"><i data-lucide="book-open" width="24"></i></div>
                <div class="plan-title">Plano M'Cheyne</div>
                <div class="plan-desc">Clássico e intensivo. Leia o Antigo Testamento 1x e Novo Testamento + Salmos 2x em 365 dias.</div>
                <div class="badge">Intensivo</div>
            </div>
        </div>

        <div style="margin-top: 32px; background: white; padding: 20px; border-radius: 16px; border: 1px solid #e2e8f0;">
            <label style="display: block; font-size: 0.85rem; font-weight: 700; color: #334155; margin-bottom: 8px;">Data de Início</label>
            <input type="date" id="start-date" value="<?= date('Y-m-d') ?>" style="width: 100%; padding: 12px; border: 1px solid #cbd5e1; border-radius: 10px; font-size: 1rem; outline: none;">
        </div>

        <button id="start-btn" onclick="confirmStart()" style="margin-top: 24px; width: 100%; padding: 16px; background: #6366f1; color: white; border: none; border-radius: 12px; font-size: 1rem; font-weight: 700; cursor: pointer; opacity: 0.5; pointer-events: none; transition: all 0.2s;">
            Iniciar Jornada
        </button>
    </div>
    
    <script src="https://unpkg.com/lucide@latest"></script>
    <script>
        lucide.createIcons();
        let selectedPlan = null;
        
        function selectPlan(id, el) {
            selectedPlan = id;
            document.querySelectorAll('.plan-card').forEach(c => c.classList.remove('selected'));
            el.classList.add('selected');
            const btn = document.getElementById('start-btn');
            btn.style.opacity = '1';
            btn.style.pointerEvents = 'auto';
            btn.innerText = 'Iniciar Jornada';
        }
        
        function confirmStart() {
            if(!selectedPlan) return;
            const date = document.getElementById('start-date').value;
            const btn = document.getElementById('start-btn');
            btn.innerText = 'Configurando...';
            
            const f = new FormData();
            f.append('action', 'select_plan');
            f.append('plan_type', selectedPlan);
            f.append('start_date', date);
            
            fetch('leitura.php', { method: 'POST', body: f })
                .then(r => r.json())
                .then(d => {
                    if(d.success) window.location.reload();
                    else alert('Erro ao salvar: ' + d.error);
                });
        }
    </script>
    <?php
    exit;
}

// --- CALCULATE PROGRESS & PACE ---
$start = new DateTime($startDateStr); $start->setTime(0,0,0); $now->setTime(0,0,0);
$diff = $start->diff($now); 
$daysPassed = $diff->invert ? -1*$diff->days : $diff->days;
$planDayIndex = max(1, $daysPassed + 1);

// Logic to determine Current Month/Day based on Plan Type
$currentPlanMonth = 1;
$currentPlanDay = 1;

$totalPlanDays = 300; // default Navigators
if ($selectedPlanType === 'chronological' || $selectedPlanType === 'mcheyne') {
    $totalPlanDays = 365;
    // Standard Calendar Mapping (Non-Leap for simplicity or just sequential)
    $monthsRef = [0, 31, 28, 31, 30, 31, 30, 31, 31, 30, 31, 30, 31];
    
    // Find where $planDayIndex falls
    $tempDays = $planDayIndex;
    if ($tempDays > 365) $tempDays = 365; // Cap at end
    if ($tempDays < 1) $tempDays = 1;

    for ($m=1; $m<=12; $m++) {
        if ($tempDays <= $monthsRef[$m]) {
            $currentPlanMonth = $m;
            $currentPlanDay = $tempDays;
            break;
        }
        $tempDays -= $monthsRef[$m];
    }
} else {
    // Navigators (Fixed 25 days/month)
    $currentPlanMonth = floor(($planDayIndex - 1) / 25) + 1; 
    $currentPlanDay = (($planDayIndex - 1) % 25) + 1;
    if($currentPlanMonth>12){ $currentPlanMonth=12; $currentPlanDay=25; }
    if($currentPlanDay > 25) $currentPlanDay = 25;
}

// Fetch Progress
$stmt = $pdo->prepare("SELECT month_num, day_num, verses_read, comment, note_title, completed_at FROM reading_progress WHERE user_id = ? ORDER BY completed_at DESC");
$stmt->execute([$userId]); $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

$progressMap = []; 
$totalChaptersRead = 0; 
$totalDaysRead = 0; 
$reportData = [];

foreach($rows as $r) {
    $verses = json_decode($r['verses_read'] ?? '[]', true); if(!is_array($verses)) $verses=[];
    $chaptersInThisDay = count($verses);
    $totalChaptersRead += $chaptersInThisDay;
    if($chaptersInThisDay > 0) $totalDaysRead++;
    
    $k = "{$r['month_num']}_{$r['day_num']}"; // format m_d
    $progressMap[$k] = ['verses'=>$verses, 'comment'=>$r['comment']??'', 'title'=>$r['note_title']??'', 'date'=>$r['completed_at']];
    if(count($verses)>0 || !empty($r['comment']) || !empty($r['note_title'])) {
        $reportData[] = ['m'=>(int)$r['month_num'], 'd'=>(int)$r['day_num'], 'date'=>$r['completed_at'], 'comment'=>$r['comment'], 'title'=>$r['note_title']??''];
    }
}

$completionPercent = min(100, round(($totalDaysRead / $totalPlanDays) * 100));

// Streak & Motivation logic (Same as before)
$currentStreak = 0; $bestStreak = 0; $streakCount = 0; $tempStreak = 0; $allStreaks = [];
$checkDate = clone $now;
for($i=0; $i<365; $i++) {
    $found = false;
    foreach($reportData as $rep) {
        if((new DateTime($rep['date']))->format('Y-m-d') === $checkDate->format('Y-m-d')) { $found=true; break; }
    }
    if($found) { $streakCount++; $tempStreak++; $checkDate->modify('-1 day'); }
    else {
        if($i===0) { $checkDate->modify('-1 day'); continue; }
        if($tempStreak > 0) $allStreaks[] = $tempStreak; $tempStreak = 0; break;
    }
}
if($tempStreak>0) $allStreaks[] = $tempStreak;
$currentStreak = $streakCount; $bestStreak = !empty($allStreaks) ? max($allStreaks) : $currentStreak;

// Predictions
$daysInPlan = max(1, $daysPassed);
$avgChapters = round($totalChaptersRead / $daysInPlan, 1);
$daysRemaining = $totalPlanDays - $totalDaysRead;

// Messages
$motivationalMessages = [
    0 => "🌱 Você começou! Cada jornada começa com um passo.",
    10 => "💪 Incrível! A persistência está valendo a pena!",
    25 => "🌟 Você está no caminho certo! Continue firme!",
    50 => "✨ Mais da metade concluída! Sua dedicação é inspiradora!",
    75 => "🎯 Quase lá! Você está tão perto de completar esta jornada!",
    90 => "🏆 Reta final! Você é um exemplo de disciplina!"
];
$currentMessage = $motivationalMessages[0];
foreach($motivationalMessages as $t => $msg) if($completionPercent >= $t) $currentMessage = $msg;

// Render View
renderAppHeader('Leitura Bíblica'); 
renderPageHeader('Plano de Leitura', 'Louvor PIB Oliveira');
?>

<!-- FRONTEND -->
<script src="../assets/js/reading_plan_data.js?v=<?= time() ?>"></script>
<script src="https://unpkg.com/lucide@latest"></script>

<style>
    /* ... (CSS preserved from original, added adjustments for 31 days columns if needed) ... */
    :root { --primary: #6366f1; --primary-soft: #e0e7ff; --success: #10b981; }
    body { background-color: #f8fafc; color: #1e293b; padding-bottom: 70px; font-family: -apple-system, sans-serif; }
    
    /* Calendar Strip & Items */
    .cal-strip { display: flex !important; gap: 8px; overflow-x: auto; padding: 10px 12px; background: white; border-bottom: 1px solid #e2e8f0; scrollbar-width: none; }
    .cal-strip::-webkit-scrollbar { display: none; }
    .cal-item { min-width: 56px; height: 68px; border-radius: 12px; background: #f3f4f6; border: 2px solid transparent; display: flex; flex-direction: column; align-items: center; justify-content: center; flex-shrink: 0; cursor: pointer; transition: all 0.2s; }
    .cal-month { font-size: 0.65rem; font-weight: 700; text-transform: uppercase; color: #6b7280; }
    .cal-num { font-size: 1.25rem; font-weight: 800; color: #1f2937; }
    .cal-item.active { background: white; border-color: #047857; box-shadow: 0 2px 8px rgba(4,120,87,0.15); }
    .cal-item.active .cal-num, .cal-item.active .cal-month { color: #065f46; }
    .cal-item.done { background: #d1fae5; }
    .cal-item.done .cal-num { color: #047857; }
    .cal-item.partial { background: #fef3c7; }
    .cal-progress { font-size: 0.65rem; color: #64748b; font-weight: 600; margin-top: 4px; line-height: 1; }
    .cal-item.done .cal-progress { color: #047857; }
    
    /* Verse Cards */
    .verse-card { background: white; border: 1px solid #e2e8f0; border-radius: 12px; padding: 12px 14px; margin-bottom: 10px; display: flex; align-items: center; justify-content: space-between; cursor: pointer; }
    .verse-card.read { background: #d1fae5; border-color: #d1fae5; }
    .check-icon { width: 22px; height: 22px; border-radius: 50%; border: 2px solid #d1d5db; display: flex; align-items: center; justify-content: center; margin-right: 10px; }
    .verse-card.read .check-icon { background: #10b981; border-color: #10b981; color: white; }
    .btn-read-link { background: #047857; color: white; padding: 6px 12px; border-radius: 8px; text-decoration: none; font-weight: 700; font-size: 0.7rem; display: flex; align-items: center; gap: 4px; }

    /* Modals & Utils */
    .modal-overlay { position: fixed; inset: 0; background: rgba(0,0,0,0.5); z-index: 9999; display: none; align-items: center; justify-content: center; backdrop-filter: blur(4px); }
    .config-fullscreen { position: fixed; inset: 0; background: #f8fafc; z-index: 99999; display: none; flex-direction: column; }
    .bottom-bar { position: fixed; bottom: 0; left: 0; right: 0; background: rgba(255,255,255,0.95); backdrop-filter: blur(12px); border-top: 1px solid #e5e7eb; padding: 10px; z-index: 200; display: grid; grid-template-columns: 1fr 1fr; gap: 10px; max-width: 800px; margin: 0 auto; padding-bottom: calc(10px + env(safe-area-inset-bottom)); }
    @media(min-width: 1024px) { .bottom-bar { left: 280px; } }
    .action-btn { background: white; border: 1px solid #e5e7eb; padding: 10px; border-radius: 10px; display: flex; flex-direction: column; align-items: center; justify-content: center; cursor: pointer; }
    .icon-box { width: 32px; height: 32px; border-radius: 10px; display: flex; align-items: center; justify-content: center; margin-bottom: 4px; }
    .icon-box.purple { background: #f3e8ff; color: #9333ea; } .icon-box.blue { background: #e0f2fe; color: #0284c7; }
</style>

<!-- HEADER STATS -->
<div style="background: white; border-bottom: 1px solid #e5e7eb; padding: 12px 16px;">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px;">
        <div style="display: flex; align-items: center; gap: 16px;">
            <div>
                <div style="font-size:1rem; font-weight:800; color:#1f2937;">
                    <span style="color:#059669"><?= $totalDaysRead ?></span><span style="color:#9ca3af; font-size:0.8rem">/<?= $totalPlanDays ?></span>
                </div>
                <div style="font-size:0.65rem; color:#6b7280; font-weight:700; text-transform:uppercase;">Dias</div>
            </div>
            <div style="border-left: 2px solid #e5e7eb; padding-left: 16px;">
                 <div style="font-size:1rem; font-weight:800; color:#10b981"><?= $totalChaptersRead ?></div>
                 <div style="font-size:0.65rem; color:#6b7280; font-weight:700; text-transform:uppercase;">Caps</div>
            </div>
            <div style="border-left: 2px solid #e5e7eb; padding-left: 16px;">
                 <div style="font-size:1rem; font-weight:800; color:#ea580c">🔥 <?= $currentStreak ?></div>
                 <div style="font-size:0.65rem; color:#6b7280; font-weight:700; text-transform:uppercase;">Streak</div>
            </div>
        </div>
        <button onclick="document.getElementById('modal-stats').style.display='flex'" style="background:linear-gradient(135deg, #8b5cf6, #7c3aed); color:white; border:none; width:48px; height:48px; border-radius:14px; cursor:pointer; display:flex; align-items:center; justify-content:center; box-shadow:0 4px 10px rgba(124,58,237,0.3);">
            <i data-lucide="bar-chart-2" width="20"></i>
        </button>
    </div>
    <!-- Progress Bar -->
    <div style="height: 6px; background: #f3f4f6; width: 100%; border-radius: 10px; overflow: hidden; position: relative;">
        <div style="height: 100%; background: #10b981; width: <?= $completionPercent ?>%; border-radius: 10px; transition: width 0.5s ease;"></div>
    </div>
</div>

<!-- CALENDAR STRIP -->
<div style="position: relative; background: white; border-bottom: 1px solid #e5e7eb;">
    <button onclick="scrollCalendar('left')" style="position: absolute; left: 0; top: 0; bottom: 0; width: 32px; z-index: 10; background: linear-gradient(90deg, white, transparent); border: none; cursor: pointer; display: flex; align-items: center; justify-content: center;"><i data-lucide="chevron-left" width="16"></i></button>
    <div class="cal-strip" id="calendar-strip"></div>
    <button onclick="scrollCalendar('right')" style="position: absolute; right: 0; top: 0; bottom: 0; width: 32px; z-index: 10; background: linear-gradient(-90deg, white, transparent); border: none; cursor: pointer; display: flex; align-items: center; justify-content: center;"><i data-lucide="chevron-right" width="16"></i></button>
</div>

<!-- MAIN CONTENT -->
<div style="max-width: 800px; margin: 0 auto; padding: 16px;">
    <div style="background: white; border: 1px solid #e5e7eb; border-radius: 12px; padding: 16px 20px; margin-bottom: 16px; display: flex; justify-content: space-between; align-items: center; box-shadow: 0 1px 2px rgba(0,0,0,0.05);">
        <div>
            <div style="font-size: 0.7rem; font-weight: 700; color: #6b7280; text-transform: uppercase; margin-bottom: 4px;">Leitura de Hoje</div>
            <h1 id="day-title" style="margin: 0; font-size: 1.4rem; font-weight: 800; color: #111827;">Carregando...</h1>
        </div>
        <div id="status-badge-container"></div>
    </div>
    
    <div id="verses-list"></div>
</div>

<!-- BOTTOM BAR -->
<div class="bottom-bar">
    <button class="action-btn" onclick="openNoteModal()"><div class="icon-box purple"><i data-lucide="pen-line" width="18"></i></div><span style="font-size:0.7rem; font-weight:600; color:#374151">Anotação</span></button>
    <button class="action-btn" onclick="openConfig()"><div class="icon-box blue"><i data-lucide="settings" width="18"></i></div><span style="font-size:0.7rem; font-weight:600; color:#374151">Opções</span></button>
</div>
<div id="save-toast" style="position:fixed; top:90px; left:50%; transform:translateX(-50%); background:#1e293b; color:white; padding:8px 16px; border-radius:20px; opacity:0; pointer-events:none; z-index:2000; transition:opacity 0.3s; display:flex; align-items:center; gap:8px;"><i data-lucide="check" width="14"></i> Salvo auto</div>

<!-- INCLUDES: Modals -->
<!-- Note Modal, Stats Modal etc are defined in previous versions. I'll include minified versions for brevity as they are unchanged logic mostly. -->
<!-- NOTE MODAL -->
<div id="modal-note" class="modal-overlay">
    <div style="background: white; width: 95%; max-width: 600px; border-radius: 20px; box-shadow: 0 25px 50px -12px rgba(0,0,0,0.25); display: flex; flex-direction: column; overflow: hidden; max-height: 90vh;">
        <div style="padding: 16px 20px; border-bottom: 1px solid #e5e7eb; display: flex; justify-content: space-between; align-items: center; background: #fff7ed;">
            <h3 style="margin:0; color:#c2410c; font-weight:700; display:flex; gap:8px; align-items:center;"><i data-lucide="pen-line" width="18"></i> Anotação</h3>
            <button onclick="document.getElementById('modal-note').style.display='none'" style="border:none; background:none; cursor:pointer;"><i data-lucide="x" width="20"></i></button>
        </div>
        <div style="padding: 20px; overflow-y: auto;">
            <input type="text" id="note-title-input" placeholder="Título..." style="width: 100%; padding: 12px; border: 1px solid #cbd5e1; border-radius: 8px; margin-bottom: 12px; font-weight: 600;">
            <textarea id="note-desc-input" placeholder="Escreva sua reflexão..." style="width: 100%; min-height: 200px; padding: 12px; border: 1px solid #cbd5e1; border-radius: 8px; resize: vertical; font-family: inherit;"></textarea>
            <button onclick="saveNote()" style="width: 100%; padding: 14px; background: #f97316; color: white; border: none; border-radius: 8px; font-weight: 700; margin-top: 16px; cursor: pointer;">Salvar</button>
        </div>
    </div>
</div>

<!-- STATS MODAL -->
<div id="modal-stats" class="modal-overlay" onclick="if(event.target===this) this.style.display='none'">
    <div style="background: white; width: 95%; max-width: 500px; border-radius: 20px; padding: 24px;">
        <div style="display:flex; justify-content:space-between; margin-bottom:20px;">
            <h2 style="margin:0; font-size:1.4rem;">Estatísticas</h2>
            <button onclick="document.getElementById('modal-stats').style.display='none'" style="border:none; background:none; cursor:pointer;"><i data-lucide="x"></i></button>
        </div>
        <div style="background: #ecfdf5; padding: 16px; border-radius: 12px; margin-bottom: 16px;">
            <p style="margin:0; color: #065f46; font-weight: 600; text-align: center;">"<?= $currentMessage ?>"</p>
        </div>
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px;">
             <div style="background: #f8fafc; padding: 16px; border-radius: 12px; text-align: center;">
                 <div style="font-size: 1.8rem; font-weight: 800; color: #1f2937;"><?= $completionPercent ?>%</div>
                 <div style="font-size: 0.7rem; color: #6b7280; font-weight: 700; text-transform: uppercase;">Concluído</div>
             </div>
             <div style="background: #f8fafc; padding: 16px; border-radius: 12px; text-align: center;">
                 <div style="font-size: 1.8rem; font-weight: 800; color: #ea580c;"><?= $currentStreak ?></div>
                 <div style="font-size: 0.7rem; color: #6b7280; font-weight: 700; text-transform: uppercase;">Dias Seguidos</div>
             </div>
             <div style="background: #f8fafc; padding: 16px; border-radius: 12px; text-align: center;">
                 <div style="font-size: 1.8rem; font-weight: 800; color: #10b981;"><?= $avgChapters ?></div>
                 <div style="font-size: 0.7rem; color: #6b7280; font-weight: 700; text-transform: uppercase;">Capítulos/Dia</div>
             </div>
             <div style="background: #f8fafc; padding: 16px; border-radius: 12px; text-align: center;">
                 <div style="font-size: 1.8rem; font-weight: 800; color: #6366f1;"><?= max(0, $daysRemaining) ?></div>
                 <div style="font-size: 0.7rem; color: #6b7280; font-weight: 700; text-transform: uppercase;">Dias Restantes</div>
             </div>
        </div>
    </div>
</div>

<!-- CONFIG MODAL -->
<div id="modal-config" class="config-fullscreen">
    <div class="config-header" style="background: white; padding: 16px; border-bottom: 1px solid #e5e7eb; display: flex; justify-content: space-between;">
        <h2 style="margin:0;">Configurações</h2>
        <button onclick="document.getElementById('modal-config').style.display='none'" style="border:none; background:none;"><i data-lucide="x"></i></button>
    </div>
    <div style="padding: 20px; max-width: 600px; margin: 0 auto;">
        
        <!-- Change Plan Section -->
        <div style="background: white; border: 1px solid #e2e8f0; border-radius: 12px; padding: 20px; margin-bottom: 20px;">
            <h3 style="margin-top: 0;">Plano Atual</h3>
            <div style="margin-bottom: 16px; padding: 12px; background: #f1f5f9; border-radius: 8px; font-weight: 600; color: #334155; display: flex; align-items: center; gap: 8px;">
                <i data-lucide="book" width="18"></i>
                <span style="text-transform: capitalize;"><?= $selectedPlanType == 'chronological' ? 'Cronológico' : ($selectedPlanType == 'mcheyne' ? 'M\'Cheyne' : 'Navigators') ?></span>
            </div>
            
            <label style="display: block; font-size: 0.85rem; font-weight: 700; color: #334155; margin-bottom: 8px;">Trocar Plano (CUIDADO!)</label>
            <select id="change-plan-select" style="width: 100%; padding: 12px; border-radius: 8px; border: 1px solid #cbd5e1; margin-bottom: 12px;">
                <option value="navigators" <?= $selectedPlanType === 'navigators' ? 'selected' : '' ?>>Navigators (300 dias)</option>
                <option value="chronological" <?= $selectedPlanType === 'chronological' ? 'selected' : '' ?>>Cronológico (365 dias)</option>
                <option value="mcheyne" <?= $selectedPlanType === 'mcheyne' ? 'selected' : '' ?>>M'Cheyne (365 dias)</option>
            </select>
            
            <label style="display: block; font-size: 0.85rem; font-weight: 700; color: #334155; margin-bottom: 8px;">Data de Início</label>
            <input type="date" id="start-date-input" value="<?= $startDateStr ?>" style="width: 100%; padding: 12px; border: 1px solid #cbd5e1; border-radius: 8px; margin-bottom: 16px;">
            
            <button onclick="saveSettings()" style="width: 100%; padding: 12px; background: #6366f1; color: white; border: none; border-radius: 8px; font-weight: 700; cursor: pointer;">Salvar Alterações</button>
        </div>

        <button onclick="resetPlan()" style="width: 100%; padding: 12px; background: #ef4444; color: white; border: none; border-radius: 8px; font-weight: 700; cursor: pointer;">
            <i data-lucide="trash-2" width="16" style="display: inline; vertical-align: middle; margin-right: 6px;"></i> Resetar Todo Progresso
        </button>
    </div>
</div>


<script>
// --- FRONTEND LOGIC ---
const serverData = <?= json_encode($progressMap) ?>;
const planType = "<?= $selectedPlanType ?>";
// Initial State
let currentMonth = <?= $currentPlanMonth ?>;
let currentDay = <?= $currentPlanDay ?>;
const dataState = serverData; 
let saveTimer = null;

// REFERENCE: Calendar Days for 365-day plans
const monthDaysRef = [0, 31, 28, 31, 30, 31, 30, 31, 31, 30, 31, 30, 31];

// Initialize global plan variable based on selection
let myPlanData = null; // Will adapt structure to { month: [days...] }

document.addEventListener('DOMContentLoaded', () => {
    // Adapter Logic: Convert specific plan structure to UI structure
    // If Navigators: direct map.
    // If Chrono/Mcheyne: Convert continuous day 1..365 to Month 1..12 structure
    
    if (readingPlans[planType]) {
        const sourceData = readingPlans[planType].data;
        
        if (readingPlans[planType].period_type === 'month_fixed') {
            myPlanData = sourceData; // Already in {1: [...], 2: [...]} format
        } else {
            // Convert Day Stream to Month Blocks
            myPlanData = {};
            let dayCounter = 1;
            for (let m = 1; m <= 12; m++) {
                myPlanData[m] = [];
                const daysInMonth = monthDaysRef[m];
                for (let d = 1; d <= daysInMonth; d++) {
                    if (sourceData[dayCounter]) {
                        myPlanData[m].push(sourceData[dayCounter]);
                    } else {
                        // Day might be missing if 365 days logic is slight off, handle grace
                        myPlanData[m].push([]); 
                    }
                    dayCounter++;
                }
            }
        }
    }
    
    // Init
    renderCalendar();
    loadDay(currentMonth, currentDay);
    lucide.createIcons();
    
    // Move modals
    document.body.append(document.getElementById('modal-note'));
    document.body.append(document.getElementById('modal-stats'));
    document.body.append(document.getElementById('modal-config'));
});

function renderCalendar() {
    const el = document.getElementById('calendar-strip'); 
    el.innerHTML = '';
    const months = ["", "JAN", "FEV", "MAR", "ABR", "MAI", "JUN", "JUL", "AGO", "SET", "OUT", "NOV", "DEZ"];
    
    // Determine how many days in CURRENT VIEWED month
    // Navigators: 25. Others: dynamic based on monthDaysRef
    let limit = 25;
    if (planType !== 'navigators') {
        limit = monthDaysRef[currentMonth];
    }
    
    for(let d=1; d<=limit; d++) {
        const key = `${currentMonth}_${d}`;
        const info = dataState[key];
        
        // Check completion based on ADAPTED plan data
        const planVerses = (myPlanData && myPlanData[currentMonth] && myPlanData[currentMonth][d-1]) ? myPlanData[currentMonth][d-1] : [];
        const isDone = planVerses.length > 0 && (info?.verses?.length || 0) >= planVerses.length;
        
        // Div creation
        const div = document.createElement('div');
        div.className = `cal-item ${currentDay === d ? 'active' : ''} ${isDone ? 'done' : ''}`;
        div.onclick = () => { currentDay = d; renderCalendar(); loadDay(currentMonth, d); };
        
        div.innerHTML = `<div class="cal-month">${months[currentMonth]}</div><div class="cal-num">${d}</div>`;
        el.appendChild(div);
        
        if(currentDay === d) setTimeout(() => div.scrollIntoView({behavior:'smooth', inline:'center'}), 100);
    }
}

function loadDay(m, d) {
    document.getElementById('day-title').innerText = `Dia ${d} de ${getMonthName(m)}`;
    const list = document.getElementById('verses-list');
    const badge = document.getElementById('status-badge-container');
    list.innerHTML = '';
    
    // Retrieve references from ADAPTED plan structure
    const verses = (myPlanData && myPlanData[m] && myPlanData[m][d-1]) ? myPlanData[m][d-1] : [];
    
    // Check Status
    const key = `${m}_${d}`;
    const savedVerses = dataState[key]?.verses || [];
    const isComplete = verses.length > 0 && savedVerses.length >= verses.length;
    
    badge.innerHTML = isComplete 
        ? '<span class="status-badge success" style="background:#dcfce7; color:#059669; padding:6px 10px; border-radius:6px; font-weight:700; font-size:0.7rem; display:flex; align-items:center; gap:4px;"><i data-lucide="check-circle" width="14"></i> Concluído</span>'
        : '<span class="status-badge pending" style="background:#fef3c7; color:#d97706; padding:6px 10px; border-radius:6px; font-weight:700; font-size:0.7rem; display:flex; align-items:center; gap:4px;"><i data-lucide="clock" width="14"></i> Pendente</span>';
    
    if (verses.length === 0) {
        list.innerHTML = '<div style="text-align:center; padding:30px; color:#94a3b8;">Nenhuma leitura programada.</div>';
    } else {
        verses.forEach((vText, idx) => {
            const isRead = savedVerses.includes(idx);
            const card = document.createElement('div');
            card.className = `verse-card ${isRead ? 'read' : ''}`;
            card.onclick = (e) => { if(!e.target.closest('a')) toggleVerse(m, d, idx); };
            
            // Generate link
            const url = getBibleLink(vText);
            
            card.innerHTML = `
                <div style="display:flex; align-items:center;">
                    <div class="check-icon"><i data-lucide="check" width="14"></i></div>
                    <span style="font-weight:600; color:#1f2937;">${vText}</span>
                </div>
                <a href="${url}" target="_blank" class="btn-read-link">LER <i data-lucide="external-link" width="12"></i></a>
            `;
            list.appendChild(card);
        });
    }
    lucide.createIcons();
}

function toggleVerse(m, d, idx) {
    const key = `${m}_${d}`;
    if (!dataState[key]) dataState[key] = { verses: [] };
    const list = dataState[key].verses;
    const exists = list.indexOf(idx);
    
    if (exists === -1) list.push(idx);
    else list.splice(exists, 1);
    
    // Update UI
    loadDay(m, d);
    renderCalendar();
    
    // Show Toast
    const toast = document.getElementById('save-toast');
    toast.style.opacity = 1;
    setTimeout(() => toast.style.opacity = 0, 1500);
    
    // Debounce Save
    clearTimeout(saveTimer);
    saveTimer = setTimeout(() => {
        const f = new FormData();
        f.append('action', 'save_progress');
        f.append('month', m);
        f.append('day', d);
        f.append('verses', JSON.stringify(list));
        fetch('leitura.php', { method: 'POST', body: f });
    }, 1000);
}

function scrollCalendar(dir) {
    const el = document.getElementById('calendar-strip');
    const amount = 200;
    
    if (dir === 'left') {
        // Go to previous month if at start?
        // Simple pixel scroll for now
        el.scrollBy({ left: -amount, behavior: 'smooth' });
        
        // Logic to switch months if user keeps scrolling left?
        if (el.scrollLeft < 50 && currentMonth > 1) {
            currentMonth--;
            currentDay = 1; // reset day to 1 or last day?
            renderCalendar();
            loadDay(currentMonth, 1);
        }
    } else {
        el.scrollBy({ left: amount, behavior: 'smooth' });
        
        // Logic to switch months 
        const maxScroll = el.scrollWidth - el.clientWidth;
        if (el.scrollLeft > maxScroll - 50 && currentMonth < 12) {
             currentMonth++;
             currentDay = 1;
             renderCalendar();
             loadDay(currentMonth, 1);
        }
    }
}

function getMonthName(m) {
    const n = ["", "Janeiro", "Fevereiro", "Março", "Abril", "Maio", "Junho", "Julho", "Agosto", "Setembro", "Outubro", "Novembro", "Dezembro"];
    return n[m];
}

// Settings & Notes Functions
function openConfig() { document.getElementById('modal-config').style.display = 'flex'; }
function openNoteModal() {
    const key = `${currentMonth}_${currentDay}`;
    document.getElementById('note-title-input').value = dataState[key]?.title || '';
    document.getElementById('note-desc-input').value = dataState[key]?.comment || '';
    document.getElementById('modal-note').style.display = 'flex';
}
function saveNote() {
    const t = document.getElementById('note-title-input').value;
    const c = document.getElementById('note-desc-input').value;
    const key = `${currentMonth}_${currentDay}`;
    if(!dataState[key]) dataState[key] = { verses:[] };
    dataState[key].title = t;
    dataState[key].comment = c;
    
    const f = new FormData();
    f.append('action', 'save_progress');
    f.append('month', currentMonth);
    f.append('day', currentDay);
    f.append('verses', JSON.stringify(dataState[key].verses));
    f.append('comment', c);
    f.append('note_title', t);
    fetch('leitura.php', { method: 'POST', body: f });
    
    document.getElementById('modal-note').style.display = 'none';
    alert('Anotação Salva!');
}
function saveSettings() {
    const f = new FormData();
    f.append('action', 'save_settings');
    f.append('start_date', document.getElementById('start-date-input').value);
    f.append('plan_type', document.getElementById('change-plan-select').value);
    fetch('leitura.php', { method:'POST', body:f }).then(r=>r.json()).then(d=>{
        if(d.success) window.location.reload();
    });
}
function resetPlan() {
    if(confirm('Tem certeza? Isso apagará TODO o progresso e não pode ser desfeito.')) {
        const f = new FormData(); f.append('action', 'reset_plan');
        fetch('leitura.php', { method:'POST', body:f }).then(()=>window.location.reload());
    }
}
</script>