# Script para adicionar filtros e corrigir funcoes no repertorio.php

file_path = r"admin\repertorio.php"

# Ler o arquivo
with open(file_path, 'r', encoding='utf-8') as f:
    content = f.read()

# Encontrar e substituir o script existente
old_script = '''<script>
    function openOptionsMenu() {
        document.getElementById('optionsMenu').classList.add('active');
    }
    
    function confirmDeleteAll() {
        if (confirm('⚠️ ATENÇÃO!\\n\\nDeseja realmente excluir TODAS as músicas do repertório?\\n\\nEsta ação não pode ser desfeita!')) {
            if (confirm('Confirme novamente: Excluir TODO o repertório?')) {
                window.location.href = 'excluir_repertorio.php';
            }
        }
        closeSheet('optionsMenu');
    }
</script>'''

new_script = '''<script>
    // Abrir/Fechar modais
    function openOptionsMenu() {
        document.getElementById('optionsMenu').classList.add('active');
    }
    
    function closeSheet(id) {
        document.getElementById(id).classList.remove('active');
    }
    
    function openFilters() {
        closeSheet('optionsMenu');
        document.getElementById('filtersModal').classList.add('active');
    }
    
    function clearFilters() {
        window.location.href = 'repertorio.php';
    }
    
    // Seleção múltipla
    let selectionMode = false;
    let selectedSongs = new Set();
    
    function toggleSelectionMode() {
        selectionMode = !selectionMode;
        closeSheet('optionsMenu');
        
        if (selectionMode) {
            document.body.classList.add('selection-mode');
            showSelectionBar();
        } else {
            document.body.classList.remove('selection-mode');
            hideSelectionBar();
            selectedSongs.clear();
        }
    }
    
    function showSelectionBar() {
        const bar = document.createElement('div');
        bar.id = 'selectionBar';
        bar.innerHTML = `
            <div style="position: fixed; bottom: var(--bottom-nav-height); left: 0; right: 0; background: var(--accent-interactive); color: white; padding: 16px; display: flex; align-items: center; justify-content: space-between; z-index: 1000; box-shadow: 0 -4px 12px rgba(0,0,0,0.2);">
                <span id="selectedCount" style="font-weight: 700;">0 selecionadas</span>
                <div style="display: flex; gap: 12px;">
                    <button onclick="createYouTubePlaylist()" class="btn-icon ripple" style="background: rgba(255,255,255,0.2);">
                        <i data-lucide="play"></i>
                    </button>
                    <button onclick="toggleSelectionMode()" class="btn-icon ripple" style="background: rgba(255,255,255,0.2);">
                        <i data-lucide="x"></i>
                    </button>
                </div>
            </div>
        `;
        document.body.appendChild(bar);
        lucide.createIcons();
    }
    
    function hideSelectionBar() {
        const bar = document.getElementById('selectionBar');
        if (bar) bar.remove();
    }
    
    function toggleSongSelection(songId) {
        if (selectedSongs.has(songId)) {
            selectedSongs.delete(songId);
        } else {
            selectedSongs.add(songId);
        }
        updateSelectionCount();
    }
    
    function updateSelectionCount() {
        const countEl = document.getElementById('selectedCount');
        if (countEl) {
            countEl.textContent = selectedSongs.size + ' selecionada' + (selectedSongs.size !== 1 ? 's' : '');
        }
    }
    
    // YouTube Playlist
    function createYouTubePlaylist() {
        if (selectedSongs.size === 0) {
            alert('Selecione pelo menos uma música!');
            return;
        }
        
        const songIds = Array.from(selectedSongs);
        window.location.href = 'criar_playlist.php?songs=' + songIds.join(',');
    }
    
    // Excluir repertório
    function confirmDeleteAll() {
        if (confirm('⚠️ ATENÇÃO!\\n\\nDeseja realmente excluir TODAS as músicas do repertório?\\n\\nEsta ação não pode ser desfeita!')) {
            if (confirm('Confirme novamente: Excluir TODO o repertório?')) {
                window.location.href = 'excluir_repertorio.php';
            }
        }
        closeSheet('optionsMenu');
    }
</script>'''

