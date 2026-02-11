<?php
// admin/musica_adicionar.php
require_once '../includes/auth.php';
require_once '../includes/db.php';
require_once '../includes/layout.php';
require_once '../includes/notification_system.php';

// Usar AuthMiddleware em vez de checkLogin()
App\AuthMiddleware::requireLogin();

// Buscar todas as tags usando Query Builder
$allTags = App\DB::table('tags')
    ->orderBy('name', 'ASC')
    ->get();

// Buscar artistas únicos para autocomplete
$artists = App\DB::table('songs')
    ->select('DISTINCT artist')
    ->orderBy('artist', 'ASC')
    ->get();
$artists = array_column($artists, 'artist'); // Converter para array simples

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

    // Notificar todos os usuários sobre a nova música
    try {
        $notificationSystem = new NotificationSystem($pdo);
        $songTitle = $_POST['title'];
        $songArtist = $_POST['artist'];
        
        // Buscar todos os usuários ativos usando Query Builder
        $users = App\DB::table('users')
            ->select('id')
            ->where('status', '=', 'active')
            ->get();
        $users = array_column($users, 'id');
        
        foreach ($users as $uid) {
            if ($uid == $_SESSION['user_id']) continue; // Não notificar o próprio criador
            
            $notificationSystem->createNotification(
                $uid,
                'new_music',
                "Nova Música: $songTitle",
                "$songTitle - $songArtist foi adicionada ao repertório.",
                "musica_detalhe.php?id=$newId"
            );
        }
    } catch (Exception $e) {
        error_log("Erro ao enviar notificações de música: " . $e->getMessage());
    }

    header("Location: musica_detalhe.php?id=$newId");
    exit;
}

renderAppHeader('Adicionar Música');
renderPageHeader('Nova Música', 'Cadastrar no repertório');
?>

<link rel="stylesheet" href="../assets/css/pages/musica-form.css">

<div class="compact-container">


    <form method="POST">
        <!-- Card 1: Informações Principais -->
        <div class="form-card" style="--card-color: var(--slate-500); --focus-shadow: rgba(59, 130, 246, 0.1);">
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
                            $tones = [
                                'C' => 'C (Dó)',
                                'C#' => 'C# (Dó Sustenido)',
                                'D' => 'D (Ré)',
                                'D#' => 'D# (Ré Sustenido)',
                                'E' => 'E (Mi)',
                                'F' => 'F (Fá)',
                                'F#' => 'F# (Fá Sustenido)',
                                'G' => 'G (Sol)',
                                'G#' => 'G# (Sol Sustenido)',
                                'A' => 'A (Lá)',
                                'A#' => 'A# (Lá Sustenido)',
                                'B' => 'B (Si)',
                                'Cm' => 'Cm (Dó Menor)',
                                'C#m' => 'C#m (Dó Sustenido Menor)',
                                'Dm' => 'Dm (Ré Menor)',
                                'D#m' => 'D#m (Ré Sustenido Menor)',
                                'Em' => 'Em (Mi Menor)',
                                'Fm' => 'Fm (Fá Menor)',
                                'F#m' => 'F#m (Fá Sustenido Menor)',
                                'Gm' => 'Gm (Sol Menor)',
                                'G#m' => 'G#m (Sol Sustenido Menor)',
                                'Am' => 'Am (Lá Menor)',
                                'A#m' => 'A#m (Lá Sustenido Menor)',
                                'Bm' => 'Bm (Si Menor)'
                            ];
                            foreach ($tones as $val => $label) {
                                echo "<option value='$val'>$label</option>";
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
        <div class="form-card" style="--card-color: var(--sage-500); --focus-shadow: rgba(34, 197, 94, 0.1);">
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
                <button type="button" onclick="addCustomFieldUI()" class="btn-link" style="padding: 0;">
                    <i data-lucide="plus-circle" style="width: 16px;"></i> Adicionar Referência Personalizada
                </button>
            </div>
        </div>

        <!-- Card 3: Tags -->
        <div class="form-card" style="--card-color: var(--yellow-500); --focus-shadow: rgba(245, 158, 11, 0.1);">
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
                        <span class="tag-dot" style="background: <?= $tag['color'] ?: 'var(--sage-500)' ?>;"></span>
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
        <div style="display: flex; gap: 12px; padding-bottom: 80px;">
            <a href="repertorio.php" style="
                flex: 1; 
                text-decoration: none; 
                padding: 14px 20px; 
                border-radius: 12px; 
                background: var(--bg-surface); 
                border: 1px solid var(--border-color); 
                color: var(--text-main); 
                font-weight: 600; 
                font-size: var(--font-body);
                text-align: center;
                transition: all 0.2s;
                box-shadow: var(--shadow-sm);
                display: flex;
                align-items: center;
                justify-content: center;
                gap: 8px;
            ">Cancelar</a>
            <button type="submit" style="
                flex: 2; 
                padding: 14px 20px; 
                border-radius: 12px; 
                background: var(--primary); 
                border: none; 
                color: white; 
                font-weight: 700; 
                font-size: var(--font-body);
                cursor: pointer;
                transition: all 0.2s;
                box-shadow: 0 4px 12px rgba(34, 197, 94, 0.3);
                display: flex;
                align-items: center;
                justify-content: center;
                gap: 8px;
            ">
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
                 <button type="button" onclick="removeCustomFieldData(${index})" class="btn-close" style="color: var(--rose-500);">
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