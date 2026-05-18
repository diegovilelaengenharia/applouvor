<?php
// admin/metronomo.php — Metrônomo Pro
require_once '../includes/db.php';
require_once '../includes/layout.php';
checkLogin();

$urlBpm = (int)($_GET['bpm'] ?? 120);
$urlBpm = max(40, min(220, $urlBpm));

renderAppHeader('Metrônomo', 'index.php');
?>
<style>
.metro-page {
    max-width: 420px;
    margin: 0 auto;
    padding: 24px 16px 100px;
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 24px;
    min-height: 80vh;
}

.bpm-display {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    width: 180px;
    height: 180px;
    border-radius: 50%;
    background: var(--color-surface, #fff);
    box-shadow: 0 4px 24px rgba(59,130,246,.18);
    border: 3px solid #3b82f6;
    cursor: pointer;
    user-select: none;
    transition: transform .08s, box-shadow .08s, background .08s, border-color .08s;
    position: relative;
}
.bpm-display.tapping {
    transform: scale(0.95);
    box-shadow: 0 2px 12px rgba(59,130,246,.35);
}
.bpm-display.beat-flash {
    background: #eff6ff;
    border-color: #1d4ed8;
}
.bpm-num {
    font-size: 3.5rem;
    font-weight: 900;
    color: #1d4ed8;
    line-height: 1;
}
.bpm-label {
    font-size: .7rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: .1em;
    color: #6b7280;
    margin-top: 2px;
}
.tap-hint {
    font-size: .65rem;
    color: #9ca3af;
    margin-top: 6px;
}

.bpm-controls {
    display: flex;
    align-items: center;
    gap: 10px;
    flex-wrap: wrap;
    justify-content: center;
}
.btn-adj {
    width: 48px;
    height: 48px;
    border-radius: 50%;
    border: 2px solid #d1d5db;
    background: var(--color-surface, #fff);
    font-size: 1rem;
    font-weight: 700;
    color: #374151;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: background .1s;
}
.btn-adj:active { background: #f3f4f6; }
.btn-tap {
    padding: 12px 28px;
    background: #3b82f6;
    color: #fff;
    border: none;
    border-radius: 14px;
    font-size: 1rem;
    font-weight: 800;
    cursor: pointer;
    min-height: 48px;
    min-width: 100px;
    transition: background .1s;
}
.btn-tap:active { background: #1d4ed8; }

.slider-container {
    width: 100%;
    padding: 0 4px;
}
.slider-label {
    display: flex;
    justify-content: space-between;
    font-size: .75rem;
    color: #6b7280;
    font-weight: 600;
    margin-bottom: 8px;
}
.bpm-slider {
    -webkit-appearance: none;
    appearance: none;
    width: 100%;
    height: 8px;
    border-radius: 4px;
    background: linear-gradient(to right, #3b82f6 0%, #3b82f6 var(--slider-pct, 50%), #e5e7eb var(--slider-pct, 50%), #e5e7eb 100%);
    outline: none;
    cursor: pointer;
}
.bpm-slider::-webkit-slider-thumb {
    -webkit-appearance: none;
    appearance: none;
    width: 28px;
    height: 28px;
    border-radius: 50%;
    background: #3b82f6;
    cursor: pointer;
    box-shadow: 0 2px 8px rgba(59,130,246,.4);
    border: 3px solid #fff;
}
.bpm-slider::-moz-range-thumb {
    width: 28px;
    height: 28px;
    border-radius: 50%;
    background: #3b82f6;
    cursor: pointer;
    border: 3px solid #fff;
}

.btn-start {
    width: 100%;
    padding: 18px;
    border: none;
    border-radius: 16px;
    font-size: 1.1rem;
    font-weight: 800;
    cursor: pointer;
    transition: background .15s, transform .08s;
    min-height: 56px;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 10px;
    color: #fff;
}
.btn-start.stopped { background: #22c55e; }
.btn-start.running { background: #ef4444; }
.btn-start:active { transform: scale(0.98); }

.tempo-info {
    background: var(--color-surface, #fff);
    border-radius: 12px;
    padding: 12px 16px;
    width: 100%;
    display: flex;
    align-items: center;
    justify-content: space-between;
    font-size: .85rem;
    color: #374151;
    box-shadow: 0 1px 4px rgba(0,0,0,.06);
}
.tempo-val { font-weight: 800; color: #1d4ed8; }
</style>

<div class="metro-page">

    <div class="bpm-display" id="bpmDisp" onclick="tapBpm()"
         onmousedown="this.classList.add('tapping')" onmouseup="this.classList.remove('tapping')"
         ontouchstart="this.classList.add('tapping')" ontouchend="this.classList.remove('tapping')">
        <div class="bpm-num" id="bpmNum">120</div>
        <div class="bpm-label">BPM</div>
        <div class="tap-hint">Toque para medir</div>
    </div>

    <div class="bpm-controls">
        <button class="btn-adj" onclick="adjustBpm(-5)" aria-label="−5 BPM">−5</button>
        <button class="btn-adj" onclick="adjustBpm(-1)" aria-label="−1 BPM">−</button>
        <button class="btn-tap" onclick="tapBpm()">TAP</button>
        <button class="btn-adj" onclick="adjustBpm(1)" aria-label="+1 BPM">+</button>
        <button class="btn-adj" onclick="adjustBpm(5)" aria-label="+5 BPM">+5</button>
    </div>

    <div class="slider-container">
        <div class="slider-label">
            <span>40 BPM</span>
            <span>220 BPM</span>
        </div>
        <input type="range" class="bpm-slider" id="bpmSlider" min="40" max="220" value="120"
               oninput="setBpm(parseInt(this.value))">
    </div>

    <button class="btn-start stopped" id="btnStart" onclick="toggleMetro()">
        <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24"
             fill="currentColor" id="btnIcon">
            <polygon points="5 3 19 12 5 21 5 3"/>
        </svg>
        <span id="btnLabel">Iniciar</span>
    </button>

    <div class="tempo-info">
        <span>Intervalo entre batidas</span>
        <span class="tempo-val" id="beatInterval">500 ms</span>
    </div>

</div>

<script>
let bpm = <?= $urlBpm ?>;
let running = false;
let timerId = null;
let taps = [];
let audioCtx = null;

function getAudioCtx() {
    if (!audioCtx) audioCtx = new (window.AudioContext || window.webkitAudioContext)();
    return audioCtx;
}

function click() {
    try {
        const ctx = getAudioCtx();
        if (ctx.state === 'suspended') ctx.resume();
        const osc = ctx.createOscillator();
        const gain = ctx.createGain();
        osc.connect(gain);
        gain.connect(ctx.destination);
        osc.type = 'sine';
        osc.frequency.value = 880;
        gain.gain.setValueAtTime(0.35, ctx.currentTime);
        gain.gain.exponentialRampToValueAtTime(0.001, ctx.currentTime + 0.1);
        osc.start(ctx.currentTime);
        osc.stop(ctx.currentTime + 0.12);

        const d = document.getElementById('bpmDisp');
        d.classList.add('beat-flash');
        setTimeout(() => d.classList.remove('beat-flash'), 80);
    } catch (e) {}
}

function setBpm(val) {
    bpm = Math.max(40, Math.min(220, val));
    document.getElementById('bpmNum').textContent = bpm;
    const slider = document.getElementById('bpmSlider');
    slider.value = bpm;
    const pct = ((bpm - 40) / (220 - 40) * 100).toFixed(1);
    slider.style.setProperty('--slider-pct', pct + '%');
    document.getElementById('beatInterval').textContent = Math.round(60000 / bpm) + ' ms';
    if (running) restartTimer();
}

function adjustBpm(delta) { setBpm(bpm + delta); }

function tapBpm() {
    const now = Date.now();
    if (taps.length && (now - taps[taps.length - 1]) > 3000) taps = [];
    taps.push(now);
    if (taps.length >= 2) {
        const intervals = taps.slice(1).map((t, i) => t - taps[i]);
        const avg = intervals.reduce((a, b) => a + b, 0) / intervals.length;
        setBpm(Math.round(60000 / avg));
    }
    click();
}

function restartTimer() {
    clearInterval(timerId);
    timerId = setInterval(click, 60000 / bpm);
}

function toggleMetro() {
    running = !running;
    const btn = document.getElementById('btnStart');
    const icon = document.getElementById('btnIcon');
    const label = document.getElementById('btnLabel');
    if (running) {
        restartTimer();
        click();
        btn.className = 'btn-start running';
        label.textContent = 'Parar';
        icon.innerHTML = '<rect x="6" y="4" width="4" height="16"/><rect x="14" y="4" width="4" height="16"/>';
    } else {
        clearInterval(timerId);
        btn.className = 'btn-start stopped';
        label.textContent = 'Iniciar';
        icon.innerHTML = '<polygon points="5 3 19 12 5 21 5 3"/>';
    }
}

setBpm(bpm);
</script>
<?php renderAppFooter(); ?>
