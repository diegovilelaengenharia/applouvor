<?php
require_once '../includes/auth.php';
require_once '../includes/db.php';
require_once '../includes/layout.php';

// Processar Adição de Nova Música
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_song') {
    $stmt = $pdo->prepare("INSERT INTO library_songs (title, artist, version, key_note, bpm, category, link_lyrics, link_cifra, link_audio, link_video, observation) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

    $stmt->execute([
        $_POST['title'],
        $_POST['artist'],
        $_POST['version'],
        $_POST['key_note'],
        $_POST['bpm'] ?: null,
        $_POST['category'],
        $_POST['link_lyrics'],
        $_POST['link_cifra'],
        $_POST['link_audio'],
        $_POST['link_video'],
        $_POST['observation']
    ]);

    header("Location: repertorio.php");
    exit;
}

// Buscar Músicas
$search = $_GET['q'] ?? '';
$where = '';
$params = [];
if ($search) {
    $where = "WHERE title LIKE ? OR artist LIKE ?";
    $params = ["%$search%", "%$search%"];
}

$stmt = $pdo->prepare("SELECT * FROM library_songs $where ORDER BY title ASC");
$stmt->execute($params);
$songs = $stmt->fetchAll();

renderAppHeader('Repertório');
?>

<div class="container" style="padding-top: 20px; padding-bottom: 80px;">

    <!-- Busca -->
    <div style="margin-bottom: 20px;">
        <form method="GET" action="" style="display:flex; gap:10px;">
            <input type="text" name="q" class="form-input" placeholder="Buscar música, artista..." value="<?= htmlspecialchars($search) ?>" style="background-color: var(--bg-tertiary); border:none;">
        </form>
    </div>

    <!-- Lista de Músicas -->
    <div class="list-group">
        <?php foreach ($songs as $song): ?>
            <div class="list-item" style="display:block; padding: 15px; background: transparent; border-bottom: 1px solid var(--border-subtle); border-radius: 0;">

                <div style="display:flex; justify-content:space-between; align-items:flex-start;">
                    <div>
                        <h3 style="font-size: 1.1rem; color: var(--text-primary); margin-bottom: 4px;"><?= htmlspecialchars($song['title']) ?></h3>
                        <div style="font-size: 0.9rem; color: var(--text-secondary);">
                            <?= htmlspecialchars($song['artist']) ?>
                            <?php if ($song['version']): ?>
                                <span style="opacity:0.7;">(<?= htmlspecialchars($song['version']) ?>)</span>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php if ($song['key_note']): ?>
                        <span class="status-badge" style="background:var(--bg-tertiary); color: var(--text-primary); border:1px solid var(--border-subtle);">
                            <?= htmlspecialchars($song['key_note']) ?>
                        </span>
                    <?php endif; ?>
                </div>

                <!-- Detalhes Extras -->
                <div style="display:flex; gap: 15px; margin-top: 8px; font-size: 0.8rem; color: var(--text-secondary); align-items: center;">
                    <?php if ($song['bpm']): ?>
                        <span style="display:flex; align-items:center; gap:4px;"><i data-lucide="activity" style="width:14px;"></i> <?= $song['bpm'] ?> BPM</span>
                    <?php endif; ?>

                    <?php if ($song['category']): ?>
                        <span style="display:flex; align-items:center; gap:4px;"><i data-lucide="tag" style="width:14px;"></i> <?= htmlspecialchars($song['category']) ?></span>
                    <?php endif; ?>
                </div>

                <!-- Links / Badges -->
                <div style="display:flex; gap: 8px; margin-top: 12px;">
                    <?php if ($song['link_cifra']): ?>
                        <a href="<?= htmlspecialchars($song['link_cifra']) ?>" target="_blank" class="btn btn-outline" style="padding: 4px 10px; font-size: 0.75rem; height: auto;">Cifra</a>
                    <?php endif; ?>
                    <?php if ($song['link_video']): ?>
                        <a href="<?= htmlspecialchars($song['link_video']) ?>" target="_blank" class="btn btn-outline" style="padding: 4px 10px; font-size: 0.75rem; height: auto;">Vídeo</a>
                    <?php endif; ?>
                </div>

            </div>
        <?php endforeach; ?>
    </div>

    <!-- FAB -->
    <button onclick="document.getElementById('modalAddSong').classList.add('visible')"
        style="position: fixed; bottom: 90px; right: 20px; width: 56px; height: 56px; background-color: var(--accent-blue); border-radius: 50%; border: none; box-shadow: 0 4px 14px rgba(59, 130, 246, 0.4); color: white; display: flex; align-items: center; justify-content: center; cursor: pointer; z-index: 100;">
        <i data-lucide="plus"></i>
    </button>

