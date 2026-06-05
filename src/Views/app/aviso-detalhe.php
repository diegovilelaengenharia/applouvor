<?php
$title = htmlspecialchars($aviso['titulo']);
$bodyClass = "";
require __DIR__ . '/../layouts/head.php';

$badgeClass = match($aviso['prioridade']) {
    'urgente' => 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400',
    'alta'    => 'bg-orange-100 text-orange-700 dark:bg-orange-900/30 dark:text-orange-400',
    'baixa'   => 'bg-slate-100 text-slate-600 dark:bg-slate-700 dark:text-slate-300',
    default   => 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400',
};
$badgeLabel = match($aviso['prioridade']) {
    'urgente' => 'URGENTE', 'alta' => 'IMPORTANTE', 'baixa' => 'INFO', default => 'INFO'
};
$isAdmin = ($_SESSION['user_role'] ?? '') === 'admin';
?>

<!-- Top bar -->
<header class="sticky top-0 z-10 w-full max-w-lg mx-auto bg-surface border-b border-slate-100 dark:border-slate-800 flex items-center gap-3 px-4 py-3.5">
    <a href="/avisos" class="text-on-surface-variant hover:text-primary transition-colors">
        <span class="material-symbols-outlined text-[22px]">arrow_back_ios_new</span>
    </a>
    <h1 class="text-lg font-bold text-on-surface flex-1">Aviso</h1>

    <?php if ($isAdmin): ?>
    <div class="relative" x-data="{ open: false }">
        <button onclick="this.nextElementSibling.classList.toggle('hidden')"
                class="text-on-surface-variant hover:text-primary transition-colors p-1">
            <span class="material-symbols-outlined text-[22px]">more_vert</span>
        </button>
        <div class="hidden absolute right-0 top-8 bg-surface border border-slate-200 dark:border-slate-700 rounded-xl shadow-lg overflow-hidden z-30 min-w-[140px]">
            <form method="POST" action="/avisos/<?= $aviso['id'] ?>/excluir"
                  onsubmit="return confirm('Excluir este aviso? Não pode ser desfeito.')">
                <?= csrf_field() ?>
                <button type="submit" class="w-full text-left px-4 py-3 text-sm text-red-600 hover:bg-red-50 dark:hover:bg-red-900/20 flex items-center gap-2">
                    <span class="material-symbols-outlined text-[16px]">delete</span> Excluir
                </button>
            </form>
        </div>
    </div>
    <?php endif; ?>
</header>

<main class="w-full max-w-lg mx-auto flex-grow px-4 pt-5 pb-10">
    <?php require __DIR__ . '/../layouts/flash.php'; ?>

    <!-- Badge + ícone -->
    <div class="flex items-center gap-3 mb-4 reveal-item">
        <div class="w-12 h-12 rounded-2xl flex items-center justify-center flex-shrink-0" style="background-color: rgba(46,126,237,0.12);">
            <span class="material-symbols-outlined text-[24px] text-primary">campaign</span>
        </div>
        <div>
            <span class="inline-block text-[11px] font-bold uppercase tracking-wider px-2.5 py-1 rounded-full <?= $badgeClass ?>">
                <?= $badgeLabel ?>
            </span>
            <p class="text-[11px] text-on-surface-variant mt-0.5">
                <?= date('d \d\e F \d\e Y', strtotime($aviso['created_at'])) ?> · <?= date('H:i', strtotime($aviso['created_at'])) ?>
            </p>
        </div>
    </div>

    <!-- Título -->
    <h2 class="text-xl font-bold text-on-surface mb-4 reveal-item leading-snug">
        <?= htmlspecialchars($aviso['titulo']) ?>
    </h2>

    <!-- Conteúdo -->
    <?php if ($aviso['conteudo']): ?>
    <div class="prose prose-sm max-w-none text-on-surface-variant leading-relaxed mb-6 reveal-item whitespace-pre-line">
        <?= nl2br(htmlspecialchars($aviso['conteudo'])) ?>
    </div>
    <?php endif; ?>

    <!-- Autor -->
    <div class="flex items-center gap-3 py-4 border-t border-slate-100 dark:border-slate-800 mb-6 reveal-item">
        <div class="w-9 h-9 rounded-full flex items-center justify-center text-sm font-bold text-white flex-shrink-0" style="background-color: var(--primary);">
            <?= mb_strtoupper(mb_substr($aviso['author_name'] ?? 'L', 0, 1)) ?>
        </div>
        <div>
            <p class="text-sm font-semibold text-on-surface"><?= htmlspecialchars($aviso['author_name'] ?? 'Liderança') ?></p>
            <p class="text-xs text-on-surface-variant">
                <?= date('d M · H:i', strtotime($aviso['created_at'])) ?>
            </p>
        </div>
    </div>

    <!-- Reações (decorativas — futuro: tabela aviso_reactions) -->
    <div class="flex items-center gap-6 py-4 border-t border-slate-100 dark:border-slate-800 reveal-item">
        <button class="flex items-center gap-1.5 text-sm text-on-surface-variant hover:text-primary transition-colors">
            <span class="text-xl">👍</span>
            <span class="font-semibold tabular-nums">0</span>
        </button>
        <button class="flex items-center gap-1.5 text-sm text-on-surface-variant hover:text-primary transition-colors">
            <span class="text-xl">🙏</span>
            <span class="font-semibold tabular-nums">0</span>
        </button>
        <button class="flex items-center gap-1.5 text-sm text-on-surface-variant hover:text-primary transition-colors">
            <span class="text-xl">❤️</span>
            <span class="font-semibold tabular-nums">0</span>
        </button>
    </div>
</main>

<script src="/assets/js/app.js"></script>
</body>
</html>
