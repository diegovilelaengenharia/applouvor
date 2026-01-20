<?php
// admin/musica_editar.php
require_once '../includes/db.php';
require_once '../includes/layout.php';

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

    $stmt = $pdo->prepare("
        UPDATE songs SET 
            title = ?, artist = ?, tone = ?, bpm = ?, duration = ?, category = ?,
            link_letra = ?, link_cifra = ?, link_audio = ?, link_video = ?, 
            tags = ?, notes = ?, custom_fields = ?
        WHERE id = ?
    ");

    $stmt->execute([
        $_POST['title'],
        $_POST['artist'],
        $_POST['tone'] ?: null,
        $_POST['bpm'] ?: null,
        $_POST['duration'] ?: null,
        $_POST['category'],
        $_POST['link_letra'] ?: null,
        $_POST['link_cifra'] ?: null,
        $_POST['link_audio'] ?: null,
        $_POST['link_video'] ?: null,
        $_POST['tags'] ?: null,
        $_POST['notes'] ?: null,
        $customFieldsJson,
        $id
    ]);

    header("Location: musica_detalhe.php?id=$id");
    exit;
}

renderAppHeader('Editar Música');
?>

<style>
    .form-section {
        background: var(--bg-secondary);
        border: 1px solid var(--border-subtle);
        border-radius: 12px;
        padding: 20px;
        margin-bottom: 16px;
    }

    .form-section-title {
        font-size: 0.9rem;
        font-weight: 700;
        color: var(--text-secondary);
        text-transform: uppercase;
        letter-spacing: 0.5px;
        margin-bottom: 16px;
    }

    .autocomplete-suggestions {
        position: absolute;
        background: var(--bg-secondary);
        border: 1px solid var(--border-subtle);
        border-radius: 8px;
        max-height: 200px;
        overflow-y: auto;
        z-index: 1000;
        width: 100%;
        display: none;
        box-shadow: var(--shadow-lg);
    }

    .autocomplete-suggestion {
        padding: 12px;
        cursor: pointer;
        border-bottom: 1px solid var(--border-subtle);
    }

    .autocomplete-suggestion:hover {
        background: var(--bg-tertiary);
    }

    .autocomplete-suggestion:last-child {
        border-bottom: none;
    }
</style>

<!-- Header -->
<div style="display: flex; align-items: center; gap: 12px; margin-bottom: 24px;">
    <a href="musica_detalhe.php?id=<?= $id ?>" class="btn-icon ripple">
        <i data-lucide="x"></i>
    </a>
    <h1 style="font-size: 1.3rem; font-weight: 800; color: var(--text-primary); margin: 0;">Editar Música</h1>
</div>

