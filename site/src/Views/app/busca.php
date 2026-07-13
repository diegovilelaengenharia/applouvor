<?php
$title     = 'Busca';
$bodyClass = '';
require __DIR__ . '/../layouts/head.php';
?>

<header class="sticky top-0 z-10 w-full max-w-lg mx-auto bg-surface border-b border-slate-100 dark:border-slate-800 px-4 py-3">
    <form method="GET" action="/busca" class="flex items-center gap-3">
        <a href="/dashboard" class="text-on-surface-variant hover:text-primary transition-colors flex-shrink-0">
            <span class="material-symbols-outlined text-[22px]">arrow_back_ios_new</span>
        </a>
        <div class="flex-1 flex items-center gap-2 bg-slate-100 dark:bg-slate-800 rounded-2xl px-4 py-2.5">
            <span class="material-symbols-outlined text-[18px] text-on-surface-variant flex-shrink-0">search</span>
            <input type="search" name="q" id="search-input"
                   value="<?= htmlspecialchars($q) ?>"
                   placeholder="Músicas, membros, escalas…"
                   autocomplete="off"
                   class="flex-1 bg-transparent text-sm text-on-surface placeholder-on-surface-variant outline-none">
            <?php if ($q): ?>
            <a href="/busca" class="text-on-surface-variant hover:text-on-surface transition-colors">
                <span class="material-symbols-outlined text-[18px]">close</span>
            </a>
            <?php endif; ?>
        </div>
    </form>
</header>

<main class="w-full max-w-lg mx-auto flex-grow px-4 pt-4 pb-10">

    <?php if ($q === ''): ?>
    <!-- Empty state / recent searches -->
    <div class="flex flex-col items-center justify-center py-16 text-center">
        <div class="w-20 h-20 rounded-3xl bg-primary/10 flex items-center justify-center mb-4">
            <span class="material-symbols-outlined text-[40px] text-primary">search</span>
        </div>
        <p class="text-sm font-medium text-on-surface mb-1">Busca global</p>
        <p class="text-xs text-on-surface-variant max-w-xs leading-relaxed">
            Digite ao menos 2 caracteres para buscar músicas, membros ou escalas.
        </p>
        <div id="recent-searches" class="mt-6 w-full max-w-xs hidden">
            <p class="text-xs font-semibold text-on-surface-variant uppercase tracking-wider mb-2">Buscas recentes</p>
            <div id="recent-list" class="flex flex-wrap gap-2"></div>
        </div>
    </div>

    <?php elseif ($total === 0): ?>
    <!-- No results -->
    <div class="flex flex-col items-center justify-center py-16 text-center">
        <span class="material-symbols-outlined text-[48px] text-on-surface-variant mb-3">search_off</span>
        <p class="text-sm font-medium text-on-surface mb-1">Nenhum resultado</p>
        <p class="text-xs text-on-surface-variant">
            Nada encontrado para "<strong><?= htmlspecialchars($q) ?></strong>".
        </p>
    </div>

    <?php else: ?>
    <!-- Results summary -->
    <p class="text-xs text-on-surface-variant mb-4">
        <?= $total ?> resultado<?= $total !== 1 ? 's' : '' ?> para
        "<strong class="text-on-surface"><?= htmlspecialchars($q) ?></strong>"
    </p>

    <?php if ($songs): ?>
    <!-- MÚSICAS -->
    <div class="mb-5">
        <div class="flex items-center gap-2 mb-2">
            <span class="material-symbols-outlined text-[16px] text-on-surface-variant">music_note</span>
            <p class="text-xs font-semibold text-on-surface-variant uppercase tracking-wider">Músicas</p>
            <span class="ml-auto text-xs text-on-surface-variant"><?= count($songs) ?></span>
        </div>
        <div class="pib-card p-0 overflow-hidden divide-y divide-slate-100 dark:divide-slate-800">
            <?php foreach ($songs as $song): ?>
            <a href="/musicas/<?= (int)$song['id'] ?>"
               class="flex items-center gap-3 px-4 py-3.5 hover:bg-slate-50 dark:hover:bg-slate-800/50 transition-colors">
                <div class="w-9 h-9 rounded-xl bg-primary/10 flex items-center justify-center flex-shrink-0">
                    <span class="material-symbols-outlined text-[18px] text-primary">music_note</span>
                </div>
                <div class="flex-1 min-w-0">
                    <p class="text-sm font-medium text-on-surface truncate">
                        <?= preg_replace('/(' . preg_quote(htmlspecialchars($q), '/') . ')/i', '<mark class="bg-primary/20 text-primary rounded px-0.5">$1</mark>', htmlspecialchars($song['title'])) ?>
                    </p>
                    <p class="text-xs text-on-surface-variant"><?= htmlspecialchars($song['artist'] ?? '') ?></p>
                </div>
                <div class="flex gap-1.5 flex-shrink-0">
                    <?php if ($song['tone']): ?>
                    <span class="text-xs bg-primary/10 text-primary px-2 py-0.5 rounded-lg"><?= htmlspecialchars($song['tone']) ?></span>
                    <?php endif; ?>
                    <?php if ($song['bpm']): ?>
                    <span class="text-xs text-on-surface-variant font-mono"><?= (int)$song['bpm'] ?></span>
                    <?php endif; ?>
                </div>
            </a>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

    <?php if ($members): ?>
    <!-- MEMBROS -->
    <div class="mb-5">
        <div class="flex items-center gap-2 mb-2">
            <span class="material-symbols-outlined text-[16px] text-on-surface-variant">group</span>
            <p class="text-xs font-semibold text-on-surface-variant uppercase tracking-wider">Membros</p>
            <span class="ml-auto text-xs text-on-surface-variant"><?= count($members) ?></span>
        </div>
        <div class="pib-card p-0 overflow-hidden divide-y divide-slate-100 dark:divide-slate-800">
            <?php foreach ($members as $m): ?>
            <a href="/membros/<?= (int)$m['id'] ?>"
               class="flex items-center gap-3 px-4 py-3.5 hover:bg-slate-50 dark:hover:bg-slate-800/50 transition-colors">
                <?php $initial = mb_strtoupper(mb_substr($m['name'], 0, 1)); ?>
                <div class="w-9 h-9 rounded-full flex items-center justify-center text-sm font-bold text-white flex-shrink-0"
                     style="background-color: <?= htmlspecialchars($m['avatar_color'] ?? '#2E7EED') ?>">
                    <?= htmlspecialchars($initial) ?>
                </div>
                <div class="flex-1 min-w-0">
                    <p class="text-sm font-medium text-on-surface truncate">
                        <?= preg_replace('/(' . preg_quote(htmlspecialchars($q), '/') . ')/i', '<mark class="bg-primary/20 text-primary rounded px-0.5">$1</mark>', htmlspecialchars($m['name'])) ?>
                    </p>
                    <p class="text-xs text-on-surface-variant"><?= htmlspecialchars($m['instrument'] ?? 'Sem instrumento') ?></p>
                </div>
                <?php if ($m['role'] === 'admin'): ?>
                <span class="text-xs bg-primary/10 text-primary px-2 py-0.5 rounded-full flex-shrink-0">Admin</span>
                <?php endif; ?>
            </a>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

    <?php if ($schedules): ?>
    <!-- ESCALAS -->
    <div class="mb-5">
        <?php
        $dayNames = ['Dom','Seg','Ter','Qua','Qui','Sex','Sáb'];
        ?>
        <div class="flex items-center gap-2 mb-2">
            <span class="material-symbols-outlined text-[16px] text-on-surface-variant">calendar_month</span>
            <p class="text-xs font-semibold text-on-surface-variant uppercase tracking-wider">Escalas</p>
            <span class="ml-auto text-xs text-on-surface-variant"><?= count($schedules) ?></span>
        </div>
        <div class="pib-card p-0 overflow-hidden divide-y divide-slate-100 dark:divide-slate-800">
            <?php foreach ($schedules as $esc): ?>
            <?php
            $dt  = new DateTime($esc['event_date']);
            $lbl = $dayNames[(int)$dt->format('w')] . ', ' . $dt->format('d/m/Y');
            ?>
            <a href="/escalas/<?= (int)$esc['id'] ?>"
               class="flex items-center gap-3 px-4 py-3.5 hover:bg-slate-50 dark:hover:bg-slate-800/50 transition-colors">
                <div class="w-9 h-9 rounded-xl bg-slate-100 dark:bg-slate-800 flex flex-col items-center justify-center flex-shrink-0">
                    <span class="text-[10px] font-bold text-primary uppercase"><?= $dt->format('M') ?></span>
                    <span class="text-sm font-bold text-on-surface leading-none"><?= $dt->format('d') ?></span>
                </div>
                <div class="flex-1 min-w-0">
                    <p class="text-sm font-medium text-on-surface truncate"><?= htmlspecialchars($esc['event_type'] ?? 'Culto') ?></p>
                    <p class="text-xs text-on-surface-variant"><?= $lbl ?></p>
                </div>
                <span class="material-symbols-outlined text-[16px] text-on-surface-variant">chevron_right</span>
            </a>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

    <?php endif; ?>

