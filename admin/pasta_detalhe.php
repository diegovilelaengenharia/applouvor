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
        background: white;
        border: 1px solid var(--border-subtle);
        border-radius: 16px;
        padding: 16px;
        margin-bottom: 12px;
        display: flex;
        align-items: center;
        gap: 16px;
        text-decoration: none;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.02);
        transition: transform 0.2s;
    }

    .song-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
    }

    .song-icon {
        width: 48px;
        height: 48px;
        background: #f1f5f9;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        color: #64748b;
    }
</style>

<!-- Hero da Pasta Compacto -->
<div style="
    background: <?= $tag['color'] ?: '#047857' ?>; 
    margin: -24px -16px 24px -16px; 
    padding: 24px 20px 48px 20px; 
    border-radius: 0 0 24px 24px; 
    box-shadow: var(--shadow-md);
    color: white;
    text-align: center;
    position: relative;
">
    <!-- Nav -->
    <div style="position: absolute; top: 16px; left: 16px;">
        <a href="repertorio.php?tab=pastas" style="
            color: white; text-decoration: none; display: flex; align-items: center; gap: 6px; 
            font-weight: 600; background: rgba(0,0,0,0.2); padding: 8px 12px; border-radius: 20px; font-size: 0.85rem;
        ">
            <i data-lucide="arrow-left" style="width: 16px;"></i> Voltar
        </a>
    </div>

    <!-- Icone Grande -->
    <div style="
        width: 64px; 
        height: 64px; 
        background: rgba(255,255,255,0.2); 
        border-radius: 16px; 
        display: inline-flex; 
        align-items: center; 
        justify-content: center; 
        margin-bottom: 12px;
        backdrop-filter: blur(4px);
    ">
        <i data-lucide="folder-open" style="width: 32px; height: 32px; color: white;"></i>
    </div>

    <h1 style="margin: 0; font-size: 1.5rem; font-weight: 800; letter-spacing: -0.5px;"><?= htmlspecialchars($tag['name']) ?></h1>
    <p style="margin-top: 6px; font-size: 0.9rem; opacity: 0.9; max-width: 600px; margin-left: auto; margin-right: auto;">
        <?= htmlspecialchars($tag['description']) ?: count($songs) . ' músicas classificadas' ?>
    </p>
</div>

<!-- Lista de Músicas -->
<div style="margin-top: -40px; position: relative; padding: 0 16px;">
    <?php if (empty($songs)): ?>
        <div style="background: white; border-radius: 20px; padding: 40px; text-align: center; box-shadow: var(--shadow-sm);">
            <div style="background: #f1f5f9; width: 64px; height: 64px; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 16px;">
                <i data-lucide="music" style="width: 32px; color: #94a3b8;"></i>
            </div>
            <h3 style="color: #334155; font-size: 1.1rem; margin-bottom: 8px;">Pasta Vazia</h3>
            <p style="color: #64748b; font-size: 0.95rem;">Nenhuma música foi marcada com esta classificação ainda.</p>
            <a href="repertorio.php" style="display: inline-block; margin-top: 16px; color: <?= $tag['color'] ?: '#047857' ?>; font-weight: 700;">Ir para Repertório</a>
        </div>
    <?php else: ?>
        <h3 style="margin-bottom: 16px; font-size: 0.9rem; font-weight: 700; color: #64748b; text-transform: uppercase;">Músicas nesta pasta (<?= count($songs) ?>)</h3>

        <?php foreach ($songs as $song): ?>
            <a href="musica_detalhe.php?id=<?= $song['id'] ?>" class="song-card ripple">
                <div class="song-icon">
                    <i data-lucide="music" style="width: 24px;"></i>
                </div>
                <div style="flex: 1;">
                    <div style="font-weight: 700; color: var(--text-primary); font-size: 1rem; margin-bottom: 2px;">
                        <?= htmlspecialchars($song['title']) ?>
                    </div>
                    <div style="font-size: 0.85rem; color: var(--text-secondary);">
                        <?= htmlspecialchars($song['artist']) ?>
                        <?php if ($song['tone']): ?> • <strong style="color: var(--primary-green);"><?= $song['tone'] ?></strong><?php endif; ?>
                    </div>
                </div>
                <i data-lucide="chevron-right" style="width: 20px; color: var(--text-muted);"></i>
            </a>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<!-- Botão Editar Pasta (Flutuante) -->
<a href="classificacoes.php" class="ripple" style="
    position: fixed; 
    bottom: 24px; 
    right: 24px; 
    width: 56px; 
    height: 56px; 
    border-radius: 50%; 
    background: <?= $tag['color'] ?: '#047857' ?>; 
    color: white; 
    display: flex; 
    align-items: center; 
    justify-content: center; 
    box-shadow: 0 4px 12px rgba(0,0,0,0.3);
    z-index: 100;
">
    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
        <path d="M12.22 2h-.44a2 2 0 0 0-2 2v.18a2 2 0 0 1-1 1.73l-.43.25a2 2 0 0 1-2 0l-.15-.08a2 2 0 0 0-2.73.73l-.22.38a2 2 0 0 0 .73 2.73l.15.1a2 2 0 0 1 1 1.72v.51a2 2 0 0 1-1 1.74l-.15.09a2 2 0 0 0-.73 2.73l.22.38a2 2 0 0 0 2.73.73l.15-.08a2 2 0 0 1 2 0l.43.25a2 2 0 0 1 1 1.73V20a2 2 0 0 0 2 2h.44a2 2 0 0 0 2-2v-.18a2 2 0 0 1 1-1.73l.43-.25a2 2 0 0 1 2 0l.15.08a2 2 0 0 0 2.73-.73l.22-.39a2 2 0 0 0-.73-2.73l-.15-.08a2 2 0 0 1-1-1.74v-.5a2 2 0 0 1 1-1.74l.15-.09a2 2 0 0 0 .73-2.73l-.22-.38a2 2 0 0 0-2.73-.73l-.15.08a2 2 0 0 1-2 0l-.43-.25a2 2 0 0 1-1-1.73V4a2 2 0 0 0-2-2z" />
        <circle cx="12" cy="12" r="3" />
    </svg>
</a>

<?php renderAppFooter(); ?>