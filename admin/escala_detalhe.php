<?php
// admin/escala_detalhe.php - Versão Otimizada
require_once '../includes/db.php';
require_once '../includes/layout.php';

$id = $_GET['id'] ?? null;
if (!$id) {
    header('Location: escalas.php');
    exit;
}

// --- API AJAX para salvamento automático ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajax'])) {
    header('Content-Type: application/json');
    
    if ($_POST['action'] === 'toggle_member') {
        $userId = $_POST['user_id'];
        $check = $pdo->prepare("SELECT * FROM schedule_users WHERE schedule_id = ? AND user_id = ?");
        $check->execute([$id, $userId]);
        
        if ($check->fetch()) {
            $stmt = $pdo->prepare("DELETE FROM schedule_users WHERE schedule_id = ? AND user_id = ?");
            $stmt->execute([$id, $userId]);
            echo json_encode(['status' => 'removed']);
        } else {
            $stmt = $pdo->prepare("INSERT INTO schedule_users (schedule_id, user_id) VALUES (?, ?)");
            $stmt->execute([$id, $userId]);
            echo json_encode(['status' => 'added']);
        }
        exit;
    }
    
    if ($_POST['action'] === 'toggle_song') {
        $songId = $_POST['song_id'];
        $check = $pdo->prepare("SELECT * FROM schedule_songs WHERE schedule_id = ? AND song_id = ?");
        $check->execute([$id, $songId]);
        
        if ($check->fetch()) {
            $stmt = $pdo->prepare("DELETE FROM schedule_songs WHERE schedule_id = ? AND song_id = ?");
            $stmt->execute([$id, $songId]);
            echo json_encode(['status' => 'removed']);
        } else {
            $stmtOrder = $pdo->prepare("SELECT MAX(position) FROM schedule_songs WHERE schedule_id = ?");
            $stmtOrder->execute([$id]);
            $lastOrder = $stmtOrder->fetchColumn() ?: 0;
            
            $stmt = $pdo->prepare("INSERT INTO schedule_songs (schedule_id, song_id, position) VALUES (?, ?, ?)");
            $stmt->execute([$id, $songId, $lastOrder + 1]);
            echo json_encode(['status' => 'added']);
        }
        exit;
    }
}

// Buscar Detalhes da Escala
$stmt = $pdo->prepare("SELECT * FROM schedules WHERE id = ?");
$stmt->execute([$id]);
$schedule = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$schedule) {
    echo "Escala não encontrada.";
    exit;
}

$date = new DateTime($schedule['event_date']);
$diaSemana = ['Domingo', 'Segunda', 'Terça', 'Quarta', 'Quinta', 'Sexta', 'Sábado'][$date->format('w')];

