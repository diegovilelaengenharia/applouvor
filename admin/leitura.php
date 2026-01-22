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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
    $action = $_POST['action'] ?? '';

    if ($action === 'save_settings') {
        try {
            $pdo->prepare("INSERT INTO user_settings (user_id, setting_key, setting_value) VALUES (?, 'reading_plan_start_date', ?) ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)")->execute([$userId, $_POST['start_date']]);
            echo json_encode(['success' => true]);
        } catch (Exception $e) { echo json_encode(['success'=>false]); }
        exit;
    }
    if ($action === 'save_progress') {
        $m = (int)$_POST['month']; $d = (int)$_POST['day']; $comment = $_POST['comment'] ?? null; $title = $_POST['note_title'] ?? null; $versesJson = $_POST['verses'] ?? '[]';
        try {
            if ($comment !== null || $title !== null) {
                $sql = "INSERT INTO reading_progress (user_id, month_num, day_num, verses_read, completed_at"; $vals = "VALUES (?, ?, ?, ?, NOW()"; $updates = "verses_read = VALUES(verses_read), completed_at = NOW()"; $params = [$userId, $m, $d, $versesJson];
                if($comment !== null) { $sql .= ", comment"; $vals .= ", ?"; $updates .= ", comment = VALUES(comment)"; $params[] = $comment; }
                if($title !== null) { $sql .= ", note_title"; $vals .= ", ?"; $updates .= ", note_title = VALUES(note_title)"; $params[] = $title; }
                $sql .= ") $vals) ON DUPLICATE KEY UPDATE $updates";
            } else {
                 $sql = "INSERT INTO reading_progress (user_id, month_num, day_num, verses_read, completed_at) VALUES (?, ?, ?, ?, NOW()) ON DUPLICATE KEY UPDATE verses_read = VALUES(verses_read), completed_at = NOW()";
                $params = [$userId, $m, $d, $versesJson];
            }
            $pdo->prepare($sql)->execute($params);
            echo json_encode(['success' => true]);
        } catch (Exception $e) { echo json_encode(['success'=>false, 'error'=>$e->getMessage()]); }
        exit;
    }
    if ($action === 'reset_plan') { $pdo->prepare("DELETE FROM reading_progress WHERE user_id = ?")->execute([$userId]); echo json_encode(['success'=>true]); exit; }
}

$stmt = $pdo->prepare("SELECT setting_key, setting_value FROM user_settings WHERE user_id = ?"); $stmt->execute([$userId]); $settings = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
$startDateStr = $settings['reading_plan_start_date'] ?? date('Y-01-01');

$start = new DateTime($startDateStr); $start->setTime(0,0,0); $now->setTime(0,0,0);
$diff = $start->diff($now); $daysPassed = $diff->invert ? -1*$diff->days : $diff->days;
$planDayIndex = max(1, $daysPassed + 1);
$currentPlanMonth = floor(($planDayIndex - 1) / 25) + 1; $currentPlanDay = (($planDayIndex - 1) % 25) + 1;
if($currentPlanMonth>12){ $currentPlanMonth=12; $currentPlanDay=25; }

$stmt = $pdo->prepare("SELECT month_num, day_num, verses_read, comment, note_title, completed_at FROM reading_progress WHERE user_id = ? ORDER BY completed_at DESC");
$stmt->execute([$userId]); $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

$progressMap = []; $totalChaptersRead = 0; $reportData = [];
foreach($rows as $r) {
    $verses = json_decode($r['verses_read'] ?? '[]', true); if(!is_array($verses)) $verses=[];
    if(count($verses)>0 || !empty($r['completed_at'])) $totalChaptersRead++;
    $k = "{$r['month_num']}_{$r['day_num']}";
    $progressMap[$k] = ['verses'=>$verses, 'comment'=>$r['comment']??'', 'title'=>$r['note_title']??'', 'date'=>$r['completed_at']];
    if(count($verses)>0 || !empty($r['comment']) || !empty($r['note_title'])) {
        $reportData[] = ['m'=>(int)$r['month_num'], 'd'=>(int)$r['day_num'], 'date'=>$r['completed_at'], 'comment'=>$r['comment'], 'title'=>$r['note_title']??''];
    }
}
$completionPercent = min(100, round(($totalChaptersRead / 300) * 100));

// RENDER: Mobile Header & Layout
renderAppHeader('Leitura Bíblica'); 
?>

<!-- FRONTEND -->
<script src="../assets/js/reading_plan_data.js?v=<?= time() ?>"></script>
<script src="https://unpkg.com/lucide@latest"></script>

