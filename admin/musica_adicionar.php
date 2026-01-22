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
    .form-container {
        max-width: 800px;
        margin: 0 auto;
        padding-bottom: 80px;
    }

    .form-section {
        background: var(--bg-surface);
        border-radius: var(--radius-lg);
        padding: 20px;
        border: 1px solid var(--border-color);
        box-shadow: var(--shadow-sm);
        margin-bottom: 20px;
    }

    .form-title {
        font-size: 1rem;
        font-weight: 700;
        color: var(--text-main);
        margin-bottom: 16px;
        display: flex;
        align-items: center;
        gap: 8px;
        padding-bottom: 12px;
        border-bottom: 1px solid var(--border-color);
    }

    .form-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 16px;
    }

    .form-group {
        margin-bottom: 16px;
    }

    .form-group label {
        display: block;
        font-size: 0.8rem;
        font-weight: 600;
        color: var(--text-muted);
        margin-bottom: 6px;
    }

    .input-field {
        width: 100%;
        padding: 10px;
        border-radius: 8px;
        border: 1px solid var(--border-color);
        background: var(--bg-body);
        color: var(--text-main);
        font-size: 0.9rem;
        outline: none;
        transition: border 0.2s;
    }

    .input-field:focus {
        border-color: var(--primary);
        background: var(--bg-surface);
    }

    /* Tags Selection */
    .tags-grid {
        display: flex;
        flex-wrap: wrap;
        gap: 8px;
    }

    .tag-checkbox {
        display: none;
    }

    .tag-label {
        padding: 6px 12px;
        border-radius: 20px;
        background: var(--bg-body);
        border: 1px solid var(--border-color);
        color: var(--text-muted);
        font-size: 0.8rem;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.2s;
        display: flex;
        align-items: center;
        gap: 6px;
    }

    .tag-checkbox:checked+.tag-label {
        background: var(--primary-subtle);
        border-color: var(--primary);
        color: var(--primary);
    }

    /* Bottom Actions */
    .bottom-bar {
        position: fixed;
        bottom: 0;
        left: 0;
        right: 0;
        background: var(--bg-surface);
        padding: 12px 24px;
        border-top: 1px solid var(--border-color);
        display: flex;
        justify-content: flex-end;
        gap: 12px;
        z-index: 40;
    }

    @media (min-width: 768px) {
        .bottom-bar {
            position: static;
            background: none;
            border: none;
            padding: 0;
            justify-content: flex-start;
            margin-top: 24px;
        }
    }
</style>

