<?php
// admin/musica_detalhe.php
require_once '../includes/db.php';
require_once '../includes/layout.php';

// Helper: detecta plataforma pelo URL e retorna [label, color, bg, icon_svg]
function detectPlatform(string $url, string $type): array {
    $url = strtolower($url);
    if ($type === 'audio') {
        if (str_contains($url, 'spotify')) return ['Spotify',   '#1db954', '#f0fdf4', '<path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm4.65 14.42c-.2.32-.62.42-.94.22-2.58-1.58-5.83-1.93-9.65-1.06-.37.08-.73-.15-.81-.52-.08-.37.15-.73.52-.81 4.18-.95 7.76-.54 10.66 1.23.32.2.42.62.22.94zm1.24-2.76c-.25.4-.78.52-1.18.27-2.95-1.81-7.45-2.34-10.94-1.28-.45.14-.93-.12-1.07-.57-.14-.45.12-.93.57-1.07 3.99-1.21 8.96-.62 12.35 1.46.4.25.52.78.27 1.19zm.11-2.87C14.25 8.85 8.84 8.68 5.82 9.57c-.54.16-1.11-.14-1.27-.68-.16-.54.14-1.11.68-1.27 3.48-1.05 9.27-.85 12.93 1.38.47.28.62.89.34 1.36-.28.47-.89.62-1.36.34z"/>'];
        if (str_contains($url, 'deezer'))  return ['Deezer',    '#a238ff', '#faf5ff', '<path d="M18.81 11.38H22v1.88h-3.19v-1.88zm-4.57 0h3.19v1.88h-3.19v-1.88zM2 11.38h3.19v1.88H2v-1.88zm4.57 0h3.19v1.88H6.57v-1.88zm4.58 0h3.19v1.88h-3.19v-1.88zM18.81 8H22v1.88h-3.19V8zm-4.57 0h3.19v1.88h-3.19V8zm-9.15 3.38H8.28v1.88H5.09v-1.88zm0-3.38H8.28v1.88H5.09V8zm4.57 3.38h3.19v1.88h-3.19v-1.88zM9.66 8h3.19v1.88H9.66V8zm0 6.75h3.19v1.88H9.66v-1.88zm4.58 0h3.19v1.88h-3.19v-1.88z"/>'];
        return ['Áudio',    '#64748b', '#f8fafc', '<path d="M9 18V5l12-2v13M6 15H3a1 1 0 0 0-1 1v2a1 1 0 0 0 1 1h3a1 1 0 0 0 1-1v-2a1 1 0 0 0-1-1zm12-2h-3a1 1 0 0 0-1 1v2a1 1 0 0 0 1 1h3a1 1 0 0 0 1-1v-2a1 1 0 0 0-1-1z"/>'];
    }
    if ($type === 'video') {
        if (str_contains($url, 'youtube') || str_contains($url, 'youtu.be'))
            return ['YouTube',  '#ff0000', '#fff5f5', '<path d="M22.54 6.42a2.78 2.78 0 0 0-1.95-1.96C18.88 4 12 4 12 4s-6.88 0-8.59.46A2.78 2.78 0 0 0 1.46 6.42 29 29 0 0 0 1 12a29 29 0 0 0 .46 5.58 2.78 2.78 0 0 0 1.95 1.95C5.12 20 12 20 12 20s6.88 0 8.59-.47a2.78 2.78 0 0 0 1.95-1.95A29 29 0 0 0 23 12a29 29 0 0 0-.46-5.58zM9.75 15.02V8.98L15.5 12l-5.75 3.02z"/>'];
        return ['Vídeo',    '#64748b', '#f8fafc', '<path d="M22 8s-2.76-3-6-3H4a2 2 0 0 0-2 2v10a2 2 0 0 0 2 2h12c3.24 0 6-3 6-3V8zM16 12l-5 3V9l5 3z"/>'];
    }
    if ($type === 'cifra')  return ['Cifra Club', '#f97316', '#fff7ed', '<path d="M9 18h6M7 22h10M12 2a10 10 0 1 0 0 20 10 10 0 0 0 0-20zm0 6v4l3 3"/>'];
    // letra
    return ['Letras',    '#6366f1', '#eef2ff', '<path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8l-6-6zm-1 1.5L18.5 9H13V3.5zM8 13h8v1.5H8V13zm0 3h8v1.5H8V16zm0-6h3v1.5H8V10z"/>'];
}

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
            <button onclick="confirmDeleteSong()" class="dropdown-item danger">
                <i data-lucide="trash-2" width="16"></i> Excluir
            </button>
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
                <div class="info-box" style="background:#eff6ff;border:1.5px solid #3b82f6;border-radius:10px;padding:12px;text-align:center;">
                    <div style="font-size:.7rem;font-weight:700;text-transform:uppercase;letter-spacing:.05em;color:#6b7280;margin-bottom:4px;">Tom Original</div>
                    <div style="font-size:1.4rem;font-weight:800;color:#111827;"><?= $song['tone'] ?: '-' ?></div>
                </div>
                <div class="info-box" style="background:#fff7ed;border:1.5px solid #f97316;border-radius:10px;padding:12px;text-align:center;">
                    <div style="font-size:.7rem;font-weight:700;text-transform:uppercase;letter-spacing:.05em;color:#6b7280;margin-bottom:4px;">BPM</div>
                    <div style="font-size:1.4rem;font-weight:800;color:#111827;"><?= $song['bpm'] ?: '-' ?></div>
                </div>
                <div class="info-box" style="background:#f0fdf4;border:1.5px solid #10b981;border-radius:10px;padding:12px;text-align:center;">
                    <div style="font-size:.7rem;font-weight:700;text-transform:uppercase;letter-spacing:.05em;color:#6b7280;margin-bottom:4px;">Duração</div>
                    <div style="font-size:1.4rem;font-weight:800;color:#111827;"><?= $song['duration'] ?: '-' ?></div>
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
<?php
$linkDefs = [
    ['url' => $song['link_letra'],  'type' => 'letra'],
    ['url' => $song['link_cifra'],  'type' => 'cifra'],
    ['url' => $song['link_audio'],  'type' => 'audio'],
    ['url' => $song['link_video'],  'type' => 'video'],
];
foreach ($linkDefs as $lnk):
    $hasUrl  = !empty($lnk['url']);
    [$label, $color, $bg, $svgPath] = detectPlatform($hasUrl ? $lnk['url'] : '', $lnk['type']);
    $href    = $hasUrl ? htmlspecialchars($lnk['url']) : '#';
    $opacity = $hasUrl ? '1' : '0.45';
