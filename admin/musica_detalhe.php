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
<style>
    /* Estilos para Detalhes da Música */
    .info-section {
        background: var(--bg-surface);
        border: 1px solid var(--border-color);
        border-radius: var(--radius-lg);
        padding: 24px;
        margin-bottom: 32px;
        box-shadow: var(--shadow-sm);
    }

    .info-section-title {
        font-size: var(--font-body-sm);
        font-weight: 700;
        color: var(--text-muted);
        text-transform: uppercase;
        letter-spacing: 0.5px;
        margin-bottom: 12px;
    }

    .info-grid {
        display: grid;
        grid-template-columns: 1fr 1fr 1fr;
        gap: 12px;
    }

    .info-item {
        text-align: center;
        padding: 12px;
        background: var(--bg-body);
        border-radius: 8px;
    }

    .info-label {
        font-size: var(--font-caption);
        color: var(--text-muted);
        margin-bottom: 4px;
    }

    .info-value {
        font-size: var(--font-h3);
        font-weight: 700;
        color: var(--primary);
    }

    .link-item {
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 14px;
        background: var(--bg-body);
        border-radius: 10px;
        margin-bottom: 8px;
        text-decoration: none;
        transition: all 0.2s;
        border: 1px solid transparent;
    }

    .link-item:hover {
        background: var(--bg-surface);
        border-color: var(--primary);
    }

    .link-icon {
        width: 40px;
        height: 40px;
        border-radius: 8px;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
    }

    .link-info {
        flex: 1;
    }

    .link-title {
        font-weight: 700;
        color: var(--text-main);
        font-size: var(--font-body);
    }

    .link-url {
        font-size: var(--font-caption);
        color: var(--text-muted);
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }
</style>

<div style="max-width: 800px; margin: 0 auto; padding: 0 16px;">

    <?php
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
    $statusColor = '#10b981'; // Emerald
    $statusDesc = 'Frequência equilbrada';
    $statusIcon = 'check-circle';

    if ($totalExecs == 0) {
        $statusLabel = 'Nunca Tocada';
        $statusColor = '#f59e0b'; // Amber
        $statusDesc = 'Oportunidade de novidade!';
        $statusIcon = 'sparkles';
    } else {
        $lastDate = new DateTime($lastPlayed);
        $diff = $today->diff($lastDate);
        $monthsDiff = ($diff->y * 12) + $diff->m;

        if ($count90Days >= 3) {
            $statusLabel = 'Alta Rotatividade';
            $statusColor = '#ef4444'; // Red
            $statusDesc = 'Cuidado para não "cansar" a igreja.';
            $statusIcon = 'flame';
        } elseif ($monthsDiff >= 3 && $monthsDiff <= 6) {
            $statusLabel = 'Geladeira';
            $statusColor = '#3b82f6'; // Blue
            $statusDesc = 'Ótima hora para reintroduzir!';
            $statusIcon = 'snowflake';
        } elseif ($monthsDiff > 6) {
            $statusLabel = 'Esquecida';
            $statusColor = '#64748b'; // Slate
            $statusDesc = 'Mais de 6 meses sem tocar.';
            $statusIcon = 'archive';
        }
    }
    
    // Lista de Tons Musicais
    $musicTones = ['C', 'C#', 'D', 'D#', 'E', 'F', 'F#', 'G', 'G#', 'A', 'A#', 'B', 'Cm', 'C#m', 'Dm', 'D#m', 'Em', 'Fm', 'F#m', 'Gm', 'G#m', 'Am', 'A#m', 'Bm'];    

    // Menu de Ações (Dropdown)
    $menuActions = '
