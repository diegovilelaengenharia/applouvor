<?php
require_once '../src/helpers/auth.php';
require_once '../src/config/db.php';
require_once '../src/layout/layout.php';
require_once '../src/helpers/reading_plan.php';

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
        
        $versesList = $bibleReadingPlan[$m][$d - 1] ?? [];
        $versesReadJson = json_encode(array_keys($versesList));
        
        try {
            $stmt = $pdo->prepare("
                INSERT INTO reading_progress (user_id, month_num, day_num, verses_read, comment, completed_at)
                VALUES (?, ?, ?, ?, ?, NOW())
                ON DUPLICATE KEY UPDATE comment = VALUES(comment), verses_read = VALUES(verses_read), completed_at = NOW()
            ");
            $stmt->execute([$userId, $m, $d, $versesReadJson, $comment]);
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

    if ($action === 'catch_up_today') {
        try {
            // Marcar todos os dias anteriores e de hoje como concluídos se não tiverem sido marcados
            $stmtCheck = $pdo->prepare("SELECT month_num, day_num FROM reading_progress WHERE user_id = ?");
            $stmtCheck->execute([$userId]);
            $completed = [];
            while ($row = $stmtCheck->fetch(PDO::FETCH_ASSOC)) {
                $completed[$row['month_num']][$row['day_num']] = true;
            }

            $stmtInsert = $pdo->prepare("
                INSERT IGNORE INTO reading_progress (user_id, month_num, day_num, verses_read, comment, completed_at)
                VALUES (?, ?, ?, ?, 'Ajuste de leitura rápido', NOW())
            ");

            for ($m_loop = 1; $m_loop <= $currentMonth; $m_loop++) {
                $maxDay = ($m_loop === $currentMonth) ? $calcDay : 25;
                for ($d_loop = 1; $d_loop <= $maxDay; $d_loop++) {
                    if (!isset($completed[$m_loop][$d_loop])) {
                        $versesList = $bibleReadingPlan[$m_loop][$d_loop - 1] ?? [];
                        $versesReadJson = json_encode(array_keys($versesList));
                        $stmtInsert->execute([$userId, $m_loop, $d_loop, $versesReadJson]);
                    }
                }
            }
            header("Location: leitura.php?success=catchup");
            exit;
        } catch (Exception $e) {
            error_log("Catch up failed: " . $e->getMessage());
        }
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

<div class="max-w-md mx-auto px-4 py-6 pb-24 space-y-6">
    
    <!-- Stats Cards (Bento Grid) -->
    <div class="grid grid-cols-2 gap-4">
        <div class="bg-surface border border-surface-container-highest rounded-2xl p-5 text-center shadow-sm border-l-4 border-l-primary transition-all duration-300 hover:scale-[1.02] hover:shadow-md">
            <div class="text-3xl font-extrabold text-primary font-outfit"><?= $percentage ?>%</div>
            <div class="text-[10px] text-muted font-bold tracking-wider uppercase mt-1">Concluído</div>
        </div>
        <div class="bg-surface border border-surface-container-highest rounded-2xl p-5 text-center shadow-sm border-l-4 <?= $delay > 0 ? 'border-l-error' : 'border-l-success' ?> transition-all duration-300 hover:scale-[1.02] hover:shadow-md">
            <div class="text-3xl font-extrabold <?= $delay > 0 ? 'text-error' : 'text-success' ?> font-outfit"><?= $delay ?></div>
            <div class="text-[10px] text-muted font-bold tracking-wider uppercase mt-1">Dias de Atraso</div>
        </div>
        
        <?php if ($delay > 0): ?>
            <!-- Botão de Ajuste Rápido de Atraso -->
            <form method="POST" class="col-span-2 -mt-1">
                <input type="hidden" name="action" value="catch_up_today">
                <button type="submit" class="w-full flex items-center justify-center gap-2 py-3 px-4 bg-red-500/10 text-red-500 border border-red-500/20 rounded-xl text-xs font-bold transition-all duration-300 hover:bg-red-500/15 active:scale-[0.98]">
                    <i data-lucide="zap" class="w-4 h-4"></i>
                    Ajustar Leitura para Hoje
                </button>
            </form>
        <?php endif; ?>
    </div>

    <!-- Today's Reading (or Next Pending) -->
    <div id="today-reading-card" class="bg-gradient-to-br from-primary to-blue-700 text-white rounded-3xl p-6 shadow-lg border border-white/10 transition-all duration-300 hover:shadow-xl relative overflow-hidden">
        <!-- Decorativo sutil de fundo para dar sensação premium -->
        <div class="absolute -right-10 -top-10 w-32 h-32 bg-white/5 rounded-full blur-xl pointer-events-none"></div>
        
        <div class="flex justify-between items-start mb-5 relative z-10">
            <div>
                <div class="text-[10px] opacity-80 uppercase font-bold tracking-widest">Leitura de Hoje</div>
                <h2 class="text-2xl font-extrabold tracking-tight mt-1 font-outfit" id="today-date-display">Dia <?= $currentDay ?>/<?= $currentMonth ?></h2>
            </div>
            <div class="bg-white/15 p-2.5 rounded-xl border border-white/20 flex items-center justify-center">
                <i data-lucide="book-open" class="text-white w-5 h-5"></i>
            </div>
        </div>
        
        <div id="today-verses-list" class="flex flex-col gap-2.5 mb-5 relative z-10">
            <!-- JS Populated -->
            <div class="h-6 bg-white/10 rounded-lg animate-pulse"></div>
            <div class="h-6 bg-white/10 rounded-lg animate-pulse w-3/4"></div>
        </div>

        <button id="btn-mark-today" class="w-full flex items-center justify-center gap-2 py-3.5 px-6 rounded-xl font-bold transition-all duration-300 shadow-md active:scale-[0.98] relative z-10">
            <i data-lucide="check-circle" class="w-5 h-5"></i>
            Marcar como Lido
        </button>
        
        <!-- Comment Area -->
        <div id="today-comment-box" class="mt-5 space-y-3 hidden relative z-10">
            <textarea id="today-comment" placeholder="Adicione uma anotação sobre a leitura de hoje..." class="w-full bg-white/10 border border-white/20 rounded-xl p-3.5 text-white placeholder-white/60 text-sm focus:outline-none focus:ring-2 focus:ring-white/30 resize-y min-h-[80px] transition-all"></textarea>
            <button onclick="saveTodayComment()" class="bg-white/20 border border-white/30 py-2 px-4 rounded-xl text-white text-xs font-semibold hover:bg-white/30 active:scale-[0.97] transition-all">Salvar Anotação</button>
        </div>
        <div id="today-comment-toggle" onclick="toggleCommentBox()" class="text-center mt-4 text-xs opacity-80 hover:opacity-100 cursor-pointer underline font-medium tracking-wide relative z-10">Adicionar anotação</div>
    </div>

    <!-- Config Notification -->
    <div class="bg-surface border border-surface-container-highest rounded-2xl p-5 shadow-sm transition-all duration-300 hover:shadow-md">
        <div class="flex justify-between items-center">
            <div class="flex gap-3.5 items-center">
                <div class="bg-surface-container-lowest p-2.5 rounded-xl border border-surface-container-highest flex items-center justify-center">
                    <i data-lucide="bell" class="w-5 h-5 text-primary"></i>
                </div>
                <div>
                    <h4 class="text-sm font-bold text-surface-on-surface font-outfit">Lembrete Diário</h4>
                    <p class="text-xs text-muted mt-0.5">Receba um aviso para ler</p>
                </div>
            </div>
            <form method="POST" id="form-notif" class="flex gap-2 items-center">
                <input type="hidden" name="action" value="save_settings">
                <input type="time" name="notification_time" value="<?= htmlspecialchars($notifTime) ?>" onchange="this.form.submit()" class="py-2 px-3 border border-surface-container-highest rounded-xl bg-surface-container-lowest text-surface-on-surface text-sm font-semibold focus:outline-none focus:ring-2 focus:ring-primary/20">
            </form>
        </div>
    </div>

    <!-- Full Plan Accordion -->
    <div class="space-y-3">
        <h3 class="text-base font-bold text-surface-on-surface pl-1 font-outfit">Plano Completo</h3>
        
        <div id="months-container" class="space-y-2.5">
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
        list.innerHTML = '<div class="text-sm opacity-80 text-center py-4">Plano não disponível para este mês.</div>';
        return;
    }
    
    const todayVerses = bibleReadingPlan[targetMonth][targetDayIndex - 1]; 
    if (!todayVerses) {
        list.innerHTML = '<div class="text-sm opacity-90 text-center py-4 font-medium">Hoje é dia de descanso ou revisão! (Dia ' + currentDay + ')</div>';
        btn.style.display = 'none';
        return;
    }

    // Render Verses
    list.innerHTML = '';
    todayVerses.forEach(verse => {
        const d = document.createElement('div');
        d.className = 'bg-white/10 hover:bg-white/15 border border-white/10 rounded-xl p-3 font-semibold text-sm transition-all duration-200';
        d.textContent = verse;
        list.appendChild(d);
    });

    dateDisplay.textContent = `Dia ${targetDayIndex} de ${getMonthName(targetMonth)}`;

    // Check status
    const read = isRead(targetMonth, targetDayIndex);
    if (read) {
        btn.innerHTML = '<i data-lucide="check-circle" class="w-5 h-5"></i> Leitura Concluída!';
        btn.className = 'w-full flex items-center justify-center gap-2 py-3.5 px-6 rounded-xl font-bold bg-white/20 text-white border border-white/30 cursor-default';
        btn.onclick = null;
        
        if (read.comment) {
            commentField.value = read.comment;
            commentBox.style.display = 'block';
            toggle.style.display = 'none';
        }
    } else {
        btn.innerHTML = '<i data-lucide="circle" class="w-5 h-5"></i> Marcar como Lido';
        btn.className = 'w-full flex items-center justify-center gap-2 py-3.5 px-6 rounded-xl font-bold bg-white text-primary hover:bg-white/90 active:scale-[0.98] transition-all cursor-pointer';
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
             alert('Anotação salva com sucesso!'); 
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
        monthHeader.className = 'p-4 bg-surface border border-surface-container-highest rounded-2xl cursor-pointer flex justify-between items-center shadow-sm hover:shadow-md hover:scale-[1.01] transition-all duration-300';
        
        let completedCount = 0;
        for(let d=1; d<=25; d++) {
             if(isRead(m, d)) completedCount++;
        }
        const isCurrent = m === currentMonth;
        const icon = isCurrent ? 'chevron-down' : 'chevron-right';
        
        monthHeader.innerHTML = `
            <div class="flex gap-3 items-center">
                <div class="font-bold text-sm text-surface-on-surface font-outfit">${getMonthName(m)}</div>
                <div class="text-[10px] bg-surface-container-lowest px-2 py-0.5 rounded-full border border-surface-container-highest text-muted font-bold">${completedCount}/25</div>
            </div>
            <i data-lucide="${icon}" class="w-4 h-4 text-muted"></i>
        `;

        // Days Container
        const daysContainer = document.createElement('div');
        daysContainer.style.display = isCurrent ? 'block' : 'none';
        daysContainer.className = 'pl-4 border-l-2 border-l-surface-container-highest ml-3.5 pb-4 space-y-4';

        monthHeader.onclick = () => {
            const isHidden = daysContainer.style.display === 'none';
            daysContainer.style.display = isHidden ? 'block' : 'none';
            // Simple chevron toggle logic
            const iconEl = monthHeader.querySelector('i');
            if (isHidden) {
                iconEl.setAttribute('data-lucide', 'chevron-down');
            } else {
                iconEl.setAttribute('data-lucide', 'chevron-right');
            }
            lucide.createIcons();
        };

        monthData.forEach((verses, idx) => {
            const dayNum = idx + 1;
            const read = isRead(m, dayNum);
            
            const dayRow = document.createElement('div');
            dayRow.className = 'flex gap-3.5 items-start relative mt-4 transition-all duration-200';
            
            const checkColor = read ? 'text-success' : 'text-muted';
            const checkIcon = read ? 'check-circle' : 'circle';
            const textColor = read ? 'text-muted line-through font-medium' : 'text-surface-on-surface font-bold';

            dayRow.innerHTML = `
                <div onclick="markRead(${m}, ${dayNum}, true)" class="cursor-pointer pt-0.5 select-none transition-transform active:scale-[0.85] hover:scale-[1.1]">
                    <i data-lucide="${checkIcon}" class="w-5 h-5 ${checkColor}"></i>
                </div>
                <div class="flex-1">
                    <div class="text-sm ${textColor} flex justify-between items-center font-outfit">
                        <span>Dia ${dayNum}</span>
                        ${read && read.comment ? '<i data-lucide="message-square" class="w-3.5 h-3.5 text-muted"></i>' : ''}
                    </div>
                    <div class="text-xs text-muted leading-relaxed mt-0.5">
                        ${verses.join(', ')}
                    </div>
                    ${read && read.comment ? `<div class="text-xs bg-surface-container-lowest border border-surface-container-highest p-3 rounded-xl mt-2 text-surface-on-surface italic">"${read.comment}"</div>` : ''}
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

window.addEventListener('load', init);
</script>

<?php renderAppFooter(); ?>
