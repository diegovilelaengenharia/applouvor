<?php
// admin/leitura.php
require_once '../includes/auth.php';
require_once '../includes/db.php';
require_once '../includes/layout.php';

checkLogin(); 

// ==========================================
// 1. BACKEND LOGIC
// ==========================================
$userId = $_SESSION['user_id'];
$now = new DateTime();

// --- 1.1 Fetch Settings ---
$stmt = $pdo->prepare("SELECT setting_key, setting_value FROM user_settings WHERE user_id = ?");
$stmt->execute([$userId]);
$settings = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
$startDate = $settings['reading_plan_start_date'] ?? date('Y-01-01');

// --- 1.2 Calculate Plan Day ---
$start = new DateTime($startDate);
$start->setTime(0, 0, 0); $now->setTime(0, 0, 0);
$diff = $start->diff($now);
$daysPassed = $diff->invert ? -1 * $diff->days : $diff->days;
$planDayIndex = max(1, $daysPassed + 1);

// Convert Index (1..300) to Month/Day (1..12 / 1..25)
// Logic: 25 days per month fixed structure
$currentPlanMonth = floor(($planDayIndex - 1) / 25) + 1;
$currentPlanDay = (($planDayIndex - 1) % 25) + 1;
if($currentPlanMonth > 12) { $currentPlanMonth = 12; $currentPlanDay = 25; } // Cap at end

