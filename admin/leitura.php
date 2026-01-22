<?php
// admin/leitura.php
require_once '../includes/auth.php';
require_once '../includes/db.php';
require_once '../includes/layout.php';

// Ensure user is logged in
checkLogin(); 

// ==========================================
// AUTO-MIGRATION (SELF-HEALING)
// ==========================================
try {
    $check = $pdo->query("SHOW TABLES LIKE 'reading_progress'");
    if ($check->rowCount() == 0) {
        // ... (creation logic same as before, omitted for brevity if already exists, but safer to keep if file is standalone)
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS reading_progress (
                id INT AUTO_INCREMENT PRIMARY KEY,
                user_id INT NOT NULL,
                month_num INT NOT NULL,
                day_num INT NOT NULL,
                completed_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                comment TEXT,
                UNIQUE KEY unique_user_reading (user_id, month_num, day_num),
                FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
        ");
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS user_settings (
                user_id INT NOT NULL,
                setting_key VARCHAR(50) NOT NULL,
                setting_value TEXT,
                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (user_id, setting_key),
                FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
        ");
    }
} catch (PDOException $e) { /* Silently fail */ }

// ==========================================
// BACKEND LOGIC
// ==========================================
$userId = $_SESSION['user_id'];
$currentMonth = (int)date('n');
$currentDay = (int)date('j');
$calcDay = min($currentDay, 25);
$expectedTotal = ($currentMonth - 1) * 25 + $calcDay;

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
        } catch (Exception $e) { /* Ignore */ }
        
        if(!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
             echo json_encode(['success' => true]);
             exit;
        }
        header("Location: leitura.php"); 
        exit;
    }
    
    if ($action === 'save_settings') {
        $time = $_POST['notification_time'];
        $pdo->prepare("INSERT INTO user_settings (user_id, setting_key, setting_value) VALUES (?, 'notification_time', ?) ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)")
            ->execute([$userId, $time]);
        header("Location: leitura.php?success=1");
        exit;
    }
}

// Fetch Data
$completedReadings = [];
$stmt = $pdo->prepare("SELECT * FROM reading_progress WHERE user_id = ?");
$stmt->execute([$userId]);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

$totalCompleted = count($rows);
$percentage = round(($totalCompleted / 300) * 100, 1);
$delay = $expectedTotal - $totalCompleted;
$delay = max(0, $delay);

foreach ($rows as $r) {
    $completedReadings[$r['month_num']][$r['day_num']] = $r;
}

$stmt = $pdo->prepare("SELECT setting_value FROM user_settings WHERE user_id = ? AND setting_key = 'notification_time'");
$stmt->execute([$userId]);
$notifTime = $stmt->fetchColumn() ?: '08:00';

renderAppHeader('Leitura Bíblica');
renderPageHeader('Leitura Bíblica', 'Plano Anual');
?>

<!-- Import JSON Data -->
<script src="../assets/js/reading_plan_data.js"></script>

