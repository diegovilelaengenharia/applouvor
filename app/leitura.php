<?php
require_once '../includes/auth.php';
require_once '../includes/db.php';
require_once '../includes/layout.php';

checkLogin();

// ==========================================
// AUTO-MIGRATION (SELF-HEALING)
// ==========================================
// Verifica se a tabela existe, se não, cria.
try {
    $check = $pdo->query("SHOW TABLES LIKE 'reading_progress'");
    if ($check->rowCount() == 0) {
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
} catch (PDOException $e) {
    // Silently fail or log
    error_log("Migracao Leitura falhou: " . $e->getMessage());
}

// ==========================================
// LOGIC
// ==========================================
$userId = $_SESSION['user_id'];
$currentMonth = (int)date('n');
$currentDay = (int)date('j');
// Limita dia a 25 para lógica de atraso, mas usuário pode estar lendo dia 26 checkando atrasado?
// O plano tem 25 dias. Se hoje é dia 26, o esperado é ter lido 25.
$calcDay = min($currentDay, 25);
$expectedTotal = ($currentMonth - 1) * 25 + $calcDay;

// Process Form
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
        
        // Retorna status para AJAX ou recarrega
        if(!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
             echo json_encode(['success' => true]);
             exit;
        }
    }
    
    if ($action === 'save_settings') {
        $time = $_POST['notification_time'];
        $pdo->prepare("INSERT INTO user_settings (user_id, setting_key, setting_value) VALUES (?, 'notification_time', ?) ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)")
            ->execute([$userId, $time]);
        header("Location: leitura.php?success=1");
        exit;
    }
}

// Fetch Progress
$completedReadings = [];
$stmt = $pdo->prepare("SELECT * FROM reading_progress WHERE user_id = ?");
$stmt->execute([$userId]);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

$totalCompleted = count($rows);
$percentage = round(($totalCompleted / 300) * 100, 1); // 12 * 25 = 300 total
$delay = $expectedTotal - $totalCompleted;
$delay = max(0, $delay); // Não mostrar atraso negativo

// Map for quick lookup
foreach ($rows as $r) {
    $completedReadings[$r['month_num']][$r['day_num']] = $r;
}

// Fetch Settings
$stmt = $pdo->prepare("SELECT setting_value FROM user_settings WHERE user_id = ? AND setting_key = 'notification_time'");
$stmt->execute([$userId]);
$notifTime = $stmt->fetchColumn() ?: '08:00';

renderAppHeader('Leitura Bíblica');
?>

<!-- Import JSON Data -->
<script src="../assets/js/reading_plan_data.js"></script>

<div class="compact-container">
    
    <!-- Stats Cards -->
    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 24px;">
        <div class="form-card" style="margin:0; padding: 16px; text-align: center; border-left: 4px solid var(--primary);">
            <div style="font-size: 2rem; font-weight: 800; color: var(--primary);"><?= $percentage ?>%</div>
            <div style="font-size: 0.75rem; color: var(--text-secondary); font-weight: 600;">CONCLUÍDO</div>
        </div>
        <div class="form-card" style="margin:0; padding: 16px; text-align: center; border-left: 4px solid <?= $delay > 0 ? '#ef4444' : '#10b981' ?>;">
            <div style="font-size: 2rem; font-weight: 800; color: <?= $delay > 0 ? '#ef4444' : '#10b981' ?>;"><?= $delay ?></div>
            <div style="font-size: 0.75rem; color: var(--text-secondary); font-weight: 600;">DIAS DE ATRASO</div>
        </div>
    </div>

    <!-- Today's Reading (or Next Pending) -->
    <div id="today-reading-card" class="form-card" style="background: #0f172a; color: white; border: none;">
        <div style="display: flex; justify-content: space-between; align-items: start; margin-bottom: 16px;">
            <div>
                <div style="font-size: 0.8rem; opacity: 0.8; text-transform: uppercase; font-weight: 600;">Leitura de Hoje</div>
                <h2 style="font-size: 1.5rem; margin: 4px 0 0 0;" id="today-date-display">Dia <?= $currentDay ?>/<?= $currentMonth ?></h2>
            </div>
            <div style="background: rgba(255,255,255,0.1); padding: 8px; border-radius: 8px;">
                <i data-lucide="book-open" style="color:white;"></i>
            </div>
        </div>
        
        <div id="today-verses-list" style="display: flex; flex-direction: column; gap: 8px; margin-bottom: 20px;">
            <!-- JS Populated -->
            <div class="skeleton" style="height: 20px; background: rgba(255,255,255,0.1);"></div>
            <div class="skeleton" style="height: 20px; background: rgba(255,255,255,0.1);"></div>
        </div>

        <button id="btn-mark-today" class="ripple" style="
            width: 100%;
            padding: 14px;
            border-radius: 12px;
            border: none;
            background: var(--primary);
            color: white;
            font-weight: 600;
            display: flex; 
            align-items: center; 
            justify-content: center; 
            gap: 8px;
            font-size: 1rem;
        ">
            <i data-lucide="check-circle"></i>
            Marcar como Lido
        </button>
        
        <!-- Comment Area (Hidden until expanded or if exists) -->
        <div id="today-comment-box" style="margin-top: 16px; display: none;">
            <textarea id="today-comment" placeholder="Adicione uma anotação sobre a leitura de hoje..." style="
                width: 100%;
                background: rgba(255,255,255,0.1);
                border: 1px solid rgba(255,255,255,0.2);
                border-radius: 8px;
                padding: 12px;
                color: white;
                font-family: inherit;
                resize: vertical;
                min-height: 80px;
            "></textarea>
            <button onclick="saveTodayComment()" style="
                margin-top: 8px;
                background: rgba(255,255,255,0.2);
                border: none;
                padding: 8px 16px;
                border-radius: 6px;
                color: white;
                font-size: 0.85rem;
                font-weight: 600;
            ">Salvar Anotação</button>
        </div>
        <div id="today-comment-toggle" onclick="toggleCommentBox()" style="
            text-align: center; 
            margin-top: 12px; 
            font-size: 0.85rem; 
            opacity: 0.7; 
            cursor: pointer; 
            text-decoration: underline;
        ">Adicionar anotação</div>
    </div>

    <!-- Config Notification -->
    <div class="form-card">
        <div style="display: flex; justify-content: space-between; align-items: center;">
            <div style="display: flex; gap: 12px; align-items: center;">
                <div style="background: var(--bg-body); padding: 8px; border-radius: 8px;">
                    <i data-lucide="bell" style="width: 20px; color: var(--primary);"></i>
                </div>
                <div>
                    <h4 style="margin: 0; font-size: 0.95rem;">Lembrete Diário</h4>
                    <p style="margin: 0; font-size: 0.8rem; color: var(--text-secondary);">Receba um aviso para ler</p>
                </div>
            </div>
            <form method="POST" id="form-notif" style="display: flex; gap: 8px; align-items: center;">
                <input type="hidden" name="action" value="save_settings">
                <input type="time" name="notification_time" value="<?= htmlspecialchars($notifTime) ?>" onchange="this.form.submit()" style="
                    padding: 8px;
                    border: 1px solid var(--border-color);
                    border-radius: 8px;
                    background: var(--bg-body);
                    color: var(--text-main);
                ">
            </form>
        </div>
    </div>

    <!-- Full Plan Accordion -->
    <div style="margin-top: 24px;">
        <h3 style="font-size: 1.1rem; margin-bottom: 12px; padding-left: 4px;">Plano Completo</h3>
        
        <div id="months-container">
            <!-- JS will populate -->
        </div>
    </div>