</main>

<script>
(function () {
    const STORAGE_KEY = 'search_recents';
    const input = document.getElementById('search-input');

    function getRecents() {
        try { return JSON.parse(localStorage.getItem(STORAGE_KEY)) || []; } catch { return []; }
    }

    function saveRecent(q) {
        if (!q || q.length < 2) return;
        let list = getRecents().filter(r => r !== q);
        list.unshift(q);
        list = list.slice(0, 6);
        localStorage.setItem(STORAGE_KEY, JSON.stringify(list));
    }

    // Save current search
    const currentQ = <?= json_encode($q) ?>;
    if (currentQ) saveRecent(currentQ);

    // Show recent chips on empty state
    const recentBox  = document.getElementById('recent-searches');
    const recentList = document.getElementById('recent-list');
    if (recentBox && recentList) {
        const recents = getRecents();
        if (recents.length) {
            recentBox.classList.remove('hidden');
            recentList.innerHTML = recents.map(r =>
                `<a href="/busca?q=${encodeURIComponent(r)}"
                    class="px-3 py-1.5 rounded-xl bg-slate-100 dark:bg-slate-800 text-sm text-on-surface hover:bg-slate-200 dark:hover:bg-slate-700 transition-colors">
                    ${r}
                </a>`
            ).join('');
        }
    }

    // Auto-submit on type (debounced)
    if (input) {
        input.focus();
        let debounce;
        input.addEventListener('input', () => {
            clearTimeout(debounce);
            const val = input.value.trim();
            if (val.length >= 2 || val.length === 0) {
                debounce = setTimeout(() => input.form.submit(), 400);
            }
        });
    }
})();
</script>

<script src="/assets/js/app.js"></script>
</body>
</html>
