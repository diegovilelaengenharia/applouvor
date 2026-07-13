<?php
$title     = 'Setlist';
$bodyClass = '';
require __DIR__ . '/../layouts/head.php';

$songs    = $schedule['songs'] ?? [];
$dayNames = ['Dom','Seg','Ter','Qua','Qui','Sex','Sáb'];
$date     = new DateTime($schedule['event_date']);
$dayLabel = $dayNames[(int)$date->format('w')] . ' ' . $date->format('d/m') . ' · ' . ($schedule['event_time'] ? substr($schedule['event_time'], 0, 5) : '');
?>

<header class="sticky top-0 z-10 w-full max-w-lg mx-auto bg-surface border-b border-slate-100 dark:border-slate-800 flex items-center gap-3 px-4 py-3.5">
    <a href="/escalas/<?= (int)$schedule['id'] ?>"
       class="text-on-surface-variant hover:text-primary transition-colors">
        <span class="material-symbols-outlined text-[22px]">arrow_back_ios_new</span>
    </a>
    <h1 class="text-lg font-bold text-on-surface flex-1">Compartilhar Setlist</h1>
</header>

<main class="w-full max-w-lg mx-auto flex-grow px-4 pt-5 pb-10 flex flex-col gap-4">

    <!-- Header card -->
    <div class="pib-card p-5 bg-gradient-to-br from-primary/5 to-transparent">
        <div class="flex items-center gap-3 mb-4">
            <div class="w-11 h-11 rounded-2xl bg-primary/10 flex items-center justify-center">
                <span class="material-symbols-outlined text-[22px] text-primary">church</span>
            </div>
            <div>
                <p class="text-xs text-on-surface-variant">PIB Oliveira</p>
                <p class="text-sm font-bold text-on-surface"><?= htmlspecialchars($schedule['event_type'] ?? 'Culto') ?></p>
                <p class="text-xs text-on-surface-variant"><?= $dayLabel ?></p>
            </div>
        </div>

        <!-- Songs list -->
        <?php if ($songs): ?>
        <div class="space-y-2 mb-4">
            <?php foreach ($songs as $i => $song): ?>
            <div class="flex items-center gap-3 py-2 border-b border-slate-100 dark:border-slate-800 last:border-0">
                <span class="text-xs tabular-nums text-on-surface-variant w-5"><?= $i + 1 ?></span>
                <div class="flex-1 min-w-0">
                    <p class="text-sm font-medium text-on-surface truncate"><?= htmlspecialchars($song['title']) ?></p>
                    <p class="text-xs text-on-surface-variant"><?= htmlspecialchars($song['artist'] ?? '') ?></p>
                </div>
                <?php if ($song['tone']): ?>
                <span class="text-xs bg-primary/10 text-primary px-2 py-0.5 rounded-lg font-medium flex-shrink-0">
                    <?= htmlspecialchars($song['tone']) ?>
                </span>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <!-- Equipe -->
        <?php if ($participants): ?>
        <div class="flex flex-wrap gap-2">
            <?php foreach ($participants as $p): ?>
            <?php $instr = $p['assigned_instrument'] ?: $p['instrument']; ?>
            <span class="flex items-center gap-1.5 text-xs bg-slate-100 dark:bg-slate-800 rounded-full px-3 py-1 text-on-surface">
                <span class="material-symbols-outlined text-[13px] text-on-surface-variant">person</span>
                <?= htmlspecialchars($p['name']) ?>
                <?php if ($instr): ?><span class="text-on-surface-variant">· <?= htmlspecialchars($instr) ?></span><?php endif; ?>
            </span>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>

    <!-- Share options -->
    <div class="pib-card p-0 overflow-hidden divide-y divide-slate-100 dark:divide-slate-800">
        <p class="px-4 pt-3 pb-2 text-xs font-semibold text-on-surface-variant uppercase tracking-wider">Compartilhar via</p>

        <!-- Native share (Web Share API) -->
        <button id="btn-native-share"
                class="w-full flex items-center gap-3 px-4 py-3.5 hover:bg-slate-50 dark:hover:bg-slate-800/50 transition-colors">
            <div class="w-9 h-9 rounded-xl bg-primary/10 flex items-center justify-center flex-shrink-0">
                <span class="material-symbols-outlined text-[18px] text-primary">share</span>
            </div>
            <p class="text-sm font-medium text-on-surface text-left">Compartilhar…</p>
        </button>

        <!-- WhatsApp -->
        <a id="btn-whatsapp" href="#"
           class="flex items-center gap-3 px-4 py-3.5 hover:bg-slate-50 dark:hover:bg-slate-800/50 transition-colors">
            <div class="w-9 h-9 rounded-xl bg-emerald-100 dark:bg-emerald-900/30 flex items-center justify-center flex-shrink-0">
                <span class="material-symbols-outlined text-[18px] text-emerald-600">chat</span>
            </div>
            <p class="text-sm font-medium text-on-surface">WhatsApp</p>
        </a>

        <!-- Copy text -->
        <button id="btn-copy-text"
                class="w-full flex items-center gap-3 px-4 py-3.5 hover:bg-slate-50 dark:hover:bg-slate-800/50 transition-colors">
            <div class="w-9 h-9 rounded-xl bg-slate-100 dark:bg-slate-800 flex items-center justify-center flex-shrink-0">
                <span class="material-symbols-outlined text-[18px] text-on-surface-variant">content_copy</span>
            </div>
            <p class="text-sm font-medium text-on-surface">Copiar texto</p>
        </button>
    </div>

    <!-- Ao Vivo / Ensaio links -->
    <div class="grid grid-cols-2 gap-3">
        <a href="/escalas/<?= (int)$schedule['id'] ?>/ao-vivo"
           class="pib-card p-4 flex items-center gap-3 hover:border-primary/30 transition-all">
            <span class="material-symbols-outlined text-[22px] text-red-500">radio_button_checked</span>
            <p class="text-sm font-medium text-on-surface">Ao Vivo</p>
        </a>
        <a href="/escalas/<?= (int)$schedule['id'] ?>/ensaio"
           class="pib-card p-4 flex items-center gap-3 hover:border-primary/30 transition-all">
            <span class="material-symbols-outlined text-[22px] text-primary">headset</span>
            <p class="text-sm font-medium text-on-surface">Ensaio</p>
        </a>
    </div>