<div style="position: relative;">
    <button onclick="toggleMenu()" class="ripple" style="
        width: 40px; height: 40px;
        background: transparent;
        border: none;
        border-radius: 50%;
        color: #64748b;
        display: flex; align-items: center; justify-content: center;
        cursor: pointer;
    ">
        <i data-lucide="more-vertical" style="width: 20px;"></i>
    </button>
    <div id="dropdown-menu" style="
        display: none;
        position: absolute;
        top: 100%; right: 0;
        background: var(--bg-surface);
        border: 1px solid var(--border-color);
        border-radius: 12px;
        box-shadow: var(--shadow-xl);
        z-index: 50;
        min-width: 160px;
        overflow: hidden;
    ">
        <a href="musica_editar.php?id=' . $id . '" class="ripple" style="
            display: flex; align-items: center; gap: 10px;
            padding: 12px 16px;
            text-decoration: none;
            color: #1e293b;
            font-size: var(--font-body-sm);
            font-weight: 500;
            transition: background 0.2s;
        " onmouseover="this.style.backgroundColor=\'#f1f5f9\'" onmouseout="this.style.backgroundColor=\'transparent\'">
            <i data-lucide="edit-3" style="width: 16px;"></i> Editar
        </a>
        <form method="POST" onsubmit="return confirm(\'Excluir música permanentemente?\')" style="margin: 0;">
            <input type="hidden" name="action" value="delete_song">
            <button type="submit" class="ripple" style="
                width: 100%;
                display: flex; align-items: center; gap: 10px;
                padding: 12px 16px;
                border: none;
                background: transparent;
                color: #ef4444;
                font-size: var(--font-body-sm);
                font-weight: 500;
                cursor: pointer;
                text-align: left;
                " onmouseover="this.style.backgroundColor=\'#fef2f2\'" onmouseout="this.style.backgroundColor=\'transparent\'">
                <i data-lucide="trash-2" style="width: 16px;"></i> Excluir
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

    <style>
        .details-grid {
            display: grid;
            grid-template-columns: 1fr 380px;
            gap: 32px;
            align-items: start;
            margin-bottom: 40px;
        }

        @media (max-width: 900px) {
            .details-grid {
                grid-template-columns: 1fr;
                gap: 24px;
            }
        }

        /* Coluna Esquerda: Cabeçalho e Referências */
        .left-column {
            display: flex;
            flex-direction: column;
            gap: 32px;
        }

        .song-header {
            text-align: left;
        }

        .song-title {
            font-size: 2.5rem;
            font-weight: 800;
            color: #1e293b;
            line-height: 1.1;
            margin-bottom: 12px;
            letter-spacing: -1px;
        }

        .meta-row {
            display: flex;
            align-items: center;
            flex-wrap: wrap;
            gap: 12px;
            font-size: 1rem;
            color: #64748b;
            font-weight: 500;
        }

        .meta-link {
            color: #64748b;
            text-decoration: none;
            transition: color 0.2s;
            display: flex;
            align-items: center;
            gap: 6px;
        }
        .meta-link:hover {
            color: var(--primary);
        }

        .status-badge {
            font-size: 0.85rem;
            padding: 4px 10px;
            border-radius: 6px;
            font-weight: 700;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }

        /* Coluna Direita: Cards Unificados */
        .right-column {
            display: flex;
            flex-direction: column;
            gap: 20px;
        }

        .unified-card {
            background: var(--bg-surface);
            border: 1px solid var(--border-color);
            border-radius: 16px;
            padding: 20px;
            box-shadow: var(--shadow-sm);
        }

        .card-title {
            font-size: 0.9rem;
            font-weight: 700;
            color: #94a3b8;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 16px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .info-metrics-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 16px;
            margin-bottom: 20px;
            padding-bottom: 20px;
            border-bottom: 1px solid var(--border-color);
        }

        .metric-box {
            background: var(--bg-body);
            padding: 12px;
            border-radius: 10px;
            text-align: center;
        }

        .metric-label { font-size: 0.75rem; color: #94a3b8; margin-bottom: 4px; }
        .metric-value { font-size: 1.25rem; font-weight: 700; color: #1e293b; }

        .tags-list {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
        }

        .tag-pill {
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
            background: #f1f5f9;
            color: #475569;
        }

        /* Card Tons */
        .original-tone-box {
            display: flex;
            align-items: center;
            justify-content: space-between;
            background: #eff6ff; 
            border: 1px solid #bfdbfe;
            color: #1e40af;
            padding: 16px;
            border-radius: 12px;
            margin-bottom: 16px;
        }
        .ot-label { font-weight: 600; font-size: 0.9rem; }
        .ot-value { font-size: 1.5rem; font-weight: 800; }

    </style>

    <div class="details-grid">
        
        <!-- Esquerda: Identidade & Refs -->
        <div class="left-column">
            
            <div class="song-header">
                <!-- Icone -->
                <div style="
                    width: 56px; height: 56px; 
                    background: linear-gradient(135deg, #047857 0%, #064e3b 100%);
                    border-radius: 14px; 
                    display: flex; align-items: center; justify-content: center;
                    box-shadow: 0 4px 12px rgba(4, 120, 87, 0.2);
                    margin-bottom: 20px;
                ">
                    <i data-lucide="music" style="width: 28px; height: 28px; color: white;"></i>
                </div>

                <h1 class="song-title"><?= htmlspecialchars($song['title']) ?></h1>
                
                <div class="meta-row">
                    <a href="repertorio.php?q=<?= urlencode($song['artist']) ?>" class="meta-link">
                        <?= htmlspecialchars($song['artist']) ?>
                    </a>
                    
                    <span>•</span>
                    
                    <span class="status-badge" style="background: <?= $statusColor ?>15; color: <?= $statusColor ?>; border: 1px solid <?= $statusColor ?>20;">
                        <?= $statusLabel ?>
                    </span>
                    
                    <span>•</span>
                    
                    <span title="Última vez: <?= $lastPlayed ? (new DateTime($lastPlayed))->format('d/m/Y') : 'Nunca' ?>" style="cursor: help;">
                        <?= $totalExecs ?> execuções
                    </span>
                    
                    <?php if ($lastPlayed): ?>
                        <span style="font-size: 0.85rem; opacity: 0.7;">(Últ: <?= (new DateTime($lastPlayed))->format('d/m/y') ?>)</span>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Referências (Linkadas na Esquerda para preencher) -->
            <div>
                <h3 style="font-size: 0.9rem; font-weight: 700; color: #94a3b8; text-transform: uppercase; margin-bottom: 16px; letter-spacing: 0.5px;">Referências</h3>
                <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(180px, 1fr)); gap: 12px;">
                    <a href="<?= $song['link_letra'] ?: '#' ?>" target="_blank" class="link-item ripple">
                        <div class="link-icon" style="background: #fff7ed; color: #f97316;"><i data-lucide="file-text" width="20"></i></div>
                        <div class="link-info"><div class="link-title">Letra</div><div class="link-url"><?= $song['link_letra'] ? 'Acessar' : 'Vazio' ?></div></div>
                    </a>
                    <a href="<?= $song['link_cifra'] ?: '#' ?>" target="_blank" class="link-item ripple">
                        <div class="link-icon" style="background: #ecfdf5; color: #10b981;"><i data-lucide="music" width="20"></i></div>
                        <div class="link-info"><div class="link-title">Cifra</div><div class="link-url"><?= $song['link_cifra'] ? 'Acessar' : 'Vazio' ?></div></div>
                    </a>
                    <a href="<?= $song['link_audio'] ?: '#' ?>" target="_blank" class="link-item ripple">
                        <div class="link-icon" style="background: #eff6ff; color: #3b82f6;"><i data-lucide="headphones" width="20"></i></div>
                        <div class="link-info"><div class="link-title">Áudio</div><div class="link-url"><?= $song['link_audio'] ? 'Acessar' : 'Vazio' ?></div></div>
                    </a>
                    <a href="<?= $song['link_video'] ?: '#' ?>" target="_blank" class="link-item ripple">
                        <div class="link-icon" style="background: #fef2f2; color: #ef4444;"><i data-lucide="video" width="20"></i></div>
                        <div class="link-info"><div class="link-title">Vídeo</div><div class="link-url"><?= $song['link_video'] ? 'Acessar' : 'Vazio' ?></div></div>
                    </a>
                </div>
            </div>

        </div>

        <!-- Direita: Informações e Tonalidade -->
        <div class="right-column">
            
            <!-- Card 1: Informações Gerais (Duração, BPM, Tags) -->
            <div class="unified-card">
                <div class="card-title">Informações Gerais</div>
                
                <div class="info-metrics-grid" style="border-bottom: 1px dashed var(--border-color);">
                    <div class="metric-box">
                        <div class="metric-label">Duração</div>
                        <div class="metric-value"><?= $song['duration'] ?: '-' ?></div>
                    </div>
                    <div class="metric-box">
                        <div class="metric-label">BPM</div>
                        <div class="metric-value"><?= $song['bpm'] ?: '-' ?></div>
                    </div>
                </div>

                <div style="padding-top: 4px;">
                    <div class="metric-label" style="text-align: left; margin-bottom: 8px;">Classificações</div>
                    <div class="tags-list">
                        <?php if ($song['tags']): ?>
                            <?php foreach (explode(',', $song['tags']) as $tag): ?>
                                <span class="tag-pill"><?= htmlspecialchars(trim($tag)) ?></span>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <span style="color: #94a3b8; font-size: 0.85rem;">Sem tags</span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Card 2: Tonalidade Unificada -->
            <div class="unified-card">
                <div class="card-title">
                    Tonalidade
                    <button onclick="openToneModal()" style="font-size: 0.75rem; color: var(--primary); background: transparent; border: none; font-weight: 700; cursor: pointer; display: flex; align-items: center; gap: 4px;">
                        <i data-lucide="plus" width="14"></i> Adicionar
                    </button>
                </div>
                
                <!-- Tom Original -->
                <div class="original-tone-box">
                    <span class="ot-label">Tom Original</span>
                    <span class="ot-value"><?= $song['tone'] ?: '-' ?></span>
                </div>

                <!-- Tons Pessoais -->
                <div style="display: flex; flex-direction: column; gap: 8px;">
                    <?php if (!empty($personalTones)): ?>
                        <?php foreach ($personalTones as $pt): ?>
                            <div style="display: flex; align-items: center; justify-content: space-between; padding: 10px; background: var(--bg-body); border-radius: 8px; border: 1px solid var(--border-color);">
                                <div style="display: flex; align-items: center; gap: 10px;">
                                    <div style="width: 28px; height: 28px; border-radius: 50%; background: #e2e8f0; display: flex; align-items: center; justify-content: center; font-weight: 700; color: #64748b; font-size: 0.75rem; overflow: hidden;">
                                        <?php if ($pt['avatar']): ?>
                                            <img src="<?= htmlspecialchars($pt['avatar']) ?>" style="width: 100%; height: 100%; object-fit: cover;">
                                        <?php else: ?>
                                            <?= strtoupper(substr($pt['name'], 0, 1)) ?>
                                        <?php endif; ?>
                                    </div>
                                    <div>
                                        <div style="font-size: 0.9rem; font-weight: 600; color: #1e293b;"><?= htmlspecialchars($pt['name']) ?></div>
                                        <?php if ($pt['observation']): ?>
                                            <div style="font-size: 0.75rem; color: #64748b;"><?= htmlspecialchars(mb_strimwidth($pt['observation'], 0, 25, '...')) ?></div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <div style="display: flex; align-items: center; gap: 8px;">
                                    <span style="font-weight: 800; font-size: 1rem; color: var(--primary);"><?= htmlspecialchars($pt['tone']) ?></span>
                                    <form method="POST" onsubmit="return confirm('Remover?')" style="margin: 0; display: flex;">
                                        <input type="hidden" name="action" value="delete_tone">
                                        <input type="hidden" name="tone_id" value="<?= $pt['id'] ?>">
                                        <button type="submit" style="background: none; border: none; padding: 4px; color: #cbd5e1; cursor: pointer; display: flex;"><i data-lucide="x" width="14"></i></button>
                                    </form>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div style="text-align: center; color: #94a3b8; font-size: 0.85rem; padding: 8px;">
                            Nenhum tom pessoal.
                        </div>
                    <?php endif; ?>
                </div>

            </div>

        </div>

    </div>

    <!-- Antigo container (Removido pelo replace) -->


<!-- Modal Add Tone -->
<div id="toneModal" style="
    display: none;
    position: fixed;
    top: 0; left: 0;
    width: 100%; height: 100%;
    background: rgba(0,0,0,0.5);
    z-index: 1000;
    backdrop-filter: blur(2px);
" onclick="if(event.target === this) closeToneModal()">
    <div style="
        position: absolute;
        top: 50%; left: 50%;
        transform: translate(-50%, -50%);
        width: 90%; max-width: 400px;
        background: var(--bg-surface);
        border-radius: 16px;
        padding: 24px;
        box-shadow: 0 20px 50px rgba(0,0,0,0.2);
    ">
        <h3 style="margin: 0 0 16px 0; font-size: 1.25rem; color: var(--text-main);">Adicionar Tom por Voz</h3>
        
        <form method="POST">
            <input type="hidden" name="action" value="add_tone">
            
            <div style="margin-bottom: 16px;">
                <label style="display: block; margin-bottom: 8px; font-weight: 600; font-size: 0.9rem;">Membro</label>
                <select name="user_id" required style="width: 100%; padding: 10px; border-radius: 8px; border: 1px solid var(--border-color); background: var(--bg-body); color: var(--text-main);">
                    <?php foreach ($usersList as $u): ?>
                        <option value="<?= $u['id'] ?>"><?= htmlspecialchars($u['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div style="margin-bottom: 16px;">
                <label style="display: block; margin-bottom: 8px; font-weight: 600; font-size: 0.9rem;">Tom</label>
                <select name="tone" required style="width: 100%; padding: 10px; border-radius: 8px; border: 1px solid var(--border-color); background: var(--bg-body); color: var(--text-main);">
                    <?php foreach ($musicTones as $t): ?>
                        <option value="<?= $t ?>" <?= $t == ($song['tone'] ?? 'C') ? 'selected' : '' ?>><?= $t ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div style="margin-bottom: 20px;">
                <label style="display: block; margin-bottom: 8px; font-weight: 600; font-size: 0.9rem;">Observação</label>
                <textarea name="observation" rows="3" placeholder="Ex: Usa capo na 2ª casa..." style="width: 100%; padding: 10px; border-radius: 8px; border: 1px solid var(--border-color); background: var(--bg-body); color: var(--text-main); font-family: inherit; resize: vertical;"></textarea>
            </div>

            <div style="display: flex; gap: 12px;">
                <button type="button" onclick="closeToneModal()" style="flex: 1; padding: 12px; border-radius: 8px; border: 1px solid var(--border-color); background: transparent; font-weight: 600; cursor: pointer;">Cancelar</button>
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