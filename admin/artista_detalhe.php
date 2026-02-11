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

<link rel="stylesheet" href="../assets/css/pages/shared-pages.css">




<!-- Hero Header Compacto -->
<div style="
    background: var(--lavender-600); 
    margin: -16px -16px 20px -16px; 
    padding: 16px 16px 40px 16px; 
    border-radius: 0 0 20px 20px; 
    box-shadow: var(--shadow-sm);
    position: relative;
    color: white;
">
    <!-- Navbar Row -->
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px;">
        <a href="repertorio.php?tab=artistas" class="ripple" style="
            width: 32px; height: 32px; border-radius: 10px; 
            display: flex; align-items: center; justify-content: center; 
            color: white; background: rgba(255,255,255,0.2); 
            text-decoration: none;
            backdrop-filter: blur(4px);
        ">
            <i data-lucide="arrow-left" style="width: 18px;"></i>
        </a>

        <div style="display: flex; gap: 8px; align-items: center;">
            <button onclick="openEditModal()" class="ripple" style="
                width: 32px; height: 32px; border-radius: 10px; 
                display: flex; align-items: center; justify-content: center; 
                color: white; background: rgba(255,255,255,0.2); 
                border: none; cursor: pointer;
                backdrop-filter: blur(4px);
            ">
                <i data-lucide="edit-2" style="width: 16px;"></i>
            </button>
            <a href="index.php" class="ripple" style="
                width: 32px; height: 32px; border-radius: 10px; 
                background: rgba(255,255,255,0.2); backdrop-filter: blur(4px);
                display: flex; align-items: center; justify-content: center;
                color: white; text-decoration: none;
            ">
                <i data-lucide="home" style="width: 16px;"></i>
            </a>
        </div>
    </div>

    <!-- Artist Info -->
    <div style="text-align: center;">
        <div style="
            width: 56px; height: 56px; 
            background: rgba(255, 255, 255, 0.2); 
            border-radius: 50%; 
            display: inline-flex; align-items: center; justify-content: center; 
            margin-bottom: 10px;
            font-size: 1.5rem; font-weight: 800; color: white;
            backdrop-filter: blur(4px);
            border: 2px solid rgba(255,255,255,0.3);
        ">
            <?= strtoupper(substr($artistName, 0, 1)) ?>
        </div>
        <h1 style="color: white; margin: 0; font-size: 1.35rem; font-weight: 800; letter-spacing: -0.5px; line-height: 1.2;"><?= htmlspecialchars($artistName) ?></h1>
        <p style="color: rgba(255,255,255,0.9); margin-top: 4px; font-weight: 500; font-size: 0.85rem;">
            <?= count($songs) ?> música<?= count($songs) > 1 ? 's' : '' ?>
        </p>
    </div>
</div>

<!-- Lista de Músicas -->
<div style="margin-top: -30px; position: relative; padding: 0 16px;">
    <h3 class="section-title">Repertório</h3>

    <?php foreach ($songs as $song): ?>
        <a href="musica_detalhe.php?id=<?= $song['id'] ?>" class="song-card ripple">
            <div style="width: 40px; height: 40px; background: var(--primary); border-radius: 8px; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                <i data-lucide="music" style="width: 20px; height: 20px; color: white;"></i>
            </div>
            <div style="flex: 1; min-width: 0;">
                <div style="font-weight: 600; color: var(--text-primary); font-size: 0.95rem; margin-bottom: 2px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                    <?= htmlspecialchars($song['title']) ?>
                </div>
                <div style="display: flex; align-items: center; gap: 8px; font-size: 0.75rem; color: var(--text-muted);">
                    <?php if ($song['tone']): ?>
                        <span>Tom: <strong><?= htmlspecialchars($song['tone']) ?></strong></span>
                    <?php endif; ?>
                    <?php if ($song['bpm']): ?>
                        <span>• BPM: <?= $song['bpm'] ?></span>
                    <?php endif; ?>
                </div>
            </div>
            <i data-lucide="chevron-right" style="width: 16px; color: var(--text-muted);"></i>
        </a>
    <?php endforeach; ?>
</div>

<!-- Modal de Edição do Nome -->
<div id="modalEditArtist" class="bottom-sheet-overlay">
    <div class="bottom-sheet-content">
        <div class="sheet-header" style="font-size:1.1rem; margin-bottom:16px;">Editar Nome do Artista</div>
        <form method="POST">
            <div class="form-group" style="margin-bottom: 20px;">
                <label class="form-label">Nome do Artista</label>
                <input type="text" name="new_name" class="form-input" value="<?= htmlspecialchars($artistName) ?>" required>
                <p style="font-size: 0.8rem; color: var(--text-secondary); margin-top: 6px;">
                    ⚠️ Isso atualizará o nome em todas as <?= count($songs) ?> músicas.
                </p>
            </div>

            <div style="display: flex; gap: 10px;">
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