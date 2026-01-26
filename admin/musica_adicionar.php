<?php
// admin/musica_adicionar.php
require_once '../includes/auth.php';
require_once '../includes/db.php';
require_once '../includes/layout.php';

checkLogin();

// Buscar todas as tags
$allTags = $pdo->query("SELECT * FROM tags ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC);

// Buscar artistas únicos para autocomplete
$artists = $pdo->query("SELECT DISTINCT artist FROM songs ORDER BY artist ASC")->fetchAll(PDO::FETCH_COLUMN);

// Processar formulário
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Processar campos customizados
    $customFields = [];
    if (!empty($_POST['custom_field_name']) && !empty($_POST['custom_field_link'])) {
        foreach ($_POST['custom_field_name'] as $index => $name) {
            if (!empty($name) && !empty($_POST['custom_field_link'][$index])) {
                $customFields[] = [
                    'name' => trim($name),
                    'link' => trim($_POST['custom_field_link'][$index])
                ];
            }
        }
    }
    $customFieldsJson = !empty($customFields) ? json_encode($customFields) : null;


    // Pegar nome da primeira tag para preencher category (legacy)
    $categoryLegacy = 'Outros';
    if (!empty($_POST['selected_tags'])) {
        // Buscar nome da primeira tag selecionada
        $firstTagId = $_POST['selected_tags'][0];
        foreach ($allTags as $t) {
            if ($t['id'] == $firstTagId) {
                $categoryLegacy = $t['name'];
                break;
            }
        }
    } else {
        $categoryLegacy = $_POST['category'] ?? 'Louvor'; // Fallback se o usuário não selecionar nada
    }

    $stmt = $pdo->prepare("
        INSERT INTO songs (
            title, artist, tone, bpm, duration, category, 
            link_letra, link_cifra, link_audio, link_video, 
            tags, notes, custom_fields, created_at
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
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
        $customFieldsJson
    ]);

    $newId = $pdo->lastInsertId();

    // Salvar Relacionamento song_tags
    if (!empty($_POST['selected_tags'])) {
        $stmtTag = $pdo->prepare("INSERT INTO song_tags (song_id, tag_id) VALUES (?, ?)");
        foreach ($_POST['selected_tags'] as $tagId) {
            $stmtTag->execute([$newId, $tagId]);
        }
    }

    header("Location: musica_detalhe.php?id=$newId");
    exit;
}

renderAppHeader('Adicionar Música');
?>

