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
        padding: 20px;
    }

    .playlist-header {
        text-align: center;
        padding: 32px 20px;
        background: linear-gradient(135deg, #2D7A4F 0%, #1a4d2e 100%);
        border-radius: 16px;
        color: white;
        margin-bottom: 32px;
    }

    .playlist-title {
        font-size: 2rem;
        font-weight: 800;
        margin-bottom: 12px;
    }

    .playlist-subtitle {
        font-size: 1.1rem;
        opacity: 0.9;
    }

    .song-item {
        background: var(--bg-secondary);
        border: 1px solid var(--border-subtle);
        border-radius: 12px;
        padding: 16px;
        margin-bottom: 12px;
        display: flex;
        align-items: center;
        gap: 16px;
    }

    .song-number {
        width: 40px;
        height: 40px;
        background: var(--accent-interactive);
        color: white;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 700;
        font-size: 1.1rem;
        flex-shrink: 0;
    }

    .song-info {
        flex: 1;
    }

    .song-title {
        font-weight: 700;
        font-size: 1.1rem;
        color: var(--text-primary);
        margin-bottom: 4px;
    }

    .song-artist {
        color: var(--text-secondary);
        font-size: 0.9rem;
    }

    .youtube-link {
        padding: 8px 16px;
        background: #FF0000;
        color: white;
        border-radius: 8px;
        text-decoration: none;
        font-weight: 600;
        display: flex;
        align-items: center;
        gap: 8px;
        transition: all 0.2s;
    }

    .youtube-link:hover {
        background: #CC0000;
        transform: scale(1.05);
    }

    .message-box {
        background: rgba(45, 122, 79, 0.1);
        border: 2px solid var(--accent-interactive);
        border-radius: 12px;
        padding: 24px;
        margin: 32px 0;
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