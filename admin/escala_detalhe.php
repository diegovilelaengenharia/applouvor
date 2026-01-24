<?php
// admin/escala_detalhe.php - Visualização e Edição
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

// Buscar Músicas
$stmtSongs = $pdo->prepare("
    SELECT ss.*, s.id as song_id, s.title, s.artist, s.tone, s.bpm
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
$allSongs = $pdo->query("SELECT id, title, artist, tone, bpm FROM songs ORDER BY title")->fetchAll(PDO::FETCH_ASSOC);

renderAppHeader('Escala');
renderPageHeader($schedule['event_type'], $diaSemana . ', ' . $date->format('d/m/Y'));
?>

<style>
.edit-mode-hidden { display: none; }
.view-mode-hidden { display: none; }
</style>

<!-- Botão Editar Fixo -->
<div style="position: fixed; bottom: 80px; right: 20px; z-index: 50;">
    <button id="editBtn" onclick="toggleEditMode()" style="
        width: 56px; height: 56px; border-radius: 50%;
        background: linear-gradient(135deg, #8b5cf6, #7c3aed);
        color: white; border: none; cursor: pointer;
        box-shadow: 0 4px 12px rgba(139, 92, 246, 0.4);
        display: flex; align-items: center; justify-content: center;
        transition: all 0.3s;
    " onmouseover="this.style.transform='scale(1.1)'" onmouseout="this.style.transform='scale(1)'">
        <i data-lucide="edit-2" style="width: 24px;"></i>
    </button>
</div>

<!-- Info Card -->
<div style="max-width: 800px; margin: 0 auto 20px; padding: 0 16px;">
    <div style="background: linear-gradient(135deg, #047857, #059669); border-radius: 16px; padding: 16px; color: white; box-shadow: 0 4px 12px rgba(4, 120, 87, 0.2);">
        <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 12px;">
            <i data-lucide="calendar" style="width: 24px;"></i>
            <div>
                <div style="font-weight: 700; font-size: 1.1rem;"><?= $diaSemana ?>, <?= $date->format('d/m/Y') ?></div>
                <div style="font-size: 0.85rem; opacity: 0.9;">19:00</div>
            </div>
        </div>
        <?php if ($schedule['notes']): ?>
            <div style="background: rgba(255,255,255,0.15); padding: 12px; border-radius: 12px; margin-top: 12px;">
                <div style="font-size: 0.75rem; font-weight: 600; opacity: 0.8; margin-bottom: 4px;">OBSERVAÇÕES</div>
                <div style="font-size: 0.9rem; line-height: 1.4;"><?= nl2br(htmlspecialchars($schedule['notes'])) ?></div>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Content -->
<div style="max-width: 800px; margin: 0 auto; padding: 0 16px 100px;">
    
    <!-- PARTICIPANTES -->
    <div style="margin-bottom: 24px;">
        <h3 style="font-size: 1rem; font-weight: 700; color: var(--text-main); margin: 0 0 12px 0; display: flex; align-items: center; gap: 8px;">
            <i data-lucide="users" style="width: 20px; color: var(--primary);"></i>
            Participantes (<?= count($team) ?>)
        </h3>
        
        <!-- Modo Visualização -->
        <div id="view-participantes" class="view-mode">
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
        
        <!-- Modo Edição -->
        <div id="edit-participantes" class="edit-mode edit-mode-hidden">
            <div style="display: flex; flex-direction: column; gap: 8px;">
                <?php foreach ($allUsers as $user): 
                    $isSelected = in_array($user['id'], $teamIds);
                ?>
                    <label style="
                        display: flex; align-items: center; gap: 12px; padding: 12px;
                        background: var(--bg-surface); border-radius: 12px;
                        border: 2px solid <?= $isSelected ? 'var(--primary)' : 'var(--border-color)' ?>;
                        cursor: pointer; transition: all 0.2s;
                    " class="member-item" data-user-id="<?= $user['id'] ?>">
                        <input type="checkbox" 
                               <?= $isSelected ? 'checked' : '' ?>
                               onchange="toggleMember(<?= $user['id'] ?>, this)"
                               style="width: 20px; height: 20px; accent-color: var(--primary); cursor: pointer;">
                        <div style="
                            width: 40px; height: 40px; border-radius: 50%;
                            background: <?= $user['avatar_color'] ?: '#e2e8f0' ?>;
                            color: white; display: flex; align-items: center; justify-content: center;
                            font-weight: 700; font-size: 1rem;
                        ">
                            <?= strtoupper(substr($user['name'], 0, 1)) ?>
                        </div>
                        <div style="flex: 1;">
                            <div style="font-weight: 600; font-size: 0.9rem; color: var(--text-main);"><?= htmlspecialchars($user['name']) ?></div>
                            <div style="font-size: 0.75rem; color: var(--text-muted);"><?= htmlspecialchars($user['instrument'] ?: 'Vocal') ?></div>
                        </div>
                    </label>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
    
    <!-- REPERTÓRIO -->
    <div>
        <h3 style="font-size: 1rem; font-weight: 700; color: var(--text-main); margin: 0 0 12px 0; display: flex; align-items: center; gap: 8px;">
            <i data-lucide="music" style="width: 20px; color: var(--primary);"></i>
            Repertório (<?= count($songs) ?>)
        </h3>
        
        <!-- Modo Visualização -->
        <div id="view-repertorio" class="view-mode">
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
                                <div style="display: flex; gap: 8px; flex-wrap: wrap;">
                                    <?php if ($song['tone']): ?>
                                        <span style="background: #fff7ed; color: #ea580c; padding: 4px 10px; border-radius: 6px; font-size: 0.75rem; font-weight: 700; border: 1px solid #ffedd5;">
                                            TOM: <?= $song['tone'] ?>
                                        </span>
                                    <?php endif; ?>
                                    <?php if ($song['bpm']): ?>
                                        <span style="background: #f0fdf4; color: #16a34a; padding: 4px 10px; border-radius: 6px; font-size: 0.75rem; font-weight: 700; border: 1px solid #dcfce7;">
                                            <?= $song['bpm'] ?> BPM
                                        </span>
                                    <?php endif; ?>
                                    <a href="https://www.youtube.com/results?search_query=<?= urlencode($song['title'] . ' ' . $song['artist']) ?>" target="_blank" style="
                                        background: #fef2f2; color: #ef4444; text-decoration: none;
                                        padding: 4px 10px; border-radius: 6px; font-size: 0.75rem; font-weight: 700; border: 1px solid #fee2e2;
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
        
        <!-- Modo Edição -->
        <div id="edit-repertorio" class="edit-mode edit-mode-hidden">
            <div style="display: flex; flex-direction: column; gap: 8px;">
                <?php foreach ($allSongs as $song): 
                    $isSelected = in_array($song['id'], $songIds);
                ?>
                    <label style="
                        display: flex; align-items: center; gap: 12px; padding: 12px;
                        background: var(--bg-surface); border-radius: 12px;
                        border: 2px solid <?= $isSelected ? 'var(--primary)' : 'var(--border-color)' ?>;
                        cursor: pointer; transition: all 0.2s;
                    " class="song-item" data-song-id="<?= $song['id'] ?>">
                        <input type="checkbox" 
                               <?= $isSelected ? 'checked' : '' ?>
                               onchange="toggleSong(<?= $song['id'] ?>, this)"
                               style="width: 20px; height: 20px; accent-color: var(--primary); cursor: pointer;">
                        <div style="flex: 1;">
                            <div style="font-weight: 600; font-size: 0.9rem; color: var(--text-main);"><?= htmlspecialchars($song['title']) ?></div>
                            <div style="font-size: 0.75rem; color: var(--text-muted); margin-top: 2px;"><?= htmlspecialchars($song['artist']) ?></div>
                            <?php if ($song['tone']): ?>
                                <div style="margin-top: 6px;">
                                    <span style="background: #fff7ed; color: #ea580c; padding: 2px 8px; border-radius: 6px; font-size: 0.7rem; font-weight: 700;">
                                        TOM: <?= $song['tone'] ?>
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

<script>
let editMode = false;

function toggleEditMode() {
    editMode = !editMode;
    const editBtn = document.getElementById('editBtn');
    
    if (editMode) {
        // Entrar em modo edição
        document.querySelectorAll('.view-mode').forEach(el => el.classList.add('view-mode-hidden'));
        document.querySelectorAll('.edit-mode').forEach(el => el.classList.remove('edit-mode-hidden'));
        editBtn.style.background = 'linear-gradient(135deg, #ef4444, #dc2626)';
        editBtn.innerHTML = '<i data-lucide="x" style="width: 24px;"></i>';
    } else {
        // Voltar para visualização
        document.querySelectorAll('.view-mode').forEach(el => el.classList.remove('view-mode-hidden'));
        document.querySelectorAll('.edit-mode').forEach(el => el.classList.add('edit-mode-hidden'));
        editBtn.style.background = 'linear-gradient(135deg, #8b5cf6, #7c3aed)';
        editBtn.innerHTML = '<i data-lucide="edit-2" style="width: 24px;"></i>';
        
        // Recarregar página para atualizar visualização
        location.reload();
    }
    
    // Re-render Lucide icons
    if (typeof lucide !== 'undefined') {
        lucide.createIcons();
    }
}

function toggleMember(userId, checkbox) {
    const label = checkbox.closest('.member-item');
    
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
    const label = checkbox.closest('.song-item');
    
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
