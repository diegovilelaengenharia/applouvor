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
        // Optional: Reset start date to today?
        // $pdo->prepare("UPDATE user_settings SET setting_value = CURDATE() WHERE user_id = ? AND setting_key = 'reading_plan_start_date'")->execute([$userId]);
        header("Location: leitura.php"); exit;
    }
}

// Header with Actions
renderAppHeader('Leitura Bíblica');
// Inject Settings Button into Header via JS or inline absolute since we are in a "Page" concept
?>

<!-- Import JSON Data -->
<script src="../assets/js/reading_plan_data.js"></script>

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
    .verse-check-item {
        background: var(--bg-surface);
        border: 1px solid var(--border-color);
        border-radius: 12px;
        padding: 16px;
        margin-bottom: 12px;
        display: flex;
        align-items: center;
        justify-content: space-between;
        transition: all 0.2s;
    }
    .verse-check-item:hover { border-color: var(--primary); }
    
    .verse-info { display: flex; align-items: center; gap: 12px; flex: 1; }
    .verse-text { font-size: 1rem; font-weight: 600; color: var(--text-main); text-decoration: none; }
    .verse-text:hover { text-decoration: underline; color: var(--primary); }

    .check-circle {
        width: 28px; height: 28px;
        border-radius: 50%;
        border: 2px solid var(--border-color);
        display: flex; align-items: center; justify-content: center;
        cursor: pointer;
        transition: all 0.2s;
        background: var(--bg-body);
    }
    .check-circle.checked {
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
    }
    .modal-config.active { display: flex; }
    .config-header {
        padding: 16px 20px;
        background: var(--bg-surface);
        border-bottom: 1px solid var(--border-color);
        display: flex; justify-content: space-between; align-items: center;
    }

    /* Page Header Override for Settings Icon */
    .page-header-actions {
        position: absolute; right: 20px; top: 20px;
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
    <div style="margin-bottom: 24px; display: flex; justify-content: space-between; align-items: end;">
        <div>
            <div style="font-size: 0.8rem; color: var(--text-muted); font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px;">Leitura de Hoje</div>
            <h1 id="main-date-title" style="margin: 4px 0 0 0; font-size: 1.5rem;">Carregando...</h1>
        </div>
        <div style="text-align: right;">
            <div style="font-size: 0.75rem; color: #ef4444; font-weight: 600;"><?= $delay > 0 ? $delay . ' Dias Perdidos' : 'Em dia!' ?></div>
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
    <div style="display: flex; gap: 12px; margin-bottom: 12px;">
         <button id="comment-trigger" onclick="openCommentModal()" style="
            flex: 1; display: flex; align-items: center; justify-content: center; gap: 8px;
            background: var(--bg-surface); border: 1px solid var(--border-color); color: var(--text-main);
            padding: 14px; border-radius: 20px; font-weight: 600; font-size: 0.95rem;
         ">
            <i data-lucide="message-square" style="width: 18px;"></i>
            <span id="comment-text-label">Anotação</span>
         </button>
    </div>

    <button id="btn-main-action" class="btn-finish-day" onclick="completeDay()">
        Concluir Leitura
    </button>
</div>

<!-- CONFIG MODAL -->
<div id="modal-config" class="modal-config">
    <div class="config-header">
        <button onclick="closeConfig()" style="background:none; border:none; padding:8px; margin-left:-8px;"><i data-lucide="arrow-left"></i></button>
        <h3 style="margin:0; font-size: 1.1rem;">Configurações</h3>
        <div style="width: 40px;"></div> <!-- Spacer -->
    </div>
    
    <div style="padding: 24px;">
        <form method="POST">
            <input type="hidden" name="action" value="save_settings">
            
            <h4 style="margin-bottom: 12px;">Preferências</h4>
            
            <div class="form-card" style="margin-bottom: 24px;">
                <label style="display: block; margin-bottom: 8px; font-weight: 600; font-size: 0.9rem;">Horário do Lembrete</label>
                <input type="time" name="notification_time" value="<?= htmlspecialchars($notifTime) ?>" style="
                    width: 100%; padding: 12px; border: 1px solid var(--border-color); border-radius: 8px; font-family: inherit; font-size: 1rem;
                ">
            </div>

            <div class="form-card" style="margin-bottom: 24px;">
                <label style="display: block; margin-bottom: 8px; font-weight: 600; font-size: 0.9rem;">Data de Início do Plano</label>
                <input type="date" name="start_date" value="<?= htmlspecialchars($startDate) ?>" style="
                    width: 100%; padding: 12px; border: 1px solid var(--border-color); border-radius: 8px; font-family: inherit; font-size: 1rem;
                ">
                <p style="font-size: 0.8rem; color: var(--text-muted); margin-top: 8px;">Alterar a data ajusta o dia atual do plano.</p>
            </div>

            <button type="submit" class="btn-primary" style="width: 100%; padding: 14px; margin-bottom: 24px;">Salvar Alterações</button>
        </form>

        <hr style="border: 0; border-top: 1px solid var(--border-color); margin-bottom: 24px;">

        <div class="form-card" style="border-color: #fecaca; background: #fef2f2;">
             <h4 style="color: #991b1b; margin-bottom: 8px;">Zona de Perigo</h4>
             <p style="font-size: 0.85rem; color: #7f1d1d; margin-bottom: 16px;">Isso irá apagar todo o seu histórico de leitura.</p>
             <form method="POST" onsubmit="return confirm('Tem certeza? Isso não pode ser desfeito.');">
                 <input type="hidden" name="action" value="reset_plan">
                 <button type="submit" style="
                    width: 100%; padding: 12px; border: 1px solid #ef4444; background: white; color: #ef4444; border-radius: 8px; font-weight: 600; cursor: pointer;
                 ">Resetar o Plano</button>
             </form>
        </div>
    </div>
</div>

<!-- COMMENT MODAL (Reused Logic) -->
<div id="modal-comment" class="modal-backdrop" style="position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 2000; display: none; align-items: center; justify-content: center;">
    <div style="background: var(--bg-surface); width: 90%; max-width: 400px; border-radius: 20px; padding: 24px; box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1);">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px;">
            <h3 style="font-size: 1.25rem;">Anotação</h3>
            <button onclick="closeCommentModal()" style="background:none; border:none; cursor:pointer;"><i data-lucide="x"></i></button>
        </div>
        <textarea id="temp-comment-area" placeholder="Escreva aqui..." style="width: 100%; height: 120px; padding: 12px; border-radius: 12px; border: 1px solid var(--border-color); margin-bottom: 16px;"></textarea>
        <button onclick="saveCommentAndFinish()" class="btn-primary" style="width: 100%;">Salvar</button>
    </div>
</div>

<script>
// Data Params
const planDayIndex = <?= $planDayIndex ?>; // Based on Start Date
const currentPlanMonth = <?= $currentPlanMonth ?>;
const currentPlanDay = <?= $currentPlanDay ?>;
const completedMap = <?= json_encode($completedIds) ?>; // "M_D" -> {id...}

// State
let selectedMonth = currentPlanMonth;
let selectedDay = currentPlanDay;

// Init
function init() {
    renderCalendar();
    selectDay(currentPlanMonth, currentPlanDay); // Select "Today" by default
    lucide.createIcons();
    
    // Scroll to active day
    setTimeout(() => {
        const active = document.querySelector('.cal-day-item.active');
        if(active) active.scrollIntoView({ behavior: 'smooth', inline: 'center', block: 'nearest' });
    }, 100);
}

function renderCalendar() {
    const strip = document.getElementById('calendar-strip');
    strip.innerHTML = '';
    
    const m = currentPlanMonth;
    
    // Render current month days
    for (let d = 1; d <= 25; d++) {
        const isCompleted = completedMap[`${m}_${d}`];
        const isActive = (d === currentPlanDay); 
        
        const el = document.createElement('div');
        el.className = `cal-day-item ${isActive ? 'active' : ''} ${isCompleted ? 'completed' : ''}`;
        el.id = `day-card-${m}-${d}`;
        el.onclick = () => selectDay(m, d);
        
        el.innerHTML = `
            <div class="cal-day-month">${getMonthAbbr(m)}</div>
            <div class="cal-day-num">${d}</div>
        `;
        strip.appendChild(el);
    }
}

function selectDay(m, d) {
    document.querySelectorAll('.cal-day-item').forEach(e => e.classList.remove('active'));
    document.getElementById(`day-card-${m}-${d}`)?.classList.add('active');
    
    selectedMonth = m;
    selectedDay = d;
    
    renderContent(m, d);
}

function renderContent(m, d) {
    const container = document.getElementById('verses-container');
    const title = document.getElementById('main-date-title');
    const btn = document.getElementById('btn-main-action');
    const commentLabel = document.getElementById('comment-text-label');
    
    // Title
    const globalIdx = (m - 1) * 25 + d;
    title.innerHTML = `Dia ${d} <span style="font-size:0.9rem; color:var(--text-muted); font-weight:400;">(Dia ${globalIdx} do ano)</span>`;
    
    // Ensure data exists
    if (!bibleReadingPlan || !bibleReadingPlan[m] || !bibleReadingPlan[m][d-1]) {
        container.innerHTML = "<div style='padding:20px; text-align:center; color:var(--text-muted);'>Nenhuma leitura cadastrada para este dia.</div>";
        btn.style.display = 'none';
        return;
    }
    
    const verses = bibleReadingPlan[m][d-1]; // string array
    container.innerHTML = '';
    
    verses.forEach(v => {
        const link = getBibleLink(v);
        // Clean verse text
        const item = document.createElement('div');
        item.className = 'verse-check-item';
        // Note: Removing onclick from container to avoid misclicks. Button specific.
        
        item.innerHTML = `
            <div class="verse-info">
                <i data-lucide="book" style="width:20px; color:var(--primary);"></i>
                <div class="verse-text">${v}</div>
            </div>
            <a href="${link}" target="_blank" class="ripple" style="
                background: var(--primary-light); color: var(--primary);
                padding: 8px 16px; border-radius: 20px; text-decoration: none;
                font-size: 0.8rem; font-weight: 700; display:flex; align-items:center; gap:6px;
            ">
                LER <i data-lucide="external-link" style="width:14px;"></i>
            </a>
        `;
        container.appendChild(item);
    });
    
    lucide.createIcons();
    
    // Footer State
    const isDone = completedMap[`${m}_${d}`];
    
    if (isDone) {
        btn.innerHTML = '<i data-lucide="check"></i> Leitura Concluída';
        btn.style.background = '#d1fae5';
        btn.style.color = '#065f46';
        btn.onclick = null;
        
        commentLabel.innerText = isDone.comment ? "Ver Anotação" : "Adicionar Anotação";
    } else {
        btn.innerHTML = 'Concluir Leitura';
        btn.style.background = '#111';
        btn.style.color = 'white';
        btn.onclick = () => completeDay();
        
        commentLabel.innerText = "Adicionar Anotação";
    }
}

// ... Actions and Modals same as before ...

// Actions
function completeDay() {
    // Check if user wants to add comment?
    // User requested "Começar a leitura" which implies opening content. 
    // But since we are listing verses, "Concluir" makes sense at bottom.
    // Let's just mark as read.
    const m = selectedMonth;
    const d = selectedDay;
    
    const formData = new FormData();
    formData.append('action', 'mark_read');
    formData.append('month', m);
    formData.append('day', d);
    
    fetch('leitura.php', { method: 'POST', body: formData })
    .then(() => window.location.reload());
}

function saveCommentAndFinish() {
    const val = document.getElementById('temp-comment-area').value;
    const m = selectedMonth;
    const d = selectedDay;
    
    const formData = new FormData();
    formData.append('action', 'mark_read');
    formData.append('month', m);
    formData.append('day', d);
    formData.append('comment', val);
    
    fetch('leitura.php', { method: 'POST', body: formData })
    .then(() => window.location.reload());
}

// Config Modal
function openConfig() { document.getElementById('modal-config').classList.add('active'); }
function closeConfig() { document.getElementById('modal-config').classList.remove('active'); }

// Comment Modal
function openCommentModal() {
    const m = selectedMonth;
    const d = selectedDay;
    const isDone = completedMap[`${m}_${d}`];
    document.getElementById('temp-comment-area').value = isDone ? (isDone.comment || '') : '';
    document.getElementById('modal-comment').style.display = 'flex';
}
function closeCommentModal() { document.getElementById('modal-comment').style.display = 'none'; }

// Helpers
function getMonthAbbr(m) {
    return ["", "JAN", "FEV", "MAR", "ABR", "MAI", "JUN", "JUL", "AGO", "SET", "OUT", "NOV", "DEZ"][m];
}

window.addEventListener('load', init);
</script>

<?php renderAppFooter(); ?>