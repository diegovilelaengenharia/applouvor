<?php 
$title = "Repertório"; 
$activeNav = "repertorio";
require __DIR__ . '/../layouts/head.php'; 
require __DIR__ . '/../layouts/top-app-bar.php'; 
?>

<!-- ========================================================================= -->
<!-- ATENÇÃO DIEGO: COLE AQUI DENTRO DO MAIN O CONTEÚDO HTML DA TELA 07 STITCH -->
<!-- ========================================================================= -->
<main class="w-full max-w-lg mx-auto flex-grow px-4 pb-24">
    <?php require __DIR__ . '/../layouts/flash.php'; ?>
    
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-2xl font-bold text-on-surface">Repertório</h1>
        <?php if (($_SESSION['user_role'] ?? '') === 'admin'): ?>
            <a href="/musicas/nova" class="btn-primary px-4 py-2 text-sm font-bold flex items-center gap-1 shadow-sm">
                <span class="material-symbols-outlined text-[18px]">add</span> Música
            </a>
        <?php endif; ?>
    </div>

    <!-- Barra de busca rudimentar -->
    <form action="/repertorio" method="GET" class="mb-6">
        <div class="relative">
            <span class="material-symbols-outlined absolute left-4 top-1/2 -translate-y-1/2 text-on-surface-variant">search</span>
            <input type="text" name="q" value="<?= htmlspecialchars($_GET['q'] ?? '') ?>" placeholder="Buscar música ou artista..." 
                   class="w-full bg-surface-container-high border border-slate-200 rounded-full pl-12 pr-4 py-3 text-sm text-on-surface input-glow">
        </div>
    </form>

    <div class="space-y-3">
        <?php if (empty($songs)): ?>
            <div class="text-center p-8 text-on-surface-variant bg-surface-container-low rounded-xl">
                Nenhuma música encontrada.
            </div>
        <?php else: ?>
            <?php foreach ($songs as $song): ?>
                <a href="/musicas/<?= $song['id'] ?>" class="block pib-card p-4 hover:shadow-md transition-shadow flex justify-between items-center">
                    <div>
                        <h3 class="font-bold text-on-surface text-base"><?= htmlspecialchars($song['title']) ?></h3>
                        <p class="text-sm text-on-surface-variant"><?= htmlspecialchars($song['artist'] ?? 'Artista Desconhecido') ?></p>
                    </div>
                    <?php if ($song['tone']): ?>
                        <div class="bg-primary/10 text-primary px-3 py-1 rounded-full text-xs font-bold">
                            <?= htmlspecialchars($song['tone']) ?>
                        </div>
                    <?php endif; ?>
                </a>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

</main>

<?php require __DIR__ . '/../layouts/bottom-nav.php'; ?>
