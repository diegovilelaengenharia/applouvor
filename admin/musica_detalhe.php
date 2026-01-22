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
        font-size: 0.85rem;
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
        font-size: 0.75rem;
        color: var(--text-muted);
        margin-bottom: 4px;
    }

    .info-value {
        font-size: 1.1rem;
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
        <h1 style="color: #1e293b; margin: 12px 0 2px 0; font-size: 1.25rem; font-weight: 800; letter-spacing: -0.5px;"><?= htmlspecialchars($song['title']) ?></h1>
        <p style="color: #64748b; margin: 0; font-weight: 500; font-size: 0.9rem;"><?= htmlspecialchars($song['artist']) ?></p>
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
            <a href="<?= $song['link_letra'] ?: '#' ?>" target="_blank" class="ripple" style="display: flex; align-items: center; gap: 12px; text-decoration: none; color: inherit; width: 100%;">
                <div style="width: 40px; height: 40px; background: #fff7ed; border-radius: 10px; color: #f97316; display: flex; align-items: center; justify-content: center;">
                    <i data-lucide="file-text" style="width: 20px;"></i>
                </div>
                <div style="flex: 1;">
                    <div style="font-weight: 600; color: #1e293b;">Letra</div>
                    <div style="font-size: 0.75rem; color: #94a3b8; text-decoration: none; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; max-width: 200px;"><?= $song['link_letra'] ?: 'Não cadastrado' ?></div>
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
                    <div style="font-size: 0.75rem; color: #94a3b8; text-decoration: none; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; max-width: 200px;"><?= $song['link_cifra'] ?: 'Não cadastrado' ?></div>
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
                    <div style="font-size: 0.75rem; color: #94a3b8; text-decoration: none; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; max-width: 200px;"><?= $song['link_audio'] ?: 'Não cadastrado' ?></div>
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
                    <div style="font-size: 0.75rem; color: #94a3b8; text-decoration: none; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; max-width: 200px;"><?= $song['link_video'] ?: 'Não cadastrado' ?></div>
                </div>
                <i data-lucide="external-link" style="width: 16px; color: #cbd5e1;"></i>
            </a>
        </div>
    </div>

    <?php
    // Lógica Inteligente para Streaming
    // Se não tiver link específico, tenta pegar dos links genéricos (áudio/vídeo)

    $spotifyUrl = $song['link_spotify'] ?? null;
    if (empty($spotifyUrl) && strpos(($song['link_audio'] ?? ''), 'spotify.com') !== false) {
        $spotifyUrl = $song['link_audio'];
    }

    $youtubeUrl = $song['link_youtube'] ?? null;
    if (empty($youtubeUrl) && (strpos(($song['link_video'] ?? ''), 'youtube.com') !== false || strpos(($song['link_video'] ?? ''), 'youtu.be') !== false)) {
        $youtubeUrl = $song['link_video'];
    }

    $appleUrl = $song['link_apple_music'] ?? null;
    if (empty($appleUrl) && strpos(($song['link_audio'] ?? ''), 'music.apple.com') !== false) {
        $appleUrl = $song['link_audio'];
    }

    $deezerUrl = $song['link_deezer'] ?? null;
    if (empty($deezerUrl) && strpos(($song['link_audio'] ?? ''), 'deezer.com') !== false) {
        $deezerUrl = $song['link_audio'];
    }

    // Só exibe a seção se tiver pelo menos um link
    if ($spotifyUrl || $youtubeUrl || $appleUrl || $deezerUrl):
    ?>
        <!-- Plataformas de Streaming -->
        <div class="info-section">
            <div class="info-section-title">Plataformas de Streaming</div>

            <?php if ($spotifyUrl): ?>
                <!-- Spotify -->
                <div class="ref-link">
                    <a href="<?= htmlspecialchars($spotifyUrl) ?>" target="_blank" class="ripple" style="display: flex; align-items: center; gap: 12px; text-decoration: none; color: inherit; width: 100%;">
                        <div style="width: 40px; height: 40px; background: #1DB954; border-radius: 10px; display: flex; align-items: center; justify-content: center;">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="white">
                                <path d="M12 0C5.4 0 0 5.4 0 12s5.4 12 12 12 12-5.4 12-12S18.66 0 12 0zm5.521 17.34c-.24.359-.66.48-1.021.24-2.82-1.74-6.36-2.101-10.561-1.141-.418.122-.779-.179-.899-.539-.12-.421.18-.78.54-.9 4.56-1.021 8.52-.6 11.64 1.32.42.18.479.659.301 1.02zm1.44-3.3c-.301.42-.841.6-1.262.3-3.239-1.98-8.159-2.58-11.939-1.38-.479.12-1.02-.12-1.14-.6-.12-.48.12-1.021.6-1.141C9.6 9.9 15 10.561 18.72 12.84c.361.181.54.78.241 1.2zm.12-3.36C15.24 8.4 8.82 8.16 5.16 9.301c-.6.179-1.2-.181-1.38-.721-.18-.601.18-1.2.72-1.381 4.26-1.26 11.28-1.02 15.721 1.621.539.3.719 1.02.419 1.56-.299.421-1.02.599-1.559.3z" />
                            </svg>
                        </div>
                        <div style="flex: 1;">
                            <div style="font-weight: 600; color: #1e293b;">Spotify</div>
                            <div style="font-size: 0.75rem; color: #94a3b8; text-decoration: none; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; max-width: 200px;"><?= htmlspecialchars($spotifyUrl) ?></div>
                        </div>
                        <i data-lucide="external-link" style="width: 16px; color: #cbd5e1;"></i>
                    </a>
                </div>
            <?php endif; ?>

            <?php if ($youtubeUrl): ?>
                <!-- YouTube -->
                <div class="ref-link">
                    <a href="<?= htmlspecialchars($youtubeUrl) ?>" target="_blank" class="ripple" style="display: flex; align-items: center; gap: 12px; text-decoration: none; color: inherit; width: 100%;">
                        <div style="width: 40px; height: 40px; background: #FF0000; border-radius: 10px; display: flex; align-items: center; justify-content: center;">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="white">
                                <path d="M23.498 6.186a3.016 3.016 0 0 0-2.122-2.136C19.505 3.545 12 3.545 12 3.545s-7.505 0-9.377.505A3.017 3.017 0 0 0 .502 6.186C0 8.07 0 12 0 12s0 3.93.502 5.814a3.016 3.016 0 0 0 2.122 2.136c1.871.505 9.376.505 9.376.505s7.505 0 9.377-.505a3.015 3.015 0 0 0 2.122-2.136C24 15.93 24 12 24 12s0-3.93-.502-5.814zM9.545 15.568V8.432L15.818 12l-6.273 3.568z" />
                            </svg>
                        </div>
                        <div style="flex: 1;">
                            <div style="font-weight: 600; color: #1e293b;">YouTube</div>
                            <div style="font-size: 0.75rem; color: #94a3b8; text-decoration: none; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; max-width: 200px;"><?= htmlspecialchars($youtubeUrl) ?></div>
                        </div>
                        <i data-lucide="external-link" style="width: 16px; color: #cbd5e1;"></i>
                    </a>
                </div>
            <?php endif; ?>

            <?php if ($appleUrl): ?>
                <!-- Apple Music -->
                <div class="ref-link">
                    <a href="<?= htmlspecialchars($appleUrl) ?>" target="_blank" class="ripple" style="display: flex; align-items: center; gap: 12px; text-decoration: none; color: inherit; width: 100%;">
                        <div style="width: 40px; height: 40px; background: linear-gradient(135deg, #FA243C, #FC3C4C); border-radius: 10px; display: flex; align-items: center; justify-content: center;">
                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="white">
                                <path d="M23.994 6.124a9.23 9.23 0 0 0-.24-2.19c-.317-1.31-1.062-2.31-2.18-3.043a5.022 5.022 0 0 0-1.877-.726 10.496 10.496 0 0 0-1.564-.15c-.04-.003-.083-.01-.124-.013H5.986c-.152.01-.303.017-.455.026-.747.043-1.49.123-2.193.4-1.336.53-2.3 1.452-2.865 2.78-.192.448-.292.925-.363 1.408a10.61 10.61 0 0 0-.1 1.18c0 .032-.007.062-.01.093v12.223c.01.14.017.283.027.424.05.815.154 1.624.497 2.373.65 1.42 1.738 2.353 3.234 2.801.42.127.856.187 1.293.228.555.053 1.11.06 1.667.06h11.03a12.5 12.5 0 0 0 1.57-.1c.822-.106 1.596-.35 2.296-.81a5.046 5.046 0 0 0 1.88-2.207c.186-.42.293-.87.37-1.324.113-.675.138-1.358.137-2.04-.002-3.8 0-7.595-.003-11.393zm-6.423 3.99v5.712c0 .417-.058.827-.244 1.206-.29.59-.76.962-1.388 1.14-.35.1-.706.157-1.07.173-.95.045-1.773-.6-1.943-1.536a1.88 1.88 0 0 1 1.038-2.022c.323-.16.67-.25 1.018-.324.378-.082.758-.153 1.134-.24.274-.063.457-.23.51-.516a.904.904 0 0 0 .02-.193c0-1.815 0-3.63-.002-5.443a.725.725 0 0 0-.026-.185c-.04-.15-.15-.243-.304-.234-.16.01-.318.035-.475.066-.76.15-1.52.303-2.28.456l-2.325.47-1.374.278c-.016.003-.034.005-.05.01-.154.040-.243.15-.257.31-.01.097-.004.193-.004.29v7.88c0 .02 0 .04-.002.058-.17 1.318-1.168 2.214-2.487 2.234-.768.01-1.45-.283-1.984-.918-.46-.547-.644-1.184-.52-1.895.136-.776.6-1.316 1.32-1.632.666-.293 1.37-.39 2.093-.39.22 0 .44.01.66.023.212.014.424.04.635.066.05.006.088-.01.125-.05.05-.055.09-.117.134-.176v-8.86c0-.04.005-.08.01-.12.02-.14.09-.23.23-.26.055-.01.11-.022.165-.03.664-.134 1.328-.27 1.993-.403.834-.168 1.668-.336 2.503-.502.323-.065.645-.13.968-.194.053-.01.108-.02.162-.025.14-.013.236.05.277.187.008.027.01.056.01.085v5.778z" />
                            </svg>
                        </div>
                        <div style="flex: 1;">
                            <div style="font-weight: 600; color: #1e293b;">Apple Music</div>
                            <div style="font-size: 0.75rem; color: #94a3b8; text-decoration: none; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; max-width: 200px;"><?= htmlspecialchars($appleUrl) ?></div>
                        </div>
                        <i data-lucide="external-link" style="width: 16px; color: #cbd5e1;"></i>
                    </a>
                </div>
            <?php endif; ?>

            <?php if ($deezerUrl): ?>
                <!-- Deezer -->
                <div class="ref-link">
                    <a href="<?= htmlspecialchars($deezerUrl) ?>" target="_blank" class="ripple" style="display: flex; align-items: center; gap: 12px; text-decoration: none; color: inherit; width: 100%;">
                        <div style="width: 40px; height: 40px; background: linear-gradient(135deg, #FF0092, #A238FF); border-radius: 10px; display: flex; align-items: center; justify-content: center;">
                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="white">
                                <path d="M18.81 4.16v3.03h2.35V4.16zm0 4.7v3.03h2.35V8.86zm0 4.7v3.03h2.35v-3.03zm-4.7-9.4v3.03h2.34V4.16zm0 4.7v3.03h2.34V8.86zm0 4.7v3.03h2.34v-3.03zm0 4.7v3.03h2.34v-3.03zm-4.7-9.4v3.03h2.35V8.86zm0 4.7v3.03h2.35v-3.03zm0 4.7v3.03h2.35v-3.03zM4.7 13.56v3.03h2.35v-3.03zm0 4.7v3.03h2.35v-3.03zm4.71-4.7v3.03h2.35v-3.03zm0 4.7v3.03h2.35v-3.03z" />
                            </svg>
                        </div>
                        <div style="flex: 1;">
                            <div style="font-weight: 600; color: #1e293b;">Deezer</div>
                            <div style="font-size: 0.75rem; color: #94a3b8; text-decoration: none; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; max-width: 200px;"><?= htmlspecialchars($deezerUrl) ?></div>
                        </div>
                        <i data-lucide="external-link" style="width: 16px; color: #cbd5e1;"></i>
                    </a>
                </div>
            <?php endif; ?>
        </div>
    <?php endif; ?>


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