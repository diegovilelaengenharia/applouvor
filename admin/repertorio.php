<?php
// admin/repertorio.php
require_once '../includes/db.php';
require_once '../includes/layout.php';

// Controle de abas
$tab = $_GET['tab'] ?? 'musicas'; // 'musicas', 'artistas', 'pastas'

// Parâmetros de busca e filtros
$search = $_GET['search'] ?? '';
$tone = $_GET['tone'] ?? '';
$hasLetra = isset($_GET['has_letra']);
$hasCifra = isset($_GET['has_cifra']);
$hasAudio = isset($_GET['has_audio']);
$hasVideo = isset($_GET['has_video']);

// Buscar dados conforme a aba
if ($tab === 'musicas') {
    $sql = "SELECT * FROM songs WHERE 1=1";
    $params = [];

    // Filtro de busca por título ou artista
    if (!empty($search)) {
        $sql .= " AND (title LIKE ? OR artist LIKE ?)";
        $params[] = "%$search%";
        $params[] = "%$search%";
    }

    // Filtro por tom
    if (!empty($tone)) {
        $sql .= " AND tone = ?";
        $params[] = $tone;
    }

    // Filtros de referências
    if ($hasLetra) {
        $sql .= " AND link_letra IS NOT NULL AND link_letra != ''";
    }
    if ($hasCifra) {
        $sql .= " AND link_cifra IS NOT NULL AND link_cifra != ''";
    }
    if ($hasAudio) {
        $sql .= " AND link_audio IS NOT NULL AND link_audio != ''";
    }
    if ($hasVideo) {
        $sql .= " AND link_video IS NOT NULL AND link_video != ''";
    }

    $sql .= " ORDER BY title ASC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $count = count($items);
} elseif ($tab === 'artistas') {
    $sql = "SELECT artist, COUNT(*) as total FROM songs WHERE 1=1";
    $params = [];

    if (!empty($search)) {
        $sql .= " AND artist LIKE ?";
        $params[] = "%$search%";
    }

    $sql .= " GROUP BY artist ORDER BY artist ASC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $count = count($items);
} elseif ($tab === 'pastas') {
    $stmt = $pdo->query("SELECT category, COUNT(*) as total FROM songs GROUP BY category ORDER BY category ASC");
    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $count = count($items);
}

renderAppHeader('Repertório');
?>

