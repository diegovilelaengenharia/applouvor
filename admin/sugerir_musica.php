<?php
// admin/sugerir_musica.php
require_once '../includes/auth.php';
require_once '../includes/db.php';
require_once '../includes/layout.php';
require_once 'init_db_suggestions.php'; // Garantir tabela

renderAppHeader('Sugerir Música');
renderPageHeader('Sugerir Música', 'Envie uma sugestão para o repertório');
?>

<link rel="stylesheet" href="../assets/css/pages/sugestoes.css">

<div class="form-container">
    <div class="form-card">
        <form id="suggestForm" onsubmit="submitSuggestion(event)">
            <!-- Row 1: Nome e Artista -->
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">Nome da Música <span class="required">*</span></label>
                    <input type="text" name="title" class="form-input" required placeholder="Ex: Bondade de Deus">
                </div>

                <div class="form-group">
                    <label class="form-label">Artista / Banda <span class="required">*</span></label>
                    <input type="text" name="artist" class="form-input" required placeholder="Ex: Isaías Saad">
                </div>
            </div>

            <!-- Row 2: Tom -->
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">Tom (Opcional)</label>
                    <input type="text" name="tone" class="form-input" placeholder="Ex: G">
                </div>
                <div></div> <!-- Empty space for alignment -->
            </div>

            <!-- Row 3: Links -->
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">Link YouTube (Opcional)</label>
                    <input type="url" name="youtube_link" class="form-input" placeholder="https://youtube.com/...">
                </div>

                <div class="form-group">
                    <label class="form-label">Link Spotify (Opcional)</label>
                    <input type="url" name="spotify_link" class="form-input" placeholder="https://spotify.com/...">
                </div>
            </div>

            <!-- Row 4: Motivo (Full Width) -->
            <div class="form-group full-width">
                <label class="form-label">Motivo / Observação</label>
                <textarea name="reason" class="form-input" rows="3" placeholder="Por que essa música seria boa para o ministério?"></textarea>
            </div>

            <!-- Actions -->
            <div class="form-actions">
                <a href="repertorio.php" class="btn btn-secondary">Cancelar</a>
                <button type="submit" class="btn btn-success">
                    <i data-lucide="send" style="width: 18px;"></i> Enviar Sugestão
                </button>
            </div>
        </form>
    </div>
</div>

<script>
async function submitSuggestion(e) {
    e.preventDefault();
    
    const form = e.target;
    const btn = form.querySelector('button[type="submit"]');
    const originalText = btn.innerHTML;
    
    // Disable button
    btn.disabled = true;
    btn.innerHTML = 'Enviando...';

    const formData = new FormData(form);
    const data = Object.fromEntries(formData.entries());

    try {
        const response = await fetch('sugestoes_api.php?action=create', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(data)
        });

        const result = await response.json();

        if (result.success) {
            alert('Sugestão enviada com sucesso!');
            window.location.href = 'repertorio.php';
        } else {
            alert('Erro: ' + (result.message || 'Erro desconhecido'));
            btn.disabled = false;
            btn.innerHTML = originalText;
        }
    } catch (error) {
        console.error(error);
        alert('Erro de conexão ao enviar sugestão.');
        btn.disabled = false;
        btn.innerHTML = originalText;
    }
}
lucide.createIcons();
</script>

<?php renderAppFooter(); ?>
