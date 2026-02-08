<?php
// admin/leitura.php
header('Content-Type: text/html; charset=UTF-8');
require_once '../includes/auth.php';
require_once '../includes/layout.php';

checkLogin(); 

// Load Reading Page CSS
echo '<link rel="stylesheet" href="../assets/css/pages/leitura.css?v=' . time() . '">';


// AUTOLOAD: T├¡tulo na Tabela
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
             // Salvar data de in├¡cio
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
// --- SELECTION SCREEN IF NO PLAN ---
if (!$planStarted) {
    // 1. Render Headers
    renderAppHeader('Novo Plano');
    renderPageHeader('Escolha seu Plano', 'Jornada B├¡blica 2026');
    ?>
    <style>
        /* Compact Design System for Selection */
        .selection-container {
            max-width: 800px; /* Wider for Grid */
            margin: 0 auto;
            padding: 12px; /* Mobile first padding */
        }
        
        .plan-grid {
            display: grid;
            grid-template-columns: 1fr;
            gap: 12px;
            margin-bottom: 24px;
        }
        
        @media (min-width: 768px) {
            .plan-grid { grid-template-columns: repeat(3, 1fr); }
        }

        .plan-card {
            background: var(--bg-surface, white);
            border: 1px solid var(--border-color, var(--slate-200));
            border-radius: 12px;
            padding: 16px;
            cursor: pointer;
            transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
            display: flex;
            flex-direction: column;
            gap: 12px;
            height: 100%;
        }

        .plan-card:hover {
            transform: translateY(-2px);
            border-color: var(--slate-600);
            box-shadow: 0 4px 12px rgba(55, 106, 200, 0.15);
        }

        .plan-card.selected {
            border: 2px solid var(--slate-600);
            background: var(--slate-50);
        }
        
        .plan-icon {
            width: 40px;
            height: 40px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.2rem;
            flex-shrink: 0;
        }

        .plan-content { flex: 1; }

        .plan-title {
            font-size: 1rem;
            font-weight: 700;
            color: var(--text-main, var(--slate-800));
            margin-bottom: 4px;
        }

        .plan-desc {
            font-size: 0.8rem;
            color: var(--text-muted, var(--slate-500));
            line-height: 1.4;
        }

        .plan-badge {
            font-size: 0.7rem;
            font-weight: 700;
            text-transform: uppercase;
            padding: 4px 8px;
            border-radius: 6px;
            background: var(--slate-100);
            color: var(--slate-600);
            align-self: flex-start;
            margin-top: auto; /* Push to bottom if needed */
        }

        /* Action Bar (Sticky Bottom style on mobile, inline on desktop) */
        .action-bar {
            background: var(--bg-surface, white);
            border: 1px solid var(--border-color, var(--slate-200));
            border-radius: 16px;
            padding: 12px 16px;
            display: flex;
            flex-direction: column;
            gap: 12px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.05);
        }

        @media(min-width: 640px) {
            .action-bar { flex-direction: row; align-items: center; justify-content: space-between; }
        }

        .date-group {
            display: flex;
            align-items: center;
            gap: 8px;
            flex: 1;
        }

        .date-input {
            border: 1px solid var(--border-color, var(--slate-200));
            border-radius: 8px;
            padding: 10px;
            font-family: inherit;
            color: var(--text-main);
            background: var(--bg-body);
            font-size: 0.9rem;
            outline: none;
            width: 100%;
        }
        @media(min-width: 640px) { .date-input { width: auto; } }

        .btn-start {
            background: var(--slate-600);
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: 10px;
            font-weight: 700;
            font-size: 0.95rem;
            cursor: pointer;
            transition: all 0.2s;
            opacity: 0.5;
            pointer-events: none;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            width: 100%;
        }
        @media(min-width: 640px) { .btn-start { width: auto; } }
        
        .btn-start.active { opacity: 1; pointer-events: auto; }
        .btn-start:active { transform: scale(0.98); }

    </style>

    <div class="selection-container animate-in">
        
        <p style="margin-bottom: 20px; color: var(--text-muted); font-size: 0.9rem;">
            Selecione um roteiro para guiar sua leitura b├¡blica este ano. 
            Voc├¬ pode alterar o ritmo a qualquer momento nas configura├º├Áes.
        </p>
        
        <div class="plan-grid">
            <!-- Navigators -->
            <div class="plan-card" onclick="selectPlan('navigators', this)">
                <div class="plan-icon" style="background: #e0e7ff; color: #4338ca;">
                    <i data-lucide="compass"></i>
                </div>
                <div class="plan-content">
                    <div class="plan-title">Navigators</div>
                    <div class="plan-desc">25 dias/m├¬s. Flexibilidade m├íxima para dias corridos.</div>
                </div>
                <div class="plan-badge">Equilibrado</div>
            </div>
            
            <!-- Cronol├│gico -->
            <div class="plan-card" onclick="selectPlan('chronological', this)">
                <div class="plan-icon" style="background: var(--sage-100); color: var(--sage-700);">
                    <i data-lucide="clock"></i>
                </div>
                <div class="plan-content">
                    <div class="plan-title">Cronol├│gico</div>
                    <div class="plan-desc">Leia os fatos na ordem hist├│rica em que ocorreram.</div>
                </div>
                <div class="plan-badge">Hist├│rico</div>
            </div>
            
            <!-- M'Cheyne -->
            <div class="plan-card" onclick="selectPlan('mcheyne', this)">
                <div class="plan-icon" style="background: var(--yellow-100); color: #b45309;">
                    <i data-lucide="book-open"></i>
                </div>
                <div class="plan-content">
                    <div class="plan-title">M'Cheyne</div>
                    <div class="plan-desc">Intensivo. AT 1x e NT+Salmos 2x ao ano.</div>
                </div>
                <div class="plan-badge">Intensivo</div>
            </div>
        </div>

        <div class="action-bar">
            <div class="date-group">
                <label style="font-weight: 600; font-size: 0.9rem; white-space: nowrap;">In├¡cio:</label>
                <input type="date" id="start-date" class="date-input" value="<?= date('Y-m-d') ?>">
            </div>
            
            <button id="start-btn" class="btn-start" onclick="confirmStart()">
                <span>Confirmar Plano</span>
                <i data-lucide="arrow-right" width="18"></i>
            </button>
        </div>

        <!-- Help Info -->
        <div style="margin-top: 24px; display: grid; gap: 12px; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));">
            <div style="display: flex; gap: 10px; align-items: start; padding: 12px; background: rgba(255,255,255,0.5); border-radius: 8px;">
                <i data-lucide="smartphone" style="width: 18px; color: var(--text-muted); margin-top: 2px;"></i>
                <div style="font-size: 0.8rem; color: var(--text-muted);">
                    <strong>App PWA</strong><br>Adicione ├á tela inicial para acesso r├ípido di├írio.
                </div>
            </div>
             <div style="display: flex; gap: 10px; align-items: start; padding: 12px; background: rgba(255,255,255,0.5); border-radius: 8px;">
                <i data-lucide="bell" style="width: 18px; color: var(--text-muted); margin-top: 2px;"></i>
                <div style="font-size: 0.8rem; color: var(--text-muted);">
                    <strong>Lembretes</strong><br>Voc├¬ receber├í notifica├º├Áes para manter o ritmo.
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://unpkg.com/lucide@latest"></script>
    <script>
        lucide.createIcons();
        let selectedPlan = null;
        
        function selectPlan(id, el) {
            selectedPlan = id;
            
            // Visual Update
            document.querySelectorAll('.plan-card').forEach(c => c.classList.remove('selected'));
            el.classList.add('selected');
            
            // Button Update
            const btn = document.getElementById('start-btn');
            btn.classList.add('active');
            btn.innerHTML = `<span>Iniciar ${id.charAt(0).toUpperCase() + id.slice(1)}</span> <i data-lucide='arrow-right' width='18'></i>`;
            lucide.createIcons();
        }
        
        function confirmStart() {
            if(!selectedPlan) return;
            const date = document.getElementById('start-date').value;
            const btn = document.getElementById('start-btn');
            
            // Loading State
            btn.style.opacity = '0.7';
            btn.innerHTML = `<span class='loader'></span> Configurando...`;
            
            const f = new FormData();
            f.append('action', 'select_plan');
            f.append('plan_type', selectedPlan);
            f.append('start_date', date);
            
            fetch('leitura.php', { method: 'POST', body: f })
                .then(r => r.json())
                .then(d => {
                    if(d.success) window.location.reload();
                    else {
                        alert('Erro ao salvar: ' + d.error);
                        btn.innerHTML = `<span>Tentar Novamente</span>`;
                        btn.classList.add('active');
                    }
                });
        }
        // This is where the new code is inserted.
        document.addEventListener('DOMContentLoaded', function() {
            // Initialize Lucide Icons
            if (typeof lucide !== 'undefined') {
                lucide.createIcons();
            }
        });
    </script>
    <?php
    renderAppFooter(); // Ensure footer is closed properly if needed
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

// USER DATA & FAVORITE TIME STATS
$stmtUser = $pdo->prepare("SELECT name, birth_date FROM users WHERE id = ?");
$stmtUser->execute([$userId]);
$userDataDB = $stmtUser->fetch(PDO::FETCH_ASSOC);
$userName = $userDataDB['name'] ?? 'Usu├írio';
$userBirthDate = $userDataDB['birth_date'] ?? null;

// Calculate Favorite Time & Distributions
// 3-hour intervals: 00-03, 03-06, 06-09, 09-12, 12-15, 15-18, 18-21, 21-00
$timeSlots = [
    '00h - 03h' => 0, '03h - 06h' => 0, '06h - 09h' => 0, '09h - 12h' => 0,
    '12h - 15h' => 0, '15h - 18h' => 0, '18h - 21h' => 0, '21h - 00h' => 0
];
$weekdayStats = [0=>0, 1=>0, 2=>0, 3=>0, 4=>0, 5=>0, 6=>0]; // 0=Dom, 6=Sab
$mapWeekdays = [0=>'Dom', 1=>'Seg', 2=>'Ter', 3=>'Qua', 4=>'Qui', 5=>'Sex', 6=>'S├íb'];
$hoursLog = [];

foreach($rows as $r) {
    if(!empty($r['completed_at'])) {
        $timestamp = strtotime($r['completed_at']);
        $h = (int)date('H', $timestamp);
        $w = (int)date('w', $timestamp);
        
        // Time Slots (3h buckets)
        if ($h < 3) $timeSlots['00h - 03h']++;
        elseif ($h < 6) $timeSlots['03h - 06h']++;
        elseif ($h < 9) $timeSlots['06h - 09h']++;
        elseif ($h < 12) $timeSlots['09h - 12h']++;
        elseif ($h < 15) $timeSlots['12h - 15h']++;
        elseif ($h < 18) $timeSlots['15h - 18h']++;
        elseif ($h < 21) $timeSlots['18h - 21h']++;
        else $timeSlots['21h - 00h']++;
        
        // Weekday
        $weekdayStats[$w]++;
        
        $hoursLog[] = $h;
    }
}

// Find Max Favorite Time
$favoriteTime = '';
$maxCount = 0;
foreach($timeSlots as $slot => $count) {
    if($count > $maxCount) { $maxCount = $count; $favoriteTime = $slot; }
}
if($maxCount === 0) $favoriteTime = '---';

// Prepare data for JS
$jsTimeDist = [];
$totalTimeReads = array_sum($timeSlots);
foreach($timeSlots as $k=>$v) {
    // Only include if count > 0 for display compacting
    if($v > 0) {
        $pct = $totalTimeReads > 0 ? round(($v/$totalTimeReads)*100) : 0;
        $jsTimeDist[] = ['label'=>$k, 'count'=>$v, 'pct'=>$pct];
    }
}

