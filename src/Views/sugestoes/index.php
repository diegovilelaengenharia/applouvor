<?php
$title = "Sugestões";
$bodyClass = "";
require __DIR__ . '/../layouts/head.php';

$isAdmin = ($_SESSION['user_role'] ?? '') === 'admin';
$tabs = [
    'pending'  => ['label' => 'Pendentes', 'count' => $counts['pending']],
    'approved' => ['label' => 'Aprovadas', 'count' => $counts['approved']],
    'rejected' => ['label' => 'Recusadas', 'count' => $counts['rejected']],
];
?>

<header class="sticky top-0 z-10 w-full max-w-lg mx-auto bg-surface border-b border-slate-100 dark:border-slate-800 flex items-center gap-3 px-4 py-3.5">
    <a href="/dashboard" class="text-on-surface-variant hover:text-primary transition-colors">
        <span class="material-symbols-outlined text-[22px]">arrow_back_ios_new</span>
    </a>
    <h1 class="text-lg font-bold text-on-surface flex-1">Sugestões</h1>
</header>

<!-- Tabs status -->
<div class="sticky top-[57px] z-10 w-full max-w-lg mx-auto bg-surface border-b border-slate-100 dark:border-slate-800">
    <div class="flex">
        <?php foreach ($tabs as $key => $tab): ?>
        <a href="/sugestoes?status=<?= $key ?>"
           class="flex-1 text-center py-3 text-xs font-semibold border-b-2 transition-colors <?= $status === $key ? 'border-primary text-primary' : 'border-transparent text-on-surface-variant' ?>">
            <?= $tab['label'] ?>
            <?php if ($tab['count'] > 0): ?>
            <span class="ml-1 px-1.5 py-0.5 rounded-full text-[10px] font-bold <?= $status === $key ? 'bg-primary text-white' : 'bg-surface-variant text-on-surface-variant' ?>">
                <?= $tab['count'] ?>
            </span>
            <?php endif; ?>
        </a>
        <?php endforeach; ?>
    </div>
</div>

<main class="w-full max-w-lg mx-auto flex-grow px-4 pt-4 pb-24">
    <?php require __DIR__ . '/../layouts/flash.php'; ?>

    <?php if (empty($sugestoes)): ?>
    <div class="flex flex-col items-center justify-center text-center py-16">
        <span class="material-symbols-outlined text-[56px] text-on-surface-variant mb-4">lightbulb</span>
        <p class="text-base font-semibold text-on-surface">Nenhuma sugestão <?= mb_strtolower($tabs[$status]['label']) ?></p>
        <p class="text-sm text-on-surface-variant mt-1">Seja o primeiro a sugerir uma música!</p>
    </div>
    <?php else: ?>
    <div class="space-y-4">
        <?php foreach ($sugestoes as $s): ?>
        <div class="pib-card p-4 reveal-item">
            <!-- Quem sugeriu + data -->
            <div class="flex items-center gap-2.5 mb-3">
                <div class="w-7 h-7 rounded-full flex items-center justify-center text-xs font-bold text-white flex-shrink-0"
                     style="background-color: <?= htmlspecialchars($s['avatar_color'] ?? '#2E7EED') ?>;">
                    <?= mb_strtoupper(mb_substr($s['user_name'], 0, 1)) ?>
                </div>
                <p class="text-xs font-semibold text-on-surface flex-1"><?= htmlspecialchars($s['user_name']) ?></p>
                <p class="text-xs text-on-surface-variant"><?= date('d M', strtotime($s['created_at'])) ?></p>
            </div>

            <!-- Música -->
            <div class="flex items-start gap-3 mb-3">
                <div class="w-10 h-10 rounded-lg bg-primary/10 flex items-center justify-center flex-shrink-0">
                    <span class="material-symbols-outlined text-[20px] text-primary">music_note</span>
                </div>
                <div class="flex-1 min-w-0">
                    <p class="text-sm font-bold text-on-surface"><?= htmlspecialchars($s['title']) ?></p>
                    <?php if ($s['artist']): ?>
                    <p class="text-xs text-on-surface-variant"><?= htmlspecialchars($s['artist']) ?></p>
                    <?php endif; ?>
                    <?php if ($s['link']): ?>
                    <a href="<?= htmlspecialchars($s['link']) ?>" target="_blank"
                       class="text-[11px] text-primary hover:underline">Ver referência ↗</a>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Justificativa -->
            <?php if ($s['notes']): ?>
            <p class="text-xs text-on-surface-variant italic mb-3 leading-relaxed line-clamp-2">
                "<?= htmlspecialchars($s['notes']) ?>"
            </p>
            <?php endif; ?>

            <!-- Ações -->
            <?php if ($isAdmin && $status === 'pending'): ?>
            <div class="flex gap-2 pt-2 border-t border-slate-100 dark:border-slate-800">
                <form method="POST" action="/sugestoes/<?= $s['id'] ?>/aprovar" class="flex-1">
                    <?= csrf_field() ?>
                    <button type="submit" class="w-full flex items-center justify-center gap-1.5 py-2 rounded-xl text-xs font-semibold bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400 hover:bg-emerald-200 transition-colors">
                        <span class="material-symbols-outlined text-[14px]">check</span> Aprovar
                    </button>
                </form>
                <form method="POST" action="/sugestoes/<?= $s['id'] ?>/recusar" class="flex-1">
                    <?= csrf_field() ?>
                    <button type="submit" class="w-full flex items-center justify-center gap-1.5 py-2 rounded-xl text-xs font-semibold bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400 hover:bg-red-200 transition-colors">
                        <span class="material-symbols-outlined text-[14px]">close</span> Recusar
                    </button>
                </form>
            </div>
            <?php elseif ($status === 'approved'): ?>
            <div class="flex items-center gap-1.5 pt-2 border-t border-slate-100 dark:border-slate-800">
                <span class="material-symbols-outlined text-[14px] text-emerald-600">check_circle</span>
                <p class="text-xs text-emerald-600 font-medium">Aprovada pela liderança</p>
            </div>
            <?php elseif ($status === 'rejected'): ?>
            <div class="flex items-center gap-1.5 pt-2 border-t border-slate-100 dark:border-slate-800">
                <span class="material-symbols-outlined text-[14px] text-red-500">cancel</span>
                <p class="text-xs text-red-500 font-medium">Não aprovada desta vez</p>
            </div>
            <?php endif; ?>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
</main>

<!-- FAB: Sugerir -->
<a href="/sugestoes/nova"
   class="fixed bottom-6 right-4 w-14 h-14 rounded-2xl shadow-lg flex items-center justify-center transform hover:scale-105 active:scale-95 transition-all z-20"
   style="background-color: var(--primary);">
    <span class="material-symbols-outlined text-[24px] text-white">add</span>
</a>

<script src="/assets/js/app.js"></script>
</body>
</html>
