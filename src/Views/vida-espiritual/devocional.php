<?php
$title = htmlspecialchars($devocional['title']);
$bodyClass = "";
require __DIR__ . '/../layouts/head.php';

$verses = json_decode($devocional['verse_references'] ?? 'null', true);
$mainRef = is_array($verses) && !empty($verses) ? $verses[0] : null;
?>

<!-- Top bar -->
<header class="sticky top-0 z-10 w-full max-w-lg mx-auto bg-surface border-b border-slate-100 dark:border-slate-800 flex items-center gap-3 px-4 py-3.5">
    <a href="/devocionais" class="text-on-surface-variant hover:text-primary transition-colors">
        <span class="material-symbols-outlined text-[22px]">arrow_back_ios_new</span>
    </a>
    <div class="flex-1 min-w-0">
        <p class="text-xs text-on-surface-variant">Devocional</p>
        <p class="text-sm font-semibold text-on-surface truncate"><?= date('j \d\e F', strtotime($devocional['created_at'])) ?></p>
    </div>
    <button onclick="navigator.share && navigator.share({title: '<?= addslashes($devocional['title']) ?>', url: window.location.href})"
            class="text-on-surface-variant hover:text-primary transition-colors">
        <span class="material-symbols-outlined text-[22px]">share</span>
    </button>
</header>

<main class="w-full max-w-lg mx-auto flex-grow px-4 pt-5 pb-10">
    <?php require __DIR__ . '/../layouts/flash.php'; ?>

    <!-- Título -->
    <h1 class="text-2xl font-bold text-on-surface mb-3 leading-snug reveal-item">
        <?= htmlspecialchars($devocional['title']) ?>
    </h1>

    <!-- Referência bíblica em destaque -->
    <?php if ($mainRef): ?>
    <div class="flex items-center gap-2 mb-4 reveal-item">
        <span class="material-symbols-outlined text-[16px] text-primary">menu_book</span>
        <span class="text-sm font-semibold text-primary"><?= htmlspecialchars($mainRef) ?></span>
    </div>
    <?php endif; ?>

    <!-- Autor -->
    <div class="flex items-center gap-2 mb-5 text-xs text-on-surface-variant reveal-item">
        <span class="material-symbols-outlined text-[14px]">person</span>
        <?= htmlspecialchars($devocional['author_name'] ?? 'Ministério de Louvor') ?>
        <?php if ($devocional['instrument']): ?>
        · <?= htmlspecialchars($devocional['instrument']) ?>
        <?php endif; ?>
    </div>

    <!-- Conteúdo principal -->
    <?php if ($devocional['content']): ?>
    <div class="text-sm text-on-surface leading-relaxed space-y-4 mb-6 reveal-item whitespace-pre-line">
        <?= nl2br(htmlspecialchars($devocional['content'])) ?>
    </div>
    <?php endif; ?>

    <!-- Link de mídia (vídeo/link externo) -->
    <?php if ($devocional['media_url'] && $devocional['media_type'] !== 'text'): ?>
    <a href="<?= htmlspecialchars($devocional['media_url']) ?>" target="_blank" rel="noopener"
       class="pib-card p-4 flex items-center gap-3 mb-6 hover:border-primary/30 transition-all reveal-item group">
        <span class="material-symbols-outlined text-[24px] text-primary">
            <?= $devocional['media_type'] === 'video' ? 'play_circle' : 'link' ?>
        </span>
        <div>
            <p class="text-sm font-semibold text-on-surface group-hover:text-primary transition-colors">
                <?= $devocional['media_type'] === 'video' ? 'Assistir vídeo' : 'Acessar link' ?>
            </p>
            <p class="text-xs text-on-surface-variant truncate"><?= htmlspecialchars($devocional['media_url']) ?></p>
        </div>
        <span class="material-symbols-outlined text-[18px] text-on-surface-variant ml-auto">open_in_new</span>
    </a>
    <?php endif; ?>

    <!-- Reações (decorativas — futuro: tabela de reações) -->
    <div class="flex items-center gap-6 py-4 border-t border-slate-100 dark:border-slate-800 mb-6 reveal-item">
        <button class="flex items-center gap-1.5 text-sm text-on-surface-variant hover:text-primary transition-colors">
            <span class="text-xl">🙏</span>
            <span class="font-semibold tabular-nums"><?= (int)($devocional['read_count'] ?? 0) ?></span>
        </button>
        <button class="flex items-center gap-1.5 text-sm text-on-surface-variant hover:text-primary transition-colors">
            <span class="text-xl">❤️</span>
            <span class="font-semibold tabular-nums">0</span>
        </button>
        <button class="flex items-center gap-1.5 text-sm text-on-surface-variant hover:text-primary transition-colors">
            <span class="text-xl">🔥</span>
            <span class="font-semibold tabular-nums">0</span>
        </button>
    </div>

    <!-- Marcar como lido -->
    <?php if (!$hasRead): ?>
    <form method="POST" action="/devocionais/<?= $devocional['id'] ?>/ler" class="mb-6 reveal-item">
        <?= csrf_field() ?>
        <button type="submit"
                class="w-full py-3.5 rounded-2xl text-sm font-bold flex items-center justify-center gap-2 transition-all active:scale-95 bg-green-100 text-green-700 hover:bg-green-200 dark:bg-green-900/30 dark:text-green-400">
            <span class="material-symbols-outlined text-[18px]">check_circle</span>
            Marcar como lido
        </button>
    </form>
    <?php else: ?>
    <div class="flex items-center justify-center gap-2 text-sm text-green-600 font-semibold mb-6 reveal-item">
        <span class="material-symbols-outlined text-[18px]">check_circle</span> Devocional concluído
    </div>
    <?php endif; ?>

    <!-- Comentários -->
    <div id="comentarios" class="reveal-item">
        <h3 class="text-sm font-bold text-on-surface uppercase tracking-wide mb-3 flex items-center gap-2">
            <span class="material-symbols-outlined text-[18px] text-primary">chat_bubble</span>
            Comentários (<?= count($comments) ?>)
        </h3>

        <?php if (!empty($comments)): ?>
        <div class="space-y-3 mb-4">
            <?php foreach ($comments as $c): ?>
            <div class="flex items-start gap-3">
                <div class="w-7 h-7 rounded-full flex items-center justify-center text-[10px] font-bold text-white flex-shrink-0"
                     style="background-color: <?= htmlspecialchars($c['avatar_color'] ?? '#2E7EED') ?>;">
                    <?= mb_strtoupper(mb_substr($c['author_name'] ?? 'A', 0, 1)) ?>
                </div>
                <div class="flex-1 bg-slate-50 dark:bg-slate-800 rounded-xl p-3">
                    <p class="text-[11px] font-semibold text-on-surface"><?= htmlspecialchars($c['author_name']) ?></p>
                    <p class="text-xs text-on-surface-variant mt-0.5 leading-relaxed">
                        <?= nl2br(htmlspecialchars($c['comment'])) ?>
                    </p>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <form method="POST" action="/devocionais/<?= $devocional['id'] ?>/comentarios" class="flex gap-2">
            <?= csrf_field() ?>
            <input type="text" name="comment" placeholder="Compartilhe sua reflexão..."
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