# Substituir
content = content.replace(old_script, new_script)

# Adicionar modal de filtros antes do script
filters_modal = '''
<!-- Modal de Filtros Avançados -->
<div id="filtersModal" class="bottom-sheet-overlay">
    <div class="bottom-sheet-content" style="max-height: 90vh; overflow-y: auto;">
        <div class="sheet-header">
            <button onclick="closeSheet('filtersModal')" class="btn-icon ripple" style="position: absolute; left: 16px; top: 16px;">
                <i data-lucide="x"></i>
            </button>
            Filtros avançados
            <button onclick="clearFilters()" class="btn-text" style="position: absolute; right: 16px; top: 16px; color: var(--accent-interactive); font-weight: 600;">
                Limpar
            </button>
        </div>
        
        <form method="GET" style="padding: 20px;">
            <div class="form-group" style="margin-bottom: 16px;">
                <label class="form-label">Título ou artista</label>
                <input type="text" name="search" class="form-input" placeholder="Buscar..." value="<?= $_GET['search'] ?? '' ?>">
            </div>
            
            <div class="form-group" style="margin-bottom: 20px;">
                <label class="form-label">Tom</label>
                <input type="text" name="tone" class="form-input" placeholder="Ex: G, Am, C#" value="<?= $_GET['tone'] ?? '' ?>">
            </div>
            
            <div style="margin-bottom: 20px;">
                <label class="form-label" style="margin-bottom: 12px; display: block;">Referências</label>
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px;">
                    <label style="display: flex; align-items: center; gap: 8px; cursor: pointer;">
                        <input type="checkbox" name="has_letra" value="1" <?= !empty($_GET['has_letra']) ? 'checked' : '' ?>>
                        <i data-lucide="file-text" style="width: 16px;"></i> Letra
                    </label>
                    <label style="display: flex; align-items: center; gap: 8px; cursor: pointer;">
                        <input type="checkbox" name="has_cifra" value="1" <?= !empty($_GET['has_cifra']) ? 'checked' : '' ?>>
                        <i data-lucide="music-2" style="width: 16px;"></i> Cifra
                    </label>
                    <label style="display: flex; align-items: center; gap: 8px; cursor: pointer;">
                        <input type="checkbox" name="has_audio" value="1" <?= !empty($_GET['has_audio']) ? 'checked' : '' ?>>
                        <i data-lucide="headphones" style="width: 16px;"></i> Áudio
                    </label>
                    <label style="display: flex; align-items: center; gap: 8px; cursor: pointer;">
                        <input type="checkbox" name="has_video" value="1" <?= !empty($_GET['has_video']) ? 'checked' : '' ?>>
                        <i data-lucide="video" style="width: 16px;"></i> Vídeo
                    </label>
                </div>
            </div>
            
            <button type="submit" class="btn-primary ripple" style="width: 100%; justify-content: center;">
                <i data-lucide="check"></i> Aplicar
            </button>
        </form>
    </div>
</div>

'''

# Inserir modal de filtros antes do script
content = content.replace('<script>', filters_modal + '<script>')

# Atualizar botões do menu para usar as novas funções
content = content.replace(
    "onclick=\"alert('Filtros em desenvolvimento')\"",
    "onclick=\"openFilters()\""
)
content = content.replace(
    "onclick=\"alert('Seleção múltipla em desenvolvimento')\"",
    "onclick=\"toggleSelectionMode()\""
)

# Salvar
with open(file_path, 'w', encoding='utf-8') as f:
    f.write(content)

print("OK - Filtros e funcoes corrigidas!")
print("Teste: http://localhost:8000/admin/repertorio.php")