</div>

<!-- Modal Adicionar Música - COMPLETO -->
<div id="modalAddSong" class="sidebar-overlay" style="z-index: 300; display: none; align-items: flex-end; justify-content: center;">
    <div class="card" style="width: 100%; max-width: 500px; margin: 0; border-radius: 24px 24px 0 0; animation: slideUp 0.3s ease-out; max-height: 90vh; overflow-y: auto;">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom: 20px;">
            <h3>Nova Música</h3>
            <button onclick="document.getElementById('modalAddSong').classList.remove('visible')" style="background:none; border:none; color:var(--text-secondary);"><i data-lucide="x"></i></button>
        </div>

        <form method="POST">
            <input type="hidden" name="action" value="add_song">

            <div class="form-group">
                <label class="form-label">Título *</label>
                <input type="text" name="title" class="form-input" required placeholder="Ex: Bondade de Deus">
            </div>

            <div style="display:flex; gap: 10px;">
                <div class="form-group" style="flex:1;">
                    <label class="form-label">Artista</label>
                    <input type="text" name="artist" class="form-input" placeholder="Ex: Isaías Saad">
                </div>
                <div class="form-group" style="flex:1;">
                    <label class="form-label">Versão</label>
                    <input type="text" name="version" class="form-input" placeholder="Ex: Ao Vivo">
                </div>
            </div>

            <div style="display:flex; gap: 10px;">
                <div class="form-group" style="flex:1;">
                    <label class="form-label">Tom</label>
                    <input type="text" name="key_note" class="form-input" placeholder="G">
                </div>
                <div class="form-group" style="flex:1;">
                    <label class="form-label">BPM</label>
                    <input type="number" name="bpm" class="form-input" placeholder="Ex: 70">
                </div>
            </div>

            <div class="form-group">
                <label class="form-label">Classificação</label>
                <select name="category" class="form-input">
                    <option value="">Selecione...</option>
                    <option value="Hino">Hino</option>
                    <option value="Louvor">Louvor (Rápida)</option>
                    <option value="Adoração">Adoração (Lenta)</option>
                    <option value="Especial">Especial</option>
                </select>
            </div>

            <h4 style="margin: 15px 0 10px; font-size: 0.9rem; color: var(--text-secondary); text-transform: uppercase;">Links</h4>

            <div class="form-group">
                <input type="url" name="link_cifra" class="form-input" placeholder="Link CifraClub">
            </div>
            <div class="form-group">
                <input type="url" name="link_video" class="form-input" placeholder="Link YouTube">
            </div>
            <div class="form-group">
                <input type="url" name="link_lyrics" class="form-input" placeholder="Link Letras">
            </div>
            <div class="form-group">
                <input type="url" name="link_audio" class="form-input" placeholder="Link Spotify/Audio">
            </div>

            <div class="form-group">
                <label class="form-label">Observações</label>
                <textarea name="observation" class="form-input" rows="3" placeholder="Detalhes de arranjo, início, etc..."></textarea>
            </div>

            <button type="submit" class="btn btn-primary w-full" style="margin-top: 15px;">Salvar Música</button>
        </form>
    </div>
</div>

<style>
    @keyframes slideUp {
        from {
            transform: translateY(100%);
        }

        to {
            transform: translateY(0);
        }
    }

    #modalAddSong.visible {
        display: flex !important;
        opacity: 1;
    }
</style>

<?php
renderAppFooter();
?>