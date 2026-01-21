<?php
// admin/musica_detalhe.php
require_once '../includes/db.php';
require_once '../includes/layout.php';

$id = $_GET['id'] ?? null;

if (!$id) {
    header('Location: repertorio.php');
    exit;
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
    :root {
        --bg-tertiary: #f8fafc;
        --border-subtle: #e2e8f0;
        --text-secondary: #64748b;
        --text-primary: #1e293b;
        --accent-interactive: #047857;
        --bg-secondary: #f1f5f9;
        --shadow-md: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
        --text-muted: #94a3b8;
    }

    .song-header {
        text-align: center;
        padding: 24px 16px;
        background: linear-gradient(135deg, #2D7A4F 0%, #1a4d2e 100%);
        border-radius: 16px;
        margin-bottom: 24px;
        color: white;
    }

    .info-section {
        background: white;
        border: 1px solid var(--border-subtle);
        border-radius: 20px;
        padding: 24px;
        margin-bottom: 32px;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05);
    }

    .info-section-title {
        font-size: 0.85rem;
        font-weight: 700;
        color: var(--text-secondary);
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
        background: var(--bg-tertiary);
        border-radius: 8px;
    }

    .info-label {
        font-size: 0.75rem;
        color: var(--text-secondary);
        margin-bottom: 4px;
    }

    .info-value {
        font-size: 1.1rem;
        font-weight: 700;
        color: var(--accent-interactive);
    }

    .link-item {
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 14px;
        background: var(--bg-tertiary);
        border-radius: 10px;
        margin-bottom: 8px;
        text-decoration: none;
        transition: all 0.2s;
        border: 1px solid transparent;
    }

    .link-item:hover {
        background: var(--bg-secondary);
        border-color: var(--accent-interactive);
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
        color: var(--text-primary);
        font-size: 0.95rem;
    }

    .link-url {
        font-size: 0.75rem;
        color: var(--text-muted);
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }
</style>

<?php

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
        background: white;
        border: 1px solid #e2e8f0;
        border-radius: 12px;
        box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
        z-index: 50;
        min-width: 160px;
        overflow: hidden;
    ">
        <a href="musica_editar.php?id=' . $id . '" class="ripple" style="
            display: flex; align-items: center; gap: 10px;
            padding: 12px 16px;
            text-decoration: none;
            color: #1e293b;
            font-size: 0.9rem;
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
                font-size: 0.9rem;
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
// Fechar ao clicar fora
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

// Ícone Principal
?>
<div style="text-align: center; margin-bottom: 24px;">
    <div style="
        width: 64px; 
        height: 64px; 
        background: linear-gradient(135deg, #047857 0%, #064e3b 100%);
        border-radius: 16px; 
        display: inline-flex; 
        align-items: center; 
        justify-content: center; 
        box-shadow: 0 4px 12px rgba(4, 120, 87, 0.2);
    ">
        <i data-lucide="music" style="width: 32px; height: 32px; color: white;"></i>
    </div>
    <h1 style="color: #1e293b; margin: 16px 0 4px 0; font-size: 1.5rem; font-weight: 800; letter-spacing: -0.5px;"><?= htmlspecialchars($song['title']) ?></h1>
    <p style="color: #64748b; margin: 0; font-weight: 500; font-size: 1rem;"><?= htmlspecialchars($song['artist']) ?></p>
</div>


<!-- Versão -->
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
                font-size: 0.8rem; 
                font-weight: 700;
                border: 1px solid <?= $tagColor ?>30;
            "><?= htmlspecialchars($tag['name']) ?></span>
            <?php endforeach; ?>
        </div>
    </div>
<?php endif; ?>

<!-- Referências -->
<div class="info-section">
    <div class="info-section-title">Referências</div>

    <div class="ref-link">
        <a href="<?= $song['lyrics_link'] ?: '#' ?>" target="_blank" class="ripple" style="display: flex; align-items: center; gap: 12px; text-decoration: none; color: inherit; width: 100%;">
            <div style="width: 40px; height: 40px; background: #fff7ed; border-radius: 10px; color: #f97316; display: flex; align-items: center; justify-content: center;">
                <i data-lucide="file-text" style="width: 20px;"></i>
            </div>
            <div style="flex: 1;">
                <div style="font-weight: 600; color: #1e293b;">Letra</div>
                <div style="font-size: 0.75rem; color: #94a3b8; text-decoration: none;"><?= $song['lyrics_link'] ?: 'Não cadastrado' ?></div>
            </div>
            <i data-lucide="external-link" style="width: 16px; color: #cbd5e1;"></i>
        </a>
    </div>

    <div class="ref-link">
        <a href="<?= $song['chords_link'] ?: '#' ?>" target="_blank" class="ripple" style="display: flex; align-items: center; gap: 12px; text-decoration: none; color: inherit; width: 100%;">
            <div style="width: 40px; height: 40px; background: #ecfdf5; border-radius: 10px; color: #10b981; display: flex; align-items: center; justify-content: center;">
                <i data-lucide="music" style="width: 20px;"></i>
            </div>
            <div style="flex: 1;">
                <div style="font-weight: 600; color: #1e293b;">Cifra</div>
                <div style="font-size: 0.75rem; color: #94a3b8; text-decoration: none;"><?= $song['chords_link'] ?: 'Não cadastrado' ?></div>
            </div>
            <i data-lucide="external-link" style="width: 16px; color: #cbd5e1;"></i>
        </a>
    </div>

    <div class="ref-link">
        <a href="<?= $song['youtube_link'] ?: '#' ?>" target="_blank" class="ripple" style="display: flex; align-items: center; gap: 12px; text-decoration: none; color: inherit; width: 100%;">
            <div style="width: 40px; height: 40px; background: #eff6ff; border-radius: 10px; color: #3b82f6; display: flex; align-items: center; justify-content: center;">
                <i data-lucide="headphones" style="width: 20px;"></i>
            </div>
            <div style="flex: 1;">
                <div style="font-weight: 600; color: #1e293b;">Áudio</div>
                <div style="font-size: 0.75rem; color: #94a3b8; text-decoration: none;"><?= $song['youtube_link'] ?: 'Não cadastrado' ?></div>
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
                <span style="padding: 6px 12px; background: rgba(139, 92, 246, 0.1); color: #8B5CF6; border-radius: 20px; font-size: 0.85rem; font-weight: 600;">
                    <?= htmlspecialchars(trim($tag)) ?>
                </span>
            <?php endforeach; ?>
        </div>
    </div>
<?php endif; ?>


<?php renderAppFooter(); ?>