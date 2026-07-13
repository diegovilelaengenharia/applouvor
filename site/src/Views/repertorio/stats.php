<?php
$title     = 'Estatísticas do Repertório';
$bodyClass = '';
require __DIR__ . '/../layouts/head.php';

$periods = ['30d' => '30 dias', '3m' => '3 meses', 'year' => 'Este ano', 'all' => 'Tudo'];

// Build total for tone distribution %
$toneTotal = array_sum(array_column($toneDistrib, 'vezes'));
?>

<header class="sticky top-0 z-10 w-full max-w-lg mx-auto bg-surface border-b border-slate-100 dark:border-slate-800 flex items-center gap-3 px-4 py-3.5">
    <a href="/repertorio" class="text-on-surface-variant hover:text-primary transition-colors">
        <span class="material-symbols-outlined text-[22px]">arrow_back_ios_new</span>
    </a>
    <h1 class="text-lg font-bold text-on-surface flex-1">Estatísticas</h1>
</header>

<main class="w-full max-w-lg mx-auto flex-grow px-4 pt-4 pb-10 flex flex-col gap-5">

    <!-- Period filter -->
    <div class="flex gap-2 overflow-x-auto no-scrollbar">
        <?php foreach ($periods as $key => $label): ?>
        <a href="?periodo=<?= $key ?>"
           class="flex-shrink-0 px-4 py-2 rounded-xl text-sm font-semibold transition-colors
                  <?= $period === $key
                      ? 'bg-primary text-white'
                      : 'bg-slate-100 dark:bg-slate-800 text-on-surface-variant hover:bg-slate-200 dark:hover:bg-slate-700' ?>">
            <?= $label ?>
        </a>
        <?php endforeach; ?>
    </div>

    <!-- KPIs -->
    <div class="grid grid-cols-2 gap-3">
        <div class="pib-card p-4 reveal-item">
            <p class="text-3xl font-bold text-primary"><?= $totalSongs ?></p>
            <p class="text-xs text-on-surface-variant mt-1">Total no repertório</p>
        </div>
        <div class="pib-card p-4 reveal-item">
            <p class="text-3xl font-bold text-primary"><?= $totalPlayed ?></p>
            <p class="text-xs text-on-surface-variant mt-1">Músicas tocadas no período</p>
        </div>
        <?php if ($topSongs): ?>
        <div class="pib-card p-4 col-span-2 reveal-item">
            <p class="text-xs text-on-surface-variant mb-0.5">Mais tocada no período</p>
            <p class="text-sm font-bold text-on-surface truncate"><?= htmlspecialchars($topSongs[0]['title'] ?? '—') ?></p>
            <p class="text-2xl font-bold text-primary"><?= (int)($topSongs[0]['vezes'] ?? 0) ?>×</p>
        </div>
        <?php endif; ?>
    </div>

    <!-- Top 10 -->
    <?php if ($topSongs): ?>
    <div class="pib-card p-0 overflow-hidden">
        <div class="px-4 pt-3 pb-2 border-b border-slate-100 dark:border-slate-800">
            <p class="text-sm font-semibold text-on-surface">Top <?= count($topSongs) ?> mais tocadas</p>
        </div>
        <?php $maxVezes = (int)($topSongs[0]['vezes'] ?? 1); ?>
        <?php foreach ($topSongs as $i => $song): ?>
        <div class="flex items-center gap-3 px-4 py-3 <?= $i < count($topSongs) - 1 ? 'border-b border-slate-100 dark:border-slate-800' : '' ?> reveal-item">
            <span class="text-xs font-bold tabular-nums text-on-surface-variant w-5 flex-shrink-0">
                <?= $i === 0 ? '🏆' : ($i + 1) ?>
            </span>
            <div class="flex-1 min-w-0">
                <p class="text-sm font-medium text-on-surface truncate"><?= htmlspecialchars($song['title']) ?></p>
                <div class="flex items-center gap-2 mt-1">
                    <?php if ($song['tone']): ?>
                    <span class="text-xs text-on-surface-variant"><?= htmlspecialchars($song['tone']) ?></span>
                    <?php endif; ?>
                    <!-- Bar -->
                    <div class="flex-1 h-1.5 bg-slate-100 dark:bg-slate-800 rounded-full overflow-hidden">
                        <div class="h-full bg-primary rounded-full transition-all"
                             style="width: <?= round(($song['vezes'] / $maxVezes) * 100) ?>%"></div>
                    </div>
                </div>
            </div>
            <span class="text-sm font-bold text-primary tabular-nums flex-shrink-0"><?= (int)$song['vezes'] ?>×</span>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <!-- Tone distribution -->
    <?php if ($toneDistrib && $toneTotal > 0): ?>
    <div class="pib-card p-5">
        <p class="text-sm font-semibold text-on-surface mb-3">Distribuição por Tom</p>
        <div class="space-y-2">
            <?php
            $colors = ['bg-blue-500','bg-emerald-500','bg-violet-500','bg-amber-500','bg-rose-500','bg-teal-500','bg-orange-500','bg-pink-500'];
            foreach ($toneDistrib as $i => $t):
                $pct = round(($t['vezes'] / $toneTotal) * 100);
            ?>
            <div class="flex items-center gap-3 reveal-item">
                <span class="text-xs font-medium text-on-surface w-8 flex-shrink-0"><?= htmlspecialchars($t['tone']) ?></span>
                <div class="flex-1 h-2 bg-slate-100 dark:bg-slate-800 rounded-full overflow-hidden">
                    <div class="h-full <?= $colors[$i % count($colors)] ?> rounded-full" style="width: <?= $pct ?>%"></div>
                </div>
                <span class="text-xs tabular-nums text-on-surface-variant w-8 text-right flex-shrink-0"><?= $pct ?>%</span>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

    <!-- Forgotten songs -->
    <?php if ($forgotten): ?>
    <div class="pib-card p-0 overflow-hidden">
        <div class="px-4 pt-3 pb-2 border-b border-slate-100 dark:border-slate-800 flex items-center gap-2">
            <span class="material-symbols-outlined text-[16px] text-amber-500">history</span>
            <p class="text-sm font-semibold text-on-surface">Músicas Esquecidas</p>
        </div>
        <?php foreach ($forgotten as $i => $song): ?>
        <a href="/musicas/<?= (int)$song['id'] ?>"
           class="flex items-center gap-3 px-4 py-3 hover:bg-slate-50 dark:hover:bg-slate-800/50 transition-colors
                  <?= $i < count($forgotten) - 1 ? 'border-b border-slate-100 dark:border-slate-800' : '' ?> reveal-item">
            <div class="flex-1 min-w-0">
                <p class="text-sm font-medium text-on-surface truncate"><?= htmlspecialchars($song['title']) ?></p>
                <p class="text-xs text-on-surface-variant">
                    <?= $song['last_played']
                        ? 'Última: ' . date('d/m/Y', strtotime($song['last_played']))
                        : 'Nunca tocada' ?>
                </p>
            </div>
            <span class="material-symbols-outlined text-[16px] text-on-surface-variant">chevron_right</span>
        </a>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

</main>

<script src="/assets/js/app.js"></script>
</body>
</html>
