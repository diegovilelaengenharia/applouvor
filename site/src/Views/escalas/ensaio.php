<?php
$title     = 'Modo Ensaio';
$bodyClass = '';
require __DIR__ . '/../layouts/head.php';

$songs = $schedule['songs'] ?? [];
$dayNames = ['Dom','Seg','Ter','Qua','Qui','Sex','Sáb'];
$date     = new DateTime($schedule['event_date']);
$dayLabel = $dayNames[(int)$date->format('w')] . ' ' . $date->format('d/m');
?>

<header class="sticky top-0 z-10 w-full max-w-lg mx-auto bg-surface border-b border-slate-100 dark:border-slate-800 flex items-center gap-3 px-4 py-3.5">
    <a href="/escalas/<?= (int)$schedule['id'] ?>"
       class="text-on-surface-variant hover:text-primary transition-colors">
        <span class="material-symbols-outlined text-[22px]">arrow_back_ios_new</span>
    </a>
    <div class="flex-1">
        <h1 class="text-sm font-bold text-on-surface">Modo Ensaio</h1>
        <p class="text-xs text-on-surface-variant"><?= htmlspecialchars($schedule['event_type'] ?? '') ?> · <?= $dayLabel ?></p>
    </div>
    <span id="elapsed-timer" class="font-mono text-sm text-primary">00:00</span>
</header>

<!-- Tabs -->
<div class="sticky top-[57px] z-10 w-full max-w-lg mx-auto bg-surface border-b border-slate-100 dark:border-slate-800">
    <div class="flex">
        <?php foreach (['setlist' => 'Setlist', 'metro' => 'Metrônomo', 'cifras' => 'Cifras'] as $tab => $label): ?>
        <button class="tab-btn flex-1 py-3 text-sm font-semibold border-b-2 transition-colors
                       <?= $tab === 'setlist' ? 'border-primary text-primary' : 'border-transparent text-on-surface-variant' ?>"
                data-tab="<?= $tab ?>">
            <?= $label ?>
        </button>
        <?php endforeach; ?>
    </div>
</div>