<style>
    body { background: var(--bg-body); }

    .compact-container {
        max-width: 900px;
        margin: 0 auto;
        padding: 16px 12px 60px 12px;
    }

    .header-bar {
        display: flex;
        align-items: center;
        gap: 12px;
        margin-bottom: 24px;
    }

    .btn-back {
        width: 40px;
        height: 40px;
        border-radius: 12px;
        border: 1px solid var(--border-color);
        background: var(--bg-surface);
        color: var(--text-muted);
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        transition: all 0.2s;
        box-shadow: var(--shadow-sm);
        text-decoration: none;
    }

    .btn-back:hover {
        background: var(--bg-body);
        color: var(--primary);
        border-color: var(--primary-light);
    }

    .page-title {
        font-size: var(--font-display);
        font-weight: 800;
        background: linear-gradient(135deg, var(--text-main) 0%, var(--text-muted) 100%);
        -webkit-background-clip: text;
        background-clip: text;
        -webkit-text-fill-color: transparent;
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
        position: relative;
        overflow: hidden;
    }

    .form-card::before {
        content: '';
        position: absolute;
        top: 0; left: 0; width: 4px; height: 100%;
        background: var(--card-color, var(--primary));
        opacity: 0.8;
    }

    .card-title {
        font-size: var(--font-body-sm);
        font-weight: 800;
        color: var(--card-color, var(--text-muted));
        text-transform: uppercase;
        letter-spacing: 0.5px;
        margin-bottom: 16px;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 8px;
    }

    .form-grid { display: grid; gap: 12px; }
    .form-grid-2 { grid-template-columns: 1fr 1fr; }
    .form-grid-3-custom { grid-template-columns: 2fr 1fr; }

    .form-group { margin-bottom: 0; }
    .form-label {
        font-size: var(--font-caption);
        font-weight: 600;
        color: var(--text-secondary);
        margin-bottom: 6px;
        display: block;
    }

    .form-input {
        width: 100%;
        padding: 10px 12px;
        border-radius: 10px;
        border: 1px solid var(--border-color);
        background: var(--bg-body);
        color: var(--text-main);
        font-size: var(--font-body);
        transition: all 0.2s;
        font-weight: 500;
    }

    .form-input:focus {
        outline: none;
        border-color: var(--card-color, var(--primary));
        background: var(--bg-surface);
        box-shadow: 0 0 0 3px var(--focus-shadow, rgba(4, 120, 87, 0.1));
    }

    /* Input Icon */
    .input-icon-wrapper { position: relative; }
    .input-icon-wrapper i {
        position: absolute; left: 12px; top: 50%; transform: translateY(-50%);
        color: var(--text-secondary); width: 18px; pointer-events: none; transition: color 0.2s;
    }
    .input-icon-wrapper input:focus + i, .input-icon-wrapper:focus-within i { color: var(--card-color, var(--primary)); }
    .input-icon-wrapper input { padding-left: 40px; }

    /* Tag Pills Compact */
    .tag-pills { display: flex; flex-wrap: wrap; gap: 8px; }
    .tag-pill-compact {
        display: inline-flex; align-items: center; gap: 8px;
        padding: 8px 14px; border-radius: 20px;
        background: var(--bg-body); border: 1px solid var(--border-color);
        font-size: var(--font-body-sm); font-weight: 600; color: var(--text-muted);
        cursor: pointer; transition: all 0.2s; user-select: none;
    }
    .tag-pill-compact:hover { border-color: var(--primary-light); color: var(--primary); background: var(--bg-surface); }
    .tag-pill-compact input { display: none; }
    .tag-pill-compact input:checked + .tag-dot { transform: scale(1.2); }
    .tag-pill-compact input:checked + .tag-dot + span { color: var(--primary); } 
    /* Hacky but works for keeping styles without complex label structure change */
    
    .tag-pill-compact.active {
        background: var(--primary-subtle);
        border-color: var(--primary);
        color: var(--primary);
        box-shadow: 0 2px 8px rgba(4, 120, 87, 0.15);
    }
    
    .tag-dot {
        width: 8px; height: 8px; border-radius: 50%;
        transition: transform 0.2s;
    }

    .btn-action {
        display: flex; align-items: center; justify-content: center; gap: 8px;
        padding: 12px 20px; border-radius: 12px; border: none; font-weight: 700; font-size: var(--font-body);
        cursor: pointer; transition: all 0.2s; width: 100%;
    }
    .btn-primary {
        background: linear-gradient(135deg, var(--primary), var(--primary-hover)); color: white;
        box-shadow: 0 4px 12px rgba(4, 120, 87, 0.25);
    }
    .btn-secondary { background: var(--bg-surface); color: var(--text-secondary); border: 1px solid var(--border-color); }
    .btn-secondary:hover { background: var(--bg-body); color: var(--text-main); }

    .custom-field-row {
        display: grid; grid-template-columns: 1fr 1fr 32px; gap: 10px; align-items: center;
        margin-bottom: 10px; background: var(--bg-body); padding: 12px; border-radius: 10px;
        border: 1px solid var(--border-color);
    }
    
    .btn-close {
        width: 32px; height: 32px; border-radius: 50%; border: none;
        background: var(--bg-body); color: var(--text-muted); cursor: pointer;
        display: flex; align-items: center; justify-content: center;
    }

    @media (max-width: 768px) {
        .form-grid-2, .form-grid-3-custom { grid-template-columns: 1fr; gap: 10px; }
        .custom-field-row { grid-template-columns: 1fr; gap: 8px; position: relative; }
        .custom-field-row button { position: absolute; top: 8px; right: 8px; }
    }
