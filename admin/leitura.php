<?php
// admin/leitura.php
require_once '../includes/auth.php';
require_once '../includes/db.php';
require_once '../includes/layout.php';

checkLogin(); 

// ==========================================
// BACKEND LOGIC
// ==========================================
$userId = $_SESSION['user_id'];

// Default Settings
$defaultStartDate = date('Y-01-01');

// Fetch Settings
$stmt = $pdo->prepare("SELECT setting_key, setting_value FROM user_settings WHERE user_id = ?");
$stmt->execute([$userId]);
$settings = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

$startDate = $settings['reading_plan_start_date'] ?? $defaultStartDate;
$notifTime = $settings['notification_time'] ?? '08:00';

// Calculate Current Day in Plan
$start = new DateTime($startDate);
$now = new DateTime();
// Reset time for accurate day diff
$start->setTime(0, 0, 0);
$nowCopy = clone $now;
$nowCopy->setTime(0, 0, 0);

$diff = $start->diff($nowCopy);
$daysPassed = $diff->invert ? -1 * $diff->days : $diff->days;
$planDayIndex = $daysPassed + 1; // Day 1 is the start date
// Max 300 days in our plan structure (12 * 25)
// But logic handles by month/day. 
// We need to map "Plan Day Index" (1..300) to Month/Day logic (1..12 / 1..25)
// Our plan has exactly 25 days per month logic in structure.
$currentPlanMonth = floor(($planDayIndex - 1) / 25) + 1;
$currentPlanDay = (($planDayIndex - 1) % 25) + 1;

if ($planDayIndex < 1) {
    // Plan hasn't started
    $currentPlanMonth = 1;
    $currentPlanDay = 1;
}

// Stats Calculation
$stmt = $pdo->prepare("SELECT * FROM reading_progress WHERE user_id = ?");
$stmt->execute([$userId]);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

$completedIds = []; // Map "M_D" -> row
foreach ($rows as $r) {
    $completedIds["{$r['month_num']}_{$r['day_num']}"] = $r;
}

$totalCompleted = count($rows);
$expectedCompleted = max(0, $planDayIndex - 1); // Should have completed up to yesterday? Or today? Let's say up to today.
if ($planDayIndex > 300) $expectedCompleted = 300;

// Update Delay Calculation
$delay = max(0, $expectedCompleted - $totalCompleted);
$percentage = min(100, round(($totalCompleted / 300) * 100)); // 300 total days in plan

// Handle POST actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'mark_read') {
        $m = (int)$_POST['month'];
        $d = (int)$_POST['day'];
        $comment = trim($_POST['comment'] ?? '');
        
        try {
            $stmt = $pdo->prepare("
                INSERT INTO reading_progress (user_id, month_num, day_num, comment, completed_at)
                VALUES (?, ?, ?, ?, NOW())
                ON DUPLICATE KEY UPDATE comment = VALUES(comment), completed_at = NOW()
            ");
            $stmt->execute([$userId, $m, $d, $comment]);
        } catch (Exception $e) {}
        
        if(!empty($_SERVER['HTTP_X_REQUESTED_WITH'])) { echo json_encode(['success' => true]); exit; }
        header("Location: leitura.php"); exit;
    }
    
    if ($action === 'save_settings') {
        $newStart = $_POST['start_date'];
        $newNotif = $_POST['notification_time'];
        
        $pdo->prepare("INSERT INTO user_settings (user_id, setting_key, setting_value) VALUES (?, 'reading_plan_start_date', ?) ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)")->execute([$userId, $newStart]);
        
        $pdo->prepare("INSERT INTO user_settings (user_id, setting_key, setting_value) VALUES (?, 'notification_time', ?) ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)")->execute([$userId, $newNotif]);

        header("Location: leitura.php"); exit;
    }

    if ($action === 'reset_plan') {
        $pdo->prepare("DELETE FROM reading_progress WHERE user_id = ?")->execute([$userId]);
        header("Location: leitura.php"); exit;
    }

    if ($action === 'get_group_comments') {
        $m = (int)$_POST['month'];
        $d = (int)$_POST['day'];
        
        $stmt = $pdo->prepare("
            SELECT u.name, rp.comment, rp.completed_at 
            FROM reading_progress rp 
            JOIN users u ON rp.user_id = u.id 
            WHERE rp.month_num = ? AND rp.day_num = ? 
            AND rp.comment IS NOT NULL AND rp.comment != ''
            ORDER BY rp.completed_at DESC
        ");
        $stmt->execute([$m, $d]);
        $comments = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode(['success' => true, 'data' => $comments]);
        exit;
    }
}

// Header with Actions
renderAppHeader('Leitura Bíblica');
// Inject Settings Button into Header via JS or inline absolute since we are in a "Page" concept
?>

