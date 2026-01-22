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
<script src="../assets/js/reading_plan_data.js?v=<?= time() ?>"></script>

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
        border-radius: 16px;
        box-shadow: var(--shadow-sm);
        border: 1px solid transparent;
        cursor: pointer;
        transition: all 0.2s;
    }
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
    <div style="margin-bottom: 24px;">
        <div style="display: flex; justify-content: space-between; align-items: end; margin-bottom: 12px;">
            <div>
                <div style="font-size: 0.8rem; color: var(--text-muted); font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px;">Leitura de Hoje</div>
                <h1 id="main-date-title" style="margin: 4px 0 0 0; font-size: 1.5rem;">Carregando...</h1>
            </div>
            <div style="text-align: right;">
                <div style="font-size: 0.75rem; color: #ef4444; font-weight: 600;"><?= $delay > 0 ? $delay . ' Dias Perdidos' : 'Em dia!' ?></div>
            </div>
        </div>
        
        <!-- Daily Progress Bar -->
        <div style="background: var(--border-color); height: 6px; border-radius: 3px; overflow: hidden; position: relative;">
            <div id="daily-progress-bar" style="width: 0%; height: 100%; background: #10b981; transition: width 0.3s ease;"></div>
        </div>
        <div style="display: flex; justify-content: space-between; margin-top: 4px; font-size: 0.7rem; color: var(--text-muted);">
            <span>Progresso Diário</span>
            <span id="daily-progress-text">0%</span>
        </div>
    </div>

    <!-- Links List -->
    <div id="verses-container">
        <!-- JS Populated -->
    </div>

    <!-- Empty Space for bottom bar -->
    <div style="height: 120px;"></div>
</div>

<!-- BOTTOM ACTION BAR -->
<div class="bottom-action-bar" id="bottom-bar">
    <button id="btn-main-action" class="btn-finish-day" onclick="completeDay()">
        Concluir Leitura
    </button>

    <div style="display: flex; gap: 12px; margin-top: 12px;">
         <button id="comment-trigger" onclick="openCommentModal()" style="
            flex: 1; display: flex; align-items: center; justify-content: center; gap: 8px;
            background: var(--bg-surface); border: 1px solid var(--border-color); color: var(--text-main);
            padding: 14px; border-radius: 16px; font-weight: 600; font-size: 0.95rem; box-shadow: var(--shadow-sm);
         ">
            <i data-lucide="message-square" style="width: 18px;"></i>
            <span id="comment-text-label">Minha Anotação</span>
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
    </div>
</div>

<!-- COMMENT MODAL (Advanced) -->
<div id="modal-comment" class="modal-backdrop" style="position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.6); backdrop-filter: blur(4px); z-index: 2000; display: none; align-items: center; justify-content: center;">
    <div style="background: var(--bg-surface); width: 90%; max-width: 600px; border-radius: 24px; box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25); overflow: hidden; animation: slideUp 0.3s;">
        <div style="background: linear-gradient(135deg, #3b82f6, #1d4ed8); padding: 16px 24px; color: white; display: flex; justify-content: space-between; align-items: center;">
            <div><h3 style="margin: 0; font-size: 1.1rem;">Minhas Anotações</h3></div>
            <button onclick="closeCommentModal()" style="background: rgba(255,255,255,0.2); border: none; width: 32px; height: 32px; border-radius: 50%; color: white; cursor: pointer; display:flex; align-items:center; justify-content:center;"><i data-lucide="x" style="width:18px;"></i></button>
        </div>
        
        <div style="padding: 24px;">
            <!-- Simple Toolbar -->
            <div style="display: flex; gap: 8px; margin-bottom: 12px; overflow-x: auto;">
                <button type="button" onclick="insertFormat('**', '**')" style="padding: 6px 12px; border: 1px solid var(--border-color); border-radius: 8px; background: #fff; font-weight: bold;">B</button>
                <button type="button" onclick="insertFormat('_', '_')" style="padding: 6px 12px; border: 1px solid var(--border-color); border-radius: 8px; background: #fff; font-style: italic;">I</button>
                <button type="button" onclick="insertFormat('\n> ', '')" style="padding: 6px 12px; border: 1px solid var(--border-color); border-radius: 8px; background: #fff;">❝</button>
                <button type="button" onclick="shareWhatsApp()" style="padding: 6px 12px; border: 1px solid #25D366; border-radius: 8px; background: #dcfce7; color: #166534; display: flex; align-items: center; gap: 4px; margin-left: auto;">
                    <i data-lucide="share-2" style="width: 14px;"></i> WhatsApp
                </button>
            </div>

            <textarea id="temp-comment-area" placeholder="O que Deus falou com você hoje?" style="width: 100%; height: 200px; padding: 16px; border-radius: 16px; border: 1px solid var(--border-color); font-family: 'Inter', sans-serif; font-size: 1rem; background: var(--bg-body); resize: none; margin-bottom: 20px; outline: none;"></textarea>
            
            <button onclick="saveCommentAndFinish()" class="ripple" style="width: 100%; padding: 16px; background: #0f172a; color: white; border: none; border-radius: 16px; font-weight: 700; font-size: 1rem;">Salvar Anotação</button>
        </div>
    </div>
