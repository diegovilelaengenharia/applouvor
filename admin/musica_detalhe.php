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

    renderPageHeader('Detalhes da Música', $song['artist'], $menuActions);

    // Ícone Principal Compacto
    ?>
    <div style="text-align: center; margin-bottom: 24px;">
        <div style="
        width: 48px; 
        height: 48px; 
        background: linear-gradient(135deg, #047857 0%, #064e3b 100%);
        border-radius: 12px; 
        display: inline-flex; 
        align-items: center; 
        justify-content: center; 
        box-shadow: 0 4px 12px rgba(4, 120, 87, 0.2);
    ">
            <i data-lucide="music" style="width: 24px; height: 24px; color: white;"></i>
        </div>
        <h1 style="color: #1e293b; margin: 12px 0 4px 0; font-size: var(--font-h1); font-weight: 800; letter-spacing: -0.5px; line-height: 1.2;"><?= htmlspecialchars($song['title']) ?></h1>
        
        <!-- Meta Info Row (Artista, Status, Stats) -->
        <div style="display: flex; align-items: center; justify-content: center; gap: 10px; flex-wrap: wrap; margin-top: 8px;">
            
            <!-- Artista Link -->
            <a href="repertorio.php?q=<?= urlencode($song['artist']) ?>" style="
                color: #64748b; font-weight: 600; font-size: 0.95rem; text-decoration: none; 
                display: flex; align-items: center; gap: 4px; border-bottom: 1px dotted transparent; transition: all 0.2s;
            " onmouseover="this.style.color='var(--primary)'; this.style.borderColor='var(--primary)'" onmouseout="this.style.color='#64748b'; this.style.borderColor='transparent'">
                <?= htmlspecialchars($song['artist']) ?>
                <i data-lucide="external-link" style="width: 12px; opacity: 0.5;"></i>
            </a>

            <span style="color: #cbd5e1;">•</span>

            <!-- Status Badge (Discreto) -->
            <div title="<?= $statusDesc ?>" style="
                display: inline-flex; align-items: center; gap: 4px; 
                padding: 2px 8px; 
                background: <?= $statusColor ?>10; 
                color: <?= $statusColor ?>; 
                border-radius: 12px; 
                font-weight: 700; 
                font-size: 0.75rem; 
                border: 1px solid <?= $statusColor ?>20;
                cursor: help;
            ">
                <i data-lucide="<?= $statusIcon ?>" style="width: 12px;"></i>
                <?= $statusLabel ?>
            </div>

            <span style="color: #cbd5e1;">•</span>

            <!-- Execuções -->
            <div style="font-size: 0.85rem; color: #64748b; font-weight: 500; display: flex; align-items: center; gap: 4px;">
                <i data-lucide="play-circle" style="width: 14px; color: #94a3b8;"></i>
                <?= $totalExecs ?> execuç<?= $totalExecs == 1 ? 'ão' : 'ões' ?>
                <?php if ($lastPlayed): ?>
                    <span style="color: #94a3b8; font-size: 0.75rem;">(Última: <?= (new DateTime($lastPlayed))->format('d/m/y') ?>)</span>
                <?php endif; ?>
            </div>

        </div>
    </div>


    <!-- Versão Compacta -->
    <div class="info-section">
        <div class="info-section-title">Versão (Original)</div>

        <div class="info-grid">
            <div class="info-item">
                <div class="info-label">Tom</div>
                <div class="info-value"><?= $song['tone'] ?: '-' ?></div>
            </div>
            <div class="info-item">
                <div class="info-label">Duração</div>
                <div class="info-value"><?= $song['duration'] ?: '-' ?></div>
            </div>
            <div class="info-item">
                <div class="info-label">BPM</div>
                <div class="info-value"><?= $song['bpm'] ?: '-' ?></div>
            </div>
        </div>
    </div>

    <!-- Classificações -->
    <?php if (!empty($tags)): ?>
        <div class="info-section">
            <div class="info-section-title">Classificações</div>
            <div style="display: flex; gap: 8px; flex-wrap: wrap;">
                <?php foreach ($tags as $tag):
                    $tagColor = $tag['color'] ?? '#047857';
                ?>
                    <span style="
                background: <?= $tagColor ?>15; 
                color: <?= $tagColor ?>; 
                padding: 6px 12px; 
                border-radius: 20px; 
                font-size: var(--font-body-sm); 
                font-weight: 700;
                border: 1px solid <?= $tagColor ?>30;
            "><?= htmlspecialchars($tag['name']) ?></span>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>

    <!-- TONS POR VOZ (NOVO) -->
    <div class="info-section">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px;">
            <div class="info-section-title" style="margin-bottom: 0;">Tons por Voz</div>
            <button onclick="openToneModal()" style="
                background: var(--bg-body); 
                border: 1px solid var(--border-color); 
                padding: 6px 12px; 
                border-radius: 20px; 
                font-size: 0.75rem; 
                font-weight: 700; 
                color: var(--primary); 
                cursor: pointer; 
                display: flex; 
                align-items: center; 
                gap: 4px;    
                transition: all 0.2s;
            " class="ripple">
                <i data-lucide="plus" style="width: 14px;"></i> Adicionar
            </button>
        </div>

        <?php if (!empty($personalTones)): ?>
            <div style="display: flex; flex-direction: column; gap: 10px;">
                <?php foreach ($personalTones as $pt): ?>
                    <div style="background: var(--bg-body); border-radius: 10px; padding: 12px; display: flex; align-items: flex-start; gap: 12px; border: 1px solid var(--border-color);">
                        <!-- Avatar -->
                        <div style="width: 36px; height: 36px; border-radius: 50%; background: #e2e8f0; display: flex; align-items: center; justify-content: center; font-weight: 700; color: #64748b; flex-shrink: 0; font-size: 0.9rem; overflow: hidden;">
                             <?php if ($pt['avatar']): ?>
                                 <img src="<?= htmlspecialchars($pt['avatar']) ?>" style="width: 100%; height: 100%; object-fit: cover;">
                             <?php else: ?>
                                 <?= strtoupper(substr($pt['name'], 0, 1)) ?>
                             <?php endif; ?>
                        </div>
                        
                        <div style="flex: 1;">
                            <div style="display: flex; justify-content: space-between; align-items: center;">
                                <div style="font-weight: 700; color: var(--text-main); font-size: 0.9rem;"><?= htmlspecialchars($pt['name']) ?></div>
                                <span style="background: #eff6ff; color: #3b82f6; font-weight: 800; padding: 2px 8px; border-radius: 6px; font-size: 0.8rem;">
                                    <?= htmlspecialchars($pt['tone']) ?>
                                </span>
                            </div>
                            <?php if ($pt['observation']): ?>
                                <div style="font-size: 0.8rem; color: #64748b; margin-top: 4px; line-height: 1.4; background: rgba(0,0,0,0.02); padding: 6px; border-radius: 6px;">
                                    <?= nl2br(htmlspecialchars($pt['observation'])) ?>
                                </div>
                            <?php endif; ?>
                        </div>

                        <!-- Delete Btn -->
                        <form method="POST" onsubmit="return confirm('Remover este tom?')" style="margin: 0;">
                            <input type="hidden" name="action" value="delete_tone">
                            <input type="hidden" name="tone_id" value="<?= $pt['id'] ?>">
                            <button type="submit" style="background: transparent; border: none; color: #94a3b8; cursor: pointer; padding: 4px;" title="Remover">
                                <i data-lucide="x" style="width: 16px;"></i>
                            </button>
                        </form>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div style="text-align: center; padding: 20px 0; color: var(--text-muted); font-size: 0.9rem;">
                Nenhum tom pessoal cadastrado.
            </div>
        <?php endif; ?>
    </div>

    <!-- Referências -->
    <div class="info-section">
        <div class="info-section-title">Referências</div>

        <div class="ref-link">
            <a href="<?= $song['link_letra'] ?: '#' ?>" target="_blank" class="ripple" style="display: flex; align-items: center; gap: 12px; text-decoration: none; color: inherit; width: 100%;">
                <div style="width: 40px; height: 40px; background: #fff7ed; border-radius: 10px; color: #f97316; display: flex; align-items: center; justify-content: center;">
                    <i data-lucide="file-text" style="width: 20px;"></i>
                </div>
                <div style="flex: 1;">
                    <div style="font-weight: 600; color: #1e293b;">Letra</div>
                    <div style="font-size: var(--font-caption); color: #94a3b8; text-decoration: none; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; max-width: 200px;"><?= $song['link_letra'] ?: 'Não cadastrado' ?></div>
                </div>
                <i data-lucide="external-link" style="width: 16px; color: #cbd5e1;"></i>
            </a>
        </div>

        <div class="ref-link">
            <a href="<?= $song['link_cifra'] ?: '#' ?>" target="_blank" class="ripple" style="display: flex; align-items: center; gap: 12px; text-decoration: none; color: inherit; width: 100%;">
                <div style="width: 40px; height: 40px; background: #ecfdf5; border-radius: 10px; color: #10b981; display: flex; align-items: center; justify-content: center;">
                    <i data-lucide="music" style="width: 20px;"></i>
                </div>
                <div style="flex: 1;">
                    <div style="font-weight: 600; color: #1e293b;">Cifra</div>
                    <div style="font-size: var(--font-caption); color: #94a3b8; text-decoration: none; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; max-width: 200px;"><?= $song['link_cifra'] ?: 'Não cadastrado' ?></div>
                </div>
                <i data-lucide="external-link" style="width: 16px; color: #cbd5e1;"></i>
            </a>
        </div>

        <div class="ref-link">
            <a href="<?= $song['link_audio'] ?: '#' ?>" target="_blank" class="ripple" style="display: flex; align-items: center; gap: 12px; text-decoration: none; color: inherit; width: 100%;">
                <div style="width: 40px; height: 40px; background: #eff6ff; border-radius: 10px; color: #3b82f6; display: flex; align-items: center; justify-content: center;">
                    <i data-lucide="headphones" style="width: 20px;"></i>
                </div>
                <div style="flex: 1;">
                    <div style="font-weight: 600; color: #1e293b;">Áudio</div>
                    <div style="font-size: var(--font-caption); color: #94a3b8; text-decoration: none; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; max-width: 200px;"><?= $song['link_audio'] ?: 'Não cadastrado' ?></div>
                </div>
                <i data-lucide="external-link" style="width: 16px; color: #cbd5e1;"></i>
            </a>
        </div>

        <div class="ref-link">
            <a href="<?= $song['link_video'] ?: '#' ?>" target="_blank" class="ripple" style="display: flex; align-items: center; gap: 12px; text-decoration: none; color: inherit; width: 100%;">
                <div style="width: 40px; height: 40px; background: #fef2f2; border-radius: 10px; color: #ef4444; display: flex; align-items: center; justify-content: center;">
                    <i data-lucide="video" style="width: 20px;"></i>
                </div>
                <div style="flex: 1;">
                    <div style="font-weight: 600; color: #1e293b;">Vídeo</div>
                    <div style="font-size: var(--font-caption); color: #94a3b8; text-decoration: none; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; max-width: 200px;"><?= $song['link_video'] ?: 'Não cadastrado' ?></div>
                </div>
                <i data-lucide="external-link" style="width: 16px; color: #cbd5e1;"></i>
            </a>
        </div>
    </div>


    <?php if ($song['tags']): ?>
        <!-- Tags -->
        <div class="info-section">
            <div class="info-section-title">Tags</div>
            <div style="display: flex; flex-wrap: wrap; gap: 8px;">
                <?php foreach (explode(',', $song['tags']) as $tag): ?>
                    <span style="padding: 6px 12px; background: rgba(139, 92, 246, 0.1); color: #8B5CF6; border-radius: 20px; font-size: var(--font-body-sm); font-weight: 600;">
                        <?= htmlspecialchars(trim($tag)) ?>
                    </span>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>

</div>

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