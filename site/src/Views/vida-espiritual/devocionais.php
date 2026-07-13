<?php
$title = "Devocionais";
$bodyClass = "";
require __DIR__ . '/../layouts/head.php';
$featured = !empty($devocionais) ? $devocionais[0] : null;
$rest = array_slice($devocionais, 1);
?>

<!-- Top bar -->
<header class="sticky top-0 z-10 w-full max-w-lg mx-auto bg-surface border-b border-slate-100 dark:border-slate-800 flex items-center gap-3 px-4 py-3.5">
    <a href="/dashboard" class="text-on-surface-variant hover:text-primary transition-colors">
        <span class="material-symbols-outlined text-[22px]">arrow_back_ios_new</span>
    </a>
    <h1 class="text-lg font-bold text-on-surface flex-1">Devocionais</h1>
    <a href="/devocionais" class="text-on-surface-variant hover:text-primary transition-colors">
        <span class="material-symbols-outlined text-[22px]">calendar_today</span>
    </a>
</header>

<main class="w-full max-w-lg mx-auto flex-grow px-4 pt-4 pb-10">
    <?php require __DIR__ . '/../layouts/flash.php'; ?>

    <!-- Streak -->
    <?php if ($streak > 0): ?>
    <div class="flex items-center gap-2 mb-5 pib-card p-3 reveal-item">
        <span class="text-2xl">🔥</span>
        <div>
            <p class="text-sm font-bold text-on-surface"><?= $streak ?> dias seguidos</p>
            <p class="text-xs text-on-surface-variant">Continue a sequência de leitura!</p>
        </div>
    </div>
    <?php endif; ?>

    <?php if (empty($devocionais)): ?>
    <div class="flex flex-col items-center justify-center text-center py-16">
        <span class="material-symbols-outlined text-[56px] text-on-surface-variant mb-4">menu_book</span>
        <p class="text-base font-semibold text-on-surface">Nenhum devocional ainda</p>
        <p class="text-sm text-on-surface-variant mt-1">Novos conteúdos em breve.</p>
    </div>
    <?php else: ?>

    <!-- Devocional em destaque (mais recente) -->
    <?php if ($featured): ?>
    <div class="pib-card p-5 mb-5 reveal-item" style="border-color: rgba(46,126,237,0.3);">
        <div class="flex items-center gap-2 mb-3">
            <?php if (!$featured['has_read']): ?>
            <span class="text-[10px] font-bold uppercase tracking-wider px-2 py-0.5 rounded-full bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400">
                NÃO LIDO
            </span>
            <?php else: ?>
            <span class="flex items-center gap-1 text-[10px] font-bold text-green-600">
                <span class="material-symbols-outlined text-[12px]">check_circle</span> LIDO
            </span>
            <?php endif; ?>
            <span class="text-xs text-on-surface-variant ml-auto"><?= date('d M', strtotime($featured['created_at'])) ?></span>
        </div>

        <h2 class="text-base font-bold text-on-surface mb-1 leading-snug">
            <?= htmlspecialchars($featured['title']) ?>
        </h2>

        <?php
        // Tenta extrair referência bíblica dos verse_references JSON
        $verses = json_decode($featured['verse_references'] ?? 'null', true);
        $ref = is_array($verses) && !empty($verses) ? implode(', ', $verses) : null;
        ?>
        <?php if ($ref): ?>
        <p class="text-xs text-primary font-semibold mb-2"><?= htmlspecialchars($ref) ?></p>
        <?php endif; ?>

        <?php if ($featured['content']): ?>
        <p class="text-xs text-on-surface-variant leading-relaxed line-clamp-3 mb-4">
            <?= htmlspecialchars(strip_tags($featured['content'])) ?>
        </p>
        <?php endif; ?>

        <a href="/devocionais/<?= $featured['id'] ?>"
           class="btn-primary w-full py-3 text-sm font-bold flex items-center justify-center gap-2 transform active:scale-95">
            <span class="material-symbols-outlined text-[18px]">menu_book</span> Ler agora
        </a>
    </div>
    <?php endif; ?>

    <!-- Anteriores -->
    <?php if (!empty($rest)): ?>
    <h3 class="text-xs font-bold text-on-surface-variant uppercase tracking-widest mb-3 mt-2">Anteriores</h3>
    <div class="space-y-2">
        <?php foreach ($rest as $d): ?>
        <a href="/devocionais/<?= $d['id'] ?>" class="block pib-card p-3.5 flex items-center gap-3 reveal-item hover:border-primary/30 transition-all group">
            <div class="flex-1 min-w-0">
                <p class="text-xs text-on-surface-variant"><?= date('d M', strtotime($d['created_at'])) ?></p>
                <p class="text-sm font-semibold text-on-surface leading-snug group-hover:text-primary transition-colors line-clamp-1">
                    <?= htmlspecialchars($d['title']) ?>
                </p>
            </div>
            <?php if ($d['has_read']): ?>
            <span class="material-symbols-outlined text-[18px] text-green-500 flex-shrink-0">check_circle</span>
            <?php else: ?>
            <span class="material-symbols-outlined text-[18px] text-on-surface-variant flex-shrink-0">chevron_right</span>
            <?php endif; ?>
        </a>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <?php endif; ?>
</main>

<script src="/assets/js/app.js"></script>
</body>
</html>
