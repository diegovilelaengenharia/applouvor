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
        justify-content: space-between;
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

    /* Input Icon */
    .input-icon-wrapper {
        position: relative;
    }

    .input-icon-wrapper i {
        position: absolute;
        left: 12px;
        top: 50%;
        transform: translateY(-50%);
        color: var(--text-muted);
        width: 16px;
        pointer-events: none;
    }

    .input-icon-wrapper input {
        padding-left: 40px;
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

    .custom-field-row {
        display: grid;
        grid-template-columns: 1fr 1fr 32px;
        gap: 12px;
        align-items: center;
        margin-bottom: 12px;
        background: var(--bg-body);
        padding: 12px;
        border-radius: 10px;
        border: 1px solid var(--border-color);
    }

    @media (max-width: 768px) {

        .form-grid-2,
        .form-grid-3 {
            grid-template-columns: 1fr;
        }

        .custom-field-row {
            grid-template-columns: 1fr;
            gap: 8px;
            position: relative;
        }

        .custom-field-row button {
            position: absolute;
            top: 8px;
            right: 8px;
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
                <div style="display: flex; align-items: center; gap: 8px;">
                    <i data-lucide="music" style="width: 14px;"></i>
                    Informações Principais
                </div>
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
                            <i data-lucide="radio" style="width: 16px;"></i>
                            Streaming
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Card 2: Referências e Mídia (Novo Card) -->
        <div class="form-card">
            <div class="card-title">
                <div style="display: flex; align-items: center; gap: 8px;">
                    <i data-lucide="link" style="width: 14px;"></i>
                    Referências e Mídia
                </div>
            </div>

            <div class="form-grid form-grid-2">
                <div class="form-group">
                    <label class="form-label">Link da Letra</label>
                    <div class="input-icon-wrapper">
                        <i data-lucide="file-text"></i>
                        <input type="url" name="link_letra" class="form-input" value="<?= htmlspecialchars($song['link_letra']) ?>" placeholder="https://...">
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label">Link da Cifra</label>
                    <div class="input-icon-wrapper">
                        <i data-lucide="music-2"></i>
                        <input type="url" name="link_cifra" class="form-input" value="<?= htmlspecialchars($song['link_cifra']) ?>" placeholder="https://...">
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label">Link do Áudio</label>
                    <div class="input-icon-wrapper">
                        <i data-lucide="headphones"></i>
                        <input type="url" name="link_audio" class="form-input" value="<?= htmlspecialchars($song['link_audio']) ?>" placeholder="https://...">
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label">Link do Vídeo</label>
                    <div class="input-icon-wrapper">
                        <i data-lucide="video"></i>
                        <input type="url" name="link_video" class="form-input" value="<?= htmlspecialchars($song['link_video']) ?>" placeholder="https://...">
                    </div>
                </div>
            </div>

            <div style="margin-top: 16px; border-top: 1px dashed var(--border-color); padding-top: 16px;">
                <label class="form-label" style="margin-bottom: 12px;">Outras Referências</label>

                <div id="customFieldsList">
                    <!-- Renderizado via JS -->
                </div>

                <button type="button" onclick="addCustomFieldUI()" class="btn-link" style="padding: 0;">
                    <i data-lucide="plus-circle" style="width: 16px;"></i>
                    Adicionar Referência Personalizada
                </button>
            </div>
        </div>

        <!-- Card 3: Classificações -->
        <div class="form-card">
            <div class="card-title">
                <div style="display: flex; align-items: center; gap: 8px;">
                    <i data-lucide="folder" style="width: 14px;"></i>
                    Classificações
                </div>
                <button type="button" onclick="openTagManager()" class="btn-link">
                    <i data-lucide="settings" style="width: 14px;"></i>
                    Gerenciar
                </button>
            </div>

            <div class="tag-pills" id="tagsSelectionContainer">
                <?php foreach ($allTags as $tag):
                    $isChecked = in_array($tag['id'], $selectedTagIds);
                ?>
                    <label class="tag-pill-compact" style="<?= $isChecked ? 'background: var(--primary-subtle); border-color: var(--primary); color: var(--primary);' : '' ?>">
                        <input type="checkbox" name="selected_tags[]" value="<?= $tag['id'] ?>" <?= $isChecked ? 'checked' : '' ?> onchange="updateTagStyle(this)">
                        <span class="tag-dot" style="background: <?= $tag['color'] ?: '#047857' ?>;"></span>
                        <?= htmlspecialchars($tag['name']) ?>
                    </label>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Card 4: Observações -->
        <div class="form-card">
            <div class="card-title">
                <div style="display: flex; align-items: center; gap: 8px;">
                    <i data-lucide="message-square" style="width: 14px;"></i>
                    Observações
                </div>
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

        <!-- Hidden Inputs para Campos Extras -->
        <div id="hiddenCustomFields">
            <?php foreach ($customFields as $index => $field): ?>
                <input type="hidden" name="custom_field_name[]" value="<?= htmlspecialchars($field['name']) ?>">
                <input type="hidden" name="custom_field_link[]" value="<?= htmlspecialchars($field['link']) ?>">
            <?php endforeach; ?>
        </div>
    </form>
</div>

<!-- Modal: Streaming -->
<div id="linksModal" class="modal-overlay">
    <div class="modal-content">
        <div class="modal-header">
            <h3 class="modal-title">Plataformas de Streaming</h3>
            <button type="button" onclick="closeLinksModal()" class="btn-close">
                <i data-lucide="x" style="width: 18px;"></i>
            </button>
        </div>

        <div style="display: grid; gap: 16px;">
            <div class="form-group" style="margin-bottom: 12px;">
                <label class="form-label">Spotify</label>
                <div class="input-icon-wrapper">
                    <input type="url" name="link_spotify" id="link_spotify" class="form-input" value="<?= htmlspecialchars($song['link_spotify'] ?? '') ?>" placeholder="https://open.spotify.com/...">
                </div>
            </div>

            <div class="form-group" style="margin-bottom: 12px;">
                <label class="form-label">YouTube</label>
                <div class="input-icon-wrapper">
                    <input type="url" name="link_youtube" id="link_youtube" class="form-input" value="<?= htmlspecialchars($song['link_youtube'] ?? '') ?>" placeholder="https://youtube.com/...">
                </div>
            </div>

            <div class="form-group" style="margin-bottom: 12px;">
                <label class="form-label">Apple Music</label>
                <div class="input-icon-wrapper">
                    <input type="url" name="link_apple_music" id="link_apple_music" class="form-input" value="<?= htmlspecialchars($song['link_apple_music'] ?? '') ?>" placeholder="https://music.apple.com/...">
                </div>
            </div>

            <div class="form-group">
                <label class="form-label">Deezer</label>
                <div class="input-icon-wrapper">
                    <input type="url" name="link_deezer" id="link_deezer" class="form-input" value="<?= htmlspecialchars($song['link_deezer'] ?? '') ?>" placeholder="https://deezer.com/...">
                </div>
            </div>
        </div>

        <button type="button" onclick="closeLinksModal()" class="btn-action btn-primary" style="width: 100%; margin-top: 20px;">
            Concluído
        </button>
    </div>
</div>

<!-- Modal: Gestão de Tags Completa -->
<div id="tagManagerModal" class="modal-overlay">
    <div class="modal-content" style="max-height: 85vh; display: flex; flex-direction: column;">
        <div class="modal-header">
            <h3 class="modal-title">Gerenciar Classificações</h3>
            <button type="button" onclick="closeTagManager()" class="btn-close">
                <i data-lucide="x" style="width: 18px;"></i>
            </button>
        </div>

        <!-- Lista de Tags -->
        <div id="tagsList" style="flex: 1; overflow-y: auto; margin-bottom: 24px; min-height: 150px;">
            <?php foreach ($allTags as $tag): ?>
                <div class="tag-card-item" data-tag-id="<?= $tag['id'] ?>" style="background: var(--bg-body); border-radius: 12px; padding: 12px; display: flex; align-items: center; gap: 12px; margin-bottom: 8px; border: 1px solid var(--border-color);">
                    <div style="width: 40px; height: 40px; border-radius: 10px; display: flex; align-items: center; justify-content: center; color: white; flex-shrink: 0; background: <?= $tag['color'] ?: '#047857' ?>;">
                        <i data-lucide="folder" style="width: 20px;"></i>
                    </div>
                    <div style="flex: 1; min-width: 0;">
                        <div style="font-weight: 700; font-size: 0.95rem; color: var(--text-main);"><?= htmlspecialchars($tag['name']) ?></div>
                        <div style="font-size: 0.8rem; color: var(--text-muted); overflow: hidden; text-overflow: ellipsis; white-space: nowrap;"><?= htmlspecialchars($tag['description']) ?></div>
                    </div>
                    <div style="display: flex; gap: 4px;">
                        <button type="button" onclick='editTagInline(<?= json_encode($tag) ?>)' class="btn-close" style="width: 32px; height: 32px;">
                            <i data-lucide="edit-2" style="width: 16px;"></i>
                        </button>
                        <button type="button" onclick="deleteTagInline(<?= $tag['id'] ?>)" class="btn-close" style="width: 32px; height: 32px; color: #ef4444;">
                            <i data-lucide="trash-2" style="width: 16px;"></i>
                        </button>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- Formulário de Nova/Editor -->
        <div style="background: var(--bg-body); border-radius: 16px; padding: 16px;">
            <h4 style="font-size: 0.9rem; font-weight: 700; color: var(--text-main); margin-bottom: 12px;" id="tagFormTitle">Nova Classificação</h4>

            <input type="hidden" id="editingTagId" value="">

            <div class="form-group" style="margin-bottom: 12px;">
                <input type="text" id="tagNameInput" class="form-input" placeholder="Nome da Pasta (Ex: Adoração)">
            </div>

            <div class="form-group" style="margin-bottom: 12px;">
                <textarea id="tagDescInput" class="form-input" rows="2" placeholder="Descrição (Opcional)"></textarea>
            </div>

            <div class="form-group" style="margin-bottom: 16px;">
                <label class="form-label">Cor</label>
                <div style="display: flex; gap: 8px; flex-wrap: wrap;">
                    <?php
                    $colors = ['#047857', '#F59E0B', '#EF4444', '#3B82F6', '#8B5CF6', '#EC4899', '#6366F1'];
                    foreach ($colors as $c): ?>
                        <label style="cursor: pointer;">
                            <input type="radio" name="tagColor" value="<?= $c ?>" style="display: none;" onchange="selectTagColor(this)">
                            <div class="color-circle" style="width: 28px; height: 28px; background: <?= $c ?>; border-radius: 50%; border: 2px solid transparent; transition: transform 0.2s;"></div>
                        </label>
                    <?php endforeach; ?>
                </div>
            </div>

            <button type="button" onclick="saveTagInline()" class="btn-action btn-primary" style="width: 100%;">
                <span id="saveButtonText">Criar Classificação</span>
            </button>
        </div>
    </div>
</div>

<script>
    // Inicializar dados de campos customizados
    let customFieldsData = <?= json_encode($customFields) ?>;

    // Atualizar UI de campos customizados
    function renderCustomFields() {
        const list = document.getElementById('customFieldsList');
        list.innerHTML = '';

        customFieldsData.forEach((field, index) => {
            const item = document.createElement('div');
            item.className = 'custom-field-row';
            item.innerHTML = `
                <div>
                    <input type="text" value="${field.name}" oninput="updateCustomFieldData(${index}, 'name', this.value)" class="form-input" placeholder="Descrição (Ex: Partitura)">
                </div>
                <div>
                    <input type="url" value="${field.link}" oninput="updateCustomFieldData(${index}, 'link', this.value)" class="form-input" placeholder="Link (https://...)">
                </div>
                <button type="button" onclick="removeCustomFieldData(${index})" class="btn-close" style="width: 32px; height: 32px; color: #ef4444;" title="Remover">
                    <i data-lucide="trash-2" style="width: 16px;"></i>
                </button>
            `;
            list.appendChild(item);
        });

        // Atualizar inputs hidden no form principal
        const hiddenContainer = document.getElementById('hiddenCustomFields');
        hiddenContainer.innerHTML = '';
        customFieldsData.forEach(field => {
            hiddenContainer.innerHTML += `
                <input type="hidden" name="custom_field_name[]" value="${field.name}">
                <input type="hidden" name="custom_field_link[]" value="${field.link}">
            `;
        });

        lucide.createIcons();
    }

    function addCustomFieldUI() {
        customFieldsData.push({
            name: '',
            link: ''
        });
        renderCustomFields();
    }

    function removeCustomFieldData(index) {
        customFieldsData.splice(index, 1);
        renderCustomFields();
    }

    function updateCustomFieldData(index, key, value) {
        customFieldsData[index][key] = value;
        // Atualizar hidden inputs
        renderCustomFields();
    }

    // Modal Functions
    function openLinksModal() {
        document.getElementById('linksModal').classList.add('active');
        lucide.createIcons();
    }

    function closeLinksModal() {
        document.getElementById('linksModal').classList.remove('active');
    }

    function openTagManager() {
        document.getElementById('tagManagerModal').classList.add('active');
        lucide.createIcons();
    }

    function closeTagManager() {
        document.getElementById('tagManagerModal').classList.remove('active');
    }

    // Tag Selection Styles
    function updateTagStyle(checkbox) {
        const label = checkbox.parentElement;
        if (checkbox.checked) {
            label.style.background = 'var(--primary-subtle)';
            label.style.borderColor = 'var(--primary)';
            label.style.color = 'var(--primary)';
        } else {
            label.style.background = 'var(--bg-body)';
            label.style.borderColor = 'transparent';
            label.style.color = 'var(--text-muted)';
        }
    }

    // SIMULAÇÃO VISUAL DE SELEÇÃO DE COR
    function selectTagColor(radio) {
        document.querySelectorAll('.color-circle').forEach(c => {
            c.style.transform = 'scale(1)';
            c.style.borderColor = 'transparent';
        });
        if (radio.checked) {
            radio.nextElementSibling.style.transform = 'scale(1.2)';
            radio.nextElementSibling.style.borderColor = 'var(--text-main)';
        }
    }

    function editTagInline(tag) {
        document.getElementById('editingTagId').value = tag.id;
        document.getElementById('tagNameInput').value = tag.name;
        document.getElementById('tagDescInput').value = tag.description;
        document.getElementById('tagFormTitle').textContent = 'Editar Classificação';
        document.getElementById('saveButtonText').textContent = 'Salvar Alterações';

        // Selecionar cor
        const radios = document.getElementsByName('tagColor');
        radios.forEach(r => {
            if (r.value === tag.color) {
                r.checked = true;
                selectTagColor(r);
            }
        });
    }

    function saveTagInline() {
        const id = document.getElementById('editingTagId').value;
        const name = document.getElementById('tagNameInput').value;
        const desc = document.getElementById('tagDescInput').value;
        const color = document.querySelector('input[name="tagColor"]:checked')?.value || '#047857';

        if (!name) return alert('Nome obrigatório');

        const formData = new FormData();
        formData.append('action', id ? 'update' : 'create');
        if (id) formData.append('id', id);
        formData.append('name', name);
        formData.append('description', desc);
        formData.append('color', color);

        fetch('classificacoes.php', {
            method: 'POST',
            body: formData
        }).then(() => {
            location.reload();
        });
    }

    function deleteTagInline(id) {
        if (!confirm('Excluir esta tag?')) return;

        const formData = new FormData();
        formData.append('action', 'delete');
        formData.append('id', id);

        fetch('classificacoes.php', {
            method: 'POST',
            body: formData
        }).then(() => {
            location.reload();
        });
    }

    // Fechar modais ao clicar fora
    document.querySelectorAll('.modal-overlay').forEach(modal => {
        modal.addEventListener('click', function(e) {
            if (e.target === this) {
                this.classList.remove('active');
            }
        });
    });

    lucide.createIcons();
    renderCustomFields(); // Inicializar campos extras
</script>

<?php renderAppFooter(); ?>