<!-- Import JSON Data with Cache Busting -->
<script src="../assets/js/reading_plan_data.js?v=<?= time() ?>"></script>

<script>
// ==========================================
// Expose Functions to Global Window explicitly
// ==========================================
window.openConfig = function() { 
    const modal = document.getElementById('modal-config');
    if(modal) {
        modal.classList.add('active');
        modal.style.display = 'flex'; // Ensure display is flex
        // Force reflow
        void modal.offsetWidth;
        modal.classList.add('open');
    } else {
        console.error("Modal config not found");
    }
};
window.closeConfig = function() { 
    const modal = document.getElementById('modal-config');
    if(modal) {
        modal.classList.remove('open');
        setTimeout(() => {
            modal.classList.remove('active');
            modal.style.display = 'none';
        }, 300);
    }
};

// ==========================================
// DATA & STATE
// ==========================================
// Parse PHP Data securely
const planDayIndex = <?= json_encode($planDayIndex) ?>; 
const currentPlanMonth = <?= json_encode($currentPlanMonth) ?>;
const currentPlanDay = <?= json_encode($currentPlanDay) ?>;
const completedMap = <?= json_encode($completedIds) ?>;

// State
let selectedMonth = currentPlanMonth;
let selectedDay = currentPlanDay;

// ==========================================
// INIT
// ==========================================
function init() {
    console.log("Reading Plan Init", {selectedMonth, selectedDay});
    renderCalendar();
    selectDay(selectedMonth, selectedDay); 
    if(window.lucide) window.lucide.createIcons();
    
    setTimeout(() => {
        const active = document.querySelector('.cal-day-item.active');
        if(active) active.scrollIntoView({ behavior: 'smooth', inline: 'center', block: 'nearest' });
    }, 100);
}

// ==========================================
// CALENDAR RENDERING
// ==========================================
function renderCalendar() {
    const strip = document.getElementById('calendar-strip');
    if(!strip) return;
    
    strip.innerHTML = '';
    const m = selectedMonth; // Show current selected month or active month logic
    
    for (let d = 1; d <= 25; d++) {
        const isCompleted = completedMap[`${m}_${d}`];
        const isActive = (d === selectedDay); 
        const el = document.createElement('div');
        el.className = `cal-day-item ${isActive ? 'active' : ''} ${isCompleted ? 'completed' : ''}`;
        el.id = `day-card-${m}-${d}`;
        el.onclick = () => selectDay(m, d);
        el.innerHTML = `<div class="cal-day-month">${getMonthAbbr(m)}</div><div class="cal-day-num">${d}</div>`;
        strip.appendChild(el);
    }
}

function selectDay(m, d) {
    console.log("Selecting Day:", m, d);
    
    // Update UI visuals
    document.querySelectorAll('.cal-day-item').forEach(e => e.classList.remove('active'));
    const target = document.getElementById(`day-card-${m}-${d}`);
    if(target) target.classList.add('active');
    
    selectedMonth = m;
    selectedDay = d;
    
    renderContent(m, d);
}

function renderContent(m, d) {
    const container = document.getElementById('verses-container');
    const commentLabel = document.getElementById('comment-text-label');
    const globalIdx = (m - 1) * 25 + d;

    // Header Content Update
    const titleArea = document.getElementById('main-date-title');
    if(titleArea) {
        titleArea.innerText = `Dia ${d}`;
        // Update surrounding elements if structure allows, or just title
        const parent = titleArea.parentElement;
        if(parent.querySelector('div')) {
           parent.querySelector('div').innerText = `DIA ${globalIdx} / 300`;
        }
    }
    
    if (!bibleReadingPlan || !bibleReadingPlan[m] || !bibleReadingPlan[m][d-1]) {
        if(container) container.innerHTML = "<div style='padding:20px; text-align:center;'>Sem leitura para este dia.</div>";
        updateProgress(0, 1);
        return;
    }
    
    const verses = bibleReadingPlan[m][d-1]; 
    if(container) container.innerHTML = '';
    
    let readCount = 0;
    
    verses.forEach((v, idx) => {
        const link = getBibleLink(v);
        const storageKey = `reading_check_${m}_${d}_${idx}`;
        const isRead = localStorage.getItem(storageKey) === 'true';
        if(isRead) readCount++;
        
        const item = document.createElement('div');
        item.className = `verse-check-item ${isRead ? 'read' : ''}`;
        
        // Inline styles for JS dynamic states (restored from working version)
        if(isRead) {
            item.style.background = '#ecfdf5';
            item.style.borderColor = '#a7f3d0';
        }
        
        item.onclick = (e) => {
            if(e.target.closest('a')) return;
            toggleVerseRead(m, d, idx, item);
        };
        
        item.innerHTML = `
            <div style="display:flex; align-items:center;">
                <div class="check-circle" style="
                    background:${isRead ? '#10b981' : 'transparent'}; 
                    border-color:${isRead ? '#10b981' : 'var(--border-color)'};
                ">
                    <i data-lucide="check" style="width:14px; color:white; opacity:${isRead ? '1' : '0'}; transition:opacity 0.2s;"></i>
                </div>
                <div class="verse-info">
                    <div class="verse-text" style="font-weight:600; ${isRead ? 'color:#065f46; text-decoration:line-through; opacity:0.8;' : ''}">${v}</div>
                </div>
            </div>
            <a href="${link}" target="_blank" class="ripple" style="
                background: var(--primary-light); color: var(--primary); padding: 8px 16px; border-radius: 20px; text-decoration: none; font-size: 0.8rem; font-weight: 700; display:flex; align-items:center; gap:6px; flex-shrink: 0;
            ">LER <i data-lucide="external-link" style="width:14px;"></i></a>
        `;
        if(container) container.appendChild(item);
    });
    
    if(window.lucide) window.lucide.createIcons();
    updateProgress(readCount, verses.length);
    
    const isDone = completedMap[`${m}_${d}`];
    if(commentLabel) commentLabel.innerText = (isDone && isDone.comment) ? "Editar Minha Anotação" : "Adicionar Anotação";
}

