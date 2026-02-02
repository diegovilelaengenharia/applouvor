<?php
// admin/sugestoes_musicas.php
require_once '../includes/auth.php';
require_once '../includes/db.php';
require_once '../includes/layout.php';
require_once 'init_db_suggestions.php';

// Apenas admin
if (($_SESSION['user_role'] ?? 'user') !== 'admin') {
    header('Location: repertorio.php');
    exit;
}

renderAppHeader('Gestão de Sugestões');
renderPageHeader('Gestão de Sugestões', 'Aprove ou rejeite músicas sugeridas');
?>

<div style="max-width: 800px; margin: 0 auto; padding: 16px;">
    
    <!-- Filtros (Abas simples) -->
    <div style="display: flex; gap: 8px; margin-bottom: 24px; overflow-x: auto; padding-bottom: 4px;">
        <button onclick="loadSuggestions('pending')" class="filter-btn active" id="btn-pending">Pendentes</button>
        <button onclick="loadSuggestions('approved')" class="filter-btn" id="btn-approved">Aprovadas</button>
        <button onclick="loadSuggestions('rejected')" class="filter-btn" id="btn-rejected">Rejeitadas</button>
    </div>

    <div id="suggestionsList" class="suggestions-grid">
        <!-- JS fill -->
        <div style="text-align: center; padding: 40px; color: var(--text-muted);">
            Carregando...
        </div>
    </div>

</div>

<style>
    .filter-btn {
        padding: 8px 16px;
        background: var(--bg-surface);
        border: 1px solid var(--border-color);
        border-radius: 20px;
        color: var(--text-muted);
        font-weight: 600;
        cursor: pointer;
        transition: all 0.2s;
        white-space: nowrap;
    }
    .filter-btn.active {
        background: var(--primary);
        color: white;
        border-color: var(--primary);
    }
    
    .suggestion-card {
        background: var(--bg-surface);
        border: 1px solid var(--border-color);
        border-radius: 12px;
        padding: 16px;
        margin-bottom: 16px;
        transition: transform 0.2s;
    }
    .suggestion-card:hover {
        transform: translateY(-2px);
        box-shadow: var(--shadow-sm);
    }
    
    .sug-header {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        margin-bottom: 12px;
    }
    
    .sug-title { font-size: 1.1rem; font-weight: 700; color: var(--text-main); margin-bottom: 2px; }
    .sug-artist { font-size: 0.9rem; color: var(--text-muted); }
    
    .sug-meta {
        background: #f8fafc;
        padding: 8px 12px;
        border-radius: 8px;
        font-size: 0.8rem;
        color: var(--text-muted);
        margin-bottom: 12px;
        display: flex;
        flex-wrap: wrap;
        gap: 12px;
        align-items: center;
    }
    
    .sug-user {
        display: flex; align-items: center; gap: 6px; font-weight: 600; color: var(--text-main);
    }
    
    .sug-reason {
        font-style: italic;
        color: var(--text-muted);
        margin-bottom: 16px;
        font-size: 0.9rem;
        padding-left: 12px;
        border-left: 2px solid var(--border-color);
    }
    
    .sug-actions {
        display: flex;
        gap: 8px;
        border-top: 1px solid var(--border-color);
        padding-top: 12px;
    }
    
    .sug-link {
        display: inline-flex; align-items: center; gap: 4px;
        color: var(--primary); text-decoration: none; font-weight: 600; font-size: 0.8rem;
    }
</style>

<script>
let currentFilter = 'pending';

async function loadSuggestions(filter) {
    currentFilter = filter;
    
    // Update tabs
    document.querySelectorAll('.filter-btn').forEach(b => b.classList.remove('active'));
    document.getElementById('btn-' + filter).classList.add('active');
    
    const container = document.getElementById('suggestionsList');
    container.innerHTML = '<div style="text-align:center; padding:40px;">Carregando...</div>';
    
    try {
        const response = await fetch(`sugestoes_api.php?action=list&filter=${filter}`);
        const result = await response.json();
        
        if (result.success) {
            renderSuggestions(result.suggestions);
        } else {
            container.innerHTML = '<div style="color:red; text-align:center;">Erro ao carregar.</div>';
        }
    } catch (e) {
        console.error(e);
        container.innerHTML = '<div style="color:red; text-align:center;">Erro de conexão.</div>';
    }
}

