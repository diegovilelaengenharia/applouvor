<?php 
$title = "Escalas"; 
$activeNav = "escalas";
require __DIR__ . '/../layouts/head.php'; 
require __DIR__ . '/../layouts/top-app-bar.php'; 
?>

<main class="w-full max-w-lg mx-auto flex-grow px-4 pb-24">
    <?php require __DIR__ . '/../layouts/flash.php'; ?>

    <div class="flex justify-between items-center mb-6">
        <h1 class="text-2xl font-bold text-on-surface">Escalas</h1>
        <?php if (($_SESSION['user_role'] ?? '') === 'admin'): ?>
            <a href="/escalas/nova" class="btn-primary px-4 py-2 text-sm font-bold flex items-center gap-1 shadow-sm">
                <span class="material-symbols-outlined text-[18px]">add</span> Nova
            </a>
        <?php endif; ?>
    </div>

    <!-- Abas Próximas / Anteriores (Mock simples, futuramente com JS) -->
    <div class="flex gap-4 border-b border-slate-200 mb-6">
        <button class="pb-3 border-b-2 border-primary font-bold text-primary text-sm">Próximas</button>
        <button class="pb-3 border-b-2 border-transparent text-on-surface-variant text-sm hover:text-on-surface">Anteriores</button>
    </div>

    <?php if (empty($upcoming)): ?>
        <div class="pib-card p-8 text-center text-on-surface-variant flex flex-col items-center gap-3">
            <span class="material-symbols-outlined text-[48px] opacity-50">calendar_month</span>
            <p>Nenhuma escala futura agendada.</p>
        </div>
    <?php else: ?>
        <div class="space-y-4">
            <?php foreach ($upcoming as $s): ?>
                <a href="/escalas/<?= $s['id'] ?>" class="block pib-card p-4 hover:shadow-md transition-shadow">
                    <div class="flex justify-between items-start mb-3">
                        <div>
                            <span class="text-[10px] font-bold tracking-widest uppercase text-primary mb-1 block"><?= htmlspecialchars($s['event_type']) ?></span>
                            <h3 class="font-bold text-on-surface text-lg"><?= date('d/m/Y', strtotime($s['event_date'])) ?></h3>
                            <span class="text-sm text-on-surface-variant flex items-center gap-1 mt-0.5">
                                <span class="material-symbols-outlined text-[14px]">schedule</span>
                                <?= date('H:i', strtotime($s['event_time'])) ?>
                            </span>
                        </div>
                        <div class="bg-primary-container text-on-primary-container rounded-full w-10 h-10 flex items-center justify-center font-bold text-sm">
                            <!-- Placeholder avatar -->
                            <?= date('d', strtotime($s['event_date'])) ?>
                        </div>
                    </div>
                </a>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</main>

<?php require __DIR__ . '/../layouts/bottom-nav.php'; ?>