<div class="container form-container">
    <div style="margin-bottom: 20px; display: flex; align-items: center; justify-content: space-between;">
        <div>
            <h1 style="font-size: 1.5rem; font-weight: 800; color: var(--text-main); margin: 0;">Nova Música</h1>
            <p style="color: var(--text-muted); margin-top: 4px; font-size: 0.9rem;">Adicionar ao repertório</p>
        </div>
        <a href="repertorio.php" class="ripple" style="
            width: 36px; height: 36px; border-radius: 50%; background: var(--bg-surface); 
            display: flex; align-items: center; justify-content: center; color: var(--text-muted);
            border: 1px solid var(--border-color);
        ">
            <i data-lucide="x" style="width: 18px;"></i>
        </a>
    </div>

    <form method="POST">

        <!-- INFO BÁSICA -->
        <div class="form-section">
            <h3 class="form-title"><i data-lucide="music" style="width: 18px;"></i> Informações Básicas</h3>

            <div class="form-group">
                <label>Título da Música</label>
                <input type="text" name="title" class="input-field" required placeholder="Ex: Grande é o Senhor" style="font-weight: 600;">
            </div>

            <div class="form-group">
                <label>Artista / Banda</label>
                <input type="text" name="artist" class="input-field" list="artist-list" required placeholder="Ex: Adhemar de Campos">
                <datalist id="artist-list">
                    <?php foreach ($artists as $art): ?>
                        <option value="<?= htmlspecialchars($art) ?>">
                        <?php endforeach; ?>
                </datalist>
            </div>

            <div class="form-grid">
                <div class="form-group">
                    <label>Tom (Original)</label>
                    <select name="tone" class="input-field">
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
                    <label>BPM</label>
                    <input type="number" name="bpm" class="input-field" placeholder="Ex: 72">
                </div>
            </div>

            <div class="form-group" style="margin-bottom: 0;">
                <label>Duração (mm:ss)</label>
                <input type="text" name="duration" class="input-field" placeholder="05:30">
            </div>
        </div>

        <!-- LINKS -->
        <div class="form-section">
            <h3 class="form-title"><i data-lucide="link" style="width: 18px;"></i> Links e Referências</h3>

            <div class="form-group">
                <label><i data-lucide="music-2" style="width: 14px; display:inline; vertical-align:middle;"></i> Link da Cifra</label>
                <input type="url" name="link_cifra" class="input-field" placeholder="https://cifraclub.com.br/...">
            </div>
            <div class="form-group">
                <label><i data-lucide="file-text" style="width: 14px; display:inline; vertical-align:middle;"></i> Link da Letra</label>
                <input type="url" name="link_letra" class="input-field" placeholder="https://letras.mus.br/...">
            </div>
            <div class="form-group">
                <label><i data-lucide="youtube" style="width: 14px; display:inline; vertical-align:middle;"></i> Link do Vídeo</label>
                <input type="url" name="link_video" class="input-field" placeholder="https://youtube.com/...">
            </div>
            <div class="form-group" style="margin-bottom: 0;">
                <label><i data-lucide="headphones" style="width: 14px; display:inline; vertical-align:middle;"></i> Link do Áudio</label>
                <input type="url" name="link_audio" class="input-field" placeholder="https://spotify.com/...">
            </div>
        </div>

        <!-- TAGS -->
        <div class="form-section">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px; border-bottom: 1px solid var(--border-color); padding-bottom: 12px;">
                <h3 style="font-size: 1rem; font-weight: 700; color: var(--text-main); margin: 0; display:flex; gap:8px; align-items:center;"><i data-lucide="tag" style="width: 18px;"></i> Classificação</h3>
                <a href="classificacoes.php" target="_blank" style="font-size: 0.75rem; color: var(--primary); font-weight: 600; text-decoration: none;">Gerenciar</a>
            </div>

            <div class="tags-grid">
                <?php foreach ($allTags as $tag): ?>
                    <label>
                        <input type="checkbox" name="selected_tags[]" value="<?= $tag['id'] ?>" class="tag-checkbox">
                        <span class="tag-label">
                            <?= htmlspecialchars($tag['name']) ?>
                        </span>
                    </label>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- NOTAS -->
        <div class="form-section">
            <h3 class="form-title"><i data-lucide="sticky-note" style="width: 18px;"></i> Observações</h3>
            <textarea name="notes" rows="3" class="input-field" style="resize: vertical; min-height: 80px;" placeholder="Detalhes sobre arranjo, versão, etc..."></textarea>
        </div>

        <!-- BOTTOM BAR -->
        <div class="bottom-bar">
            <a href="repertorio.php" style="
                padding: 10px 20px; border-radius: 8px; font-weight: 600; color: var(--text-main); 
                text-decoration: none; font-size: 0.9rem;
            ">Cancelar</a>
            <button type="submit" class="ripple" style="
                background: var(--primary); color: white; border: none; padding: 10px 24px; 
                border-radius: 8px; font-weight: 600; font-size: 0.9rem; display: flex; align-items: center; gap: 8px;
                box-shadow: var(--shadow-sm);
            ">
                <i data-lucide="check" style="width: 16px;"></i> Salvar Música
            </button>
        </div>

    </form>
</div>


<?php renderAppFooter(); ?>