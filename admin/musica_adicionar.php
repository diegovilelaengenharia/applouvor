<?php
// admin/musica_adicionar.php
require_once '../src/helpers/auth.php';
require_once '../src/config/db.php';
require_once '../src/layout/layout.php';
require_once '../src/helpers/notification_system.php';

checkLogin();

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

$errorMessage = null;

// Preservar valores em caso de erro
$formValues = [
    'title' => '',
    'artist' => '',
    'version' => '',
    'tone' => '',
    'bpm' => '',
    'duration' => '',
    'link_letra' => '',
    'link_cifra' => '',
    'link_audio' => '',
    'link_video' => '',
    'notes' => ''
];
$selectedTagIds = [];
$customFields = [];

// Processar formulário
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validação CSRF
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        http_response_code(403);
        die('Ação não autorizada. Por favor, recarregue a página e tente novamente.');
    }

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
        // Buscar nome da primeira tag selecionada
        $firstTagId = $_POST['selected_tags'][0];
        foreach ($allTags as $t) {
            if ($t->id == $firstTagId) {
                $categoryLegacy = $t->name;
                break;
            }
        }
    } else {
        $categoryLegacy = $_POST['category'] ?? 'Louvor'; // Fallback se o usuário não selecionar nada
    }

    try {
        // Tentativa de inserção com o campo version
        $stmt = $pdo->prepare("
            INSERT INTO songs (
                title, artist, tone, bpm, duration, category, 
                link_letra, link_cifra, link_audio, link_video, 
                tags, notes, custom_fields, version, created_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
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
            $_POST['version'] ?? null
        ]);

        $newId = $pdo->lastInsertId();
        $dbLegacy = 0;
    } catch (PDOException $e) {
        if ($e->getCode() == '42S22') {
            try {
                // Fallback sem version
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
                $dbLegacy = 1;
            } catch (PDOException $ex) {
                $errorMessage = "Erro ao tentar cadastrar no modo legado: " . $ex->getMessage();
            }
        } else {
            $errorMessage = "Erro ao cadastrar música no banco de dados: " . $e->getMessage();
        }
    }

    // Se salvou com sucesso
    if (isset($newId)) {
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

        header("Location: musica_detalhe.php?id=$newId" . ($dbLegacy ? '&db_legacy=1' : ''));
        exit;
    } else {
        // UX Resiliente: Preencher de volta para não perder o que foi digitado
        $formValues = [
            'title' => $_POST['title'],
            'artist' => $_POST['artist'],
            'version' => $_POST['version'] ?? '',
            'tone' => $_POST['tone'],
            'bpm' => $_POST['bpm'],
            'duration' => $_POST['duration'],
            'link_letra' => $_POST['link_letra'],
            'link_cifra' => $_POST['link_cifra'],
            'link_audio' => $_POST['link_audio'],
            'link_video' => $_POST['link_video'],
            'notes' => $_POST['notes']
        ];
        if (!empty($_POST['selected_tags'])) {
            $selectedTagIds = $_POST['selected_tags'];
        }
        $customFields = $newCustomFields;
    }
}

renderAppHeader('Adicionar Música');
renderPageHeader('Nova Música', 'Cadastrar no repertório');
?>

