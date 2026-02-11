<?php
// admin/musica_detalhe.php
require_once '../includes/db.php';
require_once '../includes/layout.php';

$id = $_GET['id'] ?? null;

if (!$id) {
    header('Location: repertorio.php');
    exit;
}

// Processar exclusão
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete_song') {
    try {
        // Deletar tags associadas primeiro
        $stmtDelTags = $pdo->prepare("DELETE FROM song_tags WHERE song_id = ?");
        $stmtDelTags->execute([$id]);
        
        // Deletar a música
        $stmtDel = $pdo->prepare("DELETE FROM songs WHERE id = ?");
        $stmtDel->execute([$id]);
        
        header('Location: repertorio.php');
        exit;
    } catch (Exception $e) {
        die("Erro ao excluir música: " . $e->getMessage());
    }
}

// Buscar Música
$stmt = $pdo->prepare("SELECT * FROM songs WHERE id = ?");
$stmt->execute([$id]);
$song = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$song) {
    die("Música não encontrada.");
}

// --- LÓGICA DE POST: PERSONAL TONES ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'add_tone') {
        $stmtInsert = $pdo->prepare("INSERT INTO song_personal_tones (song_id, user_id, tone, observation) VALUES (?, ?, ?, ?)");
        $stmtInsert->execute([$id, $_POST['user_id'], $_POST['tone'], $_POST['observation']]);
        header("Location: musica_detalhe.php?id=$id");
        exit;
    } elseif ($_POST['action'] === 'delete_tone') {
        $stmtDelete = $pdo->prepare("DELETE FROM song_personal_tones WHERE id = ?");
        $stmtDelete->execute([$_POST['tone_id']]);
        header("Location: musica_detalhe.php?id=$id");
        exit;
    }
}