?>
        <a href="<?= $href ?>" <?= $hasUrl ? 'target="_blank" rel="noopener"' : 'onclick="return false"' ?>
           style="display:flex;align-items:center;gap:12px;padding:14px 16px;
                  border-radius:12px;border:1.5px solid <?= $hasUrl ? $color : '#e5e7eb' ?>;
                  background:<?= $bg ?>;text-decoration:none;opacity:<?= $opacity ?>;
                  transition:box-shadow .15s;min-height:56px;"
           <?= $hasUrl ? 'onmouseover="this.style.boxShadow=\'0 4px 12px rgba(0,0,0,.12)\'" onmouseout="this.style.boxShadow=\'none\'"' : '' ?>>
            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"
                 fill="<?= $lnk['type'] === 'audio' && str_contains(strtolower($lnk['url'] ?? ''), 'spotify') ? $color : 'none' ?>"
                 stroke="<?= in_array($lnk['type'], ['letra','cifra','video']) || !str_contains(strtolower($lnk['url'] ?? ''), 'spotify') ? $color : 'none' ?>"
                 stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <?= $svgPath ?>
            </svg>
            <div style="flex:1;">
                <div style="font-size:.9rem;font-weight:700;color:<?= $color ?>;"><?= $label ?></div>
                <div style="font-size:.75rem;color:#6b7280;"><?= $hasUrl ? 'Acessar →' : 'Não cadastrado' ?></div>
            </div>
            <?php if ($hasUrl): ?>
            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24"
                 fill="none" stroke="#9ca3af" stroke-width="2">
                <path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/>
                <polyline points="15 3 21 3 21 9"/><line x1="10" y1="14" x2="21" y2="3"/>
            </svg>
            <?php endif; ?>
        </a>
<?php endforeach; ?>
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
<div id="toneModal" class="modal-overlay" onclick="if(event.target === this) closeToneModal()">
    <div class="modal-card" style="max-width: 400px;">
        <div class="modal-header">
            <h3 class="modal-title">
                <i data-lucide="plus-circle" width="18"></i> Adicionar Tom por Voz
            </h3>
            <button type="button" class="modal-close" onclick="closeToneModal()">
                <i data-lucide="x" width="20"></i>
            </button>
        </div>
        
        <form method="POST">
            <div class="modal-body">
                <input type="hidden" name="action" value="add_tone">
                
                <div class="form-group">
                    <label class="form-label">Membro</label>
                    <select name="user_id" required class="form-control">
                        <?php foreach ($usersList as $u): ?>
                            <option value="<?= $u['id'] ?>"><?= htmlspecialchars($u['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label class="form-label">Tom</label>
                    <select name="tone" required class="form-control">
                        <?php foreach ($musicTones as $val => $label): ?>
                            <option value="<?= $val ?>" <?= $val == ($song['tone'] ?? 'C') ? 'selected' : '' ?>><?= $label ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label class="form-label">Observação</label>
                    <textarea name="observation" class="form-control" rows="3" placeholder="Ex: Usa capo na 2ª casa..."></textarea>
                </div>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-ghost" onclick="closeToneModal()">Cancelar</button>
                <button type="submit" class="btn btn-primary">Salvar</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal Delete Song -->
<div id="deleteSongModal" class="modal-overlay" onclick="if(event.target === this) closeDeleteSongModal()">
    <div class="modal-card" style="max-width: 400px;">
        <div class="modal-header">
            <h3 class="modal-title" style="color: var(--danger);">
                <i data-lucide="alert-triangle" width="20"></i> Excluir Música
            </h3>
            <button type="button" class="modal-close" onclick="closeDeleteSongModal()">
                <i data-lucide="x" width="20"></i>
            </button>
        </div>
        <div class="modal-body">
            <p>Tem certeza que deseja excluir <strong><?= htmlspecialchars($song['title']) ?></strong>?</p>
            <p class="text-secondary" style="font-size: 0.9em; margin-top: 8px;">Isso removerá também todo o histórico de tons pessoais e tags associadas.</p>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-ghost" onclick="closeDeleteSongModal()">Cancelar</button>
            <form method="POST" style="margin:0;">
                <input type="hidden" name="action" value="delete_song">
                <button type="submit" class="btn btn-danger">Excluir Permanentemente</button>
            </form>
        </div>
    </div>
</div>

<script>
    function openToneModal() {
        document.getElementById('toneModal').classList.add('active');
    }
    function closeToneModal() {
        document.getElementById('toneModal').classList.remove('active');
    }

    function confirmDeleteSong() {
        document.getElementById('deleteSongModal').classList.add('active');
        // Fecha o menu dropdown se estiver aberto
        const menu = document.getElementById('dropdown-menu');
        if (menu) menu.style.display = 'none';
    }

    function closeDeleteSongModal() {
        document.getElementById('deleteSongModal').classList.remove('active');
    }
</script>

<?php renderAppFooter(); ?>