<form method="POST">
    <!-- Informações Básicas -->
    <div class="form-section">
        <div class="form-section-title">Informações Básicas</div>

        <div class="form-group" style="margin-bottom: 16px;">
            <label class="form-label">Título da Música *</label>
            <input type="text" name="title" class="form-input" value="<?= htmlspecialchars($song['title']) ?>" required>
        </div>

        <div class="form-group" style="margin-bottom: 16px; position: relative;">
            <label class="form-label">Artista *</label>
            <input type="text" name="artist" id="artistInput" class="form-input" value="<?= htmlspecialchars($song['artist']) ?>" required autocomplete="off">
            <div id="artistSuggestions" class="autocomplete-suggestions"></div>
        </div>

        <div class="form-group">
            <label class="form-label">Categoria</label>
            <select name="category" class="form-input">
                <option value="Louvor" <?= $song['category'] === 'Louvor' ? 'selected' : '' ?>>Louvor</option>
                <option value="Adoração" <?= $song['category'] === 'Adoração' ? 'selected' : '' ?>>Adoração</option>
                <option value="Celebração" <?= $song['category'] === 'Celebração' ? 'selected' : '' ?>>Celebração</option>
                <option value="Hino" <?= $song['category'] === 'Hino' ? 'selected' : '' ?>>Hino</option>
            </select>
        </div>
    </div>

    <!-- Detalhes Musicais -->
    <div class="form-section">
        <div class="form-section-title">Detalhes Musicais</div>

        <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 12px; margin-bottom: 16px;">
            <div class="form-group">
                <label class="form-label">Tom</label>
                <input type="text" name="tone" class="form-input" value="<?= htmlspecialchars($song['tone']) ?>" placeholder="Ex: G, Am, C#">
            </div>

            <div class="form-group">
                <label class="form-label">BPM</label>
                <input type="number" name="bpm" class="form-input" value="<?= $song['bpm'] ?>" placeholder="120">
            </div>

            <div class="form-group">
                <label class="form-label">Duração</label>
                <input type="text" name="duration" class="form-input" value="<?= htmlspecialchars($song['duration']) ?>" placeholder="3:45">
            </div>
        </div>
    </div>

    <!-- Links/Referências -->
    <div class="form-section">
        <div class="form-section-title">Referências</div>

        <div class="form-group" style="margin-bottom: 16px;">
            <label class="form-label">
                <i data-lucide="file-text" style="width: 14px; display: inline;"></i> Link da Letra
            </label>
            <input type="url" name="link_letra" class="form-input" value="<?= htmlspecialchars($song['link_letra']) ?>" placeholder="https://...">
        </div>

        <div class="form-group" style="margin-bottom: 16px;">
            <label class="form-label">
                <i data-lucide="music-2" style="width: 14px; display: inline;"></i> Link da Cifra
            </label>
            <input type="url" name="link_cifra" class="form-input" value="<?= htmlspecialchars($song['link_cifra']) ?>" placeholder="https://...">
        </div>

        <div class="form-group" style="margin-bottom: 16px;">
            <label class="form-label">
                <i data-lucide="headphones" style="width: 14px; display: inline;"></i> Link do Áudio
            </label>
            <input type="url" name="link_audio" class="form-input" value="<?= htmlspecialchars($song['link_audio']) ?>" placeholder="https://...">
        </div>

        <div class="form-group">
            <label class="form-label">
                <i data-lucide="video" style="width: 14px; display: inline;"></i> Link do Vídeo (YouTube)
            </label>
            <input type="url" name="link_video" class="form-input" value="<?= htmlspecialchars($song['link_video']) ?>" placeholder="https://...">
        </div>
    </div>

    <!-- Campos Customizados -->
    <div class="form-section">
        <div class="form-section-title">Campos Adicionais</div>
        <p style="font-size: 0.85rem; color: var(--text-secondary); margin-bottom: 16px;">
            Adicione links personalizados como Google Drive, Partitura, Playback, etc.
        </p>

        <div id="customFieldsContainer">
            <?php foreach ($customFields as $index => $field): ?>
                <div class="custom-field-item" id="custom-field-existing-<?= $index ?>" style="background: var(--bg-tertiary); padding: 16px; border-radius: 8px; margin-bottom: 12px;">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px;">
                        <span style="font-weight: 600; color: var(--text-primary);">Campo #<?= $index + 1 ?></span>
                        <button type="button" onclick="removeExistingField(<?= $index ?>)" class="btn-icon ripple" style="background: var(--status-error); color: white;">
                            <i data-lucide="trash-2" style="width: 16px;"></i>
                        </button>
                    </div>
                    <div class="form-group" style="margin-bottom: 12px;">
                        <label class="form-label">Nome do Campo</label>
                        <input type="text" name="custom_field_name[]" class="form-input" value="<?= htmlspecialchars($field['name']) ?>" placeholder="Ex: Google Drive, Partitura">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Link</label>
                        <input type="url" name="custom_field_link[]" class="form-input" value="<?= htmlspecialchars($field['link']) ?>" placeholder="https://...">
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <button type="button" onclick="addCustomField()" class="btn-outline ripple" style="width: 100%; justify-content: center; margin-top: 12px;">
            <i data-lucide="plus"></i> Adicionar Campo
        </button>
    </div>

    <!-- Tags e Observações -->
    <div class="form-section">
        <div class="form-section-title">Tags e Observações</div>

        <div class="form-group" style="margin-bottom: 16px;">
            <label class="form-label">Tags (separadas por vírgula)</label>
            <input type="text" name="tags" class="form-input" value="<?= htmlspecialchars($song['tags']) ?>" placeholder="Repertório 2025, Favorita">
        </div>

        <div class="form-group">
            <label class="form-label">Observações</label>
            <textarea name="notes" class="form-input" rows="4" style="resize: vertical;"><?= htmlspecialchars($song['notes']) ?></textarea>
        </div>
    </div>

    <!-- Botões -->
    <div style="display: flex; gap: 12px; margin-top: 24px; padding-bottom: 80px;">
        <a href="musica_detalhe.php?id=<?= $id ?>" class="btn-outline ripple" style="flex: 1; justify-content: center; text-decoration: none;">
            Cancelar
        </a>
        <button type="submit" class="btn-action-save ripple" style="flex: 2; justify-content: center;">
            <i data-lucide="save"></i> Salvar Alterações
        </button>
    </div>
