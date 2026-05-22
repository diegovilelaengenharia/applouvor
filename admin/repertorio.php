<?php
// admin/repertorio.php
require_once '../src/helpers/auth.php';
checkLogin();
require_once '../src/config/db.php';
require_once '../src/layout/layout.php';
require_once '../scripts/setup/init_db_suggestions.php';
require_once '../src/classes/MusicRepository.php';

$musicRepo = new \App\Repositories\MusicRepository($pdo);

// Filtros e Busca
$search = $_GET['q'] ?? '';
$tab = $_GET['tab'] ?? 'musicas'; // musicas, pastas, artistas

renderAppHeader('Repertório', 'index.php');
?>

<style>
    .bento-card-repertoire {
        background: var(--surface-bright, #ffffff);
        border: 1px solid var(--outline-variant, rgba(224, 226, 231, 0.4));
        box-shadow: 0 1px 3px rgba(0,0,0,0.01);
        transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
    }
    .dark .bento-card-repertoire {
        background: var(--bg-surface, #1A1B1F);
        border: 1px solid rgba(255, 255, 255, 0.05);
    }
    .bento-card-repertoire:hover {
        border-color: var(--worship-blue, #2E7EED);
        box-shadow: 0 8px 30px rgba(0, 0, 0, 0.04);
        transform: translateY(-2.5px);
    }
    .hide-scrollbar::-webkit-scrollbar { display: none; }
    .hide-scrollbar { -ms-overflow-style: none; scrollbar-width: none; }
</style>

<main class="max-w-[1200px] mx-auto px-margin-mobile md:px-margin-desktop py-8 mb-24 font-hanken">
    
    <!-- Header com Menu de Opções -->
    <div class="flex justify-between items-center mb-8">
        <div>
            <h1 class="text-2xl md:text-3xl font-extrabold text-on-background tracking-tight">Repertório</h1>
            <p class="text-xs md:text-sm text-secondary mt-1">Biblioteca musical e gestão de tons do ministério.</p>
        </div>
        
        <!-- Botão de Ações Rápidas -->
        <div class="relative">
            <button onclick="toggleOptionsMenu()" id="options-menu-btn" class="p-3 bg-ghost-gray hover:bg-outline-variant/20 dark:bg-surface-variant/10 active:scale-95 rounded-full transition-all duration-200 border border-outline-variant/30 flex items-center justify-center">
                <i data-lucide="more-vertical" class="w-5 h-5 text-on-background"></i>
            </button>
            
            <!-- Dropdown Menu Premium -->
            <div id="options-menu" class="hidden absolute right-0 mt-3 w-56 bg-white dark:bg-deep-navy border border-outline-variant/30 rounded-2xl shadow-xl z-50 overflow-hidden transform origin-top-right transition-all backdrop-blur-md">
                <a href="sugerir_musica.php" class="flex items-center gap-3 px-5 py-4 hover:bg-ghost-gray/40 dark:hover:bg-surface-variant/10 text-xs font-semibold text-on-background transition-colors">
                    <i data-lucide="send" class="w-4 h-4 text-worship-blue"></i>
                    <span>Sugerir Música</span>
                </a>
                <?php if (isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin'): ?>
                <a href="musica_adicionar.php" class="flex items-center gap-3 px-5 py-4 hover:bg-ghost-gray/40 dark:hover:bg-surface-variant/10 text-xs font-semibold text-on-background transition-colors border-t border-outline-variant/10">
                    <i data-lucide="plus" class="w-4 h-4 text-emerald-500"></i>
                    <span>Adicionar Música</span>
                </a>
                <a href="classificacoes.php" class="flex items-center gap-3 px-5 py-4 hover:bg-ghost-gray/40 dark:hover:bg-surface-variant/10 text-xs font-semibold text-on-background transition-colors border-t border-outline-variant/10">
                    <i data-lucide="tag" class="w-4 h-4 text-altar-gold"></i>
                    <span>Gerenciar TAGs</span>
                </a>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Barra de Busca -->
    <div class="relative w-full mb-8">
        <form method="GET">
            <?php if($tab != 'musicas'): ?>
                <input type="hidden" name="tab" value="<?= $tab ?>">
            <?php endif; ?>
            <div class="absolute left-5 top-1/2 -translate-y-1/2 flex items-center justify-center pointer-events-none">
                <i data-lucide="search" class="w-5 h-5 text-secondary"></i>
            </div>
            <input name="q" value="<?= htmlspecialchars($search) ?>" class="w-full h-14 pl-14 pr-5 bg-ghost-gray/30 border border-outline-variant/30 rounded-xl text-sm focus:outline-none focus:border-worship-blue focus:ring-2 focus:ring-worship-blue/10 dark:bg-surface-variant/5 text-on-background transition-all placeholder:text-secondary/50 shadow-sm" placeholder="Buscar músicas, artistas ou trechos..." type="text"/>
        </form>
    </div>
    
    <!-- Navegação por Abas (Pílulas Modernas) -->
    <div class="flex bg-ghost-gray dark:bg-surface-variant/10 p-1.5 rounded-full border border-outline-variant/30 w-fit mb-8 overflow-x-auto max-w-full gap-1 hide-scrollbar">
        <a href="?tab=musicas" class="px-6 py-2.5 rounded-full font-bold text-xs uppercase tracking-wider transition-all duration-200 <?= $tab == 'musicas' ? 'bg-worship-blue text-white shadow-sm' : 'text-secondary hover:text-worship-blue' ?>">
            Músicas
        </a>
        <a href="?tab=pastas" class="px-6 py-2.5 rounded-full font-bold text-xs uppercase tracking-wider transition-all duration-200 <?= $tab == 'pastas' ? 'bg-worship-blue text-white shadow-sm' : 'text-secondary hover:text-worship-blue' ?>">
            TAGs
        </a>
        <a href="?tab=artistas" class="px-6 py-2.5 rounded-full font-bold text-xs uppercase tracking-wider transition-all duration-200 <?= $tab == 'artistas' ? 'bg-worship-blue text-white shadow-sm' : 'text-secondary hover:text-worship-blue' ?>">
            Artistas
        </a>
        <a href="?tab=tons" class="px-6 py-2.5 rounded-full font-bold text-xs uppercase tracking-wider transition-all duration-200 <?= $tab == 'tons' ? 'bg-worship-blue text-white shadow-sm' : 'text-secondary hover:text-worship-blue' ?>">
            Tons
        </a>
    </div>

    <!-- Conteúdos Dinâmicos -->
    <div class="w-full">
        <?php 
        if (!function_exists('getToneClass')) {
            function getToneClass($tone) {
                return 'tone-C';
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
            <!-- Emblemas de Filtro Ativo -->
            <div class="flex flex-wrap gap-2 mb-6">
                <?php if ($tagId): $currentTag = $musicRepo->getTagById($tagId); if ($currentTag): ?>
                    <div class="inline-flex items-center gap-2 px-4 py-2 rounded-full border border-outline-variant/30 bg-ghost-gray/40 dark:bg-surface-variant/10 text-xs font-bold shadow-sm" style="color: <?= $currentTag['color'] ?>;">
                        <i data-lucide="folder-open" class="w-4 h-4"></i>
                        <span>TAG: <?= htmlspecialchars($currentTag['name']) ?></span>
                        <a href="repertorio.php?tab=musicas" class="ml-2 hover:opacity-70 flex items-center justify-center">
                            <i data-lucide="x" class="w-3.5 h-3.5"></i>
                        </a>
                    </div>
                <?php endif; endif; ?>

                <?php if ($tone): ?>
                    <div class="inline-flex items-center gap-2 px-4 py-2 rounded-full border border-outline-variant/30 bg-ghost-gray/40 dark:bg-surface-variant/10 text-xs font-bold text-altar-gold shadow-sm">
                        <i data-lucide="music" class="w-4 h-4"></i>
                        <span>Tom: <?= htmlspecialchars($tone) ?></span>
                        <a href="repertorio.php?tab=musicas" class="ml-2 hover:opacity-70 flex items-center justify-center">
                            <i data-lucide="x" class="w-3.5 h-3.5"></i>
                        </a>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Bento Grid de Músicas -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <?php 
                foreach ($songs as $song): 
                    $songTags = $musicRepo->getSongTags($song['id']);
                    $borderHex = !empty($songTags) ? $songTags[0]['color'] : '#2E7EED';
                ?>
                    <a href="musica_detalhe.php?id=<?= $song['id'] ?>" class="block group">
                        <div class="bento-card-repertoire rounded-2xl p-6 flex flex-col justify-between gap-5 relative overflow-hidden">
                            
                            <!-- Indicador de Tag Lateral Colorida -->
                            <div class="absolute left-0 top-0 bottom-0 w-[4.5px]" style="background-color: <?= $borderHex ?>;"></div>
                            
                            <div class="flex items-start gap-4">
                                <!-- Badge de Tom Original (Circular Dial) -->
                                <div class="w-11 h-11 rounded-xl bg-ghost-gray dark:bg-surface-variant/20 flex flex-col items-center justify-center text-worship-blue border border-outline-variant/20 shrink-0 shadow-sm font-extrabold text-sm tracking-tight">
                                    <?= $song['tone'] ?: '?' ?>
                                </div>
                                
                                <div class="flex-1 min-w-0">
                                    <h3 class="font-bold text-base text-on-background leading-tight truncate group-hover:text-worship-blue transition-colors">
                                        <?= htmlspecialchars($song['title']) ?>
                                    </h3>
                                    <p class="text-xs text-secondary mt-0.5 truncate">
                                        <?= htmlspecialchars($song['artist']) ?>
                                    </p>
                                    
                                    <!-- Tags Internas do Repertório -->
                                    <?php if (!empty($songTags)): ?>
                                    <div class="flex flex-wrap gap-1.5 mt-3">
                                        <?php foreach ($songTags as $tag): ?>
                                            <span class="text-[9px] uppercase tracking-wider px-2.5 py-0.5 rounded-full font-bold border" style="background: <?= $tag['color'] ?>10; color: <?= $tag['color'] ?>; border-color: <?= $tag['color'] ?>25;">
                                                <?= htmlspecialchars($tag['name']) ?>
                                            </span>
                                        <?php endforeach; ?>
                                    </div>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <!-- Disponibilidade de Arquivos de Mídia -->
                            <div class="flex items-center justify-between border-t border-outline-variant/10 pt-4 text-xs font-bold">
                                <div class="flex gap-4">
                                    <?php if ($song['link_cifra']): ?>
                                        <span class="text-worship-blue/70 hover:text-worship-blue flex items-center gap-1 transition-colors">
                                            <i data-lucide="file-text" class="w-3.5 h-3.5"></i> Cifra
                                        </span>
                                    <?php endif; ?>
                                    <?php if ($song['link_video']): ?>
                                        <span class="text-rose-500/70 hover:text-rose-500 flex items-center gap-1 transition-colors">
                                            <i data-lucide="play-circle" class="w-3.5 h-3.5"></i> Vídeo
                                        </span>
                                    <?php endif; ?>
                                    <?php if ($song['link_audio']): ?>
                                        <span class="text-emerald-500/70 hover:text-emerald-500 flex items-center gap-1 transition-colors">
                                            <i data-lucide="headphones" class="w-3.5 h-3.5"></i> Áudio
                                        </span>
                                    <?php endif; ?>
                                </div>
                                <span class="text-secondary/60 group-hover:text-worship-blue transition-colors group-hover:translate-x-[2px] transform duration-200">
                                    <i data-lucide="chevron-right" class="w-4 h-4"></i>
                                </span>
                            </div>

                        </div>
                    </a>
                <?php endforeach; ?>
                
                <?php if(empty($songs)): ?>
                    <div class="col-span-full bg-white dark:bg-deep-navy border border-dashed border-outline-variant/60 rounded-3xl p-16 text-center shadow-sm max-w-lg mx-auto w-full">
                        <div class="w-16 h-16 rounded-full bg-ghost-gray dark:bg-surface-variant/30 flex items-center justify-center mb-4 border border-outline-variant/40 mx-auto">
                            <i data-lucide="music" class="w-8 h-8 text-secondary"></i>
                        </div>
                        <h4 class="font-bold text-lg text-on-background mb-1">Nenhuma música encontrada</h4>
                        <p class="text-xs text-secondary">Altere os termos da pesquisa ou limpe os filtros ativos.</p>
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
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <?php foreach ($tags as $tag): $bgHex = $tag['color'] ?? '#2E7EED'; ?>
                    <a href="repertorio.php?tab=musicas&tag_id=<?= $tag['id'] ?>" class="block group">
                        <div class="bento-card-repertoire rounded-2xl p-6 flex items-center justify-between">
                            <div class="flex items-center space-x-4">
                                <div class="w-12 h-12 rounded-2xl flex items-center justify-center shrink-0 border shadow-sm transition-all" style="background-color: <?= $bgHex ?>12; color: <?= $bgHex ?>; border-color: <?= $bgHex ?>25;">
                                    <i data-lucide="folder" class="w-5 h-5"></i>
                                </div>
                                <div>
                                    <h3 class="font-bold text-base text-on-background leading-tight group-hover:text-worship-blue transition-colors">
                                        <?= htmlspecialchars($tag['name']) ?>
                                    </h3>
                                    <p class="text-xs text-secondary mt-0.5"><?= $tag['count'] ?> música<?= $tag['count'] > 1 ? 's' : '' ?></p>
                                </div>
                            </div>
                            <span class="text-secondary/60 group-hover:text-worship-blue transition-colors group-hover:translate-x-[2px] transform duration-200">
                                <i data-lucide="chevron-right" class="w-4 h-4"></i>
                            </span>
                        </div>
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
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <?php foreach ($artists as $artist): ?>
                    <a href="artista_perfil.php?artist=<?= urlencode($artist['name']) ?>" class="block group">
                        <div class="bento-card-repertoire rounded-2xl p-6 flex items-center justify-between">
                            <div class="flex items-center space-x-4">
                                <div class="w-12 h-12 rounded-2xl bg-ghost-gray dark:bg-surface-variant/20 border border-outline-variant/30 flex items-center justify-center text-worship-blue shrink-0 shadow-sm">
                                    <i data-lucide="user" class="w-5 h-5"></i>
                                </div>
                                <div>
                                    <h3 class="font-bold text-base text-on-background leading-tight group-hover:text-worship-blue transition-colors">
                                        <?= htmlspecialchars($artist['name']) ?>
                                    </h3>
                                    <p class="text-xs text-secondary mt-0.5"><?= $artist['count'] ?> música<?= $artist['count'] > 1 ? 's' : '' ?></p>
                                </div>
                            </div>
                            <span class="text-secondary/60 group-hover:text-worship-blue transition-colors group-hover:translate-x-[2px] transform duration-200">
                                <i data-lucide="chevron-right" class="w-4 h-4"></i>
                            </span>
                        </div>
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
            <div class="grid grid-cols-2 md:grid-cols-4 gap-6">
                <?php foreach ($tones as $toneItem): ?>
                    <a href="repertorio.php?tab=musicas&tone=<?= urlencode($toneItem['name']) ?>" class="block group">
                        <div class="bento-card-repertoire rounded-2xl p-6 flex flex-col items-center justify-center text-center relative overflow-hidden">
                            <div class="w-14 h-14 rounded-full bg-ghost-gray dark:bg-surface-variant/20 border border-outline-variant/30 flex items-center justify-center text-worship-blue mb-4 shadow-sm group-hover:scale-105 transition-all">
                                <span class="font-extrabold text-lg tracking-tight"><?= htmlspecialchars($toneItem['name']) ?></span>
                            </div>
                            <h4 class="font-bold text-sm text-on-background leading-tight"><?= htmlspecialchars($toneItem['name']) ?></h4>
                            <p class="text-xs text-secondary mt-1"><?= $toneItem['count'] ?> música<?= $toneItem['count'] > 1 ? 's' : '' ?></p>
                        </div>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

    </div>
</main>

<script>
    function toggleOptionsMenu() {
        const menu = document.getElementById('options-menu');
        menu.classList.toggle('hidden');
    }
    
    // Fechar menu ao clicar fora
    document.addEventListener('click', function(event) {
        const menu = document.getElementById('options-menu');
        const btn = document.getElementById('options-menu-btn');
        if (menu && btn && !btn.contains(event.target) && !menu.contains(event.target)) {
            menu.classList.add('hidden');
        }
    });
</script>

<?php renderAppFooter(); ?>