</style>

<div class="compact-container">
    <div class="header-bar">
        <a href="repertorio.php" class="btn-back">
            <i data-lucide="arrow-left" style="width: 20px;"></i>
        </a>
        <h1 class="page-title">Nova Música</h1>
    </div>

    <form method="POST">
        <!-- Card 1: Informações Principais -->
        <div class="form-card" style="--card-color: #3b82f6; --focus-shadow: rgba(59, 130, 246, 0.1);">
            <div class="card-title">
                <div style="display: flex; align-items: center; gap: 8px;">
                    <i data-lucide="music" style="width: 14px;"></i>
                    Informações Principais
                </div>
            </div>

            <div class="form-grid">
                <div class="form-grid form-grid-3-custom" style="display: grid; gap: 12px;">
                    <div class="form-group">
                        <label class="form-label">Título *</label>
                        <input type="text" name="title" class="form-input" required placeholder="Ex: Grande é o Senhor" style="font-weight: 700;">
                    </div>
                     <div class="form-group">
                        <label class="form-label">Artista *</label>
                        <input type="text" name="artist" class="form-input" list="artist-list" required placeholder="Ex: Adhemar...">
                        <datalist id="artist-list">
                            <?php foreach ($artists as $art): ?>
                                <option value="<?= htmlspecialchars($art) ?>">
                            <?php endforeach; ?>
                        </datalist>
                    </div>
                </div>

                <div class="form-grid form-grid-2">
                    <div class="form-group">
                        <label class="form-label">Tom</label>
                         <select name="tone" class="form-input" style="appearance: none;">
                            <option value="">Selecione...</option>
                            <?php
                            $tones = ['C', 'C#', 'D', 'D#', 'E', 'F', 'F#', 'G', 'G#', 'A', 'A#', 'B', 'Cm', 'C#m', 'Dm', 'D#m', 'Em', 'Fm', 'F#m', 'Gm', 'G#m', 'Am', 'A#m', 'Bm'];
                            foreach ($tones as $t) {
                                echo "<option value='$t'>$t</option>";
                            }
                            ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">BPM</label>
                        <input type="number" name="bpm" class="form-input" placeholder="Ex: 72">
                    </div>
                </div>
                
                 <div class="form-group">
                    <label class="form-label">Duração</label>
                    <input type="text" name="duration" class="form-input" placeholder="Ex: 05:30">
                </div>
            </div>
        </div>

        <!-- Card 2: Referências -->
        <div class="form-card" style="--card-color: #10b981; --focus-shadow: rgba(16, 185, 129, 0.1);">
            <div class="card-title">
                <div style="display: flex; align-items: center; gap: 8px;">
                    <i data-lucide="link" style="width: 14px;"></i>
                    Links e Mídia
                </div>
            </div>

            <div class="form-grid form-grid-2">
                <div class="form-group">
                    <label class="form-label">Link da Cifra</label>
                    <div class="input-icon-wrapper">
                        <i data-lucide="music-2"></i>
                        <input type="url" name="link_cifra" class="form-input" placeholder="https://...">
                    </div>
                </div>
                <div class="form-group">
                    <label class="form-label">Link da Letra</label>
                    <div class="input-icon-wrapper">
                        <i data-lucide="file-text"></i>
                        <input type="url" name="link_letra" class="form-input" placeholder="https://...">
                    </div>
                </div>
                <div class="form-group">
                    <label class="form-label">Link do Vídeo</label>
                    <div class="input-icon-wrapper">
                        <i data-lucide="video"></i>
                        <input type="url" name="link_video" class="form-input" placeholder="https://youtube...">
                    </div>
                </div>
                 <div class="form-group">
                    <label class="form-label">Link do Áudio</label>
                    <div class="input-icon-wrapper">
                        <i data-lucide="headphones"></i>
                        <input type="url" name="link_audio" class="form-input" placeholder="https://spotify...">
                    </div>
                </div>
            </div>

             <!-- Campos Extras -->
            <div style="border-top: 1px dashed var(--border-color); margin-top: 20px; padding-top: 20px;">
                <label class="form-label" style="margin-bottom: 12px; color: var(--text-main);">Outras Referências</label>
                <div id="customFieldsList"></div>
                <button type="button" onclick="addCustomFieldUI()" class="btn-secondary" style="background:none; border:none; padding: 0; color: var(--primary); font-size: var(--font-body-sm); display: flex; align-items: center; gap: 6px;">
                    <i data-lucide="plus-circle" style="width: 16px;"></i> Adicionar Referência Personalizada
                </button>
            </div>
        </div>

        <!-- Card 3: Tags -->
        <div class="form-card" style="--card-color: #f59e0b; --focus-shadow: rgba(245, 158, 11, 0.1);">
            <div class="card-title">
                <div style="display: flex; align-items: center; gap: 8px;">
                    <i data-lucide="folder" style="width: 14px;"></i>
                    Classificação
                </div>
                 <a href="classificacoes.php" target="_blank" style="color: var(--card-color); text-decoration: none; font-size: var(--font-caption); display: flex; align-items: center; gap: 4px;">
                    <i data-lucide="settings" style="width: 12px;"></i> Gerenciar
                </a>
            </div>

            <div class="tag-pills">
                <?php foreach ($allTags as $tag): ?>
                    <label class="tag-pill-compact" onclick="this.classList.toggle('active')">
                        <input type="checkbox" name="selected_tags[]" value="<?= $tag['id'] ?>">
                        <span class="tag-dot" style="background: <?= $tag['color'] ?: '#047857' ?>;"></span>
                        <span><?= htmlspecialchars($tag['name']) ?></span>
                    </label>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Card 4: Obs -->
        <div class="form-card" style="--card-color: #6366f1; --focus-shadow: rgba(99, 102, 241, 0.1);">
             <div class="card-title">
                <div style="display: flex; align-items: center; gap: 8px;">
                    <i data-lucide="message-square" style="width: 14px;"></i>
                    Observações
                </div>
            </div>
            <textarea name="notes" class="form-input" rows="3" style="resize: vertical; min-height: 80px;" placeholder="Detalhes sobre arranjo, versão, etc..."></textarea>
        </div>

        <!-- Bottom Actions -->
        <div style="display: flex; gap: 12px; padding-bottom: 20px;">
            <a href="repertorio.php" class="btn-action btn-secondary" style="flex: 1; text-decoration: none;">Cancelar</a>
            <button type="submit" class="btn-action btn-primary" style="flex: 2;">
                <i data-lucide="check" style="width: 18px;"></i> Salvar Música
            </button>
        </div>

        <!-- Hidden Inputs Custom Fields -->
        <div id="hiddenCustomFields"></div>

    </form>
