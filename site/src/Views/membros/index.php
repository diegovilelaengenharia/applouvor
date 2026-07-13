<?php
$title = "Membros";
$bodyClass = "";
require __DIR__ . '/../layouts/head.php';
?>

<header class="sticky top-0 z-10 w-full max-w-lg mx-auto bg-surface border-b border-slate-100 dark:border-slate-800 flex items-center gap-3 px-4 py-3.5">
    <a href="/dashboard" class="text-on-surface-variant hover:text-primary transition-colors">
        <span class="material-symbols-outlined text-[22px]">arrow_back_ios_new</span>
    </a>
    <h1 class="text-lg font-bold text-on-surface flex-1">Membros</h1>
    <span class="text-xs text-on-surface-variant font-medium"><?= count($membros) ?></span>
</header>

<main class="w-full max-w-lg mx-auto flex-grow px-4 pt-4 pb-24">
    <?php require __DIR__ . '/../layouts/flash.php'; ?>

    <!-- Busca -->
    <form method="GET" action="/membros" class="mb-4">
        <div class="relative">
            <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-[18px] text-on-surface-variant">search</span>
            <input type="text" name="q" value="<?= htmlspecialchars($search) ?>"
                   placeholder="Buscar membro..."
                   class="input-glow w-full pl-10 pr-4 py-2.5 text-sm">
            <?php if ($sort !== 'name'): ?>
            <input type="hidden" name="sort" value="<?= htmlspecialchars($sort) ?>">
            <?php endif; ?>
        </div>
    </form>

    <!-- Filtros -->
    <div class="flex gap-2 mb-4">
        <a href="/membros?q=<?= urlencode($search) ?>&sort=name"
           class="px-3 py-1.5 rounded-full text-xs font-medium border transition-colors <?= $sort !== 'presenca' ? 'bg-primary text-white border-primary' : 'border-slate-300 dark:border-slate-600 text-on-surface-variant' ?>">
            Todos
        </a>
        <a href="/membros?q=<?= urlencode($search) ?>&sort=presenca"
           class="px-3 py-1.5 rounded-full text-xs font-medium border transition-colors <?= $sort === 'presenca' ? 'bg-primary text-white border-primary' : 'border-slate-300 dark:border-slate-600 text-on-surface-variant' ?>">
            Ranking presença
        </a>
    </div>

    <?php if (empty($membros)): ?>
    <div class="flex flex-col items-center justify-center text-center py-16">
        <span class="material-symbols-outlined text-[56px] text-on-surface-variant mb-4">group</span>
        <p class="text-base font-semibold text-on-surface">Nenhum membro encontrado</p>
        <p class="text-sm text-on-surface-variant mt-1">Tente outro nome ou instrumento.</p>
    </div>
    <?php else: ?>
    <div class="space-y-2">
        <?php foreach ($membros as $m): ?>
        <a href="/membros/<?= $m['id'] ?>" class="flex items-center gap-3 pib-card p-3.5 reveal-item hover:border-primary/30 transition-all">
            <div class="w-11 h-11 rounded-full flex items-center justify-center text-sm font-bold text-white flex-shrink-0"
                 style="background-color: <?= htmlspecialchars($m['avatar_color'] ?? '#2E7EED') ?>;">
                <?= mb_strtoupper(mb_substr($m['name'], 0, 1)) ?>
            </div>
            <div class="flex-1 min-w-0">
                <div class="flex items-center gap-2">
                    <p class="text-sm font-semibold text-on-surface truncate"><?= htmlspecialchars($m['name']) ?></p>
                    <?php if ($m['role'] === 'admin'): ?>
                    <span class="text-[10px] font-bold px-1.5 py-0.5 rounded-full bg-primary/10 text-primary flex-shrink-0">Admin</span>
                    <?php endif; ?>
                </div>
                <p class="text-xs text-on-surface-variant truncate"><?= htmlspecialchars($m['instrument'] ?? '—') ?></p>
            </div>
            <div class="flex items-center gap-2 flex-shrink-0">
                <?php $pct = (int)($m['presenca_pct'] ?? 0); ?>
                <span class="text-xs font-semibold <?= $pct >= 80 ? 'text-emerald-600 dark:text-emerald-400' : ($pct >= 60 ? 'text-amber-500' : 'text-red-500') ?>">
                    <?= $pct ?>%
                </span>
                <span class="material-symbols-outlined text-[18px] text-on-surface-variant">chevron_right</span>
            </div>
        </a>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
</main>

<!-- FAB: Convidar Membro -->
<a href="/membros/convidar"
   class="fixed bottom-6 right-4 w-14 h-14 rounded-2xl shadow-lg flex items-center justify-center transform hover:scale-105 active:scale-95 transition-all z-20"
   style="background-color: var(--primary);">
    <span class="material-symbols-outlined text-[24px] text-white">person_add</span>
</a>

<script src="/assets/js/app.js"></script>
</body>
</html>
