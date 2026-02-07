<?php
// admin/repertorio.php
require_once '../includes/db.php';
require_once '../includes/layout.php';
require_once 'init_db_suggestions.php';

// Filtros e Busca
$search = $_GET['q'] ?? '';
$tab = $_GET['tab'] ?? 'musicas'; // musicas, pastas, artistas

renderAppHeader('Repertório', 'index.php');
?>
<style>
    /* FORÇANDO ESTILOS COMPACTOS (Dedup de Cache) */
    .timeline-card.compact .card-content-wrapper {
        padding: 8px 12px !important;
        gap: 10px !important;
    }
    .timeline-card.compact .date-box {
        min-width: 36px !important;
        height: 36px !important;
        border-radius: 8px !important;
    }
    .timeline-card.compact .date-box i {
        width: 18px !important;
        height: 18px !important;
    }
    .timeline-card.compact .event-title {
        font-size: 0.9rem !important;
        margin-bottom: 0 !important;
    }
    .timeline-card.compact .meta-row {
        font-size: 0.7rem !important;
    }
    .timeline-card.compact .card-arrow {
        transform: scale(0.9);
    }
</style>
<?php
renderPageHeader('Repertório', 'Gestão de Músicas');
?>
<!-- Tabs Navegação com Menu -->
<div class="repertorio-controls">
    <div class="tabs-container">
        <a href="?tab=musicas" class="tab-link <?= $tab == 'musicas' ? 'active' : '' ?>">Músicas</a>
        <a href="?tab=pastas" class="tab-link <?= $tab == 'pastas' ? 'active' : '' ?>">TAG's</a>
        <a href="?tab=artistas" class="tab-link <?= $tab == 'artistas' ? 'active' : '' ?>">Artistas</a>
        <a href="?tab=tons" class="tab-link <?= $tab == 'tons' ? 'active' : '' ?>">Tons</a>
    </div>
    
    <!-- Botão de 3 Pontinhos -->
    <div style="position: relative;">
        <button onclick="toggleOptionsMenu()" id="options-menu-btn" class="btn-options">
            <i data-lucide="more-vertical" width="20"></i>
        </button>
        
        <!-- Dropdown Menu -->
        <div id="options-menu" class="options-dropdown">
            <a href="sugerir_musica.php" class="dropdown-item">
                <i data-lucide="send" width="16"></i> Sugerir Música
            </a>
            <?php if (isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin'): ?>
            <a href="musica_adicionar.php" class="dropdown-item">
                <i data-lucide="plus" width="16"></i> Adicionar Música
            </a>
            <a href="classificacoes.php" class="dropdown-item">
                <i data-lucide="tags" width="16"></i> Gerenciar TAGs
            </a>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
    function toggleOptionsMenu() {
        const menu = document.getElementById('options-menu');
        const btn = document.getElementById('options-menu-btn');
        if (getComputedStyle(menu).display === 'none') {
            menu.style.display = 'block';
            btn.classList.add('active');
        } else {
            menu.style.display = 'none';
            btn.classList.remove('active');
        }
    }
    
    // Fechar menu ao clicar fora
    document.addEventListener('click', function(event) {
        const menu = document.getElementById('options-menu');
        const btn = document.getElementById('options-menu-btn');
        if (menu && btn && !btn.contains(event.target) && !menu.contains(event.target)) {
            menu.style.display = 'none';
            btn.classList.remove('active');
        }
    });
</script>

<!-- Busca -->
<div class="search-container">
    <form method="GET">
        <?php if($tab != 'musicas'): ?>
            <input type="hidden" name="tab" value="<?= $tab ?>">
        <?php endif; ?>
        <div class="search-wrapper">
            <i data-lucide="search" class="search-icon"></i>
            <input type="text" name="q" class="search-input" placeholder="Buscar música, artista..." value="<?= htmlspecialchars($search) ?>">
        </div>
    </form>
</div>

<!-- Conteúdos -->
<div class="results-layout">

    <?php if ($tab === 'musicas'):
        $tagId = $_GET['tag_id'] ?? null;
        $tone = $_GET['tone'] ?? null;
        try {
            if ($tagId) {
                // Busca por Tag
                $sql = "
                    SELECT s.* 
                    FROM songs s 
                    JOIN song_tags st ON s.id = st.song_id 
                    WHERE st.tag_id = :tagId ";
                 if (!empty($search)) { $sql .= " AND (s.title LIKE :q OR s.artist LIKE :q) "; }
                $sql .= " ORDER BY s.title ASC LIMIT 50";
                
                $stmt = $pdo->prepare($sql);
                $params = ['tagId' => $tagId];
                if (!empty($search)) { $params['q'] = "%$search%"; }
                $stmt->execute($params);
            } elseif ($tone) {
                // Busca por Tom
                $sql = "SELECT * FROM songs WHERE tone = :tone";
                if (!empty($search)) { $sql .= " AND (title LIKE :q OR artist LIKE :q) "; }
                $sql .= " ORDER BY title ASC LIMIT 50";
                
                $stmt = $pdo->prepare($sql);
                $params = ['tone' => $tone];
                if (!empty($search)) { $params['q'] = "%$search%"; }
                $stmt->execute($params);
            } else {
                // Busca Normal
                $sql = "SELECT * FROM songs WHERE title LIKE :q OR artist LIKE :q ORDER BY title ASC LIMIT 50";
                $stmt = $pdo->prepare($sql);
                $stmt->execute(['q' => "%$search%"]);
            }
            $songs = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            $songs = [];
        }
    ?>

        <!-- Filter Badge for Tag -->
        <?php if ($tagId):
            $stmtTag = $pdo->prepare("SELECT name, color FROM tags WHERE id = ?");
            $stmtTag->execute([$tagId]);
            $currentTag = $stmtTag->fetch(PDO::FETCH_ASSOC);
        ?>
            <?php if ($currentTag): ?>
                <div class="active-filter-badge" style="background: <?= $currentTag['color'] ?>15; border-color: <?= $currentTag['color'] ?>30;">
                    <div class="filter-label" style="color: <?= $currentTag['color'] ?>;">
                        <i data-lucide="folder-open" width="18"></i>
                        Pasta: <?= htmlspecialchars($currentTag['name']) ?>
                    </div>
                    <a href="repertorio.php?tab=musicas" class="btn-clear-filter" style="color: <?= $currentTag['color'] ?>;">
                        <i data-lucide="x" width="16"></i> Limpar
                    </a>
                </div>
            <?php endif; ?>
        <?php endif; ?>

        <!-- Filter Badge for Tone -->
        <?php if ($tone):
            $toneColors = [
                'C' => 'var(--rose-500)', 'C#' => '#f97316', 'Db' => '#f97316',
                'D' => 'var(--yellow-500)', 'D#' => '#84cc16', 'Eb' => '#84cc16',
                'E' => 'var(--sage-500)', 'F' => '#14b8a6', 'F#' => '#06b6d4', 'Gb' => '#06b6d4',
                'G' => 'var(--slate-500)', 'G#' => '#6366f1', 'Ab' => '#6366f1',
                'A' => 'var(--lavender-600)', 'A#' => 'var(--lavender-500)', 'Bb' => 'var(--lavender-500)',
                'B' => '#ec4899'
            ];
            $toneColor = $toneColors[$tone] ?? 'var(--sage-500)';
        ?>
            <div class="active-filter-badge" style="background: <?= $toneColor ?>15; border-color: <?= $toneColor ?>30;">
                <div class="filter-label" style="color: <?= $toneColor ?>;">
                    <i data-lucide="music" width="18"></i>
                    Tom: <?= htmlspecialchars($tone) ?>
                </div>
                <a href="repertorio.php?tab=musicas" class="btn-clear-filter" style="color: <?= $toneColor ?>;">
                    <i data-lucide="x" width="16"></i> Limpar
                </a>
            </div>
        <?php endif; ?>

        <!-- MUSIC LIST (TIMELINE CARDS) -->
        <div class="results-list">
            <?php foreach ($songs as $song): 
                $stmtSongTags = $pdo->prepare("SELECT t.id, t.name, t.color FROM tags t JOIN song_tags st ON t.id = st.tag_id WHERE st.song_id = ? ORDER BY t.name");
                $stmtSongTags->execute([$song['id']]);
                $songTags = $stmtSongTags->fetchAll(PDO::FETCH_ASSOC);

                // Define visual tone color
                $tColor = 'var(--text-tertiary)';
                $tBg = 'var(--bg-surface-active)';
                if ($song['tone']) {
                    // Reuse tone array or default
                   $tBg = 'var(--bg-surface-active)'; 
                }
            ?>
                <!-- COMPACT MUSIC CARD -->
                <a href="musica_detalhe.php?id=<?= $song['id'] ?>" class="compact-card">
                    
                    <!-- Tom Badge -->
                    <div class="compact-card-icon">
                        <?php if ($song['tone']): ?>
                            <div style="font-size: 1rem; font-weight: 800; line-height: 1; color: var(--text-primary);"><?= $song['tone'] ?></div>
                            <div style="font-size: 0.6rem; font-weight: 700; text-transform: uppercase; opacity: 0.6; margin-top: 2px;">TOM</div>
                        <?php else: ?>
                            <i data-lucide="music" width="20" style="opacity:0.3"></i>
                        <?php endif; ?>
                    </div>

                    <!-- Conteúdo -->
                    <div class="compact-card-content">
                        <div class="compact-card-title">
                            <?= htmlspecialchars($song['title']) ?>
                        </div>
                        <div class="compact-card-subtitle">
                            <span><?= htmlspecialchars($song['artist']) ?></span>
                            <?php if (!empty($songTags)): ?>
                                <?php foreach (array_slice($songTags, 0, 2) as $tag): ?>
                                    <span style="background: <?= $tag['color'] ?>15; color: <?= $tag['color'] ?>; padding: 1px 6px; border-radius: 4px; font-size: 0.65rem; font-weight: 700;">
                                        <?= htmlspecialchars($tag['name']) ?>
                                    </span>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Seta -->
                    <i data-lucide="chevron-right" width="18" class="compact-card-arrow"></i>
                </a>
            <?php endforeach; ?>
            
            <?php if(empty($songs)): ?>
                <div class="empty-timeline">
                    <p class="text-tertiary">Nenhuma música encontrada.</p>
                </div>
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <!-- Conteúdo: Pastas (Tags) -->
    <?php if ($tab === 'pastas'):
        try {
            $sql = "SELECT t.*, COUNT(st.song_id) as count FROM tags t LEFT JOIN song_tags st ON t.id = st.tag_id GROUP BY t.id ORDER BY t.name ASC";
            $stmt = $pdo->query($sql);
            $tags = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) { $tags = []; }
    ?>
        <div class="results-list">
            <?php foreach ($tags as $tag): $bgHex = $tag['color'] ?? 'var(--sage-500)'; ?>
                <a href="repertorio.php?tab=musicas&tag_id=<?= $tag['id'] ?>" class="compact-card" style="border-left-color: <?= $bgHex ?>;">
                    
                    <!-- Ícone -->
                    <div class="compact-card-icon" style="background: <?= $bgHex ?>15; color: <?= $bgHex ?>;">
                        <i data-lucide="folder-heart" width="20"></i>
                    </div>

                    <!-- Conteúdo -->
                    <div class="compact-card-content">
                        <div class="compact-card-title">
                            <?= htmlspecialchars($tag['name']) ?>
                        </div>
                        <div class="compact-card-subtitle">
                            <?= $tag['count'] ?> músicas
                        </div>
                    </div>

                    <!-- Seta -->
                    <i data-lucide="chevron-right" width="18" class="compact-card-arrow"></i>
                </a>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <!-- Conteúdo: Artistas -->
    <?php if ($tab === 'artistas'):
        try {
            $sql = "SELECT artist as name, COUNT(*) as count FROM songs WHERE artist IS NOT NULL AND artist != '' GROUP BY artist ORDER BY artist ASC";
            $stmt = $pdo->query($sql);
            $artists = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) { $artists = []; }
    ?>
        <div class="results-list">
            <?php foreach ($artists as $artist): ?>
                <a href="repertorio.php?tab=musicas&q=<?= urlencode($artist['name']) ?>" class="compact-card" style="border-left-color: var(--violet-500);">
                    
                    <!-- Avatar -->
                    <div class="compact-card-icon rounded" style="background: var(--violet-500); color: white; font-size: 1rem; font-weight: 700;">
                        <?= strtoupper(substr($artist['name'], 0, 1)) ?>
                    </div>

                    <!-- Conteúdo -->
                    <div class="compact-card-content">
                        <div class="compact-card-title">
                            <?= htmlspecialchars($artist['name']) ?>
                        </div>
                        <div class="compact-card-subtitle">
                            <?= $artist['count'] ?> músicas
                        </div>
                    </div>

                    <!-- Seta -->
                    <i data-lucide="chevron-right" width="18" class="compact-card-arrow"></i>
                </a>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <!-- Conteúdo: Tons -->
    <?php if ($tab === 'tons'):
        // (Copy tone logic if needed or adapt)
        // ... (Simplified for brevity, assuming similar structure)
        $toneColors = ['C' => 'var(--rose-500)', 'C#' => '#f97316', 'Db' => '#f97316', 'D' => 'var(--yellow-500)', 'E' => 'var(--sage-500)', 'F' => '#14b8a6', 'G' => 'var(--slate-500)', 'A' => 'var(--lavender-600)', 'B' => '#ec4899'];
        try {
            $sql = "SELECT tone as name, COUNT(*) as count FROM songs WHERE tone IS NOT NULL AND tone != '' GROUP BY tone ORDER BY tone ASC";
            $stmt = $pdo->query($sql);
            $tones = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) { $tones = []; }
    ?>
        <div class="results-list">
            <?php foreach ($tones as $toneItem):
                $bgHex = $toneColors[$toneItem['name']] ?? 'var(--slate-500)';
            ?>
                <a href="repertorio.php?tab=musicas&tone=<?= urlencode($toneItem['name']) ?>" class="compact-card" style="border-left-color: <?= $bgHex ?>;">
                    
                    <!-- Ícone Musical -->
                    <div class="compact-card-icon" style="background: <?= $bgHex ?>15; color: <?= $bgHex ?>;">
                        <i data-lucide="music" width="20"></i>
                    </div>

                    <!-- Conteúdo -->
                    <div class="compact-card-content">
                        <div class="compact-card-title">
                            Tom <?= htmlspecialchars($toneItem['name']) ?>
                        </div>
                        <div class="compact-card-subtitle">
                            <?= $toneItem['count'] ?> músicas
                        </div>
                    </div>

                    <!-- Seta -->
                    <i data-lucide="chevron-right" width="18" class="compact-card-arrow"></i>
                </a>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

</div>

<?php renderAppFooter(); ?>