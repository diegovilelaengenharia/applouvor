<?php
$title = "Sugerir Música";
$bodyClass = "";
require __DIR__ . '/../layouts/head.php';

$tons = ['C','C#/Db','D','D#/Eb','E','F','F#/Gb','G','G#/Ab','A','A#/Bb','B'];
?>

<header class="sticky top-0 z-10 w-full max-w-lg mx-auto bg-surface border-b border-slate-100 dark:border-slate-800 flex items-center gap-3 px-4 py-3.5">
    <a href="/sugestoes" class="text-on-surface-variant hover:text-primary transition-colors">
        <span class="material-symbols-outlined text-[22px]">arrow_back_ios_new</span>
    </a>
    <h1 class="text-lg font-bold text-on-surface flex-1">Sugerir Música</h1>
</header>

<main class="w-full max-w-lg mx-auto flex-grow px-4 pt-6 pb-10">
    <?php require __DIR__ . '/../layouts/flash.php'; ?>

    <div class="pib-card p-4 mb-5 flex items-center gap-3">
        <div class="w-10 h-10 rounded-xl bg-primary/10 flex items-center justify-center flex-shrink-0">
            <span class="material-symbols-outlined text-[20px] text-primary">lightbulb</span>
        </div>
        <p class="text-sm text-on-surface">Sua sugestão será avaliada pela liderança antes de entrar no repertório.</p>
    </div>

    <form method="POST" action="/sugestoes/nova" class="space-y-4">
        <?= csrf_field() ?>

        <div>
            <label class="block text-xs font-semibold text-on-surface-variant uppercase tracking-wider mb-1.5">Título da Música *</label>
            <input type="text" name="title" required placeholder="Ex: Yeshua" class="input-glow w-full">
        </div>

        <div>
            <label class="block text-xs font-semibold text-on-surface-variant uppercase tracking-wider mb-1.5">Artista / Ministério</label>
            <input type="text" name="artist" placeholder="Ex: Fernandinho" class="input-glow w-full">
        </div>

        <div>
            <label class="block text-xs font-semibold text-on-surface-variant uppercase tracking-wider mb-1.5">Link (Spotify, YouTube, Cifra Club)</label>
            <input type="url" name="link" placeholder="https://..." class="input-glow w-full">
        </div>

        <div>
            <label class="block text-xs font-semibold text-on-surface-variant uppercase tracking-wider mb-1.5">Tom Sugerido</label>
            <select name="key" class="input-glow w-full">
                <option value="">— Não sei / Qualquer tom —</option>
                <?php foreach ($tons as $t): ?>
                <option value="<?= $t ?>"><?= $t ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div>
            <label class="block text-xs font-semibold text-on-surface-variant uppercase tracking-wider mb-1.5">Por que esta música?</label>
            <textarea name="notes" rows="3"
                      placeholder="Conte por que acha que ela seria boa para o ministério..."
                      class="input-glow w-full resize-none"></textarea>
        </div>

        <div class="pt-2 flex gap-3">
            <a href="/sugestoes" class="btn-outline flex-1 text-center">Cancelar</a>
            <button type="submit" class="btn-primary flex-1">Enviar Sugestão</button>
        </div>
    </form>
</main>

<script src="/assets/js/app.js"></script>
</body>
</html>
