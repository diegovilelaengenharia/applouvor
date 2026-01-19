# Script para melhorar filtros e selecao no repertorio.php

file_path = r"admin\repertorio.php"

# Ler arquivo
with open(file_path, 'r', encoding='utf-8') as f:
    content = f.read()

# Adicionar campo de busca sempre visível após o header
search_bar = '''
<!-- Barra de Busca Sempre Visível -->
<div style="margin-bottom: 20px;">
    <form method="GET" style="position: relative;">
        <input type="hidden" name="tab" value="<?= $tab ?>">
        <input 
            type="text" 
            name="search" 
            class="form-input" 
            placeholder="🔍 Buscar músicas ou artistas..." 
            value="<?= $_GET['search'] ?? '' ?>"
            style="padding-right: 100px;"
        >
        <?php if (!empty($_GET['search'])): ?>
            <a href="?tab=<?= $tab ?>" style="position: absolute; right: 60px; top: 50%; transform: translateY(-50%); color: var(--text-muted); text-decoration: none; font-size: 1.2rem;">×</a>
        <?php endif; ?>
        <button type="submit" style="position: absolute; right: 12px; top: 50%; transform: translateY(-50%); background: var(--accent-interactive); color: white; border: none; padding: 8px 16px; border-radius: 6px; cursor: pointer; font-weight: 600;">
            Buscar
        </button>
    </form>
</div>
'''

# Inserir barra de busca após o header do repertório
content = content.replace(
    '</div>\n\n<!-- Navegação de Abas -->',
    '</div>\n\n' + search_bar + '\n<!-- Navegação de Abas -->'
)

# Adicionar CSS para modo de seleção
selection_css = '''
<style>
    .selection-mode .song-card,
    .selection-mode .artist-card {
        padding-left: 60px;
        position: relative;
    }
    .selection-mode .song-checkbox {
        position: absolute;
        left: 12px;
        top: 50%;
        transform: translateY(-50%);
        width: 24px;
        height: 24px;
        cursor: pointer;
    }
    .song-card.selected {
        background: rgba(45, 122, 79, 0.1);
        border-color: var(--accent-interactive);
    }
</style>
'''

# Adicionar CSS antes do </style> existente
content = content.replace('</style>', selection_css + '</style>')

# Melhorar função de seleção múltipla no JavaScript
old_selection_js = '''    // Seleção múltipla
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
    }'''

new_selection_js = '''    // Seleção múltipla
    let selectionMode = false;
    let selectedSongs = new Set();
    
    function toggleSelectionMode() {
        selectionMode = !selectionMode;
        closeSheet('optionsMenu');
        
        if (selectionMode) {
            document.body.classList.add('selection-mode');
            addCheckboxesToSongs();
            showSelectionBar();
        } else {
            document.body.classList.remove('selection-mode');
            removeCheckboxesFromSongs();
            hideSelectionBar();
            selectedSongs.clear();
        }
    }
    
    function addCheckboxesToSongs() {
        const songCards = document.querySelectorAll('.song-card');
        songCards.forEach(card => {
            if (!card.querySelector('.song-checkbox')) {
                const checkbox = document.createElement('input');
                checkbox.type = 'checkbox';
                checkbox.className = 'song-checkbox';
                checkbox.onclick = function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    const songId = card.getAttribute('href').split('id=')[1];
                    toggleSongSelection(songId, card, checkbox);
                };
                card.insertBefore(checkbox, card.firstChild);
                card.style.cursor = 'pointer';
                card.onclick = function(e) {
                    if (!e.target.classList.contains('song-checkbox')) {
                        checkbox.click();
                    }
                };
            }
        });
    }
    
    function removeCheckboxesFromSongs() {
        const checkboxes = document.querySelectorAll('.song-checkbox');
        checkboxes.forEach(cb => cb.remove());
        const songCards = document.querySelectorAll('.song-card');
        songCards.forEach(card => {
            card.onclick = null;
            card.style.cursor = '';
            card.classList.remove('selected');
        });
    }
    
    function toggleSongSelection(songId, card, checkbox) {
        if (selectedSongs.has(songId)) {
            selectedSongs.delete(songId);
            card.classList.remove('selected');
            checkbox.checked = false;
        } else {
            selectedSongs.add(songId);
            card.classList.add('selected');
            checkbox.checked = true;
        }
        updateSelectionCount();
    }'''

content = content.replace(old_selection_js, new_selection_js)

# Salvar
with open(file_path, 'w', encoding='utf-8') as f:
    f.write(content)

print("OK - Filtros e selecao melhorados!")
print("- Busca sempre visivel no topo")
print("- Checkboxes visuais na selecao multipla")
print("- Interface mais intuitiva")
