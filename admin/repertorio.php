<?php
// admin/repertorio.php
require_once '../src/helpers/auth.php';
checkLogin();
require_once '../src/config/db.php';
require_once '../src/layout/layout.php';
require_once 'init_db_suggestions.php';
require_once '../src/classes/MusicRepository.php';

$musicRepo = new \App\Repositories\MusicRepository($pdo);

// Filtros e Busca
$search = $_GET['q'] ?? '';
$tab = $_GET['tab'] ?? 'musicas'; // musicas, pastas, artistas

renderAppHeader('Repertório', 'index.php');
?>

<main class="max-w-[1200px] mx-auto px-margin-mobile md:px-margin-desktop py-8 mb-24 animate-fade-in">
    <!-- Header com Menu de Opções -->
    <div class="flex justify-between items-center mb-8 reveal-item reveal-stagger-1">
        <div>
            <h1 class="font-display-lg-mobile md:font-display-lg text-display-lg-mobile md:text-display-lg text-on-surface font-bold tracking-tight">Repertório</h1>
            <p class="text-body-md text-on-surface-variant/80 mt-1">Biblioteca musical e gestão de tons do ministério</p>
        </div>
        
        <!-- Botão de Ações Rápidas -->
        <div class="relative">
            <button onclick="toggleOptionsMenu()" id="options-menu-btn" class="p-3 bg-surface-container hover:bg-surface-container-high active:scale-95 rounded-full transition-all duration-200 border border-surface-container-highest shadow-sm flex items-center justify-center interactive-scale">
                <span class="material-symbols-outlined text-[24px]">more_vert</span>
            </button>
            
            <!-- Dropdown Menu -->
            <div id="options-menu" class="hidden absolute right-0 mt-3 w-56 bg-surface-container-lowest border border-surface-container-highest rounded-3xl shadow-xl z-50 overflow-hidden transform origin-top-right transition-all">
                <a href="sugerir_musica.php" class="flex items-center gap-3 px-5 py-4 hover:bg-surface-container-low text-body-md text-on-surface transition-colors">
                    <span class="material-symbols-outlined text-[20px] text-worship-blue">send</span>
                    <span>Sugerir Música</span>
                </a>
                <?php if (isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin'): ?>
                <a href="musica_adicionar.php" class="flex items-center gap-3 px-5 py-4 hover:bg-surface-container-low text-body-md text-on-surface transition-colors border-t border-surface-container-highest">
                    <span class="material-symbols-outlined text-[20px] text-emerald-500">add</span>
                    <span>Adicionar Música</span>
                </a>
                <a href="classificacoes.php" class="flex items-center gap-3 px-5 py-4 hover:bg-surface-container-low text-body-md text-on-surface transition-colors border-t border-surface-container-highest">
                    <span class="material-symbols-outlined text-[20px] text-altar-gold">label</span>
                    <span>Gerenciar TAGs</span>
                </a>
                <?php endif; ?>
            </div>
        </div>
    </div>

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

    <!-- Barra de Busca -->
    <div class="relative w-full mb-8 reveal-item reveal-stagger-2">
        <form method="GET">
            <?php if($tab != 'musicas'): ?>
                <input type="hidden" name="tab" value="<?= $tab ?>">
            <?php endif; ?>
            <span class="material-symbols-outlined absolute left-5 top-1/2 -translate-y-1/2 text-outline">search</span>
            <input name="q" value="<?= htmlspecialchars($search) ?>" class="w-full h-14 pl-14 pr-5 bg-surface-container border border-surface-container-highest rounded-2xl text-body-md font-body-md focus:outline-none focus:border-worship-blue focus:ring-2 focus:ring-worship-blue/10 transition-all placeholder-outline/70 shadow-sm" placeholder="Buscar músicas, artistas ou trechos..." type="text"/>
        </form>
    </div>
    
    <!-- Navegação por Abas (Pílulas Modernas) -->
    <div class="flex space-x-3 mb-8 overflow-x-auto hide-scrollbar pb-2 reveal-item reveal-stagger-2">
        <a href="?tab=musicas" class="px-5 py-3 rounded-full whitespace-nowrap text-body-md font-bold transition-all interactive-scale <?= $tab == 'musicas' ? 'bg-worship-blue text-white shadow-md shadow-worship-blue/10 scale-102' : 'bg-surface-container text-on-surface-variant hover:bg-surface-container-high' ?>">
            Músicas
        </a>
        <a href="?tab=pastas" class="px-5 py-3 rounded-full whitespace-nowrap text-body-md font-bold transition-all interactive-scale <?= $tab == 'pastas' ? 'bg-worship-blue text-white shadow-md shadow-worship-blue/10 scale-102' : 'bg-surface-container text-on-surface-variant hover:bg-surface-container-high' ?>">
            TAGs
        </a>
        <a href="?tab=artistas" class="px-5 py-3 rounded-full whitespace-nowrap text-body-md font-bold transition-all interactive-scale <?= $tab == 'artistas' ? 'bg-worship-blue text-white shadow-md shadow-worship-blue/10 scale-102' : 'bg-surface-container text-on-surface-variant hover:bg-surface-container-high' ?>">
            Artistas
        </a>
        <a href="?tab=tons" class="px-5 py-3 rounded-full whitespace-nowrap text-body-md font-bold transition-all interactive-scale <?= $tab == 'tons' ? 'bg-worship-blue text-white shadow-md shadow-worship-blue/10 scale-102' : 'bg-surface-container text-on-surface-variant hover:bg-surface-container-high' ?>">
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
            <div class="flex flex-wrap gap-2 mb-4">
                <?php if ($tagId): $currentTag = $musicRepo->getTagById($tagId); if ($currentTag): ?>
                    <div class="inline-flex items-center gap-2 px-4 py-2 rounded-full border shadow-sm transition-all text-sm font-bold bg-surface-container border-surface-container-highest" style="color: <?= $currentTag['color'] ?>;">
                        <span class="material-symbols-outlined text-[18px]">folder_open</span>
                        <span>Pasta: <?= htmlspecialchars($currentTag['name']) ?></span>
                        <a href="repertorio.php?tab=musicas" class="ml-2 hover:opacity-70 flex items-center justify-center"><span class="material-symbols-outlined text-[16px]">close</span></a>
                    </div>
                <?php endif; endif; ?>

                <?php if ($tone): ?>
                    <div class="inline-flex items-center gap-2 px-4 py-2 rounded-full border border-surface-container-highest bg-surface-container shadow-sm text-altar-gold text-sm font-bold">
                        <span class="material-symbols-outlined text-[18px]">music_note</span>
                        <span>Tom: <?= htmlspecialchars($tone) ?></span>
                        <a href="repertorio.php?tab=musicas" class="ml-2 hover:opacity-70 flex items-center justify-center"><span class="material-symbols-outlined text-[16px]">close</span></a>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Bento Grid de Músicas -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <?php 
                $songIndex = 1;
                foreach ($songs as $song): 
                    $songTags = $musicRepo->getSongTags($song['id']);
                    $borderHex = !empty($songTags) ? $songTags[0]['color'] : '#2E7EED';
                    $staggerClass = 'reveal-stagger-' . min($songIndex, 4);
                    $songIndex++;
                ?>
                    <a href="musica_detalhe.php?id=<?= $song['id'] ?>" class="block group <?= $staggerClass ?> reveal-item interactive-scale">
                        <div class="bg-surface-container-lowest border border-surface-container-highest rounded-3xl p-6 flex flex-col justify-between hover:border-worship-blue/40 hover:shadow-md hover:-translate-y-0.5 active:scale-[0.99] transition-all duration-300 gap-5 relative overflow-hidden">
                            
                            <!-- Indicador de Tag Lateral -->
                            <div class="absolute left-0 top-0 bottom-0 w-[4px]" style="background-color: <?= $borderHex ?>;"></div>
                            
                            <div class="flex items-start gap-4">
                                <!-- Badge de Tom Original -->
                                <div class="w-12 h-12 rounded-full bg-surface-container flex flex-col items-center justify-center text-worship-blue border border-surface-container-highest shrink-0 shadow-sm font-bold text-sm tracking-tight">
                                    <?= $song['tone'] ?: '?' ?>
                                </div>
                                <div class="flex-1 min-w-0">
                                    <h3 class="font-body-lg text-body-lg text-on-surface font-bold leading-tight truncate group-hover:text-worship-blue transition-colors"><?= htmlspecialchars($song['title']) ?></h3>
                                    <p class="font-body-md text-body-md text-on-surface-variant text-sm mt-0.5 truncate"><?= htmlspecialchars($song['artist']) ?></p>
                                    
                                    <!-- Tags Internas -->
                                    <?php if (!empty($songTags)): ?>
                                    <div class="flex flex-wrap gap-1.5 mt-3">
                                        <?php foreach ($songTags as $tag): ?>
                                            <span class="font-label-sm text-[10px] uppercase tracking-wider px-2.5 py-1 rounded-full font-bold shadow-sm" style="background: <?= $tag['color'] ?>12; color: <?= $tag['color'] ?>; border: 1px solid <?= $tag['color'] ?>25;">
                                                <?= htmlspecialchars($tag['name']) ?>
                                            </span>
                                        <?php endforeach; ?>
                                    </div>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <!-- Mídias e Ação -->
                            <div class="flex items-center justify-between border-t border-surface-container-highest pt-4">
                                <div class="flex gap-4">
                                    <?php if ($song['link_cifra']): ?>
                                        <span class="text-worship-blue/70 hover:text-worship-blue flex items-center gap-1 text-xs font-bold transition-colors">
                                            <span class="material-symbols-outlined text-[16px]">description</span> Cifra
                                        </span>
                                    <?php endif; ?>
                                    <?php if ($song['link_video']): ?>
                                        <span class="text-error/70 hover:text-error flex items-center gap-1 text-xs font-bold transition-colors">
                                            <span class="material-symbols-outlined text-[16px]">play_circle</span> Vídeo
                                        </span>
                                    <?php endif; ?>
                                    <?php if ($song['link_audio']): ?>
                                        <span class="text-emerald-500/70 hover:text-emerald-500 flex items-center gap-1 text-xs font-bold transition-colors">
                                            <span class="material-symbols-outlined text-[16px]">headphones</span> Áudio
                                        </span>
                                    <?php endif; ?>
                                </div>
                                <span class="text-outline/70 group-hover:text-worship-blue transition-colors">
                                    <span class="material-symbols-outlined">chevron_right</span>
                                </span>
                            </div>

                        </div>
                    </a>
                <?php endforeach; ?>
                
                <?php if(empty($songs)): ?>
                    <div class="col-span-full bg-surface-container-lowest border border-dashed border-surface-container-highest rounded-3xl p-12 text-center mt-6 shadow-sm">
                        <span class="material-symbols-outlined text-5xl text-outline mb-4">music_off</span>
                        <h4 class="font-headline-md text-headline-md text-on-surface mb-2 font-bold">Sua biblioteca está vazia</h4>
                        <p class="font-body-md text-body-md text-on-surface-variant/80">Nenhuma música encontrada no repertório.</p>
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
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 animate-fade-in">
                <?php foreach ($tags as $tag): $bgHex = $tag['color'] ?? '#2E7EED'; ?>
                    <a href="repertorio.php?tab=musicas&tag_id=<?= $tag['id'] ?>" class="bg-surface-container-lowest border border-surface-container-highest rounded-3xl p-6 flex items-center justify-between hover:border-worship-blue/40 hover:shadow-md active:scale-[0.99] transition-all duration-300">
                        <div class="flex items-center space-x-4">
                            <div class="w-12 h-12 rounded-2xl flex items-center justify-center shrink-0 shadow-sm" style="background-color: <?= $bgHex ?>15; color: <?= $bgHex ?>; border: 1px solid <?= $bgHex ?>25;">
                                <span class="material-symbols-outlined text-[24px]">folder</span>
                            </div>
                            <div>
                                <h3 class="font-body-lg text-body-lg text-on-surface font-bold leading-tight"><?= htmlspecialchars($tag['name']) ?></h3>
                                <p class="font-body-md text-body-md text-on-surface-variant text-sm mt-0.5"><?= $tag['count'] ?> música<?= $tag['count'] > 1 ? 's' : '' ?></p>
                            </div>
                        </div>
                        <span class="text-outline/70"><span class="material-symbols-outlined">chevron_right</span></span>
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
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 animate-fade-in">
                <?php foreach ($artists as $artist): ?>
                    <a href="repertorio.php?tab=musicas&q=<?= urlencode($artist['name']) ?>" class="bg-surface-container-lowest border border-surface-container-highest rounded-3xl p-6 flex items-center justify-between hover:border-worship-blue/40 hover:shadow-md active:scale-[0.99] transition-all duration-300">
                        <div class="flex items-center space-x-4">
                            <div class="w-12 h-12 rounded-2xl bg-surface-container border border-surface-container-highest flex items-center justify-center text-worship-blue shrink-0 shadow-sm">
                                <span class="material-symbols-outlined text-[24px]">person</span>
                            </div>
                            <div>
                                <h3 class="font-body-lg text-body-lg text-on-surface font-bold leading-tight"><?= htmlspecialchars($artist['name']) ?></h3>
                                <p class="font-body-md text-body-md text-on-surface-variant text-sm mt-0.5"><?= $artist['count'] ?> música<?= $artist['count'] > 1 ? 's' : '' ?></p>
                            </div>
                        </div>
                        <span class="text-outline/70"><span class="material-symbols-outlined">chevron_right</span></span>
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
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4 animate-fade-in">
                <?php foreach ($tones as $toneItem): ?>
                    <a href="repertorio.php?tab=musicas&tone=<?= urlencode($toneItem['name']) ?>" class="bg-surface-container-lowest border border-surface-container-highest rounded-3xl p-6 flex flex-col items-center justify-center hover:border-worship-blue/40 hover:shadow-md active:scale-[0.98] transition-all duration-300 text-center relative overflow-hidden">
                        <div class="w-14 h-14 rounded-full bg-surface-container border border-surface-container-highest flex items-center justify-center text-worship-blue mb-4 shadow-sm">
                            <span class="font-bold text-xl tracking-tight"><?= htmlspecialchars($toneItem['name']) ?></span>
                        </div>
                        <p class="font-body-md text-body-md text-on-surface font-bold leading-tight"><?= htmlspecialchars($toneItem['name']) ?></p>
                        <p class="text-xs text-on-surface-variant mt-1"><?= $toneItem['count'] ?> música<?= $toneItem['count'] > 1 ? 's' : '' ?></p>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

    </div>
</main>

<style>
    .hide-scrollbar::-webkit-scrollbar { display: none; }
    .hide-scrollbar { -ms-overflow-style: none; scrollbar-width: none; }
</style>

<?php renderAppFooter(); ?>