function renderSuggestions(list) {
    const container = document.getElementById('suggestionsList');
    if (list.length === 0) {
        container.innerHTML = `
            <div style="text-align: center; padding: 40px; color: var(--text-muted);">
                <i data-lucide="inbox" style="width: 48px; height: 48px; margin-bottom: 12px; opacity: 0.5;"></i>
                <p>Nenhuma sugestão encontrada nesta categoria.</p>
            </div>
        `;
        lucide.createIcons();
        return;
    }
    
    container.innerHTML = list.map(item => {
        const isPending = item.status === 'pending';
        const userPhoto = item.user_photo || 'https://ui-avatars.com/api/?name='+item.user_name+'&background=random';
        
        let actionsHtml = '';
        if (isPending) {
            actionsHtml = `
                <button onclick="decideSuggestion(${item.id}, 'approve')" class="btn btn-sm btn-success" style="flex:1;">
                    <i data-lucide="check"></i> Aprovar
                </button>
                <button onclick="decideSuggestion(${item.id}, 'reject')" class="btn btn-sm btn-danger" style="flex:1;">
                    <i data-lucide="x"></i> Rejeitar
                </button>
            `;
        } else {
            const statusLabel = item.status === 'approved' 
                ? '<span style="color:#059669; font-weight:700;">Aprovada</span>' 
                : '<span style="color:#dc2626; font-weight:700;">Rejeitada</span>';
            actionsHtml = `<div style="flex:1; text-align:right; font-size:0.9rem;">${statusLabel}</div>`;
        }
        
        return `
            <div class="suggestion-card">
                <div class="sug-header">
                    <div>
                        <div class="sug-title">${escapeHtml(item.title)}</div>
                        <div class="sug-artist">${escapeHtml(item.artist)} ${item.tone ? '• Tom: '+escapeHtml(item.tone) : ''}</div>
                    </div>
                    <div style="font-size:0.75rem; color:var(--text-muted); white-space:nowrap;">
                        ${new Date(item.created_at).toLocaleDateString()}
                    </div>
                </div>
                
                <div class="sug-meta">
                    <div class="sug-user">
                        <img src="${userPhoto}" style="width:20px; height:20px; border-radius:50%;">
                        ${escapeHtml(item.user_name)}
                    </div>
                    ${item.youtube_link ? `<a href="${item.youtube_link}" target="_blank" class="sug-link"><i data-lucide="youtube" width="14"></i> YouTube</a>` : ''}
                    ${item.spotify_link ? `<a href="${item.spotify_link}" target="_blank" class="sug-link"><i data-lucide="music" width="14"></i> Spotify</a>` : ''}
                </div>
                
                ${item.reason ? `<div class="sug-reason">"${escapeHtml(item.reason)}"</div>` : ''}
                
                <div class="sug-actions">
                    ${actionsHtml}
                </div>
            </div>
        `;
    }).join('');
    
    lucide.createIcons();
}

async function decideSuggestion(id, decision) {
    if (!confirm(decision === 'approve' ? 'Confirma aprovação e adição ao repertório?' : 'Confirma rejeição?')) return;
    
    try {
        const response = await fetch(`sugestoes_api.php?action=${decision}`, {
            method: 'POST',
            body: JSON.stringify({ id: id }),
            headers: { 'Content-Type': 'application/json' }
        });
        const result = await response.json();
        
        if (result.success) {
            alert(result.message);
            loadSuggestions(currentFilter);
        } else {
            alert('Erro: ' + result.message);
        }
    } catch (e) {
        console.error(e);
        alert('Erro ao processar.');
    }
}

function escapeHtml(text) {
    if (!text) return '';
    return text.replace(/&/g, "&amp;").replace(/</g, "&lt;").replace(/>/g, "&gt;").replace(/"/g, "&quot;").replace(/'/g, "&#039;");
}

// Init
loadSuggestions('pending');
</script>
<?php renderAppFooter(); ?>
