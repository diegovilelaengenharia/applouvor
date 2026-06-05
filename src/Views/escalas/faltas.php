<?php 
$title = "Registrar Faltas"; 
$activeNav = "escalas";
require __DIR__ . '/../layouts/head.php'; 
require __DIR__ . '/../layouts/top-app-bar.php'; 
?>

<main class="w-full max-w-lg mx-auto flex-grow px-4 pb-24">
    <?php require __DIR__ . '/../layouts/flash.php'; ?>

    <div class="mb-6">
        <a href="/escalas/<?= $schedule['id'] ?>" class="text-primary text-sm font-bold flex items-center gap-1 mb-4">
            <span class="material-symbols-outlined text-[18px]">arrow_back</span> Cancelar
        </a>
        <h1 class="text-2xl font-bold text-on-surface">Registrar Faltas</h1>
        <p class="text-on-surface-variant text-sm mt-1">
            Escala do dia <?= date('d/m/Y', strtotime($schedule['event_date'])) ?>
        </p>
    </div>

    <form action="/escalas/<?= $schedule['id'] ?>/faltas" method="POST" class="space-y-6">
        <?= csrf_field() ?>

        <div class="pib-card divide-y divide-slate-100">
            <?php if (empty($schedule['participants'])): ?>
                <div class="p-4 text-center text-sm text-on-surface-variant">Nenhum membro nesta escala.</div>
            <?php else: ?>
                <?php foreach ($schedule['participants'] as $p): ?>
                    <div class="p-4 flex flex-col gap-3">
                        <div class="flex items-center gap-4">
                            <div class="w-10 h-10 rounded-full flex items-center justify-center text-white font-bold shrink-0" style="background-color: <?= htmlspecialchars($p['avatar_color']) ?>">
                                <?= strtoupper(substr($p['name'], 0, 1)) ?>
                            </div>
                            <div class="flex-grow">
                                <h4 class="font-bold text-on-surface text-sm"><?= htmlspecialchars($p['name']) ?></h4>
                                <p class="text-xs text-on-surface-variant"><?= htmlspecialchars($p['assigned_instrument'] ?? 'Vocal') ?></p>
                            </div>
                        </div>
                        
                        <div class="flex gap-2 bg-slate-50 p-1 rounded-xl">
                            <!-- Toggle simpes com radios disfarçados de botões para MVP -->
                            <label class="flex-1 text-center cursor-pointer">
                                <input type="radio" name="status[<?= $p['user_id'] ?>]" value="confirmed" <?= $p['status'] === 'confirmed' ? 'checked' : '' ?> class="peer sr-only">
                                <div class="py-2 text-xs font-bold rounded-lg peer-checked:bg-green-100 peer-checked:text-green-800 text-slate-500 hover:bg-slate-200 transition-colors">
                                    Presente
                                </div>
                            </label>
                            <label class="flex-1 text-center cursor-pointer">
                                <input type="radio" name="status[<?= $p['user_id'] ?>]" value="absent" <?= $p['status'] === 'absent' ? 'checked' : '' ?> class="peer sr-only">
                                <div class="py-2 text-xs font-bold rounded-lg peer-checked:bg-red-100 peer-checked:text-red-800 text-slate-500 hover:bg-slate-200 transition-colors">
                                    Faltou
                                </div>
                            </label>
                            <label class="flex-1 text-center cursor-pointer">
                                <input type="radio" name="status[<?= $p['user_id'] ?>]" value="absent_justified" <?= $p['status'] === 'absent_justified' ? 'checked' : '' ?> class="peer sr-only">
                                <div class="py-2 text-xs font-bold rounded-lg peer-checked:bg-yellow-100 peer-checked:text-yellow-800 text-slate-500 hover:bg-slate-200 transition-colors">
                                    Justificado
                                </div>
                            </label>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <?php if (!empty($schedule['participants'])): ?>
            <button type="submit" class="btn-primary w-full py-4 text-sm font-bold shadow-sm flex items-center justify-center gap-2 transform active:scale-95">
                <span>Salvar Faltas</span>
                <span class="material-symbols-outlined text-[20px]">how_to_reg</span>
            </button>
        <?php endif; ?>
    </form>
</main>

<?php require __DIR__ . '/../layouts/bottom-nav.php'; ?>
