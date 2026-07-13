<?php
$title = "Aniversariantes";
$bodyClass = "";
require __DIR__ . '/../layouts/head.php';

$nomeMes = [
    1 => 'Janeiro', 2 => 'Fevereiro', 3 => 'Março',    4 => 'Abril',
    5 => 'Maio',    6 => 'Junho',     7 => 'Julho',     8 => 'Agosto',
    9 => 'Setembro',10 => 'Outubro', 11 => 'Novembro', 12 => 'Dezembro',
];
?>

<header class="sticky top-0 z-10 w-full max-w-lg mx-auto bg-surface border-b border-slate-100 dark:border-slate-800 flex items-center gap-3 px-4 py-3.5">
    <a href="/dashboard" class="text-on-surface-variant hover:text-primary transition-colors">
        <span class="material-symbols-outlined text-[22px]">arrow_back_ios_new</span>
    </a>
    <h1 class="text-lg font-bold text-on-surface flex-1">Aniversariantes</h1>
    <span class="material-symbols-outlined text-[22px] text-primary">cake</span>
</header>

<main class="w-full max-w-lg mx-auto flex-grow px-4 pt-4 pb-10">

    <?php if (empty($porMes)): ?>
    <div class="flex flex-col items-center justify-center text-center py-16">
        <span class="material-symbols-outlined text-[56px] text-on-surface-variant mb-4">cake</span>
        <p class="text-base font-semibold text-on-surface">Nenhuma data de aniversário cadastrada</p>
        <p class="text-sm text-on-surface-variant mt-1">Os membros podem atualizar seus dados em Perfil → Editar.</p>
    </div>
    <?php else: ?>

    <!-- Mês atual em destaque -->
    <?php if (isset($porMes[$mesAtual])): ?>
    <div class="mb-5">
        <div class="flex items-center gap-2 mb-3">
            <span class="material-symbols-outlined text-[18px] text-primary">celebration</span>
            <p class="text-sm font-bold text-primary">Este mês — <?= $nomeMes[$mesAtual] ?></p>
        </div>
        <div class="space-y-3">
            <?php foreach ($porMes[$mesAtual] as $p): ?>
            <div class="pib-card p-3.5 flex items-center gap-3 reveal-item" style="border-color: rgba(46,126,237,.2)">
                <div class="w-11 h-11 rounded-full flex items-center justify-center text-sm font-bold text-white flex-shrink-0"
                     style="background-color: <?= htmlspecialchars($p['avatar_color'] ?? '#2E7EED') ?>;">
                    <?= mb_strtoupper(mb_substr($p['name'], 0, 1)) ?>
                </div>
                <div class="flex-1 min-w-0">
                    <p class="text-sm font-semibold text-on-surface"><?= htmlspecialchars($p['name']) ?></p>
                    <p class="text-xs text-on-surface-variant"><?= htmlspecialchars($p['instrument'] ?? '—') ?></p>
                </div>
                <div class="text-right flex-shrink-0">
                    <p class="text-xs font-bold text-primary"><?= sprintf('%02d', $p['birth_day']) ?> de <?= $nomeMes[$p['birth_month']] ?></p>
                    <?php if (!empty($p['phone'])): ?>
                    <a href="https://wa.me/55<?= preg_replace('/\D/', '', $p['phone']) ?>" target="_blank"
                       class="text-[11px] text-primary flex items-center gap-0.5 justify-end mt-0.5 hover:underline">
                        <span class="material-symbols-outlined text-[13px]">chat_bubble</span>
                        Parabenizar
                    </a>
                    <?php endif; ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

    <!-- Demais meses -->
    <?php foreach ($porMes as $mes => $pessoas): ?>
    <?php if ($mes === $mesAtual) continue; ?>
    <div class="mb-4">
        <p class="text-xs font-semibold text-on-surface-variant uppercase tracking-wider mb-2 px-1"><?= $nomeMes[$mes] ?></p>
        <div class="space-y-2">
            <?php foreach ($pessoas as $p): ?>
            <div class="flex items-center gap-3 pib-card p-3 reveal-item">
                <div class="w-9 h-9 rounded-full flex items-center justify-center text-sm font-bold text-white flex-shrink-0"
                     style="background-color: <?= htmlspecialchars($p['avatar_color'] ?? '#2E7EED') ?>;">
                    <?= mb_strtoupper(mb_substr($p['name'], 0, 1)) ?>
                </div>
                <div class="flex-1 min-w-0">
                    <p class="text-sm font-medium text-on-surface"><?= htmlspecialchars($p['name']) ?></p>
                    <p class="text-xs text-on-surface-variant"><?= htmlspecialchars($p['instrument'] ?? '—') ?></p>
                </div>
                <p class="text-xs text-on-surface-variant flex-shrink-0">
                    <?= sprintf('%02d', $p['birth_day']) ?> de <?= $nomeMes[$p['birth_month']] ?>
                </p>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endforeach; ?>

    <div class="flex items-center justify-center gap-2 mt-6 text-on-surface-variant">
        <span class="material-symbols-outlined text-[15px]">church</span>
        <p class="text-xs">Soli Deo Gloria</p>
    </div>

    <?php endif; ?>
</main>

<script src="/assets/js/app.js"></script>
</body>
</html>
