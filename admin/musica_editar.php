<?php
// admin/musica_editar.php
require_once '../includes/db.php';
require_once '../includes/layout.php';

// Buscar todas as tags
$allTags = $pdo->query("SELECT * FROM tags ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC);

// Se for editar, buscar tags já selecionadas
$selectedTagIds = [];
if (isset($id)) {
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
            if ($t['id'] == $firstTagId) { $categoryLegacy = $t['name']; break; }
        }
    } else {
        $categoryLegacy = $song['category']; // Manter anterior se não mudar
    }

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
        $categoryLegacy,
        $_POST['link_letra'] ?: null,
        $_POST['link_cifra'] ?: null,
        $_POST['link_audio'] ?: null,
        $_POST['link_video'] ?: null,
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
    /* Modern Form Styles & Tag Selector */
    body { background-color: #f8fafc !important; }
    .form-section { background: white; border: 1px solid rgba(226, 232, 240, 0.8); border-radius: 20px; padding: 32px; margin-bottom: 32px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.02); transition: all 0.3s ease; }
    .form-section:hover { box-shadow: 0 10px 15px -3px rgba(0,0,0,0.05); }
    .form-section-title { font-size: 0.85rem; font-weight: 800; color: #64748b; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 24px; border-bottom: 2px solid #f1f5f9; padding-bottom: 12px; }
    .form-label { font-size: 0.9rem; font-weight: 700; color: #334155; margin-bottom: 8px; display: block; }
    .form-input { width: 100%; padding: 14px 16px; border-radius: 12px; border: 1px solid #e2e8f0; background: #f8fafc; color: #1e293b; font-size: 0.95rem; font-weight: 500; transition: all 0.2s; }
    .form-input:focus { background: white; border-color: #047857; box-shadow: 0 0 0 4px rgba(4, 120, 87, 0.1); outline: none; }
    
    /* Tag Selector */
    .tag-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(140px, 1fr)); gap: 10px; }
    .tag-option { position: relative; cursor: pointer; }
    .tag-option input { display: none; }
    .tag-pill { display: flex; align-items: center; justify-content: center; gap: 8px; padding: 12px; border-radius: 12px; background: #f1f5f9; border: 2px solid transparent; font-weight: 600; color: #64748b; transition: all 0.2s; text-align: center; font-size: 0.9rem; }
    .tag-option input:checked + .tag-pill { background: #ecfdf5; border-color: var(--tag-color, #047857); color: var(--tag-color, #047857); box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05); }
    .tag-pill::before { content: ''; width: 10px; height: 10px; border-radius: 50%; background: var(--tag-color, #ccc); opacity: 0.5; }
    .tag-option input:checked + .tag-pill::before { opacity: 1; box-shadow: 0 0 0 2px white; }
    
    /* Input Icon */
    .input-icon-wrapper { position: relative; }
    .input-icon-wrapper i { position: absolute; left: 16px; top: 50%; transform: translateY(-50%); color: #94a3b8; width: 18px; pointer-events: none; }
    .input-icon-wrapper input { padding-left: 48px; }
    
    /* Autocomplete */
    .autocomplete-suggestions { position: absolute; background: white; border: 1px solid #e2e8f0; border-radius: 12px; max-height: 250px; overflow-y: auto; z-index: 1000; width: 100%; display: none; box-shadow: 0 10px 15px -3px rgba(0,0,0,0.1); margin-top: 4px; }
    .autocomplete-suggestion { padding: 12px 16px; cursor: pointer; border-bottom: 1px solid #f1f5f9; color: #475569; }
    .autocomplete-suggestion:hover { background: #f0fdf4; color: #047857; }
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
            <label class="form-label">Classificações (Selecione uma ou mais)</label>
            <div class="tag-grid">
                <?php foreach ($allTags as $tag): 
                    $isChecked = in_array($tag['id'], $selectedTagIds); 
                ?>
                    <label class="tag-option">
                        <input type="checkbox" name="selected_tags[]" value="<?= $tag['id'] ?>" <?= $isChecked ? 'checked' : '' ?>>
                        <span class="tag-pill" style="--tag-color: <?= $tag['color'] ?: '#047857' ?>">
                            <?= htmlspecialchars($tag['name']) ?>
                        </span>
                    </label>
                <?php endforeach; ?>
            </div>
            <div style="margin-top: 10px; text-align: right;">
                <a href="classificacoes.php" target="_blank" style="font-size: 0.85rem; color: #047857; font-weight: 600; text-decoration: none;">+ Gerenciar Classificações</a>
            </div>
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
        <div class="form-section-title">Referências e Mídia</div>
        
        <div class="form-group" style="margin-bottom: 20px;">
            <label class="form-label">Link da Letra</label>
            <div class="input-icon-wrapper">
                <i data-lucide="file-text"></i>
                <input type="url" name="link_letra" class="form-input" value="<?= htmlspecialchars($song['link_letra']) ?>" placeholder="https://...">
            </div>
        </div>
        
        <div class="form-group" style="margin-bottom: 20px;">
            <label class="form-label">Link da Cifra</label>
            <div class="input-icon-wrapper">
                <i data-lucide="music-2"></i>
                <input type="url" name="link_cifra" class="form-input" value="<?= htmlspecialchars($song['link_cifra']) ?>" placeholder="https://...">
            </div>
        </div>
        
        <div class="form-group" style="margin-bottom: 20px;">
            <label class="form-label">Link do Áudio</label>
            <div class="input-icon-wrapper">
                <i data-lucide="headphones"></i>
                <input type="url" name="link_audio" class="form-input" value="<?= htmlspecialchars($song['link_audio']) ?>" placeholder="https://...">
            </div>
        </div>
        
        <div class="form-group" style="margin-bottom: 20px;">
            <label class="form-label">Link do Vídeo</label>
            <div class="input-icon-wrapper">
                <i data-lucide="video"></i>
                <input type="url" name="link_video" class="form-input" value="<?= htmlspecialchars($song['link_video']) ?>" placeholder="https://...">
            </div>
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
        <a href="musica_detalhe.php?id=<?= $id ?>" class="ripple" style="background: white; color: #64748b; border: 1px solid #cbd5e1; padding: 16px; border-radius: 12px; font-weight: 600; flex: 1; display: flex; align-items: center; justify-content: center; text-decoration: none;">
            Cancelar
        </a>
        <button type="submit" class="ripple" style="background: linear-gradient(135deg, #2563EB 0%, #1D4ED8 100%); color: white; border: none; padding: 16px; border-radius: 12px; font-weight: 700; font-size: 1rem; box-shadow: 0 4px 12px rgba(37, 99, 235, 0.25); flex: 2; display: flex; align-items: center; justify-content: center; gap: 8px; transition: all 0.3s;">
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