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

    // Ícone Principal Compacto
    ?>
    <div style="text-align: center; margin-bottom: 20px;">
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
        <h1 style="color: #1e293b; margin: 12px 0 2px 0; font-size: var(--font-h1); font-weight: 800; letter-spacing: -0.5px;"><?= htmlspecialchars($song['title']) ?></h1>
        <p style="color: #64748b; margin: 0; font-weight: 500; font-size: var(--font-body);"><?= htmlspecialchars($song['artist']) ?></p>
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


    <?php renderAppFooter(); ?>