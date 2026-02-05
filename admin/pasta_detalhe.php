<?php
// admin/pasta_detalhe.php
require_once '../includes/db.php';
require_once '../includes/layout.php';

if (!isset($_GET['id'])) {
    header('Location: repertorio.php?tab=pastas');
    exit;
}

$tagId = $_GET['id'];

// 1. Buscar Detalhes da Tag
$stmtTag = $pdo->prepare("SELECT * FROM tags WHERE id = ?");
$stmtTag->execute([$tagId]);
$tag = $stmtTag->fetch(PDO::FETCH_ASSOC);

if (!$tag) {
    echo "Pasta não encontrada.";
    exit;
}

// 2. Buscar Músicas dessa Tag
$stmtSongs = $pdo->prepare("
    SELECT s.* 
    FROM songs s
    JOIN song_tags st ON s.id = st.song_id
    WHERE st.tag_id = ?
    ORDER BY s.title ASC
");
$stmtSongs->execute([$tagId]);
$songs = $stmtSongs->fetchAll(PDO::FETCH_ASSOC);

renderAppHeader($tag['name']);
?>

<style>
    .song-card {
        background: var(--bg-card);
        border: 1px solid var(--border-subtle);
        border-radius: 12px;
        padding: 10px 12px;
        margin-bottom: 8px;
        display: flex;
        align-items: center;
        gap: 12px;
        text-decoration: none;
        transition: transform 0.2s;
    }

    .song-card:active {
        background: var(--bg-hover);
        transform: scale(0.98);
    }

    .song-icon {
        width: 40px;
        height: 40px;
        background: var(--bg-surface);
        border-radius: 8px;
        display: flex;
        align-items: center;
        justify-content: center;
        color: var(--text-secondary);
    }

    .empty-state {
        background: var(--bg-card);
        border-radius: 16px;
        padding: 32px 16px;
        text-align: center;
        box-shadow: var(--shadow-sm);
        border: 1px solid var(--border-subtle);
    }
</style>

<!-- Hero da Pasta Compacto -->
<div style="
    background: <?= $tag['color'] ?: 'var(--sage-600)' ?>; 
    margin: -16px -16px 20px -16px; 
    padding: 16px 16px 40px 16px; 
    border-radius: 0 0 20px 20px; 
    box-shadow: var(--shadow-sm);
    color: white;
    text-align: center;
    position: relative;
">
    <!-- Nav -->
    <div style="position: absolute; top: 16px; left: 16px;">
        <a href="repertorio.php?tab=pastas" style="
            color: white; text-decoration: none; display: flex; align-items: center; gap: 4px; 
            font-weight: 600; background: rgba(0,0,0,0.2); padding: 6px 10px; border-radius: 16px; font-size: var(--font-body-sm);
            backdrop-filter: blur(4px);
        ">
            <i data-lucide="arrow-left" style="width: 14px;"></i> Voltar
        </a>
    </div>

    <!-- Icone Grande -->
    <div style="
        width: 56px; 
        height: 56px; 
        background: rgba(255,255,255,0.2); 
        border-radius: 12px; 
        display: inline-flex; 
        align-items: center; 
        justify-content: center; 
        margin-bottom: 10px;
        backdrop-filter: blur(4px);
    ">
        <i data-lucide="folder-open" style="width: 28px; height: 28px; color: white;"></i>
    </div>

    <h1 style="margin: 0; font-size: var(--font-h1); font-weight: 800; letter-spacing: -0.5px;"><?= htmlspecialchars($tag['name']) ?></h1>
    <p style="margin-top: 4px; font-size: var(--font-body-sm); opacity: 0.9; max-width: 600px; margin-left: auto; margin-right: auto;">
        <?= htmlspecialchars($tag['description']) ?: count($songs) . ' músicas' ?>
    </p>
</div>

<!-- Lista de Músicas -->
<div style="margin-top: -30px; position: relative; padding: 0 16px;">
    <?php if (empty($songs)): ?>
        <div class="empty-state">
            <div style="background: var(--bg-surface); width: 56px; height: 56px; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 12px;">
                <i data-lucide="music" style="width: 28px; color: var(--text-muted);"></i>
            </div>
            <h3 style="color: var(--text-primary); font-size: var(--font-h3); margin-bottom: 6px;">Pasta Vazia</h3>
            <p style="color: var(--text-secondary); font-size: var(--font-body-sm);">Nenhuma música vinculada.</p>
            <a href="repertorio.php" style="display: inline-block; margin-top: 12px; color: <?= $tag['color'] ?: 'var(--sage-600)' ?>; font-weight: 700; font-size: var(--font-body);">Ir para Repertório</a>
        </div>
    <?php else: ?>
        <h3 style="margin-bottom: 12px; font-size: var(--font-body-sm); font-weight: 700; color: var(--text-secondary); text-transform: uppercase;">Músicas (<?= count($songs) ?>)</h3>

        <?php foreach ($songs as $song): ?>
            <a href="musica_detalhe.php?id=<?= $song['id'] ?>" class="song-card ripple">
                <div class="song-icon">
                    <i data-lucide="music" style="width: 20px;"></i>
                </div>
                <div style="flex: 1;">
                    <div style="font-weight: 600; color: var(--text-primary); font-size: var(--font-body); margin-bottom: 2px;">
                        <?= htmlspecialchars($song['title']) ?>
                    </div>
                    <div style="font-size: var(--font-caption); color: var(--text-secondary);">
                        <?= htmlspecialchars($song['artist']) ?>
                        <?php if ($song['tone']): ?> • <strong style="color: var(--primary);"><?= $song['tone'] ?></strong><?php endif; ?>
                    </div>
                </div>
                <i data-lucide="chevron-right" style="width: 16px; color: var(--text-muted);"></i>
            </a>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<!-- Botão Editar Pasta (Flutuante) -->
<a href="classificacoes.php" class="ripple" style="
    position: fixed; 
    bottom: 20px; 
    right: 20px; 
    width: 48px; 
    height: 48px; 
    border-radius: 50%; 
    background: <?= $tag['color'] ?: 'var(--sage-600)' ?>; 
    color: white; 
    display: flex; 
    align-items: center; 
    justify-content: center; 
    box-shadow: 0 4px 12px rgba(0,0,0,0.3);
    z-index: 100;
">
    <i data-lucide="edit-2" style="width: 20px; height: 20px;"></i>
</a>

<?php renderAppFooter(); ?>