// Buscar Tags
$stmtTags = $pdo->prepare("
    SELECT t.* 
    FROM tags t 
    JOIN song_tags st ON st.tag_id = t.id 
    WHERE st.song_id = ?
");
$stmtTags->execute([$id]);
$tags = $stmtTags->fetchAll(PDO::FETCH_ASSOC);

renderAppHeader('Detalhes da Música');
?>

<!-- Import CSS -->
<link rel="stylesheet" href="../assets/css/pages/musica-detalhe.css?v=<?= time() ?>">

<div class="music-detail-wrapper">

    <?php
    // --- QUERIES ADICIONAIS ---

    // 1. Tons Pessoais
    $stmtTones = $pdo->prepare("
        SELECT spt.*, u.name, u.avatar 
        FROM song_personal_tones spt 
        JOIN users u ON spt.user_id = u.id 
        WHERE spt.song_id = ?
        ORDER BY u.name
    ");
    $stmtTones->execute([$id]);
    $personalTones = $stmtTones->fetchAll(PDO::FETCH_ASSOC);

    // 2. Todos os Usuários (para o modal)
    $stmtUsers = $pdo->query("SELECT id, name FROM users ORDER BY name");
    $usersList = $stmtUsers->fetchAll(PDO::FETCH_ASSOC);
    
    // 3. Histórico de Execuções e Status
    $stmtHistory = $pdo->prepare("SELECT s.event_date FROM schedule_songs ss JOIN schedules s ON ss.schedule_id = s.id WHERE ss.song_id = ? ORDER BY s.event_date DESC");
    $stmtHistory->execute([$id]);
    $history = $stmtHistory->fetchAll(PDO::FETCH_ASSOC);

    // Cálculo de Status
    $totalExecs = count($history);
    $lastPlayed = $history[0]['event_date'] ?? null;
    $count90Days = 0;
    $today = new DateTime();
    $threeMonthsAgo = (clone $today)->modify('-3 months');

    // Estatísticas Anuais
    $yearsStats = [];
    foreach ($history as $h) {
        $dateObj = new DateTime($h['event_date']);
        if ($dateObj >= $threeMonthsAgo) {
            $count90Days++;
        }
        $year = $dateObj->format('Y');
        if (!isset($yearsStats[$year])) {
            $yearsStats[$year] = 0;
        }
        $yearsStats[$year]++;
    }

    // Definição do Status Visual
    $statusLabel = 'Normal';
    $statusColor = 'var(--text-secondary)'; 
    $statusDesc = 'Frequência equilbrada';
    $statusIcon = 'check-circle';

    if ($totalExecs == 0) {
        $statusLabel = 'Nunca Tocada';
        $statusColor = 'var(--yellow-600)';
        $statusDesc = 'Oportunidade de novidade!';
        $statusIcon = 'sparkles';
    } else {
        $lastDate = new DateTime($lastPlayed);
        $diff = $today->diff($lastDate);
        $monthsDiff = ($diff->y * 12) + $diff->m;

        if ($count90Days >= 3) {
            $statusLabel = 'Alta Rotatividade';
            $statusColor = 'var(--red-600)';
            $statusDesc = 'Cuidado para não "cansar" a igreja.';
            $statusIcon = 'flame';
        } elseif ($monthsDiff >= 3 && $monthsDiff <= 6) {
            $statusLabel = 'Geladeira';
            $statusColor = 'var(--blue-600)';
            $statusDesc = 'Ótima hora para reintroduzir!';
            $statusIcon = 'snowflake';
        } elseif ($monthsDiff > 6) {
            $statusLabel = 'Esquecida';
            $statusColor = 'var(--text-tertiary)';
            $statusDesc = 'Mais de 6 meses sem tocar.';
            $statusIcon = 'archive';
        }
    }
    
    // Lista de Tons Musicais
    $musicTones = [
        'C' => 'C (Dó)', 'C#' => 'C# (Dó Sustenido)', 'D' => 'D (Ré)',
        'D#' => 'D# (Ré Sustenido)', 'E' => 'E (Mi)', 'F' => 'F (Fá)',
        'F#' => 'F# (Fá Sustenido)', 'G' => 'G (Sol)', 'G#' => 'G# (Sol Sustenido)',
        'A' => 'A (Lá)', 'A#' => 'A# (Lá Sustenido)', 'B' => 'B (Si)',
        'Cm' => 'Cm (Dó Menor)', 'C#m' => 'C#m (Dó Sustenido Menor)',
        'Dm' => 'Dm (Ré Menor)', 'D#m' => 'D#m (Ré Sustenido Menor)',
        'Em' => 'Em (Mi Menor)', 'Fm' => 'Fm (Fá Menor)',
        'F#m' => 'F#m (Fá Sustenido Menor)', 'Gm' => 'Gm (Sol Menor)',
        'G#m' => 'G#m (Sol Sustenido Menor)', 'Am' => 'Am (Lá Menor)',
        'A#m' => 'A#m (Lá Sustenido Menor)', 'Bm' => 'Bm (Si Menor)'
    ];

    // Menu de Ações (Dropdown)
    $menuActions = '
    <div style="position: relative;">
        <button onclick="toggleMenu()" class="action-menu-btn">
            <i data-lucide="more-vertical" width="20"></i>
        </button>
        <div id="dropdown-menu" class="dropdown-menu">
            <a href="musica_editar.php?id=' . $id . '" class="dropdown-item">
                <i data-lucide="edit-3" width="16"></i> Editar
            </a>
            <form method="POST" onsubmit="return confirm(\'Excluir música permanentemente?\')" style="margin: 0;">
                <input type="hidden" name="action" value="delete_song">
                <button type="submit" class="dropdown-item danger">
                    <i data-lucide="trash-2" width="16"></i> Excluir
                </button>
            </form>
        </div>
    </div>
    <script>
    function toggleMenu() {
        const menu = document.getElementById(\'dropdown-menu\');
        menu.style.display = menu.style.display === \'block\' ? \'none\' : \'block\';
    }
    document.addEventListener(\'click\', function(e) {
        const menu = document.getElementById(\'dropdown-menu\');
        const btn = document.querySelector(\'[onclick="toggleMenu()"]\');
        if (menu.style.display === \'block\' && !menu.contains(e.target) && !btn.contains(e.target)) {
            menu.style.display = \'none\';
        }
    });
    </script>
    ';

    renderPageHeader('Detalhes da Música', '', $menuActions);
    ?>

    <!-- Header Centralizado -->
    <div class="song-header-center">
        <div class="sh-icon">
            <i data-lucide="music" width="32"></i>
        </div>
        <h1 class="sh-title"><?= htmlspecialchars($song['title']) ?></h1>
        
        <div class="sh-meta">
            <a href="repertorio.php?q=<?= urlencode($song['artist']) ?>" class="sh-link">
                <?= htmlspecialchars($song['artist']) ?> <i data-lucide="external-link" width="14"></i>
            </a>

            <span style="color: var(--text-tertiary);">•</span>

            <span class="sh-badge" style="color: <?= $statusColor ?>; border-color: <?= $statusColor ?>">
                <i data-lucide="<?= $statusIcon ?>" width="14"></i> <?= $statusLabel ?>
            </span>

            <span style="color: var(--text-tertiary);">•</span>

            <span style="display: flex; align-items: center; gap: 6px;" title="Última vez: <?= $lastPlayed ? (new DateTime($lastPlayed))->format('d/m/Y') : 'Nunca' ?>">
                <i data-lucide="play-circle" width="16"></i> <?= $totalExecs ?> execuções
            </span>
        </div>
    </div>

    <!-- Tabs Navigation -->
    <div class="tabs-container">
        <button class="tab-btn active" onclick="switchTab('info')">Visão Geral</button>
        <button class="tab-btn" onclick="switchTab('tones')">Tons</button>
        <button class="tab-btn" onclick="switchTab('refs')">Referências</button>
    </div>

    <!-- TAB 1: Visão Geral -->
    <div id="tab-info" class="tab-panel active">
        <div class="panel-card">
            <!-- Grid de Metricas -->
            <div class="info-grid-row">
                <div class="info-box">
                    <label>Tom Original</label>
                    <value><?= $song['tone'] ?: '-' ?></value>
                </div>
                <div class="info-box">
                    <label>BPM</label>
                    <value><?= $song['bpm'] ?: '-' ?></value>
                </div>
                <div class="info-box">
                    <label>Duração</label>
                    <value><?= $song['duration'] ?: '-' ?></value>
                </div>
            </div>

            <!-- Classificações -->
            <div style="text-align: center;">
                <div class="classifications-title">Classificações</div>
                <?php if (!empty($tags)): ?>
                    <div class="tags-wrapper">
                        <?php foreach ($tags as $tag): 
                            $tagColor = $tag['color'] ?? 'var(--slate-600)';
                        ?>
                            <span class="tag-badge">
                                <?= htmlspecialchars($tag['name']) ?>
                            </span>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <span style="color: var(--text-muted); font-weight: 500;">Nenhuma tag cadastrada</span>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- TAB 2: Tons -->
    <div id="tab-tones" class="tab-panel">
        <div class="panel-card">
            <div class="tones-header">
                <h3 class="tones-title">Tons por Voz</h3>
                <button onclick="openToneModal()" class="btn-add-tone">
                    <i data-lucide="plus" width="16"></i> Adicionar
                </button>
            </div>

            <?php if (!empty($personalTones)): ?>
                <div class="tone-list">
                    <?php foreach ($personalTones as $pt): ?>
                        <div class="tone-item">
                            <div class="tone-user-info">
                                <div class="tone-avatar-box">
                                    <?php if ($pt['avatar']): ?>
                                        <img src="<?= htmlspecialchars($pt['avatar']) ?>" style="width: 100%; height: 100%; object-fit: cover;">
                                    <?php else: ?>
                                        <?= strtoupper(substr($pt['name'], 0, 1)) ?>
                                    <?php endif; ?>
                                </div>
                                <div>
                                    <div class="tone-user-name"><?= htmlspecialchars($pt['name']) ?></div>
                                    <?php if ($pt['observation']): ?>
                                        <div class="tone-obs"><?= htmlspecialchars($pt['observation']) ?></div>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div class="tone-actions">
                                <span class="tone-badge"><?= htmlspecialchars($pt['tone']) ?></span>
                                <form method="POST" onsubmit="return confirm('Remover tom?')" style="margin: 0; display: flex;">
                                    <input type="hidden" name="action" value="delete_tone">
                                    <input type="hidden" name="tone_id" value="<?= $pt['id'] ?>">
                                    <button type="submit" class="btn-icon-danger"><i data-lucide="trash-2" width="18"></i></button>
                                </form>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="empty-state">
                    <i data-lucide="mic-off" class="empty-icon"></i>
                    <p>Nenhum tom pessoal cadastrado ainda.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- TAB 3: Referências -->
    <div id="tab-refs" class="tab-panel">
        <div class="panel-card">
            <div class="links-grid">
                <a href="<?= $song['link_letra'] ?: '#' ?>" target="_blank" class="link-card">
                    <div class="link-card-icon icon-orange"><i data-lucide="file-text" width="24"></i></div>
                    <div class="link-card-content">
                        <div class="link-card-title">Letra</div>
                        <div class="link-card-status"><?= $song['link_letra'] ? 'Acessar Link' : 'Não cadastrado' ?></div>
                    </div>
                    <?php if($song['link_letra']): ?><i data-lucide="external-link" width="16" style="color: var(--text-tertiary);"></i><?php endif; ?>
                </a>
                
                <a href="<?= $song['link_cifra'] ?: '#' ?>" target="_blank" class="link-card">
                    <div class="link-card-icon icon-green"><i data-lucide="music" width="24"></i></div>
                    <div class="link-card-content">
                        <div class="link-card-title">Cifra</div>
                        <div class="link-card-status"><?= $song['link_cifra'] ? 'Acessar Link' : 'Não cadastrado' ?></div>
                    </div>
                     <?php if($song['link_cifra']): ?><i data-lucide="external-link" width="16" style="color: var(--text-tertiary);"></i><?php endif; ?>
                </a>
                
                <a href="<?= $song['link_audio'] ?: '#' ?>" target="_blank" class="link-card">
                    <div class="link-card-icon icon-slate"><i data-lucide="headphones" width="24"></i></div>
                    <div class="link-card-content">
                        <div class="link-card-title">Áudio</div>
                        <div class="link-card-status"><?= $song['link_audio'] ? 'Acessar Link' : 'Não cadastrado' ?></div>
                    </div>
                     <?php if($song['link_audio']): ?><i data-lucide="external-link" width="16" style="color: var(--text-tertiary);"></i><?php endif; ?>
                </a>
                
                <a href="<?= $song['link_video'] ?: '#' ?>" target="_blank" class="link-card">
                    <div class="link-card-icon icon-rose"><i data-lucide="video" width="24"></i></div>
                    <div class="link-card-content">
                        <div class="link-card-title">Vídeo</div>
                        <div class="link-card-status"><?= $song['link_video'] ? 'Acessar Link' : 'Não cadastrado' ?></div>
                    </div>
                     <?php if($song['link_video']): ?><i data-lucide="external-link" width="16" style="color: var(--text-tertiary);"></i><?php endif; ?>
                </a>
            </div>
        </div>
    </div>

    <!-- Script Tabs -->
    <script>
        function switchTab(tabName) {
            // Remove active class from buttons
            document.querySelectorAll('.tab-btn').forEach(btn => btn.classList.remove('active'));
            // Remove active class from panels
            document.querySelectorAll('.tab-panel').forEach(panel => panel.classList.remove('active'));

            // Activate button (naive logic)
            document.querySelector(`[onclick="switchTab('${tabName}')"]`).classList.add('active');

            // Activate panel
            document.getElementById(`tab-${tabName}`).classList.add('active');
        }
    </script>

</div>

<!-- Modal Add Tone -->
<div id="toneModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 1000; backdrop-filter: blur(2px);" onclick="if(event.target === this) closeToneModal()">
    <div style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); width: 90%; max-width: 400px; background: var(--bg-surface); border-radius: 16px; padding: 24px; box-shadow: var(--shadow-xl);">
        <h3 style="margin: 0 0 16px 0; font-size: 1.25rem; color: var(--text-primary); font-weight: 700;">Adicionar Tom por Voz</h3>
        
        <form method="POST">
            <input type="hidden" name="action" value="add_tone">
            
            <div style="margin-bottom: 16px;">
                <label style="display: block; margin-bottom: 8px; font-weight: 600; font-size: 0.9rem; color: var(--text-secondary);">Membro</label>
                <select name="user_id" required style="width: 100%; padding: 10px; border-radius: 8px; border: 1px solid var(--border-medium); background: var(--bg-input); color: var(--text-primary);">
                    <?php foreach ($usersList as $u): ?>
                        <option value="<?= $u['id'] ?>"><?= htmlspecialchars($u['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div style="margin-bottom: 16px;">
                <label style="display: block; margin-bottom: 8px; font-weight: 600; font-size: 0.9rem; color: var(--text-secondary);">Tom</label>
                <select name="tone" required style="width: 100%; padding: 10px; border-radius: 8px; border: 1px solid var(--border-medium); background: var(--bg-input); color: var(--text-primary);">
                    <?php foreach ($musicTones as $val => $label): ?>
                        <option value="<?= $val ?>" <?= $val == ($song['tone'] ?? 'C') ? 'selected' : '' ?>><?= $label ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div style="margin-bottom: 20px;">
                <label style="display: block; margin-bottom: 8px; font-weight: 600; font-size: 0.9rem; color: var(--text-secondary);">Observação</label>
                <textarea name="observation" rows="3" placeholder="Ex: Usa capo na 2ª casa..." style="width: 100%; padding: 10px; border-radius: 8px; border: 1px solid var(--border-medium); background: var(--bg-input); color: var(--text-primary); font-family: inherit; resize: vertical;"></textarea>
            </div>

            <div style="display: flex; gap: 12px;">
                <button type="button" onclick="closeToneModal()" style="flex: 1; padding: 12px; border-radius: 8px; border: 1px solid var(--border-medium); background: transparent; font-weight: 600; cursor: pointer; color: var(--text-secondary);">Cancelar</button>
                <button type="submit" style="flex: 2; padding: 12px; border-radius: 8px; border: none; background: var(--primary); color: white; font-weight: 700; cursor: pointer;">Salvar</button>
            </div>
        </form>
    </div>
</div>

<script>
    function openToneModal() {
        document.getElementById('toneModal').style.display = 'block';
    }
    function closeToneModal() {
        document.getElementById('toneModal').style.display = 'none';
    }
</script>

<?php renderAppFooter(); ?>