function toggleVerseRead(m, d, idx, item) {
    const storageKey = `reading_check_${m}_${d}_${idx}`;
    const newState = !item.classList.contains('read');
    
    item.classList.toggle('read');
    localStorage.setItem(storageKey, newState);
    
    // Visual Updates specific to this item
    const checkCircle = item.querySelector('.check-circle');
    const checkIcon = item.querySelector('.check-circle i');
    const verseText = item.querySelector('.verse-text');
    
    if(newState) {
        item.style.background = '#ecfdf5';
        item.style.borderColor = '#a7f3d0';
        checkCircle.style.background = '#10b981';
        checkCircle.style.borderColor = '#10b981';
        checkIcon.style.opacity = '1';
        verseText.style.color = '#065f46';
        verseText.style.textDecoration = 'line-through';
        verseText.style.opacity = '0.8';
    } else {
        item.style.background = 'var(--bg-surface)';
        item.style.borderColor = 'var(--border-color)';
        checkCircle.style.background = 'transparent';
        checkCircle.style.borderColor = 'var(--border-color)';
        checkIcon.style.opacity = '0';
        verseText.style.color = 'var(--text-main)';
        verseText.style.textDecoration = 'none';
        verseText.style.opacity = '1';
    }
    
    // Recalculate progress for this day
    const total = document.querySelectorAll('.verse-check-item').length;
    const currentRead = document.querySelectorAll('.verse-check-item.read').length;
    updateProgress(currentRead, total);
}

function updateProgress(current, total) {
    if(total === 0) return;
    
    const pct = Math.round((current / total) * 100);
    const btn = document.getElementById('btn-main-action');
    const statusText = document.getElementById('day-status-text');
    const isDoneServer = completedMap[`${selectedMonth}_${selectedDay}`];
    
    if(!btn) return;
    
    // Force reset to base state
    btn.style.cssText = ""; 
    btn.className = "ripple";

    // Base Style: Fixed Size, Bold
    const baseStyle = "border: none; padding: 12px 24px; border-radius: 12px; font-weight: 700; font-size: 0.9rem; cursor: pointer; transition: all 0.2s; display: flex; align-items: center; gap: 8px; white-space: nowrap;";

    if (current < total) {
        // INCOMPLETE: Disabled-look
        btn.innerHTML = `Concluir Dia (${current}/${total})`;
        btn.onclick = () => alert(`Você precisa ler todas as passagens para concluir.\n\nLido: ${current}\nTotal: ${total}`);
        
        // Gray/Disabled Style
        btn.style.cssText = baseStyle + "background: #f1f5f9; color: #94a3b8; cursor: not-allowed; opacity: 0.8;";
        
        if(statusText) statusText.innerHTML = '<span style="color:#d97706">Pendente</span>';
    } else {
        // COMPLETE (Locally or Server)
        if (isDoneServer) {
             btn.innerHTML = '<i data-lucide="check-circle-2"></i> Dia Concluído';
             btn.onclick = () => completeDay(); 
             // Soft Green (Done)
             btn.style.cssText = baseStyle + "background: #dcfce7; color: #166534; border: 1px solid transparent;";
             
             if(statusText) statusText.innerHTML = '<span style="color:#16a34a">Concluído</span>';
        } else {
             // Ready to Save
             btn.innerHTML = 'Concluir Dia';
             btn.onclick = () => completeDay();
             // Bright Green (Action)
             btn.style.cssText = baseStyle + "background: #10b981; color: white; box-shadow: 0 4px 12px rgba(16, 185, 129, 0.4); transform: scale(1.02);";
             
             if(statusText) statusText.innerHTML = '<span style="color:#10b981">Pronto!</span>';
        }
    }
    
    if(window.lucide) window.lucide.createIcons();
}

