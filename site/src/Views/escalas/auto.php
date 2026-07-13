<?php
$title     = 'Auto-Escalação';
$bodyClass = '';
require __DIR__ . '/../layouts/head.php';

$instruments = ['Vocal','Violão','Guitarra','Baixo','Bateria','Teclado'];
$eventTypes  = ['Culto de Domingo','Culto de Celebração','Culto de Oração','Culto de Juventude','Culto Especial'];

$selectedDate = $params['date'] ?? '';
$selectedType = $params['type'] ?? 'Culto de Domingo';
?>

<header class="sticky top-0 z-10 w-full max-w-lg mx-auto bg-surface border-b border-slate-100 dark:border-slate-800 flex items-center gap-3 px-4 py-3.5">
    <a href="/lider" class="text-on-surface-variant hover:text-primary transition-colors">
        <span class="material-symbols-outlined text-[22px]">arrow_back_ios_new</span>
    </a>
    <h1 class="text-lg font-bold text-on-surface flex-1">Auto-Escalação</h1>
</header>

<main class="w-full max-w-lg mx-auto flex-grow px-4 pt-5 pb-10 flex flex-col gap-5">

    <!-- Parameters form -->
    <form method="POST" action="/escalas/auto/gerar" class="pib-card p-5 flex flex-col gap-4">
        <?= csrf_field() ?>
        <p class="text-sm font-semibold text-on-surface">Parâmetros do Culto</p>

        <div>
            <label class="block text-xs text-on-surface-variant mb-1" for="event_date">Data</label>
            <input type="date" id="event_date" name="event_date"
                   value="<?= htmlspecialchars($selectedDate) ?>"
                   class="w-full input-glow text-sm py-2 rounded-xl" required>
        </div>

        <div>
            <label class="block text-xs text-on-surface-variant mb-1">Tipo de Culto</label>
            <div class="flex flex-wrap gap-2">
                <?php foreach ($eventTypes as $et): ?>
                <label class="flex items-center gap-1.5 cursor-pointer">
                    <input type="radio" name="event_type" value="<?= htmlspecialchars($et) ?>"
                           <?= $selectedType === $et ? 'checked' : '' ?>
                           class="text-primary">
                    <span class="text-sm text-on-surface"><?= htmlspecialchars($et) ?></span>
                </label>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="flex items-center gap-2 text-xs text-on-surface-variant bg-slate-50 dark:bg-slate-800/50 rounded-xl p-3">
            <span class="material-symbols-outlined text-[16px] text-primary">info</span>
            Algoritmo rotaciona membros, respeita indisponibilidades e prioriza quem foi escalado menos vezes.
        </div>

        <button type="submit" class="btn-primary py-3 text-sm flex items-center justify-center gap-2">
            <span class="material-symbols-outlined text-[18px]">auto_awesome</span>
            Gerar Sugestão
        </button>
    </form>

    <?php if ($suggestions !== null): ?>

    <?php if (empty($suggestions)): ?>
    <div class="pib-card p-6 text-center">
        <span class="material-symbols-outlined text-[36px] text-on-surface-variant block mb-2">sentiment_neutral</span>
        <p class="text-sm font-medium text-on-surface">Nenhum membro disponível nesta data</p>
        <p class="text-xs text-on-surface-variant mt-1">Verifique as indisponibilidades cadastradas.</p>
    </div>
    <?php else: ?>

    <!-- Confidence badge -->
    <div class="flex items-center gap-2">
        <span class="px-3 py-1 rounded-full text-xs font-bold bg-emerald-100 dark:bg-emerald-900/30 text-emerald-700 dark:text-emerald-400 uppercase tracking-wider">
            Confiança <?= count($suggestions) >= 5 ? 'ALTA' : (count($suggestions) >= 3 ? 'MÉDIA' : 'BAIXA') ?>
        </span>
        <p class="text-xs text-on-surface-variant"><?= count($suggestions) ?> membros disponíveis</p>
    </div>

    <!-- Save form with checkboxes -->
    <form method="POST" action="/escalas/auto/confirmar">
        <?= csrf_field() ?>
        <input type="hidden" name="event_date" value="<?= htmlspecialchars($selectedDate) ?>">
        <input type="hidden" name="event_type" value="<?= htmlspecialchars($selectedType) ?>">

        <div class="pib-card p-0 overflow-hidden mb-4">
            <div class="px-4 py-2.5 border-b border-slate-100 dark:border-slate-800">
                <p class="text-sm font-semibold text-on-surface">Escala Sugerida</p>
            </div>

            <?php foreach ($suggestions as $i => $member): ?>
            <?php $initial = mb_strtoupper(mb_substr($member['name'], 0, 1)); ?>
            <?php $isFirst = $member['total_escalas'] == 0; ?>
            <label class="flex items-center gap-3 px-4 py-3.5 hover:bg-slate-50 dark:hover:bg-slate-800/50 cursor-pointer
                          <?= $i < count($suggestions) - 1 ? 'border-b border-slate-100 dark:border-slate-800' : '' ?>">
                <input type="checkbox" name="user_ids[]" value="<?= (int)$member['id'] ?>"
                       <?= $i < 6 ? 'checked' : '' ?>
                       class="rounded text-primary flex-shrink-0">
                <div class="w-9 h-9 rounded-full flex items-center justify-center text-sm font-bold text-white flex-shrink-0"
                     style="background-color: <?= htmlspecialchars($member['avatar_color'] ?? '#2E7EED') ?>">
                    <?= htmlspecialchars($initial) ?>
                </div>
                <div class="flex-1 min-w-0">
                    <p class="text-sm font-medium text-on-surface truncate"><?= htmlspecialchars($member['name']) ?></p>
                    <p class="text-xs text-on-surface-variant"><?= htmlspecialchars($member['instrument'] ?? 'Sem instrumento') ?></p>
                </div>
                <div class="flex-shrink-0">
                    <?php if ($isFirst): ?>
                    <span class="text-xs bg-amber-100 dark:bg-amber-900/30 text-amber-700 dark:text-amber-400 px-2 py-0.5 rounded-full">Estreia</span>
                    <?php elseif ($member['total_escalas'] == 1): ?>
                    <span class="text-xs bg-primary/10 text-primary px-2 py-0.5 rounded-full">1ª vez</span>
                    <?php else: ?>
                    <span class="text-xs text-on-surface-variant"><?= (int)$member['total_escalas'] ?>ª vez</span>
                    <?php endif; ?>
                </div>
            </label>
            <?php endforeach; ?>
        </div>

        <div class="flex gap-3">
            <a href="/escalas/auto" class="btn-outline flex-1 py-3 text-center text-sm">
                Ajustar manualmente
            </a>
            <button type="submit" class="btn-primary flex-1 py-3 text-sm flex items-center justify-center gap-2">
                <span class="material-symbols-outlined text-[16px]">check</span>
                Aceitar escala
            </button>
        </div>
    </form>

    <?php endif; ?>
    <?php endif; ?>

</main>

<script src="/assets/js/app.js"></script>
</body>
</html>