// Buscar Membros
$stmtUsers = $pdo->prepare("
    SELECT su.*, u.id as user_id, u.name, u.instrument, u.avatar_color
    FROM schedule_users su
    JOIN users u ON su.user_id = u.id
    WHERE su.schedule_id = ?
    ORDER BY u.name ASC
");
$stmtUsers->execute([$id]);
$team = $stmtUsers->fetchAll(PDO::FETCH_ASSOC);
$teamIds = array_column($team, 'user_id');

// Buscar Músicas com TODAS as tags
$stmtSongs = $pdo->prepare("
    SELECT ss.*, s.id as song_id, s.title, s.artist, s.tone, s.bpm, s.category, s.tag
    FROM schedule_songs ss
    JOIN songs s ON ss.song_id = s.id
    WHERE ss.schedule_id = ?
    ORDER BY ss.position ASC
");
$stmtSongs->execute([$id]);
$songs = $stmtSongs->fetchAll(PDO::FETCH_ASSOC);
$songIds = array_column($songs, 'song_id');

// Buscar TODOS para edição
$allUsers = $pdo->query("SELECT id, name, instrument, avatar_color FROM users ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);
$allSongs = $pdo->query("SELECT id, title, artist, tone, bpm, category, tag FROM songs ORDER BY title")->fetchAll(PDO::FETCH_ASSOC);

renderAppHeader('Escala');
renderPageHeader($schedule['event_type'], $diaSemana . ', ' . $date->format('d/m/Y'));
?>

<style>
.edit-mode-hidden { display: none; }
.view-mode-hidden { display: none; }
</style>

<!-- Info Card Moderno -->
<div style="max-width: 800px; margin: 0 auto 20px; padding: 0 16px;">
    <div style="background: var(--bg-surface); border-radius: 16px; padding: 16px; border: 1px solid var(--border-color); box-shadow: var(--shadow-sm);">
        <!-- Header com Botão Editar -->
        <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 16px;">
            <div style="flex: 1;">
                <h1 style="margin: 0 0 4px 0; font-size: 1.3rem; font-weight: 700; color: var(--text-main);"><?= htmlspecialchars($schedule['event_type']) ?></h1>
                <div style="font-size: 0.85rem; color: var(--text-muted);"><?= $diaSemana ?>, <?= $date->format('d/m/Y') ?></div>
            </div>
            
            <!-- Botão Editar -->
            <button id="editBtn" onclick="toggleEditMode()" style="
                padding: 10px 16px; border-radius: 10px;
                background: var(--bg-body); border: 1px solid var(--border-color);
                color: var(--text-main); cursor: pointer;
                display: flex; align-items: center; gap: 6px;
                font-weight: 600; font-size: 0.85rem;
                transition: all 0.2s;
            ">
                <i data-lucide="edit-2" style="width: 16px;"></i>
                <span>Editar</span>
            </button>
        </div>
        
        <!-- Info Row -->
        <div style="display: flex; align-items: center; gap: 16px; padding: 12px; background: var(--bg-body); border-radius: 12px; margin-bottom: <?= $schedule['notes'] ? '12px' : '0' ?>;">
            <div style="display: flex; align-items: center; gap: 6px;">
                <i data-lucide="clock" style="width: 16px; color: var(--text-muted);"></i>
                <span style="font-size: 0.9rem; font-weight: 600; color: var(--text-main);">19:00</span>
            </div>
            <div style="display: flex; align-items: center; gap: 6px;">
                <i data-lucide="users" style="width: 16px; color: var(--text-muted);"></i>
                <span style="font-size: 0.9rem; font-weight: 600; color: var(--text-main);"><?= count($team) ?></span>
            </div>
            <div style="display: flex; align-items: center; gap: 6px;">
                <i data-lucide="music" style="width: 16px; color: var(--text-muted);"></i>
                <span style="font-size: 0.9rem; font-weight: 600; color: var(--text-main);"><?= count($songs) ?></span>
            </div>
        </div>
        
        <!-- Observações -->
        <?php if ($schedule['notes']): ?>
            <div style="padding: 12px; background: #fffbeb; border-radius: 12px; border: 1px solid #fef3c7;">
                <div style="display: flex; align-items: center; gap: 6px; margin-bottom: 6px;">
                    <i data-lucide="info" style="width: 14px; color: #f59e0b;"></i>
                    <span style="font-size: 0.75rem; font-weight: 700; color: #f59e0b; text-transform: uppercase;">Observações</span>
                </div>
                <div style="font-size: 0.85rem; line-height: 1.4; color: #78350f;"><?= nl2br(htmlspecialchars($schedule['notes'])) ?></div>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Content -->
<div style="max-width: 800px; margin: 0 auto; padding: 0 16px 100px;">
    
    <!-- MODO VISUALIZAÇÃO -->
    <div id="view-mode" class="view-mode">
        <!-- PARTICIPANTES -->
        <div style="margin-bottom: 24px;">
            <h3 style="font-size: 1rem; font-weight: 700; color: var(--text-main); margin: 0 0 12px 0; display: flex; align-items: center; gap: 8px;">
                <i data-lucide="users" style="width: 20px; color: var(--primary);"></i>
                Participantes (<?= count($team) ?>)
            </h3>
            
            <?php if (empty($team)): ?>
                <div style="text-align: center; padding: 40px 20px; background: var(--bg-surface); border-radius: 12px; border: 1px dashed var(--border-color);">
                    <i data-lucide="user-plus" style="width: 32px; color: var(--text-muted); margin-bottom: 8px;"></i>
                    <p style="color: var(--text-muted); font-size: 0.9rem; margin: 0;">Nenhum participante escalado</p>
                </div>
            <?php else: ?>
                <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(140px, 1fr)); gap: 12px;">
                    <?php foreach ($team as $member): ?>
                        <div style="background: var(--bg-surface); border-radius: 12px; padding: 12px; text-align: center; border: 1px solid var(--border-color);">
                            <div style="
                                width: 56px; height: 56px; border-radius: 50%; margin: 0 auto 8px;
                                background: <?= $member['avatar_color'] ?: '#e2e8f0' ?>;
                                color: white; display: flex; align-items: center; justify-content: center;
                                font-weight: 700; font-size: 1.3rem;
                            ">
                                <?= strtoupper(substr($member['name'], 0, 1)) ?>
                            </div>
                            <div style="font-weight: 600; font-size: 0.85rem; color: var(--text-main); margin-bottom: 2px;"><?= htmlspecialchars($member['name']) ?></div>
                            <div style="font-size: 0.7rem; color: var(--text-muted);"><?= htmlspecialchars($member['instrument'] ?: 'Vocal') ?></div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
        
        <!-- REPERTÓRIO -->
        <div>
            <h3 style="font-size: 1rem; font-weight: 700; color: var(--text-main); margin: 0 0 12px 0; display: flex; align-items: center; gap: 8px;">
                <i data-lucide="music" style="width: 20px; color: var(--primary);"></i>
                Repertório (<?= count($songs) ?>)
            </h3>
            
            <?php if (empty($songs)): ?>
                <div style="text-align: center; padding: 40px 20px; background: var(--bg-surface); border-radius: 12px; border: 1px dashed var(--border-color);">
                    <i data-lucide="music-2" style="width: 32px; color: var(--text-muted); margin-bottom: 8px;"></i>
                    <p style="color: var(--text-muted); font-size: 0.9rem; margin: 0;">Nenhuma música selecionada</p>
                </div>
            <?php else: ?>
                <div style="display: flex; flex-direction: column; gap: 12px;">
                    <?php foreach ($songs as $index => $song): ?>
                        <div style="background: var(--bg-surface); border-radius: 12px; padding: 14px; border: 1px solid var(--border-color); display: flex; gap: 12px;">
                            <div style="font-size: 1.2rem; font-weight: 800; color: #e2e8f0; min-width: 24px;">
                                <?= $index + 1 ?>
                            </div>
                            <div style="flex: 1;">
                                <h4 style="margin: 0 0 4px 0; font-size: 1rem; font-weight: 700; color: var(--text-main);"><?= htmlspecialchars($song['title']) ?></h4>
                                <p style="margin: 0 0 10px 0; font-size: 0.85rem; color: var(--text-muted);"><?= htmlspecialchars($song['artist']) ?></p>
                                <div style="display: flex; gap: 6px; flex-wrap: wrap;">
                                    <?php if ($song['category']): ?>
                                        <span style="background: #eff6ff; color: #2563eb; padding: 4px 10px; border-radius: 6px; font-size: 0.7rem; font-weight: 700; border: 1px solid #dbeafe;">
                                            <?= htmlspecialchars($song['category']) ?>
                                        </span>
                                    <?php endif; ?>
                                    <?php if ($song['tag']): ?>
                                        <span style="background: #f0fdf4; color: #16a34a; padding: 4px 10px; border-radius: 6px; font-size: 0.7rem; font-weight: 700; border: 1px solid #dcfce7;">
                                            <?= htmlspecialchars($song['tag']) ?>
                                        </span>
                                    <?php endif; ?>
                                    <?php if ($song['tone']): ?>
                                        <span style="background: #fff7ed; color: #ea580c; padding: 4px 10px; border-radius: 6px; font-size: 0.7rem; font-weight: 700; border: 1px solid #ffedd5;">
                                            TOM: <?= $song['tone'] ?>
                                        </span>
                                    <?php endif; ?>
                                    <?php if ($song['bpm']): ?>
                                        <span style="background: #fef2f2; color: #dc2626; padding: 4px 10px; border-radius: 6px; font-size: 0.7rem; font-weight: 700; border: 1px solid #fee2e2;">
                                            <?= $song['bpm'] ?> BPM
                                        </span>
                                    <?php endif; ?>
                                    <a href="https://www.youtube.com/results?search_query=<?= urlencode($song['title'] . ' ' . $song['artist']) ?>" target="_blank" style="
                                        background: #fef2f2; color: #ef4444; text-decoration: none;
                                        padding: 4px 10px; border-radius: 6px; font-size: 0.7rem; font-weight: 700; border: 1px solid #fee2e2;
                                        display: inline-flex; align-items: center; gap: 4px;
                                    ">
                                        <i data-lucide="youtube" style="width: 12px;"></i> YouTube
                                    </a>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- MODO EDIÇÃO com 2 Colunas e Busca -->
    <div id="edit-mode" class="edit-mode edit-mode-hidden">
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
            <!-- Coluna Participantes -->
            <div>
                <h4 style="font-size: 0.9rem; font-weight: 700; color: var(--text-main); margin: 0 0 12px 0;">Participantes</h4>
                <input type="text" id="searchMembers" placeholder="Buscar participante..." onkeyup="filterMembers()" style="
                    width: 100%; padding: 10px 12px; border: 1px solid var(--border-color);
                    border-radius: 10px; font-size: 0.85rem; margin-bottom: 12px;
                    background: var(--bg-surface);
                ">
                <div id="membersList" style="display: flex; flex-direction: column; gap: 8px; max-height: 400px; overflow-y: auto;">
                    <?php foreach ($allUsers as $user): 
                        $isSelected = in_array($user['id'], $teamIds);
                    ?>
                        <label class="member-filter-item" data-name="<?= strtolower($user['name']) ?>" style="
                            display: flex; align-items: center; gap: 10px; padding: 10px;
                            background: var(--bg-surface); border-radius: 10px;
                            border: 2px solid <?= $isSelected ? 'var(--primary)' : 'var(--border-color)' ?>;
                            cursor: pointer; transition: all 0.2s;
                        ">
                            <input type="checkbox" 
                                   <?= $isSelected ? 'checked' : '' ?>
                                   onchange="toggleMember(<?= $user['id'] ?>, this)"
                                   style="width: 18px; height: 18px; accent-color: var(--primary); cursor: pointer;">
                            <div style="
                                width: 32px; height: 32px; border-radius: 50%;
                                background: <?= $user['avatar_color'] ?: '#e2e8f0' ?>;
                                color: white; display: flex; align-items: center; justify-content: center;
                                font-weight: 700; font-size: 0.85rem; flex-shrink: 0;
                            ">
                                <?= strtoupper(substr($user['name'], 0, 1)) ?>
                            </div>
                            <div style="flex: 1; min-width: 0;">
                                <div style="font-weight: 600; font-size: 0.8rem; color: var(--text-main); white-space: nowrap; overflow: hidden; text-overflow: ellipsis;"><?= htmlspecialchars($user['name']) ?></div>
                                <div style="font-size: 0.7rem; color: var(--text-muted);"><?= htmlspecialchars($user['instrument'] ?: 'Vocal') ?></div>
                            </div>
                        </label>
                    <?php endforeach; ?>
                </div>
            </div>
            
            <!-- Coluna Repertório -->
            <div>
                <h4 style="font-size: 0.9rem; font-weight: 700; color: var(--text-main); margin: 0 0 12px 0;">Repertório</h4>
                <input type="text" id="searchSongs" placeholder="Buscar música..." onkeyup="filterSongs()" style="
                    width: 100%; padding: 10px 12px; border: 1px solid var(--border-color);
                    border-radius: 10px; font-size: 0.85rem; margin-bottom: 12px;
                    background: var(--bg-surface);
                ">
                <div id="songsList" style="display: flex; flex-direction: column; gap: 8px; max-height: 400px; overflow-y: auto;">
                    <?php foreach ($allSongs as $song): 
                        $isSelected = in_array($song['id'], $songIds);
                    ?>
                        <label class="song-filter-item" data-title="<?= strtolower($song['title']) ?>" data-artist="<?= strtolower($song['artist']) ?>" style="
                            display: flex; align-items: center; gap: 10px; padding: 10px;
                            background: var(--bg-surface); border-radius: 10px;
                            border: 2px solid <?= $isSelected ? 'var(--primary)' : 'var(--border-color)' ?>;
                            cursor: pointer; transition: all 0.2s;
                        ">
                            <input type="checkbox" 
                                   <?= $isSelected ? 'checked' : '' ?>
                                   onchange="toggleSong(<?= $song['id'] ?>, this)"
                                   style="width: 18px; height: 18px; accent-color: var(--primary); cursor: pointer;">
                            <div style="flex: 1; min-width: 0;">
                                <div style="font-weight: 600; font-size: 0.8rem; color: var(--text-main); white-space: nowrap; overflow: hidden; text-overflow: ellipsis;"><?= htmlspecialchars($song['title']) ?></div>
                                <div style="font-size: 0.7rem; color: var(--text-muted); white-space: nowrap; overflow: hidden; text-overflow: ellipsis;"><?= htmlspecialchars($song['artist']) ?></div>
                                <?php if ($song['tone']): ?>
                                    <div style="margin-top: 4px;">
                                        <span style="background: #fff7ed; color: #ea580c; padding: 2px 6px; border-radius: 4px; font-size: 0.65rem; font-weight: 700;">
                                            <?= $song['tone'] ?>
                                        </span>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </label>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
let editMode = false;

function toggleEditMode() {
    editMode = !editMode;
    const editBtn = document.getElementById('editBtn');
    const viewMode = document.getElementById('view-mode');
    const editModeEl = document.getElementById('edit-mode');
    
    if (editMode) {
        // Entrar em modo edição
        viewMode.classList.add('view-mode-hidden');
        editModeEl.classList.remove('edit-mode-hidden');
        editBtn.style.background = '#ef4444';
        editBtn.style.borderColor = '#ef4444';
        editBtn.style.color = 'white';
        editBtn.innerHTML = '<i data-lucide="x" style="width: 16px;"></i><span>Cancelar</span>';
    } else {
        // Voltar para visualização
        viewMode.classList.remove('view-mode-hidden');
        editModeEl.classList.add('edit-mode-hidden');
        editBtn.style.background = 'var(--bg-body)';
        editBtn.style.borderColor = 'var(--border-color)';
        editBtn.style.color = 'var(--text-main)';
        editBtn.innerHTML = '<i data-lucide="edit-2" style="width: 16px;"></i><span>Editar</span>';
        
        // Recarregar página para atualizar visualização
        location.reload();
    }
    
    // Re-render Lucide icons
    if (typeof lucide !== 'undefined') {
        lucide.createIcons();
    }
}

function filterMembers() {
    const search = document.getElementById('searchMembers').value.toLowerCase();
    const items = document.querySelectorAll('.member-filter-item');
    items.forEach(item => {
        const name = item.getAttribute('data-name');
        item.style.display = name.includes(search) ? 'flex' : 'none';
    });
}

function filterSongs() {
    const search = document.getElementById('searchSongs').value.toLowerCase();
    const items = document.querySelectorAll('.song-filter-item');
    items.forEach(item => {
        const title = item.getAttribute('data-title');
        const artist = item.getAttribute('data-artist');
        item.style.display = (title.includes(search) || artist.includes(search)) ? 'flex' : 'none';
    });
}

function toggleMember(userId, checkbox) {
    const label = checkbox.closest('.member-filter-item');
    
    fetch('escala_detalhe.php?id=<?= $id ?>', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: 'ajax=1&action=toggle_member&user_id=' + userId
    })
    .then(r => r.json())
    .then(data => {
        if (data.status === 'added') {
            label.style.borderColor = 'var(--primary)';
        } else {
            label.style.borderColor = 'var(--border-color)';
        }
    });
}

function toggleSong(songId, checkbox) {
    const label = checkbox.closest('.song-filter-item');
    
    fetch('escala_detalhe.php?id=<?= $id ?>', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: 'ajax=1&action=toggle_song&song_id=' + songId
    })
    .then(r => r.json())
    .then(data => {
        if (data.status === 'added') {
            label.style.borderColor = 'var(--primary)';
        } else {
            label.style.borderColor = 'var(--border-color)';
        }
    });
}
</script>

<?php renderAppFooter(); ?>
