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
            if ($t['id'] == $firstTagId) {
                $categoryLegacy = $t['name'];
                break;
            }
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
    /* Modern Form Styles & Tag Selector */
    body {
        background-color: var(--bg-body) !important;
    }

    .form-section {
        background: var(--bg-surface);
        border: 1px solid var(--border-color);
        border-radius: 20px;
        padding: 32px;
        margin-bottom: 32px;
        box-shadow: var(--shadow-sm);
        transition: all 0.3s ease;
    }

    .form-section:hover {
        box-shadow: var(--shadow-xl);
    }

    .form-section-title {
        font-size: 0.85rem;
        font-weight: 800;
        color: var(--text-muted);
        text-transform: uppercase;
        letter-spacing: 1px;
        margin-bottom: 24px;
        border-bottom: 2px solid var(--bg-body);
        padding-bottom: 12px;
    }

    .form-label {
        font-size: 0.9rem;
        font-weight: 700;
        color: var(--text-main);
        margin-bottom: 8px;
        display: block;
    }

    .form-input {
        width: 100%;
        padding: 14px 16px;
        border-radius: 12px;
        border: 1px solid var(--border-color);
        background: var(--bg-body);
        color: var(--text-main);
        font-size: 0.95rem;
        font-weight: 500;
        transition: all 0.2s;
    }

    .form-input:focus {
        background: var(--bg-surface);
        border-color: var(--primary);
        box-shadow: 0 0 0 4px var(--primary-light);
        outline: none;
    }

    /* Tag Selector */
    .tag-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(140px, 1fr));
        gap: 10px;
    }

    .tag-option {
        position: relative;
        cursor: pointer;
    }

    .tag-option input {
        display: none;
    }

    .tag-pill {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        padding: 12px;
        border-radius: 12px;
        background: var(--bg-body);
        border: 2px solid transparent;
        font-weight: 600;
        color: var(--text-muted);
        transition: all 0.2s;
        text-align: center;
        font-size: 0.9rem;
    }

    .tag-option input:checked+.tag-pill {
        background: var(--primary-subtle);
        border-color: var(--primary);
        color: var(--primary);
        box-shadow: var(--shadow-sm);
    }

    .tag-pill::before {
        content: '';
        width: 10px;
        height: 10px;
        border-radius: 50%;
        background: var(--tag-color, #ccc);
        opacity: 0.5;
    }

    .tag-option input:checked+.tag-pill::before {
        opacity: 1;
        box-shadow: 0 0 0 2px var(--bg-surface);
    }

    /* Input Icon */
    .input-icon-wrapper {
        position: relative;
    }

    .input-icon-wrapper i {
        position: absolute;
        left: 16px;
        top: 50%;
        transform: translateY(-50%);
        color: var(--text-muted);
        width: 18px;
        pointer-events: none;
    }

    .input-icon-wrapper input {
        padding-left: 48px;
    }

    /* Autocomplete */
    .autocomplete-suggestions {
        position: absolute;
        background: var(--bg-surface);
        border: 1px solid var(--border-color);
        border-radius: 12px;
        max-height: 250px;
        overflow-y: auto;
        z-index: 1000;
        width: 100%;
        display: none;
        box-shadow: var(--shadow-xl);
        margin-top: 4px;
    }

    .autocomplete-suggestion {
        padding: 12px 16px;
        cursor: pointer;
        border-bottom: 1px solid var(--border-color);
        color: var(--text-main);
    }

    .autocomplete-suggestion:hover {
        background: var(--primary-subtle);
        color: var(--primary);
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
                <button type="button" onclick="openTagManager()" style="font-size: 0.85rem; color: #047857; font-weight: 600; background: none; border: none; cursor: pointer; text-decoration: none;">+ Gerenciar Classificações</button>
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
        <a href="musica_detalhe.php?id=<?= $id ?>" class="ripple" style="background: var(--bg-surface); color: var(--text-muted); border: 1px solid var(--border-color); padding: 16px; border-radius: 12px; font-weight: 600; flex: 1; display: flex; align-items: center; justify-content: center; text-decoration: none;">
            Cancelar
        </a>
        <button type="submit" class="ripple" style="background: var(--primary); color: white; border: none; padding: 16px; border-radius: 12px; font-weight: 700; font-size: 1rem; box-shadow: var(--shadow-md); flex: 2; display: flex; align-items: center; justify-content: center; gap: 8px; transition: all 0.3s; cursor: pointer;">
            <i data-lucide="save"></i> Salvar Alterações
        </button>
    </div>
</form>

<!-- Modal de Gestão de Tags -->
<div id="tagManagerModal" class="bottom-sheet-overlay" style="display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0, 0, 0, 0.5); backdrop-filter: blur(4px); z-index: 1000; align-items: flex-end;">
    <div class="bottom-sheet-content" style="background: var(--bg-surface); border-radius: 24px 24px 0 0; padding: 24px; width: 100%; max-width: 600px; margin: 0 auto; max-height: 80vh; overflow-y: auto;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px;">
            <h2 style="font-size: 1.25rem; font-weight: 700; color: var(--text-main); margin: 0;">Gerenciar Classificações</h2>
            <button type="button" onclick="closeTagManager()" style="width: 36px; height: 36px; border-radius: 50%; border: none; background: var(--bg-body); color: var(--text-muted); cursor: pointer; display: flex; align-items: center; justify-content: center;">
                <i data-lucide="x" style="width: 20px;"></i>
            </button>
        </div>

        <!-- Lista de Tags Existentes -->
        <div id="tagsList" style="margin-bottom: 24px;">
            <?php foreach ($allTags as $tag): ?>
                <div class="tag-card" data-tag-id="<?= $tag['id'] ?>" style="background: var(--bg-body); border: 1px solid var(--border-color); border-radius: 12px; padding: 16px; display: flex; align-items: center; gap: 12px; margin-bottom: 12px;">
                    <div style="width: 48px; height: 48px; border-radius: 12px; display: flex; align-items: center; justify-content: center; color: white; flex-shrink: 0; background: <?= $tag['color'] ?: '#047857' ?>;">
                        <i data-lucide="folder" style="width: 24px;"></i>
                    </div>
                    <div style="flex: 1; min-width: 0;">
                        <div style="font-weight: 700; font-size: 1rem; color: var(--text-main); margin-bottom: 4px;"><?= htmlspecialchars($tag['name']) ?></div>
                        <div style="font-size: 0.85rem; color: var(--text-muted); overflow: hidden; text-overflow: ellipsis; white-space: nowrap;"><?= htmlspecialchars($tag['description']) ?></div>
                    </div>
                    <div style="display: flex; flex-direction: column; gap: 8px;">
                        <button type="button" onclick='editTagInline(<?= json_encode($tag) ?>)' style="width: 36px; height: 36px; border-radius: 8px; border: none; background: transparent; display: flex; align-items: center; justify-content: center; cursor: pointer; color: #64748b;">
                            <i data-lucide="edit-2" style="width: 18px;"></i>
                        </button>
                        <button type="button" onclick="deleteTagInline(<?= $tag['id'] ?>)" style="width: 36px; height: 36px; border-radius: 8px; border: none; background: transparent; display: flex; align-items: center; justify-content: center; cursor: pointer; color: #ef4444;">
                            <i data-lucide="trash-2" style="width: 18px;"></i>
                        </button>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- Formulário de Nova Tag -->
        <div style="background: var(--bg-body); border-radius: 16px; padding: 20px;">
            <h3 style="font-size: 1rem; font-weight: 700; color: var(--text-main); margin-bottom: 16px;" id="tagFormTitle">Nova Classificação</h3>

            <input type="hidden" id="editingTagId" value="">

            <div style="margin-bottom: 16px;">
                <label style="display: block; font-weight: 600; font-size: 0.9rem; color: var(--text-main); margin-bottom: 8px;">Nome da Pasta</label>
                <input type="text" id="tagNameInput" placeholder="Ex: Adoração" style="width: 100%; padding: 12px 16px; border: 1px solid var(--border-color); border-radius: 10px; background: var(--bg-surface); color: var(--text-main); font-size: 0.95rem;">
            </div>

            <div style="margin-bottom: 16px;">
                <label style="display: block; font-weight: 600; font-size: 0.9rem; color: var(--text-main); margin-bottom: 8px;">Descrição</label>
                <textarea id="tagDescInput" rows="3" placeholder="Para que serve..." style="width: 100%; padding: 12px 16px; border: 1px solid var(--border-color); border-radius: 10px; background: var(--bg-surface); color: var(--text-main); font-size: 0.95rem; resize: vertical;"></textarea>
            </div>

            <div style="margin-bottom: 24px;">
                <label style="display: block; font-weight: 600; font-size: 0.9rem; color: var(--text-main); margin-bottom: 8px;">Cor</label>
                <div style="display: flex; gap: 8px; flex-wrap: wrap;">
                    <?php
                    $colors = ['#047857', '#F59E0B', '#EF4444', '#3B82F6', '#8B5CF6', '#EC4899', '#6366F1'];
                    foreach ($colors as $c): ?>
                        <label style="cursor: pointer;">
                            <input type="radio" name="tagColor" value="<?= $c ?>" style="display: none;" onchange="selectTagColor(this)">
                            <div class="color-circle-inline" style="width: 32px; height: 32px; background: <?= $c ?>; border-radius: 50%; border: 2px solid transparent; transition: transform 0.2s;"></div>
                        </label>
                    <?php endforeach; ?>
                </div>
            </div>

            <button type="button" onclick="saveTagInline()" style="width: 100%; background: linear-gradient(135deg, var(--primary), var(--primary-hover)); color: white; border: none; padding: 14px 24px; border-radius: 12px; font-weight: 600; font-size: 1rem; cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 8px; box-shadow: 0 4px 12px rgba(4, 120, 87, 0.2);">
                <i data-lucide="save" style="width: 18px;"></i>
                <span id="saveButtonText">Salvar</span>
            </button>
        </div>
    </div>
</div>

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

    // ===== GESTÃO DE TAGS INLINE =====
    function openTagManager() {
        document.getElementById('tagManagerModal').style.display = 'flex';
        lucide.createIcons();
    }

    function closeTagManager() {
        document.getElementById('tagManagerModal').style.display = 'none';
        resetTagForm();
    }

    function resetTagForm() {
        document.getElementById('editingTagId').value = '';
        document.getElementById('tagNameInput').value = '';
        document.getElementById('tagDescInput').value = '';
        document.getElementById('tagFormTitle').textContent = 'Nova Classificação';
        document.getElementById('saveButtonText').textContent = 'Salvar';
        document.querySelectorAll('input[name="tagColor"]').forEach(input => {
            input.checked = false;
            input.nextElementSibling.style.transform = 'scale(1)';
            input.nextElementSibling.style.borderColor = 'transparent';
        });
    }

    function selectTagColor(input) {
        document.querySelectorAll('.color-circle-inline').forEach(c => {
            c.style.transform = 'scale(1)';
            c.style.borderColor = 'transparent';
        });
        if (input.checked) {
            input.nextElementSibling.style.transform = 'scale(1.2)';
            input.nextElementSibling.style.borderColor = 'white';
            input.nextElementSibling.style.boxShadow = '0 0 0 2px ' + input.value;
        }
    }

    function editTagInline(tag) {
        document.getElementById('editingTagId').value = tag.id;
        document.getElementById('tagNameInput').value = tag.name;
        document.getElementById('tagDescInput').value = tag.description || '';
        document.getElementById('tagFormTitle').textContent = 'Editar Classificação';
        document.getElementById('saveButtonText').textContent = 'Atualizar';

        // Selecionar cor
        document.querySelectorAll('input[name="tagColor"]').forEach(input => {
            if (input.value === tag.color) {
                input.checked = true;
                selectTagColor(input);
            }
        });
    }

    function saveTagInline() {
        const tagId = document.getElementById('editingTagId').value;
        const name = document.getElementById('tagNameInput').value.trim();
        const description = document.getElementById('tagDescInput').value.trim();
        const colorInput = document.querySelector('input[name="tagColor"]:checked');
        const color = colorInput ? colorInput.value : '#047857';

        if (!name) {
            alert('Por favor, insira um nome para a classificação');
            return;
        }

        const formData = new FormData();
        formData.append('action', tagId ? 'update' : 'create');
        if (tagId) formData.append('id', tagId);
        formData.append('name', name);
        formData.append('description', description);
        formData.append('color', color);

        fetch('classificacoes.php', {
                method: 'POST',
                body: formData
            })
            .then(() => {
                // Recarregar a página para atualizar as tags
                window.location.reload();
            })
            .catch(error => {
                console.error('Erro:', error);
                alert('Erro ao salvar classificação');
            });
    }

    function deleteTagInline(tagId) {
        if (!confirm('Excluir esta classificação?')) return;

        const formData = new FormData();
        formData.append('action', 'delete');
        formData.append('id', tagId);

        fetch('classificacoes.php', {
                method: 'POST',
                body: formData
            })
            .then(() => {
                window.location.reload();
            })
            .catch(error => {
                console.error('Erro:', error);
                alert('Erro ao excluir classificação');
            });
    }

    // Fechar modal ao clicar fora
    document.getElementById('tagManagerModal').addEventListener('click', function(e) {
        if (e.target === this) closeTagManager();
    });
</script>

<?php renderAppFooter(); ?>