function insertFormat(startTag, endTag) {
    const tarea = document.getElementById('temp-comment-area');
    if(!tarea) return;
    const start = tarea.selectionStart;
    const end = tarea.selectionEnd;
    const text = tarea.value;
    const before = text.substring(0, start);
    const selected = text.substring(start, end);
    const after = text.substring(end);
    tarea.value = before + startTag + selected + endTag + after;
    tarea.focus();
    tarea.selectionStart = start + startTag.length;
    tarea.selectionEnd = end + startTag.length;
}

function shareWhatsApp() {
    const tarea = document.getElementById('temp-comment-area');
    const text = tarea ? tarea.value : '';
    if(!text) return alert("Escreva algo primeiro!");
    const url = `https://wa.me/?text=${encodeURIComponent("*Minha Anotação de Leitura:* \n\n" + text)}`;
    window.open(url, '_blank');
}

function completeDay() {
    const m = selectedMonth;
    const d = selectedDay;
    const formData = new FormData();
    formData.append('action', 'mark_read');
    formData.append('month', m);
    formData.append('day', d);
    
    fetch('leitura.php', { method: 'POST', body: formData })
    .then(r => r.json())
    .catch(() => ({success:true}))
    .then(() => {
        // Visual update
        const dayCard = document.getElementById(`day-card-${m}-${d}`);
        if(dayCard) dayCard.classList.add('completed');
        
        // Update local map
        if(typeof completedMap !== 'undefined') {
            completedMap[`${m}_${d}`] = { completed_at: new Date().toISOString() };
        }

        const modal = document.getElementById('modal-success');
        if(modal) modal.style.display = 'flex';
        else window.location.reload();
    });
}

function closeSuccessModal() {
    window.location.reload();
}

function saveCommentAndFinish() {
    const tarea = document.getElementById('temp-comment-area');
    const val = tarea ? tarea.value : '';
    const m = selectedMonth;
    const d = selectedDay;
    const formData = new FormData();
    formData.append('action', 'mark_read');
    formData.append('month', m);
    formData.append('day', d);
    formData.append('comment', val);
    
    fetch('leitura.php', { method: 'POST', body: formData })
    .then(r => r.json())
    .catch(() => ({success:true}))
    .then(() => {
        const modal = document.getElementById('modal-success');
        if(modal) modal.style.display = 'flex';
        else window.location.reload();
    });
}

function openCommentModal() {
    const m = selectedMonth;
    const d = selectedDay;
    const isDone = completedMap[`${m}_${d}`];
    const tarea = document.getElementById('temp-comment-area');
    const modal = document.getElementById('modal-comment');
    
    if(tarea) tarea.value = isDone ? (isDone.comment || '') : '';
    if(modal) modal.style.display = 'flex';
}
function closeCommentModal() { 
    const modal = document.getElementById('modal-comment');
    if(modal) modal.style.display = 'none'; 
}

function getMonthAbbr(m) {
    return ["", "JAN", "FEV", "MAR", "ABR", "MAI", "JUN", "JUL", "AGO", "SET", "OUT", "NOV", "DEZ"][m];
}

window.addEventListener('load', init);
</script>

