<?php 
$title = "Música"; 
$activeNav = "repertorio";
require __DIR__ . '/../layouts/head.php'; 
require __DIR__ . '/../layouts/top-app-bar.php'; 
?>

<!-- ========================================================================= -->
<!-- ATENÇÃO DIEGO: COLE AQUI DENTRO DO MAIN O CONTEÚDO HTML DA TELA 08 STITCH -->
<!-- Substitua as variáveis estáticas pelas variáveis PHP ex: <?= $song['title'] ?> -->
<!-- ========================================================================= -->
<main class="w-full max-w-lg mx-auto flex-grow px-4 pb-24">
    <?php require __DIR__ . '/../layouts/flash.php'; ?>

    <div class="mb-6">
        <a href="/repertorio" class="text-primary text-sm font-bold flex items-center gap-1 mb-4">
            <span class="material-symbols-outlined text-[18px]">arrow_back</span> Voltar
        </a>
        <h1 class="text-3xl font-bold text-on-surface"><?= htmlspecialchars($song['title']) ?></h1>
        <p class="text-on-surface-variant text-lg mt-1"><?= htmlspecialchars($song['artist'] ?? 'Artista Desconhecido') ?></p>
    </div>

    <?php if (($_SESSION['user_role'] ?? '') === 'admin'): ?>
        <div class="flex gap-2 mb-6">
            <a href="/musicas/<?= $song['id'] ?>/editar" class="btn-outline px-4 py-2 text-xs font-bold rounded-full">Editar Dados</a>
            <!-- Form de deletar (protegido CSRF) -->
            <form action="/musicas/<?= $song['id'] ?>/deletar" method="POST" onsubmit="return confirm('Tem certeza que deseja excluir esta música?')">
                <?= csrf_field() ?>
                <button type="submit" class="btn-outline px-4 py-2 text-xs font-bold rounded-full text-red-600 border-red-200 hover:bg-red-50">Excluir</button>
            </form>
        </div>
    <?php endif; ?>

    <div class="grid grid-cols-3 gap-3 mb-8">
        <div class="bg-surface-container border border-slate-100 p-4 rounded-2xl flex flex-col items-center justify-center text-center">
            <span class="text-xs text-on-surface-variant uppercase tracking-wider font-bold mb-1">Tom</span>
            <span class="text-xl font-bold text-primary"><?= htmlspecialchars($song['tone'] ?? '--') ?></span>
        </div>
        <div class="bg-surface-container border border-slate-100 p-4 rounded-2xl flex flex-col items-center justify-center text-center">
            <span class="text-xs text-on-surface-variant uppercase tracking-wider font-bold mb-1">BPM</span>
            <span class="text-xl font-bold text-on-surface"><?= htmlspecialchars($song['bpm'] ?? '--') ?></span>
        </div>
        <div class="bg-surface-container border border-slate-100 p-4 rounded-2xl flex flex-col items-center justify-center text-center">
            <span class="text-xs text-on-surface-variant uppercase tracking-wider font-bold mb-1">Tempo</span>
            <span class="text-xl font-bold text-on-surface"><?= htmlspecialchars($song['duration'] ?? '--') ?></span>
        </div>
    </div>

    <div class="space-y-3">
        <!-- Modo Palco -->
        <a href="/musicas/<?= $song['id'] ?>/cifra" class="flex items-center gap-4 p-4 bg-primary text-on-primary rounded-2xl hover:bg-primary/90 transition-colors shadow-sm">
            <div class="w-10 h-10 bg-white/20 rounded-full flex items-center justify-center">
                <span class="material-symbols-outlined">queue_music</span>
            </div>
            <div class="flex-grow">
                <h4 class="font-bold">Modo Palco (Cifra)</h4>
                <p class="text-xs text-white/80">Abrir tela de apresentação</p>
            </div>
            <span class="material-symbols-outlined">chevron_right</span>
        </a>

        <!-- Links Externos -->
        <?php if ($song['link_audio'] || $song['link_video'] || $song['link_cifra']): ?>
            <h3 class="text-sm font-bold text-on-surface-variant uppercase tracking-wider mt-8 mb-4">Links de Estudo</h3>
            
            <?php if ($song['link_audio']): ?>
                <a href="<?= htmlspecialchars($song['link_audio']) ?>" target="_blank" class="flex items-center gap-4 p-4 pib-card hover:shadow-md transition-shadow">
                    <span class="material-symbols-outlined text-primary">headphones</span>
                    <span class="font-bold text-on-surface text-sm">Áudio Original</span>
                    <span class="material-symbols-outlined text-on-surface-variant ml-auto">open_in_new</span>
                </a>
            <?php endif; ?>

            <?php if ($song['link_video']): ?>
                <a href="<?= htmlspecialchars($song['link_video']) ?>" target="_blank" class="flex items-center gap-4 p-4 pib-card hover:shadow-md transition-shadow">
                    <span class="material-symbols-outlined text-red-600">play_circle</span>
                    <span class="font-bold text-on-surface text-sm">Vídeo (YouTube)</span>
                    <span class="material-symbols-outlined text-on-surface-variant ml-auto">open_in_new</span>
                </a>
            <?php endif; ?>

            <?php if ($song['link_cifra']): ?>
                <a href="<?= htmlspecialchars($song['link_cifra']) ?>" target="_blank" class="flex items-center gap-4 p-4 pib-card hover:shadow-md transition-shadow">
                    <span class="material-symbols-outlined text-orange-500">library_music</span>
                    <span class="font-bold text-on-surface text-sm">Cifra Club</span>
                    <span class="material-symbols-outlined text-on-surface-variant ml-auto">open_in_new</span>
                </a>
            <?php endif; ?>
        <?php endif; ?>
    </div>

</main>

<?php require __DIR__ . '/../layouts/bottom-nav.php'; ?>
