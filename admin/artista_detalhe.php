<?php
// admin/artista_detalhe.php
require_once '../includes/db.php';
require_once '../includes/layout.php';

if (!isset($_GET['name'])) {
    header('Location: repertorio.php?tab=artistas');
    exit;
}

$artistName = urldecode($_GET['name']);

// Processar edição do nome do artista
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['new_name'])) {
    $newName = trim($_POST['new_name']);
    if (!empty($newName)) {
        $stmt = $pdo->prepare("UPDATE songs SET artist = ? WHERE artist = ?");
        $stmt->execute([$newName, $artistName]);
        header("Location: artista_detalhe.php?name=" . urlencode($newName));
        exit;
    }
}

// Buscar músicas do artista
$stmt = $pdo->prepare("SELECT * FROM songs WHERE artist = ? ORDER BY title ASC");
$stmt->execute([$artistName]);
$songs = $stmt->fetchAll(PDO::FETCH_ASSOC);

renderAppHeader('Artista');
?>

<style>
    .artist-header {
        text-align: center;
        padding: 32px 16px;
        background: linear-gradient(135deg, #8B5CF6 0%, #6D28D9 100%);
        border-radius: 16px;
        margin-bottom: 24px;
        color: white;
    }

    .artist-avatar-large {
        width: 100px;
        height: 100px;
        background: rgba(255, 255, 255, 0.2);
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 0 auto 16px;
        font-size: 2.5rem;
        font-weight: 800;
    }

    .artist-name {
        font-size: 1.8rem;
        font-weight: 800;
        margin-bottom: 8px;
    }

    .artist-count {
        font-size: 1rem;
        opacity: 0.9;
    }
</style>

<!-- Hero Header Compacto -->
<div style="
    background: linear-gradient(135deg, #8B5CF6 0%, #6D28D9 100%); 
    margin: -24px -16px 24px -16px; 
    padding: 24px 20px 48px 20px; 
    border-radius: 0 0 24px 24px; 
    box-shadow: var(--shadow-md);
    position: relative;
    overflow: visible;
">
    <!-- Navigation Row -->
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px;">
        <a href="repertorio.php?tab=artistas" class="ripple" style="
            width: 36px; 
            height: 36px; 
            border-radius: 12px; 
            display: flex; 
            align-items: center; 
            justify-content: center; 
            color: white; 
            background: rgba(255,255,255,0.2); 
            text-decoration: none;
            backdrop-filter: blur(4px);
        ">
            <i data-lucide="arrow-left" style="width: 18px;"></i>
        </a>

        <div style="display: flex; gap: 8px; align-items: center;">
            <!-- Edit Button -->
            <button onclick="openEditModal()" class="ripple" style="
                width: 36px; 
                height: 36px; 
                border-radius: 12px; 
                display: flex; 
                align-items: center; 
                justify-content: center; 
                color: white; 
                background: rgba(255,255,255,0.2); 
                border: none;
                cursor: pointer;
                backdrop-filter: blur(4px);
            ">
                <i data-lucide="edit-2" style="width: 16px;"></i>
            </button>

            <!-- Home Button -->
            <a href="index.php" class="ripple" style="
                width: 36px; height: 36px; border-radius: 12px; 
                background: rgba(255,255,255,0.2); backdrop-filter: blur(4px);
                display: flex; align-items: center; justify-content: center;
                color: white; text-decoration: none;
            ">
                <i data-lucide="home" style="width: 16px;"></i>
            </a>
        </div>
    </div>

    <!-- Artist Info in Hero -->
    <div style="text-align: center;">
        <div style="
            width: 64px; 
            height: 64px; 
            background: rgba(255, 255, 255, 0.2); 
            border-radius: 50%; 
            display: inline-flex; 
            align-items: center; 
            justify-content: center; 
            margin-bottom: 12px;
            font-size: 1.8rem;
            font-weight: 800;
            color: white;
            backdrop-filter: blur(4px);
            border: 2px solid rgba(255,255,255,0.3);
        ">
            <?= strtoupper(substr($artistName, 0, 1)) ?>
        </div>
        <h1 style="color: white; margin: 0; font-size: 1.5rem; font-weight: 800; letter-spacing: -0.5px; line-height: 1.2;"><?= htmlspecialchars($artistName) ?></h1>
        <p style="color: rgba(255,255,255,0.9); margin-top: 6px; font-weight: 500; font-size: 0.9rem;">
            <i data-lucide="music" style="width: 12px; display: inline; vertical-align: middle; margin-right: 4px;"></i>
            <?= count($songs) ?> música<?= count($songs) > 1 ? 's' : '' ?>
        </p>
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

<!-- Modal de Edição do Nome -->
<div id="modalEditArtist" class="bottom-sheet-overlay">
    <div class="bottom-sheet-content">
        <div class="sheet-header">Editar Nome do Artista</div>
        <form method="POST">
            <div class="form-group" style="margin-bottom: 24px;">
                <label class="form-label">Nome do Artista</label>
                <input type="text" name="new_name" class="form-input" value="<?= htmlspecialchars($artistName) ?>" required>
                <p style="font-size: 0.85rem; color: var(--text-secondary); margin-top: 8px;">
                    ⚠️ Isso atualizará o nome do artista em todas as <?= count($songs) ?> música<?= count($songs) > 1 ? 's' : '' ?>
                </p>
            </div>

            <div style="display: flex; gap: 12px;">
                <button type="button" onclick="closeSheet('modalEditArtist')" class="btn-outline ripple" style="flex: 1; justify-content: center;">
                    Cancelar
                </button>
                <button type="submit" class="btn-action-save ripple" style="flex: 1; justify-content: center;">
                    Salvar
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    function openEditModal() {
        document.getElementById('modalEditArtist').classList.add('active');
    }
</script>

<?php renderAppFooter(); ?>