</div>

<script>
    let customFieldsData = [];

    function renderCustomFields() {
        const list = document.getElementById('customFieldsList');
        list.innerHTML = '';
        customFieldsData.forEach((field, index) => {
            const item = document.createElement('div');
            item.className = 'custom-field-row';
            item.innerHTML = `
                <input type="text" value="${field.name}" oninput="updateCustomFieldData(${index}, 'name', this.value)" class="form-input" placeholder="Descrição">
                <input type="url" value="${field.link}" oninput="updateCustomFieldData(${index}, 'link', this.value)" class="form-input" placeholder="Link">
                 <button type="button" onclick="removeCustomFieldData(${index})" class="btn-close" style="color: #ef4444;">
                    <i data-lucide="trash-2" style="width: 14px;"></i>
                </button>
            `;
            list.appendChild(item);
        });

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
        customFieldsData.push({ name: '', link: '' });
        renderCustomFields();
    }

    function removeCustomFieldData(index) {
        customFieldsData.splice(index, 1);
        renderCustomFields();
    }

    function updateCustomFieldData(index, key, value) {
        customFieldsData[index][key] = value;
        renderCustomFields();
    }

    // Toggle active class for tag pills on load if any checked (none on add)
    // For Add page, we just toggle on click using inline onclick for simplicity
</script>

<?php renderAppFooter(); ?>