<style>
    /* YouVersion Inspired Styles */
    :root {
        --yv-bg: #ffffff;
        --yv-bar: #eeeeee;
        --yv-check: #50bdae;
        --yv-text: #333333;
    }
    
    /* Horizontal Calendar Scroll */
    .calendar-strip {
        display: flex;
        overflow-x: auto;
        gap: 8px;
        padding: 12px 16px;
        background: var(--bg-surface);
        border-bottom: 1px solid var(--border-color);
        scrollbar-width: none; /* Firefox */
        -ms-overflow-style: none;  /* IE */
    }
    .calendar-strip::-webkit-scrollbar { display: none; }
    
    .cal-day-item {
        min-width: 60px;
        height: 70px;
        background: var(--bg-body);
        border-radius: 12px;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        opacity: 0.7;
        transition: all 0.2s;
        border: 2px solid transparent;
        flex-shrink: 0;
    }
    .cal-day-item.active {
        opacity: 1;
        background: var(--bg-surface);
        border-color: var(--primary);
        box-shadow: 0 4px 12px rgba(4, 120, 87, 0.15);
    }
    .cal-day-num { font-size: 1.25rem; font-weight: 700; color: var(--text-main); }
    .cal-day-month { font-size: 0.7rem; text-transform: uppercase; color: var(--text-muted); font-weight: 600; }
    
    .cal-day-item.completed .cal-day-num { color: #10b981; }

    /* Main Content Area */
    .reading-container {
        padding: 16px;
        padding-bottom: 100px;
    }

    /* Verse Check List */
    .check-circle {
        width: 24px; height: 24px;
        border-radius: 50%;
        border: 2px solid var(--border-color);
        display: flex; align-items: center; justify-content: center;
        transition: all 0.2s;
        background: transparent; /* Ensure empty by default */
    }
    .verse-check-item.read .check-circle {
        background: var(--primary);
        border-color: var(--primary);
    }
    .verse-check-item:hover .check-circle {
        border-color: var(--primary);
    }
    .verse-check-item:hover { border-color: var(--primary); }
    .verse-check-item:active { transform: scale(0.98); }
    .verse-check-item.read {
        background: #ecfdf5; /* Green-50 */
        border-color: #a7f3d0; /* Green-200 */
    }
    .verse-check-item.read .verse-text {
        color: #065f46;
        text-decoration: line-through;
        opacity: 0.8;
    }
    
    .verse-info { display: flex; align-items: center; gap: 12px; flex: 1; }
    .verse-text { font-size: 1rem; font-weight: 600; color: var(--text-main); text-decoration: none; }
    .verse-text:hover { text-decoration: underline; color: var(--primary); }

    /* Checkbox Circle */
    .check-circle {
        width: 24px; height: 24px;
        border: 2px solid var(--border-color);
        border-radius: 50%;
        margin-right: 12px;
        display: flex; align-items: center; justify-content: center;
        transition: all 0.2s;
        flex-shrink: 0;
    }
    .verse-check-item.read .check-circle {
        background: #10b981;
        border-color: #10b981;
        color: white;
    }

    /* Fixed Bottom Action */
    .bottom-action-bar {
        position: fixed; bottom: 0; left: 0; right: 0;
        background: rgba(255,255,255,0.9); backdrop-filter: blur(10px);
        padding: 16px 20px 24px 20px;
        border-top: 1px solid var(--border-color);
        z-index: 100;
        display: flex; flex-direction: column; gap: 12px;
    }
    /* Adjust for bottom nav if layout has one (Admin usually uses Sidebar, but check layout) */
    @media (max-width: 1024px) {
        .bottom-action-bar { bottom: 65px; /* Above Bottom Nav */ }
    }

    .btn-finish-day {
        width: 100%;
        background: #000; /* YouVersion Style Black Button */
        color: white;
        border: none;
        padding: 16px;
        border-radius: 30px;
        font-size: 1rem;
        font-weight: 700;
        cursor: pointer;
        display: flex; align-items: center; justify-content: center; gap: 8px;
        box-shadow: 0 4px 15px rgba(0,0,0,0.2);
    }
    
    /* Config Modal */
    .modal-config {
        position: fixed; top: 0; left: 0; right: 0; bottom: 0;
        background: var(--bg-body);
        z-index: 3000;
        display: none;
        flex-direction: column;
        transform: translateY(100%); transition: transform 0.3s cubic-bezier(0.16, 1, 0.3, 1);
        overflow-y: auto;
    }
    .modal-config.active { display: flex; }
    .modal-config.open { transform: translateY(0); }

    .config-header {
        padding: 16px 20px;
        background: var(--bg-surface);
        border-bottom: 1px solid var(--border-color);
        display: flex; justify-content: space-between; align-items: center;
    }

    /* Page Header Override for Settings Icon */
    .page-header-actions {
        position: absolute; right: 20px; top: 20px; z-index: 10;
    }
    @media(max-width: 768px) {
        .page-header-actions { top: 16px; right: 16px; }
    }
</style>

<!-- HEADER (Settings moved to layout.php) -->
<?php renderPageHeader('Leitura Bíblica', 'Dia ' . $planDayIndex . ' de 300 (' . $percentage . '%)'); ?>

<!-- HORIZONTAL DATE SCROLL (Calendar Strip) -->
<div class="calendar-strip" id="calendar-strip">
    <!-- JS Populated -->
</div>

<!-- CONTENT -->
<div class="reading-container">
    <!-- HEADER & ACTIONS -->
    <!-- HEADER & ACTIONS -->
    <div style="margin-bottom: 24px;">
        <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 16px; gap: 16px;">
            <div>
                <div style="font-size: 0.8rem; color: var(--text-muted); font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px;">Leitura de Hoje</div>
                <h1 id="main-date-title" style="margin: 4px 0 0 0; font-size: 1.5rem;">Carregando...</h1>
                
                <!-- Days Lost Indicator -->
                <?php if ($delay > 0): ?>
                <div style="margin-top: 6px; display: inline-flex; align-items: center; gap: 6px; padding: 4px 10px; background: #fef2f2; border: 1px solid #fee2e2; border-radius: 20px; color: #ef4444; font-size: 0.75rem; font-weight: 700;">
                    <i data-lucide="alert-circle" style="width: 12px;"></i> <?= $delay ?> dias atrasados
                </div>
                <?php endif; ?>
            </div>
            
            <!-- Top Action Button -->
            <button id="btn-main-action" onclick="completeDay()" class="ripple" style="
                border: none; padding: 10px 20px; border-radius: 12px; font-weight: 700; font-size: 0.85rem; cursor: pointer; transition: all 0.2s; display: flex; align-items: center; gap: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); white-space: nowrap;
            ">
                Carregando...
            </button>
        </div>
        
        <!-- DUAL PROGRESS SECTION -->
        <div style="display: grid; gap: 16px; margin-bottom: 16px;">
            
            <!-- Annual Progress -->
            <div>
                <div style="display: flex; justify-content: space-between; margin-bottom: 4px; font-size: 0.75rem; font-weight: 600; color: var(--text-muted);">
                    <span>Progresso Anual (App)</span>
                    <span><?= $percentage ?>%</span>
                </div>
                <div style="background: var(--border-color); height: 8px; border-radius: 4px; overflow: hidden;">
                    <div style="width: <?= $percentage ?>%; height: 100%; background: linear-gradient(90deg, #3b82f6, #60a5fa); border-radius: 4px;"></div>
                </div>
            </div>



        </div>
        

    </div>

    <!-- Links List -->
    <div id="verses-container">
        <!-- JS Populated -->
    </div>

    <!-- Empty Space for bottom bar -->
    <div style="height: 100px;"></div>
