<?php
// admin/sugerir_musica.php
require_once '../includes/auth.php';
require_once '../includes/db.php';
require_once '../includes/layout.php';
require_once 'init_db_suggestions.php'; // Garantir tabela

renderAppHeader('Sugerir Música');
renderPageHeader('Sugerir Música', 'Envie uma sugestão para o repertório');
?>

<style>
    .form-container {
        max-width: 600px;
        margin: 0 auto;
        padding: 16px;
    }

    .form-card {
        background: var(--bg-surface);
        border-radius: 12px;
        padding: 20px;
        box-shadow: var(--shadow-sm);
        border: 1px solid var(--border-color);
    }

    .form-row {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 12px;
        margin-bottom: 12px;
    }

    .form-group {
        margin-bottom: 12px;
    }

    .form-group.full-width {
        grid-column: 1 / -1;
    }

    .form-label {
        display: block;
        font-size: 0.875rem;
        font-weight: 600;
        color: var(--text-main);
        margin-bottom: 6px;
    }

    .form-label .required {
        color: #ef4444;
    }

    .form-input {
        width: 100%;
        padding: 10px 12px;
        border: 1px solid var(--border-color);
        border-radius: 8px;
        font-size: 0.9375rem;
        color: var(--text-main);
        background: var(--bg-surface);
        transition: all 0.2s;
        box-sizing: border-box;
    }

    .form-input:focus {
        outline: none;
        border-color: var(--primary);
        box-shadow: 0 0 0 3px rgba(4, 120, 87, 0.1);
    }

    textarea.form-input {
        resize: vertical;
        min-height: 80px;
        font-family: inherit;
    }

    .form-actions {
        display: flex;
        gap: 12px;
        margin-top: 20px;
    }

    .btn {
        flex: 1;
        padding: 12px 20px;
        border-radius: 8px;
        font-weight: 600;
        font-size: 0.9375rem;
        cursor: pointer;
        transition: all 0.2s;
        border: none;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        text-decoration: none;
    }

    .btn-secondary {
        background: #f1f5f9;
        color: #64748b;
    }

    .btn-secondary:hover {
        background: #e2e8f0;
    }

    .btn-success {
        background: var(--primary);
        color: white;
    }

    .btn-success:hover {
        background: var(--primary-hover);
    }

    .btn:disabled {
        opacity: 0.5;
        cursor: not-allowed;
    }

    @media (max-width: 640px) {
        .form-row {
            grid-template-columns: 1fr;
        }
    }
</style>

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
