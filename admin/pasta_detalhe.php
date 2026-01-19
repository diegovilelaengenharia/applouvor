<?php
// admin/pasta_detalhe.php
require_once '../includes/db.php';
require_once '../includes/layout.php';

if (!isset($_GET['category'])) {
    header('Location: repertorio.php?tab=pastas');
    exit;
}

$category = urldecode($_GET['category']);

// Buscar músicas da categoria
$stmt = $pdo->prepare("SELECT * FROM songs WHERE category = ? ORDER BY title ASC");
$stmt->execute([$category]);
$songs = $stmt->fetchAll(PDO::FETCH_ASSOC);

renderAppHeader('Pasta');
?>

<style>
    .category-header {
        text-align: center;
        padding: 32px 16px;
        background: linear-gradient(135deg, #F59E0B 0%, #D97706 100%);
        border-radius: 16px;
        margin-bottom: 24px;
        color: white;
    }

    .category-icon-large {
        width: 100px;
        height: 100px;
        background: rgba(255, 255, 255, 0.2);
        border-radius: 20px;
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 0 auto 16px;
    }

    .category-name {
        font-size: 1.8rem;
        font-weight: 800;
        margin-bottom: 8px;
    }

    .category-count {
        font-size: 1rem;
        opacity: 0.9;
    }
</style>

<!-- Header com Voltar -->
<div style="display: flex; align-items: center; gap: 12px; margin-bottom: 20px;">
    <a href="repertorio.php?tab=pastas" class="btn-icon ripple">
        <i data-lucide="arrow-left"></i>
    </a>
    <h1 style="font-size: 1.2rem; font-weight: 700; color: var(--text-primary); margin: 0; flex: 1;">Pasta</h1>
</div>

<!-- Header da Categoria -->
<div class="category-header">
    <div class="category-icon-large">
        <i data-lucide="folder" style="width: 48px; height: 48px;"></i>
    </div>
    <div class="category-name"><?= htmlspecialchars($category) ?></div>
    <div class="category-count">
        <i data-lucide="music" style="width: 16px; display: inline;"></i>
        <?= count($songs) ?> música<?= count($songs) > 1 ? 's' : '' ?>
    </div>
</div>

<!-- Lista de Músicas -->
<div style="margin-bottom: 16px;">
    <h3 style="font-size: 0.9rem; font-weight: 700; color: var(--text-secondary); text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 12px;">
        Músicas
    </h3>
</div>

<?php foreach ($songs as $song): ?>
    <a href="musica_detalhe.php?id=<?= $song['id'] ?>" class="song-card ripple" style="background: var(--bg-secondary); border: 1px solid var(--border-subtle); border-radius: 12px; padding: 12px; margin-bottom: 10px; display: flex; align-items: center; gap: 12px; text-decoration: none; transition: all 0.2s;">
        <div style="width: 48px; height: 48px; background: linear-gradient(135deg, #2D7A4F 0%, #1a4d2e 100%); border-radius: 8px; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
            <i data-lucide="music" style="width: 24px; height: 24px; color: white;"></i>
        </div>
        <div style="flex: 1; min-width: 0;">
            <div style="font-weight: 700; color: var(--text-primary); font-size: 0.95rem; margin-bottom: 2px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                <?= htmlspecialchars($song['title']) ?>
            </div>
            <div style="font-size: 0.85rem; color: var(--text-secondary); white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                <?= htmlspecialchars($song['artist']) ?>
            </div>
            <div style="display: flex; align-items: center; gap: 8px; font-size: 0.8rem; color: var(--text-muted);">
                <?php if ($song['tone']): ?>
                    <span>Tom: <strong><?= htmlspecialchars($song['tone']) ?></strong></span>
                <?php endif; ?>
                <?php if ($song['bpm']): ?>
                    <span>• BPM: <?= $song['bpm'] ?></span>
                <?php endif; ?>
            </div>
        </div>
        <i data-lucide="chevron-right" style="width: 20px; color: var(--text-muted);"></i>
    </a>
<?php endforeach; ?>

<?php renderAppFooter(); ?>