<!-- Importações e estilos premium inline para override e compatibilidade -->
<link rel="stylesheet" href="../assets/css/pages/musica-form.css">
<style>
    /* Estilos Customizados para o Design System Sacred Minimalist */
    :root {
        --primary: #0284C7; /* Worship Blue */
        --primary-hover: #0369A1;
        --primary-subtle: rgba(2, 132, 199, 0.08);
        --radius-3xl: 24px;
        --bg-surface-glass: rgba(255, 255, 255, 0.8);
    }
    
    .dark {
        --bg-surface-glass: rgba(15, 23, 42, 0.8);
    }

    .form-card-bento {
        background: var(--bg-surface-glass);
        backdrop-filter: blur(12px);
        -webkit-backdrop-filter: blur(12px);
        border: 1px solid var(--border-color);
        border-radius: var(--radius-3xl);
        padding: 24px;
        box-shadow: 0 4px 20px -2px rgba(0, 0, 0, 0.02), 0 2px 6px -1px rgba(0, 0, 0, 0.02);
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        position: relative;
        overflow: hidden;
    }
    
    .form-card-bento:hover {
        transform: translateY(-2px);
        box-shadow: 0 12px 30px -4px rgba(0, 0, 0, 0.04), 0 4px 12px -2px rgba(0, 0, 0, 0.03);
        border-color: var(--border-strong);
    }

    /* Removido indicador de barra clássica para minimalismo real */
    .form-card-bento::before {
        display: none;
    }

    .form-input-premium {
        width: 100%;
        padding: 12px 16px;
        border-radius: 14px;
        border: 1px solid var(--border-color);
        background: var(--bg-body);
        color: var(--text-main);
        font-size: var(--font-body);
        font-weight: 500;
        transition: all 0.2s ease;
    }

    .form-input-premium:focus {
        outline: none;
        border-color: var(--primary);
        background: var(--bg-surface);
        box-shadow: 0 0 0 4px rgba(2, 132, 199, 0.15);
    }

    .animate-scale-up {
        animation: scaleUp 0.3s cubic-bezier(0.34, 1.56, 0.64, 1) forwards;
    }

    @keyframes scaleUp {
        0% { transform: scale(0.92); opacity: 0; }
        100% { transform: scale(1); opacity: 1; }
    }

    @keyframes fadeInDown {
        0% { transform: translate(-50%, -20px); opacity: 0; }
        100% { transform: translate(-50%, 0); opacity: 1; }
    }

    .animate-fade-in-down {
        animation: fadeInDown 0.35s cubic-bezier(0.16, 1, 0.3, 1) forwards;
    }
</style>

<!-- Componente de Notificação Avançada (Toast de Erro de Banco de Dados) -->
<?php if ($errorMessage): ?>
<div class="fixed top-6 left-1/2 -translate-x-1/2 z-50 w-full max-w-md px-4 animate-fade-in-down">
    <div class="bg-red-500/10 dark:bg-red-950/20 backdrop-blur-xl border border-red-500/20 rounded-2xl p-4 shadow-xl flex items-start gap-3">
        <div class="p-2 bg-red-500/20 text-red-600 dark:text-red-400 rounded-xl flex-shrink-0">
            <i data-lucide="alert-triangle" class="w-5 h-5"></i>
        </div>
        <div class="flex-1 min-w-0">
            <h4 class="font-bold text-red-800 dark:text-red-400 text-sm">Problema com Banco de Dados</h4>
            <p class="text-xs text-red-600/90 dark:text-red-300/90 mt-1 leading-relaxed break-words"><?= htmlspecialchars($errorMessage) ?></p>
        </div>
        <button type="button" onclick="this.parentElement.parentElement.remove()" class="text-red-400 hover:text-red-600 transition-colors p-1 flex-shrink-0">
            <i data-lucide="x" class="w-4 h-4"></i>
        </button>
    </div>
</div>
<?php endif; ?>

