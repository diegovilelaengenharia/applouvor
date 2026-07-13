<?php 
$isEdit = isset($schedule['id']);
$title = $isEdit ? "Editar Escala" : "Nova Escala"; 
$activeNav = "escalas";
require __DIR__ . '/../layouts/head.php'; 
require __DIR__ . '/../layouts/top-app-bar.php'; 
?>

<main class="w-full max-w-lg mx-auto flex-grow px-4 pb-24">
    <?php require __DIR__ . '/../layouts/flash.php'; ?>

    <div class="mb-6">
        <a href="/escalas<?= $isEdit ? "/{$schedule['id']}" : '' ?>" class="text-primary text-sm font-bold flex items-center gap-1 mb-4">
            <span class="material-symbols-outlined text-[18px]">arrow_back</span> Cancelar
        </a>
        <h1 class="text-2xl font-bold text-on-surface"><?= $title ?></h1>
    </div>

    <form action="<?= $isEdit ? "/escalas/{$schedule['id']}/editar" : "/escalas/nova" ?>" method="POST" class="space-y-6">
        <?= csrf_field() ?>

        <div class="pib-card p-6 space-y-4">
            <div>
                <label class="block text-xs font-bold text-on-surface-variant uppercase tracking-wider mb-2 ml-1">Tipo de Evento</label>
                <input type="text" name="event_type" value="<?= htmlspecialchars($schedule['event_type'] ?? 'Culto de Domingo') ?>" required
                       class="w-full bg-slate-50 border border-slate-200 rounded-[16px] px-4 py-3.5 text-on-surface input-glow">
            </div>

            <div class="flex gap-4">
                <div class="flex-1">
                    <label class="block text-xs font-bold text-on-surface-variant uppercase tracking-wider mb-2 ml-1">Data</label>
                    <input type="date" name="event_date" value="<?= htmlspecialchars($schedule['event_date'] ?? date('Y-m-d')) ?>" required
                           class="w-full bg-slate-50 border border-slate-200 rounded-[16px] px-4 py-3.5 text-on-surface input-glow">
                </div>
                <div class="flex-1">
                    <label class="block text-xs font-bold text-on-surface-variant uppercase tracking-wider mb-2 ml-1">Horário</label>
                    <input type="time" name="event_time" value="<?= htmlspecialchars(isset($schedule['event_time']) ? date('H:i', strtotime($schedule['event_time'])) : '09:00') ?>" required
                           class="w-full bg-slate-50 border border-slate-200 rounded-[16px] px-4 py-3.5 text-on-surface input-glow">
                </div>
            </div>

            <div>
                <label class="block text-xs font-bold text-on-surface-variant uppercase tracking-wider mb-2 ml-1">Observações</label>
                <textarea name="notes" rows="3" class="w-full bg-slate-50 border border-slate-200 rounded-[16px] px-4 py-3.5 text-on-surface input-glow"><?= htmlspecialchars($schedule['notes'] ?? '') ?></textarea>
            </div>
        </div>

        <button type="submit" class="btn-primary w-full py-4 text-sm font-bold shadow-sm flex items-center justify-center gap-2 transform active:scale-95">
            <span>Salvar Escala</span>
            <span class="material-symbols-outlined text-[20px]">save</span>
        </button>
    </form>
</main>

<?php require __DIR__ . '/../layouts/bottom-nav.php'; ?>