<style>
    .repertorio-header {
        text-align: center;
        margin-bottom: 24px;
    }

    .repertorio-title {
        font-size: 1.8rem;
        font-weight: 800;
        color: var(--text-primary);
        margin-bottom: 4px;
    }

    .repertorio-subtitle {
        font-size: 0.9rem;
        color: var(--text-secondary);
    }

    .song-card {
        background: var(--bg-secondary);
        border: 1px solid var(--border-subtle);
        border-radius: 12px;
        padding: 12px;
        margin-bottom: 10px;
        display: flex;
        align-items: center;
        gap: 12px;
        text-decoration: none;
        transition: all 0.2s;
    }

    .song-card:hover {
        background: var(--bg-tertiary);
        border-color: var(--accent-interactive);
    }

    .song-icon {
        width: 48px;
        height: 48px;
        background: linear-gradient(135deg, #2D7A4F 0%, #1a4d2e 100%);
        border-radius: 8px;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
    }

    .song-info {
        flex: 1;
        min-width: 0;
    }

    .song-title {
        font-weight: 700;
        color: var(--text-primary);
        font-size: 0.95rem;
        margin-bottom: 2px;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .song-artist {
        font-size: 0.85rem;
        color: var(--text-secondary);
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .song-meta {
        display: flex;
        align-items: center;
        gap: 8px;
        font-size: 0.8rem;
        color: var(--text-muted);
    }

    .artist-card {
        background: var(--bg-secondary);
        border: 1px solid var(--border-subtle);
        border-radius: 12px;
        padding: 16px;
        margin-bottom: 10px;
        display: flex;
        align-items: center;
        gap: 12px;
        text-decoration: none;
        transition: all 0.2s;
    }

    .artist-card:hover {
        background: var(--bg-tertiary);
        border-color: var(--accent-interactive);
    }

    .artist-avatar {
        width: 48px;
        height: 48px;
        background: linear-gradient(135deg, #8B5CF6 0%, #6D28D9 100%);
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 700;
        color: white;
        font-size: 1.2rem;
        flex-shrink: 0;
    }

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
</style>

<!-- Hero Search Section -->
<div style="
    background: var(--gradient-yellow); 
    margin: -24px -16px 32px -16px; 
    padding: 32px 24px 64px 24px; 
    border-radius: 0 0 32px 32px; 
    box-shadow: var(--shadow-md);
    position: relative;
    overflow: visible;
">
    <div style="display: flex; justify-content: space-between; align-items: flex-start;">
        <div>
            <h1 style="color: white; margin: 0; font-size: 2rem; font-weight: 800; letter-spacing: -0.5px;">Repertório</h1>
            <p style="color: rgba(255,255,255,0.9); margin-top: 4px; font-weight: 500; font-size: 0.95rem;">Louvor PIB Oliveira</p>
        </div>
        <button class="ripple" onclick="openOptionsMenu()" style="
            background: rgba(255,255,255,0.2); 
            border: none; 
            width: 44px; 
            height: 44px; 
            border-radius: 12px; 
            display: flex; 
            align-items: center; 
            justify-content: center;
            color: white;
            backdrop-filter: blur(4px);
            cursor: pointer;
        ">
            <i data-lucide="more-vertical" style="width: 20px;"></i>
        </button>
    </div>

    <!-- Floating Search Bar -->
    <div style="position: absolute; bottom: -28px; left: 20px; right: 20px; z-index: 10;">
        <form method="GET" style="margin: 0;">
            <input type="hidden" name="tab" value="<?= $tab ?>">
            <div style="
                background: var(--bg-secondary); 
                border-radius: 16px; 
                padding: 6px; 
                box-shadow: 0 10px 30px rgba(0,0,0,0.15); 
                display: flex; 
                align-items: center;
                border: 1px solid rgba(0,0,0,0.05);
            ">
                <div style="
                    width: 44px; 
                    height: 44px; 
                    display: flex; 
                    align-items: center; 
                    justify-content: center; 
                    color: var(--primary-green);
                ">
                    <i data-lucide="search" style="width: 22px;"></i>
                </div>

                <input
                    type="text"
                    name="search"
                    value="<?= htmlspecialchars($_GET['search'] ?? '') ?>"
                    placeholder="Buscar músicas, artistas..."
                    style="
                        border: none; 
                        background: transparent; 
                        padding: 12px 0; 
                        width: 100%; 
                        font-size: 1rem; 
                        color: var(--text-primary);
                        outline: none;
                        font-weight: 500;
                    ">

                <?php if (!empty($_GET['search'])): ?>
                    <a href="?tab=<?= $tab ?>" style="
                        width: 40px; 
                        height: 40px; 
                        display: flex; 
                        align-items: center; 
                        justify-content: center; 
                        color: var(--text-muted); 
                        text-decoration: none;
                        cursor: pointer;
                    ">
                        <i data-lucide="x" style="width: 18px;"></i>
                    </a>
                <?php endif; ?>
            </div>
        </form>
    </div>
</div>

<!-- Navegação de Abas -->
<div style="background: var(--bg-tertiary); padding: 4px; border-radius: 16px; display: flex; margin-bottom: 20px;">
    <a href="?tab=musicas" class="ripple" style="flex: 1; text-align: center; padding: 10px; border-radius: 12px; font-size: 0.9rem; font-weight: 600; text-decoration: none; transition: all 0.2s; <?= $tab === 'musicas' ? 'background: var(--bg-secondary); color: var(--text-primary); box-shadow: var(--shadow-sm);' : 'color: var(--text-secondary);' ?>">
        Músicas (<?= $tab === 'musicas' ? $count : $pdo->query("SELECT COUNT(*) FROM songs")->fetchColumn() ?>)
    </a>
    <a href="?tab=pastas" class="ripple" style="flex: 1; text-align: center; padding: 10px; border-radius: 12px; font-size: 0.9rem; font-weight: 600; text-decoration: none; transition: all 0.2s; <?= $tab === 'pastas' ? 'background: var(--bg-secondary); color: var(--text-primary); box-shadow: var(--shadow-sm);' : 'color: var(--text-secondary);' ?>">
        Pastas (<?= $tab === 'pastas' ? $count : $pdo->query("SELECT COUNT(DISTINCT category) FROM songs")->fetchColumn() ?>)
    </a>
    <a href="?tab=artistas" class="ripple" style="flex: 1; text-align: center; padding: 10px; border-radius: 12px; font-size: 0.9rem; font-weight: 600; text-decoration: none; transition: all 0.2s; <?= $tab === 'artistas' ? 'background: var(--bg-secondary); color: var(--text-primary); box-shadow: var(--shadow-sm);' : 'color: var(--text-secondary);' ?>">
        Artistas (<?= $tab === 'artistas' ? $count : $pdo->query("SELECT COUNT(DISTINCT artist) FROM songs")->fetchColumn() ?>)
    </a>
</div>

<!-- Conteúdo das Abas -->
<?php if ($tab === 'musicas'): ?>
    <!-- Lista de Músicas -->
    <?php foreach ($items as $song): ?>
        <a href="musica_detalhe.php?id=<?= $song['id'] ?>" class="song-card ripple">
            <div class="song-icon">
                <i data-lucide="music" style="width: 24px; height: 24px; color: white;"></i>
            </div>
            <div class="song-info">
                <div class="song-title"><?= htmlspecialchars($song['title']) ?></div>
                <div class="song-artist"><?= htmlspecialchars($song['artist']) ?></div>
                <div class="song-meta">
                    <?php if ($song['tone']): ?>
                        <span>Tom: <strong><?= htmlspecialchars($song['tone']) ?></strong></span>
                    <?php endif; ?>
                    <?php if ($song['bpm']): ?>
                        <span>• BPM: <?= $song['bpm'] ?></span>
                    <?php endif; ?>
                </div>
            </div>
            <i data-lucide="chevron-right" style="width: 20px; color: var(--text-muted);"></i>
        </a>
    <?php endforeach; ?>

<?php elseif ($tab === 'artistas'): ?>
    <!-- Lista de Artistas -->
    <?php foreach ($items as $artist): ?>
        <a href="artista_detalhe.php?name=<?= urlencode($artist['artist']) ?>" class="artist-card ripple">
            <div class="artist-avatar">
                <?= strtoupper(substr($artist['artist'], 0, 1)) ?>
            </div>
            <div style="flex: 1;">
                <div style="font-weight: 700; color: var(--text-primary); margin-bottom: 2px;">
                    <?= htmlspecialchars($artist['artist']) ?>
                </div>
                <div style="font-size: 0.85rem; color: var(--text-secondary);">
                    <i data-lucide="music" style="width: 14px; display: inline;"></i> <?= $artist['total'] ?> música<?= $artist['total'] > 1 ? 's' : '' ?>
                </div>
            </div>
            <i data-lucide="chevron-right" style="width: 20px; color: var(--text-muted);"></i>
        </a>
    <?php endforeach; ?>

<?php elseif ($tab === 'pastas'): ?>
    <!-- Lista de Pastas/Categorias -->
    <?php foreach ($items as $pasta): ?>
        <a href="pasta_detalhe.php?category=<?= urlencode($pasta['category']) ?>" class="artist-card ripple">
            <div class="artist-avatar" style="background: linear-gradient(135deg, #F59E0B 0%, #D97706 100%);">
                <i data-lucide="folder" style="width: 24px; color: white;"></i>
            </div>
            <div style="flex: 1;">
                <div style="font-weight: 700; color: var(--text-primary); margin-bottom: 2px;">
                    <?= htmlspecialchars($pasta['category']) ?>
                </div>
                <div style="font-size: 0.85rem; color: var(--text-secondary);">
                    <?= $pasta['total'] ?> música<?= $pasta['total'] > 1 ? 's' : '' ?>
                </div>
            </div>
            <i data-lucide="chevron-right" style="width: 20px; color: var(--text-muted);"></i>
        </a>
    <?php endforeach; ?>
<?php endif; ?>

<!-- Botão Flutuante para Adicionar -->
<a href="musica_adicionar.php" class="btn-primary ripple" style="position: fixed; bottom: calc(var(--bottom-nav-height) + 20px); right: 20px; width: 56px; height: 56px; border-radius: 50%; display: flex; align-items: center; justify-content: center; box-shadow: 0 8px 24px rgba(45, 122, 79, 0.4); z-index: 500;">
    <i data-lucide="plus" style="width: 24px; height: 24px;"></i>
</a>


<!-- Menu de Opções -->
<div id="optionsMenu" class="bottom-sheet-overlay">
    <div class="bottom-sheet-content">
        <div class="sheet-header">Opções do Repertório</div>

        <button onclick="openFilters()" class="btn-outline ripple" style="width: 100%; justify-content: flex-start; margin-bottom: 10px;">
            <i data-lucide="filter"></i> Filtros avançados
        </button>

        <a href="importar_excel_page.php" class="btn-outline ripple" style="width: 100%; justify-content: flex-start; margin-bottom: 10px; text-decoration: none;">
            <i data-lucide="upload"></i> Importar músicas
        </a>

        <a href="exportar_completo.php" class="btn-outline ripple" style="width: 100%; justify-content: flex-start; margin-bottom: 10px; text-decoration: none;">
            <i data-lucide="download"></i> Exportar músicas
        </a>

        <button onclick="confirmDeleteAll()" class="btn-outline ripple" style="width: 100%; justify-content: flex-start; color: var(--status-error); border-color: var(--status-error);">
            <i data-lucide="trash-2"></i> Excluir repertório
        </button>

        <button onclick="closeSheet('optionsMenu')" class="btn-primary ripple" style="width: 100%; justify-content: center; margin-top: 16px;">
            Fechar
        </button>
    </div>
</div>


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

<script>
    // Abrir/Fechar modais
    function openOptionsMenu() {
        document.getElementById('optionsMenu').classList.add('active');
    }

    function closeSheet(id) {
        const sheet = document.getElementById(id);
        if (sheet) {
            sheet.classList.remove('active');
        }
    }

    function closeAllSheets() {
        const sheets = document.querySelectorAll('.bottom-sheet-overlay');
        sheets.forEach(sheet => sheet.classList.remove('active'));
    }

    function openFilters() {
        closeAllSheets();
        setTimeout(() => {
            document.getElementById('filtersModal').classList.add('active');
        }, 100);
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
        if (confirm('⚠️ ATENÇÃO!\n\nDeseja realmente excluir TODAS as músicas do repertório?\n\nEsta ação não pode ser desfeita!')) {
            if (confirm('Confirme novamente: Excluir TODO o repertório?')) {
                window.location.href = 'excluir_repertorio.php';
            }
        }
        closeSheet('optionsMenu');
    }
    // Fechar modal ao clicar fora
    document.addEventListener('click', function(e) {
        if (e.target.classList.contains('bottom-sheet-overlay')) {
            closeAllSheets();
        }
    });

    // Fechar com tecla ESC
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            closeAllSheets();
        }
    });

    // Busca em tempo real
    const searchInput = document.querySelector('input[name="search"]');
    if (searchInput) {
        let searchTimeout;

        searchInput.addEventListener('input', function() {
            clearTimeout(searchTimeout);
            const value = this.value.trim();

            // Aguardar 500ms após parar de digitar
            searchTimeout = setTimeout(() => {
                const currentTab = new URLSearchParams(window.location.search).get('tab') || 'musicas';
                if (value.length >= 2) {
                    window.location.href = `?tab=${currentTab}&search=${encodeURIComponent(value)}`;
                } else if (value.length === 0) {
                    window.location.href = `?tab=${currentTab}`;
                }
            }, 500);
        });

        // Remover botão "Buscar" pois agora é automático
        const searchButton = searchInput.parentElement.querySelector('button[type="submit"]');
        if (searchButton) {
            searchButton.style.display = 'none';
        }
    }
</script>

<?php renderAppFooter(); ?>