<div class="compact-container">
    <form method="POST">
        <?= App\AuthMiddleware::csrfField() ?>

        <!-- Grid Principal Bento -->
        <div class="grid grid-cols-1 gap-6">

            <!-- Card 1: Informações Principais -->
            <div class="form-card-bento" style="--card-color: var(--primary);">
                <div class="card-title flex items-center justify-between mb-6 pb-4 border-b border-slate-100 dark:border-slate-800">
                    <div class="flex items-center gap-3 text-slate-800 dark:text-slate-200">
                        <div class="p-2 bg-sky-50 dark:bg-sky-950/50 text-sky-600 dark:text-sky-400 rounded-xl">
                            <i data-lucide="music" class="w-5 h-5"></i>
                        </div>
                        <span class="font-bold text-base tracking-normal normal-case">Informações Principais</span>
                    </div>
                </div>

                <div class="space-y-4">
                    <!-- Título e Versão -->
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div class="form-group md:col-span-2">
                            <label class="form-label text-slate-600 dark:text-slate-400 font-semibold mb-2 block">Título *</label>
                            <input type="text" name="title" class="form-input-premium" value="<?= htmlspecialchars($formValues['title']) ?>" required placeholder="Ex: Grande é o Senhor" style="font-weight: 700;">
                        </div>
                        <div class="form-group">
                            <label class="form-label text-slate-600 dark:text-slate-400 font-semibold mb-2 block">Versão / Arranjo</label>
                            <input type="text" name="version" class="form-input-premium" value="<?= htmlspecialchars($formValues['version']) ?>" placeholder="Ex: Ao Vivo, Acústico...">
                        </div>
                    </div>

                    <!-- Artista e Tom -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <!-- Autocomplete Artista -->
                        <div class="form-group relative" id="artist-autocomplete-container">
                            <label class="form-label text-slate-600 dark:text-slate-400 font-semibold mb-2 block">Artista *</label>
                            <div class="relative">
                                <input type="text" id="artist-input" name="artist" class="form-input-premium" value="<?= htmlspecialchars($formValues['artist']) ?>" required autocomplete="off" placeholder="Ex: Morada, Nívea Soares...">
                                <div id="artist-suggestions" class="absolute left-0 right-0 mt-2 bg-white dark:bg-slate-900 border border-slate-150 dark:border-slate-800 rounded-2xl shadow-xl z-50 hidden max-h-48 overflow-y-auto backdrop-blur-lg bg-opacity-95">
                                    <!-- Renderizado via JS -->
                                </div>
                            </div>
                        </div>

                        <!-- Seletor de Tom -->
                        <div class="form-group">
                            <label class="form-label text-slate-600 dark:text-slate-400 font-semibold mb-2 block">Tom Original</label>
                            <div class="relative">
                                <select name="tone" class="form-input-premium appearance-none pr-10">
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
                                        $selected = ($formValues['tone'] === $val) ? 'selected' : '';
                                        echo "<option value='$val' $selected>$label</option>";
                                    }
                                    ?>
                                </select>
                                <div class="absolute inset-y-0 right-3 flex items-center pointer-events-none text-slate-400">
                                    <i data-lucide="chevron-down" class="w-4 h-4"></i>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- BPM e Duração -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="form-group">
                            <label class="form-label text-slate-600 dark:text-slate-400 font-semibold mb-2 block">BPM (Batidas Por Minuto)</label>
                            <input type="number" name="bpm" class="form-input-premium" value="<?= $formValues['bpm'] ?>" placeholder="Ex: 72">
                        </div>

                        <div class="form-group">
                            <label class="form-label text-slate-600 dark:text-slate-400 font-semibold mb-2 block">Duração (Min:Seg)</label>
                            <input type="text" name="duration" class="form-input-premium" value="<?= htmlspecialchars($formValues['duration']) ?>" placeholder="Ex: 4:35">
                        </div>
                    </div>
                </div>
            </div>

            <!-- Card 2: Referências e Mídia -->
            <div class="form-card-bento">
                <div class="card-title flex items-center justify-between mb-6 pb-4 border-b border-slate-100 dark:border-slate-800">
                    <div class="flex items-center gap-3 text-slate-800 dark:text-slate-200">
                        <div class="p-2 bg-emerald-50 dark:bg-emerald-950/50 text-emerald-600 dark:text-emerald-400 rounded-xl">
                            <i data-lucide="link" class="w-5 h-5"></i>
                        </div>
                        <span class="font-bold text-base tracking-normal normal-case">Referências e Mídia</span>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="form-group">
                        <label class="form-label text-slate-600 dark:text-slate-400 font-semibold mb-2 block">Link da Letra</label>
                        <div class="input-icon-wrapper">
                            <i data-lucide="file-text"></i>
                            <input type="url" name="link_letra" class="form-input-premium" value="<?= htmlspecialchars($formValues['link_letra']) ?>" placeholder="https://...">
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label text-slate-600 dark:text-slate-400 font-semibold mb-2 block">Link da Cifra</label>
                        <div class="input-icon-wrapper">
                            <i data-lucide="music-2"></i>
                            <input type="url" name="link_cifra" class="form-input-premium" value="<?= htmlspecialchars($formValues['link_cifra']) ?>" placeholder="https://...">
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label text-slate-600 dark:text-slate-400 font-semibold mb-2 block">Link do Áudio (Spotify / Deezer)</label>
                        <div class="input-icon-wrapper">
                            <i data-lucide="headphones"></i>
                            <input type="url" name="link_audio" class="form-input-premium" value="<?= htmlspecialchars($formValues['link_audio']) ?>" placeholder="https://...">
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label text-slate-600 dark:text-slate-400 font-semibold mb-2 block">Link do Vídeo (YouTube)</label>
                        <div class="input-icon-wrapper">
                            <i data-lucide="video"></i>
                            <input type="url" name="link_video" class="form-input-premium" value="<?= htmlspecialchars($formValues['link_video']) ?>" placeholder="https://...">
                        </div>
                    </div>
                </div>

                <!-- Campos Extras (Referências Customizadas) -->
                <div class="mt-6 pt-6 border-t border-dashed border-slate-200 dark:border-slate-800">
                    <label class="form-label text-slate-800 dark:text-slate-200 font-bold text-sm mb-4 block">Outras Referências Customizadas</label>

                    <div id="customFieldsList" class="space-y-3 mb-4">
                        <!-- Renderizado via JS -->
                    </div>

                    <button type="button" onclick="addCustomFieldUI()" class="flex items-center gap-2 py-2.5 px-4 rounded-xl border border-dashed border-slate-250 dark:border-slate-800 hover:border-sky-500 dark:hover:border-sky-500 hover:bg-sky-50/20 text-sky-600 dark:text-sky-400 font-semibold text-sm transition-all duration-300">
                        <i data-lucide="plus-circle" class="w-4 h-4"></i>
                        Adicionar Referência Personalizada
                    </button>
                </div>
            </div>

            <!-- Card 3: Classificações (Tags) -->
            <div class="form-card-bento">
                <div class="card-title flex items-center justify-between mb-6 pb-4 border-b border-slate-100 dark:border-slate-800">
                    <div class="flex items-center gap-3 text-slate-800 dark:text-slate-200">
                        <div class="p-2 bg-amber-50 dark:bg-amber-950/50 text-amber-600 dark:text-amber-400 rounded-xl">
                            <i data-lucide="folder" class="w-5 h-5"></i>
                        </div>
                        <span class="font-bold text-base tracking-normal normal-case">Classificações e Categorias</span>
                    </div>
                    <a href="classificacoes.php" target="_blank" class="flex items-center gap-1.5 py-1.5 px-3 rounded-lg hover:bg-slate-50 dark:hover:bg-slate-800 text-slate-500 dark:text-slate-400 hover:text-slate-800 dark:hover:text-slate-200 font-bold text-xs transition-colors">
                        <i data-lucide="settings" class="w-3.5 h-3.5"></i>
                        Gerenciar Tags
                    </a>
                </div>

                <div class="flex flex-wrap gap-2.5" id="tagsSelectionContainer">
                    <?php foreach ($allTags as $tag):
                        $isChecked = in_array($tag->id, $selectedTagIds);
                        $tagColor = $tag->color ?: '#0EA5E9';
                    ?>
                        <label class="tag-pill-compact relative flex items-center gap-2 py-2.5 px-4 rounded-full border border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-900 cursor-pointer font-bold text-xs select-none transition-all duration-300 active:scale-95" 
                               style="<?= $isChecked ? "background-color: {$tagColor}15; border-color: {$tagColor}; color: {$tagColor};" : '' ?>">
                            <input type="checkbox" name="selected_tags[]" value="<?= $tag->id ?>" <?= $isChecked ? 'checked' : '' ?> onchange="updateTagStyle(this, '<?= $tagColor ?>')" class="hidden">
                            <span class="tag-dot w-2 h-2 rounded-full transition-transform duration-300" style="background-color: <?= $tagColor ?>; <?= $isChecked ? 'transform: scale(1.2);' : '' ?>"></span>
                            <span><?= htmlspecialchars($tag->name) ?></span>
                        </label>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Card 4: Observações -->
            <div class="form-card-bento">
                <div class="card-title flex items-center justify-between mb-4 pb-2">
                    <div class="flex items-center gap-3 text-slate-800 dark:text-slate-200">
                        <div class="p-2 bg-indigo-50 dark:bg-indigo-950/50 text-indigo-600 dark:text-indigo-400 rounded-xl">
                            <i data-lucide="message-square" class="w-5 h-5"></i>
                        </div>
                        <span class="font-bold text-base tracking-normal normal-case">Observações Internas</span>
                    </div>
                </div>

                <div class="form-group">
                    <textarea name="notes" class="form-input-premium w-full min-h-[100px] py-3 px-4 rounded-2xl resize-y" placeholder="Adicione observações importantes para os ministros ou instrumentistas (ex: andamento, transição)..."><?= htmlspecialchars($formValues['notes']) ?></textarea>
                </div>
            </div>

            <!-- Botões de Ação Principal -->
            <div class="flex gap-4 items-center pt-2 pb-16">
                <a href="repertorio.php" class="flex-1 text-center py-4 px-6 rounded-2xl border border-slate-200 dark:border-slate-800 hover:border-slate-300 bg-white dark:bg-slate-900 text-slate-700 dark:text-slate-300 font-bold text-base transition-all duration-300 shadow-sm hover:shadow active:scale-98">
                    Cancelar
                </a>
                <button type="submit" class="flex-[2] py-4 px-6 rounded-2xl bg-sky-600 hover:bg-sky-500 text-white font-bold text-base transition-all duration-300 shadow-lg shadow-sky-600/20 active:scale-98 flex items-center justify-center gap-2">
                    <i data-lucide="check" class="w-5 h-5"></i>
                    Salvar Música
                </button>
            </div>

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