</form>

<script>
    // Autocomplete de artistas
    const artists = <?= json_encode($artists) ?>;
    const artistInput = document.getElementById('artistInput');
    const artistSuggestions = document.getElementById('artistSuggestions');

    artistInput.addEventListener('input', function() {
        const value = this.value.toLowerCase();
        artistSuggestions.innerHTML = '';

        if (value.length < 2) {
            artistSuggestions.style.display = 'none';
            return;
        }

        const filtered = artists.filter(artist => artist.toLowerCase().includes(value));

        if (filtered.length > 0) {
            filtered.forEach(artist => {
                const div = document.createElement('div');
                div.className = 'autocomplete-suggestion';
                div.textContent = artist;
                div.onclick = function() {
                    artistInput.value = artist;
                    artistSuggestions.style.display = 'none';
                };
                artistSuggestions.appendChild(div);
            });
            artistSuggestions.style.display = 'block';
        } else {
            artistSuggestions.style.display = 'none';
        }
    });

    document.addEventListener('click', function(e) {
        if (!artistInput.contains(e.target)) {
            artistSuggestions.style.display = 'none';
        }
    });

    // Campos customizados
    let customFieldCount = <?= count($customFields) ?>;

    function addCustomField() {
        customFieldCount++;
        const container = document.getElementById('customFieldsContainer');
        const fieldHtml = `
        <div class="custom-field-item" id="custom-field-${customFieldCount}" style="background: var(--bg-tertiary); padding: 16px; border-radius: 8px; margin-bottom: 12px;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px;">
                <span style="font-weight: 600; color: var(--text-primary);">Campo #${customFieldCount + 1}</span>
                <button type="button" onclick="removeCustomField(${customFieldCount})" class="btn-icon ripple" style="background: var(--status-error); color: white;">
                    <i data-lucide="trash-2" style="width: 16px;"></i>
                </button>
            </div>
            <div class="form-group" style="margin-bottom: 12px;">
                <label class="form-label">Nome do Campo</label>
                <input type="text" name="custom_field_name[]" class="form-input" placeholder="Ex: Google Drive, Partitura, Playback">
            </div>
            <div class="form-group">
                <label class="form-label">Link</label>
                <input type="url" name="custom_field_link[]" class="form-input" placeholder="https://...">
            </div>
        </div>
    `;
        container.insertAdjacentHTML('beforeend', fieldHtml);
        lucide.createIcons();
    }

    function removeCustomField(id) {
        document.getElementById(`custom-field-${id}`).remove();
    }

    function removeExistingField(id) {
        document.getElementById(`custom-field-existing-${id}`).remove();
    }
</script>

<?php renderAppFooter(); ?>