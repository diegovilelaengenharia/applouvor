<?php 
$title = "Detalhe da Escala"; 
$activeNav = "escalas";
require __DIR__ . '/../layouts/head.php'; 
require __DIR__ . '/../layouts/top-app-bar.php'; 

$currentUserId = $_SESSION['user_id'] ?? 0;
$myStatus = null;
foreach ($schedule['participants'] as $p) {
    if ($p['user_id'] == $currentUserId) {
        $myStatus = $p['status'];
        break;
    }
}
?>

<main class="w-full max-w-lg mx-auto flex-grow px-4 pb-24">
    <?php require __DIR__ . '/../layouts/flash.php'; ?>

    <div class="mb-4">
        <a href="/escalas" class="text-primary text-sm font-bold flex items-center gap-1 mb-4">
            <span class="material-symbols-outlined text-[18px]">arrow_back</span> Voltar
        </a>
        <span class="text-xs font-bold tracking-widest uppercase text-primary mb-1 block"><?= htmlspecialchars($schedule['event_type']) ?></span>
        <h1 class="text-3xl font-bold text-on-surface"><?= date('d/m/Y', strtotime($schedule['event_date'])) ?></h1>
        <p class="text-on-surface-variant mt-1 flex items-center gap-1">
            <span class="material-symbols-outlined text-[18px]">schedule</span> <?= date('H:i', strtotime($schedule['event_time'])) ?>
        </p>
    </div>

    <?php if (($_SESSION['user_role'] ?? '') === 'admin'): ?>
        <div class="flex gap-2 mb-6">
            <a href="/escalas/<?= $schedule['id'] ?>/editar" class="btn-outline px-4 py-2 text-xs font-bold rounded-full">Editar Escala</a>
            <a href="/escalas/<?= $schedule['id'] ?>/faltas" class="btn-outline px-4 py-2 text-xs font-bold rounded-full border-slate-300 text-on-surface-variant hover:bg-slate-100 hover:text-on-surface">Registrar Faltas</a>
        </div>
    <?php endif; ?>

    <?php if ($myStatus === 'pending'): ?>
        <div class="pib-card p-6 border-primary/20 bg-primary/5 mb-6">
            <h3 class="font-bold text-on-surface mb-2">Você foi escalado!</h3>
            <p class="text-sm text-on-surface-variant mb-4">Por favor, confirme ou recuse sua presença para esta escala.</p>
            <div class="flex gap-3">
                <form action="/escalas/<?= $schedule['id'] ?>/status" method="POST" class="flex-1">
                    <?= csrf_field() ?>
                    <input type="hidden" name="status" value="confirmed">
                    <button type="submit" class="btn-primary w-full py-3 text-sm font-bold">Confirmar</button>
                </form>
                <form action="/escalas/<?= $schedule['id'] ?>/status" method="POST" class="flex-1">
                    <?= csrf_field() ?>
                    <input type="hidden" name="status" value="declined">
                    <button type="submit" class="w-full py-3 text-sm font-bold border border-red-200 text-red-600 rounded-full hover:bg-red-50">Recusar</button>
                </form>
            </div>
        </div>
    <?php elseif ($myStatus === 'confirmed'): ?>
        <div class="bg-green-50 border border-green-200 text-green-800 p-4 rounded-xl flex items-center gap-3 mb-6 text-sm">
            <span class="material-symbols-outlined text-[20px]">check_circle</span>
            <span class="font-bold">Presença confirmada!</span>
        </div>
    <?php elseif ($myStatus === 'declined'): ?>
        <div class="bg-red-50 border border-red-200 text-red-800 p-4 rounded-xl flex items-center gap-3 mb-6 text-sm">
            <span class="material-symbols-outlined text-[20px]">cancel</span>
            <span class="font-bold">Presença recusada.</span>
        </div>
    <?php endif; ?>

    <h2 class="text-lg font-bold text-on-surface mt-8 mb-4">Equipe Escalada</h2>
    
    <div class="pib-card divide-y divide-slate-100">
        <?php if (empty($schedule['participants'])): ?>
            <div class="p-4 text-center text-sm text-on-surface-variant">Nenhum membro escalado ainda.</div>
        <?php else: ?>
            <?php foreach ($schedule['participants'] as $p): ?>
                <div class="p-4 flex items-center gap-4">
                    <div class="w-10 h-10 rounded-full flex items-center justify-center text-white font-bold" style="background-color: <?= htmlspecialchars($p['avatar_color']) ?>">
                        <?= strtoupper(substr($p['name'], 0, 1)) ?>
                    </div>
                    <div class="flex-grow">
                        <h4 class="font-bold text-on-surface text-sm"><?= htmlspecialchars($p['name']) ?></h4>
                        <p class="text-xs text-on-surface-variant"><?= htmlspecialchars($p['assigned_instrument'] ?? 'Vocal') ?></p>
                    </div>
                    <div>
                        <?php if ($p['status'] === 'confirmed'): ?>
                            <span class="material-symbols-outlined text-green-500" title="Confirmado">check_circle</span>
                        <?php elseif ($p['status'] === 'declined'): ?>
                            <span class="material-symbols-outlined text-red-500" title="Recusado">cancel</span>
                        <?php elseif ($p['status'] === 'pending'): ?>
                            <span class="material-symbols-outlined text-yellow-500" title="Pendente">help</span>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</main>

<?php require __DIR__ . '/../layouts/bottom-nav.php'; ?>
