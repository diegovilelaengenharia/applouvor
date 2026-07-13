<?php
$title = "Início";
$activeNav = "inicio";
require __DIR__ . '/../layouts/head.php';
require __DIR__ . '/../layouts/top-app-bar.php';

// helpers de badge
function statusBadge(string $status): string {
    return match($status) {
        'confirmed' => 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400',
        'declined'  => 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400',
        default     => 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400',
    };
}
function statusLabel(string $status): string {
    return match($status) {
        'confirmed' => 'Confirmado',
        'declined'  => 'Recusado',
        default     => 'Pendente',
    };
}
function prioridadeBadge(string $p): string {
    return match($p) {
        'urgente' => 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400',
        'alta'    => 'bg-orange-100 text-orange-700 dark:bg-orange-900/30 dark:text-orange-400',
        default   => 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400',
    };
}
function prioridadeLabel(string $p): string {
    return match($p) {
        'urgente' => 'URGENTE',
        'alta'    => 'IMPORTANTE',
        'baixa'   => 'INFO',
        default   => 'INFO',
    };
}
?>

<main class="w-full max-w-lg mx-auto flex-grow px-4 pb-28">
    <?php require __DIR__ . '/../layouts/flash.php'; ?>

    <!-- ── Saudação ── -->
    <div class="mb-5 reveal-item">
        <h1 class="text-2xl font-bold text-on-surface">Olá, <?= htmlspecialchars($userName) ?>! 👋</h1>
        <div class="flex items-center gap-1.5 mt-1.5">
            <span class="material-symbols-outlined text-[15px] text-primary">
                <?= $userRole === 'admin' ? 'shield' : 'music_note' ?>
            </span>
            <span class="text-sm text-on-surface-variant font-medium">
                <?php if ($userRole === 'admin'): ?>
                    Administrador
                <?php else: ?>
                    Músico<?= $userInstrument ? ' — ' . htmlspecialchars($userInstrument) : '' ?>
                <?php endif; ?>
            </span>
        </div>
    </div>

    <!-- ── Card: Aviso da Liderança ── -->
    <?php if ($latestAviso): ?>
    <a href="/avisos/<?= $latestAviso['id'] ?>" class="block pib-card p-4 mb-4 reveal-item group hover:border-primary/30 transition-all">
        <div class="flex items-start gap-3">
            <div class="w-10 h-10 rounded-xl flex items-center justify-center flex-shrink-0" style="background-color: rgba(46,126,237,0.12);">
                <span class="material-symbols-outlined text-[20px] text-primary">campaign</span>
            </div>
            <div class="flex-1 min-w-0">
                <div class="flex items-center gap-2 mb-1">
                    <span class="text-[10px] font-bold uppercase tracking-wider px-1.5 py-0.5 rounded-full <?= prioridadeBadge($latestAviso['prioridade']) ?>">
                        <?= prioridadeLabel($latestAviso['prioridade']) ?>
                    </span>
                    <span class="text-xs text-on-surface-variant">Aviso da Liderança</span>
                </div>
                <p class="text-sm font-semibold text-on-surface leading-snug line-clamp-1">
                    <?= htmlspecialchars($latestAviso['titulo']) ?>
                </p>
                <?php if ($latestAviso['conteudo']): ?>
                <p class="text-xs text-on-surface-variant mt-0.5 line-clamp-2">
                    <?= htmlspecialchars(strip_tags($latestAviso['conteudo'])) ?>
                </p>
                <?php endif; ?>
            </div>
            <span class="material-symbols-outlined text-[18px] text-on-surface-variant group-hover:text-primary transition-colors">chevron_right</span>
        </div>
    </a>
    <?php endif; ?>

    <!-- ── Card: Próximo Culto ── -->
    <?php if ($nextSchedule): ?>
    <div class="pib-card p-4 mb-4 reveal-item">
        <div class="flex items-center gap-2 mb-3">
            <span class="material-symbols-outlined text-[20px] text-primary">calendar_today</span>
            <h2 class="text-sm font-bold text-on-surface uppercase tracking-wide">Próximo Culto</h2>
            <span class="ml-auto text-[11px] font-bold px-2 py-0.5 rounded-full <?= statusBadge($myStatus ?? 'pending') ?>">
                <?= statusLabel($myStatus ?? 'pending') ?>
            </span>
        </div>

        <a href="/escalas/<?= $nextSchedule['id'] ?>" class="block mb-3">
            <div class="flex items-center gap-2 text-sm">
                <span class="material-symbols-outlined text-[16px] text-on-surface-variant">schedule</span>
                <span class="text-on-surface font-medium">
                    <?= date('d/m (D)', strtotime($nextSchedule['event_date'])) ?> · <?= substr($nextSchedule['event_time'], 0, 5) ?>
                </span>
            </div>
            <p class="text-xs text-on-surface-variant mt-1 ml-6">
                <?= htmlspecialchars($nextSchedule['event_type']) ?>
            </p>
        </a>

        <!-- Músicas do culto -->
        <?php if (!empty($nextSchedule['songs'])): ?>
        <div class="space-y-1.5 mb-4">
            <?php foreach ($nextSchedule['songs'] as $song): ?>
            <div class="flex items-center gap-2">
                <span class="material-symbols-outlined text-[16px] text-primary">play_circle</span>
                <span class="text-xs text-on-surface truncate"><?= htmlspecialchars($song['title']) ?></span>
                <?php if ($song['artist']): ?>
                <span class="text-[10px] text-on-surface-variant ml-auto flex-shrink-0"><?= htmlspecialchars($song['artist']) ?></span>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <!-- Botões Confirmar / Recusar (quando pendente ou recusado) -->
        <?php if ($scheduleUserId && in_array($myStatus, ['pending', 'declined', null])): ?>
        <form method="POST" action="/dashboard/presenca/<?= $scheduleUserId ?>" class="flex gap-2">
            <?= csrf_field() ?>
            <button type="submit" name="action" value="confirm"
                class="flex-1 flex items-center justify-center gap-1.5 text-xs font-bold py-2.5 rounded-xl bg-green-100 text-green-700 hover:bg-green-200 dark:bg-green-900/30 dark:text-green-400 transition-all active:scale-95">
                <span class="material-symbols-outlined text-[16px]">check_circle</span> Confirmar
            </button>
            <?php if ($myStatus !== 'declined'): ?>
            <button type="submit" name="action" value="decline"
                class="flex-1 flex items-center justify-center gap-1.5 text-xs font-bold py-2.5 rounded-xl bg-red-100 text-red-700 hover:bg-red-200 dark:bg-red-900/30 dark:text-red-400 transition-all active:scale-95">
                <span class="material-symbols-outlined text-[16px]">cancel</span> Recusar
            </button>
            <?php endif; ?>
        </form>
        <?php elseif ($myStatus === 'confirmed'): ?>
        <div class="flex items-center gap-2 text-xs text-green-600 font-semibold">
            <span class="material-symbols-outlined text-[16px]">check_circle</span>
            Você confirmou sua presença
        </div>
        <?php endif; ?>
    </div>

    <?php else: ?>
    <!-- Empty state: nenhuma escala próxima -->
    <div class="pib-card p-6 mb-4 text-center reveal-item">
        <span class="material-symbols-outlined text-[40px] text-on-surface-variant mb-3 block">event_available</span>
        <p class="text-sm font-medium text-on-surface">Nenhum culto agendado</p>
        <p class="text-xs text-on-surface-variant mt-1">Você não está em nenhuma escala futura.</p>
        <?php if ($userRole === 'admin'): ?>
        <a href="/escalas/nova" class="btn-primary inline-flex items-center gap-1.5 px-4 py-2.5 text-xs font-bold mt-4 transform active:scale-95">
            <span class="material-symbols-outlined text-[15px]">add</span> Nova Escala
        </a>
        <?php endif; ?>
    </div>
    <?php endif; ?>

    <!-- ── Atalhos rápidos ── -->
    <div class="grid grid-cols-2 gap-3 reveal-item">
        <a href="/escalas" class="pib-card p-4 flex items-center gap-3 hover:border-primary/30 transition-all group">
            <div class="w-9 h-9 rounded-xl flex items-center justify-center" style="background-color: rgba(46,126,237,0.12);">
                <span class="material-symbols-outlined text-[18px] text-primary">calendar_month</span>
            </div>
            <span class="text-sm font-semibold text-on-surface group-hover:text-primary transition-colors">Escalas</span>
        </a>
        <a href="/repertorio" class="pib-card p-4 flex items-center gap-3 hover:border-primary/30 transition-all group">
            <div class="w-9 h-9 rounded-xl flex items-center justify-center" style="background-color: rgba(46,126,237,0.12);">
                <span class="material-symbols-outlined text-[18px] text-primary">library_music</span>
            </div>
            <span class="text-sm font-semibold text-on-surface group-hover:text-primary transition-colors">Repertório</span>
        </a>
        <a href="/avisos" class="pib-card p-4 flex items-center gap-3 hover:border-primary/30 transition-all group">
            <div class="w-9 h-9 rounded-xl flex items-center justify-center" style="background-color: rgba(46,126,237,0.12);">
                <span class="material-symbols-outlined text-[18px] text-primary">campaign</span>
            </div>
            <span class="text-sm font-semibold text-on-surface group-hover:text-primary transition-colors">Avisos</span>
        </a>
        <a href="/oracao" class="pib-card p-4 flex items-center gap-3 hover:border-primary/30 transition-all group">
            <div class="w-9 h-9 rounded-xl flex items-center justify-center" style="background-color: rgba(46,126,237,0.12);">
                <span class="material-symbols-outlined text-[18px] text-primary">self_improvement</span>
            </div>
            <span class="text-sm font-semibold text-on-surface group-hover:text-primary transition-colors">Oração</span>
        </a>
    </div>
</main>

<?php require __DIR__ . '/../layouts/bottom-nav.php'; ?>
