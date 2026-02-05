<?php
// admin/criar_playlist.php
// Página simples mostrando músicas selecionadas com links do YouTube

require_once '../includes/db.php';
require_once '../includes/layout.php';

if (!isset($_GET['songs']) || empty($_GET['songs'])) {
    header('Location: repertorio.php');
    exit;
}

$songIds = explode(',', $_GET['songs']);
$placeholders = str_repeat('?,', count($songIds) - 1) . '?';

$stmt = $pdo->prepare("SELECT * FROM songs WHERE id IN ($placeholders) ORDER BY title ASC");
$stmt->execute($songIds);
$songs = $stmt->fetchAll(PDO::FETCH_ASSOC);

renderAppHeader('Playlist Selecionada');
?>

<style>
    .playlist-container {
        max-width: 800px;
        margin: 0 auto;
        padding: 16px;
    }

    .playlist-header {
        text-align: center;
        padding: 24px 16px;
        background: var(--primary);
        border-radius: 12px;
        color: white;
        margin-bottom: 24px;
    }

    .playlist-title {
        font-size: 1.5rem;
        font-weight: 800;
        margin-bottom: 8px;
    }

    .playlist-subtitle {
        font-size: 0.95rem;
        opacity: 0.9;
    }

    .song-item {
        background: var(--bg-secondary);
        border: 1px solid var(--border-subtle);
        border-radius: 12px;
        padding: 12px;
        margin-bottom: 8px;
        display: flex;
        align-items: center;
        gap: 12px;
    }

    .song-number {
        width: 32px;
        height: 32px;
        background: var(--accent-interactive);
        color: white;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 700;
        font-size: 0.9rem;
        flex-shrink: 0;
    }

    .song-info {
        flex: 1;
    }

    .song-title {
        font-weight: 700;
        font-size: 0.95rem;
        color: var(--text-primary);
        margin-bottom: 2px;
    }

    .song-artist {
        color: var(--text-secondary);
        font-size: 0.8rem;
    }

    .youtube-link {
        padding: 6px 12px;
        background: #FF0000;
        color: white;
        border-radius: 8px;
        text-decoration: none;
        font-weight: 600;
        display: flex;
        align-items: center;
        gap: 6px;
        transition: all 0.2s;
        font-size: 0.8rem;
    }

    .youtube-link:hover {
        background: #CC0000;
        transform: scale(1.05);
    }

    .message-box {
        background: rgba(45, 122, 79, 0.1);
        border: 2px solid var(--accent-interactive);
        border-radius: 12px;
        padding: 16px;
        margin: 24px 0;
        text-align: center;
    }
</style>

<div class="playlist-container">
    <div class="playlist-header">
        <div class="playlist-title">🎵 Playlist Selecionada</div>
        <div class="playlist-subtitle">
            <?= count($songs) ?> música<?= count($songs) > 1 ? 's' : '' ?> para louvar ao Senhor
        </div>
    </div>

    <div class="message-box">
        <h3 style="color: var(--accent-interactive); margin-bottom: 12px;">Obrigado por usar o App Louvor!</h3>
        <p style="color: var(--text-secondary); line-height: 1.6;">
            Preparamos esta seleção especial de músicas para você.
            Clique nos botões abaixo para acessar cada música no YouTube.
            Que o Senhor abençoe seu momento de louvor! 🙏
        </p>
    </div>

    <?php foreach ($songs as $index => $song): ?>
        <div class="song-item">
            <div class="song-number"><?= $index + 1 ?></div>
            <div class="song-info">
                <div class="song-title"><?= htmlspecialchars($song['title']) ?></div>
                <div class="song-artist"><?= htmlspecialchars($song['artist']) ?></div>
            </div>
            <?php if ($song['link_video']): ?>
                <a href="<?= htmlspecialchars($song['link_video']) ?>" target="_blank" class="youtube-link">
                    <i data-lucide="play"></i> YouTube
                </a>
            <?php else: ?>
                <span style="color: var(--text-muted); font-size: 0.85rem;">Sem link</span>
            <?php endif; ?>
        </div>
    <?php endforeach; ?>

    <div style="text-align: center; margin-top: 32px; padding-bottom: 80px;">
        <a href="repertorio.php" class="btn-primary ripple" style="text-decoration: none; padding: 14px 32px; display: inline-block;">
            <i data-lucide="arrow-left"></i> Voltar ao Repertório
        </a>
    </div>

    <div style="text-align: center; color: var(--text-muted); font-size: 0.85rem; margin-top: 24px; padding-bottom: 40px;">
        <p>PIB Oliveira - Ministério de Louvor</p>
        <p>Gerado em <?= date('d/m/Y \à\s H:i') ?></p>
    </div>
</div>

<?php renderAppFooter(); ?>