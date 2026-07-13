<?php
$title     = 'Metrônomo';
$bodyClass = '';
require __DIR__ . '/../layouts/head.php';
?>

<header class="sticky top-0 z-10 w-full max-w-lg mx-auto bg-surface border-b border-slate-100 dark:border-slate-800 flex items-center gap-3 px-4 py-3.5">
    <a href="/dashboard" class="text-on-surface-variant hover:text-primary transition-colors">
        <span class="material-symbols-outlined text-[22px]">arrow_back_ios_new</span>
    </a>
    <h1 class="text-lg font-bold text-on-surface flex-1">Metrônomo</h1>
    <?php if ($schedule): ?>
    <a href="/escalas/<?= (int)$schedule['id'] ?>"
       class="text-xs text-primary font-medium px-2 py-1 rounded-lg bg-primary/10">
        Ver escala
    </a>
    <?php endif; ?>
</header>

<main class="w-full max-w-lg mx-auto flex-grow px-4 pt-5 pb-10 flex flex-col gap-5">

    <!-- BPM Display -->
    <div id="metro-panel" class="pib-card p-6 flex flex-col items-center gap-4">
        <div id="beat-ring"
             class="w-28 h-28 rounded-full border-4 border-slate-200 dark:border-slate-700 flex items-center justify-center transition-all duration-75"
             style="border-color: transparent;">
            <div class="text-center">
                <p id="bpm-display" class="text-5xl font-bold text-primary tabular-nums">72</p>
                <p class="text-xs text-on-surface-variant uppercase tracking-wider mt-1">BPM</p>
            </div>
        </div>

        <!-- BPM controls -->
        <div class="flex items-center gap-4">
            <button id="btn-minus-10" class="w-10 h-10 rounded-xl bg-slate-100 dark:bg-slate-800 flex items-center justify-center text-sm font-bold text-on-surface active:scale-95 transition-transform">−10</button>
            <button id="btn-minus"    class="w-12 h-12 rounded-2xl bg-slate-100 dark:bg-slate-800 flex items-center justify-center text-xl font-bold text-on-surface active:scale-95 transition-transform">−</button>
            <button id="btn-plus"     class="w-12 h-12 rounded-2xl bg-primary flex items-center justify-center text-xl font-bold text-white active:scale-95 transition-transform">+</button>
            <button id="btn-plus-10"  class="w-10 h-10 rounded-xl bg-slate-100 dark:bg-slate-800 flex items-center justify-center text-sm font-bold text-on-surface active:scale-95 transition-transform">+10</button>
        </div>

        <!-- Compass -->
        <div class="flex gap-2">
            <?php foreach (['2/4','3/4','4/4','6/8'] as $c): ?>
            <button class="compass-btn px-3 py-1.5 rounded-xl text-sm font-semibold border border-slate-200 dark:border-slate-700 text-on-surface-variant transition-all"
                    data-compass="<?= $c ?>">
                <?= $c ?>
            </button>
            <?php endforeach; ?>
        </div>

        <!-- Play / TAP -->
        <div class="flex gap-3 w-full">
            <button id="btn-tap"
                    class="flex-1 py-3 rounded-2xl border border-slate-200 dark:border-slate-700 text-sm font-semibold text-on-surface active:scale-95 transition-transform">
                TAP
            </button>
            <button id="btn-play"
                    class="flex-1 py-3 rounded-2xl bg-primary text-white font-bold text-sm flex items-center justify-center gap-2 active:scale-95 transition-transform">
                <span id="play-icon" class="material-symbols-outlined text-[20px]">play_arrow</span>
                <span id="play-label">Iniciar</span>
            </button>
        </div>
    </div>

    <!-- Setlist panel -->
    <?php if ($songs): ?>
    <div class="pib-card p-0 overflow-hidden">
        <div class="px-4 pt-3 pb-2 border-b border-slate-100 dark:border-slate-800 flex items-center gap-2">
            <span class="material-symbols-outlined text-[18px] text-on-surface-variant">queue_music</span>
            <p class="text-sm font-semibold text-on-surface">
                <?= $schedule ? htmlspecialchars($schedule['event_type'] ?? 'Próximo Culto') : 'Repertório' ?>
            </p>
        </div>
        <div id="song-list" class="divide-y divide-slate-100 dark:divide-slate-800">
            <?php foreach ($songs as $i => $song): ?>
            <button class="song-row w-full flex items-center gap-3 px-4 py-3 text-left hover:bg-slate-50 dark:hover:bg-slate-800/50 transition-colors active:scale-[0.99]"
                    data-bpm="<?= (int)($song['bpm'] ?? 72) ?>"
                    data-title="<?= htmlspecialchars($song['title']) ?>">
                <span class="text-xs tabular-nums font-medium text-on-surface-variant w-5 flex-shrink-0">
                    <?= $i + 1 ?>
                </span>
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
                    <span class="material-symbols-outlined text-[16px] text-on-surface-variant">chevron_right</span>
                </div>
            </button>
            <?php endforeach; ?>
        </div>
    </div>
    <?php else: ?>
    <div class="pib-card p-6 text-center text-on-surface-variant text-sm">
        <span class="material-symbols-outlined text-[32px] mb-2 block">queue_music</span>
        Nenhum culto com músicas cadastradas ainda.
    </div>
    <?php endif; ?>

