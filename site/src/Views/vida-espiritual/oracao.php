<?php
$title = "Mural de Oração";
$bodyClass = "";
require __DIR__ . '/../layouts/head.php';

$categories = [
    'todos'    => ['label' => 'Todos',     'icon' => 'all_inclusive'],
    'saude'    => ['label' => 'Saúde',     'icon' => 'favorite'],
    'familia'  => ['label' => 'Família',   'icon' => 'family_restroom'],
    'gratidao' => ['label' => 'Gratidão',  'icon' => 'volunteer_activism'],
    'trabalho' => ['label' => 'Trabalho',  'icon' => 'work'],
    'other'    => ['label' => 'Geral',     'icon' => 'circle'],
];

function prayerCategoryBadge(string $cat): string {
    return match($cat) {
        'saude'    => 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400',
        'familia'  => 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400',
        'gratidao' => 'bg-purple-100 text-purple-700 dark:bg-purple-900/30 dark:text-purple-400',
        'trabalho' => 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400',
        default    => 'bg-slate-100 text-slate-600 dark:bg-slate-700 dark:text-slate-300',
    };
}
?>

<!-- Top bar -->
<header class="sticky top-0 z-10 w-full max-w-lg mx-auto bg-surface border-b border-slate-100 dark:border-slate-800 px-4 py-3.5">
    <div class="flex items-center gap-3 mb-3">
        <a href="/dashboard" class="text-on-surface-variant hover:text-primary transition-colors">
            <span class="material-symbols-outlined text-[22px]">arrow_back_ios_new</span>
        </a>
        <h1 class="text-lg font-bold text-on-surface flex-1">Mural de Oração</h1>
        <span class="material-symbols-outlined text-[22px] text-on-surface-variant">filter_list</span>
    </div>

    <!-- Filter tabs -->
    <div class="flex gap-2 overflow-x-auto pb-0.5 hide-scrollbar">
        <?php foreach ($categories as $key => $cat): ?>
        <a href="/oracao?categoria=<?= $key ?>"
           class="flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-semibold whitespace-nowrap transition-all
                  <?= $category === $key ? 'text-white' : 'bg-slate-100 text-on-surface-variant hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700' ?>"
           <?= $category === $key ? 'style="background-color: var(--primary);"' : '' ?>>
            <span class="material-symbols-outlined text-[13px]"><?= $cat['icon'] ?></span>
            <?= $cat['label'] ?>
        </a>
        <?php endforeach; ?>
    </div>
</header>

<main class="w-full max-w-lg mx-auto flex-grow px-4 pt-4 pb-24">
    <?php require __DIR__ . '/../layouts/flash.php'; ?>

    <?php if (empty($requests)): ?>
    <div class="flex flex-col items-center justify-center text-center py-16">
        <span class="material-symbols-outlined text-[56px] text-on-surface-variant mb-4">self_improvement</span>
        <p class="text-base font-semibold text-on-surface">Nenhum pedido ainda</p>
        <p class="text-sm text-on-surface-variant mt-1">Seja o primeiro a compartilhar!</p>
    </div>
    <?php else: ?>
    <div class="space-y-3">
        <?php foreach ($requests as $r): ?>
        <div class="pib-card p-4 reveal-item">
            <!-- Header do card -->
            <div class="flex items-center gap-2 mb-3">
                <div class="w-8 h-8 rounded-full flex items-center justify-center text-xs font-bold text-white flex-shrink-0"
                     style="background-color: <?= htmlspecialchars($r['avatar_color'] ?? '#2E7EED') ?>;">
                    <?= $r['is_anonymous'] ? '?' : mb_strtoupper(mb_substr($r['author_name'] ?? 'A', 0, 1)) ?>
                </div>
                <div class="flex-1 min-w-0">
                    <p class="text-xs font-semibold text-on-surface">
                        <?= $r['is_anonymous'] ? 'Anônimo' : htmlspecialchars($r['author_name'] ?? '') ?>
                    </p>
                    <p class="text-[10px] text-on-surface-variant"><?= date('d M', strtotime($r['created_at'])) ?></p>
                </div>
                <span class="text-[10px] font-bold uppercase tracking-wider px-2 py-0.5 rounded-full <?= prayerCategoryBadge($r['category']) ?>">
                    <?= $categories[$r['category']]['label'] ?? 'Geral' ?>
                </span>
                <?php if ($r['is_urgent']): ?>
                <span class="text-[10px] font-bold uppercase tracking-wider px-2 py-0.5 rounded-full bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400">
                    URGENTE
                </span>
                <?php endif; ?>
            </div>

            <!-- Texto do pedido -->
            <p class="text-sm text-on-surface leading-relaxed mb-3 line-clamp-3">
                <?= htmlspecialchars($r['title']) ?>
            </p>

            <!-- Ações -->
            <div class="flex items-center gap-3 pt-3 border-t border-slate-100 dark:border-slate-800">
                <form method="POST" action="/oracao/<?= $r['id'] ?>/orar" class="flex-1">
                    <?= csrf_field() ?>
                    <button type="submit"
                            class="w-full flex items-center justify-center gap-1.5 text-xs font-bold py-2 rounded-xl transition-all active:scale-95"
                            style="background-color: rgba(46,126,237,0.12); color: var(--primary);">
                        <span>🙏</span> Estou orando
                        <span class="ml-1 font-semibold"><?= (int)$r['pray_count'] ?></span>
                    </button>
                </form>
                <a href="/oracao/<?= $r['id'] ?>"
                   class="flex items-center gap-1.5 text-xs text-on-surface-variant hover:text-primary transition-colors px-2">
                    <span class="material-symbols-outlined text-[16px]">chat_bubble</span>
                    <span><?= (int)$r['comment_count'] ?></span>
                </a>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
</main>

<!-- FAB: Novo Pedido -->
<a href="/oracao/novo"
   class="fixed bottom-6 right-4 w-14 h-14 rounded-2xl shadow-lg flex items-center justify-center transform hover:scale-105 active:scale-95 transition-all z-20"
   style="background-color: var(--primary);">
    <span class="material-symbols-outlined text-[24px] text-white">add</span>
</a>

<script src="/assets/js/app.js"></script>
</body>
</html>
