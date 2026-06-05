<?php
$title = htmlspecialchars($membro['name']);
$bodyClass = "";
require __DIR__ . '/../layouts/head.php';

$instrumentos = array_filter(array_map('trim', explode('/', $membro['instrument'] ?? '')));

function mShowStatusClass(string $s): string {
    return match($s) {
        'confirmed' => 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400',
        'declined'  => 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400',
        default     => 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400',
    };
}
function mShowStatusLabel(string $s): string {
    return match($s) { 'confirmed' => 'Confirmado', 'declined' => 'Recusou', default => 'Pendente' };
}
function mShowStatusIcon(string $s): string {
    return match($s) { 'confirmed' => 'check_circle', 'declined' => 'cancel', default => 'schedule' };
}
?>

<header class="sticky top-0 z-10 w-full max-w-lg mx-auto bg-surface border-b border-slate-100 dark:border-slate-800 flex items-center gap-3 px-4 py-3.5">
    <a href="/membros" class="text-on-surface-variant hover:text-primary transition-colors">
        <span class="material-symbols-outlined text-[22px]">arrow_back_ios_new</span>
    </a>
    <h1 class="text-lg font-bold text-on-surface flex-1">Perfil do Membro</h1>
</header>

<main class="w-full max-w-lg mx-auto flex-grow px-4 pt-6 pb-10">
    <?php require __DIR__ . '/../layouts/flash.php'; ?>

    <!-- Avatar + nome + métricas -->
    <div class="flex flex-col items-center mb-6">
        <div class="w-20 h-20 rounded-full flex items-center justify-center text-2xl font-bold text-white mb-3"
             style="background-color: <?= htmlspecialchars($membro['avatar_color'] ?? '#2E7EED') ?>;">
            <?= mb_strtoupper(mb_substr($membro['name'], 0, 1)) ?>
        </div>
        <?php if ($membro['role'] === 'admin'): ?>
        <span class="text-[11px] font-bold px-2 py-0.5 rounded-full bg-primary/10 text-primary mb-2">Admin</span>
        <?php endif; ?>
        <h2 class="text-xl font-bold text-on-surface"><?= htmlspecialchars($membro['name']) ?></h2>
        <p class="text-sm text-on-surface-variant mt-0.5"><?= htmlspecialchars($membro['instrument'] ?? '—') ?></p>

        <div class="flex items-center gap-8 mt-4">
            <div class="text-center">
                <p class="text-2xl font-bold text-primary"><?= (int)($membro['presenca_pct'] ?? 0) ?>%</p>
                <p class="text-xs text-on-surface-variant">Presença</p>
            </div>
            <div class="w-px h-10 bg-slate-200 dark:bg-slate-700"></div>
            <div class="text-center">
                <p class="text-2xl font-bold text-on-surface"><?= (int)($membro['total_escalas'] ?? 0) ?></p>
                <p class="text-xs text-on-surface-variant">Escalas</p>
            </div>
        </div>
    </div>

    <!-- Instrumentos -->
    <?php if ($instrumentos): ?>
    <div class="pib-card p-4 mb-4">
        <p class="text-xs font-semibold text-on-surface-variant uppercase tracking-wider mb-3">Instrumentos</p>
        <div class="flex flex-wrap gap-2">
            <?php foreach ($instrumentos as $inst): ?>
            <span class="flex items-center gap-1.5 px-3 py-1.5 rounded-full bg-surface-variant text-sm text-on-surface">
                <span class="material-symbols-outlined text-[15px] text-primary">music_note</span>
                <?= htmlspecialchars($inst) ?>
            </span>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

    <!-- Próximas Escalas -->
    <?php if ($proximasEscalas): ?>
    <div class="pib-card p-4 mb-4">
        <div class="flex items-center justify-between mb-3">
            <p class="text-xs font-semibold text-on-surface-variant uppercase tracking-wider">Próximas Escalas</p>
            <a href="/escalas" class="text-xs text-primary font-medium">Ver todas</a>
        </div>
        <div class="space-y-3">
            <?php foreach ($proximasEscalas as $e): ?>
            <div class="flex items-center gap-3">
                <div class="text-center min-w-[44px]">
                    <p class="text-[10px] font-bold text-primary uppercase"><?= date('M', strtotime($e['date'])) ?></p>
                    <p class="text-xl font-bold text-on-surface leading-none"><?= date('d', strtotime($e['date'])) ?></p>
                </div>
                <div class="flex-1 min-w-0">
                    <p class="text-sm font-medium text-on-surface truncate"><?= htmlspecialchars($e['title']) ?></p>
                    <p class="text-xs text-on-surface-variant"><?= date('l', strtotime($e['date'])) ?> <?= substr($e['time'] ?? '', 0, 5) ?></p>
                </div>
                <span class="flex items-center gap-1 text-[11px] font-medium px-2 py-1 rounded-full flex-shrink-0 <?= mShowStatusClass($e['status']) ?>">
                    <span class="material-symbols-outlined text-[13px]"><?= mShowStatusIcon($e['status']) ?></span>
                    <?= mShowStatusLabel($e['status']) ?>
                </span>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

    <!-- Contato -->
    <?php if ($membro['phone'] || $membro['email']): ?>
    <div class="pib-card p-4 mb-4">
        <p class="text-xs font-semibold text-on-surface-variant uppercase tracking-wider mb-3">Contato</p>
        <div class="space-y-2.5">
            <?php if ($membro['phone']): ?>
            <a href="https://wa.me/55<?= preg_replace('/\D/', '', $membro['phone']) ?>" target="_blank"
               class="flex items-center gap-3 text-sm text-on-surface hover:text-primary transition-colors">
                <span class="material-symbols-outlined text-[18px] text-primary">phone</span>
                <?= htmlspecialchars($membro['phone']) ?>
            </a>
            <?php endif; ?>
            <?php if ($membro['email']): ?>
            <div class="flex items-center gap-3 text-sm text-on-surface">
                <span class="material-symbols-outlined text-[18px] text-primary">mail</span>
                <?= htmlspecialchars($membro['email']) ?>
            </div>
            <?php endif; ?>
        </div>
    </div>
    <?php endif; ?>

    <!-- Enviar Mensagem -->
    <a href="/mensagens" class="btn-outline w-full flex items-center justify-center gap-2">
        <span class="material-symbols-outlined text-[18px]">send</span>
        Enviar Mensagem
    </a>
</main>

<script src="/assets/js/app.js"></script>
</body>
</html>