</div>

<!-- BOTTOM ACTION BAR -->
<div class="bottom-action-bar" id="bottom-bar">
    <div style="display: flex; gap: 12px;">
        <button id="comment-trigger" onclick="openCommentModal()" style="
            flex: 1; display: flex; align-items: center; justify-content: center; gap: 8px;
            background: #fff; border: 1px solid var(--border-color); color: var(--text-main);
            padding: 14px; border-radius: 16px; font-weight: 700; font-size: 0.95rem; box-shadow: var(--shadow-sm);
        ">
            <i data-lucide="pen-line" style="width: 18px;"></i>
            <span id="comment-text-label">Anotar</span>
        </button>

        <button onclick="openGroupComments()" class="ripple" style="
            flex: 1; display: flex; align-items: center; justify-content: center; gap: 8px;
            background: #f0f9ff; border: 1px solid #bae6fd; color: #0284c7;
            padding: 14px; border-radius: 16px; font-weight: 700; font-size: 0.95rem; box-shadow: var(--shadow-sm);
        ">
            <i data-lucide="users" style="width: 18px;"></i>
            <span>Comentários</span>
        </button>
    </div>
</div>

<!-- GROUP COMMENTS MODAL -->
<div id="modal-group-comments" class="modal-backdrop" style="position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.6); backdrop-filter: blur(4px); z-index: 2000; display: none; align-items: end; justify-content: center;">
    <div class="modal-content" style="
        background: var(--bg-surface); width: 100%; max-width: 600px; height: 80vh; 
        border-radius: 24px 24px 0 0; box-shadow: 0 -10px 40px rgba(0,0,0,0.2); 
        display: flex; flex-direction: column; animation: slideUpBig 0.3s;
    ">
        <div style="padding: 20px; border-bottom: 1px solid var(--border-color); display: flex; justify-content: space-between; align-items: center;">
            <h3 style="margin: 0; font-size: 1.2rem;">Comentários do Grupo</h3>
            <button onclick="closeGroupComments()" style="background: none; border: none; padding: 8px;"><i data-lucide="x"></i></button>
        </div>
        
        <div id="group-comments-list" style="flex: 1; overflow-y: auto; padding: 20px; background: var(--bg-body);">
            <!-- Populated via JS -->
            <div style="text-align: center; color: var(--text-muted); margin-top: 40px;">
                <div class="spinner"></div> Carregando...
            </div>
        </div>
    </div>
</div>

<!-- SUCCESS MODAL (Encouragement) -->
<div id="modal-success" class="modal-backdrop" style="position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.6); backdrop-filter: blur(4px); z-index: 2000; display: none; align-items: center; justify-content: center;">
    <div style="background: white; width: 85%; max-width: 400px; border-radius: 24px; padding: 24px; text-align: center; box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25); animation: zoomIn 0.3s;">
        <div style="width: 60px; height: 60px; background: #dcfce7; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 16px auto;">
            <i data-lucide="check" style="width: 32px; color: #16a34a; stroke-width: 3;"></i>
        </div>
        <h3 style="margin: 0 0 8px 0; font-size: 1.25rem; color: #166534;">Leitura Concluída!</h3>
        <p style="color: #4b5563; margin-bottom: 24px; font-size: 0.95rem; line-height: 1.5;">
            "A palavra de Deus renova suas forças. Continue firme! 💪"
        </p>
        <button onclick="closeSuccessModal()" class="ripple" style="width: 100%; padding: 14px; background: #16a34a; color: white; border: none; border-radius: 16px; font-weight: 700; font-size: 1rem;">
            Amém
        </button>
    </div>
