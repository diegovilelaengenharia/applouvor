<?php
$title     = 'Escolher Plano de Leitura';
$bodyClass = '';
require __DIR__ . '/../layouts/head.php';
?>

<header class="sticky top-0 z-10 w-full max-w-lg mx-auto bg-surface border-b border-slate-100 dark:border-slate-800 flex items-center gap-3 px-4 py-3.5">
    <a href="/leitura" class="text-on-surface-variant hover:text-primary transition-colors">
        <span class="material-symbols-outlined text-[22px]">arrow_back_ios_new</span>
    </a>
    <h1 class="text-lg font-bold text-on-surface flex-1">Planos de Leitura</h1>
</header>

<main class="w-full max-w-lg mx-auto flex-grow px-4 pt-5 pb-10 flex flex-col gap-4">

    <p class="text-sm text-on-surface-variant">
        Escolha um plano de leitura bíblica. Ao confirmar, seu progresso atual será mantido e o novo plano será aplicado a partir de hoje.
    </p>

    <?php foreach ($plans as $key => $plan): ?>
    <?php $isActive = ($key === $activePlan); ?>
    <div class="pib-card p-5 flex flex-col gap-4
                <?= $isActive ? 'border-primary' : '' ?> reveal-item">
        <div class="flex items-center gap-3">
            <div class="w-11 h-11 rounded-2xl flex items-center justify-center flex-shrink-0
                        <?= $isActive ? 'bg-primary text-white' : 'bg-primary/10 text-primary' ?>">
                <span class="material-symbols-outlined text-[22px]"><?= htmlspecialchars($plan['icon']) ?></span>
            </div>
            <div class="flex-1">
                <div class="flex items-center gap-2">
                    <p class="text-sm font-bold text-on-surface"><?= htmlspecialchars($plan['name']) ?></p>
                    <?php if ($isActive): ?>
                    <span class="text-xs bg-primary/10 text-primary px-2 py-0.5 rounded-full font-semibold">ATIVO</span>
                    <?php endif; ?>
                </div>
                <p class="text-xs text-on-surface-variant"><?= htmlspecialchars($plan['desc']) ?></p>
            </div>
            <div class="text-right flex-shrink-0">
                <p class="text-sm font-bold text-on-surface"><?= $plan['days'] ?></p>
                <p class="text-xs text-on-surface-variant">dias</p>
            </div>
        </div>

        <?php if (!$isActive): ?>
        <form method="POST" action="/leitura/planos">
            <?= csrf_field() ?>
            <input type="hidden" name="plan_key" value="<?= htmlspecialchars($key) ?>">
            <button type="submit"
                    class="btn-outline w-full py-2.5 text-sm flex items-center justify-center gap-2">
                <span class="material-symbols-outlined text-[16px]">check</span>
                Confirmar plano
            </button>
        </form>
        <?php else: ?>
        <div class="flex items-center justify-center gap-2 py-2.5 rounded-2xl bg-primary/5 text-primary text-sm font-medium">
            <span class="material-symbols-outlined text-[16px]">check_circle</span>
            Plano atual
        </div>
        <?php endif; ?>
    </div>
    <?php endforeach; ?>

    <p class="text-center text-xs text-on-surface-variant italic mt-2">
        "Lâmpada para os meus pés é a tua palavra" — Salmos 119:105
    </p>

</main>

<script src="/assets/js/app.js"></script>
</body>
</html>
