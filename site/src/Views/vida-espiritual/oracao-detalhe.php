<?php
$title = "Pedido de Oração";
$bodyClass = "";
require __DIR__ . '/../layouts/head.php';

$catLabels = [
    'saude'=>'Saúde','familia'=>'Família','gratidao'=>'Gratidão','trabalho'=>'Trabalho','other'=>'Geral'
];
$catColors = [
    'saude'    => 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400',
    'familia'  => 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400',
    'gratidao' => 'bg-purple-100 text-purple-700 dark:bg-purple-900/30 dark:text-purple-400',
    'trabalho' => 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400',
    'other'    => 'bg-slate-100 text-slate-600 dark:bg-slate-700 dark:text-slate-300',
];
?>

<!-- Top bar -->
<header class="sticky top-0 z-10 w-full max-w-lg mx-auto bg-surface border-b border-slate-100 dark:border-slate-800 flex items-center gap-3 px-4 py-3.5">
    <a href="/oracao" class="text-on-surface-variant hover:text-primary transition-colors">
        <span class="material-symbols-outlined text-[22px]">arrow_back_ios_new</span>
    </a>
    <h1 class="text-lg font-bold text-on-surface flex-1">Pedido de Oração</h1>
</header>

<main class="w-full max-w-lg mx-auto flex-grow px-4 pt-5 pb-10">
    <?php require __DIR__ . '/../layouts/flash.php'; ?>

    <!-- Header do pedido -->
    <div class="flex items-center gap-3 mb-4 reveal-item">
        <div class="w-10 h-10 rounded-full flex items-center justify-center text-sm font-bold text-white flex-shrink-0"
             style="background-color: <?= htmlspecialchars($request['avatar_color'] ?? '#2E7EED') ?>;">
            <?= $request['is_anonymous'] ? '?' : mb_strtoupper(mb_substr($request['author_name'] ?? 'A', 0, 1)) ?>
        </div>
        <div class="flex-1">
            <p class="text-sm font-semibold text-on-surface">
                <?= $request['is_anonymous'] ? 'Anônimo' : htmlspecialchars($request['author_name'] ?? '') ?>
            </p>
            <p class="text-xs text-on-surface-variant"><?= date('d \d\e F', strtotime($request['created_at'])) ?></p>
        </div>
        <div class="flex items-center gap-1.5">
            <span class="text-[10px] font-bold uppercase tracking-wider px-2 py-0.5 rounded-full <?= $catColors[$request['category']] ?? $catColors['other'] ?>">
                <?= $catLabels[$request['category']] ?? 'Geral' ?>
            </span>
            <?php if ($request['is_urgent']): ?>
            <span class="text-[10px] font-bold uppercase tracking-wider px-2 py-0.5 rounded-full bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400">
                URGENTE
            </span>
            <?php endif; ?>
        </div>
    </div>

    <!-- Texto do pedido -->
    <div class="pib-card p-5 mb-6 reveal-item">
        <p class="text-sm text-on-surface leading-relaxed">
            <?= nl2br(htmlspecialchars($request['title'])) ?>
        </p>
        <?php if ($request['description']): ?>
        <p class="text-xs text-on-surface-variant mt-3 leading-relaxed border-t border-slate-100 dark:border-slate-800 pt-3">
            <?= nl2br(htmlspecialchars($request['description'])) ?>
        </p>
        <?php endif; ?>
    </div>

    <!-- Botão: Orar -->
    <form method="POST" action="/oracao/<?= $request['id'] ?>/orar" class="mb-6 reveal-item">
        <?= csrf_field() ?>
        <button type="submit"
                class="w-full py-4 rounded-2xl text-sm font-bold flex items-center justify-center gap-2.5 transition-all active:scale-95
                       <?= $hasPrayed ? 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400' : 'btn-primary' ?>">
            <span class="text-xl">🙏</span>
            <?= $hasPrayed ? 'Você está orando' : 'Estou orando' ?>
            <span class="font-bold tabular-nums">(<?= (int)$request['pray_count'] ?>)</span>
        </button>
    </form>

    <!-- Comentários -->
    <div id="comentarios" class="reveal-item">
        <h3 class="text-sm font-bold text-on-surface uppercase tracking-wide mb-3 flex items-center gap-2">
            <span class="material-symbols-outlined text-[18px] text-primary">chat_bubble</span>
            Comentários (<?= count($comments) ?>)
        </h3>

        <?php if (empty($comments)): ?>
        <p class="text-sm text-on-surface-variant text-center py-4">Seja o primeiro a encorajar!</p>
        <?php else: ?>
        <div class="space-y-3 mb-4">
            <?php foreach ($comments as $c): ?>
            <div class="flex items-start gap-3">
                <div class="w-7 h-7 rounded-full flex items-center justify-center text-[10px] font-bold text-white flex-shrink-0"
                     style="background-color: <?= htmlspecialchars($c['avatar_color'] ?? '#2E7EED') ?>;">
                    <?= mb_strtoupper(mb_substr($c['author_name'] ?? 'A', 0, 1)) ?>
                </div>
                <div class="flex-1 bg-slate-50 dark:bg-slate-800 rounded-xl p-3">
                    <p class="text-[11px] font-semibold text-on-surface"><?= htmlspecialchars($c['author_name'] ?? 'Alguém') ?></p>
                    <p class="text-xs text-on-surface-variant mt-0.5 leading-relaxed">
                        <?= nl2br(htmlspecialchars($c['comment'])) ?>
                    </p>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <!-- Form de comentário -->
        <form method="POST" action="/oracao/<?= $request['id'] ?>/comentar" class="flex gap-2">
            <?= csrf_field() ?>
            <input type="text" name="comment" placeholder="Deixe um encorajamento..."
                   class="flex-1 bg-slate-50 border border-slate-200 rounded-xl px-3 py-2.5 text-sm text-on-surface placeholder:text-on-surface-variant/40 input-glow transition-all dark:bg-slate-800 dark:border-slate-700">
            <button type="submit"
                    class="w-10 h-10 rounded-xl flex items-center justify-center flex-shrink-0 transition-all active:scale-95"
                    style="background-color: var(--primary);">
                <span class="material-symbols-outlined text-[18px] text-white">send</span>
            </button>
        </form>
    </div>
</main>

<script src="/assets/js/app.js"></script>
</body>
</html>