</main>

<script>
(function () {
    const songs = <?= json_encode(array_map(function($s) {
        return ['title' => $s['title'], 'artist' => $s['artist'] ?? '', 'tone' => $s['tone'] ?? '', 'bpm' => $s['bpm'] ?? 0];
    }, $songs)) ?>;

    const eventInfo = "<?= htmlspecialchars(addslashes(($schedule['event_type'] ?? 'Culto') . ' · ' . $dayLabel)) ?>";

    function buildText() {
        let t = "🎵 Setlist — " + eventInfo + "\n\n";
        songs.forEach((s, i) => {
            t += (i + 1) + ". " + s.title;
            if (s.tone) t += " (" + s.tone + ")";
            t += "\n";
        });
        t += "\nPIB Oliveira · Ministério de Louvor";
        return t;
    }

    // Native share
    document.getElementById('btn-native-share').addEventListener('click', async () => {
        if (navigator.share) {
            try {
                await navigator.share({ title: 'Setlist', text: buildText(), url: window.location.href });
            } catch (_) {}
        } else {
            navigator.clipboard.writeText(buildText()).then(() => alert('Setlist copiado!'));
        }
    });

    // WhatsApp
    document.getElementById('btn-whatsapp').href =
        "https://wa.me/?text=" + encodeURIComponent(buildText());

    // Copy text
    document.getElementById('btn-copy-text').addEventListener('click', () => {
        navigator.clipboard.writeText(buildText()).then(() => {
            const btn = document.getElementById('btn-copy-text');
            btn.querySelector('p').textContent = 'Copiado!';
            setTimeout(() => btn.querySelector('p').textContent = 'Copiar texto', 2000);
        });
    });
})();
</script>

<script src="/assets/js/app.js"></script>
</body>
</html>