<style>
    :root {
        --primary: #6366f1; --primary-soft: #e0e7ff; --success: #10b981; --success-soft: #d1fae5;
        --warning: #f59e0b; --warning-soft: #fef3c7; --surface: #ffffff; --bg: #f8fafc;
        --text: #1e293b; --text-light: #64748b; --border: #e2e8f0;
    }
    body { background-color: var(--bg); color: var(--text); padding-bottom: 80px; }
    
    /* Desktop Header Fixed */
    .desktop-header {
        display: none; background: var(--bg-surface); padding: 16px 32px; border-bottom: 1px solid var(--border);
        align-items: center; justify-content: space-between; position: sticky; top: 0; z-index: 100;
    }
    @media (min-width: 1025px) { .desktop-header { display: flex; } }
    
    .header-actions { display: flex; align-items: center; gap: 12px; }
    .btn-icon-header {
        width: 40px; height: 40px; border-radius: 10px; display: flex; align-items: center; justify-content: center;
        border: 1px solid var(--border); background: var(--surface); color: var(--text-light); cursor: pointer; transition: all 0.2s;
        text-decoration: none;
    }
    .btn-icon-header:hover { background: var(--bg); color: var(--text); }
    .avatar-circle {
        width: 40px; height: 40px; border-radius: 50%; overflow: hidden; border: 2px solid var(--bg); box-shadow: 0 0 0 2px var(--border);
        cursor: pointer; display: block;
    }
    .avatar-circle img { width: 100%; height: 100%; object-fit: cover; }

    /* Calendar Strip */
    .cal-strip {
        display: flex !important; flex-direction: row !important; flex-wrap: nowrap !important;
        gap: 12px; overflow-x: auto; padding: 12px 20px;
        background: var(--surface); border-bottom: 1px solid var(--border);
        scrollbar-width: none;
    }
    .cal-strip::-webkit-scrollbar { display: none; }
    .cal-item {
        min-width: 64px; height: 76px; border-radius: 16px; background: var(--bg); border: 2px solid transparent; 
        display: flex; flex-direction: column; align-items: center; justify-content: center; flex-shrink: 0; cursor: pointer; transition: all 0.2s;
    }
    .cal-item.active { background: var(--surface); border-color: var(--primary); box-shadow: 0 4px 12px rgba(99,99,241,0.2); }
    .cal-item.active .cal-num { color: var(--primary); }
    .cal-item.done { background: var(--success-soft); border-color: #a7f3d0; }
    .cal-item.done .cal-num { color: #047857; }
    
    .main-area { max-width: 800px; margin: 0 auto; padding: 20px 16px; }
    .status-badge { font-size: 0.75rem; font-weight: 700; padding: 4px 10px; border-radius: 20px; text-transform: uppercase; }
    .status-badge.success { background: #dcfce7; color: #166534; }
    .status-badge.pending { background: #ffedd5; color: #9a3412; }
    
    .verse-card {
        background: var(--surface); border: 1px solid var(--border); border-radius: 16px; padding: 16px; margin-bottom: 12px;
        display: flex; align-items: center; justify-content: space-between; cursor: pointer;
    }
    .verse-card.read { background: #f0fdf4; border-color: #bbf7d0; }
    .verse-card.read .check-icon { background: var(--success); border-color: var(--success); color: white; }
    .check-icon { width: 24px; height: 24px; border-radius: 50%; border: 2px solid var(--border); color: transparent; display: flex; align-items: center; justify-content: center; margin-right: 12px; }
    .btn-read-link { background: linear-gradient(135deg, #10b981 0%, #059669 100%); color: white; padding: 8px 16px; border-radius: 20px; text-decoration: none; font-weight: 700; font-size: 0.75rem; display: flex; align-items: center; gap: 6px; }

    /* Modais Fixed */
    .modal-overlay {
        position: fixed !important; top: 0 !important; left: 0 !important; right: 0 !important; bottom: 0 !important;
        width: 100vw; height: 100vh; background: rgba(0,0,0,0.5); backdrop-filter: blur(4px); z-index: 9999;
        display: none; align-items: center; justify-content: center;
    }
    .config-fullscreen {
        position: fixed !important; top: 0 !important; left: 0 !important; right: 0 !important; bottom: 0 !important;
        width: 100vw; height: 100vh; background: var(--bg-body); z-index: 99999;
        display: none; flex-direction: column; overflow-y: auto;
    }
    .config-header { background: var(--surface); padding: 16px 20px; border-bottom: 1px solid var(--border); display: flex; justify-content: space-between; align-items: center; }
    .config-tabs { display: flex; background: var(--surface); border-bottom: 1px solid var(--border); padding: 0 20px; }
    .tab-btn { padding: 16px 20px; font-weight: 600; color: var(--text-light); border-bottom: 2px solid transparent; cursor: pointer; }
    .tab-btn.active { color: var(--primary); border-bottom-color: var(--primary); }
    .config-content { padding: 20px; max-width: 800px; margin: 0 auto; width: 100%; }
    .report-item { background: var(--surface); border-radius: 12px; padding: 16px; margin-bottom: 12px; border: 1px solid var(--border); }
    .bottom-bar { position: fixed; bottom: 0; left: 0; right: 0; background: rgba(255,255,255,0.9); backdrop-filter: blur(12px); border-top: 1px solid var(--border); padding: 12px; z-index: 200; display: grid; grid-template-columns: 1fr 1fr; gap: 12px; max-width: 800px; margin: 0 auto; }
    @media (min-width: 1024px) { .bottom-bar { left: 280px; } }
    .action-btn { background: var(--surface); border: 1px solid var(--border); padding: 12px; border-radius: 12px; display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 6px; cursor: pointer; }
    .icon-box { width: 32px; height: 32px; border-radius: 10px; display: flex; align-items: center; justify-content: center; }
    .icon-box.purple { background: #f3e8ff; color: #9333ea; } .icon-box.blue { background: #e0f2fe; color: #0284c7; }
</style>

<!-- DESKTOP HEADER -->
<div class="desktop-header">
    <div style="display: flex; align-items: center; gap: 16px;">
        <button onclick="window.history.back()" style="border:none; background:none; cursor:pointer;"><i data-lucide="arrow-left" color="#64748b"></i></button>
        <div>
            <h1 style="font-size: 1.5rem; font-weight: 700; color: var(--text); margin: 0; line-height: 1.2;">Leitura Bíblica</h1>
            <div style="font-size: 0.85rem; color: var(--text-light);">App Louvor PIB Oliveira</div>
        </div>
    </div>
    <div class="header-actions">
        <?php if (isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin'): ?>
            <a href="lider.php" class="btn-icon-header" title="Líder" style="background:#fef2f2; color:#ef4444; border-color:#fecaca;"><i data-lucide="crown" width="20"></i></a>
        <?php endif; ?>
        <button onclick="openConfig()" class="btn-icon-header" title="Configurações"><i data-lucide="settings-2" width="20"></i></button>
        <a href="perfil.php" class="avatar-circle">
            <img src="<?= $_layoutUser['photo'] ?? 'https://ui-avatars.com/api/?name=User' ?>" alt="Avatar">
        </a>
    </div>
</div>

<!-- INFO BAR -->
<div style="background: var(--bg-surface); border-bottom: 1px solid var(--border); padding: 16px 20px;">
    <div style="display: flex; justify-content: space-between; align-items: end; margin-bottom: 10px;">
        <span style="font-size:0.7rem; text-transform:uppercase; color:var(--text-light); font-weight:700;">Seu Progresso Global</span>
        <div style="color:var(--text); font-weight:700; font-size:1.1rem; line-height:1.2;">
            <span style="color:var(--primary);"><?= $totalChaptersRead ?></span> / 300 <span style="color:var(--text-light); font-size:0.9rem;">(<?= $completionPercent ?>%)</span>
        </div>
    </div>
    <div style="height: 6px; background: var(--bg); width: 100%; border-radius: 3px; overflow: hidden;">
        <div style="height: 100%; background: linear-gradient(90deg, var(--primary), var(--secondary)); width: <?= $completionPercent ?>%;"></div>
    </div>
</div>

<div class="cal-strip" id="calendar-strip"></div>

<div class="main-area">
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
    <button class="action-btn" onclick="openNoteModal()"><div class="icon-box purple"><i data-lucide="pen-line" width="18"></i></div><span>Anotação</span></button>
    <button class="action-btn" onclick="openGroupComments()"><div class="icon-box blue"><i data-lucide="message-circle" width="18"></i></div><span>Comentários</span></button>
</div>
<div id="save-toast" class="auto-save-feedback" style="position:fixed; top:90px; left:50%; transform:translateX(-50%); background:#1e293b; color:white; padding:8px 16px; border-radius:20px; opacity:0; pointer-events:none; z-index:2000; transition:opacity 0.3s; display:flex; gap:8px;"><i data-lucide="check" width="14"></i> Salvo auto</div>

<!-- NOTE MODAL (RICH) -->
<div id="modal-note" class="modal-overlay">
    <div style="background: white; width: 95%; max-width: 600px; border-radius: 20px; box-shadow: 0 25px 50px -12px rgba(0,0,0,0.25); display: flex; flex-direction: column; overflow: hidden; animation: scaleUp 0.25s;">
        <div style="background: #fff7ed; padding: 20px 24px; border-bottom: 1px solid #fed7aa; display: flex; justify-content: space-between; align-items: center;">
            <h3 style="margin: 0; color: #ea580c; font-size: 1.15rem; display: flex; align-items: center; gap: 10px; font-weight:700;"><i data-lucide="pen-line" width="20"></i> Minha Anotação</h3>
            <button onclick="document.getElementById('modal-note').style.display='none'" style="border: none; background: none; color: #c2410c; cursor: pointer;"><i data-lucide="x" width="22"></i></button>
        </div>
        <div style="padding: 24px; overflow-y:auto; max-height: 70vh;">
            <div style="margin-bottom: 20px;">
                <label style="display: block; font-size: 0.85rem; font-weight: 700; color: #334155; margin-bottom: 8px;">Título</label>
                <input type="text" id="note-title-input" placeholder="Ex: Reflexão sobre Gênesis..." style="width: 100%; padding: 12px 16px; border: 1px solid #cbd5e1; border-radius: 10px; font-size: 0.95rem; outline: none;">
            </div>
            <div style="margin-bottom: 20px;">
                <label style="display: block; font-size: 0.85rem; font-weight: 700; color: #334155; margin-bottom: 8px;">Arquivo (Opcional)</label>
                <div style="display:flex; gap:10px;">
                    <label style="background:#f1f5f9; border:1px solid #cbd5e1; padding:8px 16px; border-radius:8px; font-size:0.85rem; color:#475569; font-weight:600; cursor:not-allowed;">Escolher arquivo</label>
                    <span style="font-size:0.85rem; color:#94a3b8; align-self:center;">Nenhum arquivo</span>
                </div>
            </div>
            <div style="margin-bottom: 10px;">
                <label style="display: block; font-size: 0.85rem; font-weight: 700; color: #334155; margin-bottom: 8px;">Descrição Detalhada</label>
                <div style="border: 1px solid #cbd5e1; border-bottom:none; border-radius: 10px 10px 0 0; background: #f8fafc; padding: 8px 12px; display:flex; gap:12px; border-bottom:1px solid #e2e8f0;">
                    <i data-lucide="bold" width="16" style="color:#64748b;"></i> <i data-lucide="italic" width="16" style="color:#64748b;"></i> <i data-lucide="link" width="16" style="color:#64748b;"></i>
                </div>
                <textarea id="note-desc-input" style="width: 100%; min-height: 180px; padding: 16px; border: 1px solid #cbd5e1; border-top:none; border-radius: 0 0 10px 10px; outline: none; resize: vertical;" placeholder="Digite aqui..."></textarea>
            </div>
        </div>
        <div style="padding: 16px 24px; background: #fff; border-top: 1px solid #f1f5f9; display: flex; justify-content: flex-end; gap: 12px;">
            <button onclick="document.getElementById('modal-note').style.display='none'" style="padding: 12px 20px; border: 1px solid #e2e8f0; background: white; color: #64748b; border-radius: 10px; font-weight: 600; cursor: pointer;">Cancelar</button>
            <button onclick="saveNote()" style="padding: 12px 24px; border: none; background: #f97316; color: white; border-radius: 10px; font-weight: 700; cursor: pointer;">Salvar Anotação</button>
        </div>
    </div>
</div>

<!-- CONFIG MODAL (FULLSCREEN) -->
<div id="modal-config" class="config-fullscreen">
    <div class="config-header">
        <h2 style="font-size: 1.25rem;">Configurações & Diário</h2>
        <button onclick="document.getElementById('modal-config').style.display='none'" style="border:none; background:none; cursor:pointer;"><i data-lucide="x"></i></button>
    </div>
    <div class="config-tabs">
        <div class="tab-btn active" onclick="switchTab('general')" id="tab-general">Geral</div>
        <div class="tab-btn" onclick="switchTab('diary')" id="tab-diary">Meu Diário</div>
    </div>
    <div id="content-general" class="config-content">
        <div class="report-item">
            <h4 style="margin:0 0 16px 0;">Meu Plano</h4>
            <div style="display:flex; gap:12px;">
                <input type="date" id="start-date-input" value="<?= $startDateStr ?>" style="padding:10px; border:1px solid var(--border); border-radius:8px; flex:1;">
                <button onclick="saveStartDate()" style="padding:10px 20px; background:var(--primary); color:white; border:none; border-radius:8px; cursor:pointer;">Atualizar</button>
            </div>
            <p style="font-size:0.8rem; color:var(--text-light); margin-top:8px;">Ajuste o "Dia 1".</p>
        </div>
        <div class="report-item" style="border-color:#fecaca; background:#fff1f2;">
            <h4 style="margin:0 0 8px 0; color:#b91c1c;">Zona de Perigo</h4>
            <button onclick="resetPlan()" style="padding:12px 20px; background:#dc2626; color:white; border:none; border-radius:8px; font-weight:700; cursor:pointer; width:100%;">Resetar Tudo</button>
        </div>
    </div>
    <div id="content-diary" class="config-content" style="display:none;">
        <?php if(empty($reportData)): ?><div style="text-align:center; padding:40px;">Nada.</div><?php else: ?>
        <?php foreach($reportData as $rep): ?>
            <div class="report-item">
                <div style="font-size:0.75rem; color:#64748b; font-weight:700;"><?= date('d/m H:i', strtotime($rep['date'])) ?> - Dia <?= $rep['d'] ?></div>
                <?php if($rep['title']): ?><div style="font-weight:800; color:#1e293b; margin:4px 0;"><?= htmlspecialchars($rep['title']) ?></div><?php endif; ?>
                <?php if($rep['comment']): ?><div style="font-style:italic; color:#475569; margin-top:4px;">"<?= htmlspecialchars($rep['comment']) ?>"</div><?php endif; ?>
            </div>
        <?php endforeach; endif; ?>
    </div>
</div>

<script>
const serverData = <?= json_encode($progressMap) ?>;
const currentPlanMonth = <?= json_encode($currentPlanMonth) ?>;
const currentPlanDay = <?= json_encode($currentPlanDay) ?>;
const state = { m: currentPlanMonth, d: currentPlanDay, data: serverData, saveTimer: null };

function init() { 
    // FIX: Move Modals to Body to prevent layout clipping
    document.body.appendChild(document.getElementById('modal-note'));
    document.body.appendChild(document.getElementById('modal-config'));
    document.body.appendChild(document.getElementById('save-toast'));

    renderCalendar(); 
    loadDay(state.m, state.d); 
    lucide.createIcons(); 
}

function renderCalendar() {
    const el = document.getElementById('calendar-strip'); el.innerHTML = '';
    const months = ["", "JAN", "FEV", "MAR", "ABR", "MAI", "JUN", "JUL", "AGO", "SET", "OUT", "NOV", "DEZ"];
    
    // Obter dia atual real do plano para cálculo de atraso
    const actualPlanDay = currentPlanDay; 
    const actualPlanMonth = currentPlanMonth;

    for(let d=1; d<=25; d++) {
        const key = `${state.m}_${d}`; const info = state.data[key];
        const isDone = info && info.verses && info.verses.length > 0 && isDayComplete(state.m,d); 
        
        // Lógica de Atraso: Se o mês é anterior, ou mês atual mas dia anterior ao dia do plano
        const isPast = (state.m < actualPlanMonth) || (state.m === actualPlanMonth && d < actualPlanDay);
        
        // Partial/Warning: Tem progresso incompleto OU está atrasado
        // Apenas marca como parcial se não estiver feito
        const hasProgress = info && info.verses && info.verses.length > 0;
        const isPartial = !isDone && (hasProgress || isPast);

        const div = document.createElement('div');
        div.className = `cal-item ${state.d === d ? 'active' : ''} ${isDone ? 'done' : ''} ${isPartial && state.d !== d ? 'partial' : ''}`;
        div.onclick = () => { state.d = d; renderCalendar(); loadDay(state.m, d); };
        div.innerHTML = `<div class="cal-month">${months[state.m]}</div><div class="cal-num">${d}</div>`;
        el.appendChild(div);
        if(state.d === d) setTimeout(() => div.scrollIntoView({behavior:'smooth', inline:'center'}), 100);
    }
}

function loadDay(m, d) {
    const list = document.getElementById('verses-list');
    const title = document.getElementById('day-title');
    const badge = document.getElementById('status-badge-container');
    title.innerText = `Dia ${d}`;
    badge.innerHTML = isDayComplete(m, d) ? '<span class="status-badge success">Concluído</span>' : '<span class="status-badge pending">Pendente</span>';
    
    if (!bibleReadingPlan || !bibleReadingPlan[m] || !bibleReadingPlan[m][d-1]) { list.innerHTML = '...'; return; }
    const verses = bibleReadingPlan[m][d-1];
    const key = `${m}_${d}`;
    const savedVerses = (state.data[key] && state.data[key].verses) ? state.data[key].verses : [];
    
    list.innerHTML = '';
    verses.forEach((vText, idx) => {
        const isRead = savedVerses.includes(idx);
        const card = document.createElement('div');
        card.className = `verse-card ${isRead ? 'read' : ''}`;
        card.onclick = (e) => { if(e.target.closest('a')) return; toggleVerse(m, d, idx); };
        card.innerHTML = `<div style="display:flex; align-items:center;"><div class="check-icon"><i data-lucide="check" width="14"></i></div><span style="font-weight:600; color:#334155;">${vText}</span></div><a href="https://www.bible.com/pt/bible/1608/${vText.replace(/\s/g,'.').replace(/:/g,'.')}" target="_blank" class="btn-read-link">LER <i data-lucide="book-open" width="12"></i></a>`;
        list.appendChild(card);
    });
    lucide.createIcons();
}

function toggleVerse(m, d, idx) {
    const key = `${m}_${d}`;
    if (!state.data[key]) state.data[key] = { verses: [], comment: "", title: "" };
    const list = state.data[key].verses;
    const exists = list.indexOf(idx);
    if (exists === -1) list.push(idx); else list.splice(exists, 1);
    loadDay(m, d); renderCalendar(); showToast();
    clearTimeout(state.saveTimer); state.saveTimer = setTimeout(() => saveToServer(m, d), 1000);
}

function isDayComplete(m, d) {
    if (!bibleReadingPlan || !bibleReadingPlan[m]) return false;
    return (state.data[`${m}_${d}`]?.verses?.length || 0) >= bibleReadingPlan[m][d-1].length;
}

function saveToServer(m, d) {
    const key = `${m}_${d}`; const data = state.data[key];
    const form = new FormData();
    form.append('action', 'save_progress'); form.append('month', m); form.append('day', d);
    form.append('verses', JSON.stringify(data.verses));
    if(data.comment) form.append('comment', data.comment);
    if(data.title) form.append('note_title', data.title);
    fetch('leitura.php', { method: 'POST', body: form });
}

function openNoteModal() {
    const key = `${state.m}_${state.d}`;
    document.getElementById('note-title-input').value = state.data[key]?.title || "";
    document.getElementById('note-desc-input').value = state.data[key]?.comment || "";
    document.getElementById('modal-note').style.display = 'flex';
}
function saveNote() {
    const title = document.getElementById('note-title-input').value;
    const desc = document.getElementById('note-desc-input').value;
    const key = `${state.m}_${state.d}`;
    if(!state.data[key]) state.data[key] = { verses: [], comment: "", title: "" };
    state.data[key].title = title;
    state.data[key].comment = desc;
    showToast();
    saveToServer(state.m, state.d);
    document.getElementById('modal-note').style.display = 'none';
}
function openConfig() { document.getElementById('modal-config').style.display = 'flex'; }
function switchTab(t) {
    document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
    document.querySelectorAll('.config-content').forEach(c => c.style.display = 'none');
    document.getElementById(`tab-${t}`).classList.add('active'); document.getElementById(`content-${t}`).style.display = 'block';
}
function saveStartDate() {
    const f = new FormData(); f.append('action', 'save_settings'); f.append('start_date', document.getElementById('start-date-input').value);
    fetch('leitura.php', { method:'POST', body:f }).then(() => window.location.reload());
}
function resetPlan() { if(confirm("Certeza?")) { const f = new FormData(); f.append('action', 'reset_plan'); fetch('leitura.php', { method:'POST', body:f }).then(() => window.location.reload()); } }
function showToast() { const el = document.getElementById('save-toast'); el.classList.add('show'); setTimeout(() => el.classList.remove('show'), 2000); }
function openGroupComments() { alert('Breve'); }
init();
</script>
<style> @keyframes scaleUp { from {transform:scale(0.95); opacity:0;} to {transform:scale(1); opacity:1;} } .auto-save-feedback.show { opacity:1; } </style>