</div>

<script>
// State
const userId = <?= $userId ?>;
const completedMap = <?= json_encode($completedReadings) ?>; // Obj: { 1: { 1: {completed_at:..., comment:...} } }
const currentMonth = <?= $currentMonth ?>;
const currentDay = <?= $currentDay ?>; // 1 to 31

// Helper to check if read
function isRead(m, d) {
    return completedMap[m] && completedMap[m][d];
}

// Logic to determine "Today's" View
// If today > 25, we could show "Catch up" or "Rest".
// Let's assume day index is min(currentDay, 25)
const targetDayIndex = Math.min(currentDay, 25);
const targetMonth = currentMonth;

function init() {
    renderTodayCard();
    renderFullList();
    
    // Check Notification Permission
    if ("Notification" in window && Notification.permission !== "granted") {
        Notification.requestPermission();
    }
}

function renderTodayCard() {
    const list = document.getElementById('today-verses-list');
    const dateDisplay = document.getElementById('today-date-display');
    const btn = document.getElementById('btn-mark-today');
    const commentBox = document.getElementById('today-comment-box');
    const commentField = document.getElementById('today-comment');
    const toggle = document.getElementById('today-comment-toggle');

    // Get Data
    if (!bibleReadingPlan[targetMonth]) {
        list.innerHTML = '<div>Plano não disponível para este mês.</div>';
        return;
    }
    
    // Arrays are 0-indexed, Days are 1-indexed in UI
    const todayVerses = bibleReadingPlan[targetMonth][targetDayIndex - 1]; 
    if (!todayVerses) {
        // Day > 25 or invalid
        list.innerHTML = '<div>Hoje é dia de descanso ou revisão! (Dia ' + currentDay + ')</div>';
        btn.style.display = 'none';
        return;
    }

    // Render Verses
    list.innerHTML = '';
    todayVerses.forEach(verse => {
        const d = document.createElement('div');
        d.className = 'verse-pill';
        d.style.cssText = 'background: rgba(255,255,255,0.15); padding: 8px 12px; border-radius: 8px; font-weight: 500; font-size: 0.95rem;';
        d.textContent = verse;
        list.appendChild(d);
    });

    dateDisplay.textContent = `Dia ${targetDayIndex} de ${getMonthName(targetMonth)}`;

    // Check status
    const read = isRead(targetMonth, targetDayIndex);
    if (read) {
        btn.innerHTML = '<i data-lucide="check-circle" style="width:20px;"></i> Leitura Concluída!';
        btn.style.background = '#10b981'; // Green
        btn.onclick = null;
        
        // Show comment if exists
        if (read.comment) {
            commentField.value = read.comment;
            commentBox.style.display = 'block';
            toggle.style.display = 'none';
        }
    } else {
        btn.innerHTML = '<i data-lucide="circle" style="width:20px;"></i> Marcar como Lido';
        btn.style.background = 'var(--primary)';
        btn.onclick = () => markRead(targetMonth, targetDayIndex, true);
    }
}