<!-- Custom Styles for this page -->
<style>
    /* Stats Cards */
    .stat-card {
        background: var(--bg-surface);
        border-radius: 16px;
        padding: 20px;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        box-shadow: var(--shadow-sm);
        border: 1px solid var(--border-color);
        transition: transform 0.2s;
    }
    .stat-card:hover { transform: translateY(-2px); }
    .stat-value { font-size: 1.75rem; font-weight: 800; color: var(--text-main); }
    .stat-label { font-size: 0.75rem; font-weight: 600; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.5px; margin-top: 4px; }
    
    /* Config Card */
    .config-card {
        background: linear-gradient(135deg, #f0f9ff 0%, #e0f2fe 100%);
        border: 1px solid #bae6fd;
    }

    /* Today Card */
    .today-card {
        background: var(--bg-surface);
        border-radius: 20px;
        box-shadow: var(--shadow-md);
        overflow: hidden;
        border: 1px solid var(--border-color);
        margin-bottom: 24px;
        position: relative;
    }
    .today-header {
        background: linear-gradient(to right, #ecfdf5, #fff);
        padding: 20px;
        border-bottom: 1px solid #d1fae5;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    .today-content { padding: 24px; }
    
    .verse-item-large {
        background: #f8fafc;
        border: 1px solid var(--border-color);
        padding: 12px 16px;
        border-radius: 12px;
        margin-bottom: 8px;
        font-weight: 500;
        color: var(--text-main);
        display: flex;
        align-items: center;
        gap: 12px;
    }
    .verse-item-large i { color: var(--primary); width: 18px; }

    /* Action Button */
    .btn-action-main {
        width: 100%;
        padding: 16px;
        border-radius: 14px;
        border: none;
        background: var(--primary);
        color: white;
        font-weight: 600;
        font-size: 1rem;
        cursor: pointer;
        display: flex; align-items: center; justify-content: center; gap: 10px;
        transition: background 0.2s;
        box-shadow: 0 4px 12px rgba(4, 120, 87, 0.2);
    }
    .btn-action-main:hover { background: var(--primary-hover); }
    .btn-action-main.completed { background: #d1fae5; color: #065f46; box-shadow: none; cursor: default; }

    /* Modal Styling (Overrides or adds to layout) */
    .modal-backdrop {
        position: fixed; top: 0; left: 0; width: 100%; height: 100%;
        background: rgba(0,0,0,0.5); backdrop-filter: blur(4px);
        z-index: 2000; display: none; align-items: center; justify-content: center;
    }
    .modal-backdrop.active { display: flex; animation: fadeIn 0.2s ease-out; }
    .modal-box {
        background: var(--bg-surface);
        width: 90%; max-width: 400px;
        border-radius: 20px;
        padding: 24px;
        box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1);
        transform: scale(0.95); opacity: 0; transition: all 0.2s;
    }
    .modal-backdrop.active .modal-box { transform: scale(1); opacity: 1; }
</style>

<div class="compact-container">
    
    <!-- KPI Row -->
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(140px, 1fr)); gap: 16px; margin-bottom: 24px;">
        <div class="stat-card">
            <div style="width: 40px; height: 40px; background: #dcfce7; border-radius: 10px; display: flex; align-items: center; justify-content: center; margin-bottom: 8px;">
                <i data-lucide="bar-chart-2" style="color: var(--primary);"></i>
            </div>
            <div class="stat-value"><?= $percentage ?>%</div>
            <div class="stat-label">Concluído</div>
        </div>

        <div class="stat-card" style="<?= $delay > 0 ? 'border-color: #fecaca;' : '' ?>">
            <div style="width: 40px; height: 40px; background: <?= $delay > 0 ? '#fee2e2' : '#dcfce7' ?>; border-radius: 10px; display: flex; align-items: center; justify-content: center; margin-bottom: 8px;">
                <i data-lucide="clock" style="color: <?= $delay > 0 ? '#ef4444' : '#10b981' ?>;"></i>
            </div>
            <div class="stat-value" style="color: <?= $delay > 0 ? '#ef4444' : 'var(--text-main)' ?>"><?= $delay ?></div>
            <div class="stat-label">Dias de Atraso</div>
        </div>

        <div class="stat-card config-card">
             <div class="stat-label" style="color: #0369a1; marginBottom: 8px;">Lembrete Diário</div>
             <form method="POST" id="form-notif" style="width: 100%;">
                <input type="hidden" name="action" value="save_settings">
                <input type="time" name="notification_time" value="<?= htmlspecialchars($notifTime) ?>" onchange="this.form.submit()" style="
                    background: white; border: 1px solid #bae6fd; color: #0284c7;
                    padding: 8px; border-radius: 8px; width: 100%; text-align: center; font-weight: 700;
                    cursor: pointer; font-family: inherit;
                ">
            </form>
        </div>
    </div>

    <!-- Main Active Card -->
    <div class="today-card">
        <div class="today-header">
            <div>
                <p style="margin:0; font-size: 0.8rem; font-weight: 600; color: var(--primary); text-transform: uppercase; letter-spacing: 1px;">Leitura de Hoje</p>
                <h2 style="margin: 4px 0 0; color: #1e293b;">Dia <?= $currentDay ?> de <?= date('F', mktime(0, 0, 0, $currentMonth, 10)) // Needs JS translation but ok for now ?></h2>
            </div>
            <div style="width: 48px; height: 48px; background: white; border-radius: 12px; border: 1px solid #d1fae5; display: flex; align-items: center; justify-content: center;">
                <i data-lucide="book-open" style="color: var(--primary);"></i>
            </div>
        </div>
        <div class="today-content">
            <div id="today-verses-list">
                <!-- Javascript will render verses here -->
                <div class="skeleton" style="height: 40px; margin-bottom: 8px;"></div>
                <div class="skeleton" style="height: 40px;"></div>
            </div>

            <div style="margin-top: 24px;">
                <button id="btn-mark-today" class="btn-action-main">
                    <i data-lucide="check"></i> Marcar Leitura Realizada
                </button>
                
                <div id="today-comment-preview" onclick="openCommentModal(<?= $currentMonth ?>, <?= $currentDay ?>)" style="
                    margin-top: 16px; padding: 12px; background: #f8fafc; border-radius: 12px; 
                    border: 1px dashed var(--border-color); color: var(--text-muted); font-size: 0.9rem;
                    cursor: pointer; text-align: center; transition: background 0.2s;
                ">
                    <i data-lucide="message-square" style="width: 16px; vertical-align: middle; margin-right: 6px;"></i>
                    <span id="comment-text-display">Adicionar anotação...</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Full Plan Accordion -->
    <div class="form-card" style="padding: 0; overflow: hidden;">
        <div style="padding: 16px; border-bottom: 1px solid var(--border-color); background: #f8fafc;">
            <h3 style="font-size: 1rem; color: var(--text-muted);">Plano Completo</h3>
        </div>
        <div id="months-container" style="padding: 16px;">
            <!-- JS Populated -->
        </div>
    </div>

</div>

<!-- MODAL FOR COMMENTS -->
<div id="modal-comment" class="modal-backdrop">
    <div class="modal-box">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px;">
            <h3 style="font-size: 1.25rem;">Minha Anotação</h3>
            <button onclick="closeCommentModal()" style="background:none; border:none; cursor:pointer; color: var(--text-muted);"><i data-lucide="x"></i></button>
        </div>
        
        <p id="modal-date-display" style="color: var(--text-muted); font-size: 0.9rem; margin-bottom: 16px;">Dia X</p>
        
        <form method="POST" id="form-modal-comment">
            <input type="hidden" name="action" value="mark_read">
            <input type="hidden" name="month" id="modal-input-month">
            <input type="hidden" name="day" id="modal-input-day">
            
            <textarea name="comment" id="modal-input-comment" placeholder="Escreva o que Deus falou com você..." style="
                width: 100%; height: 120px; padding: 12px; border-radius: 12px; border: 1px solid var(--border-color);
                font-family: inherit; font-size: 1rem; margin-bottom: 20px; resize: none; background: #f8fafc;
            "></textarea>
            
            <button type="submit" class="btn-action-main">
                Salvar Anotação
            </button>
        </form>
    </div>
</div>

<script>
// State
const completedMap = <?= json_encode($completedReadings) ?>;
const currentMonth = <?= $currentMonth ?>;
const currentDay = <?= $currentDay ?>;
const targetDayIndex = Math.min(currentDay, 25);

// --- Render Logic ---

function init() {
    renderToday();
    renderMonths();
    lucide.createIcons();
}

function getMonthName(m) {
    const names = ["", "Janeiro", "Fevereiro", "Março", "Abril", "Maio", "Junho", "Julho", "Agosto", "Setembro", "Outubro", "Novembro", "Dezembro"];
    return names[m];
}

function isRead(m, d) { return completedMap[m] && completedMap[m][d]; }

function renderToday() {
    const list = document.getElementById('today-verses-list');
    const displayDate = document.querySelector('.today-header h2');
    const btn = document.getElementById('btn-mark-today');
    const commentPrev = document.getElementById('comment-text-display');
    const commentPrevBox = document.getElementById('today-comment-preview');

    if (!bibleReadingPlan[currentMonth]) return;

    // Date
    displayDate.textContent = `Dia ${targetDayIndex} de ${getMonthName(currentMonth)}`;

    // Verses
    const verses = bibleReadingPlan[currentMonth][targetDayIndex - 1] || [];
    list.innerHTML = verses.map(v => 
        `<div class="verse-item-large"><i data-lucide="book" style="width:16px;"></i> ${v}</div>`
    ).join('');

    // Status
    const read = isRead(currentMonth, targetDayIndex);
    if (read) {
        btn.innerHTML = '<i data-lucide="check-circle"></i> Leitura Concluída!';
        btn.classList.add('completed');
        btn.onclick = null; // Disable main click, enable modal edit via comment box
        
        if (read.comment) {
            commentPrev.textContent = `"${read.comment}"`;
            commentPrev.style.color = 'var(--text-main)';
            commentPrev.style.fontStyle = 'italic';
        } else {
            commentPrev.textContent = "Adicionar anotação...";
        }
    } else {
        btn.innerHTML = '<i data-lucide="check"></i> Marcar como Lido';
        btn.classList.remove('completed');
        // When clicking mark as read, we just mark it. User can add comment later.
        btn.onclick = () => {
             // Submit immediate mark read via AJAX or Form?
             // Let's use the modal to confirm/add comment or just simple mark.
             // User wants "Mark as Read" to be simple.
             markSimple(currentMonth, targetDayIndex);
        };
    }
}

function markSimple(m, d) {
    // Simple AJAX mark
    const f = new FormData();
    f.append('action', 'mark_read'); f.append('month', m); f.append('day', d);
    fetch('leitura.php', { method: 'POST', body: f })
    .then(() => window.location.reload());
}

// --- Modal Logic ---

function openCommentModal(m, d) {
    const modal = document.getElementById('modal-comment');
    const display = document.getElementById('modal-date-display');
    const inputM = document.getElementById('modal-input-month');
    const inputD = document.getElementById('modal-input-day');
    const inputC = document.getElementById('modal-input-comment');
    
    // Set Data
    display.textContent = `Dia ${d} de ${getMonthName(m)} • ${bibleReadingPlan[m][d-1].join(', ')}`;
    inputM.value = m;
    inputD.value = d;
    
    // Fill existing comment if any
    const read = isRead(m, d);
    inputC.value = read ? (read.comment || '') : '';
    
    modal.classList.add('active');
}

function closeCommentModal() {
    document.getElementById('modal-comment').classList.remove('active');
}

// Close on outside click
document.getElementById('modal-comment').addEventListener('click', (e) => {
    if(e.target === document.getElementById('modal-comment')) closeCommentModal();
});


// --- Full List Logic (Accordion) ---

function renderMonths() {
    const container = document.getElementById('months-container');
    container.innerHTML = '';
    
    for (let m = 1; m <= 12; m++) {
        if (!bibleReadingPlan[m]) continue;
        
        let doneCount = 0;
        for(let d=1; d<=25; d++) if(isRead(m,d)) doneCount++;
        
        const isCurrent = m === currentMonth;
        
        // Month Header
        const head = document.createElement('div');
        head.style.cssText = 'display:flex; justify-content:space-between; align-items:center; padding: 12px; cursor: pointer; border-bottom: 1px solid var(--border-color);';
        if(isCurrent) head.style.background = '#f0fdf4';
        
        head.innerHTML = `
            <div style="font-weight: 600; color: ${isCurrent ? 'var(--primary)' : 'var(--text-main)'};">${getMonthName(m)}</div>
            <div style="display:flex; align-items:center; gap:8px;">
                <span style="font-size:0.75rem; color:var(--text-muted);">${doneCount}/25</span>
                <i data-lucide="${isCurrent ? 'chevron-down' : 'chevron-right'}" style="width:16px;"></i>
            </div>
        `;
        
        // Days List
        const body = document.createElement('div');
        body.style.display = isCurrent ? 'block' : 'none';
        
        head.onclick = () => {
             const hidden = body.style.display === 'none';
             body.style.display = hidden ? 'block' : 'none';
             lucide.createIcons();
        };
        
        bibleReadingPlan[m].forEach((verses, idx) => {
            const d = idx + 1;
            const read = isRead(m, d);
            
            const row = document.createElement('div');
            row.style.cssText = 'padding: 10px 12px; border-bottom: 1px solid var(--border-color); display: flex; align-items: center; justify-content: space-between; gap: 12px;';
            
            row.innerHTML = `
                <div style="flex:1;">
                    <div style="font-size: 0.9rem; font-weight: 600; color: ${read ? '#10b981' : 'var(--text-main)'}; display:flex; gap:6px; align-items:center;">
                        Dia ${d} ${read ? '<i data-lucide="check" style="width:14px;"></i>' : ''}
                    </div>
                    <div style="font-size: 0.8rem; color: var(--text-muted);">${verses.join(', ')}</div>
                </div>
                <button onclick="openCommentModal(${m}, ${d})" style="
                    background: ${read ? '#dcfce7' : '#f1f5f9'}; border:none; width:36px; height:36px; border-radius:10px; 
                    color: ${read ? '#15803d' : '#64748b'}; cursor:pointer; display:flex; align-items:center; justify-content:center;
                ">
                    <i data-lucide="${read ? 'edit-2' : 'plus'}" style="width:16px;"></i>
                </button>
            `;
            body.appendChild(row);
        });
        
        container.appendChild(head);
        container.appendChild(body);
    }
}

window.addEventListener('load', init);
</script>

<?php renderAppFooter(); ?>