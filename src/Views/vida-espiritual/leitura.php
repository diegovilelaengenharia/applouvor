<?php
$title     = 'Leitura Bíblica';
$bodyClass = '';
require __DIR__ . '/../layouts/head.php';

$pct = $plan['days'] > 0 ? min(100, round(($daysRead / $plan['days']) * 100)) : 0;
?>

<header class="sticky top-0 z-10 w-full max-w-lg mx-auto bg-surface border-b border-slate-100 dark:border-slate-800 flex items-center gap-3 px-4 py-3.5">
    <a href="/dashboard" class="text-on-surface-variant hover:text-primary transition-colors">
        <span class="material-symbols-outlined text-[22px]">arrow_back_ios_new</span>
    </a>
    <h1 class="text-lg font-bold text-on-surface flex-1">Leitura Bíblica</h1>
    <a href="/leitura/planos" class="text-on-surface-variant hover:text-primary transition-colors" title="Trocar plano">
        <span class="material-symbols-outlined text-[22px]">swap_horiz</span>
    </a>
</header>

<main class="w-full max-w-lg mx-auto flex-grow px-4 pt-5 pb-10 flex flex-col gap-5">

    <!-- Plan progress card -->
    <div class="pib-card p-5 bg-gradient-to-br from-primary/5 to-transparent reveal-item">
        <div class="flex items-center gap-3 mb-4">
            <div class="w-11 h-11 rounded-2xl bg-primary/10 flex items-center justify-center flex-shrink-0">
                <span class="material-symbols-outlined text-[22px] text-primary"><?= htmlspecialchars($plan['icon']) ?></span>
            </div>
            <div class="flex-1">
                <p class="text-sm font-bold text-on-surface"><?= htmlspecialchars($plan['name']) ?></p>
                <p class="text-xs text-on-surface-variant"><?= htmlspecialchars($plan['desc']) ?></p>
            </div>
        </div>

        <!-- Progress bar -->
        <div class="mb-3">
            <div class="flex items-center justify-between mb-1.5">
                <span class="text-xs font-medium text-on-surface"><?= $pct ?>% concluído</span>
                <span class="text-xs text-on-surface-variant"><?= $daysRead ?> / <?= $plan['days'] ?> dias</span>
            </div>
            <div class="w-full h-2 bg-slate-100 dark:bg-slate-800 rounded-full overflow-hidden">
                <div class="h-full bg-primary rounded-full transition-all duration-500"
                     style="width: <?= $pct ?>%"></div>
            </div>
        </div>

        <!-- Stats -->
        <div class="flex gap-4">
            <div class="text-center">
                <p class="text-2xl font-bold text-primary"><?= $streak ?></p>
                <p class="text-xs text-on-surface-variant">Sequência 🔥</p>
            </div>
            <div class="w-px bg-slate-100 dark:bg-slate-800"></div>
            <div class="text-center">
                <p class="text-2xl font-bold text-on-surface"><?= $daysRead ?></p>
                <p class="text-xs text-on-surface-variant">Dias lidos</p>
            </div>
            <div class="w-px bg-slate-100 dark:bg-slate-800"></div>
            <div class="text-center">
                <p class="text-2xl font-bold text-on-surface"><?= max(0, $plan['days'] - $daysRead) ?></p>
                <p class="text-xs text-on-surface-variant">Restantes</p>
            </div>
        </div>
    </div>

    <!-- Today's reading -->
    <div class="pib-card p-5 reveal-item">
        <div class="flex items-center gap-2 mb-3">
            <span class="material-symbols-outlined text-[18px] text-primary">today</span>
            <p class="text-sm font-semibold text-on-surface">Leitura de Hoje</p>
            <?php if ($readToday): ?>
            <span class="ml-auto flex items-center gap-1 text-xs text-emerald-600 dark:text-emerald-400 font-medium">
                <span class="material-symbols-outlined text-[14px]">check_circle</span>
                Lida!
            </span>
            <?php endif; ?>
        </div>

        <div class="space-y-2 mb-4">
            <?php foreach ($todayPassages as $passage): ?>
            <div class="flex items-center gap-2.5 p-2.5 rounded-xl bg-slate-50 dark:bg-slate-800/50">
                <span class="material-symbols-outlined text-[16px] text-on-surface-variant flex-shrink-0">auto_stories</span>
                <p class="text-sm font-medium text-on-surface"><?= htmlspecialchars($passage) ?></p>
            </div>
            <?php endforeach; ?>
        </div>

        <?php if (!$readToday): ?>
        <form method="POST" action="/leitura/ler">
            <?= csrf_field() ?>
            <input type="hidden" name="plan_key" value="<?= htmlspecialchars($planKey) ?>">
            <button type="submit" class="btn-primary w-full py-3 text-sm flex items-center justify-center gap-2">
                <span class="material-symbols-outlined text-[18px]">check_circle</span>
                Marcar como lida
            </button>
        </form>
        <?php else: ?>
        <div class="flex items-center justify-center gap-2 py-3 rounded-2xl bg-emerald-50 dark:bg-emerald-900/20 text-emerald-700 dark:text-emerald-400 text-sm font-medium">
            <span class="material-symbols-outlined text-[18px]">check_circle</span>
            Parabéns pela fidelidade!
        </div>
        <?php endif; ?>
    </div>

    <!-- Reflection -->
    <div class="pib-card p-5 reveal-item">
        <div class="flex items-center gap-2 mb-3">
            <span class="material-symbols-outlined text-[18px] text-on-surface-variant">edit_note</span>
            <p class="text-sm font-semibold text-on-surface">Minha Reflexão</p>
        </div>
        <textarea placeholder="Anote o que Deus falou com você hoje…"
                  class="w-full input-glow text-sm rounded-xl py-3 px-4 min-h-[100px] resize-none"
                  oninput="autoSave(this)"></textarea>
        <p id="save-indicator" class="text-xs text-on-surface-variant mt-2 opacity-0 transition-opacity">Salvo localmente</p>
    </div>

    <!-- Soli Deo Gloria -->
    <p class="text-center text-xs text-on-surface-variant italic">Soli Deo Gloria · <?= date('d/m/Y') ?></p>

</main>

<script>
(function () {
    const key  = 'reflection_<?= date('Y-m-d') ?>';
    const ta   = document.querySelector('textarea');
    const ind  = document.getElementById('save-indicator');
    ta.value   = localStorage.getItem(key) || '';

    let debounce;
    window.autoSave = function (el) {
        clearTimeout(debounce);
        debounce = setTimeout(() => {
            localStorage.setItem(key, el.value);
            ind.style.opacity = '1';
            setTimeout(() => ind.style.opacity = '0', 1500);
        }, 800);
    };
})();
</script>

<script src="/assets/js/app.js"></script>
</body>
</html>