<main class="w-full max-w-lg mx-auto flex-grow px-4 pt-4 pb-10">

    <!-- Tab: Setlist -->
    <div id="tab-setlist" class="tab-panel">
        <?php if ($songs): ?>
        <div class="pib-card p-0 overflow-hidden divide-y divide-slate-100 dark:divide-slate-800">
            <?php foreach ($songs as $i => $song): ?>
            <div class="song-item flex items-center gap-3 px-4 py-3.5 hover:bg-slate-50 dark:hover:bg-slate-800/50 cursor-pointer transition-colors"
                 data-bpm="<?= (int)($song['bpm'] ?? 72) ?>"
                 data-title="<?= htmlspecialchars($song['title']) ?>"
                 data-cifra="<?= htmlspecialchars($song['link_cifra'] ?? '') ?>">
                <span class="text-xs tabular-nums text-on-surface-variant w-5 flex-shrink-0"><?= $i + 1 ?></span>
                <div class="flex-1 min-w-0">
                    <p class="text-sm font-medium text-on-surface truncate"><?= htmlspecialchars($song['title']) ?></p>
                    <p class="text-xs text-on-surface-variant"><?= htmlspecialchars($song['artist'] ?? '') ?></p>
                </div>
                <div class="flex items-center gap-2 flex-shrink-0">
                    <?php if ($song['tone']): ?>
                    <span class="text-xs bg-primary/10 text-primary px-2 py-0.5 rounded-lg font-medium"><?= htmlspecialchars($song['tone']) ?></span>
                    <?php endif; ?>
                    <?php if ($song['bpm']): ?>
                    <span class="text-xs text-on-surface-variant font-mono"><?= (int)$song['bpm'] ?></span>
                    <?php endif; ?>
                    <span class="material-symbols-outlined text-[16px] text-on-surface-variant">play_circle</span>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <p class="text-center text-xs text-on-surface-variant mt-3">
            Toque em uma música para carregar no metrônomo
        </p>
        <?php else: ?>
        <div class="pib-card p-6 text-center text-on-surface-variant text-sm">
            Nenhuma música na escala.
        </div>
        <?php endif; ?>
    </div>

    <!-- Tab: Metrônomo -->
    <div id="tab-metro" class="tab-panel hidden">
        <div class="pib-card p-6 flex flex-col items-center gap-4">
            <p id="metro-song-name" class="text-xs text-on-surface-variant italic">Selecione uma música no Setlist</p>

            <div id="beat-ring-e"
                 class="w-24 h-24 rounded-full border-4 border-slate-200 dark:border-slate-700 flex items-center justify-center transition-all duration-75"
                 style="border-color: transparent;">
                <div class="text-center">
                    <p id="bpm-display-e" class="text-4xl font-bold text-primary tabular-nums">72</p>
                    <p class="text-xs text-on-surface-variant uppercase tracking-wider mt-1">BPM</p>
                </div>
            </div>

            <div class="flex items-center gap-3">
                <button id="e-minus" class="w-10 h-10 rounded-xl bg-slate-100 dark:bg-slate-800 flex items-center justify-center text-lg font-bold text-on-surface active:scale-95 transition-transform">−</button>
                <button id="e-plus"  class="w-10 h-10 rounded-xl bg-primary flex items-center justify-center text-lg font-bold text-white active:scale-95 transition-transform">+</button>
            </div>

            <!-- Compass -->
            <div class="flex gap-2">
                <?php foreach (['2/4','3/4','4/4','6/8'] as $c): ?>
                <button class="e-compass-btn px-3 py-1.5 rounded-xl text-sm font-semibold border border-slate-200 dark:border-slate-700 text-on-surface-variant"
                        data-compass="<?= $c ?>"><?= $c ?></button>
                <?php endforeach; ?>
            </div>

            <div class="flex gap-3 w-full">
                <button id="e-tap"  class="flex-1 py-3 rounded-2xl border border-slate-200 dark:border-slate-700 text-sm font-semibold text-on-surface active:scale-95">TAP</button>
                <button id="e-play" class="flex-1 py-3 rounded-2xl bg-primary text-white font-bold text-sm flex items-center justify-center gap-2 active:scale-95">
                    <span id="e-play-icon" class="material-symbols-outlined text-[18px]">play_arrow</span>
                    <span id="e-play-label">Iniciar</span>
                </button>
            </div>
        </div>
    </div>

    <!-- Tab: Cifras -->
    <div id="tab-cifras" class="tab-panel hidden">
        <?php if (array_filter(array_column($songs, 'link_cifra'))): ?>
        <div class="pib-card p-0 overflow-hidden divide-y divide-slate-100 dark:divide-slate-800">
            <?php foreach ($songs as $i => $song): if (!$song['link_cifra']) continue; ?>
            <a href="<?= htmlspecialchars($song['link_cifra']) ?>" target="_blank"
               class="flex items-center gap-3 px-4 py-3.5 hover:bg-slate-50 dark:hover:bg-slate-800/50 transition-colors">
                <span class="text-xs tabular-nums text-on-surface-variant w-5"><?= $i + 1 ?></span>
                <div class="flex-1 min-w-0">
                    <p class="text-sm font-medium text-on-surface truncate"><?= htmlspecialchars($song['title']) ?></p>
                    <p class="text-xs text-on-surface-variant">Cifra Club</p>
                </div>
                <span class="material-symbols-outlined text-[18px] text-primary">open_in_new</span>
            </a>
            <?php endforeach; ?>
        </div>
        <?php else: ?>
        <div class="pib-card p-6 text-center text-on-surface-variant text-sm">
            Nenhuma cifra cadastrada para este setlist.
        </div>
        <?php endif; ?>
    </div>

</main>

