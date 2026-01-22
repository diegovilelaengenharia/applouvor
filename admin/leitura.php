<?php
// admin/leitura.php
require_once '../includes/auth.php';
// require_once '../includes/db.php'; // Já incluído em layout.php geralmente, mas ok garantir
require_once '../includes/layout.php';

checkLogin(); 

// ==========================================
// 1. BACKEND LOGIC
// ==========================================
$userId = $_SESSION['user_id'];
$now = new DateTime();

// --- 1.1 Action Handler (POST) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
    $action = $_POST['action'] ?? '';

    // SAVE SETTINGS
    if ($action === 'save_settings') {
        $newStart = $_POST['start_date'];
        try {
            // Save to user_settings
            $pdo->prepare("INSERT INTO user_settings (user_id, setting_key, setting_value) VALUES (?, 'reading_plan_start_date', ?) ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)")
                ->execute([$userId, $newStart]);
            echo json_encode(['success' => true]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
        exit;
    }

    // SAVE PROGRESS (Granular or Complete)
    if ($action === 'save_progress') {
        $m = (int)$_POST['month'];
        $d = (int)$_POST['day'];
        $comment = $_POST['comment'] ?? null;
        $versesJson = $_POST['verses'] ?? '[]'; 
        
        try {
            if ($comment !== null) {
                $sql = "INSERT INTO reading_progress (user_id, month_num, day_num, verses_read, comment, completed_at)
                        VALUES (?, ?, ?, ?, ?, NOW())
                        ON DUPLICATE KEY UPDATE 
                            verses_read = VALUES(verses_read), 
                            comment = VALUES(comment),
                            completed_at = NOW()";
                $params = [$userId, $m, $d, $versesJson, $comment];
            } else {
                 $sql = "INSERT INTO reading_progress (user_id, month_num, day_num, verses_read, completed_at)
                    VALUES (?, ?, ?, ?, NOW())
                    ON DUPLICATE KEY UPDATE 
                        verses_read = VALUES(verses_read),
                        completed_at = NOW()";
                $params = [$userId, $m, $d, $versesJson];
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

// --- 1.2 Fetch Settings ---
$stmt = $pdo->prepare("SELECT setting_key, setting_value FROM user_settings WHERE user_id = ?");
$stmt->execute([$userId]);
$settings = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
$startDateStr = $settings['reading_plan_start_date'] ?? date('Y-01-01');

// --- 1.3 Calculate Plan Day ---
$start = new DateTime($startDateStr);
$start->setTime(0, 0, 0); $now->setTime(0, 0, 0);
$diff = $start->diff($now);
$daysPassed = $diff->invert ? -1 * $diff->days : $diff->days;
$planDayIndex = max(1, $daysPassed + 1);

// Convert Index (1..300) to Month/Day logic
$currentPlanMonth = floor(($planDayIndex - 1) / 25) + 1;
$currentPlanDay = (($planDayIndex - 1) % 25) + 1;
if($currentPlanMonth > 12) { $currentPlanMonth = 12; $currentPlanDay = 25; }

// --- 1.4 Fetch User Progress State ---
$stmt = $pdo->prepare("
    SELECT month_num, day_num, verses_read, comment, completed_at 
    FROM reading_progress 
    WHERE user_id = ? 
    ORDER BY completed_at DESC
");
$stmt->execute([$userId]);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

$progressMap = [];
$totalChaptersRead = 0;
// Structure for Report: Array of objects
$reportData = [];

foreach($rows as $r) {
    $verses = json_decode($r['verses_read'] ?? '[]', true);
    if (!is_array($verses)) $verses = [];
    
    if (count($verses) > 0 || !empty($r['completed_at'])) {
        $totalChaptersRead++;
    }

    $key = "{$r['month_num']}_{$r['day_num']}";
    $progressMap[$key] = [
        'verses' => $verses,
        'comment' => $r['comment'],
        'date' => $r['completed_at']
    ];
    
    // Add to report if has content
    if (count($verses) > 0 || !empty($r['comment'])) {
        $reportData[] = [
            'm' => (int)$r['month_num'],
            'd' => (int)$r['day_num'],
            'date' => $r['completed_at'],
            'comment' => $r['comment'],
            'verses_count' => count($verses)
        ];
    }
}

$completionPercent = min(100, round(($totalChaptersRead / 300) * 100));

// --- 1.5 Render System Header ---
renderAppHeader('Leitura Bíblica'); // Standard Header (with Avatar, Menu, Config Button)
?>

<!-- ========================================== -->
<!-- 2. FRONTEND RESOURCES -->
<!-- ========================================== -->
<script src="../assets/js/reading_plan_data.js?v=<?= time() ?>"></script>
<script src="https://unpkg.com/lucide@latest"></script>

<style>
    :root {
        --primary: #6366f1;         --primary-soft: #e0e7ff; 
        --success: #10b981;         --success-soft: #d1fae5;
        --warning: #f59e0b;         --warning-soft: #fef3c7;
        --surface: #ffffff;         --bg: #f8fafc;
        --text: #1e293b;            --text-light: #64748b;
        --border: #e2e8f0;
    }
    body { background-color: var(--bg); color: var(--text); padding-bottom: 80px; }
    
    /* Calendar Strip */
    .cal-strip {
        display: flex; gap: 8px; overflow-x: auto; padding: 12px 16px;
        background: var(--surface); border-bottom: 1px solid var(--border);
        scrollbar-width: none;
    }
    .cal-strip::-webkit-scrollbar { display: none; }
    
    .cal-item {
        min-width: 60px; height: 72px; border-radius: 12px;
        background: var(--bg); border: 2px solid transparent; 
        display: flex; flex-direction: column; align-items: center; justify-content: center;
        flex-shrink: 0; cursor: pointer; transition: all 0.2s;
    }
    .cal-item.active { background: var(--surface); border-color: var(--primary); box-shadow: 0 4px 12px rgba(99,99,241,0.2); }
    .cal-item.active .cal-num { color: var(--primary); }
    .cal-item.done { background: var(--success-soft); border-color: #a7f3d0; }
    .cal-item.done .cal-num { color: #047857; }
    .cal-item.partial { background: var(--warning-soft); border-color: #fde68a; }
    .cal-item.partial .cal-num { color: #b45309; }
    
    .cal-month { font-size: 0.7rem; font-weight: 700; color: var(--text-light); text-transform: uppercase; }
    .cal-num { font-size: 1.2rem; font-weight: 800; }

    /* Main Area */
    .main-area { max-width: 800px; margin: 0 auto; padding: 20px 16px; }

    /* Verses */
    .verse-card {
        background: var(--surface); border: 1px solid var(--border); border-radius: 16px; padding: 16px; margin-bottom: 12px;
        display: flex; align-items: center; justify-content: space-between; cursor: pointer; transition: all 0.1s;
    }
    .verse-card:active { transform: scale(0.99); }
    .verse-card.read { background: #f0fdf4; border-color: #bbf7d0; }
    
    .check-icon {
        width: 24px; height: 24px; border-radius: 50%; border: 2px solid var(--border);
        color: transparent; display: flex; align-items: center; justify-content: center; margin-right: 12px;
    }
    .verse-card.read .check-icon { background: var(--success); border-color: var(--success); color: white; }
    .btn-read-link {
        background: linear-gradient(135deg, #10b981 0%, #059669 100%); color: white; padding: 8px 16px;
        border-radius: 20px; text-decoration: none; font-weight: 700; font-size: 0.75rem; display: flex; align-items: center; gap: 6px;
    }
    
    /* Bottom Bar */
    .bottom-bar {
        position: fixed; bottom: 0; left: 0; right: 0; background: rgba(255,255,255,0.9); backdrop-filter: blur(12px);
        border-top: 1px solid var(--border); padding: 12px 16px 20px 16px; z-index: 100;
        display: grid; grid-template-columns: 1fr 1fr; gap: 12px; max-width: 800px; margin: 0 auto;
    }
    @media (min-width: 1024px) { .bottom-bar { left: 280px; } }
    
    .action-btn {
        background: var(--surface); border: 1px solid var(--border); padding: 12px; border-radius: 12px;
        display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 6px; cursor: pointer;
    }
    .action-btn span { font-size: 0.8rem; font-weight: 600; color: var(--text); }
    .icon-box { width: 32px; height: 32px; border-radius: 10px; display: flex; align-items: center; justify-content: center; }
    .icon-box.purple { background: #f3e8ff; color: #9333ea; }
    .icon-box.blue { background: #e0f2fe; color: #0284c7; }

    /* Toast */
    .auto-save-feedback {
        position: fixed; top: 90px; left: 50%; transform: translateX(-50%);
        background: var(--text); color: white; padding: 8px 16px; border-radius: 20px; font-size: 0.8rem;
        z-index: 2000; opacity: 0; transition: opacity 0.3s; pointer-events: none; display: flex; align-items: center; gap: 8px;
    }
    .auto-save-feedback.show { opacity: 1; }

    /* Status Badge */
    .status-badge {
        font-size: 0.75rem; font-weight: 700; padding: 4px 10px; border-radius: 20px; text-transform: uppercase; letter-spacing: 0.5px;
    }
    .status-badge.success { background: #dcfce7; color: #166534; }
    .status-badge.pending { background: #ffedd5; color: #9a3412; }

    /* NEW: Config Modal Styles */
    .config-fullscreen {
        position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: var(--bg); z-index: 5000;
        overflow-y: auto; display: none; flex-direction: column;
    }
    .config-header {
        background: var(--surface); padding: 16px 20px; border-bottom: 1px solid var(--border);
        display: flex; justify-content: space-between; align-items: center;
    }
    .config-tabs {
        display: flex; background: var(--surface); border-bottom: 1px solid var(--border); padding: 0 20px;
    }
    .tab-btn {
        padding: 16px 20px; font-weight: 600; color: var(--text-light); border-bottom: 2px solid transparent; cursor: pointer;
    }
    .tab-btn.active { color: var(--primary); border-bottom-color: var(--primary); }
    
    .config-content { padding: 20px; max-width: 800px; margin: 0 auto; width: 100%; }
    
    .report-item {
        background: var(--surface); border-radius: 12px; padding: 16px; margin-bottom: 12px; border: 1px solid var(--border);
    }
    .report-date { font-size: 0.75rem; color: var(--text-light); font-weight: 600; text-transform: uppercase; margin-bottom: 4px; }
    .report-title { font-weight: 700; color: var(--text); font-size: 1rem; margin-bottom: 8px; display: flex; align-items: center; gap: 8px; }
    .report-comment { background: #f8fafc; padding: 12px; border-radius: 8px; font-size: 0.9rem; color: #475569; font-style: italic; border-left: 3px solid var(--primary); }
</style>

<!-- ========================================== -->
<!-- 3. MAIN UI -->
<!-- ========================================== -->

<!-- Progress Header (Below Standard Header) -->
<div style="background: var(--bg-surface); border-bottom: 1px solid var(--border); padding: 16px 20px;">
    <div style="display: flex; justify-content: space-between; align-items: end; margin-bottom: 10px;">
        <div>
            <span style="font-size:0.7rem; text-transform:uppercase; color:var(--text-light); font-weight:700; letter-spacing:0.5px;">Seu Progresso Global</span>
            <div style="color:var(--text); font-weight:700; font-size:1.1rem; line-height:1.2;">
                <span style="color:var(--primary);"><?= $totalChaptersRead ?></span> / 300
                <span style="color:var(--text-light); font-size:0.9rem; font-weight:400;">(<?= $completionPercent ?>%)</span>
            </div>
        </div>
    </div>
    <div style="height: 6px; background: var(--bg); width: 100%; border-radius: 3px; overflow: hidden;">
        <div style="height: 100%; background: linear-gradient(90deg, var(--primary), var(--secondary)); width: <?= $completionPercent ?>%;"></div>
    </div>
</div>

<div class="cal-strip" id="calendar-strip">
    <!-- Rendered via JS -->
</div>

<div class="main-area">
    <!-- Day Header Flex Container -->
    <div style="margin-bottom: 24px;">
        <span style="font-size:0.75rem; text-transform:uppercase; color:var(--text-light); font-weight:700; display:block; margin-bottom:4px;">Leitura de Hoje</span>
        <div style="display: flex; align-items: center; justify-content: space-between; gap: 12px;">
            <h1 id="day-title" style="font-size:1.75rem; margin:0; color: var(--text); line-height:1;">Carregando...</h1>
            <span id="status-badge-container" style="flex-shrink: 0;"></span>
        </div>
    </div>
    <div id="verses-list"></div>
</div>

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

<div id="save-toast" class="auto-save-feedback"><i data-lucide="check" width="14"></i> Salvo automaticamente</div>

<!-- ========================================== -->
<!-- 4. MODALS (Modern Redesign) -->
<!-- ========================================== -->

<!-- Note Modal -->
<div id="modal-note" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.6); backdrop-filter: blur(4px); z-index:2000; align-items:center; justify-content:center; animation: fadeIn 0.2s;">
    <div style="background: white; width: 95%; max-width: 600px; border-radius: 24px; box-shadow: 0 20px 50px -10px rgba(0,0,0,0.25); display: flex; flex-direction: column; overflow: hidden; animation: scaleUp 0.25s cubic-bezier(0.16, 1, 0.3, 1);">
        <div style="background: #fff7ed; padding: 20px 24px; border-bottom: 1px solid #fed7aa; display: flex; justify-content: space-between; align-items: center;">
            <h3 style="margin: 0; color: #c2410c; font-size: 1.1rem; display: flex; align-items: center; gap: 8px;"><i data-lucide="pen-line" width="18"></i> Minha Anotação</h3>
            <button onclick="document.getElementById('modal-note').style.display='none'" style="border: none; background: none; color: #9a3412; cursor: pointer;"><i data-lucide="x" width="20"></i></button>
        </div>
        <div style="padding: 24px;">
            <label style="display: block; font-size: 0.85rem; font-weight: 700; color: #475569; margin-bottom: 8px;">O que Deus falou com você?</label>
            <textarea id="note-input" style="width: 100%; min-height: 200px; padding: 16px; border: 1px solid #e2e8f0; border-radius: 12px; font-family: 'Inter', sans-serif; font-size: 0.95rem; line-height: 1.6; color: #334155; outline: none; background: #f8fafc;" placeholder="Escreva aqui suas reflexões..."></textarea>
            <div style="margin-top: 12px; font-size: 0.75rem; color: #94a3b8; text-align: right;">Suas anotações são privadas.</div>
        </div>
        <div style="padding: 16px 24px; background: #fff; border-top: 1px solid #f1f5f9; display: flex; justify-content: flex-end; gap: 12px;">
            <button onclick="document.getElementById('modal-note').style.display='none'" style="padding: 12px 20px; border: 1px solid #e2e8f0; background: white; color: #64748b; border-radius: 12px; font-weight: 600; cursor: pointer;">Cancelar</button>
            <button onclick="saveNote()" style="padding: 12px 24px; border: none; background: #f97316; color: white; border-radius: 12px; font-weight: 700; cursor: pointer; display: flex; align-items: center; gap: 8px;"><i data-lucide="save" width="16"></i> Salvar</button>
        </div>
    </div>
</div>

<!-- FULL CONFIG MODAL (Refined Design) -->
<div id="modal-config" class="config-fullscreen" style="background: var(--bg-body);">
    <div class="config-header" style="background: var(--bg-surface);">
        <h2 style="font-size: 1.25rem; font-weight:700;">Configurações & Diário</h2>
        <button onclick="document.getElementById('modal-config').style.display='none'" style="border:none; background:none; cursor:pointer; padding:8px; margin-right:-8px;"><i data-lucide="x"></i></button>
    </div>
    <div class="config-tabs" style="background: var(--bg-surface);">
        <div class="tab-btn active" onclick="switchTab('general')" id="tab-general">Geral</div>
        <div class="tab-btn" onclick="switchTab('diary')" id="tab-diary">Meu Diário</div>
    </div>
    
    <div id="content-general" class="config-content">
        <div class="report-item" style="box-shadow: 0 1px 3px rgba(0,0,0,0.05); border:1px solid var(--border);">
            <div style="display:flex; gap:12px; align-items:center; margin-bottom:16px;">
                 <div style="width:40px; height:40px; background:var(--primary-soft); border-radius:8px; display:flex; align-items:center; justify-content:center; color:var(--primary);"><i data-lucide="calendar"></i></div>
                 <div>
                    <h4 style="margin:0;">Meu Plano</h4>
                    <span style="font-size:0.8rem; color:var(--text-light);">Ajuste o cronograma de leitura</span>
                 </div>
            </div>
            
            <label style="display:block; font-size:0.8rem; font-weight:600; color:var(--text); margin-bottom:8px;">Data de Início da Leitura</label>
            <div style="display:flex; gap:12px;">
                <input type="date" id="start-date-input" value="<?= $startDateStr ?>" style="padding:10px 12px; border:1px solid var(--border); border-radius:8px; flex:1; font-family:inherit; color:var(--text);">
                <button onclick="saveStartDate()" style="padding:10px 20px; background:var(--primary); color:white; border:none; border-radius:8px; font-weight:600; cursor:pointer;">Atualizar</button>
            </div>
            <p style="font-size:0.75rem; color:var(--text-light); margin-top:8px;">Isso ajustará qual é o "Dia 1" do seu plano.</p>
        </div>
        <div class="report-item" style="border-color:#fecaca; background:#fff1f2; box-shadow:none;">
            <div style="display:flex; gap:12px; align-items:center; margin-bottom:12px;">
                 <div style="width:36px; height:36px; background:#fecaca; border-radius:8px; display:flex; align-items:center; justify-content:center; color:#dc2626;"><i data-lucide="alert-triangle" width="18"></i></div>
                 <h4 style="margin:0; color:#991b1b;">Zona de Perigo</h4>
            </div>
            <p style="font-size:0.85rem; color:#7f1d1d; margin-bottom:16px;">Apagar todo o seu progresso de leitura e anotações. Esta ação é irreversível.</p>
            <button onclick="resetPlan()" style="padding:12px 20px; background:#dc2626; color:white; border:none; border-radius:8px; font-weight:700; cursor:pointer; width:100%;">Resetar Tudo</button>
        </div>
    </div>

    <div id="content-diary" class="config-content" style="display:none;">
        <?php if(empty($reportData)): ?>
            <div style="text-align:center; padding:40px; color:var(--text-light);">
                <i data-lucide="book-open" width="40" style="opacity:0.5; margin-bottom:12px;"></i>
                <p>Nenhuma leitura registrada ainda.</p>
            </div>
        <?php else: ?>
            <?php foreach($reportData as $rep): ?>
                <div class="report-item">
                    <div class="report-date"><?= date('d/m/Y H:i', strtotime($rep['date'])) ?></div>
                    <div class="report-title">
                        <span style="background:var(--primary-soft); color:var(--primary); padding:2px 8px; border-radius:4px; font-size:0.8rem;">Dia <?= $rep['d'] ?></span>
                        Mês <?= $rep['m'] ?>
                    </div>
                    <?php if($rep['verses_count'] > 0): ?>
                        <div style="font-size:0.9rem; margin-bottom:8px;">
                            <i data-lucide="check-circle-2" width="14" style="color:var(--success); vertical-align:middle;"></i> 
                            Leu <b><?= $rep['verses_count'] ?></b> passagens.
                        </div>
                    <?php endif; ?>
                    <?php if(!empty($rep['comment'])): ?>
                        <div class="report-comment">"<?= htmlspecialchars($rep['comment']) ?>"</div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<!-- ========================================== -->
<!-- 5. FRONTEND LOGIC -->
<!-- ========================================== -->
<script>
const serverData = <?= json_encode($progressMap) ?>;
const currentPlanMonth = <?= json_encode($currentPlanMonth) ?>;
const currentPlanDay = <?= json_encode($currentPlanDay) ?>;
const state = { m: currentPlanMonth, d: currentPlanDay, data: serverData, saveTimer: null };

function init() {
    renderCalendar();
    loadDay(state.m, state.d);
    lucide.createIcons();
}

function renderCalendar() {
    const el = document.getElementById('calendar-strip');
    el.innerHTML = '';
    const months = ["", "JAN", "FEV", "MAR", "ABR", "MAI", "JUN", "JUL", "AGO", "SET", "OUT", "NOV", "DEZ"];
    
    for(let d=1; d<=25; d++) {
        const key = `${state.m}_${d}`;
        const info = state.data[key];
        const isDone = info && info.verses && info.verses.length > 0 && isDayComplete(state.m,d); 
        const isPartial = info && info.verses && info.verses.length > 0 && !isDone;
        
        const div = document.createElement('div');
        div.className = `cal-item ${state.d === d ? 'active' : ''} ${isDone ? 'done' : ''} ${isPartial ? 'partial' : ''}`;
        div.onclick = () => {
            state.d = d;
            renderCalendar(); 
            loadDay(state.m, d);
        };
        div.innerHTML = `<div class="cal-month">${months[state.m]}</div><div class="cal-num">${d}</div>`;
        el.appendChild(div);
        if(state.d === d) setTimeout(() => div.scrollIntoView({behavior:'smooth', inline:'center'}), 100);
    }
}

function loadDay(m, d) {
    const list = document.getElementById('verses-list');
    const title = document.getElementById('day-title');
    const badgeContainer = document.getElementById('status-badge-container');
    
    title.innerText = `Dia ${d}`;
    
    // Status Badge Logic
    let badgeHtml = '';
    if(isDayComplete(m, d)) {
        badgeHtml = '<span class="status-badge success">Concluído</span>';
    } else {
        badgeHtml = '<span class="status-badge pending">Pendente</span>';
    }
    badgeContainer.innerHTML = badgeHtml;

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
        card.onclick = (e) => { if(e.target.closest('a')) return; toggleVerse(m, d, idx); };
        const link = "https://www.bible.com/pt/bible/1608/" + vText.replace(/\s/g, '.').replace(/:/g, '.');
        card.innerHTML = `
            <div style="display:flex; align-items:center;">
                <div class="check-icon"><i data-lucide="check" width="14"></i></div>
                <span style="font-weight:600; color:#334155;">${vText}</span>
            </div>
            <a href="${link}" target="_blank" class="btn-read-link">LER <i data-lucide="book-open" width="12"></i></a>
        `;
        list.appendChild(card);
    });
    lucide.createIcons();
}

function toggleVerse(m, d, idx) {
    const key = `${m}_${d}`;
    if (!state.data[key]) state.data[key] = { verses: [], comment: "" };
    const list = state.data[key].verses;
    const exists = list.indexOf(idx);
    
    if (exists === -1) list.push(idx); else list.splice(exists, 1);
    
    loadDay(m, d);
    checkCompletionAndNavigate(m, d);
    showToast();
    clearTimeout(state.saveTimer);
    state.saveTimer = setTimeout(() => saveToServer(m, d), 1000);
}

function isDayComplete(m, d) {
    if (!bibleReadingPlan || !bibleReadingPlan[m]) return false;
    return (state.data[`${m}_${d}`]?.verses?.length || 0) >= bibleReadingPlan[m][d-1].length;
}

function checkCompletionAndNavigate(m, d) {
    if (isDayComplete(m, d)) {
        renderCalendar();
        setTimeout(() => navigateToNextDay(), 1200);
    } else {
        renderCalendar();
    }
}

function navigateToNextDay() {
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
    
    fetch('leitura.php', { method: 'POST', body: form }).catch(err => console.error(err));
}

// Config & Modal Logic
function openNoteModal() {
    const key = `${state.m}_${state.d}`;
    document.getElementById('note-input').value = state.data[key]?.comment || "";
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
// Hook for layout button
window.openConfig = function() { document.getElementById('modal-config').style.display = 'flex'; };

function openGroupComments() { alert('Funcionalidade de grupo em breve!'); }
function showToast() {
    const el = document.getElementById('save-toast');
    el.classList.add('show');
    setTimeout(() => el.classList.remove('show'), 2000);
}

// Config Tabs
function switchTab(tab) {
    document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
    document.querySelectorAll('.config-content').forEach(c => c.style.display = 'none');
    document.getElementById(`tab-${tab}`).classList.add('active');
    document.getElementById(`content-${tab}`).style.display = 'block';
}

function saveStartDate() {
    const val = document.getElementById('start-date-input').value;
    const f = new FormData();
    f.append('action', 'save_settings');
    f.append('start_date', val);
    fetch('leitura.php', { method:'POST', body:f }).then(() => window.location.reload());
}

function resetPlan() {
    if(!confirm("Tem certeza absoluta? Isso apagará TUDO.")) return;
    const f = new FormData(); f.append('action', 'reset_plan');
    fetch('leitura.php', { method:'POST', body:f }).then(() => window.location.reload());
}

init();
</script>