</main>

<script>
(function () {
    const AudioCtx  = window.AudioContext || window.webkitAudioContext;
    let   ctx       = null;
    let   bpm       = 72;
    let   compass   = 4;
    let   beat      = 0;
    let   timer     = null;
    let   playing   = false;
    let   tapTimes  = [];

    const display   = document.getElementById('bpm-display');
    const ring      = document.getElementById('beat-ring');
    const playIcon  = document.getElementById('play-icon');
    const playLabel = document.getElementById('play-label');

    function updateDisplay() {
        display.textContent = bpm;
    }

    function setBpm(v) {
        bpm = Math.min(220, Math.max(40, v));
        updateDisplay();
        if (playing) { stop(); start(); }
    }

    function flash(accent) {
        ring.style.borderColor = accent ? '#2E7EED' : '#64748b';
        setTimeout(() => ring.style.borderColor = 'transparent', 80);
    }

    function tick() {
        const isAccent = beat % compass === 0;
        flash(isAccent);

        if (ctx) {
            const osc  = ctx.createOscillator();
            const gain = ctx.createGain();
            osc.connect(gain);
            gain.connect(ctx.destination);
            osc.frequency.value = isAccent ? 1050 : 800;
            gain.gain.setValueAtTime(isAccent ? 0.6 : 0.3, ctx.currentTime);
            gain.gain.exponentialRampToValueAtTime(0.001, ctx.currentTime + 0.06);
            osc.start(ctx.currentTime);
            osc.stop(ctx.currentTime + 0.06);
        }

        beat++;
    }

    function start() {
        if (!ctx) ctx = new AudioCtx();
        if (ctx.state === 'suspended') ctx.resume();
        beat    = 0;
        playing = true;
        playIcon.textContent  = 'stop';
        playLabel.textContent = 'Parar';
        tick();
        timer = setInterval(tick, 60000 / bpm);
    }

    function stop() {
        clearInterval(timer);
        playing  = false;
        beat     = 0;
        ring.style.borderColor = 'transparent';
        playIcon.textContent  = 'play_arrow';
        playLabel.textContent = 'Iniciar';
    }

    document.getElementById('btn-play').addEventListener('click', () => playing ? stop() : start());

    document.getElementById('btn-minus').addEventListener('click',    () => setBpm(bpm - 1));
    document.getElementById('btn-plus').addEventListener('click',     () => setBpm(bpm + 1));
    document.getElementById('btn-minus-10').addEventListener('click', () => setBpm(bpm - 10));
    document.getElementById('btn-plus-10').addEventListener('click',  () => setBpm(bpm + 10));

    // Compass selection
    document.querySelectorAll('.compass-btn').forEach(btn => {
        btn.addEventListener('click', function () {
            document.querySelectorAll('.compass-btn').forEach(b => {
                b.classList.remove('bg-primary','text-white','border-primary');
                b.classList.add('text-on-surface-variant');
            });
            this.classList.add('bg-primary','text-white','border-primary');
            this.classList.remove('text-on-surface-variant');
            compass = parseInt(this.dataset.compass);
            beat    = 0;
        });
        if (btn.dataset.compass === '4/4') btn.click();
    });

    // TAP tempo
    document.getElementById('btn-tap').addEventListener('click', () => {
        const now = Date.now();
        tapTimes  = tapTimes.filter(t => now - t < 3000);
        tapTimes.push(now);
        if (tapTimes.length >= 2) {
            const avg = (tapTimes[tapTimes.length - 1] - tapTimes[0]) / (tapTimes.length - 1);
            setBpm(Math.round(60000 / avg));
        }
    });

    // Song row click → set BPM
    document.querySelectorAll('.song-row').forEach(row => {
        row.addEventListener('click', function () {
            const songBpm = parseInt(this.dataset.bpm);
            if (songBpm) setBpm(songBpm);
            document.querySelectorAll('.song-row').forEach(r => r.classList.remove('bg-primary/10'));
            this.classList.add('bg-primary/10');
        });
    });
})();
</script>

<script src="/assets/js/app.js"></script>
</body>
</html>