</div>

<!-- CONFIG MODAL -->
<div id="modal-config" class="modal-config">
    <div class="config-header" style="background: white; padding: 16px; display: flex; align-items: center; justify-content: space-between; border-bottom: 1px solid var(--border-color);">
        <button onclick="closeConfig()" style="background:none; border:none; padding:8px; margin-left:-8px;"><i data-lucide="arrow-left"></i></button>
        <h3 style="margin:0; font-size: 1.1rem;">Configurações</h3>
        <div style="width: 40px;"></div>
    </div>
    
    <div style="padding: 24px;">
        <form method="POST">
            <input type="hidden" name="action" value="save_settings">
            
            <h4 style="margin-bottom: 12px;">Preferências</h4>
            
            <div class="form-card" style="margin-bottom: 24px; background: white; padding: 16px; border-radius: 12px; border: 1px solid var(--border-color);">
                <label style="display: block; margin-bottom: 8px; font-weight: 600; font-size: 0.9rem;">Horário do Lembrete</label>
                <input type="time" name="notification_time" value="<?= htmlspecialchars($notifTime) ?>" style="width: 100%; padding: 12px; border: 1px solid var(--border-color); border-radius: 8px;">
            </div>

            <div class="form-card" style="margin-bottom: 24px; background: white; padding: 16px; border-radius: 12px; border: 1px solid var(--border-color);">
                <label style="display: block; margin-bottom: 8px; font-weight: 600; font-size: 0.9rem;">Data de Início do Plano</label>
                <input type="date" name="start_date" value="<?= htmlspecialchars($startDate) ?>" style="width: 100%; padding: 12px; border: 1px solid var(--border-color); border-radius: 8px;">
            </div>

            <button type="submit" class="btn-primary" style="width: 100%; padding: 14px; margin-bottom: 24px;">Salvar Alterações</button>
        </form>

        <form method="POST">
            <input type="hidden" name="action" value="export_report">
            <button type="submit" class="ripple" style="width: 100%; padding: 14px; background: white; border: 1px solid var(--primary); color: var(--primary); border-radius: 12px; font-weight: 600; margin-bottom: 24px; display: flex; align-items: center; justify-content: center; gap: 8px;">
                <i data-lucide="download"></i> Baixar Relatório de Leitura
            </button>
        </form>

        <div style="border-top: 1px solid var(--border-color); padding-top: 24px;">
            <h4 style="margin-bottom: 8px; color: #ef4444;">Zona de Perigo</h4>
            <p style="font-size: 0.85rem; color: var(--text-muted); margin-bottom: 16px;">
                Ao resetar, todo o seu progresso de leitura e anotações serão apagados permanentemente.
            </p>
            
            <form method="POST" onsubmit="return confirm('TEM CERTEZA? Essa ação não pode ser desfeita e todo o histórico será perdido.');">
                <input type="hidden" name="action" value="reset_plan">
                <button type="submit" class="ripple" style="width: 100%; padding: 14px; background: #fee2e2; border: 1px solid #fecaca; color: #b91c1c; border-radius: 12px; font-weight: 700; display: flex; align-items: center; justify-content: center; gap: 8px;">
                    <i data-lucide="trash-2"></i> Resetar Tudo e Começar do Zero
                </button>
            </form>
        </div>
    </div>
</div>

