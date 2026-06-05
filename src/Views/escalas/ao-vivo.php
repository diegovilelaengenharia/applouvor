<?php
$title     = 'Ao Vivo';
$bodyClass = 'bg-slate-950';
require __DIR__ . '/../layouts/head.php';

$songs        = $schedule['songs'] ?? [];
$total        = count($songs);
$currentSong  = $songs[$currentIndex] ?? null;
$prevIndex    = $currentIndex - 1;
$nextIndex    = $currentIndex + 1;

$dayNames = ['Dom','Seg','Ter','Qua','Qui','Sex','Sáb'];
$date     = new DateTime($schedule['event_date']);
$dayLabel = $dayNames[(int)$date->format('w')] . ' ' . $date->format('d/m');
?>

<div class="min-h-screen flex flex-col bg-slate-950 text-white">

    <!-- Header dark -->
    <header class="sticky top-0 z-10 w-full max-w-lg mx-auto bg-slate-950/90 backdrop-blur border-b border-white/10 flex items-center gap-3 px-4 py-3.5">
        <a href="/escalas/<?= (int)$schedule['id'] ?>"
           class="text-slate-400 hover:text-white transition-colors">
            <span class="material-symbols-outlined text-[22px]">arrow_back_ios_new</span>
        </a>
        <div class="flex-1">
            <h1 class="text-sm font-bold text-white">Modo Ao Vivo</h1>
            <p class="text-xs text-slate-400"><?= htmlspecialchars($schedule['event_type'] ?? '') ?> · <?= $dayLabel ?></p>
        </div>
        <span class="flex items-center gap-1 text-xs text-emerald-400 font-medium">
            <span class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></span>
            Ao vivo
        </span>
    </header>

    <main class="w-full max-w-lg mx-auto flex-grow flex flex-col px-4 pt-6 pb-10">

        <?php if ($currentSong): ?>
        <!-- Current song card -->
        <div class="flex-shrink-0 bg-gradient-to-br from-primary/20 to-slate-900 rounded-3xl p-6 mb-6 border border-white/10">
            <div class="flex items-start justify-between mb-4">
                <div>
                    <p class="text-xs text-slate-400 uppercase tracking-wider mb-1">
                        <?= $currentIndex + 1 ?> de <?= $total ?>
                    </p>
                    <h2 class="text-2xl font-bold text-white leading-tight">
                        <?= htmlspecialchars($currentSong['title']) ?>
                    </h2>
                    <p class="text-sm text-slate-300 mt-1"><?= htmlspecialchars($currentSong['artist'] ?? '') ?></p>
                </div>
                <?php if ($currentSong['link_cifra']): ?>
                <a href="<?= htmlspecialchars($currentSong['link_cifra']) ?>" target="_blank"
                   class="w-10 h-10 rounded-2xl bg-white/10 flex items-center justify-center hover:bg-white/20 transition-colors flex-shrink-0">
                    <span class="material-symbols-outlined text-[18px] text-white">open_in_new</span>
                </a>
                <?php endif; ?>
            </div>

            <div class="flex gap-3">
                <?php if ($currentSong['tone']): ?>
                <span class="px-3 py-1 rounded-xl bg-primary/30 text-primary text-sm font-semibold">
                    <?= htmlspecialchars($currentSong['tone']) ?>
                </span>
                <?php endif; ?>
                <?php if ($currentSong['bpm']): ?>
                <span class="px-3 py-1 rounded-xl bg-white/10 text-slate-300 text-sm font-mono">
                    <?= (int)$currentSong['bpm'] ?> BPM
                </span>
                <?php endif; ?>
            </div>
        </div>

        <!-- Prev / Next navigation -->
        <div class="flex gap-3 mb-6">
            <?php if ($prevIndex >= 0): ?>
            <a href="?musica=<?= $prevIndex ?>"
               class="flex-1 flex items-center gap-2 bg-white/10 hover:bg-white/15 rounded-2xl px-4 py-3 text-sm text-white transition-colors">
                <span class="material-symbols-outlined text-[20px]">skip_previous</span>
                <span class="truncate"><?= htmlspecialchars($songs[$prevIndex]['title']) ?></span>
            </a>
            <?php else: ?>
            <div class="flex-1 rounded-2xl bg-white/5 px-4 py-3"></div>
            <?php endif; ?>

            <?php if ($nextIndex < $total): ?>
            <a href="?musica=<?= $nextIndex ?>"
               class="flex-1 flex items-center justify-end gap-2 bg-primary/80 hover:bg-primary rounded-2xl px-4 py-3 text-sm text-white transition-colors">
                <span class="truncate text-right"><?= htmlspecialchars($songs[$nextIndex]['title']) ?></span>
                <span class="material-symbols-outlined text-[20px] flex-shrink-0">skip_next</span>
            </a>
            <?php else: ?>
            <div class="flex-1 flex items-center justify-center rounded-2xl bg-white/5 px-4 py-3 text-xs text-slate-500">
                Fim do setlist
            </div>
            <?php endif; ?>
        </div>
        <?php else: ?>
        <div class="flex-grow flex items-center justify-center text-slate-500 text-sm">
            Nenhuma música na escala.
        </div>
        <?php endif; ?>

        <!-- Full setlist -->
        <?php if ($songs): ?>
        <div class="bg-white/5 rounded-2xl overflow-hidden border border-white/10">
            <p class="px-4 pt-3 pb-2 text-xs font-semibold text-slate-400 uppercase tracking-wider border-b border-white/10">
                Setlist completo
            </p>
            <?php foreach ($songs as $i => $song): ?>
            <a href="?musica=<?= $i ?>"
               class="flex items-center gap-3 px-4 py-3 hover:bg-white/5 transition-colors
                      <?= $i === $currentIndex ? 'bg-primary/20 border-l-2 border-primary' : '' ?>
                      <?= $i < count($songs) - 1 ? 'border-b border-white/5' : '' ?>">
                <span class="text-xs tabular-nums text-slate-500 w-5 flex-shrink-0"><?= $i + 1 ?></span>
                <div class="flex-1 min-w-0">
                    <p class="text-sm <?= $i === $currentIndex ? 'text-primary font-bold' : 'text-white' ?> truncate">
                        <?= htmlspecialchars($song['title']) ?>
                    </p>
                    <p class="text-xs text-slate-500"><?= htmlspecialchars($song['tone'] ?? '') ?> <?= $song['bpm'] ? '· ' . (int)$song['bpm'] . ' BPM' : '' ?></p>
                </div>
                <?php if ($i === $currentIndex): ?>
                <span class="material-symbols-outlined text-[16px] text-primary">music_note</span>
                <?php endif; ?>
            </a>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <!-- Metronome link -->
        <?php if ($currentSong && $currentSong['bpm']): ?>
        <a href="/metronomo?escala=<?= (int)$schedule['id'] ?>"
           class="mt-4 flex items-center justify-center gap-2 py-3 rounded-2xl border border-white/10 text-sm text-slate-300 hover:text-white hover:border-white/20 transition-colors">
            <span class="material-symbols-outlined text-[18px]">timer</span>
            Abrir metrônomo (<?= (int)$currentSong['bpm'] ?> BPM)
        </a>
        <?php endif; ?>

    </main>
</div>

<script src="/assets/js/app.js"></script>
</body>
</html>
