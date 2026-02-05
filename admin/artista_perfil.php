<?php
// admin/artista_perfil.php - Perfil do Artista
require_once '../includes/db.php';
require_once '../includes/layout.php';

$artistName = $_GET['artist'] ?? null;
if (!$artistName) {
    header('Location: repertorio.php?tab=artistas');
    exit;
}

// Atualizar nome do artista (se enviado)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_artist'])) {
    $newName = trim($_POST['new_name']);
    if (!empty($newName)) {
        $stmt = $pdo->prepare("UPDATE songs SET artist = ? WHERE artist = ?");
        $stmt->execute([$newName, $artistName]);
        header("Location: artista_perfil.php?artist=" . urlencode($newName));
        exit;
    }
}

// Buscar músicas do artista
$stmt = $pdo->prepare("
    SELECT id, title, artist, tone, bpm, category, created_at
    FROM songs 
    WHERE artist = ?
    ORDER BY title ASC
");
$stmt->execute([$artistName]);
$songs = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Estatísticas
$totalSongs = count($songs);
$avgBpm = 0;
$tones = [];
$categories = [];

foreach ($songs as $song) {
    if ($song['bpm']) $avgBpm += $song['bpm'];
    if ($song['tone']) $tones[] = $song['tone'];
    if ($song['category']) $categories[] = $song['category'];
}

$avgBpm = $totalSongs > 0 ? round($avgBpm / $totalSongs) : 0;
$mostUsedTone = !empty($tones) ? array_count_values($tones) : [];
arsort($mostUsedTone);
$mostUsedTone = !empty($mostUsedTone) ? array_key_first($mostUsedTone) : '-';

renderAppHeader('Artista');
renderPageHeader($artistName, 'Perfil do Artista');
?>

<!-- Header Card -->
<div style="max-width: 800px; margin: 0 auto 20px; padding: 0 16px;">
    <div style="background: linear-gradient(135deg, var(--lavender-600), #7c3aed); border-radius: 16px; padding: 20px; color: white; box-shadow: 0 4px 12px rgba(139, 92, 246, 0.3);">
        <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 16px;">
            <div style="flex: 1;">
                <div style="
                    width: 80px; height: 80px; border-radius: 50%;
                    background: rgba(255,255,255,0.2);
                    color: white; display: flex; align-items: center; justify-content: center;
                    font-weight: 700; font-size: 2rem; margin-bottom: 12px;
                    border: 3px solid rgba(255,255,255,0.3);
                ">
                    <?= strtoupper(substr($artistName, 0, 1)) ?>
                </div>
                <h1 style="margin: 0 0 8px 0; font-size: var(--font-display); font-weight: 700;"><?= htmlspecialchars($artistName) ?></h1>
                <div style="font-size: var(--font-body); opacity: 0.9;"><?= $totalSongs ?> música<?= $totalSongs != 1 ? 's' : '' ?> no repertório</div>
            </div>
            
            <!-- Botão Editar -->
            <button onclick="openEditModal()" style="
                padding: 10px 16px; border-radius: 10px;
                background: rgba(255,255,255,0.2); border: 1px solid rgba(255,255,255,0.3);
                color: white; cursor: pointer;
                display: flex; align-items: center; gap: 6px;
                font-weight: 600; font-size: var(--font-body-sm);
                transition: all 0.2s;
            ">
                <i data-lucide="edit-2" style="width: 16px;"></i>
                <span>Editar</span>
            </button>
        </div>
        
        <!-- Stats Row -->
        <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 12px; margin-top: 16px;">
            <div style="background: rgba(255,255,255,0.15); padding: 12px; border-radius: 12px; text-align: center;">
                <div style="font-size: var(--font-h1); font-weight: 700;"><?= $totalSongs ?></div>
                <div style="font-size: var(--font-caption); opacity: 0.8;">Músicas</div>
            </div>
            <div style="background: rgba(255,255,255,0.15); padding: 12px; border-radius: 12px; text-align: center;">
                <div style="font-size: var(--font-h1); font-weight: 700;"><?= $avgBpm ?: '-' ?></div>
                <div style="font-size: var(--font-caption); opacity: 0.8;">BPM Médio</div>
            </div>
            <div style="background: rgba(255,255,255,0.15); padding: 12px; border-radius: 12px; text-align: center;">
                <div style="font-size: var(--font-h1); font-weight: 700;"><?= $mostUsedTone ?></div>
                <div style="font-size: var(--font-caption); opacity: 0.8;">Tom Mais Usado</div>
            </div>
        </div>
    </div>
</div>

<!-- Músicas -->
<div style="max-width: 800px; margin: 0 auto; padding: 0 16px 100px;">
    <h3 style="font-size: var(--font-h3); font-weight: 700; color: var(--text-main); margin: 0 0 16px 0; display: flex; align-items: center; gap: 8px;">
        <i data-lucide="music" style="width: 20px; color: var(--primary);"></i>
        Músicas
    </h3>
    
    <?php if (empty($songs)): ?>
        <div style="text-align: center; padding: 40px 20px; background: var(--bg-surface); border-radius: 12px; border: 1px dashed var(--border-color);">
            <i data-lucide="music-2" style="width: 32px; color: var(--text-muted); margin-bottom: 8px;"></i>
            <p style="color: var(--text-muted); font-size: var(--font-body); margin: 0;">Nenhuma música encontrada</p>
        </div>
    <?php else: ?>
        <div style="display: flex; flex-direction: column; gap: 12px;">
            <?php foreach ($songs as $song): ?>
                <a href="musica_detalhe.php?id=<?= $song['id'] ?>" style="
                    background: var(--bg-surface); border-radius: 12px; padding: 14px; 
                    border: 1px solid var(--border-color); display: block; text-decoration: none;
                    transition: all 0.2s;
                " onmouseover="this.style.borderColor='var(--primary)'; this.style.transform='translateY(-2px)'"
                   onmouseout="this.style.borderColor='var(--border-color)'; this.style.transform='none'">
                    <div style="display: flex; gap: 12px; align-items: flex-start;">
                        <div style="
                            width: 48px; height: 48px; border-radius: 10px;
                            background: linear-gradient(135deg, var(--lavender-600), #7c3aed);
                            color: white; display: flex; align-items: center; justify-content: center;
                            font-weight: 700; font-size: var(--font-h2); flex-shrink: 0;
                        ">
                            <i data-lucide="music" style="width: 24px;"></i>
                        </div>
                        <div style="flex: 1;">
                            <h4 style="margin: 0 0 4px 0; font-size: var(--font-h3); font-weight: 700; color: var(--text-main);"><?= htmlspecialchars($song['title']) ?></h4>
                            <div style="display: flex; gap: 6px; flex-wrap: wrap; margin-top: 8px;">
                                <?php if ($song['category']): ?>
                                    <span style="background: var(--slate-50); color: var(--slate-600); padding: 4px 10px; border-radius: 6px; font-size: var(--font-caption); font-weight: 700; border: 1px solid var(--slate-100);">
                                        <?= htmlspecialchars($song['category']) ?>
                                    </span>
                                <?php endif; ?>
                                <?php if ($song['tone']): ?>
                                    <span style="background: #fff7ed; color: #ea580c; padding: 4px 10px; border-radius: 6px; font-size: var(--font-caption); font-weight: 700; border: 1px solid #ffedd5;">
                                        TOM: <?= $song['tone'] ?>
                                    </span>
                                <?php endif; ?>
                                <?php if ($song['bpm']): ?>
                                    <span style="background: var(--rose-50); color: var(--rose-600); padding: 4px 10px; border-radius: 6px; font-size: var(--font-caption); font-weight: 700; border: 1px solid var(--rose-100);">
                                        <?= $song['bpm'] ?> BPM
                                    </span>
                                <?php endif; ?>
                            </div>
                        </div>
                        <i data-lucide="chevron-right" style="width: 20px; color: var(--text-muted);"></i>
                    </div>
                </a>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<!-- Modal Editar Artista -->
<div id="editModal" style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.5); z-index: 100; align-items: center; justify-content: center;">
    <div style="background: white; width: 90%; max-width: 500px; border-radius: 20px; padding: 24px; animation: slideUp 0.3s ease;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px;">
            <h3 style="margin: 0; font-size: var(--font-h2); color: var(--slate-800);">Editar Artista</h3>
            <button onclick="closeEditModal()" style="background: none; border: none; font-size: var(--font-display); color: var(--slate-400); cursor: pointer;">&times;</button>
        </div>
        
        <form method="POST">
            <input type="hidden" name="update_artist" value="1">
            
            <div style="margin-bottom: 20px;">
                <label style="display: block; margin-bottom: 8px; font-weight: 600; color: #374151;">Nome do Artista</label>
                <input type="text" name="new_name" value="<?= htmlspecialchars($artistName) ?>" required style="
                    width: 100%; padding: 12px; border: 1px solid #d1d5db; border-radius: 8px;
                    font-size: var(--font-body); outline: none;
                " onfocus="this.style.borderColor='var(--primary)'" onblur="this.style.borderColor='#d1d5db'">
            </div>
            
            <div style="background: var(--yellow-50); padding: 12px; border-radius: 8px; border: 1px solid var(--yellow-100); margin-bottom: 20px;">
                <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 4px;">
                    <i data-lucide="alert-triangle" style="width: 16px; color: var(--yellow-500);"></i>
                    <span style="font-size: var(--font-body-sm); font-weight: 700; color: var(--yellow-500);">ATENÇÃO</span>
                </div>
                <p style="margin: 0; font-size: var(--font-body-sm); color: #78350f;">
                    Alterar o nome do artista irá atualizar <strong>todas as <?= $totalSongs ?> música<?= $totalSongs != 1 ? 's' : '' ?></strong> deste artista.
                </p>
            </div>
            
            <div style="display: flex; gap: 12px;">
                <button type="button" onclick="closeEditModal()" style="flex: 1; padding: 12px; background: white; border: 1px solid #d1d5db; color: #374151; border-radius: 12px; font-weight: 600; cursor: pointer;">Cancelar</button>
                <button type="submit" style="flex: 1; padding: 12px; background: var(--lavender-600); border: none; color: white; border-radius: 12px; font-weight: 600; cursor: pointer;">Salvar</button>
            </div>
        </form>
    </div>
</div>

<style>
@keyframes slideUp {
    from { transform: translateY(20px); opacity: 0; }
    to { transform: translateY(0); opacity: 1; }
}
</style>

<script>
function openEditModal() {
    document.getElementById('editModal').style.display = 'flex';
}

function closeEditModal() {
    document.getElementById('editModal').style.display = 'none';
}

// Fechar ao clicar fora
document.getElementById('editModal').addEventListener('click', function(e) {
    if (e.target === this) {
        closeEditModal();
    }
});
</script>

<?php renderAppFooter(); ?>
