<?php
$title     = 'Sugerir Setlist';
$bodyClass = '';
require __DIR__ . '/../layouts/head.php';

$dayNames = ['Dom','Seg','Ter','Qua','Qui','Sex','Sáb'];
$date     = new DateTime($schedule['event_date']);
$dayLabel = $dayNames[(int)$date->format('w')] . ' ' . $date->format('d/m');

// Group by approximate BPM energy
$highBpm = array_filter($suggestions, fn($s) => ($s['bpm'] ?? 0) >= 100);
$midBpm  = array_filter($suggestions, fn($s) => ($s['bpm'] ?? 0) >= 70 && ($s['bpm'] ?? 0) < 100);
$lowBpm  = array_filter($suggestions, fn($s) => ($s['bpm'] ?? 0) < 70 || !$s['bpm']);
?>

<header class="sticky top-0 z-10 w-full max-w-lg mx-auto bg-surface border-b border-slate-100 dark:border-slate-800 flex items-center gap-3 px-4 py-3.5">
    <a href="/escalas/<?= (int)$schedule['id'] ?>"
       class="text-on-surface-variant hover:text-primary transition-colors">
        <span class="material-symbols-outlined text-[22px]">arrow_back_ios_new</span>
    </a>
    <div class="flex-1">
        <h1 class="text-sm font-bold text-on-surface">Sugestão de Setlist</h1>
        <p class="text-xs text-on-surface-variant"><?= htmlspecialchars($schedule['event_type'] ?? '') ?> · <?= $dayLabel ?></p>
    </div>
</header>

<main class="w-full max-w-lg mx-auto flex-grow px-4 pt-5 pb-10">

    <!-- Filter form -->
    <form method="GET" action="" class="pib-card p-4 mb-5 flex flex-wrap gap-3 items-end">
        <div class="flex-1 min-w-[100px]">
            <label class="block text-xs text-on-surface-variant mb-1">N° de músicas</label>
            <select name="qty" class="w-full input-glow text-sm py-2 rounded-xl">
                <?php foreach ([3,4,5,6,7,8] as $n): ?>
                <option value="<?= $n ?>" <?= $qty == $n ? 'selected' : '' ?>><?= $n ?> músicas</option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="flex-1 min-w-[130px]">
            <label class="block text-xs text-on-surface-variant mb-1">Evitar últimas</label>
            <select name="semanas" class="w-full input-glow text-sm py-2 rounded-xl">
                <?php foreach ([2 => '2 semanas', 4 => '4 semanas', 8 => '8 semanas'] as $v => $l): ?>
                <option value="<?= $v ?>" <?= $weeks == $v ? 'selected' : '' ?>><?= $l ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <button type="submit" class="btn-primary px-5 py-2 text-sm flex items-center gap-2">
            <span class="material-symbols-outlined text-[16px]">refresh</span>
            Gerar
        </button>
    </form>

    <?php if (!empty($suggestions)): ?>

    <!-- Energy indicator -->
    <div class="flex items-center gap-2 mb-3">
        <p class="text-xs font-semibold text-on-surface-variant uppercase tracking-wider">Fluxo de Energia</p>
        <div class="flex-1 h-1.5 rounded-full bg-slate-100 dark:bg-slate-800 overflow-hidden">
            <div class="h-full bg-gradient-to-r from-primary/40 via-primary to-primary/60 rounded-full"></div>
        </div>
    </div>

    <!-- Save form with song checkboxes -->
    <form method="POST" action="/escalas/<?= (int)$schedule['id'] ?>/setlist-sugerida/salvar">
        <?= csrf_field() ?>

        <div class="pib-card p-0 overflow-hidden mb-4">
            <div class="px-4 py-2.5 border-b border-slate-100 dark:border-slate-800 flex items-center justify-between">
                <p class="text-sm font-semibold text-on-surface">Músicas Sugeridas</p>
                <label class="flex items-center gap-1.5 text-xs text-on-surface-variant cursor-pointer">
                    <input type="checkbox" id="select-all" checked class="rounded">
                    Todas
                </label>
            </div>

            <?php foreach ($suggestions as $i => $song): ?>
            <label class="flex items-center gap-3 px-4 py-3.5 hover:bg-slate-50 dark:hover:bg-slate-800/50 cursor-pointer
                          <?= $i < count($suggestions) - 1 ? 'border-b border-slate-100 dark:border-slate-800' : '' ?>">
                <input type="checkbox" name="songs[]" value="<?= (int)$song['id'] ?>" checked
                       class="rounded text-primary flex-shrink-0">
                <div class="flex-1 min-w-0">
                    <p class="text-sm font-medium text-on-surface truncate"><?= htmlspecialchars($song['title']) ?></p>
                    <p class="text-xs text-on-surface-variant">
                        <?= htmlspecialchars($song['artist'] ?? '') ?>
                        <?php if ($song['last_played']): ?>
                        · última: <?= date('d/m', strtotime($song['last_played'])) ?>
                        <?php else: ?>
                        · nunca tocada
                        <?php endif; ?>
                    </p>
                </div>
                <div class="flex gap-2 flex-shrink-0">
                    <?php if ($song['tone']): ?>
                    <span class="text-xs bg-primary/10 text-primary px-2 py-0.5 rounded-lg font-medium"><?= htmlspecialchars($song['tone']) ?></span>
                    <?php endif; ?>
                    <?php if ($song['bpm']): ?>
                    <span class="text-xs font-mono text-on-surface-variant"><?= (int)$song['bpm'] ?></span>
                    <?php endif; ?>
                </div>
            </label>
            <?php endforeach; ?>
        </div>

        <div class="flex gap-3">
            <a href="?qty=<?= $qty ?>&semanas=<?= $weeks ?>"
               class="btn-outline flex-1 py-3 text-center text-sm">
                Gerar outra
            </a>
            <button type="submit" class="btn-primary flex-1 py-3 text-sm flex items-center justify-center gap-2">
                <span class="material-symbols-outlined text-[16px]">save</span>
                Salvar na escala
            </button>
        </div>
    </form>

    <?php else: ?>
    <div class="pib-card p-8 text-center">
        <span class="material-symbols-outlined text-[40px] text-on-surface-variant block mb-3">queue_music</span>
        <p class="text-sm font-medium text-on-surface mb-1">Nenhuma sugestão disponível</p>
        <p class="text-xs text-on-surface-variant">Tente reduzir o período de exclusão ou cadastre mais músicas no repertório.</p>
    </div>
    <?php endif; ?>

</main>

<script>
document.getElementById('select-all')?.addEventListener('change', function () {
    document.querySelectorAll('input[name="songs[]"]').forEach(cb => cb.checked = this.checked);
});
</script>

<script src="/assets/js/app.js"></script>
</body>
</html>
