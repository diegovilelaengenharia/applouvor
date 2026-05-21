<?php
// admin/criar_playlist.php
// Página simples mostrando músicas selecionadas com links do YouTube

require_once '../src/config/db.php';
require_once '../src/layout/layout.php';

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

<link rel="stylesheet" href="../assets/css/pages/config-pages.css">

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