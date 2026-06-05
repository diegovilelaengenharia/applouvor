<?php 
$isEdit = isset($song['id']);
$title = $isEdit ? "Editar Música" : "Nova Música"; 
$activeNav = "repertorio";
require __DIR__ . '/../layouts/head.php'; 
require __DIR__ . '/../layouts/top-app-bar.php'; 
?>

<!-- ========================================================================= -->
<!-- ATENÇÃO DIEGO: COLE AQUI DENTRO DO MAIN O CONTEÚDO HTML DA TELA 09 STITCH -->
<!-- ========================================================================= -->
<main class="w-full max-w-lg mx-auto flex-grow px-4 pb-24">
    <?php require __DIR__ . '/../layouts/flash.php'; ?>

    <div class="mb-6">
        <a href="/repertorio<?= $isEdit ? "/{$song['id']}" : '' ?>" class="text-primary text-sm font-bold flex items-center gap-1 mb-4">
            <span class="material-symbols-outlined text-[18px]">arrow_back</span> Cancelar
        </a>
        <h1 class="text-2xl font-bold text-on-surface"><?= $title ?></h1>
    </div>

    <form action="<?= $isEdit ? "/musicas/{$song['id']}/editar" : "/musicas/nova" ?>" method="POST" class="space-y-6">
        <?= csrf_field() ?>

        <div class="pib-card p-6 space-y-4">
            <div>
                <label class="block text-xs font-bold text-on-surface-variant uppercase tracking-wider mb-2 ml-1">Título da Música *</label>
                <input type="text" name="title" value="<?= htmlspecialchars($song['title'] ?? '') ?>" required
                       class="w-full bg-slate-50 border border-slate-200 rounded-[16px] px-4 py-3.5 text-on-surface input-glow">
            </div>

            <div>
                <label class="block text-xs font-bold text-on-surface-variant uppercase tracking-wider mb-2 ml-1">Artista Original</label>
                <input type="text" name="artist" value="<?= htmlspecialchars($song['artist'] ?? '') ?>"
                       class="w-full bg-slate-50 border border-slate-200 rounded-[16px] px-4 py-3.5 text-on-surface input-glow">
            </div>

            <div class="flex gap-4">
                <div class="flex-1">
                    <label class="block text-xs font-bold text-on-surface-variant uppercase tracking-wider mb-2 ml-1">Tom Principal</label>
                    <input type="text" name="tone" value="<?= htmlspecialchars($song['tone'] ?? '') ?>" placeholder="Ex: G, Am"
                           class="w-full bg-slate-50 border border-slate-200 rounded-[16px] px-4 py-3.5 text-on-surface input-glow">
                </div>
                <div class="flex-1">
                    <label class="block text-xs font-bold text-on-surface-variant uppercase tracking-wider mb-2 ml-1">BPM</label>
                    <input type="number" name="bpm" value="<?= htmlspecialchars($song['bpm'] ?? '') ?>" placeholder="Ex: 72"
                           class="w-full bg-slate-50 border border-slate-200 rounded-[16px] px-4 py-3.5 text-on-surface input-glow">
                </div>
            </div>

            <hr class="border-slate-100 my-2">

            <div>
                <label class="block text-xs font-bold text-on-surface-variant uppercase tracking-wider mb-2 ml-1">Link do Cifra Club</label>
                <input type="url" name="link_cifra" value="<?= htmlspecialchars($song['link_cifra'] ?? '') ?>" placeholder="https://cifraclub.com.br/..."
                       class="w-full bg-slate-50 border border-slate-200 rounded-[16px] px-4 py-3.5 text-on-surface input-glow">
            </div>
            
            <div>
                <label class="block text-xs font-bold text-on-surface-variant uppercase tracking-wider mb-2 ml-1">Link do YouTube</label>
                <input type="url" name="link_video" value="<?= htmlspecialchars($song['link_video'] ?? '') ?>" placeholder="https://youtube.com/..."
                       class="w-full bg-slate-50 border border-slate-200 rounded-[16px] px-4 py-3.5 text-on-surface input-glow">
            </div>

            <div>
                <label class="block text-xs font-bold text-on-surface-variant uppercase tracking-wider mb-2 ml-1">Anotações do Ministério</label>
                <textarea name="notes" rows="2" class="w-full bg-slate-50 border border-slate-200 rounded-[16px] px-4 py-3.5 text-on-surface input-glow"><?= htmlspecialchars($song['notes'] ?? '') ?></textarea>
            </div>
        </div>

        <button type="submit" class="btn-primary w-full py-4 text-sm font-bold shadow-sm flex items-center justify-center gap-2 transform active:scale-95">
            <span>Salvar Música</span>
            <span class="material-symbols-outlined text-[20px]">save</span>
        </button>
    </form>
</main>

<?php require __DIR__ . '/../layouts/bottom-nav.php'; ?>