<script>
(function () {
    // Tabs
    document.querySelectorAll('.tab-btn').forEach(btn => {
        btn.addEventListener('click', function () {
            document.querySelectorAll('.tab-btn').forEach(b => {
                b.classList.remove('border-primary','text-primary');
                b.classList.add('border-transparent','text-on-surface-variant');
            });
            this.classList.add('border-primary','text-primary');
            this.classList.remove('border-transparent','text-on-surface-variant');
            document.querySelectorAll('.tab-panel').forEach(p => p.classList.add('hidden'));
            document.getElementById('tab-' + this.dataset.tab).classList.remove('hidden');
        });
    });

    // Timer
    let seconds = 0;
    const timerEl = document.getElementById('elapsed-timer');
    setInterval(() => {
        seconds++;
        const m = String(Math.floor(seconds / 60)).padStart(2, '0');
        const s = String(seconds % 60).padStart(2, '0');
        timerEl.textContent = m + ':' + s;
    }, 1000);

    // Metronome (same logic as standalone page)
    const AudioCtx = window.AudioContext || window.webkitAudioContext;
    let ctx = null, bpm = 72, compass = 4, beat = 0, timer = null, playing = false, tapTimes = [];
    const ring = document.getElementById('beat-ring-e');
    const disp = document.getElementById('bpm-display-e');

    function setBpm(v) {
        bpm = Math.min(220, Math.max(40, v));
        disp.textContent = bpm;
        if (playing) { clearInterval(timer); beat = 0; tick(); timer = setInterval(tick, 60000 / bpm); }
    }

    function tick() {
        const isAccent = beat % compass === 0;
        ring.style.borderColor = isAccent ? '#2E7EED' : '#64748b';
        setTimeout(() => ring.style.borderColor = 'transparent', 80);
        if (ctx) {
            const osc = ctx.createOscillator(), gain = ctx.createGain();
            osc.connect(gain); gain.connect(ctx.destination);
            osc.frequency.value = isAccent ? 1050 : 800;
            gain.gain.setValueAtTime(isAccent ? 0.5 : 0.25, ctx.currentTime);
            gain.gain.exponentialRampToValueAtTime(0.001, ctx.currentTime + 0.06);
            osc.start(); osc.stop(ctx.currentTime + 0.06);
        }
        beat++;
    }

    function startMetro() {
        if (!ctx) ctx = new AudioCtx();
        if (ctx.state === 'suspended') ctx.resume();
        playing = true; beat = 0;
        document.getElementById('e-play-icon').textContent = 'stop';
        document.getElementById('e-play-label').textContent = 'Parar';
        tick(); timer = setInterval(tick, 60000 / bpm);
    }
    function stopMetro() {
        clearInterval(timer); playing = false; beat = 0;
        ring.style.borderColor = 'transparent';
        document.getElementById('e-play-icon').textContent = 'play_arrow';
        document.getElementById('e-play-label').textContent = 'Iniciar';
    }

    document.getElementById('e-play').addEventListener('click', () => playing ? stopMetro() : startMetro());
    document.getElementById('e-minus').addEventListener('click', () => setBpm(bpm - 1));
    document.getElementById('e-plus').addEventListener('click',  () => setBpm(bpm + 1));

    document.querySelectorAll('.e-compass-btn').forEach(btn => {
        btn.addEventListener('click', function () {
            document.querySelectorAll('.e-compass-btn').forEach(b => {
                b.classList.remove('bg-primary','text-white','border-primary');
                b.classList.add('text-on-surface-variant');
            });
            this.classList.add('bg-primary','text-white','border-primary');
            compass = parseInt(this.dataset.compass); beat = 0;
        });
        if (btn.dataset.compass === '4/4') btn.click();
    });

    document.getElementById('e-tap').addEventListener('click', () => {
        const now = Date.now();
        tapTimes = tapTimes.filter(t => now - t < 3000);
        tapTimes.push(now);
        if (tapTimes.length >= 2) {
            const avg = (tapTimes[tapTimes.length - 1] - tapTimes[0]) / (tapTimes.length - 1);
            setBpm(Math.round(60000 / avg));
        }
    });

    // Song row click
    document.querySelectorAll('.song-item').forEach(row => {
        row.addEventListener('click', function () {
            const songBpm  = parseInt(this.dataset.bpm) || 72;
            const songName = this.dataset.title;
            setBpm(songBpm);
            document.getElementById('metro-song-name').textContent = songName;
            document.querySelectorAll('.tab-btn')[1].click(); // switch to Metrônomo tab
        });
    });
})();
</script>

<script src="/assets/js/app.js"></script>
</body>
</html>