</div>

<style>
@keyframes slideUp { from { transform: translateY(20px); opacity: 0; } to { transform: translateY(0); opacity: 1; } }
</style>

<script>
window.openConfig = function() { document.getElementById('modal-config').classList.add('active'); };
window.closeConfig = function() { document.getElementById('modal-config').classList.remove('active'); };
</script>

<?php renderAppFooter(); ?>

<script>
// Data Params
const planDayIndex = <?= $planDayIndex ?>; 
const currentPlanMonth = <?= $currentPlanMonth ?>;
const currentPlanDay = <?= $currentPlanDay ?>;
const completedMap = <?= json_encode($completedIds) ?>;

// State
let selectedMonth = currentPlanMonth;
let selectedDay = currentPlanDay;

// Init
function init() {
    renderCalendar();
    selectDay(currentPlanMonth, currentPlanDay); 
    lucide.createIcons();
    setTimeout(() => {
        const active = document.querySelector('.cal-day-item.active');
        if(active) active.scrollIntoView({ behavior: 'smooth', inline: 'center', block: 'nearest' });
    }, 100);
}

function renderCalendar() {
    const strip = document.getElementById('calendar-strip');
    strip.innerHTML = '';
    const m = currentPlanMonth;
    for (let d = 1; d <= 25; d++) {
        const isCompleted = completedMap[`${m}_${d}`];
        const isActive = (d === currentPlanDay); 
        const el = document.createElement('div');
        el.className = `cal-day-item ${isActive ? 'active' : ''} ${isCompleted ? 'completed' : ''}`;
        el.id = `day-card-${m}-${d}`;
        el.onclick = () => selectDay(m, d);
        el.innerHTML = `<div class="cal-day-month">${getMonthAbbr(m)}</div><div class="cal-day-num">${d}</div>`;
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
    const commentLabel = document.getElementById('comment-text-label');
    const globalIdx = (m - 1) * 25 + d;

    // Header Content Update (No encouragement text)
    const titleArea = document.getElementById('main-date-title').parentElement;
    titleArea.innerHTML = `
        <div style="font-size: 0.8rem; color: var(--text-muted); font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px; opacity:0.8;">
            Dia ${globalIdx} / 300
        </div>
        <h1 style="margin: 4px 0 0 0; font-size: 1.5rem; display:flex; align-items:center; gap:8px;">
            Dia ${d} 
        </h1>
    `;
    
    if (!bibleReadingPlan || !bibleReadingPlan[m] || !bibleReadingPlan[m][d-1]) {
        container.innerHTML = "<div style='padding:20px; text-align:center;'>Sem leitura.</div>";
        updateProgress(0, 1);
        return;
    }
    
    const verses = bibleReadingPlan[m][d-1]; 
    container.innerHTML = '';
    
    let readCount = 0;
    
    verses.forEach((v, idx) => {
        const link = getBibleLink(v);
        const storageKey = `reading_check_${m}_${d}_${idx}`;
        const isRead = localStorage.getItem(storageKey) === 'true';
        if(isRead) readCount++;
        
        const item = document.createElement('div');
        item.className = `verse-check-item ${isRead ? 'read' : ''}`;
        
        // Added green tint to parent if read
        if(isRead) {
            item.style.background = '#ecfdf5';
            item.style.borderColor = '#a7f3d0';
        }
        
        item.onclick = (e) => {
            if(e.target.closest('a')) return;
            const newState = !item.classList.contains('read');
            item.classList.toggle('read');
            localStorage.setItem(storageKey, newState);
            
            // Visual Updates
            if(newState) {
                item.style.background = '#ecfdf5';
                item.style.borderColor = '#a7f3d0';
                item.querySelector('.check-circle').style.background = '#10b981';
                item.querySelector('.check-circle').style.borderColor = '#10b981';
                item.querySelector('.check-circle i').style.opacity = '1';
                item.querySelector('.verse-text').style.color = '#065f46';
                item.querySelector('.verse-text').style.textDecoration = 'line-through';
                item.querySelector('.verse-text').style.opacity = '0.8';
            } else {
                item.style.background = 'var(--bg-surface)';
                item.style.borderColor = 'var(--border-color)';
                item.querySelector('.check-circle').style.background = 'transparent';
                item.querySelector('.check-circle').style.borderColor = 'var(--border-color)';
                item.querySelector('.check-circle i').style.opacity = '0';
                item.querySelector('.verse-text').style.color = 'var(--text-main)';
                item.querySelector('.verse-text').style.textDecoration = 'none';
                item.querySelector('.verse-text').style.opacity = '1';
            }
            
            const total = document.querySelectorAll('.verse-check-item').length;
            const currentRead = document.querySelectorAll('.verse-check-item.read').length;
            updateProgress(currentRead, total);
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
        container.appendChild(item);
    });
    
    lucide.createIcons();
    updateProgress(readCount, verses.length);
    
    const isDone = completedMap[`${m}_${d}`];
    commentLabel.innerText = (isDone && isDone.comment) ? "Editar Minha Anotação" : "Adicionar Anotação";
}

function updateProgress(current, total) {
    if(total === 0) return;
    const pct = Math.round((current / total) * 100);
    document.getElementById('daily-progress-bar').style.width = `${pct}%`;
    document.getElementById('daily-progress-text').innerText = `${pct}% Concluído`;
    
    const btn = document.getElementById('btn-main-action');
    const isDoneServer = completedMap[`${selectedMonth}_${selectedDay}`];
    
    // Logic: Yellow (Incomplete) vs Green (Complete)
    // We prioritize local state interaction over server "done" state for button colors, 
    // unless user hasn't touched anything? 
    // Actually, if current < total, it MUST be yellow.
    
    if (current < total) {
        const missing = total - current;
        btn.innerHTML = `<i data-lucide="circle"></i> Faltam ${missing} leituras...`;
        btn.onclick = () => alert("Por favor, marque todos os textos como lidos antes de concluir.");
        // Yellow Amber Styling
        btn.style = "width:100%; background:#fef3c7; color:#d97706; border:1px solid #fde68a; padding:16px; border-radius:16px; font-weight:700; display:flex; justify-content:center; gap:8px; box-shadow:none;";
    } else {
        // Complete (Locally)
        // Check if server also thinks it's done
        if (isDoneServer) {
             btn.innerHTML = '<i data-lucide="check-circle-2"></i> Leitura Registrada';
             // If user wants to re-submit (maybe updated note?), allowed.
             btn.onclick = () => completeDay();
             // Moderate Green / Grayish
             btn.style = "width:100%; background:#d1fae5; color:#065f46; border:none; padding:16px; border-radius:16px; font-weight:700; display:flex; justify-content:center; gap:8px; box-shadow:none;";
        } else {
             // Ready to Finish (New)
             btn.innerHTML = 'Confirmar Conclusão Do Dia';
             btn.onclick = () => completeDay();
             // Strong Green
             btn.style = "width:100%; background:#10b981; color:white; border:none; padding:16px; border-radius:16px; font-weight:700; box-shadow: 0 4px 12px rgba(16, 185, 129, 0.3); display:flex; justify-content:center; gap:8px;";
        }
    }
    lucide.createIcons();
}

function insertFormat(startTag, endTag) {
    const tarea = document.getElementById('temp-comment-area');
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
    const text = document.getElementById('temp-comment-area').value;
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
    fetch('leitura.php', { method: 'POST', body: formData }).then(() => window.location.reload());
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
    fetch('leitura.php', { method: 'POST', body: formData }).then(() => window.location.reload());
}

function openConfig() { document.getElementById('modal-config').classList.add('active'); }
function closeConfig() { document.getElementById('modal-config').classList.remove('active'); }

function openCommentModal() {
    const m = selectedMonth;
    const d = selectedDay;
    const isDone = completedMap[`${m}_${d}`];
    document.getElementById('temp-comment-area').value = isDone ? (isDone.comment || '') : '';
    document.getElementById('modal-comment').style.display = 'flex';
}
function closeCommentModal() { document.getElementById('modal-comment').style.display = 'none'; }

function getMonthAbbr(m) {
    return ["", "JAN", "FEV", "MAR", "ABR", "MAI", "JUN", "JUL", "AGO", "SET", "OUT", "NOV", "DEZ"][m];
}

window.addEventListener('load', init);
</script>