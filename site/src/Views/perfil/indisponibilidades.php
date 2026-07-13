<?php
$title = "Indisponibilidades";
$activeNav = "perfil";
require __DIR__ . '/../layouts/head.php';
require __DIR__ . '/../layouts/top-app-bar.php';
?>

<main class="w-full max-w-lg mx-auto flex-grow px-4 pb-24">
    <?php require __DIR__ . '/../layouts/flash.php'; ?>

    <div class="mb-6">
        <a href="/perfil" class="text-primary text-sm font-bold flex items-center gap-1 mb-4">
            <span class="material-symbols-outlined text-[18px]">arrow_back</span> Voltar
        </a>
        <h1 class="text-2xl font-bold text-on-surface">Indisponibilidades</h1>
        <p class="text-sm text-on-surface-variant mt-1">Marque os períodos em que você não poderá servir. A liderança vê isso ao montar as escalas.</p>
    </div>

    <!-- Form de novo período -->
    <form action="/indisponibilidades" method="POST" class="pib-card p-6 space-y-4 mb-8">
        <?= csrf_field() ?>
        <div class="flex gap-4">
            <div class="flex-1">
                <label class="block text-xs font-bold text-on-surface-variant uppercase tracking-wider mb-2 ml-1">Início</label>
                <input type="date" name="start_date" required
                       class="w-full bg-slate-50 border border-slate-200 rounded-[16px] px-4 py-3.5 text-on-surface input-glow">
            </div>
            <div class="flex-1">
                <label class="block text-xs font-bold text-on-surface-variant uppercase tracking-wider mb-2 ml-1">Fim</label>
                <input type="date" name="end_date" required
                       class="w-full bg-slate-50 border border-slate-200 rounded-[16px] px-4 py-3.5 text-on-surface input-glow">
            </div>
        </div>
        <div>
            <label class="block text-xs font-bold text-on-surface-variant uppercase tracking-wider mb-2 ml-1">Motivo (opcional)</label>
            <input type="text" name="reason" placeholder="Ex.: Viagem, trabalho, saúde"
                   class="w-full bg-slate-50 border border-slate-200 rounded-[16px] px-4 py-3.5 text-on-surface input-glow">
        </div>
        <button type="submit" class="btn-primary w-full py-3.5 text-sm font-bold shadow-sm flex items-center justify-center gap-2 transform active:scale-95">
            <span class="material-symbols-outlined text-[20px]">add</span> Adicionar período
        </button>
    </form>

    <!-- Lista de períodos -->
    <p class="text-xs font-bold uppercase tracking-wider text-on-surface-variant mb-2 ml-1">Meus períodos</p>
    <?php if (empty($periods)): ?>
        <div class="pib-card p-8 text-center text-on-surface-variant flex flex-col items-center gap-3">
            <span class="material-symbols-outlined text-[48px] opacity-50">event_available</span>
            <p>Nenhuma indisponibilidade registrada.</p>
        </div>
    <?php else: ?>
        <div class="space-y-3">
            <?php foreach ($periods as $p): ?>
                <div class="pib-card p-4 flex items-center justify-between gap-3">
                    <div class="flex items-start gap-3 min-w-0">
                        <span class="material-symbols-outlined text-[22px] text-primary mt-0.5">event_busy</span>
                        <div class="min-w-0">
                            <p class="text-sm font-bold text-on-surface">
                                <?= date('d/m/Y', strtotime($p['start_date'])) ?> &rarr; <?= date('d/m/Y', strtotime($p['end_date'])) ?>
                            </p>
                            <?php if (!empty($p['reason'])): ?>
                                <p class="text-xs text-on-surface-variant truncate"><?= htmlspecialchars($p['reason']) ?></p>
                            <?php endif; ?>
                        </div>
                    </div>
                    <form action="/indisponibilidades/<?= (int) $p['id'] ?>/remover" method="POST" onsubmit="return confirm('Remover este período?');">
                        <?= csrf_field() ?>
                        <button type="submit" class="text-on-surface-variant hover:text-error transition-colors p-1" aria-label="Remover">
                            <span class="material-symbols-outlined text-[20px]">delete</span>
                        </button>
                    </form>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</main>

<?php require __DIR__ . '/../layouts/bottom-nav.php'; ?>