function toggleCommentBox() {
    document.getElementById('today-comment-box').style.display = 'block';
    document.getElementById('today-comment-toggle').style.display = 'none';
}

function saveTodayComment() {
    const val = document.getElementById('today-comment').value;
    markRead(targetMonth, targetDayIndex, false, val);
}

function markRead(m, d, reload = true, comment = null) {
    const formData = new FormData();
    formData.append('action', 'mark_read');
    formData.append('month', m);
    formData.append('day', d);
    if (comment !== null) formData.append('comment', comment);

    fetch('leitura.php', {
        method: 'POST',
        body: formData,
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
    })
    .then(res => res.json())
    .then(data => {
        if(reload) window.location.reload();
        else {
             alert('Salvo!'); 
             // Ideally update local state without reload
        }
    })
    .catch(err => console.error(err));
}

function renderFullList() {
    const container = document.getElementById('months-container');
    container.innerHTML = '';

    for (let m = 1; m <= 12; m++) {
        const monthData = bibleReadingPlan[m];
        if (!monthData) continue;

        // Month Header
        const monthHeader = document.createElement('div');
        monthHeader.style.cssText = 'padding: 12px; background: var(--bg-surface); border: 1px solid var(--border-color); border-radius: 12px; margin-bottom: 8px; cursor: pointer; display: flex; justify-content: space-between; align-items: center;';
        
        // Calculate progress for month
        let completedCount = 0;
        for(let d=1; d<=25; d++) {
             if(isRead(m, d)) completedCount++;
        }
        const isCurrent = m === currentMonth;
        const icon = isCurrent ? 'chevron-down' : 'chevron-right';
        
        monthHeader.innerHTML = `
            <div style="display:flex; gap:12px; align-items:center;">
                <div style="font-weight: 700;">${getMonthName(m)}</div>
                <div style="font-size: 0.75rem; background: var(--bg-body); padding: 2px 8px; border-radius: 12px; color: var(--text-muted);">${completedCount}/25</div>
            </div>
            <i data-lucide="${icon}" style="width: 18px; color: var(--text-muted);"></i>
        `;

        // Days Container
        const daysContainer = document.createElement('div');
        daysContainer.style.display = isCurrent ? 'block' : 'none'; // Only open current month default
        daysContainer.style.padding = '0 0 16px 12px';
        daysContainer.style.borderLeft = '2px solid var(--border-color)';
        daysContainer.style.marginLeft = '12px';

        monthHeader.onclick = () => {
            const isHidden = daysContainer.style.display === 'none';
            daysContainer.style.display = isHidden ? 'block' : 'none';
            // Update icon needs traverse or simple toggle logic
            lucide.createIcons();
        };

        monthData.forEach((verses, idx) => {
            const dayNum = idx + 1;
            const read = isRead(m, dayNum);
            
            const dayRow = document.createElement('div');
            dayRow.style.cssText = 'display: flex; gap: 12px; align-items: flex-start; margin-top: 12px; position: relative;';
            
            const checkColor = read ? '#10b981' : 'var(--border-color)';
            const checkIcon = read ? 'check-circle' : 'circle';
            const textColor = read ? 'var(--text-muted)' : 'var(--text-main)';

            dayRow.innerHTML = `
                <div onclick="markRead(${m}, ${dayNum}, true)" style="cursor: pointer; padding-top: 2px;">
                    <i data-lucide="${checkIcon}" style="width: 20px; color: ${checkColor};"></i>
                </div>
                <div style="flex: 1;">
                    <div style="font-weight: 600; font-size: 0.9rem; color: ${textColor}; display: flex; justify-content:space-between;">
                        <span>Dia ${dayNum}</span>
                        ${read && read.comment ? '<i data-lucide="message-square" style="width: 14px; color: var(--text-muted);"></i>' : ''}
                    </div>
                    <div style="font-size: 0.8rem; color: var(--text-muted); line-height: 1.4;">
                        ${verses.join(', ')}
                    </div>
                    ${read && read.comment ? `<div style="font-size: 0.8rem; background: var(--bg-body); padding: 6px; border-radius: 6px; margin-top: 4px; color: var(--text-main);">"${read.comment}"</div>` : ''}
                </div>
            `;
            daysContainer.appendChild(dayRow);
        });

        container.appendChild(monthHeader);
        container.appendChild(daysContainer);
    }
    lucide.createIcons();
}

function getMonthName(m) {
    const names = ["", "Janeiro", "Fevereiro", "Março", "Abril", "Maio", "Junho", "Julho", "Agosto", "Setembro", "Outubro", "Novembro", "Dezembro"];
    return names[m];
}

// Run
window.addEventListener('load', init);

// Register Service Worker Notification if needed (Optional for now)
</script>

<?php renderAppFooter(); ?>
