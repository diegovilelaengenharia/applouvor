<?php
// admin/repertorio.php
require_once '../includes/db.php';
require_once '../includes/layout.php';
require_once 'init_db_suggestions.php';
require_once '../includes/classes/MusicRepository.php';

$musicRepo = new \App\Repositories\MusicRepository($pdo);

// Filtros e Busca
$search = $_GET['q'] ?? '';
$tab = $_GET['tab'] ?? 'musicas'; // musicas, pastas, artistas

renderAppHeader('Repertório', 'index.php');
?>
<!-- Import CSS -->
<link rel="stylesheet" href="../assets/css/pages/repertorio.css?v=<?= time() ?>">

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
    <!-- Botão de 3 Pontinhos -->
    <div class="relative">
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
        menu.classList.toggle('active');
        btn.classList.toggle('active');
    }
    
    // Fechar menu ao clicar fora
    document.addEventListener('click', function(event) {
        const menu = document.getElementById('options-menu');
        const btn = document.getElementById('options-menu-btn');
        if (menu && btn && !btn.contains(event.target) && !menu.contains(event.target)) {
            menu.classList.remove('active');
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

    <?php 
    // Helper function for tone classes available globally in this scope
    if (!function_exists('getToneClass')) {
        function getToneClass($tone) {
            $toneMap = [
                'C'=>'tone-C', 'C#'=>'tone-Cs', 'Db'=>'tone-Db',
                'D'=>'tone-D', 'D#'=>'tone-Ds', 'Eb'=>'tone-Eb',
                'E'=>'tone-E', 'F'=>'tone-F', 'F#'=>'tone-Fs', 'Gb'=>'tone-Gb',
                'G'=>'tone-G', 'G#'=>'tone-Gs', 'Ab'=>'tone-Ab',
                'A'=>'tone-A', 'A#'=>'tone-As', 'Bb'=>'tone-Bb',
                'B'=>'tone-B'
            ];
            return $toneMap[$tone] ?? 'tone-C';
        }
    }

    if ($tab === 'musicas'):
        $tagId = $_GET['tag_id'] ?? null;
        $tone = $_GET['tone'] ?? null;
        try {
            $songs = $musicRepo->getSongs($search, $tagId, $tone, 50);
        } catch (Exception $e) {
            $songs = [];
        }
    ?>

        <!-- Filter Badge for Tag -->
        <?php if ($tagId):
            $currentTag = $musicRepo->getTagById($tagId);
        ?>
            <?php if ($currentTag): ?>
                <div class="active-filter-badge tag-badge" style="--tag-color: <?= $currentTag['color'] ?>;">
                    <div class="filter-label">
                        <i data-lucide="folder-open" width="18"></i>
                        Pasta: <?= htmlspecialchars($currentTag['name']) ?>
                    </div>
                    <a href="repertorio.php?tab=musicas" class="btn-clear-filter">
                        <i data-lucide="x" width="16"></i> Limpar
                    </a>
                </div>
            <?php endif; ?>
        <?php endif; ?>

        <!-- Filter Badge for Tone -->
        <?php if ($tone):
            $toneClass = getToneClass($tone);
        ?>
            <div class="active-filter-badge tone-badge <?= $toneClass ?>">
                 <div class="filter-label">
                    <i data-lucide="music" width="18"></i>
                    Tom: <?= htmlspecialchars($tone) ?>
                </div>
                <a href="repertorio.php?tab=musicas" class="btn-clear-filter">
                    <i data-lucide="x" width="16"></i> Limpar
                </a>
            </div>
        <?php endif; ?>

        <!-- MUSIC LIST (PIB CARDS) -->
        <div class="results-list" style="display: flex; flex-direction: column; gap: var(--space-md); padding-bottom: 100px;">
            <?php 
            $delay = 0.1;
            foreach ($songs as $song): 
                $songTags = $musicRepo->getSongTags($song['id']);
            ?>
                <!-- PIB MUSIC CARD -->
                <div class="animate-card" style="animation-delay: <?= $delay ?>s;">
                    <div class="pib-card" style="border-left: 5px solid <?= !empty($songTags) ? $songTags[0]['color'] : 'var(--color-primary)' ?>;">
                        <div class="pib-card-header">
                            <div style="display: flex; align-items: center; gap: 8px;">
                                <div style="width: 36px; height: 36px; background: var(--color-surface-alt); border-radius: var(--radius-md); display: flex; flex-direction: column; align-items: center; justify-content: center; border: 1px solid var(--color-border);">
                                    <span style="font-size: 0.9rem; font-weight: 800; color: var(--color-primary);"><?= $song['tone'] ?: '?' ?></span>
                                    <span style="font-size: 0.5rem; font-weight: 700; opacity: 0.6;">TOM</span>
                                </div>
                                <div>
                                    <h3 class="pib-card-title" style="margin: 0; font-size: 1rem;"><?= htmlspecialchars($song['title']) ?></h3>
                                    <p style="margin: 0; font-size: 0.75rem; color: var(--color-text-muted); font-weight: 600;"><?= htmlspecialchars($song['artist']) ?></p>
                                    <?php if (!empty($song['last_played'])): ?>
                                    <p style="margin: 0; font-size: 0.68rem; color: var(--color-text-muted); opacity: 0.7;">
                                        Última: <?= (new DateTime($song['last_played']))->format('d/m/y') ?>
                                    </p>
                                    <?php else: ?>
                                    <p style="margin: 0; font-size: 0.68rem; color: var(--color-text-muted); opacity: 0.4;">Nunca tocada</p>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <a href="musica_detalhe.php?id=<?= $song['id'] ?>" style="color: var(--color-primary); opacity: 0.5;">
                                <i data-lucide="chevron-right" style="width: 20px;"></i>
                            </a>
                        </div>

                        <!-- Tags -->
                        <?php if (!empty($songTags)): ?>
                        <div style="display: flex; gap: 4px; flex-wrap: wrap; margin-top: 4px;">
                            <?php foreach ($songTags as $tag): ?>
                                <span class="pib-badge" style="background: <?= $tag['color'] ?>15; color: <?= $tag['color'] ?>; font-size: 0.6rem; padding: 2px 8px;">
                                    <?= htmlspecialchars($tag['name']) ?>
                                </span>
                            <?php endforeach; ?>
                        </div>
                        <?php endif; ?>

                        <!-- Study Actions -->
                        <div class="pib-card-footer" style="padding-top: var(--space-sm); margin-top: var(--space-xs); border-top: 1px solid var(--color-border); justify-content: flex-start; gap: var(--space-md);">
                            <?php if ($song['link_cifra']): ?>
                                <a href="<?= $song['link_cifra'] ?>" target="_blank" class="pib-card-meta" style="color: var(--color-primary);">
                                    <i data-lucide="file-text" style="width: 14px;"></i> Cifra
                                </a>
                            <?php endif; ?>
                            <?php if ($song['link_video']): ?>
                                <a href="<?= $song['link_video'] ?>" target="_blank" class="pib-card-meta" style="color: #ef4444;">
                                    <i data-lucide="play-circle" style="width: 14px;"></i> Vídeo
                                </a>
                            <?php endif; ?>
                            <?php if ($song['link_audio']): ?>
                                <a href="<?= $song['link_audio'] ?>" target="_blank" class="pib-card-meta" style="color: #10b981;">
                                    <i data-lucide="headphones" style="width: 14px;"></i> Áudio
                                </a>
                            <?php endif; ?>
                            
                            <?php if (!$song['link_cifra'] && !$song['link_video'] && !$song['link_audio']): ?>
                                <span class="pib-card-meta" style="opacity: 0.4;">
                                    <i data-lucide="info" style="width: 14px;"></i> Sem links de estudo
                                </span>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php 
                $delay += 0.05;
            endforeach; ?>
            
            <?php if(empty($songs)): ?>
                <div class="empty-card" style="border: 2px dashed var(--border-medium); border-radius: var(--radius-lg); padding: 40px 20px; text-align: center; margin-top: 20px; width: 100%; box-sizing: border-box; background: var(--bg-surface);">
                    <div style="width: 56px; height: 56px; border-radius: 50%; background: var(--bg-surface-active); display: flex; align-items: center; justify-content: center; margin: 0 auto 16px;">
                        <i data-lucide="music" style="color: var(--text-tertiary); width: 24px; height: 24px;"></i>
                    </div>
                    <h4 style="margin: 0 0 6px 0; font-size: 1rem; font-weight: 800; color: var(--text-primary);">Sua biblioteca está vazia</h4>
                    <p style="margin: 0; font-size: 0.85rem; color: var(--text-secondary); font-weight: 600;">Nenhuma música encontrada no repertório.</p>
                </div>
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <!-- Conteúdo: Pastas (Tags) -->
    <?php if ($tab === 'pastas'):
        try {
            $tags = $musicRepo->getTagsWithCount();
        } catch (Exception $e) { $tags = []; }
    ?>
        <div class="results-list">
            <?php foreach ($tags as $tag): $bgHex = $tag['color'] ?? 'var(--sage-500)'; ?>
                <a href="repertorio.php?tab=musicas&tag_id=<?= $tag['id'] ?>" class="compact-card tag-card" style="--tag-color: <?= $bgHex ?>;">
                    
                    <!-- Ícone -->
                    <div class="compact-card-icon">
                        <i data-lucide="folder-heart" width="20"></i>
                    </div>

                    <!-- Conteúdo -->
                    <div class="compact-card-content">
                        <div class="compact-card-title">
                            <?= htmlspecialchars($tag['name']) ?>
                        </div>
                        <div class="compact-card-subtitle">
                            <?= $tag['count'] ?> música<?= $tag['count'] > 1 ? 's' : '' ?>
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
            $artists = $musicRepo->getArtistsWithCount();
        } catch (Exception $e) { $artists = []; }
    ?>
        <div class="results-list">
            <?php foreach ($artists as $artist): 
                // Buscar tags mais usadas pelo artista
                try {
                    $artistTags = $musicRepo->getTopTagsByArtist($artist['name'], 2);
                } catch (Exception $e) { $artistTags = []; }
                
                // Buscar tons mais usados pelo artista
                try {
                    $artistTones = $musicRepo->getTopTonesByArtist($artist['name'], 2);
                } catch (Exception $e) { $artistTones = []; }
            ?>
                <a href="repertorio.php?tab=musicas&q=<?= urlencode($artist['name']) ?>" class="compact-card">
                    
                    <!-- Ícone de Artista -->
                    <div class="compact-card-icon">
                        <i data-lucide="user" width="20" style="opacity: 0.5;"></i>
                    </div>

                    <!-- Conteúdo -->
                    <div class="compact-card-content">
                        <div class="compact-card-title">
                            <?= htmlspecialchars($artist['name']) ?>
                        </div>
                        <div class="compact-card-subtitle">
                            <span style="font-weight: 600;"><?= $artist['count'] ?> música<?= $artist['count'] > 1 ? 's' : '' ?></span>
                            
                            <?php if (!empty($artistTones)): ?>
                                <span style="opacity: 0.6;">•</span>
                                <span style="display: inline-flex; align-items: center; gap: 3px;">
                                    <i data-lucide="music" width="11"></i>
                                    <?php 
                                    $tonesList = array_column($artistTones, 'tone');
                                    echo htmlspecialchars(implode(', ', $tonesList));
                                    ?>
                                </span>
                            <?php endif; ?>
                            
                            <?php if (!empty($artistTags)): ?>
                                <?php foreach ($artistTags as $tag): ?>
                                    <span style="background: <?= $tag['color'] ?>15; color: <?= $tag['color'] ?>; padding: 1px 5px; border-radius: 4px; font-size: 0.65rem; font-weight: 700;">
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
        </div>
    <?php endif; ?>

    <!-- Conteúdo: Tons -->
    <?php if ($tab === 'tons'):
        try {
            $tones = $musicRepo->getTonesWithCount();
        } catch (Exception $e) { $tones = []; }
    ?>
        <div class="results-grid-2col">
            <?php foreach ($tones as $toneItem):
                $toneClass = getToneClass($toneItem['name']);
            ?>
                <a href="repertorio.php?tab=musicas&tone=<?= urlencode($toneItem['name']) ?>" class="compact-card tone-card <?= $toneClass ?>">
                    
                    <!-- Ícone Musical -->
                    <div class="compact-card-icon">
                        <i data-lucide="music" width="20"></i>
                    </div>

                    <!-- Conteúdo -->
                    <div class="compact-card-content">
                        <div class="compact-card-title">
                            Tom <?= htmlspecialchars($toneItem['name']) ?>
                        </div>
                        <div class="compact-card-subtitle">
                            <?= $toneItem['count'] ?> música<?= $toneItem['count'] > 1 ? 's' : '' ?>
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