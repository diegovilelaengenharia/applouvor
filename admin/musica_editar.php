<?php
// admin/musica_editar.php
require_once '../includes/db.php';
require_once '../includes/layout.php';

// Buscar todas as tags
$allTags = $pdo->query("SELECT * FROM tags ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC);

// Se for editar, buscar tags já selecionadas
$selectedTagIds = [];
if (isset($_GET['id'])) {
    $id = $_GET['id'];
    $stmtTags = $pdo->prepare("SELECT tag_id FROM song_tags WHERE song_id = ?");
    $stmtTags->execute([$id]);
    $selectedTagIds = $stmtTags->fetchAll(PDO::FETCH_COLUMN);
}

if (!isset($_GET['id'])) {
    header('Location: repertorio.php');
    exit;
}

$id = $_GET['id'];

// Buscar artistas únicos para autocomplete
$artists = $pdo->query("SELECT DISTINCT artist FROM songs ORDER BY artist ASC")->fetchAll(PDO::FETCH_COLUMN);

// Buscar música
$stmt = $pdo->prepare("SELECT * FROM songs WHERE id = ?");
$stmt->execute([$id]);
$song = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$song) {
    header('Location: repertorio.php');
    exit;
}

// Decodificar campos customizados
$customFields = [];
if (!empty($song['custom_fields'])) {
    $customFields = json_decode($song['custom_fields'], true) ?: [];
}

// Processar formulário
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Processar campos customizados
    $newCustomFields = [];
    if (!empty($_POST['custom_field_name']) && !empty($_POST['custom_field_link'])) {
        foreach ($_POST['custom_field_name'] as $index => $name) {
            if (!empty($name) && !empty($_POST['custom_field_link'][$index])) {
                $newCustomFields[] = [
                    'name' => trim($name),
                    'link' => trim($_POST['custom_field_link'][$index])
                ];
            }
        }
    }
    $customFieldsJson = !empty($newCustomFields) ? json_encode($newCustomFields) : null;

    // Pegar nome da primeira tag para preencher category (legacy)
    $categoryLegacy = 'Outros';
    if (!empty($_POST['selected_tags'])) {
        $firstTagId = $_POST['selected_tags'][0];
        foreach ($allTags as $t) {
            if ($t['id'] == $firstTagId) {
                $categoryLegacy = $t['name'];
                break;
            }
        }
    } else {
        $categoryLegacy = $song['category'];
    }

    $stmt = $pdo->prepare("
        UPDATE songs SET 
            title = ?, artist = ?, tone = ?, bpm = ?, duration = ?, category = ?,
            link_letra = ?, link_cifra = ?, link_audio = ?, link_video = ?,
            link_spotify = ?, link_youtube = ?, link_apple_music = ?, link_deezer = ?,
            tags = ?, notes = ?, custom_fields = ?
        WHERE id = ?
    ");

    $stmt->execute([
        $_POST['title'],
        $_POST['artist'],
        $_POST['tone'] ?: null,
        $_POST['bpm'] ?: null,
        $_POST['duration'] ?: null,
        $categoryLegacy,
        $_POST['link_letra'] ?: null,
        $_POST['link_cifra'] ?: null,
        $_POST['link_audio'] ?: null,
        $_POST['link_video'] ?: null,
        $_POST['link_spotify'] ?: null,
        $_POST['link_youtube'] ?: null,
        $_POST['link_apple_music'] ?: null,
        $_POST['link_deezer'] ?: null,
        $_POST['tags'] ?: null,
        $_POST['notes'] ?: null,
        $customFieldsJson,
        $id
    ]);

    // Atualizar Tags Relacionadas
    $pdo->prepare("DELETE FROM song_tags WHERE song_id = ?")->execute([$id]);

    if (!empty($_POST['selected_tags'])) {
        $stmtTag = $pdo->prepare("INSERT INTO song_tags (song_id, tag_id) VALUES (?, ?)");
        foreach ($_POST['selected_tags'] as $tagId) {
            $stmtTag->execute([$id, $tagId]);
        }
    }

    header("Location: musica_detalhe.php?id=$id");
    exit;
}

renderAppHeader('Editar Música');
?>

