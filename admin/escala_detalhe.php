<?php
// admin/escala_detalhe.php - Versão Compacta Mobile
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
        // Verificar se já existe
        $check = $pdo->prepare("SELECT * FROM schedule_users WHERE schedule_id = ? AND user_id = ?");
        $check->execute([$id, $userId]);
        
        if ($check->fetch()) {
            // Remover
            $stmt = $pdo->prepare("DELETE FROM schedule_users WHERE schedule_id = ? AND user_id = ?");
            $stmt->execute([$id, $userId]);
            echo json_encode(['status' => 'removed']);
        } else {
            // Adicionar
            $stmt = $pdo->prepare("INSERT INTO schedule_users (schedule_id, user_id) VALUES (?, ?)");
            $stmt->execute([$id, $userId]);
            echo json_encode(['status' => 'added']);
        }
        exit;
    }
    
    if ($_POST['action'] === 'toggle_song') {
        $songId = $_POST['song_id'];
        // Verificar se já existe
        $check = $pdo->prepare("SELECT * FROM schedule_songs WHERE schedule_id = ? AND song_id = ?");
        $check->execute([$id, $songId]);
        
        if ($check->fetch()) {
            // Remover
            $stmt = $pdo->prepare("DELETE FROM schedule_songs WHERE schedule_id = ? AND song_id = ?");
            $stmt->execute([$id, $songId]);
            echo json_encode(['status' => 'removed']);
        } else {
            // Adicionar
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

// Buscar Membros
$stmtUsers = $pdo->prepare("
    SELECT su.*, u.id as user_id, u.name, u.instrument, u.photo, u.avatar_color
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
    SELECT ss.*, s.id as song_id, s.title, s.artist, s.tone
    FROM schedule_songs ss
    JOIN songs s ON ss.song_id = s.id
    WHERE ss.schedule_id = ?
    ORDER BY ss.position ASC
");
$stmtSongs->execute([$id]);
$songs = $stmtSongs->fetchAll(PDO::FETCH_ASSOC);
$songIds = array_column($songs, 'song_id');

// Buscar TODOS para seleção
$allUsers = $pdo->query("SELECT id, name, instrument, avatar_color FROM users ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);
$allSongs = $pdo->query("SELECT id, title, artist, tone FROM songs ORDER BY title")->fetchAll(PDO::FETCH_ASSOC);

renderAppHeader('Escala');
renderPageHeader($schedule['event_type'], $date->format('d/m/Y'));
?>

<!-- Info Card Compacta -->
<div style="max-width: 800px; margin: 0 auto 16px; padding: 0 16px;">
    <div style="background: var(--bg-surface); border-radius: 12px; padding: 12px; border: 1px solid var(--border-color); display: flex; align-items: center; gap: 12px;">
        <i data-lucide="calendar" style="width: 20px; color: var(--primary);"></i>
        <div style="flex: 1;">
            <div style="font-weight: 600; font-size: 0.9rem; color: var(--text-main);"><?= $date->format('d/m/Y') ?> • 19:00</div>
            <?php if ($schedule['notes']): ?>
                <div style="font-size: 0.75rem; color: var(--text-muted); margin-top: 2px;"><?= htmlspecialchars($schedule['notes']) ?></div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Tabs -->
<div style="max-width: 800px; margin: 0 auto 16px; padding: 0 16px;">
    <div style="background: var(--bg-body); padding: 4px; border-radius: 12px; display: flex; gap: 4px;">
        <button onclick="switchTab('participantes')" id="tab-participantes" class="tab-btn active" style="flex: 1; padding: 8px; border-radius: 8px; border: none; background: var(--bg-surface); color: var(--primary); font-weight: 600; font-size: 0.85rem; cursor: pointer; transition: all 0.2s;">
            Participantes
        </button>
        <button onclick="switchTab('repertorio')" id="tab-repertorio" class="tab-btn" style="flex: 1; padding: 8px; border-radius: 8px; border: none; background: transparent; color: var(--text-muted); font-weight: 600; font-size: 0.85rem; cursor: pointer; transition: all 0.2s;">
            Repertório
        </button>
    </div>
</div>

<!-- Content -->
<div style="max-width: 800px; margin: 0 auto; padding: 0 16px 100px;">
    
    <!-- Tab Participantes -->
    <div id="content-participantes" class="tab-content">
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
    
    <!-- Tab Repertório -->
    <div id="content-repertorio" class="tab-content" style="display: none;">
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

<script>
function switchTab(tab) {
    // Update tabs
    document.querySelectorAll('.tab-btn').forEach(btn => {
        btn.style.background = 'transparent';
        btn.style.color = 'var(--text-muted)';
        btn.classList.remove('active');
    });
    document.getElementById('tab-' + tab).style.background = 'var(--bg-surface)';
    document.getElementById('tab-' + tab).style.color = 'var(--primary)';
    document.getElementById('tab-' + tab).classList.add('active');
    
    // Update content
    document.querySelectorAll('.tab-content').forEach(content => {
        content.style.display = 'none';
    });
    document.getElementById('content-' + tab).style.display = 'block';
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