<!-- ADVANCED NOTE MODAL -->
<div id="modal-comment" class="modal-backdrop" style="position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.6); backdrop-filter: blur(4px); z-index: 2100; display: none; align-items: center; justify-content: center;">
    <div style="background: white; width: 95%; max-width: 700px; border-radius: 12px; box-shadow: 0 10px 40px rgba(0,0,0,0.2); overflow: hidden; animation: zoomIn 0.2s; display: flex; flex-direction: column;">
        
        <!-- Header -->
        <div style="padding: 16px 24px; border-bottom: 1px solid #f1f5f9; display: flex; justify-content: space-between; align-items: center; background: #fff;">
            <div>
                <h3 style="margin: 0; color: #f97316; font-size: 1.1rem; display: flex; align-items: center; gap: 8px;">
                    <i data-lucide="plus" style="width: 18px;"></i> Adicionar Nova Anotação
                </h3>
            </div>
            <button onclick="closeCommentModal()" style="background: none; border: none; padding: 4px; color: #94a3b8; cursor: pointer;"><i data-lucide="x" style="width: 20px;"></i></button>
        </div>

        <div style="padding: 24px; background: #fafafa;">
            
            <!-- Title Mockup (Visual only unless DB supports it, defaulting to first line if needed later) -->
            <div style="margin-bottom: 20px;">
                <label style="display: block; font-weight: 700; font-size: 0.85rem; color: #334155; margin-bottom: 8px;">Título</label>
                <input type="text" placeholder="Ex: Reflexão sobre Salmos..." style="width: 100%; padding: 12px; border: 1px solid #e2e8f0; border-radius: 8px; font-size: 0.95rem; outline: none; background: #fff;" disabled title="Funcionalidade em desenvolvimento">
            </div>

            <!-- Description & Toolbar -->
            <div>
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px;">
                   <label style="font-weight: 700; font-size: 0.85rem; color: #334155;">Descrição Detalhada</label> 
                   <span style="font-size: 0.75rem; color: #94a3b8; background: #1e293b; color: white; padding: 2px 8px; border-radius: 4px;">Nenhum arquivo escolhido</span>
                </div>
                
                <div style="background: #fff; border: 1px solid #e2e8f0; border-radius: 8px; overflow: hidden;">
                    <!-- Toolbar -->
                    <div style="padding: 8px 12px; border-bottom: 1px solid #e2e8f0; background: #fff; display: flex; gap: 4px;">
                        <button type="button" onclick="insertFormat('**', '**')" title="Negrito" style="padding: 6px; border-radius: 4px; border: none; background: hover: #f1f5f9; cursor: pointer;"><i data-lucide="bold" style="width: 16px; color: #64748b;"></i></button>
                        <button type="button" onclick="insertFormat('_', '_')" title="Itálico" style="padding: 6px; border-radius: 4px; border: none; background: hover: #f1f5f9; cursor: pointer;"><i data-lucide="italic" style="width: 16px; color: #64748b;"></i></button>
                        <div style="width: 1px; height: 24px; background: #e2e8f0; margin: 0 8px;"></div>
                        <button type="button" onclick="insertFormat('\n- ', '')" title="Lista" style="padding: 6px; border-radius: 4px; border: none; background: hover: #f1f5f9; cursor: pointer;"><i data-lucide="list" style="width: 16px; color: #64748b;"></i></button>
                        <button type="button" onclick="insertFormat('[', '](url)')" title="Link" style="padding: 6px; border-radius: 4px; border: none; background: hover: #f1f5f9; cursor: pointer;"><i data-lucide="link" style="width: 16px; color: #64748b;"></i></button>
                        <div style="margin-left: auto;"></div>
                        <button type="button" style="padding: 6px; border-radius: 4px; border: none; cursor: not-allowed; opacity: 0.5;"><i data-lucide="undo" style="width: 16px; color: #64748b;"></i></button>
                        <button type="button" style="padding: 6px; border-radius: 4px; border: none; cursor: not-allowed; opacity: 0.5;"><i data-lucide="redo" style="width: 16px; color: #64748b;"></i></button>
                    </div>

                    <textarea id="temp-comment-area" placeholder="Digite a descrição detalhada da anotação..." style="width: 100%; height: 250px; padding: 16px; border: none; outline: none; resize: none; font-family: 'Inter', sans-serif; font-size: 0.95rem; line-height: 1.5; color: #334155;"></textarea>
                </div>
            </div>

        </div>

        <!-- Footer Actions -->
        <div style="padding: 16px 24px; border-top: 1px solid #f1f5f9; background: #fff; display: flex; justify-content: space-between; align-items: center;">
             <button onclick="shareWhatsApp()" style="background: #fef08a; border: none; padding: 10px 16px; border-radius: 8px; font-weight: 700; color: #854d0e; font-size: 0.85rem; cursor: pointer; display: flex; align-items: center; gap: 8px;">
                <i data-lucide="share-2" style="width: 16px;"></i> Compartilhar
            </button>
            <div style="display: flex; gap: 12px;">
                <button onclick="closeCommentModal()" style="background: white; border: 1px solid #cbd5e1; padding: 10px 20px; border-radius: 8px; font-weight: 600; color: #475569; font-size: 0.9rem; cursor: pointer;">Cancelar</button>
                <button onclick="saveCommentAndFinish()" style="background: #f97316; border: none; padding: 10px 24px; border-radius: 8px; font-weight: 700; color: white; font-size: 0.9rem; cursor: pointer; box-shadow: 0 4px 12px rgba(249, 115, 22, 0.2);">Salvar Anotação</button>
            </div>
        </div>
    </div>
</div>

<style>
@keyframes slideUp { from { transform: translateY(20px); opacity: 0; } to { transform: translateY(0); opacity: 1; } }
@keyframes zoomIn { from { transform: scale(0.9); opacity: 0; } to { transform: scale(1); opacity: 1; } }
</style>

<?php renderAppFooter(); ?>