<style>
    body {
        background: var(--bg-body);
    }

    .compact-container {
        max-width: 900px;
        margin: 0 auto;
        padding: 16px;
    }

    .header-bar {
        display: flex;
        align-items: center;
        gap: 12px;
        margin-bottom: 20px;
    }

    .btn-back {
        width: 40px;
        height: 40px;
        border-radius: 50%;
        border: none;
        background: var(--bg-surface);
        color: var(--text-main);
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        transition: all 0.2s;
    }

    .btn-back:hover {
        background: var(--border-color);
    }

    .page-title {
        font-size: 1.5rem;
        font-weight: 800;
        color: var(--text-main);
        margin: 0;
    }

    /* Compact Form Card */
    .form-card {
        background: var(--bg-surface);
        border: 1px solid var(--border-color);
        border-radius: 16px;
        padding: 20px;
        margin-bottom: 16px;
        box-shadow: var(--shadow-sm);
    }

    .card-title {
        font-size: 0.75rem;
        font-weight: 800;
        color: var(--text-muted);
        text-transform: uppercase;
        letter-spacing: 0.5px;
        margin-bottom: 16px;
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .form-grid {
        display: grid;
        gap: 12px;
    }

    .form-grid-2 {
        grid-template-columns: 1fr 1fr;
    }

    .form-grid-3 {
        grid-template-columns: 1fr 1fr 1fr;
    }

    .form-group {
        margin-bottom: 0;
    }

    .form-label {
        font-size: 0.8rem;
        font-weight: 600;
        color: var(--text-muted);
        margin-bottom: 6px;
        display: block;
    }

    .form-input {
        width: 100%;
        padding: 10px 12px;
        border-radius: 8px;
        border: 1px solid var(--border-color);
        background: var(--bg-body);
        color: var(--text-main);
        font-size: 0.9rem;
        transition: all 0.2s;
    }

    .form-input:focus {
        outline: none;
        border-color: var(--primary);
        box-shadow: 0 0 0 3px rgba(4, 120, 87, 0.1);
    }

    /* Tag Pills Compact */
    .tag-pills {
        display: flex;
        flex-wrap: wrap;
        gap: 8px;
    }

    .tag-pill-compact {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 6px 12px;
        border-radius: 20px;
        background: var(--bg-body);
        border: 2px solid transparent;
        font-size: 0.8rem;
        font-weight: 600;
        color: var(--text-muted);
        cursor: pointer;
        transition: all 0.2s;
    }

    .tag-pill-compact input {
        display: none;
    }

    .tag-pill-compact input:checked+label {
        background: var(--primary-subtle);
        border-color: var(--primary);
        color: var(--primary);
    }

    .tag-dot {
        width: 8px;
        height: 8px;
        border-radius: 50%;
    }

    /* Action Buttons Compact */
    .btn-action {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        padding: 10px 16px;
        border-radius: 10px;
        border: none;
        font-weight: 600;
        font-size: 0.9rem;
        cursor: pointer;
        transition: all 0.2s;
    }

    .btn-primary {
        background: linear-gradient(135deg, var(--primary), var(--primary-hover));
        color: white;
        box-shadow: 0 2px 8px rgba(4, 120, 87, 0.2);
    }

    .btn-secondary {
        background: var(--bg-body);
        color: var(--text-muted);
        border: 1px solid var(--border-color);
    }

    .btn-link {
        background: none;
        border: none;
        color: var(--primary);
        font-weight: 600;
        font-size: 0.85rem;
        cursor: pointer;
        padding: 8px 12px;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        gap: 6px;
    }

    /* Modal Styles */
    .modal-overlay {
        display: none;
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: rgba(0, 0, 0, 0.5);
        backdrop-filter: blur(4px);
        z-index: 1000;
        align-items: center;
        justify-content: center;
        padding: 20px;
    }

    .modal-overlay.active {
        display: flex;
    }

    .modal-content {
        background: var(--bg-surface);
        border-radius: 20px;
        padding: 24px;
        width: 100%;
        max-width: 600px;
        max-height: 80vh;
        overflow-y: auto;
        animation: slideUp 0.3s ease-out;
    }

    @keyframes slideUp {
        from {
            opacity: 0;
            transform: translateY(20px);
        }

        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    .modal-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 20px;
    }

    .modal-title {
        font-size: 1.25rem;
        font-weight: 700;
        color: var(--text-main);
        margin: 0;
    }

    .btn-close {
        width: 32px;
        height: 32px;
        border-radius: 50%;
        border: none;
        background: var(--bg-body);
        color: var(--text-muted);
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    /* Link Preview Cards */
    .link-preview {
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 12px;
        background: var(--bg-body);
        border: 1px solid var(--border-color);
        border-radius: 10px;
        margin-bottom: 8px;
    }

    .link-icon {
        width: 36px;
        height: 36px;
        border-radius: 8px;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
    }

    .link-info {
        flex: 1;
        min-width: 0;
    }

    .link-label {
        font-size: 0.75rem;
        font-weight: 600;
        color: var(--text-muted);
        margin-bottom: 2px;
    }

    .link-url {
        font-size: 0.8rem;
        color: var(--text-main);
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    @media (max-width: 768px) {

        .form-grid-2,
        .form-grid-3 {
            grid-template-columns: 1fr;
        }
    }
</style>

<div class="compact-container">
    <!-- Header -->
    <div class="header-bar">
        <a href="musica_detalhe.php?id=<?= $id ?>" class="btn-back">
            <i data-lucide="arrow-left" style="width: 20px;"></i>
        </a>
        <h1 class="page-title">Editar Música</h1>
    </div>

    <form method="POST">
        <!-- Card 1: Informações Principais -->
        <div class="form-card">
            <div class="card-title">
                <i data-lucide="music" style="width: 14px;"></i>
                Informações Principais
            </div>

            <div class="form-grid">
                <div class="form-group">
                    <label class="form-label">Título *</label>
                    <input type="text" name="title" class="form-input" value="<?= htmlspecialchars($song['title']) ?>" required>
                </div>

                <div class="form-grid form-grid-2">
                    <div class="form-group">
                        <label class="form-label">Artista *</label>
                        <input type="text" name="artist" class="form-input" value="<?= htmlspecialchars($song['artist']) ?>" required>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Tom</label>
                        <input type="text" name="tone" class="form-input" value="<?= htmlspecialchars($song['tone']) ?>" placeholder="Ex: G, Am">
                    </div>
                </div>

                <div class="form-grid form-grid-3">
                    <div class="form-group">
                        <label class="form-label">BPM</label>
                        <input type="number" name="bpm" class="form-input" value="<?= $song['bpm'] ?>" placeholder="120">
                    </div>

                    <div class="form-group">
                        <label class="form-label">Duração</label>
                        <input type="text" name="duration" class="form-input" value="<?= htmlspecialchars($song['duration']) ?>" placeholder="3:45">
                    </div>

                    <div class="form-group">
                        <label class="form-label">&nbsp;</label>
                        <button type="button" onclick="openLinksModal()" class="btn-action btn-secondary" style="width: 100%;">
                            <i data-lucide="link" style="width: 16px;"></i>
                            Links
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Card 2: Classificações -->
        <div class="form-card">
            <div class="card-title">
                <i data-lucide="folder" style="width: 14px;"></i>
                Classificações
                <button type="button" onclick="openTagManager()" class="btn-link" style="margin-left: auto;">
                    <i data-lucide="settings" style="width: 14px;"></i>
                    Gerenciar
                </button>
            </div>

            <div class="tag-pills">
                <?php foreach ($allTags as $tag):
                    $isChecked = in_array($tag['id'], $selectedTagIds);
                ?>
                    <label class="tag-pill-compact" style="<?= $isChecked ? 'background: var(--primary-subtle); border-color: var(--primary); color: var(--primary);' : '' ?>">
                        <input type="checkbox" name="selected_tags[]" value="<?= $tag['id'] ?>" <?= $isChecked ? 'checked' : '' ?> onchange="this.parentElement.style.background = this.checked ? 'var(--primary-subtle)' : 'var(--bg-body)'; this.parentElement.style.borderColor = this.checked ? 'var(--primary)' : 'transparent'; this.parentElement.style.color = this.checked ? 'var(--primary)' : 'var(--text-muted)';">
                        <span class="tag-dot" style="background: <?= $tag['color'] ?: '#047857' ?>;"></span>
                        <?= htmlspecialchars($tag['name']) ?>
                    </label>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Card 3: Observações -->
        <div class="form-card">
            <div class="card-title">
                <i data-lucide="message-square" style="width: 14px;"></i>
                Observações
            </div>

            <div class="form-group">
                <textarea name="notes" class="form-input" rows="3" style="resize: vertical;" placeholder="Adicione observações sobre a música..."><?= htmlspecialchars($song['notes']) ?></textarea>
            </div>
        </div>

        <!-- Botões de Ação -->
        <div style="display: flex; gap: 12px; padding-bottom: 80px;">
            <a href="musica_detalhe.php?id=<?= $id ?>" class="btn-action btn-secondary" style="flex: 1; text-decoration: none;">
                Cancelar
            </a>
            <button type="submit" class="btn-action btn-primary" style="flex: 2;">
                <i data-lucide="save" style="width: 18px;"></i>
                Salvar Alterações
            </button>
        </div>
    </form>
</div>

<!-- Modal: Links e Referências -->
<div id="linksModal" class="modal-overlay">
    <div class="modal-content">
        <div class="modal-header">
            <h3 class="modal-title">Links e Referências</h3>
            <button type="button" onclick="closeLinksModal()" class="btn-close">
                <i data-lucide="x" style="width: 18px;"></i>
            </button>
        </div>

        <div style="display: grid; gap: 16px;">
            <!-- Referências Básicas -->
            <div>
                <h4 style="font-size: 0.85rem; font-weight: 700; color: var(--text-muted); margin-bottom: 12px; text-transform: uppercase;">Referências</h4>

                <div class="form-group" style="margin-bottom: 12px;">
                    <label class="form-label">Letra</label>
                    <input type="url" name="link_letra" id="link_letra" class="form-input" value="<?= htmlspecialchars($song['link_letra']) ?>" placeholder="https://">
                </div>

                <div class="form-group" style="margin-bottom: 12px;">
                    <label class="form-label">Cifra</label>
                    <input type="url" name="link_cifra" id="link_cifra" class="form-input" value="<?= htmlspecialchars($song['link_cifra']) ?>" placeholder="https://">
                </div>

                <div class="form-group" style="margin-bottom: 12px;">
                    <label class="form-label">Áudio</label>
                    <input type="url" name="link_audio" id="link_audio" class="form-input" value="<?= htmlspecialchars($song['link_audio']) ?>" placeholder="https://">
                </div>

                <div class="form-group">
                    <label class="form-label">Vídeo</label>
                    <input type="url" name="link_video" id="link_video" class="form-input" value="<?= htmlspecialchars($song['link_video']) ?>" placeholder="https://">
                </div>
            </div>

            <!-- Plataformas de Streaming -->
            <div>
                <h4 style="font-size: 0.85rem; font-weight: 700; color: var(--text-muted); margin-bottom: 12px; text-transform: uppercase;">Streaming</h4>

                <div class="form-group" style="margin-bottom: 12px;">
                    <label class="form-label">Spotify</label>
                    <input type="url" name="link_spotify" id="link_spotify" class="form-input" value="<?= htmlspecialchars($song['link_spotify']) ?>" placeholder="https://open.spotify.com/...">
                </div>

                <div class="form-group" style="margin-bottom: 12px;">
                    <label class="form-label">YouTube</label>
                    <input type="url" name="link_youtube" id="link_youtube" class="form-input" value="<?= htmlspecialchars($song['link_youtube']) ?>" placeholder="https://youtube.com/...">
                </div>

                <div class="form-group" style="margin-bottom: 12px;">
                    <label class="form-label">Apple Music</label>
                    <input type="url" name="link_apple_music" id="link_apple_music" class="form-input" value="<?= htmlspecialchars($song['link_apple_music']) ?>" placeholder="https://music.apple.com/...">
                </div>

                <div class="form-group">
                    <label class="form-label">Deezer</label>
                    <input type="url" name="link_deezer" id="link_deezer" class="form-input" value="<?= htmlspecialchars($song['link_deezer']) ?>" placeholder="https://deezer.com/...">
                </div>
            </div>
        </div>

        <button type="button" onclick="closeLinksModal()" class="btn-action btn-primary" style="width: 100%; margin-top: 20px;">
            Concluído
        </button>
    </div>
</div>

<!-- Modal: Gestão de Tags (simplificado) -->
<div id="tagManagerModal" class="modal-overlay">
    <div class="modal-content">
        <div class="modal-header">
            <h3 class="modal-title">Gerenciar Classificações</h3>
            <button type="button" onclick="closeTagManager()" class="btn-close">
                <i data-lucide="x" style="width: 18px;"></i>
            </button>
        </div>

        <p style="font-size: 0.9rem; color: var(--text-muted); margin-bottom: 16px;">
            Para gerenciar classificações completas, acesse a página dedicada.
        </p>

        <a href="classificacoes.php" class="btn-action btn-primary" style="width: 100%; text-decoration: none;">
            <i data-lucide="folder-plus" style="width: 18px;"></i>
            Abrir Gestão Completa
        </a>
    </div>
</div>

<script>
    // Modal de Links
    function openLinksModal() {
        document.getElementById('linksModal').classList.add('active');
        lucide.createIcons();
    }

    function closeLinksModal() {
        document.getElementById('linksModal').classList.remove('active');
    }

    // Modal de Tags
    function openTagManager() {
        document.getElementById('tagManagerModal').classList.add('active');
        lucide.createIcons();
    }

    function closeTagManager() {
        document.getElementById('tagManagerModal').classList.remove('active');
    }

    // Fechar modais ao clicar fora
    document.querySelectorAll('.modal-overlay').forEach(modal => {
        modal.addEventListener('click', function(e) {
            if (e.target === this) {
                this.classList.remove('active');
            }
        });
    });

    // Inicializar ícones
    lucide.createIcons();
</script>

<?php renderAppFooter(); ?>