$jsWeekDist = [];
$totalWeekReads = array_sum($weekdayStats);
foreach($weekdayStats as $k=>$v) {
    $pct = $totalWeekReads > 0 ? round(($v/$totalWeekReads)*100) : 0;
    $jsWeekDist[] = ['label'=>$mapWeekdays[$k], 'count'=>$v, 'pct'=>$pct];
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

// Last 7 Days Activity
$activityChart = [];
for($i=6; $i>=0; $i--) {
    $d = clone $now;
    $d->modify("-$i days");
    $dStr = $d->format('Y-m-d');
    $chaptersOnDate = 0;
    foreach($rows as $r) {
        if(substr($r['completed_at'], 0, 10) === $dStr) {
             $versesRead = json_decode($r['verses_read'] ?? '[]', true);
             if(is_array($versesRead)) $chaptersOnDate += count($versesRead);
        }
    }
    $activityChart[] = ['label' => $d->format('d/m'), 'count' => $chaptersOnDate];
}

// Predictions
$daysInPlan = max(1, $daysPassed);
$avgChapters = round($totalChaptersRead / $daysInPlan, 1);
$daysRemaining = $totalPlanDays - $totalDaysRead;

// Prediction based on pace
$pace = ($daysPassed > 0) ? ($totalDaysRead / $daysPassed) : 0; // Plan days completed per real day
$estimatedFinishDate = null;
if ($pace > 0 && $daysRemaining > 0) {
    $realDaysNeeded = $daysRemaining / $pace;
    $estDate = clone $now;
    $estDate->modify("+" . round($realDaysNeeded) . " days");
    $estimatedFinishDate = $estDate->format('d/m/Y');
} elseif ($daysRemaining <= 0) {
    $estimatedFinishDate = "Conclu├¡do!";
}


// Messages
$motivationalMessages = [
    0 => "­ƒî▒ Voc├¬ come├ºou! Cada jornada come├ºa com um passo.",
    10 => "­ƒÆ¬ Incr├¡vel! A persist├¬ncia est├í valendo a pena!",
    25 => "­ƒîƒ Voc├¬ est├í no caminho certo! Continue firme!",
    50 => "Ô£¿ Mais da metade conclu├¡da! Sua dedica├º├úo ├® inspiradora!",
    75 => "­ƒÄ» Quase l├í! Voc├¬ est├í t├úo perto de completar esta jornada!",
    90 => "­ƒÅå Reta final! Voc├¬ ├® um exemplo de disciplina!"
];
$currentMessage = $motivationalMessages[0];
foreach($motivationalMessages as $t => $msg) if($completionPercent >= $t) $currentMessage = $msg;

// Render View
// Render View
renderAppHeader('Leitura B├¡blica'); 




renderPageHeader('Plano de Leitura', 'Louvor PIB Oliveira');
?>

<!-- FRONTEND -->
<script src="../assets/js/reading_plan_data.js?v=<?= time() ?>"></script>
<script src="https://unpkg.com/lucide@latest"></script>

<style>
    /* ... (CSS preserved from original, added adjustments for 31 days columns if needed) ... */
    :root { --primary: var(--slate-500); --primary-soft: var(--slate-50); --success: var(--sage-500); }
    /* body { background-color: var(--slate-50); color: var(--slate-800); padding-bottom: 70px; font-family: -apple-system, sans-serif; } */
    
    /* Calendar Strip & Items */
    .cal-strip { display: flex !important; gap: 8px; overflow-x: auto; padding: 10px 12px; background: white; border-bottom: 1px solid var(--slate-200); scrollbar-width: none; }
    .cal-strip::-webkit-scrollbar { display: none; }
    .cal-item { min-width: 56px; height: 68px; border-radius: 12px; background: #f3f4f6; border: 2px solid transparent; display: flex; flex-direction: column; align-items: center; justify-content: center; flex-shrink: 0; cursor: pointer; transition: all 0.2s; }
    .cal-month { font-size: var(--font-caption); font-weight: 700; text-transform: uppercase; color: #6b7280; }
    .cal-num { font-size: var(--font-h1); font-weight: 800; color: #1f2937; }
    .cal-item.active { background: white; border-color: var(--sage-600); box-shadow: 0 2px 8px rgba(22, 163, 74, 0.15); }
    .cal-item.active .cal-num, .cal-item.active .cal-month { color: var(--sage-700); }
    .cal-item.done { background: var(--sage-100); }
    .cal-item.done .cal-num { color: var(--sage-600); }
    .cal-item.partial { background: var(--yellow-100); }
    .cal-progress { font-size: var(--font-caption); color: var(--slate-500); font-weight: 600; margin-top: 4px; line-height: 1; }
    .cal-item.done .cal-progress { color: var(--sage-600); }
    
    /* Verse Cards */
    .verse-card { background: white; border: 1px solid var(--slate-200); border-radius: 12px; padding: 12px 14px; margin-bottom: 10px; display: flex; align-items: center; justify-content: space-between; cursor: pointer; }
    .verse-card.read { background: var(--sage-100); border-color: var(--sage-100); }
    .check-icon { width: 22px; height: 22px; border-radius: 50%; border: 2px solid #d1d5db; display: flex; align-items: center; justify-content: center; margin-right: 10px; }
    .verse-card.read .check-icon { background: var(--sage-500); border-color: var(--sage-500); color: white; }
    .btn-read-link { background: var(--sage-600); color: white; padding: 6px 12px; border-radius: 8px; text-decoration: none; font-weight: 700; font-size: var(--font-caption); display: flex; align-items: center; gap: 4px; }

    /* Modals & Utils */
    .modal-overlay { position: fixed; inset: 0; background: rgba(0,0,0,0.5); z-index: 9999; display: none; align-items: center; justify-content: center; backdrop-filter: blur(4px); }
    
    /* MODAL CONFIG: Embedded Style */
    .config-fullscreen { 
        position: fixed; 
        top: 0; bottom: 0; right: 0; left: 0; 
        background: var(--slate-50); 
        z-index: 100; /* Lower than sidebar (usually > 100) check if needed, but sidebar takes space */
        display: none; 
        flex-direction: column; 
    }
    @media(min-width: 1024px) {
        .config-fullscreen { left: 280px; } /* Respect Sidebar */
    }
    .bottom-bar { position: fixed; bottom: 0; left: 0; right: 0; background: rgba(255,255,255,0.95); backdrop-filter: blur(12px); border-top: 1px solid #e5e7eb; padding: 10px; z-index: 200; display: grid; grid-template-columns: 1fr 1fr; gap: 10px; max-width: 800px; margin: 0 auto; padding-bottom: calc(10px + env(safe-area-inset-bottom)); }
    @media(min-width: 1024px) { .bottom-bar { left: 280px; } }
    
    .action-btn {
        display: flex; align-items: center; justify-content: center; gap: 8px;
        padding: 12px 20px; border-radius: 12px; border: none; font-weight: 700; font-size: var(--font-body);
        cursor: pointer; transition: all 0.2s; width: 100%; text-decoration: none;
    }
    .action-btn:active { transform: scale(0.98); }
    
    .btn-orange-light {
        background: #fff7ed; color: #ea580c; border: 1px solid #fed7aa;
        box-shadow: 0 4px 6px -1px rgba(234, 88, 12, 0.1), 0 2px 4px -1px rgba(234, 88, 12, 0.06);
    }
    .btn-orange-light:hover { background: #ffedd5; border-color: #fdba74; }

    .btn-blue-light {
        background: var(--slate-50); color: var(--slate-600); border: 1px solid #bfdbfe;
        box-shadow: 0 4px 6px -1px rgba(37, 99, 235, 0.1), 0 2px 4px -1px rgba(37, 99, 235, 0.06);
    }
    .btn-blue-light:hover { background: var(--slate-100); border-color: #93c5fd; }
</style>

<?php
// Tab parameter
$tab = $_GET['tab'] ?? 'reading';
?>

<!-- Tabs Navegação (Padrão Repertório) -->
<div class="repertorio-controls">
    <div class="tabs-container">
        <a href="?tab=reading" class="tab-link <?= $tab == 'reading' ? 'active' : '' ?>">📖 Texto Bíblico</a>
        <a href="?tab=dashboard" class="tab-link <?= $tab == 'dashboard' ? 'active' : '' ?>">📊 Estatísticas</a>
        <a href="?tab=achievements" class="tab-link <?= $tab == 'achievements' ? 'active' : '' ?>">🏆 Conquistas</a>
    </div>
</div>

<?php if ($tab == 'dashboard'): ?>
<!-- TAB CONTENT: DASHBOARD -->

<style>
/* Cards compactos e eficientes */
.reading-stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(160px, 1fr));
    gap: 1rem;
    max-width: 1200px;
    margin: 0 auto;
    padding: 1.5rem;
}

.stat-card-compact {
    background: white;
    border-radius: 16px;
    padding: 1rem;
    border: 1px solid rgba(0,0,0,0.06);
    box-shadow: 0 2px 8px rgba(0,0,0,0.04);
    transition: all 0.2s ease;
    text-decoration: none;
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
}

.stat-card-compact:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0,0,0,0.08);
}

.stat-header {
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.stat-icon-compact {
    width: 32px;
    height: 32px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}

.stat-icon-compact i {
    width: 18px;
    height: 18px;
}

.stat-title-compact {
    font-size: 0.8rem;
    font-weight: 600;
    color: var(--slate-600);
    text-transform: uppercase;
    letter-spacing: 0.03em;
}

.stat-value-compact {
    font-size: 1.75rem;
    font-weight: 800;
    line-height: 1;
    margin: 0.25rem 0;
}

.stat-label-compact {
    font-size: 0.75rem;
    color: var(--slate-500);
    font-weight: 500;
}

/* Cores por tema */
.stat-green { background: #f0fdf4; }
.stat-green .stat-icon-compact { background: white; color: #16a34a; }
.stat-green .stat-value-compact { color: #166534; }

.stat-cyan { background: #ecfeff; }
.stat-cyan .stat-icon-compact { background: white; color: #0891b2; }
.stat-cyan .stat-value-compact { color: #155e75; }

.stat-blue { background: #eff6ff; }
.stat-blue .stat-icon-compact { background: white; color: #2563eb; }
.stat-blue .stat-value-compact { color: #1e40af; }

.stat-amber { background: #fffbeb; }
.stat-amber .stat-icon-compact { background: white; color: #d97706; }
.stat-amber .stat-value-compact { color: #92400e; }

.stat-violet { background: #f5f3ff; }
.stat-violet .stat-icon-compact { background: white; color: #7c3aed; }
.stat-violet .stat-value-compact { color: #5b21b6; }

.stat-emerald { background: #ecfdf5; }
.stat-emerald .stat-icon-compact { background: white; color: #059669; }
.stat-emerald .stat-value-compact { color: #065f46; }

.stat-slate { background: #f1f5f9; }
.stat-slate .stat-icon-compact { background: white; color: #475569; }
.stat-slate .stat-value-compact { color: #1e293b; }

body.dark-mode .stat-card-compact {
    background: var(--bg-surface);
    border-color: var(--border-subtle);
}
</style>

<div class="reading-stats-grid">
    
    <!-- Card: Sequência -->
    <a href="#" class="stat-card-compact stat-green">
        <div class="stat-header">
            <div class="stat-icon-compact">
                <i data-lucide="flame"></i>
            </div>
            <span class="stat-title-compact">Sequência</span>
        </div>
        <div class="stat-value-compact"><?= $currentStreak ?></div>
        <div class="stat-label-compact">dias consecutivos</div>
    </a>

    <!-- Card: Capítulos -->
    <a href="#" class="stat-card-compact stat-cyan">
        <div class="stat-header">
            <div class="stat-icon-compact">
                <i data-lucide="book-open"></i>
            </div>
            <span class="stat-title-compact">Capítulos</span>
        </div>
        <div class="stat-value-compact"><?= $totalChaptersRead ?></div>
        <div class="stat-label-compact">lidos este ano</div>
    </a>

    <!-- Card: Progresso -->
    <a href="#" class="stat-card-compact stat-blue">
        <div class="stat-header">
            <div class="stat-icon-compact">
                <i data-lucide="target"></i>
            </div>
            <span class="stat-title-compact">Progresso</span>
        </div>
        <div class="stat-value-compact"><?= $completionPercent ?>%</div>
        <div class="stat-label-compact">do plano completo</div>
    </a>

    <!-- Card: Nível -->
    <a href="#" class="stat-card-compact stat-amber">
        <div class="stat-header">
            <div class="stat-icon-compact">
                <i data-lucide="award"></i>
            </div>
            <span class="stat-title-compact">Nível</span>
        </div>
        <?php $level = min(20, floor($totalDaysRead / 15) + 1); ?>
        <div class="stat-value-compact">Nv.<?= $level ?></div>
        <div class="stat-label-compact"><?= $totalDaysRead ?> dias lidos</div>
    </a>

    <!-- Card: Recorde -->
    <a href="#" class="stat-card-compact stat-violet">
        <div class="stat-header">
            <div class="stat-icon-compact">
                <i data-lucide="trophy"></i>
            </div>
            <span class="stat-title-compact">Recorde</span>
        </div>
        <div class="stat-value-compact"><?= $bestStreak ?></div>
        <div class="stat-label-compact">melhor sequência</div>
    </a>

    <!-- Card: Livros -->
    <a href="#" class="stat-card-compact stat-emerald">
        <div class="stat-header">
            <div class="stat-icon-compact">
                <i data-lucide="library"></i>
            </div>
            <span class="stat-title-compact">Livros</span>
        </div>
        <?php $booksRead = 0; ?>
        <div class="stat-value-compact"><?= $booksRead ?></div>
        <div class="stat-label-compact">livros diferentes</div>
    </a>

    <!-- Card: Total Dias -->
    <a href="#" class="stat-card-compact stat-slate">
        <div class="stat-header">
            <div class="stat-icon-compact">
                <i data-lucide="calendar-check"></i>
            </div>
            <span class="stat-title-compact">Dias Lidos</span>
        </div>
        <div class="stat-value-compact"><?= $totalDaysRead ?></div>
        <div class="stat-label-compact">total de dias</div>
    </a>

</div>

<?php endif; ?>

<?php if ($tab == 'reading'): ?>
<!-- TAB CONTENT: READING -->

<!-- CALENDAR STRIP -->

<div style="position: relative; background: white; border-bottom: 1px solid #e5e7eb;">
    <button onclick="scrollCalendar('left')" style="position: absolute; left: 0; top: 0; bottom: 0; width: 32px; z-index: 10; background: white; border: none; cursor: pointer; display: flex; align-items: center; justify-content: center;"><i data-lucide="chevron-left" width="16"></i></button>
    <div class="cal-strip" id="calendar-strip"></div>
    <button onclick="scrollCalendar('right')" style="position: absolute; right: 0; top: 0; bottom: 0; width: 32px; z-index: 10; background: white; border: none; cursor: pointer; display: flex; align-items: center; justify-content: center;"><i data-lucide="chevron-right" width="16"></i></button>
</div>

<!-- MAIN CONTENT -->
<div style="max-width: 800px; margin: 0 auto; padding: 16px;">
    <div style="background: white; border: 1px solid #e5e7eb; border-radius: 12px; padding: 20px; margin-bottom: 20px; box-shadow: 0 1px 2px rgba(0,0,0,0.05); position: relative; overflow: hidden;">


        <div style="display: flex; justify-content: space-between; align-items: start;">
            <div>
                <div style="font-size: var(--font-caption); font-weight: 700; color: #6b7280; text-transform: uppercase; margin-bottom: 4px;">Leitura de Hoje</div>
                <h1 id="day-title" style="margin: 0; font-size: var(--font-display); font-weight: 800; color: #111827; letter-spacing: -0.5px;">Carregando...</h1>
            </div>
            <div id="status-badge-container"></div>
        </div>
    </div>
    
    <div id="verses-list"></div>
</div>

<!-- BOTTOM BAR (Inside Reading Tab) -->
<div class="bottom-bar">
    <button class="action-btn btn-orange-light" onclick="openNoteModal()">
        <i data-lucide="pen-line" width="18"></i> Anotar
    </button>
    <button class="action-btn btn-blue-light" onclick="openConfig('diario')">
        <i data-lucide="book" width="18"></i> Meu Diário
    </button>
</div>

<?php endif; ?>

<?php if ($tab == 'achievements'): ?>
<!-- TAB CONTENT: ACHIEVEMENTS/GAMIFICATION -->

<style>
/* Achievements Page Styles */
.achievements-container {
    max-width: 1200px;
    margin: 0 auto;
    padding: 1.5rem;
}

.section-title {
    font-size: 1.25rem;
    font-weight: 700;
    color: var(--slate-800);
    margin-bottom: 1rem;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

/* Level Progress Card */
.level-card {
    background: linear-gradient(135deg, #fbbf24 0%, #f59e0b 100%);
    border-radius: 20px;
    padding: 2rem;
    margin-bottom: 2rem;
    color: white;
    box-shadow: 0 10px 30px rgba(245, 158, 11, 0.3);
}

.level-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1.5rem;
}

.level-number {
    font-size: 3rem;
    font-weight: 900;
    line-height: 1;
}

.level-label {
    font-size: 0.875rem;
    opacity: 0.9;
    text-transform: uppercase;
    letter-spacing: 0.05em;
}

.xp-info {
    text-align: right;
}

.xp-current {
    font-size: 1.5rem;
    font-weight: 700;
}

.xp-total {
    font-size: 0.875rem;
    opacity: 0.9;
}

.level-progress-bar {
    background: rgba(255,255,255,0.3);
    height: 12px;
    border-radius: 10px;
    overflow: hidden;
    margin-bottom: 0.5rem;
}

.level-progress-fill {
    background: white;
    height: 100%;
    border-radius: 10px;
    transition: width 0.5s ease;
}

.next-level-text {
    font-size: 0.875rem;
    opacity: 0.9;
}

/* Badges Grid */
.badges-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(140px, 1fr));
    gap: 1rem;
    margin-bottom: 2rem;
}

.badge-card {
    background: white;
    border: 2px solid #e5e7eb;
    border-radius: 16px;
    padding: 1.25rem;
    text-align: center;
    transition: all 0.2s ease;
}

.badge-card.unlocked {
    border-color: #fbbf24;
    box-shadow: 0 4px 12px rgba(251, 191, 36, 0.2);
}

.badge-card.locked {
    opacity: 0.5;
    filter: grayscale(1);
}

.badge-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 8px 20px rgba(0,0,0,0.1);
}

.badge-icon {
    font-size: 3rem;
    margin-bottom: 0.5rem;
}

.badge-name {
    font-size: 0.875rem;
    font-weight: 700;
    color: var(--slate-800);
    margin-bottom: 0.25rem;
}

.badge-desc {
    font-size: 0.75rem;
    color: var(--slate-500);
}

.badge-progress {
    margin-top: 0.5rem;
    font-size: 0.75rem;
    color: var(--amber-600);
    font-weight: 600;
}

/* HeatMap */
.heatmap-container {
    background: white;
    border-radius: 16px;
    padding: 1.5rem;
    border: 1px solid #e5e7eb;
    margin-bottom: 2rem;
}

.heatmap-grid {
    display: grid;
    grid-template-columns: repeat(7, 1fr);
    gap: 4px;
    margin-top: 1rem;
}

.heatmap-day {
    aspect-ratio: 1;
    border-radius: 4px;
    background: #f1f5f9;
    transition: all 0.2s ease;
    cursor: pointer;
    position: relative;
}

.heatmap-day.level-0 { background: #f1f5f9; }
.heatmap-day.level-1 { background: #dcfce7; }
.heatmap-day.level-2 { background: #86efac; }
.heatmap-day.level-3 { background: #22c55e; }
.heatmap-day.level-4 { background: #16a34a; }

.heatmap-day:hover {
    transform: scale(1.1);
    z-index: 10;
}

.heatmap-legend {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    margin-top: 1rem;
    font-size: 0.75rem;
    color: var(--slate-600);
}

.legend-item {
    display: flex;
    align-items: center;
    gap: 0.25rem;
}

.legend-box {
    width: 12px;
    height: 12px;
    border-radius: 2px;
}

/* Stats Cards */
.stats-row {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 1rem;
    margin-bottom: 2rem;
}

.stat-card-achievement {
    background: white;
    border-radius: 16px;
    padding: 1.5rem;
    border: 1px solid #e5e7eb;
}

.stat-icon-large {
    width: 48px;
    height: 48px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-bottom: 1rem;
}

.stat-value-large {
    font-size: 2rem;
    font-weight: 800;
    color: var(--slate-800);
    line-height: 1;
    margin-bottom: 0.5rem;
}

.stat-label-large {
    font-size: 0.875rem;
    color: var(--slate-600);
}

body.dark-mode .badge-card,
body.dark-mode .heatmap-container,
body.dark-mode .stat-card-achievement {
    background: var(--bg-surface);
    border-color: var(--border-subtle);
}
</style>

<div class="achievements-container">
    
    <?php 
    // Calculate level for achievements tab
    $level = min(20, floor($totalDaysRead / 15) + 1);
    ?>
    
    <!-- Level Progress Card -->
    <div class="level-card">
        <div class="level-header">
            <div>
                <div class="level-label">Nível Atual</div>
                <div class="level-number"><?= $level ?></div>
            </div>
            <div class="xp-info">
                <div class="xp-current"><?= $totalDaysRead * 10 ?> XP</div>
                <div class="xp-total">/ <?= $level * 150 ?> XP</div>
            </div>
        </div>
        <?php 
        $xpProgress = min(100, ($totalDaysRead * 10) / ($level * 150) * 100);
        ?>
        <div class="level-progress-bar">
            <div class="level-progress-fill" style="width: <?= $xpProgress ?>%"></div>
        </div>
        <div class="next-level-text">
            <?= max(0, ($level * 150) - ($totalDaysRead * 10)) ?> XP para o próximo nível
        </div>
    </div>

    <!-- Conquistas/Badges -->
    <h2 class="section-title">
        <i data-lucide="award"></i>
        Conquistas Desbloqueadas
    </h2>
    
    <div class="badges-grid">
        <?php
        // Define achievements
        $achievements = [
            ['icon' => '🔥', 'name' => 'Primeira Chama', 'desc' => '1 dia de leitura', 'req' => 1],
            ['icon' => '📚', 'name' => 'Leitor Iniciante', 'desc' => '7 dias de leitura', 'req' => 7],
            ['icon' => '⭐', 'name' => 'Sequência de Ferro', 'desc' => '7 dias consecutivos', 'req' => 7],
            ['icon' => '🏆', 'name' => 'Dedicado', 'desc' => '30 dias de leitura', 'req' => 30],
            ['icon' => '💎', 'name' => 'Sequência de Ouro', 'desc' => '30 dias consecutivos', 'req' => 30],
            ['icon' => '👑', 'name' => 'Mestre', 'desc' => '100 dias de leitura', 'req' => 100],
            ['icon' => '🎯', 'name' => 'Focado', 'desc' => '50 dias consecutivos', 'req' => 50],
            ['icon' => '🌟', 'name' => 'Estrela', 'desc' => '365 dias de leitura', 'req' => 365],
        ];
        
        foreach ($achievements as $achievement) {
            $unlocked = ($achievement['name'] == 'Primeira Chama' || $achievement['name'] == 'Sequência de Ferro') 
                ? ($totalDaysRead >= $achievement['req']) 
                : ($currentStreak >= $achievement['req']);
            $class = $unlocked ? 'unlocked' : 'locked';
            ?>
            <div class="badge-card <?= $class ?>">
                <div class="badge-icon"><?= $achievement['icon'] ?></div>
                <div class="badge-name"><?= $achievement['name'] ?></div>
                <div class="badge-desc"><?= $achievement['desc'] ?></div>
                <?php if (!$unlocked): ?>
                    <div class="badge-progress">🔒 Bloqueado</div>
                <?php else: ?>
                    <div class="badge-progress">✅ Desbloqueado</div>
                <?php endif; ?>
            </div>
        <?php } ?>
    </div>

</div>

<?php endif; ?>

<div id="save-toast" style="position:fixed; top:90px; left:50%; transform:translateX(-50%); background:var(--slate-800); color:white; padding:8px 16px; border-radius:20px; opacity:0; pointer-events:none; z-index:2000; transition:opacity 0.3s; display:flex; align-items:center; gap:8px;"><i data-lucide="check" width="14"></i> Salvo auto</div>

<!-- INCLUDES: Modals -->
<!-- Note Modal, Stats Modal etc are defined in previous versions. I'll include minified versions for brevity as they are unchanged logic mostly. -->
<!-- NOTE MODAL WITH RICH TEXT EDITOR -->
<div id="modal-note" class="modal-overlay">
    <div style="background: white; width: 95%; max-width: 700px; border-radius: 20px; box-shadow: 0 25px 50px -12px rgba(0,0,0,0.25); display: flex; flex-direction: column; overflow: hidden; max-height: 90vh;">
        <div style="padding: 16px 24px; background: #fff7ed; border-bottom: 1px solid #fed7aa; display: flex; justify-content: space-between; align-items: center;">
            <h3 style="margin: 0; font-size: var(--font-h2); font-weight: 800; color: #c2410c; display: flex; align-items: center; gap: 8px;">
                <i data-lucide="pen-line" width="20"></i> Anota├º├úo
            </h3>
            <button onclick="document.getElementById('modal-note').style.display='none'" style="border: none; background: none; cursor: pointer; color: #9ca3af; padding: 4px;">
                <i data-lucide="x" width="20"></i>
            </button>
        </div>
        <div style="padding: 20px 24px; overflow-y: auto; flex: 1;">
            <!-- Title Input -->
            <input type="text" id="note-title-input" placeholder="T├¡tulo da anota├º├úo..." style="width: 100%; padding: 12px 14px; border: 1px solid var(--slate-300); border-radius: 10px; font-size: var(--font-body); font-weight: 600; outline: none; margin-bottom: 16px; transition: all 0.2s;">
            
            <!-- Rich Text Editor -->
            <div style="border: 1px solid var(--slate-300); border-radius: 10px; overflow: hidden; background: white;">
                <!-- Toolbar -->
                <div style="display: flex; align-items: center; gap: 4px; padding: 8px 12px; background: var(--slate-50); border-bottom: 1px solid var(--slate-200); flex-wrap: wrap;">
                    <!-- Text Formatting -->
                    <button type="button" onclick="formatText('bold')" class="editor-btn" title="Negrito">
                        <i data-lucide="bold" width="16"></i>
                    </button>
                    <button type="button" onclick="formatText('italic')" class="editor-btn" title="It├ílico">
                        <i data-lucide="italic" width="16"></i>
                    </button>
                    <button type="button" onclick="formatText('underline')" class="editor-btn" title="Sublinhado">
                        <i data-lucide="underline" width="16"></i>
                    </button>
                    <button type="button" onclick="formatText('strikeThrough')" class="editor-btn" title="Tachado">
                        <i data-lucide="strikethrough" width="16"></i>
                    </button>
                    
                    <div style="width:1px; height:20px; background:var(--slate-200); margin:0 4px;"></div>
                    
                    <!-- Lists -->
                    <button type="button" onclick="formatText('insertUnorderedList')" class="editor-btn" title="Lista">
                        <i data-lucide="list" width="16"></i>
                    </button>
                    <button type="button" onclick="formatText('insertOrderedList')" class="editor-btn" title="Lista numerada">
                        <i data-lucide="list-ordered" width="16"></i>
                    </button>
                    
                    <div style="width:1px; height:20px; background:var(--slate-200); margin:0 4px;"></div>
                    
                    <!-- Link -->
                    <button type="button" onclick="insertLink()" class="editor-btn" title="Inserir link">
                        <i data-lucide="link" width="16"></i>
                    </button>
                    
                    <!-- Emoji Picker -->
                    <div style="position: relative;">
                        <button type="button" id="emoji-btn" onclick="toggleEmojiPicker()" class="editor-btn" title="Emoji">
                            <i data-lucide="smile" width="16"></i>
                        </button>
                        <div id="emoji-picker" style="display: none; position: absolute; top: 100%; left: 0; margin-top: 4px; background: white; border: 1px solid var(--slate-200); border-radius: 8px; padding: 8px; box-shadow: 0 4px 12px rgba(0,0,0,0.1); z-index: 1000; max-width: 280px;">
                            <div style="display: grid; grid-template-columns: repeat(8, 1fr); gap: 4px;">
                                <!-- Spiritual -->
                                <button type="button" onclick="insertEmoji('­ƒÖÅ')" class="emoji-btn">­ƒÖÅ</button>
                                <button type="button" onclick="insertEmoji('Ô£Ø´©Å')" class="emoji-btn">Ô£Ø´©Å</button>
                                <button type="button" onclick="insertEmoji('Ôø¬')" class="emoji-btn">Ôø¬</button>
                                <button type="button" onclick="insertEmoji('­ƒôû')" class="emoji-btn">­ƒôû</button>
                                <button type="button" onclick="insertEmoji('­ƒô┐')" class="emoji-btn">­ƒô┐</button>
                                <button type="button" onclick="insertEmoji('­ƒòè´©Å')" class="emoji-btn">­ƒòè´©Å</button>
                                <button type="button" onclick="insertEmoji('­ƒîê')" class="emoji-btn">­ƒîê</button>
                                <button type="button" onclick="insertEmoji('ÔÿÇ´©Å')" class="emoji-btn">ÔÿÇ´©Å</button>
                                <button type="button" onclick="insertEmoji('­ƒîÖ')" class="emoji-btn">­ƒîÖ</button>
                                <!-- Music & Worship -->
                                <button type="button" onclick="insertEmoji('­ƒÄÁ')" class="emoji-btn">­ƒÄÁ</button>
                                <button type="button" onclick="insertEmoji('­ƒÄÂ')" class="emoji-btn">­ƒÄÂ</button>
                                <button type="button" onclick="insertEmoji('­ƒÄñ')" class="emoji-btn">­ƒÄñ</button>
                                <button type="button" onclick="insertEmoji('­ƒÄ©')" class="emoji-btn">­ƒÄ©</button>
                                <button type="button" onclick="insertEmoji('­ƒÄ╣')" class="emoji-btn">­ƒÄ╣</button>
                                <button type="button" onclick="insertEmoji('­ƒÑü')" class="emoji-btn">­ƒÑü</button>
                                <button type="button" onclick="insertEmoji('­ƒÄ║')" class="emoji-btn">­ƒÄ║</button>
                                <!-- Nature -->
                                <button type="button" onclick="insertEmoji('­ƒî║')" class="emoji-btn">­ƒî║</button>
                                <button type="button" onclick="insertEmoji('­ƒî©')" class="emoji-btn">­ƒî©</button>
                                <button type="button" onclick="insertEmoji('­ƒî╝')" class="emoji-btn">­ƒî╝</button>
                                <button type="button" onclick="insertEmoji('­ƒî╗')" class="emoji-btn">­ƒî╗</button>
                                <button type="button" onclick="insertEmoji('­ƒî╣')" class="emoji-btn">­ƒî╣</button>
                                <button type="button" onclick="insertEmoji('­ƒî┐')" class="emoji-btn">­ƒî┐</button>
                                <button type="button" onclick="insertEmoji('­ƒìâ')" class="emoji-btn">­ƒìâ</button>
                                <button type="button" onclick="insertEmoji('­ƒî▒')" class="emoji-btn">­ƒî▒</button>
                                <!-- Hearts & Emotions -->
                                <button type="button" onclick="insertEmoji('ÔØñ´©Å')" class="emoji-btn">ÔØñ´©Å</button>
                                <button type="button" onclick="insertEmoji('­ƒÆø')" class="emoji-btn">­ƒÆø</button>
                                <button type="button" onclick="insertEmoji('­ƒÆÜ')" class="emoji-btn">­ƒÆÜ</button>
                                <button type="button" onclick="insertEmoji('­ƒÆÖ')" class="emoji-btn">­ƒÆÖ</button>
                                <button type="button" onclick="insertEmoji('­ƒÆ£')" class="emoji-btn">­ƒÆ£</button>
                                <button type="button" onclick="insertEmoji('­ƒñì')" class="emoji-btn">­ƒñì</button>
                                <button type="button" onclick="insertEmoji('­ƒÿè')" class="emoji-btn">­ƒÿè</button>
                                <button type="button" onclick="insertEmoji('­ƒÿç')" class="emoji-btn">­ƒÿç</button>
                            </div>
                        </div>
                    </div>
                    
                    <div style="width:1px; height:20px; background:var(--slate-200); margin:0 4px;"></div>
                    
                    <!-- Clear Formatting -->
                    <button type="button" onclick="formatText('removeFormat')" class="editor-btn" title="Limpar formata├º├úo">
                        <i data-lucide="eraser" width="16"></i>
                    </button>
                </div>
                
                <!-- Rich Text Editor Content -->
                <div 
                    id="note-desc-input" 
                    contenteditable="true" 
                    style="width: 100%; min-height: 120px; max-height: 60vh; padding: 16px; border: none; outline: none; overflow-y: auto; font-size:var(--font-body); line-height:1.6; color:var(--slate-700);"
                    data-placeholder="Digite aqui... Use a barra de ferramentas para formatar o texto."
                ></div>
            </div>
            
            <style>
                .editor-btn {
                    background: white;
                    border: 1px solid var(--slate-200);
                    border-radius: 6px;
                    padding: 6px 8px;
                    cursor: pointer;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    transition: all 0.2s;
                    color: var(--slate-500);
                }
                .editor-btn:hover {
                    background: var(--slate-50);
                    border-color: var(--slate-300);
                    color: var(--slate-700);
                }
                .editor-btn:active {
                    transform: scale(0.95);
                    background: var(--slate-100);
                }
                
                .emoji-btn {
                    background: white;
                    border: 1px solid transparent;
                    border-radius: 4px;
                    padding: 6px;
                    cursor: pointer;
                    font-size: var(--font-h2);
                    transition: all 0.2s;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                }
                .emoji-btn:hover {
                    background: var(--slate-50);
                    border-color: var(--slate-200);
                    transform: scale(1.1);
                }
                
                /* Placeholder for contenteditable */
                #note-desc-input:empty:before {
                    content: attr(data-placeholder);
                    color: var(--slate-400);
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
        <div style="padding: 16px 24px; background: var(--bg-surface); border-top: 1px solid var(--border-color); display: flex; justify-content: flex-end; gap: 12px;">
            <button onclick="document.getElementById('modal-note').style.display='none'" style="padding: 12px 20px; border: 1px solid var(--border-color); background: var(--bg-surface); color: var(--text-muted); border-radius: 10px; font-weight: 600; cursor: pointer;">Cancelar</button>
            <button onclick="saveNote()" style="padding: 12px 24px; border: none; background: var(--accent-orange); color: white; border-radius: 10px; font-weight: 700; cursor: pointer;">Salvar Anota├º├úo</button>
        </div>
    </div>
</div>





<!-- CONFIG MODAL WITH TABS -->
<div id="modal-config" class="config-fullscreen">
    <div class="config-header" style="background: white; padding: 16px 20px; border-bottom: 1px solid #e5e7eb; display: flex; justify-content: space-between; align-items: center;">
        <div style="display: flex; align-items: center; gap: 8px;">
            <button onclick="document.getElementById('modal-config').style.display='none'" style="border:none; background: #f3f4f6; color: #374151; width: 32px; height: 32px; border-radius: 8px; cursor:pointer; display:flex; align-items:center; justify-content:center;">
                <i data-lucide="chevron-left" width="20"></i>
            </button>
            <h2 style="margin:0; font-size: var(--font-h1); font-weight: 800; color: #111827; display: flex; align-items: center; gap: 8px;">
                Configura├º├Áes
            </h2>
        </div>
        <div style="display: flex; gap: 8px;">
            <button onclick="window.location.reload()" style="border:none; background: #f3f4f6; color: #374151; padding: 8px 12px; border-radius: 8px; cursor:pointer; display:flex; align-items:center; gap: 6px; font-size: 0.85rem; font-weight: 600;">
                <i data-lucide="refresh-cw" width="16"></i> Atualizar
            </button>
            <button onclick="document.getElementById('modal-config').style.display='none'" style="border:none; background:none; cursor:pointer; color: #6b7280; padding: 4px;">
                <i data-lucide="x" width="24"></i>
            </button>
        </div>
    </div>
    
    <!-- Tabs -->
    <div class="config-tabs" style="display: flex; background: white; border-bottom: 1px solid #e5e7eb; padding: 0 20px; overflow-x: auto;">
        <div class="tab-btn active" onclick="switchConfigTab('geral')" id="tab-geral" style="padding: 16px 20px; font-weight: 600; color: #6b7280; border-bottom: 2px solid transparent; cursor: pointer; transition: all 0.2s; white-space: nowrap;">
            <i data-lucide="sliders" width="16" style="display: inline; margin-right: 6px;"></i> Geral
        </div>
        <div class="tab-btn" onclick="switchConfigTab('estatisticas')" id="tab-estatisticas" style="padding: 16px 20px; font-weight: 600; color: #6b7280; border-bottom: 2px solid transparent; cursor: pointer; transition: all 0.2s; white-space: nowrap;">
            <i data-lucide="bar-chart-2" width="16" style="display: inline; margin-right: 6px;"></i> Estat├¡sticas
        </div>
        <div class="tab-btn" onclick="switchConfigTab('diario')" id="tab-diario" style="padding: 16px 20px; font-weight: 600; color: #6b7280; border-bottom: 2px solid transparent; cursor: pointer; transition: all 0.2s; white-space: nowrap;">
            <i data-lucide="book-open" width="16" style="display: inline; margin-right: 6px;"></i> Meu Di├írio
        </div>
    </div>
    
    <!-- TAB: GERAL -->
    <div id="content-geral" class="config-content" style="padding: 20px; max-width: 600px; margin: 0 auto; width: 100%;">
        <!-- Change Plan Section -->
        <div style="background: white; border: 1px solid var(--slate-200); border-radius: 12px; padding: 20px; margin-bottom: 20px;">
            <h3 style="margin-top: 0;">Plano Atual</h3>
            <div style="margin-bottom: 16px; padding: 12px; background: var(--slate-100); border-radius: 8px; font-weight: 600; color: var(--slate-700); display: flex; align-items: center; gap: 8px;">
                <i data-lucide="book" width="18"></i>
                <span style="text-transform: capitalize;"><?= $selectedPlanType == 'chronological' ? 'Cronol├│gico' : ($selectedPlanType == 'mcheyne' ? 'M\'Cheyne' : 'Navigators') ?></span>
            </div>
            
            <label style="display: block; font-size: 0.85rem; font-weight: 700; color: var(--slate-700); margin-bottom: 8px;">Trocar Plano (CUIDADO!)</label>
            <select id="change-plan-select" style="width: 100%; padding: 12px; border-radius: 8px; border: 1px solid var(--slate-300); margin-bottom: 12px;">
                <option value="navigators" <?= $selectedPlanType === 'navigators' ? 'selected' : '' ?>>Navigators (300 dias)</option>
                <option value="chronological" <?= $selectedPlanType === 'chronological' ? 'selected' : '' ?>>Cronol├│gico (365 dias)</option>
                <option value="mcheyne" <?= $selectedPlanType === 'mcheyne' ? 'selected' : '' ?>>M'Cheyne (365 dias)</option>
            </select>
            
            <label style="display: block; font-size: 0.85rem; font-weight: 700; color: var(--slate-700); margin-bottom: 8px;">Data de In├¡cio</label>
            <input type="date" id="start-date-input" value="<?= $startDateStr ?>" style="width: 100%; padding: 12px; border: 1px solid var(--slate-300); border-radius: 8px; margin-bottom: 16px;">
            
            <button onclick="saveSettings()" style="width: 100%; padding: 12px; background: #6366f1; color: white; border: none; border-radius: 8px; font-weight: 700; cursor: pointer;">Salvar Altera├º├Áes</button>
        </div>

        <button onclick="resetPlan()" style="width: 100%; padding: 12px; background: var(--rose-500); color: white; border: none; border-radius: 8px; font-weight: 700; cursor: pointer;">
            <i data-lucide="trash-2" width="16" style="display: inline; vertical-align: middle; margin-right: 6px;"></i> Resetar Todo Progresso
        </button>
    </div>

    <!-- TAB: ESTAT├ìSTICAS -->
    <div id="content-estatisticas" class="config-content" style="display:none; padding: 20px; max-width: 900px; margin: 0 auto; width: 100%;">
        <!-- Reports Section -->
        <div style="margin-bottom: 24px; display: flex; justify-content: flex-end;">
            <button onclick="exportDiary('pdf')" style="padding: 10px 16px; background: white; color: #374151; border: 1px solid #d1d5db; border-radius: 8px; font-weight: 600; cursor: pointer; font-size: 0.85rem; display: flex; align-items: center; gap: 6px; transition: all 0.2s;">
                <i data-lucide="file-text" width="16"></i> Baixar Relat├│rio Completo
            </button>
        </div>

        <!-- Motivation Banner -->
        <div style="background: #10b981; padding: 20px; border-radius: 16px; color: white; margin-bottom: 24px; position: relative; overflow: hidden;">
            <div style="position: absolute; right: -10px; top: -10px; opacity: 0.2; transform: rotate(15deg);">
                <i data-lucide="trophy" width="100" height="100"></i>
            </div>
            <p style="margin: 0; font-weight: 600; font-size: 0.95rem; opacity: 0.9; margin-bottom: 8px;">Mensagem do Dia</p>
            <p style="margin: 0; font-size: 1.1rem; font-weight: 700; line-height: 1.4;">"<?= $currentMessage ?>"</p>
        </div>

        <!-- Main Metrics Grid -->
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(140px, 1fr)); gap: 16px; margin-bottom: 24px;">
            <!-- Completion Card -->
            <div style="background: white; border: 1px solid #e5e7eb; border-radius: 16px; padding: 16px; text-align: center; box-shadow: 0 2px 4px rgba(0,0,0,0.02);">
                <div style="position: relative; width: 80px; height: 80px; margin: 0 auto 12px auto; display: flex; align-items: center; justify-content: center;">
                    <svg viewBox="0 0 36 36" style="width: 100%; height: 100%;">
                        <path d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" fill="none" stroke="#e5e7eb" stroke-width="3.8" />
                        <path d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" fill="none" stroke="#6366f1" stroke-width="3.8" stroke-dasharray="<?= $completionPercent ?>, 100" style="transition: stroke-dasharray 1s ease 0s;" />
                    </svg>
                    <div style="position: absolute; font-weight: 800; font-size: 1.2rem; color: #1f2937;"><?= $completionPercent ?>%</div>
                </div>
                <div style="font-size: 0.75rem; font-weight: 700; text-transform: uppercase; color: #6b7280;">Conclu├¡do</div>
            </div>

            <!-- Streak Card -->
            <div style="background: white; border: 1px solid #e5e7eb; border-radius: 16px; padding: 16px; display: flex; flex-direction: column; justify-content: center; align-items: center; box-shadow: 0 2px 4px rgba(0,0,0,0.02);">
                <div style="background: #fff7ed; width: 48px; height: 48px; border-radius: 12px; display: flex; align-items: center; justify-content: center; color: #ea580c; margin-bottom: 12px;">
                    <i data-lucide="flame" width="24"></i>
                </div>
                <div style="font-size: 1.8rem; font-weight: 800; color: #ea580c; line-height: 1;"><?= $currentStreak ?></div>
                <div style="font-size: 0.75rem; font-weight: 700; text-transform: uppercase; color: #6b7280; margin-bottom: 4px;">Dias Seguidos</div>
                <div style="font-size: 0.7rem; color: #9ca3af;">Recorde: <span style="font-weight: 700; color: #ea580c;"><?= $bestStreak ?></span></div>
            </div>

            <!-- Pace Card -->
            <div style="background: white; border: 1px solid #e5e7eb; border-radius: 16px; padding: 16px; display: flex; flex-direction: column; justify-content: center; align-items: center; box-shadow: 0 2px 4px rgba(0,0,0,0.02);">
                <div style="background: var(--primary-50); width: 48px; height: 48px; border-radius: 12px; display: flex; align-items: center; justify-content: center; color: var(--primary); margin-bottom: 12px;">
                    <i data-lucide="book-open" width="24"></i>
                </div>
                <div style="font-size: 1.8rem; font-weight: 800; color: var(--primary); line-height: 1;"><?= $avgChapters ?></div>
                <div style="font-size: 0.75rem; font-weight: 700; text-transform: uppercase; color: #6b7280;">Caps/Dia (M├®dia)</div>
            </div>
        </div>

        <!-- HABIT ANALYSIS CHARTS -->
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 16px; margin-bottom: 24px;">
            <!-- Weekday Distribution -->
            <div style="background: white; border: 1px solid #e5e7eb; border-radius: 16px; padding: 20px;">
                <h4 style="margin: 0 0 16px 0; font-size: 0.9rem; font-weight: 700; color: #374151; display: flex; align-items: center; gap: 8px;">
                    <i data-lucide="calendar-check" width="16"></i> Frequ├¬ncia Semanal
                </h4>
                <div style="display: flex; justify-content: space-between; align-items: flex-end; height: 100px; gap: 6px;">
                    <?php foreach($jsWeekDist as $wd): 
                        $h = max(10, $wd['pct']); 
                        $bg = $wd['pct'] > 0 ? 'var(--lavender-600)' : 'var(--slate-100)';
                        $txt = $wd['pct'] > 0 ? '#4c1d95' : 'var(--slate-400)';
                    ?>
                    <div style="flex: 1; display: flex; flex-direction: column; align-items: center; gap: 6px;">
                        <input type="hidden" value="<?= $wd['count'] ?> leituras">
                        <div style="width: 100%; height: 100%; display: flex; align-items: flex-end;">
                             <div style="width: 100%; height: <?= $h ?>%; background: <?= $bg ?>; border-radius: 4px; transition: height 0.3s; position: relative;"></div>
                        </div>
                        <span style="font-size: 0.65rem; font-weight: 700; color: <?= $txt ?>;"><?= substr($wd['label'],0,3) ?></span>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            
            <!-- Time Distribution -->
             <div style="background: white; border: 1px solid #e5e7eb; border-radius: 16px; padding: 20px;">
                <h4 style="margin: 0 0 16px 0; font-size: 0.9rem; font-weight: 700; color: #374151; display: flex; align-items: center; gap: 8px;">
                    <i data-lucide="clock" width="16"></i> Hor├írios Preferidos
                </h4>
                <div style="display: flex; flex-direction: column; gap: 12px;">
                    <?php foreach($jsTimeDist as $td): 
                         if($td['pct'] == 0 && $totalTimeReads > 0) continue; // Skip empty if we have data
                         $w = max(5, $td['pct']);
                    ?>
                    <div style="display: flex; align-items: center; gap: 10px;">
                        <span style="width: 70px; font-size: 0.75rem; font-weight: 600; color: var(--slate-500);"><?= $td['label'] ?></span>
                        <div style="flex: 1; height: 24px; background: var(--slate-100); border-radius: 6px; overflow: hidden; position: relative;">
                            <div style="width: <?= $w ?>%; height: 100%; background: #10b981; border-radius: 6px;"></div>
                        </div>
                        <span style="width: 30px; font-size: 0.75rem; font-weight: 700; color: #111827; text-align: right;"><?= $td['count'] ?></span>
                    </div>
                    <?php endforeach; ?>
                    <?php if($totalTimeReads == 0): ?>
                        <div style="text-align: center; color: var(--slate-400); font-size: 0.8rem; padding: 10px;">Sem dados ainda</div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Activity Chart (Last 7 Days) -->
        <div style="background: white; border: 1px solid #e5e7eb; border-radius: 16px; padding: 20px; margin-bottom: 24px;">
            <h4 style="margin: 0 0 16px 0; font-size: 0.9rem; font-weight: 700; color: #374151; display: flex; align-items: center; gap: 8px;">
                <i data-lucide="bar-chart-2" width="16"></i> Atividade (├Ültimos 7 Dias)
            </h4>
            <div style="display: flex; justify-content: space-between; align-items: flex-end; height: 120px; gap: 8px;">
                <?php 
                $maxVal = 0; foreach($activityChart as $d) $maxVal = max($maxVal, $d['count']);
                $maxVal = max($maxVal, 5); // Minimum scale
                foreach($activityChart as $day): 
                    $h = ($day['count'] / $maxVal) * 100;
                    $color = $day['count'] > 0 ? '#6366f1' : 'var(--slate-100)';
                ?>
                <div style="flex: 1; display: flex; flex-direction: column; align-items: center; gap: 6px;">
                    <div style="width: 100%; background: var(--slate-100); border-radius: 6px; height: 100%; position: relative; display: flex; align-items: flex-end; overflow: hidden;">
                        <div style="width: 100%; height: <?= $h ?>%; background: <?= $color ?>; border-radius: 6px; transition: height 0.5s;"></div>
                    </div>
                    <span style="font-size: 0.7rem; font-weight: 600; color: #6b7280;"><?= $day['label'] ?></span>
                </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Detailed Stats Table -->
        <div style="background: white; border: 1px solid #e5e7eb; border-radius: 16px; overflow: hidden;">
            <div style="padding: 16px 20px; border-bottom: 1px solid #f3f4f6; font-weight: 700; font-size: 0.9rem; color: #374151;">Detalhes da Jornada</div>
            <div style="padding: 0 20px;">
                <div style="display: flex; justify-content: space-between; padding: 14px 0; border-bottom: 1px solid #f3f4f6;">
                    <span style="color: #6b7280; font-size: 0.9rem;">Dias Restantes</span>
                    <span style="font-weight: 600; color: #111827;"><?= $daysRemaining ?></span>
                </div>
                <div style="display: flex; justify-content: space-between; padding: 14px 0; border-bottom: 1px solid #f3f4f6;">
                    <span style="color: #6b7280; font-size: 0.9rem;">Total Lido</span>
                    <span style="font-weight: 600; color: #111827;"><?= $totalChaptersRead ?> cap├¡tulos</span>
                </div>
                <div style="display: flex; justify-content: space-between; padding: 14px 0;">
                    <span style="color: #6b7280; font-size: 0.9rem;">Previs├úo de T├®rmino</span>
                    <span style="font-weight: 700; color: #6366f1;"><?= $estimatedFinishDate ? $estimatedFinishDate : '---' ?></span>
                </div>
            </div>
        </div>
    </div>
    
    <!-- TAB: MEU DI├üRIO -->
    <div id="content-diario" class="config-content" style="display:none; padding: 20px; max-width: 900px; margin: 0 auto; width: 100%;">
        <!-- Export Button with Dropdown -->
        <div style="margin-bottom: 20px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px;">
            <h3 style="margin: 0; font-size: 1.1rem; font-weight: 700; color: #111827;">Minhas Anota├º├Áes</h3>
            <?php if(!empty($reportData)): ?>
            <div style="position: relative;">
                <button onclick="toggleExportMenu()" id="export-btn" class="ripple" style="padding: 10px 16px; background: #6366f1; color: white; border: none; border-radius: 8px; font-weight: 600; cursor: pointer; font-size: 0.85rem; display: flex; align-items: center; gap: 6px; transition: all 0.2s;">
                    <i data-lucide="download" width="16"></i> Exportar Di├írio <i data-lucide="chevron-down" width="14"></i>
                </button>
                
                <!-- Dropdown Menu -->
                <div id="export-menu" style="display: none; position: absolute; top: 100%; right: 0; margin-top: 4px; background: white; border: 1px solid #e5e7eb; border-radius: 8px; box-shadow: 0 4px 12px rgba(0,0,0,0.1); z-index: 1000; min-width: 180px; overflow: hidden;">
                    <button onclick="exportDiary('word')" style="width: 100%; padding: 10px 16px; border: none; background: white; text-align: left; cursor: pointer; display: flex; align-items: center; gap: 8px; font-size: 0.85rem; color: #374151; transition: all 0.2s; border-bottom: 1px solid #f3f4f6;">
                        <i data-lucide="file-text" width="16" style="color: var(--slate-600);"></i>
                        <span style="font-weight: 600;">Exportar como Word</span>
                    </button>
                    <button onclick="exportDiary('pdf')" style="width: 100%; padding: 10px 16px; border: none; background: white; text-align: left; cursor: pointer; display: flex; align-items: center; gap: 8px; font-size: 0.85rem; color: #374151; transition: all 0.2s;">
                        <i data-lucide="file" width="16" style="color: var(--rose-600);"></i>
                        <span style="font-weight: 600;">Exportar como PDF</span>
                    </button>
                </div>
            </div>
            <?php endif; ?>
        </div>
        
        <!-- Search Filter -->
        <?php if(!empty($reportData)): ?>
        <div style="margin-bottom: 20px; position: relative;">
            <i data-lucide="search" width="18" style="position: absolute; left: 14px; top: 50%; transform: translateY(-50%); color: #9ca3af;"></i>
            <input type="text" id="diary-search" onkeyup="filterDiary()" placeholder="Buscar anota├º├Áes (t├¡tulo, conte├║do, data)..." style="width: 100%; padding: 12px 14px 12px 42px; border: 1px solid var(--slate-300); border-radius: 8px; font-size: 0.95rem; outline: none; transition: all 0.2s;">
        </div>
        <?php endif; ?>
        
        <style>
            #export-menu button:hover { background: #f9fafb; }
            .tab-btn.active { color: #6366f1 !important; border-bottom-color: #6366f1 !important; }
            .tab-btn:hover { color: #374151; }
            
            /* Modern Timeline & Cards */
            .timeline-container { position: relative; padding-left: 20px; }
            .timeline-container::before {
                content: ''; position: absolute; left: 6px; top: 0; bottom: 0; width: 2px; background: var(--slate-200); border-radius: 2px;
            }
            
            .group-header { 
                position: relative; padding: 20px 0 10px 0; background: var(--slate-50); z-index: 1; cursor: pointer; user-select: none;
                transition: opacity 0.2s;
            }
            .group-header:hover { opacity: 0.8; }
            .group-header .toggle-icon { transition: transform 0.3s; }
            .group-header.collapsed .toggle-icon { transform: rotate(-90deg); }
            
            .month-label { 
                font-size: 1.2rem; font-weight: 800; color: var(--slate-800); text-transform: capitalize; 
                display: flex; align-items: center; justify-content: space-between; margin-bottom: 4px;
            }
            .week-label {
                font-size: 0.85rem; font-weight: 600; color: var(--slate-500); text-transform: uppercase; letter-spacing: 1px;
                padding-left: 4px; margin-bottom: 12px;
            }
            
            .diary-card {
                background: white; border-radius: 16px; padding: 20px; margin-bottom: 24px;
                box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05), 0 2px 4px -1px rgba(0,0,0,0.03);
                border: 1px solid white;
                transition: transform 0.2s, box-shadow 0.2s;
                position: relative;
            }
            .diary-card:hover { transform: translateY(-2px); box-shadow: 0 10px 15px -3px rgba(0,0,0,0.08); }
            
            .diary-card::before {
                content: ''; position: absolute; left: -26px; top: 24px; width: 14px; height: 14px;
                background: #6366f1; border: 3px solid var(--slate-50); border-radius: 50%; box-shadow: 0 2px 4px rgba(99,102,241,0.3);
            }
            
            .diary-date { font-size: 0.8rem; color: var(--slate-500); font-weight: 600; margin-bottom: 8px; display: flex; align-items: center; gap: 6px; }
            
            .diary-title { font-size: 1.15rem; font-weight: 700; color: var(--slate-800); margin: 0 0 10px 0; line-height: 1.4; }
            .diary-content { color: var(--slate-700); line-height: 1.6; font-size: 0.95rem; }
            
            .diary-content b, .diary-content strong { font-weight: 700; }
            .diary-content i, .diary-content em { font-style: italic; }
            .diary-content u { text-decoration: underline; }
            .diary-content ul, .diary-content ol { margin-left: 20px; margin-top: 8px; margin-bottom: 8px; }
            .diary-content li { margin-bottom: 4px; }
            .diary-content a { color: #6366f1; text-decoration: underline; }
            
            /* Truncation Logic */
            .diary-content.truncated {
                max-height: 120px;
                overflow: hidden;
                mask-image: none;
            }
            .read-more-btn {
                width: 100%;
                background: var(--slate-50);
                border: 1px solid var(--slate-200);
                border-top: none;
                padding: 8px;
                color: #6366f1;
                font-weight: 600;
                font-size: 0.85rem;
                cursor: pointer;
                display: flex;
                align-items: center;
                justify-content: center;
                gap: 6px;
                border-radius: 0 0 12px 12px;
                margin-top: 0;
                transition: background 0.2s;
            }
            .read-more-btn:hover { background: var(--slate-100); }
        </style>
        
        <?php if(empty($reportData)): ?>
        <!-- Empty State -->
        <div style="text-align: center; padding: 60px 20px; background: white; border-radius: 12px; border: 1px solid #e5e7eb; margin-top: 20px;">
            <div style="background: #f3f4f6; width: 80px; height: 80px; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 16px; color: #9ca3af;">
                <i data-lucide="book-open" width="40"></i>
            </div>
            <h3 style="margin: 0 0 8px 0; font-size: 1.1rem; font-weight: 700; color: #111827;">Nenhuma anota├º├úo ainda</h3>
            <p style="margin: 0; font-size: 0.9rem; color: #6b7280;">Comece a registrar suas reflex├Áes sobre as leituras b├¡blicas!</p>
        </div>
        <?php else: ?>
        <!-- Diary Entries Timeline -->
        <div id="diary-entries-container" class="timeline-container">
        <?php 
            $currentMonthLabel = '';
            $currentWeekLabel = '';
            $monthsPT = [1=>'Janeiro', 2=>'Fevereiro', 3=>'Mar├ºo', 4=>'Abril', 5=>'Maio', 6=>'Junho', 7=>'Julho', 8=>'Agosto', 9=>'Setembro', 10=>'Outubro', 11=>'Novembro', 12=>'Dezembro'];
            
            foreach($reportData as $rep): 
                $d = new DateTime($rep['date']);
                $mLabel = $monthsPT[(int)$d->format('n')] . ' ' . $d->format('Y');
                $wLabel = 'Semana ' . $d->format('W');
                
                // MONTH HEADER
                if($mLabel !== $currentMonthLabel): ?>
                    <div class="group-header month-header" onclick="toggleGroup(this, 'month')" style="padding-top:24px; padding-bottom:12px; border-bottom:1px solid var(--slate-200); margin-bottom:12px;">
                        <div class="month-label" style="display:flex; align-items:center; justify-content:space-between; width:100%;">
                            <div style="display:flex; align-items:center; gap:8px; font-size:1.1rem; font-weight:800; color:var(--slate-800);">
                                <i data-lucide="calendar" width="20" style="color:#6366f1;"></i> <?= $mLabel ?>
                            </div>
                            <i data-lucide="chevron-down" class="toggle-icon" width="20" style="color:var(--slate-400);"></i>
                        </div>
                    </div>
                <?php 
                    $currentMonthLabel = $mLabel;
                    $currentWeekLabel = ''; // Reset week label on new month
                endif; 
                
                // WEEK HEADER
                if($wLabel !== $currentWeekLabel): ?>
                    <div class="group-header week-header" onclick="toggleGroup(this, 'week')" style="margin-left:8px; padding:10px 0; cursor:pointer; display:flex; align-items:center; gap:8px;">
                        <div class="week-label" style="margin:0; font-size:0.85rem; color:var(--slate-500); font-weight:700; text-transform:uppercase; letter-spacing:0.5px;"><?= $wLabel ?></div>
                         <i data-lucide="chevron-down" class="toggle-icon" width="14" style="color:var(--slate-300);"></i>
                    </div>
                <?php 
                    $currentWeekLabel = $wLabel;
                endif; 
                ?>
                
            <div class="diary-card" data-search-content="<?= strtolower(htmlspecialchars($rep['title'] ?? '') . ' ' . strip_tags($rep['comment'] ?? '') . ' ' . date('d/m/Y', strtotime($rep['date']))) ?>">
                <div style="display:flex; justify-content:space-between; align-items:start;">
                    <div class="diary-date">
                        <i data-lucide="clock" width="14"></i> <?= $d->format('d \d\e F \├á\s H:i') ?>
                         <span style="width: 4px; height: 4px; background: var(--slate-300); border-radius: 50%; display: inline-block; margin: 0 8px;"></span>
                         Dia <?= $rep['d'] ?>
                    </div>
                    
                    <button onclick="shareEntry('<?= addslashes($rep['title']??'Leitura do Dia') ?>', '<?= $d->format('d/m/Y') ?>', `<?= addslashes(str_replace(["\r", "\n"], ' ', strip_tags($rep['comment'] ?? ''))) ?>`)" class="ripple" style="background:transparent; border:none; cursor:pointer; padding:8px; border-radius:50%; color:#6b7280; display:flex; align-items:center; justify-content:center; transition:background 0.2s;" title="Compartilhar">
                        <i data-lucide="share-2" width="18"></i>
                    </button>
                </div>
                
                <?php if($rep['title']): ?>
                <h4 class="diary-title"><?= htmlspecialchars($rep['title']) ?></h4>
                <?php endif; ?>
                
                <?php if($rep['comment']): 
                    $cleanComment = strip_tags($rep['comment']);
                    $isLong = mb_strlen($cleanComment) > 300 || substr_count($cleanComment, "\n") > 4;
                    $uniqueId = 'diary-' . $rep['m'] . '-' . $rep['d'];
                ?>
                <div class="diary-content <?= $isLong ? 'truncated' : '' ?>" id="<?= $uniqueId ?>" style="margin-top: 8px;">
                    <?= $rep['comment'] ?>
                </div>
                <?php if($isLong): ?>
                    <button onclick="toggleDiaryExpanded('<?= $uniqueId ?>', this)" class="read-more-btn">
                        Ver tudo <i data-lucide="chevron-down" width="14"></i>
                    </button>
                <?php endif; ?>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>
</div>



<script>
// --- FRONTEND LOGIC ---
const serverData = <?= json_encode($progressMap) ?>;
const planType = "<?= $selectedPlanType ?>";
const startDateStr = "<?= $startDateStr ?>"; 
const userData = {
    name: "<?= addslashes($userName) ?>",
    birthDate: "<?= $userBirthDate ?>",
    favoriteTime: "<?= $favoriteTime ?>"
};
const statsData = {
    week: <?= json_encode($jsWeekDist) ?>,
    time: <?= json_encode($jsTimeDist) ?>
};

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
    
    // Parse start date
    const startDate = new Date(startDateStr + 'T00:00:00');
    
    // Determine how many days in CURRENT VIEWED month
    // Navigators: 25. Others: dynamic based on monthDaysRef
    let limit = 25;
    if (planType !== 'navigators') {
        limit = monthDaysRef[currentMonth];
    }
    
    for(let d=1; d<=limit; d++) {
        const key = `${currentMonth}_${d}`;
        const info = dataState[key];
        
        // Calculate the actual calendar date for this plan day
        // For Navigators: month/day is already correct (Month 1 Day 1 = first day)
        // For 365-day plans: need to calculate from start_date
        let actualDate;
        let displayMonth, displayDay;
        
        if (planType === 'navigators') {
            // Navigators: Calculate based on month and day within that month
            // Month 1 Day 1 = start_date + 0 days
            // Month 1 Day 2 = start_date + 1 day
            // Month 2 Day 1 = start_date + 25 days
            const dayOffset = ((currentMonth - 1) * 25) + (d - 1);
            actualDate = new Date(startDate);
            actualDate.setDate(startDate.getDate() + dayOffset);
        } else {
            // 365-day plans: Calculate absolute day number from month/day
            let absoluteDay = 0;
            for (let m = 1; m < currentMonth; m++) {
                absoluteDay += monthDaysRef[m];
            }
            absoluteDay += d;
            
            actualDate = new Date(startDate);
            actualDate.setDate(startDate.getDate() + absoluteDay - 1);
        }
        
        displayMonth = months[actualDate.getMonth() + 1];
        displayDay = actualDate.getDate();
        
        // Check completion based on ADAPTED plan data
        const planVerses = (myPlanData && myPlanData[currentMonth] && myPlanData[currentMonth][d-1]) ? myPlanData[currentMonth][d-1] : [];
        const isDone = planVerses.length > 0 && (info?.verses?.length || 0) >= planVerses.length;
        
        // Div creation
        const div = document.createElement('div');
        div.className = `cal-item ${currentDay === d ? 'active' : ''} ${isDone ? 'done' : ''}`;
        div.onclick = () => { currentDay = d; renderCalendar(); loadDay(currentMonth, d); };
        
        div.innerHTML = `<div class="cal-month">${displayMonth}</div><div class="cal-num">${displayDay}</div>`;
        el.appendChild(div);
        
        if(currentDay === d) setTimeout(() => div.scrollIntoView({behavior:'smooth', inline:'center'}), 100);
    }
}

function loadDay(m, d) {
    // GENERIC TITLE: "Dia X" or "Leitura do Dia X"
    // We can try to calculate the absolute day if generic, or just stick to m/d
    let displayTitle = '';
    
    if (planType === 'navigators') {
        const absoluteDay = ((m-1)*25) + d;
        displayTitle = `Dia ${absoluteDay}`;
    } else {
        // For 365 days, we can try to find the absolute day or just "Dia d do M├¬s m"
        // User requested generic. "Dia X" is best.
        let absoluteDay = 0;
        for(let i=1; i<m; i++) absoluteDay += monthDaysRef[i];
        absoluteDay += d;
        displayTitle = `Dia ${absoluteDay}`;
    }
    
    document.getElementById('day-title').innerText = displayTitle;
    
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
        ? '<span class="status-badge success" style="background:var(--primary-subtle); color:var(--primary); padding:6px 10px; border-radius:6px; font-weight:700; font-size:0.7rem; display:flex; align-items:center; gap:4px;"><i data-lucide="check-circle" width="14"></i> Conclu├¡do</span>'
        : '<span class="status-badge pending" style="background:var(--yellow-100); color:var(--yellow-600); padding:6px 10px; border-radius:6px; font-weight:700; font-size:0.7rem; display:flex; align-items:center; gap:4px;"><i data-lucide="clock" width="14"></i> Pendente</span>';
    
    if (verses.length === 0) {
        list.innerHTML = '<div style="text-align:center; padding:30px; color:var(--slate-400);">Nenhuma leitura programada.</div>';
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
    const n = ["", "Janeiro", "Fevereiro", "Mar├ºo", "Abril", "Maio", "Junho", "Julho", "Agosto", "Setembro", "Outubro", "Novembro", "Dezembro"];
    return n[m];
}

// Settings & Notes Functions
function openConfig(defaultTab = 'geral') { 
    document.getElementById('modal-config').style.display = 'flex';
    switchConfigTab(defaultTab);
}

// Collapsible Logic
function toggleGroup(header, type) {
    header.classList.toggle('collapsed');
    let content = header.nextElementSibling;
    
    // Determine loop condition based on type
    // If Month: stop at next Month Header
    // If Week: stop at next Week Header OR Month Header
    
    while(content) {
        if(type === 'month') {
            if(content.classList.contains('month-header')) break; // Stop at next month
            
            // Toggle EVERYTHING inside the month
            if(header.classList.contains('collapsed')) {
                // Save state? No, simply hide everything
                content.setAttribute('data-visible-state', content.style.display);
                content.style.display = 'none';
            } else {
                // Restore? Or just Block?
                // If we want to restore exact state, we need to check sub-headers.
                // Simple approach: Show all headers. Show Cards IF their parent week is not collapsed.
                // BUT, week headers themselves might be collapsed.
                
                // Let's simplified approach: When expanding Month, expand EVERYTHING? 
                // Or respect collapsed weeks?
                // Hard to respect collapsed weeks in a flat list without saving state on every element.
                
                // Reset to visible standard
                if(content.classList.contains('week-header') || content.classList.contains('diary-card')) {
                     content.style.display = 'block';
                     content.classList.remove('collapsed'); // Expand weeks too for simplicity
                }
            }
        } 
        else if(type === 'week') {
            if(content.classList.contains('week-header') || content.classList.contains('month-header')) break;
            
            // Toggle only cards in this week
            if(content.classList.contains('diary-card')) {
                content.style.display = header.classList.contains('collapsed') ? 'none' : 'block';
            }
        }
        
        content = content.nextElementSibling;
    }
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
            document.execCommand('createLink', false, url);
        } else {
            document.execCommand('insertHTML', false, `<a href="${url}" target="_blank" style="color:#047857; text-decoration:underline;">${url}</a>`);
        }
        document.getElementById('note-desc-input').focus();
    }
}

// Close emoji picker when clicking outside
function toggleDiaryExpanded(id, btn) {
    const el = document.getElementById(id);
    if(el.classList.contains('truncated')) {
        el.classList.remove('truncated');
        btn.innerHTML = 'Ver menos <i data-lucide="chevron-up" width="14"></i>';
        lucide.createIcons();
    } else {
        el.classList.add('truncated');
        btn.innerHTML = 'Ver tudo <i data-lucide="chevron-down" width="14"></i>';
        lucide.createIcons();
        // Scroll back to card top if needed
        el.scrollIntoView({ behavior: 'smooth', block: 'center' });
    }
}
document.addEventListener('click', function(e) {
    const picker = document.getElementById('emoji-picker');
    const emojiBtn = document.getElementById('emoji-btn');
    if (picker && !picker.contains(e.target) && e.target !== emojiBtn && !emojiBtn.contains(e.target)) {
        picker.style.display = 'none';
    }
});

function openNoteModal() {
    const key = `${currentMonth}_${currentDay}`;
    document.getElementById('note-title-input').value = dataState[key]?.title || '';
    // Use innerHTML for contenteditable div
    document.getElementById('note-desc-input').innerHTML = dataState[key]?.comment || '';
    document.getElementById('modal-note').style.display = 'flex';
    lucide.createIcons();
}

function saveNote() {
    const t = document.getElementById('note-title-input').value;
    // Get HTML content from contenteditable div
    const c = document.getElementById('note-desc-input').innerHTML;
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
    
    // Show toast instead of alert
    const toast = document.getElementById('save-toast');
    toast.innerHTML = '<i data-lucide="check" width="14"></i> Anota├º├úo salva!';
    toast.style.opacity = 1;
    setTimeout(() => {
        toast.style.opacity = 0;
        setTimeout(() => toast.innerHTML = '<i data-lucide="check" width="14"></i> Salvo auto', 300);
    }, 2000);
    lucide.createIcons();
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

// Tab Switching for Config Modal
function switchConfigTab(tabName) {
    // Remove active class from all tabs
    document.querySelectorAll('.tab-btn').forEach(btn => btn.classList.remove('active'));
    document.querySelectorAll('.config-content').forEach(content => content.style.display = 'none');
    
    // Add active class to clicked tab
    document.getElementById(`tab-${tabName}`).classList.add('active');
    document.getElementById(`content-${tabName}`).style.display = 'block';
    
    lucide.createIcons();
}

// Toggle Export Menu
function toggleExportMenu() {
    const menu = document.getElementById('export-menu');
    menu.style.display = menu.style.display === 'none' ? 'block' : 'none';
    lucide.createIcons();
}

// Close export menu when clicking outside
document.addEventListener('click', function(e) {
    const menu = document.getElementById('export-menu');
    const btn = document.getElementById('export-btn');
    if (menu && btn && !menu.contains(e.target) && !btn.contains(e.target)) {
        menu.style.display = 'none';
    }
});

// Filter Diary Entries
function filterDiary() {
    const input = document.getElementById('diary-search');
    const filter = input.value.toLowerCase();
    const cards = document.querySelectorAll('.diary-card');
    
    // 1. Filter Cards
    cards.forEach(card => {
        const searchContent = card.getAttribute('data-search-content');
        if (searchContent.includes(filter)) {
            card.style.display = 'block';
            card.classList.add('visible-card');
        } else {
            card.style.display = 'none';
            card.classList.remove('visible-card');
        }
    });

    // 2. Handle Group Headers
    const container = document.getElementById('diary-entries-container');
    if(!container) return;
    
    const children = Array.from(container.children);
    
    children.forEach((child, index) => {
        if(child.classList.contains('group-header')) {
            let visibleSiblings = false;
            for(let i = index + 1; i < children.length; i++) {
                const sibling = children[i];
                if(sibling.classList.contains('group-header')) break;
                if(sibling.classList.contains('visible-card')) {
                    visibleSiblings = true;
                    break;
                }
            }
            child.style.display = visibleSiblings ? 'block' : 'none';
        }
    });
}

// --- SHARING FUNCTIONALITY ---
async function shareEntry(title, dateStr, content) {
    const tempDiv = document.createElement("div"); tempDiv.innerHTML = content;
    const cleanContent = tempDiv.innerText;
    const shareText = `­ƒôà *Di├írio de Leitura*\n­ƒùô´©Å ${dateStr}\n\n­ƒôû *${title}*\n"${cleanContent}"\n\n_Compartilhado via App Louvor PIB_`;

    if (navigator.share) {
        try { await navigator.share({ title: 'Di├írio de Leitura', text: shareText }); } catch (err) { console.log('Error sharing:', err); }
    } else {
        navigator.clipboard.writeText(shareText).then(() => {
            const toast = document.getElementById('save-toast');
            toast.innerHTML = '<i data-lucide="copy" width="14"></i> Copiado para ├írea de transfer├¬ncia!';
            toast.style.opacity = 1;
            setTimeout(() => toast.style.opacity = 0, 3000);
        });
    }
}

// Export Diary Function
function exportDiary(format) {
    // Close menu
    document.getElementById('export-menu').style.display = 'none';
    
    // Get all diary entries
    const entries = document.querySelectorAll('.diary-card');
    if (entries.length === 0) {
        alert('Nenhuma anota├º├úo para exportar.');
        return;
    }
    
    const dateStr = new Date().toISOString().split('T')[0];
    
    if (format === 'word') {
        exportAsWordNew(entries, dateStr);
    } else if (format === 'pdf') {
        exportAsPDFNew(entries, dateStr);
    }
}

// Export as Word (.docx)
function exportAsWord(entries, dateStr) {
    // Build HTML content with statistics header
    let html = '<html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:w="urn:schemas-microsoft-com:office:word" xmlns="http://www.w3.org/TR/REC-html40">';
    html += '<head><meta charset="utf-8"><title>Di├írio de Leitura B├¡blica</title>';
    html += '<style>';
    html += 'body{font-family:Arial,sans-serif;line-height:1.6;padding:20px;}';
    html += 'h1{color:#6366f1;border-bottom:3px solid #6366f1;padding-bottom:10px;margin-bottom:20px;}';
    html += '.stats-box{background:var(--slate-50);border:1px solid #e5e7eb;border-radius:8px;padding:15px;margin:20px 0;}';
    html += '.stats-grid{display:grid;grid-template-columns:repeat(4,1fr);gap:15px;margin-top:15px;}';
    html += '.stat-item{text-align:center;padding:10px;background:white;border-radius:6px;}';
    html += '.stat-value{font-size:24px;font-weight:bold;color:#111827;}';
    html += '.stat-label{font-size:11px;color:#6b7280;text-transform:uppercase;font-weight:600;margin-top:4px;}';
    html += '.entry{margin-bottom:30px;padding:20px;border:1px solid #e5e7eb;border-radius:8px;page-break-inside:avoid;}';
    html += '.date{color:#6b7280;font-size:11px;text-transform:uppercase;font-weight:bold;letter-spacing:0.5px;}';
    html += '.day-badge{background:#e0e7ff;color:#4338ca;padding:4px 8px;border-radius:4px;font-size:11px;display:inline-block;margin:5px 0;font-weight:600;}';
    html += '.title{font-weight:bold;font-size:16px;margin:10px 0;color:#111827;}';
    html += '.content{color:#374151;margin-top:10px;line-height:1.7;}';
    html += 'a{color:#6366f1;text-decoration:underline;}';
    html += 'b,strong{font-weight:700;}i,em{font-style:italic;}u{text-decoration:underline;}strike{text-decoration:line-through;}';
    html += 'ul,ol{margin-left:20px;}li{margin-bottom:4px;}';
    html += '</style></head><body>';
    
    // Header
    html += '<h1>­ƒôû DI├üRIO DE LEITURA B├ìBLICA</h1>';
    html += '<p style="color:#6b7280;margin-bottom:10px;">Louvor PIB Oliveira</p>';
    
    // Statistics Box
    html += '<div class="stats-box">';
    html += '<div style="font-weight:700;color:#111827;margin-bottom:10px;">­ƒôè Estat├¡sticas do Plano</div>';
    html += '<div class="stats-grid">';
    html += `<div class="stat-item"><div class="stat-value"><?= $totalDaysRead ?></div><div class="stat-label">Dias Lidos</div></div>`;
    html += `<div class="stat-item"><div class="stat-value"><?= $totalChaptersRead ?></div><div class="stat-label">Cap├¡tulos</div></div>`;
    html += `<div class="stat-item"><div class="stat-value"><?= $currentStreak ?></div><div class="stat-label">Sequ├¬ncia</div></div>`;
    html += `<div class="stat-item"><div class="stat-value"><?= $completionPercent ?>%</div><div class="stat-label">Conclu├¡do</div></div>`;
    html += '</div>';
    html += `<div style="margin-top:15px;font-size:13px;color:#6b7280;">`;
    html += `<strong>Plano:</strong> <?= ucfirst($selectedPlanType) ?> | `;
    html += `<strong>In├¡cio:</strong> <?= date('d/m/Y', strtotime($startDateStr)) ?> | `;
    html += `<strong>Total de Anota├º├Áes:</strong> ${entries.length}`;
    html += `</div></div>`;
    
    // Entries
    entries.forEach((entry) => {
        const dateEl = entry.querySelector('.diary-date');
        const titleEl = entry.querySelector('.diary-title');
        const contentEl = entry.querySelector('.diary-content');
        
        html += '<div class="entry">';
        if (dateEl) {
             // Remove potential icon text if present (e.g. from alt text or similar, though svg usually empty)
             // We can just take the text text content
             const cleanDate = dateEl.innerText.trim();
             html += `<div class="date">${cleanDate}</div>`;
        }
        if (titleEl) html += `<div class="title">${titleEl.textContent.trim()}</div>`;
        if (contentEl) html += `<div class="content">${contentEl.innerHTML}</div>`;
        html += '</div>';
    });
    
    html += '</body></html>';
    
    // Download
    const blob = new Blob(['\ufeff', html], { type: 'application/msword;charset=utf-8' });
    const url = URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = `diario-leitura-biblica-${dateStr}.doc`;
    document.body.appendChild(a);
    a.click();
    document.body.removeChild(a);
    URL.revokeObjectURL(url);
    
    showExportSuccess('Word');
}

// Export as PDF
function exportAsPDF(entries, dateStr) {
    let printWindow = window.open('', '_blank');
    let html = '<html><head><meta charset="utf-8"><title>Di├írio de Leitura B├¡blica</title>';
    html += '<style>';
    html += '@media print{@page{margin:20mm;}}';
    html += 'body{font-family:Arial,sans-serif;line-height:1.6;color:#333;max-width:800px;margin:0 auto;padding:20px;}';
    html += 'h1{color:#6366f1;border-bottom:3px solid #6366f1;padding-bottom:10px;margin-bottom:20px;}';
    html += '.stats-box{background:var(--slate-50);border:1px solid #e5e7eb;border-radius:8px;padding:15px;margin:20px 0;page-break-inside:avoid;}';
    html += '.stats-grid{display:grid;grid-template-columns:repeat(4,1fr);gap:15px;margin-top:15px;}';
    html += '.stat-item{text-align:center;padding:10px;background:white;border-radius:6px;border:1px solid #e5e7eb;}';
    html += '.stat-value{font-size:20px;font-weight:bold;color:#111827;}';
    html += '.stat-label{font-size:10px;color:#6b7280;text-transform:uppercase;font-weight:600;margin-top:4px;}';
    html += '.entry{margin-bottom:25px;padding:15px;border:1px solid #e5e7eb;border-radius:8px;page-break-inside:avoid;}';
    html += '.date{color:#6b7280;font-size:11px;text-transform:uppercase;font-weight:bold;letter-spacing:0.5px;}';
    html += '.day-badge{background:#e0e7ff;color:#4338ca;padding:4px 8px;border-radius:4px;font-size:11px;display:inline-block;margin:5px 0;font-weight:600;}';
    html += '.title{font-weight:bold;font-size:15px;margin:10px 0;color:#111827;}';
    html += '.content{color:#374151;margin-top:10px;font-size:14px;line-height:1.7;}';
    html += 'a{color:#6366f1;text-decoration:underline;}';
    html += 'strong,b{font-weight:700;}em,i{font-style:italic;}u{text-decoration:underline;}strike{text-decoration:line-through;}';
    html += 'ul,ol{margin-left:20px;}li{margin-bottom:4px;}';
    html += '</style></head><body>';
    
    // Header
    html += '<h1>­ƒôû DI├üRIO DE LEITURA B├ìBLICA</h1>';
    html += '<p style="color:#6b7280;margin-bottom:10px;font-size:14px;">Louvor PIB Oliveira</p>';
    
    // Statistics Box
    html += '<div class="stats-box">';
    html += '<div style="font-weight:700;color:#111827;margin-bottom:10px;font-size:14px;">­ƒôè Estat├¡sticas do Plano</div>';
    html += '<div class="stats-grid">';
    html += `<div class="stat-item"><div class="stat-value"><?= $totalDaysRead ?></div><div class="stat-label">Dias Lidos</div></div>`;
    html += `<div class="stat-item"><div class="stat-value"><?= $totalChaptersRead ?></div><div class="stat-label">Cap├¡tulos</div></div>`;
    html += `<div class="stat-item"><div class="stat-value"><?= $currentStreak ?></div><div class="stat-label">Sequ├¬ncia</div></div>`;
    html += `<div class="stat-item"><div class="stat-value"><?= $completionPercent ?>%</div><div class="stat-label">Conclu├¡do</div></div>`;
    html += '</div>';
    html += `<div style="margin-top:15px;font-size:12px;color:#6b7280;">`;
    html += `<strong>Plano:</strong> <?= ucfirst($selectedPlanType) ?> | `;
    html += `<strong>In├¡cio:</strong> <?= date('d/m/Y', strtotime($startDateStr)) ?> | `;
    html += `<strong>Total de Anota├º├Áes:</strong> ${entries.length}`;
    html += `</div></div>`;
    
    // Entries
    entries.forEach((entry) => {
        const dateEl = entry.querySelector('div[style*="text-transform: uppercase"]');
        const dayEl = entry.querySelector('div[style*="background: #e0e7ff"]');
        const titleEl = entry.querySelector('h4');
        const contentEl = entry.querySelector('.diary-content');
        
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
        showExportSuccess('PDF');
    };
}

// Show Export Success Toast
function showExportSuccess(format) {
    const toast = document.getElementById('save-toast');
    toast.innerHTML = `<i data-lucide="check" width="14"></i> Di├írio exportado como ${format} com sucesso!`;
    toast.style.opacity = 1;
    setTimeout(() => {
        toast.style.opacity = 0;
        setTimeout(() => toast.innerHTML = '<i data-lucide="check" width="14"></i> Salvo auto', 300);
    }, 2500);
    lucide.createIcons();
}


function getExportStyles() {
    return `
    <style>
        @page { size: A4; margin: 25mm 20mm; }
        body { font-family: 'Times New Roman', Times, serif; color: #111; line-height: 1.5; font-size: 11pt; }
        .header { text-align: center; border-bottom: 2px solid #333; padding-bottom: 15px; margin-bottom: 30px; }
        .church-name { font-size: 10pt; text-transform: uppercase; letter-spacing: 2px; color: #555; margin-bottom: 5px; }
        .doc-title { font-size: 24pt; font-weight: bold; color: #000; margin: 0; }
        .user-info { margin-bottom: 30px; display: flex; justify-content: space-between; border-bottom: 1px solid #ddd; padding-bottom: 15px; }
        .user-detail { font-size: 12pt; }
        .stats-grid { display: grid; grid-template-columns: repeat(5, 1fr); gap: 10px; margin-bottom: 30px; background: #f9f9f9; padding: 15px; border-radius: 5px; border: 1px solid #eee; }
        .stat-item { text-align: center; }
        .stat-val { font-size: 18pt; font-weight: bold; color: #333; }
        .stat-lbl { font-size: 8pt; text-transform: uppercase; color: #666; letter-spacing: 0.5px; margin-top: 5px; }
        .entry { margin-bottom: 25px; page-break-inside: avoid; border-left: 3px solid #eee; padding-left: 15px; }
        .entry-meta { font-size: 10pt; color: #666; margin-bottom: 5px; font-style: italic; display: flex; justify-content: space-between; }
        .entry-title { font-size: 14pt; font-weight: bold; color: #000; margin-bottom: 8px; }
        .entry-content { text-align: justify; white-space: pre-wrap; }
        .footer { position: fixed; bottom: 0; left: 0; right: 0; font-size: 8pt; text-align: center; color: #999; border-top: 1px solid #eee; padding-top: 10px; }
    </style>`;
}

function exportAsWordNew(entries, dateStr) {
    let html = '<html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:w="urn:schemas-microsoft-com:office:word" xmlns="http://www.w3.org/TR/REC-html40">';
    html += '<head><meta charset="utf-8"><title>Di├írio de Leitura B├¡blica</title>' + getExportStyles() + '</head><body>';
    
    html += '<div class="header"><div class="church-name">Louvor PIB Oliveira</div><h1 class="doc-title">Di├írio de Leitura B├¡blica</h1></div>';
    
    const age = userData.birthDate ? new Date().getFullYear() - new Date(userData.birthDate).getFullYear() : '---';
    const birthFormatted = userData.birthDate ? new Date(userData.birthDate).toLocaleDateString('pt-BR', { timeZone: 'UTC' }) : '---';
    
    html += `<div class="user-info">
        <div class="user-detail"><strong>Nome:</strong> ${userData.name}</div>
        <div class="user-detail"><strong>Nascimento:</strong> ${birthFormatted} (${age} anos)</div>
        <div class="user-detail"><strong>Gerado em:</strong> ${new Date().toLocaleDateString('pt-BR')}</div>
    </div>`;
    
    html += `<div class="stats-grid">
        <div class="stat-item"><div class="stat-val"><?= $totalDaysRead ?></div><div class="stat-lbl">Dias</div></div>
        <div class="stat-item"><div class="stat-val"><?= $totalChaptersRead ?></div><div class="stat-lbl">Cap├¡tulos</div></div>
        <div class="stat-item"><div class="stat-val"><?= $currentStreak ?></div><div class="stat-lbl">Sequ├¬ncia</div></div>
        <div class="stat-item"><div class="stat-val"><?= $completionPercent ?>%</div><div class="stat-lbl">Conclu├¡do</div></div>
        <div class="stat-item"><div class="stat-val" style="font-size:12pt; line-height:2.2;">${userData.favoriteTime}</div><div class="stat-lbl">Hor├írio Fav.</div></div>
    </div>`;
    
    entries.forEach(entry => {
        const dateTxt = entry.querySelector('.diary-date').textContent.trim(); 
        const title = entry.querySelector('.diary-title') ? entry.querySelector('.diary-title').textContent : 'Sem t├¡tulo';
        const content = entry.querySelector('.diary-content') ? entry.querySelector('.diary-content').innerHTML : '';
        html += `<div class="entry"><div class="entry-meta">${dateTxt}</div><div class="entry-title">${title}</div><div class="entry-content">${content}</div></div>`;
    });
    
    html += '</body></html>';
    const blob = new Blob(['\ufeff', html], { type: 'application/msword;charset=utf-8' });
    const url = URL.createObjectURL(blob);
    const a = document.createElement('a'); a.href = url; a.download = `Diario_Leitura_${userData.name.replace(/\s+/g,'_')}_${dateStr}.doc`;
    document.body.appendChild(a); a.click(); document.body.removeChild(a);
    showExportSuccess('Word');
}

function exportAsPDFNew(entries, dateStr) {
    let w = window.open('', '_blank');
    let html = '<html><head><meta charset="utf-8"><title>Di├írio PDF</title>' + getExportStyles() + '</head><body>';
    
    html += '<div class="header"><div class="church-name">Louvor PIB Oliveira</div><h1 class="doc-title">Di├írio de Leitura B├¡blica</h1></div>';
    
    const age = userData.birthDate ? new Date().getFullYear() - new Date(userData.birthDate).getFullYear() : '---';
    const birthFormatted = userData.birthDate ? new Date(userData.birthDate).toLocaleDateString('pt-BR', { timeZone: 'UTC' }) : '---';
    
    html += `<div class="user-info">
        <div class="user-detail"><strong>Nome:</strong> ${userData.name}</div>
        <div class="user-detail"><strong>Nascimento:</strong> ${birthFormatted} (${age} anos)</div>
        <div class="user-detail"><strong>Plano:</strong> ${planType.charAt(0).toUpperCase() + planType.slice(1)}</div>
    </div>`;
    
    html += `<div class="stats-grid">
        <div class="stat-item"><div class="stat-val"><?= $totalDaysRead ?></div><div class="stat-lbl">Dias</div></div>
        <div class="stat-item"><div class="stat-val"><?= $totalChaptersRead ?></div><div class="stat-lbl">Cap├¡tulos</div></div>
        <div class="stat-item"><div class="stat-val"><?= $currentStreak ?></div><div class="stat-lbl">Streak</div></div>
        <div class="stat-item"><div class="stat-val"><?= $completionPercent ?>%</div><div class="stat-lbl">Conclu├¡do</div></div>
        <div class="stat-item"><div class="stat-val" style="font-size:12pt; line-height:2.2;">${userData.favoriteTime}</div><div class="stat-lbl">Hor├írio Fav.</div></div>
    </div>`;

    // Add Advanced Stats Block
    html += `<div style="margin-bottom: 20px; border: 1px solid #eee; background: #fdfdfd; padding: 10px; border-radius: 5px;">
        <h3 style="font-size: 12pt; margin: 0 0 10px 0; color: #555;">An├ílise de H├íbitos</h3>
        <div style="display: flex; gap: 20px;">
             <!-- Time Dist -->
             <div style="flex: 1;">
                <div style="font-size: 8pt; font-weight: bold; margin-bottom: 4px; text-transform: uppercase;">Por Per├¡odo</div>
                ${statsData.time.map(t => `<div style="display: flex; justify-content: space-between; font-size: 8pt; margin-bottom: 2px;"><span>${t.label}</span><span>${t.count}</span></div>`).join('')}
             </div>
             <!-- Week Dist -->
             <div style="flex: 1;">
                <div style="font-size: 8pt; font-weight: bold; margin-bottom: 4px; text-transform: uppercase;">Por Dia da Semana</div>
                ${statsData.week.map(w => `<div style="display: flex; justify-content: space-between; font-size: 8pt; margin-bottom: 2px;"><span>${w.label}</span><span>${w.count}</span></div>`).join('')}
             </div>
        </div>
    </div>`;
    
    entries.forEach(entry => {
        const dateTxt = entry.querySelector('.diary-date').textContent.trim();
        const title = entry.querySelector('.diary-title') ? entry.querySelector('.diary-title').textContent : 'Sem t├¡tulo';
        const content = entry.querySelector('.diary-content') ? entry.querySelector('.diary-content').innerHTML : '';
        html += `<div class="entry"><div class="entry-meta">${dateTxt}</div><div class="entry-title">${title}</div><div class="entry-content">${content}</div></div>`;
    });
    
    html += `<div class="footer">Gerado em ${new Date().toLocaleString('pt-BR')} ÔÇó App Louvor PIB Oliveira</div></body></html>`;
    
    w.document.write(html); w.document.close();
    w.onload = function() { w.print(); setTimeout(()=>w.close(), 1000); };
}

function resetPlan() {
    if(confirm('Tem certeza? Isso apagar├í TODO o progresso e n├úo pode ser desfeito.')) {
        const f = new FormData(); f.append('action', 'reset_plan');
        fetch('leitura.php', { method:'POST', body:f }).then(()=>window.location.reload());
    }
}

// Tab Switching for Reading Page
function switchReadingTab(tabName) {
    // Hide all tabs
    document.querySelectorAll('.tab-content-reading').forEach(tab => {
        tab.classList.remove('active');
    });
    
    // Remove active from all buttons
    document.querySelectorAll('.tab-btn').forEach(btn => {
        btn.classList.remove('active');
    });
    
    // Show selected tab
    document.getElementById('tab-' + tabName).classList.add('active');
    document.getElementById('btn-tab-' + tabName).classList.add('active');
}

// Initialize Lucide Icons (Final call to ensure all icons are rendered)
document.addEventListener('DOMContentLoaded', function() {
    if (typeof lucide !== 'undefined') {
        lucide.createIcons();
        console.log('Lucide icons initialized');
    }
});

// Also initialize after a short delay to catch any dynamically added content
setTimeout(function() {
    if (typeof lucide !== 'undefined') {
        lucide.createIcons();
    }
}, 500);
</script>