// --- 1.3 Handle API Requests (POST) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
    $action = $_POST['action'] ?? '';

    // SAVE PROGRESS (Granular or Complete)
    if ($action === 'save_progress') {
        $m = (int)$_POST['month'];
        $d = (int)$_POST['day'];
        $comment = $_POST['comment'] ?? null;
        $versesJson = $_POST['verses'] ?? '[]'; // JSON Array string: "[0, 1, 3]"
        
        try {
            // Check if exists to update or insert
            // We use ON DUPLICATE to be atomic
            $sql = "INSERT INTO reading_progress (user_id, month_num, day_num, verses_read, completed_at)
                    VALUES (?, ?, ?, ?, NOW())
                    ON DUPLICATE KEY UPDATE 
                        verses_read = VALUES(verses_read),
                        completed_at = NOW()";
            
            $params = [$userId, $m, $d, $versesJson];
            
            // If comment is provided (even empty string to clear), update it. 
            // If null, leave as is.
            if ($comment !== null) {
                $sql = "INSERT INTO reading_progress (user_id, month_num, day_num, verses_read, comment, completed_at)
                        VALUES (?, ?, ?, ?, ?, NOW())
                        ON DUPLICATE KEY UPDATE 
                            verses_read = VALUES(verses_read), 
                            comment = VALUES(comment),
                            completed_at = NOW()";
                $params = [$userId, $m, $d, $versesJson, $comment];
            }

            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            echo json_encode(['success' => true]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
        exit;
    }

    // RESET PLAN
    if ($action === 'reset_plan') {
        $pdo->prepare("DELETE FROM reading_progress WHERE user_id = ?")->execute([$userId]);
        echo json_encode(['success' => true]);
        exit;
    }
}

// --- 1.4 Fetch User Progress State ---
$stmt = $pdo->prepare("SELECT month_num, day_num, verses_read, comment, completed_at FROM reading_progress WHERE user_id = ?");
$stmt->execute([$userId]);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Transform into Map for JS: "M_D" => { verses: [0,1], comment: "..." }
$progressMap = [];
$totalChaptersRead = 0;
foreach($rows as $r) {
    $verses = json_decode($r['verses_read'] ?? '[]', true);
    if (!is_array($verses)) $verses = [];
    
    // Count simple completion if at least one verse is read OR old logic
    if (count($verses) > 0 || !empty($r['completed_at'])) {
        $totalChaptersRead++;
    }

    $progressMap["{$r['month_num']}_{$r['day_num']}"] = [
        'verses' => $verses,
        'comment' => $r['comment'],
        'date' => $r['completed_at']
    ];
}

$completionPercent = min(100, round(($totalChaptersRead / 300) * 100));

// --- 1.5 Header Rendering ---
renderAppHeader('Leitura Bíblica'); // Use existing layout helper
?>

<!-- ========================================== -->
<!-- 2. FRONTEND RESOURCES -->
<!-- ========================================== -->
<script src="../assets/js/reading_plan_data.js?v=<?= time() ?>"></script>
<!-- Lucide Icons -->
<script src="https://unpkg.com/lucide@latest"></script>

<style>
    :root {
        --primary: #6366f1;         /* Indigo */
        --primary-soft: #e0e7ff; 
        --success: #10b981;         /* Emerald */
        --success-soft: #d1fae5;
        --warning: #f59e0b;         /* Amber */
        --warning-soft: #fef3c7;
        --surface: #ffffff;
        --bg: #f8fafc;
        --text: #1e293b;
        --text-light: #64748b;
        --border: #e2e8f0;
    }
    
    body { background-color: var(--bg); color: var(--text); }
    
    /* Calendar Strip */
    .cal-strip {
        display: flex; gap: 8px; overflow-x: auto; padding: 12px 16px;
        background: var(--surface); border-bottom: 1px solid var(--border);
        scrollbar-width: none;
    }
    .cal-strip::-webkit-scrollbar { display: none; }
    
    .cal-item {
        min-width: 60px; height: 72px;
        border-radius: 12px;
        background: var(--bg);
        display: flex; flex-direction: column; align-items: center; justify-content: center;
        border: 2px solid transparent; cursor: pointer;
        transition: all 0.2s; position: relative;
        flex-shrink: 0;
    }
    .cal-item.active {
        background: var(--surface); border-color: var(--primary);
        box-shadow: 0 4px 12px rgba(99, 102, 241, 0.2);
        z-index: 2;
    }
    .cal-item.active .cal-num { color: var(--primary); }
    
    .cal-item.done {
        background: var(--success-soft); border-color: #a7f3d0;
    }
    .cal-item.done .cal-num { color: #047857; }
    
    /* Yellow state for partial */
    .cal-item.partial {
        background: var(--warning-soft); border-color: #fde68a;
    }
    .cal-item.partial .cal-num { color: #b45309; }
    
    .cal-month { font-size: 0.7rem; font-weight: 700; color: var(--text-light); text-transform: uppercase; }
    .cal-num { font-size: 1.2rem; font-weight: 800; }

    /* Main Container */
    .main-area {
        max-width: 800px; margin: 0 auto; padding: 20px 16px 120px 16px;
    }

    /* Verses */
    .verse-card {
        background: var(--surface);
        border: 1px solid var(--border);
        border-radius: 16px;
        padding: 16px;
        margin-bottom: 12px;
        display: flex; align-items: center; justify-content: space-between;
        transition: all 0.1s;
        cursor: pointer;
    }
    .verse-card:active { transform: scale(0.99); }
    
    .verse-card.read {
        background: #f0fdf4; border-color: #bbf7d0;
    }
    
    .check-icon {
        width: 24px; height: 24px; border-radius: 50%;
        border: 2px solid var(--border);
        color: transparent; display: flex; align-items: center; justify-content: center;
        margin-right: 12px;
    }
    .verse-card.read .check-icon {
        background: var(--success); border-color: var(--success); color: white;
    }
    
    .btn-read-link {
        background: linear-gradient(135deg, #10b981 0%, #059669 100%);
        color: white; padding: 8px 16px;
        border-radius: 20px; text-decoration: none;
        font-weight: 700; font-size: 0.75rem;
        display: flex; align-items: center; gap: 6px;
        box-shadow: 0 4px 10px rgba(16, 185, 129, 0.3);
    }
    
    /* Floating Bottom Bar (Modern) */
    .bottom-bar {
        position: fixed; bottom: 0; left: 0; right: 0;
        background: rgba(255,255,255,0.9); backdrop-filter: blur(12px);
        border-top: 1px solid var(--border);
        padding: 12px 16px 20px 16px;
        display: grid; grid-template-columns: 1fr 1fr; gap: 12px;
        z-index: 100;
        max-width: 800px; margin: 0 auto;
    }
    
    /* Adjust for sidebar layout if needed */
    @media (min-width: 1024px) {
        .bottom-bar { left: 280px; } /* Assuming sidebar width */
    }
    
    .action-btn {
        background: var(--surface); border: 1px solid var(--border);
        padding: 12px; border-radius: 12px;
        display: flex; flex-direction: column; align-items: center; justify-content: center;
        gap: 6px; cursor: pointer; box-shadow: 0 2px 5px rgba(0,0,0,0.05);
        transition: all 0.2s;
    }
    .action-btn:active { transform: scale(0.98); }
    .action-btn span { font-size: 0.8rem; font-weight: 600; color: var(--text); }
    
    .icon-box {
        width: 32px; height: 32px; border-radius: 10px;
        display: flex; align-items: center; justify-content: center;
    }
    .icon-box.purple { background: #f3e8ff; color: #9333ea; }
    .icon-box.blue { background: #e0f2fe; color: #0284c7; }

    /* Animations */
    @keyframes pulse-green {
        0% { transform: scale(1); }
        50% { transform: scale(1.05); }
        100% { transform: scale(1); }
    }
    .auto-save-feedback {
        position: fixed; top: 80px; left: 50%; transform: translateX(-50%);
        background: var(--text); color: white;
        padding: 8px 16px; border-radius: 20px; font-size: 0.8rem;
        z-index: 2000; opacity: 0; transition: opacity 0.3s; pointer-events: none;
        display: flex; align-items: center; gap: 8px; box-shadow: 0 4px 12px rgba(0,0,0,0.2);
    }
    .auto-save-feedback.show { opacity: 1; top: 90px; }
</style>

<!-- ========================================== -->
<!-- 3. MAIN UI -->
<!-- ========================================== -->

<!-- Header Custom -->
<?php renderPageHeader('Leitura Bíblica', 'Dia ' . $planDayIndex . ' de 300 (' . $completionPercent . '%)'); ?>

<div class="cal-strip" id="calendar-strip">
    <!-- Rendered via JS -->
</div>

<div class="main-area">
    
    <!-- Day Title -->
    <div style="margin-bottom: 24px;">
        <span style="font-size:0.75rem; text-transform:uppercase; color:var(--text-light); font-weight:700;">Leitura de Hoje</span>
        <h1 id="day-title" style="font-size:1.5rem; margin:4px 0;">Carregando...</h1>
    </div>

    <!-- Verses Container -->
    <div id="verses-list"></div>

</div>

<!-- Bottom Actions -->
<div class="bottom-bar">
    <button class="action-btn" onclick="openNoteModal()">
        <div class="icon-box purple"><i data-lucide="pen-line" width="18"></i></div>
        <span>Minha Anotação</span>
    </button>
    <button class="action-btn" onclick="openGroupComments()">
        <div class="icon-box blue"><i data-lucide="message-circle" width="18"></i></div>
        <span>Comentários</span>
    </button>
</div>

<!-- Auto Save Toast -->
<div id="save-toast" class="auto-save-feedback">
    <i data-lucide="check" width="14"></i> Salvo automaticamente
</div>

<!-- ========================================== -->
<!-- 4. MODALS (Simplified) -->
<!-- ========================================== -->

<!-- Note Modal -->
<div id="modal-note" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); z-index:2000; align-items:center; justify-content:center;">
    <div style="background:white; width:90%; max-width:500px; max-height:80vh; border-radius:20px; padding:20px; display:flex; flex-direction:column;">
        <h3 style="margin:0 0 16px 0;">Minha Anotação</h3>
        <textarea id="note-input" style="flex:1; min-height:150px; padding:12px; border:1px solid #ddd; border-radius:12px; font-family:inherit; margin-bottom:16px;" placeholder="O que Deus falou com você hoje?"></textarea>
        <div style="display:flex; justify-content:end; gap:10px;">
            <button onclick="document.getElementById('modal-note').style.display='none'" style="padding:10px 20px; border:none; background:#f1f5f9; border-radius:10px; cursor:pointer;">Cancelar</button>
            <button onclick="saveNote()" style="padding:10px 20px; border:none; background:var(--primary); color:white; border-radius:10px; font-weight:700; cursor:pointer;">Salvar</button>
        </div>
    </div>
</div>

<!-- Config Modal (Reset) -->
<div id="modal-config" style="display:none; position:fixed; top:0; right:0; width:300px; height:100%; background:white; z-index:3000; padding:20px; box-shadow:-5px 0 20px rgba(0,0,0,0.1);">
    <h3>Configurações</h3>
    <button onclick="resetPlan()" style="width:100%; padding:14px; background:#fee2e2; color:#b91c1c; border:none; border-radius:12px; display:flex; gap:8px; align-items:center; justify-content:center; margin-top:20px; cursor:pointer; font-weight:700;">
        <i data-lucide="trash-2" width="18"></i> Resetar Plano
    </button>
    <button onclick="document.getElementById('modal-config').style.display='none'" style="margin-top:20px; width:100%; padding:10px;">Fechar</button>
</div>

<!-- Message Group Modal Stub -->
<script>
function openGroupComments() { alert('Funcionalidade de grupo em breve!'); }
</script>


<!-- ========================================== -->
<!-- 5. FRONTEND LOGIC (Clean & Sync) -->
<!-- ========================================== -->
<script>
// --- STATE MANAGEMENT ---
// Source of truth is now Server Data, injected via PHP
const serverData = <?= json_encode($progressMap) ?>;
const planStartDate = <?= json_encode($planDayIndex) ?>;
const currentPlanMonth = <?= json_encode($currentPlanMonth) ?>;
const currentPlanDay = <?= json_encode($currentPlanDay) ?>;

const state = {
    m: currentPlanMonth,
    d: currentPlanDay,
    data: serverData, // Map "M_D" -> { verses: [], comment: "" }
    saveTimer: null
};

// --- INIT ---
function init() {
    renderCalendar();
    loadDay(state.m, state.d);
    
    // Config Button hook (Header is global)
    // We can inject the config button behavior if needed
}

// --- CALENDAR ---
function renderCalendar() {
    const el = document.getElementById('calendar-strip');
    el.innerHTML = '';
    
    const months = ["", "JAN", "FEV", "MAR", "ABR", "MAI", "JUN", "JUL", "AGO", "SET", "OUT", "NOV", "DEZ"];
    // Render current month (25 days)
    const m = state.m;
    
    for(let d=1; d<=25; d++) {
        const key = `${m}_${d}`;
        const info = state.data[key];
        const isDone = info && info.verses && info.verses.length > 0 && isDayComplete(m,d); 
        const isPartial = info && info.verses && info.verses.length > 0 && !isDone;
        
        const div = document.createElement('div');
        div.className = `cal-item ${state.d === d ? 'active' : ''} ${isDone ? 'done' : ''} ${isPartial ? 'partial' : ''}`;
        div.onclick = () => {
            state.d = d;
            renderCalendar(); // Re-render to update active state
            loadDay(m, d);
        };
        div.innerHTML = `
            <div class="cal-month">${months[m]}</div>
            <div class="cal-num">${d}</div>
        `;
        el.appendChild(div);
        
        // Auto scroll to active
        if(state.d === d) {
            setTimeout(() => div.scrollIntoView({behavior:'smooth', inline:'center'}), 100);
        }
    }
}

// --- LOAD DAY CONTENT ---
function loadDay(m, d) {
    const list = document.getElementById('verses-list');
    const title = document.getElementById('day-title');
    title.innerText = `Dia ${d}`;
    
    // Get Plan Data
    if (!bibleReadingPlan || !bibleReadingPlan[m] || !bibleReadingPlan[m][d-1]) {
        list.innerHTML = '<div style="padding:20px; text-align:center; color:#888;">Sem leitura programada.</div>';
        return;
    }
    
    const verses = bibleReadingPlan[m][d-1];
    const key = `${m}_${d}`;
    const savedVerses = (state.data[key] && state.data[key].verses) ? state.data[key].verses : [];
    
    list.innerHTML = '';
    
    verses.forEach((vText, idx) => {
        const isRead = savedVerses.includes(idx);
        
        const card = document.createElement('div');
        card.className = `verse-card ${isRead ? 'read' : ''}`;
        card.onclick = (e) => {
            if(e.target.closest('a')) return;
            toggleVerse(m, d, idx);
        };
        
        // Build Bible Link (Simple Helper)
        const link = "https://www.bible.com/pt/bible/1608/" + vText.replace(/\s/g, '.').replace(/:/g, '.');
        
        card.innerHTML = `
            <div style="display:flex; align-items:center;">
                <div class="check-icon"><i data-lucide="check" width="14"></i></div>
                <span style="font-weight:600; color:#334155;">${vText}</span>
            </div>
            <a href="${link}" target="_blank" class="btn-read-link">
                LER <i data-lucide="book-open" width="12"></i>
            </a>
        `;
        list.appendChild(card);
    });
    
    if(window.lucide) lucide.createIcons();
}

// --- LOGIC: TOGGLE & SAVE ---
function toggleVerse(m, d, idx) {
    const key = `${m}_${d}`;
    
    // Init state if empty
    if (!state.data[key]) state.data[key] = { verses: [], comment: "" };
    
    const list = state.data[key].verses;
    const exists = list.indexOf(idx);
    
    if (exists === -1) {
        list.push(idx); // Mark read
    } else {
        list.splice(exists, 1); // Unmark
    }
    
    // Optimistic UI Update
    loadDay(m, d);
    checkCompletionAndNavigate(m, d);
    
    // Debounce Save
    showToast();
    clearTimeout(state.saveTimer);
    state.saveTimer = setTimeout(() => {
        saveToServer(m, d);
    }, 1000);
}

function isDayComplete(m, d) {
    if (!bibleReadingPlan || !bibleReadingPlan[m]) return false;
    const totalVerses = bibleReadingPlan[m][d-1].length;
    const key = `${m}_${d}`;
    const readCount = (state.data[key] && state.data[key].verses) ? state.data[key].verses.length : 0;
    return readCount >= totalVerses;
}

function checkCompletionAndNavigate(m, d) {
    // Check if day is complete
    if (isDayComplete(m, d)) {
        renderCalendar(); // Update green status
        
        // Auto navigate to next unfinished day
        setTimeout(() => {
             navigateToNextDay();
        }, 1200);
    } else {
        renderCalendar(); // Update partial status (yellow)
    }
}

function navigateToNextDay() {
    // Simple logic: find next day in current month that is not complete
    // We stay in current month for UX stability, unless it's last day
    let nextD = state.d + 1;
    if (nextD <= 25) {
        state.d = nextD;
        loadDay(state.m, state.d);
        renderCalendar();
    }
}

function saveToServer(m, d) {
    const key = `${m}_${d}`;
    const data = state.data[key];
    
    const form = new FormData();
    form.append('action', 'save_progress');
    form.append('month', m);
    form.append('day', d);
    form.append('verses', JSON.stringify(data.verses));
    if(data.comment) form.append('comment', data.comment);
    
    fetch('leitura.php', { method: 'POST', body: form })
        .then(r => r.json())
        .then(res => {
            if(res.success) console.log("Saved.");
        })
        .catch(err => console.error("Save failed", err));
}

// --- MODALS ---
function openNoteModal() {
    const key = `${state.m}_${state.d}`;
    const current = state.data[key]?.comment || "";
    document.getElementById('note-input').value = current;
    document.getElementById('modal-note').style.display = 'flex';
}

function saveNote() {
    const val = document.getElementById('note-input').value;
    const key = `${state.m}_${state.d}`;
    
    if(!state.data[key]) state.data[key] = { verses: [], comment: "" };
    state.data[key].comment = val;
    
    saveToServer(state.m, state.d);
    document.getElementById('modal-note').style.display = 'none';
}

function openConfig() {
    document.getElementById('modal-config').style.display = 'block';
}

function resetPlan() {
    if(!confirm("Resetar TUDO?")) return;
    
    const f = new FormData(); f.append('action', 'reset_plan');
    fetch('leitura.php', { method:'POST', body:f })
        .then(() => window.location.reload());
}

function showToast() {
    const el = document.getElementById('save-toast');
    el.classList.add('show');
    setTimeout(() => el.classList.remove('show'), 2000);
}

// Start
init();
window.openConfig = openConfig; // Expose to header
</script>