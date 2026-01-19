<?php
// admin/musica_adicionar.php
require_once '../includes/db.php';
require_once '../includes/layout.php';

// Processar formulário
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $stmt = $pdo->prepare("
        INSERT INTO songs (
            title, artist, tone, bpm, duration, category, 
            link_letra, link_cifra, link_audio, link_video, 
            tags, notes, created_at
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
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
        $_POST['notes'] ?: null
    ]);

    $newId = $pdo->lastInsertId();
    header("Location: musica_detalhe.php?id=$newId");
    exit;
}

renderAppHeader('Nova Música');
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
</style>

<!-- Header -->
<div style="display: flex; align-items: center; gap: 12px; margin-bottom: 24px;">
    <a href="repertorio.php" class="btn-icon ripple">
        <i data-lucide="x"></i>
    </a>
    <h1 style="font-size: 1.3rem; font-weight: 800; color: var(--text-primary); margin: 0;">Nova Música</h1>
</div>

<form method="POST">
    <!-- Informações Básicas -->
    <div class="form-section">
        <div class="form-section-title">Informações Básicas</div>

        <div class="form-group" style="margin-bottom: 16px;">
            <label class="form-label">Título da Música *</label>
            <input type="text" name="title" class="form-input" placeholder="Ex: Grande é o Senhor" required autofocus>
        </div>

        <div class="form-group" style="margin-bottom: 16px;">
            <label class="form-label">Artista *</label>
            <input type="text" name="artist" class="form-input" placeholder="Ex: Adhemar De Campos" required>
        </div>

        <div class="form-group">
            <label class="form-label">Categoria</label>
            <select name="category" class="form-input">
                <option value="Louvor" selected>Louvor</option>
                <option value="Adoração">Adoração</option>
                <option value="Celebração">Celebração</option>
                <option value="Hino">Hino</option>
            </select>
        </div>
    </div>

    <!-- Detalhes Musicais -->
    <div class="form-section">
        <div class="form-section-title">Detalhes Musicais</div>

        <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 12px;">
            <div class="form-group">
                <label class="form-label">Tom</label>
                <input type="text" name="tone" class="form-input" placeholder="Ex: G, Am, C#">
            </div>

            <div class="form-group">
                <label class="form-label">BPM</label>
                <input type="number" name="bpm" class="form-input" placeholder="120">
            </div>

            <div class="form-group">
                <label class="form-label">Duração</label>
                <input type="text" name="duration" class="form-input" placeholder="3:45">
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
            <input type="url" name="link_letra" class="form-input" placeholder="https://www.letras.mus.br/...">
        </div>

        <div class="form-group" style="margin-bottom: 16px;">
            <label class="form-label">
                <i data-lucide="music-2" style="width: 14px; display: inline;"></i> Link da Cifra
            </label>
            <input type="url" name="link_cifra" class="form-input" placeholder="https://www.cifraclub.com.br/...">
        </div>

        <div class="form-group" style="margin-bottom: 16px;">
            <label class="form-label">
                <i data-lucide="headphones" style="width: 14px; display: inline;"></i> Link do Áudio
            </label>
            <input type="url" name="link_audio" class="form-input" placeholder="https://www.deezer.com/...">
        </div>

        <div class="form-group">
            <label class="form-label">
                <i data-lucide="video" style="width: 14px; display: inline;"></i> Link do Vídeo (YouTube)
            </label>
            <input type="url" name="link_video" class="form-input" placeholder="https://youtu.be/...">
        </div>
    </div>

    <!-- Tags e Observações -->
    <div class="form-section">
        <div class="form-section-title">Tags e Observações</div>

        <div class="form-group" style="margin-bottom: 16px;">
            <label class="form-label">Tags (separadas por vírgula)</label>
            <input type="text" name="tags" class="form-input" placeholder="Repertório 2025, Favorita, Natal">
        </div>

        <div class="form-group">
            <label class="form-label">Observações</label>
            <textarea name="notes" class="form-input" rows="4" style="resize: vertical;" placeholder="Alguma observação sobre a música..."></textarea>
        </div>
    </div>

    <!-- Botões -->
    <div style="display: flex; gap: 12px; margin-top: 24px; padding-bottom: 80px;">
        <a href="repertorio.php" class="btn-outline ripple" style="flex: 1; justify-content: center; text-decoration: none;">
            Cancelar
        </a>
        <button type="submit" class="btn-primary ripple" style="flex: 2; justify-content: center;">
            <i data-lucide="plus"></i> Adicionar Música
        </button>
    </div>
</form>

<script>
    let customFieldCount = 0;

    function addCustomField() {
        customFieldCount++;
        const container = document.getElementById('customFieldsContainer');
        const fieldHtml = `
        <div class="custom-field-item" id="custom-field-${customFieldCount}" style="background: var(--bg-tertiary); padding: 16px; border-radius: 8px; margin-bottom: 12px;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px;">
                <span style="font-weight: 600; color: var(--text-primary);">Campo #${customFieldCount}</span>
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
</script>

<?php renderAppFooter(); ?>