<script>
    // Inicializar dados de campos customizados
    let customFieldsData = <?= json_encode($customFields) ?>;

    // Atualizar UI de campos customizados
    function renderCustomFields() {
        const list = document.getElementById('customFieldsList');
        list.innerHTML = '';

        customFieldsData.forEach((field, index) => {
            const item = document.createElement('div');
            // Animação de fade-in elástico
            item.className = 'custom-field-row flex items-center gap-3 p-3 bg-slate-50 dark:bg-slate-900/50 rounded-2xl border border-slate-100 dark:border-slate-800/80 hover:border-slate-200 dark:hover:border-slate-700 transition-all duration-300 animate-scale-up';
            item.innerHTML = `
                <div class="flex-1">
                    <input type="text" value="${field.name}" oninput="updateCustomFieldData(${index}, 'name', this.value)" class="form-input-premium py-2 px-3 text-sm font-medium bg-white dark:bg-slate-900" placeholder="Descrição (Ex: Partitura)">
                </div>
                <div class="flex-1">
                    <input type="url" value="${field.link}" oninput="updateCustomFieldData(${index}, 'link', this.value)" class="form-input-premium py-2 px-3 text-sm font-medium bg-white dark:bg-slate-900" placeholder="Link (https://...)">
                </div>
                <button type="button" onclick="removeCustomFieldData(${index})" class="p-2 text-slate-400 hover:text-red-500 hover:bg-red-50 dark:hover:bg-red-950/30 rounded-xl transition-all duration-200 flex-shrink-0" title="Remover">
                    <i data-lucide="trash-2" class="w-4 h-4"></i>
                </button>
            `;
            list.appendChild(item);
        });

        // Atualizar inputs hidden no form principal
        const hiddenContainer = document.getElementById('hiddenCustomFields');
        hiddenContainer.innerHTML = '';
        customFieldsData.forEach(field => {
            hiddenContainer.innerHTML += `
                <input type="hidden" name="custom_field_name[]" value="${escapeHtml(field.name)}">
                <input type="hidden" name="custom_field_link[]" value="${escapeHtml(field.link)}">
            `;
        });

        lucide.createIcons();
    }

    function escapeHtml(text) {
        if (!text) return '';
        return text
            .replace(/&/g, "&amp;")
            .replace(/</g, "&lt;")
            .replace(/>/g, "&gt;")
            .replace(/"/g, "&quot;")
            .replace(/'/g, "&#039;");
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
        // Atualizar hidden inputs silenciosamente sem re-renderizar todo o HTML para não perder o foco do cursor!
        const hiddenContainer = document.getElementById('hiddenCustomFields');
        hiddenContainer.innerHTML = '';
        customFieldsData.forEach(field => {
            hiddenContainer.innerHTML += `
                <input type="hidden" name="custom_field_name[]" value="${escapeHtml(field.name)}">
                <input type="hidden" name="custom_field_link[]" value="${escapeHtml(field.link)}">
            `;
        });
    }

    // Tag Selection Styles
    function updateTagStyle(checkbox, color) {
        const label = checkbox.parentElement;
        const dot = label.querySelector('.tag-dot');
        if (checkbox.checked) {
            label.style.backgroundColor = color + '15';
            label.style.borderColor = color;
            label.style.color = color;
            dot.style.transform = 'scale(1.2)';
        } else {
            label.style.backgroundColor = '';
            label.style.borderColor = '';
            label.style.color = '';
            dot.style.transform = 'none';
        }
    }

    // Autocomplete de Artistas Dinâmico
    const allArtists = <?= json_encode($artists) ?>;
    const artistInput = document.getElementById('artist-input');
    const suggestionsBox = document.getElementById('artist-suggestions');

    artistInput.addEventListener('input', function() {
        const query = this.value.toLowerCase().trim();
        if (!query) {
            suggestionsBox.classList.add('hidden');
            return;
        }

        const filtered = allArtists.filter(artist => artist.toLowerCase().includes(query));
        if (filtered.length === 0) {
            suggestionsBox.classList.add('hidden');
            return;
        }

        suggestionsBox.innerHTML = '';
        filtered.forEach(artist => {
            const item = document.createElement('div');
            item.className = 'px-4 py-3 hover:bg-sky-50 dark:hover:bg-slate-800 cursor-pointer font-semibold text-sm text-slate-700 dark:text-slate-350 transition-colors first:rounded-t-2xl last:rounded-b-2xl';
            item.textContent = artist;
            item.addEventListener('click', () => {
                artistInput.value = artist;
                suggestionsBox.classList.add('hidden');
            });
            suggestionsBox.appendChild(item);
        });

        suggestionsBox.classList.remove('hidden');
    });

    // Fechar sugestões ao clicar fora
    document.addEventListener('click', function(e) {
        const container = document.getElementById('artist-autocomplete-container');
        if (container && !container.contains(e.target)) {
            suggestionsBox.classList.add('hidden');
        }
    });

    lucide.createIcons();
    renderCustomFields(); // Inicializar campos extras
</script>

<?php renderAppFooter(); ?>