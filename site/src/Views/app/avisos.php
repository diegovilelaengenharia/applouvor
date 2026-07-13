<?php
$title = "Avisos";
$bodyClass = "";
require __DIR__ . '/../layouts/head.php';

function avisosBadgeClass(string $p): string {
    return match($p) {
        'urgente' => 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400',
        'alta'    => 'bg-orange-100 text-orange-700 dark:bg-orange-900/30 dark:text-orange-400',
        'baixa'   => 'bg-slate-100 text-slate-600 dark:bg-slate-700 dark:text-slate-300',
        default   => 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400',
    };
}
function avisosBadgeLabel(string $p): string {
    return match($p) { 'urgente'=>'URGENTE','alta'=>'IMPORTANTE','baixa'=>'INFO', default=>'INFO' };
}
?>

<!-- Top bar (secundária) -->
<header class="sticky top-0 z-10 w-full max-w-lg mx-auto bg-surface border-b border-slate-100 dark:border-slate-800 flex items-center gap-3 px-4 py-3.5">
    <a href="/dashboard" class="text-on-surface-variant hover:text-primary transition-colors">
        <span class="material-symbols-outlined text-[22px]">arrow_back_ios_new</span>
    </a>
    <h1 class="text-lg font-bold text-on-surface flex-1">Avisos</h1>
    <span class="material-symbols-outlined text-[22px] text-on-surface-variant">inventory_2</span>
</header>

<main class="w-full max-w-lg mx-auto flex-grow px-4 pt-4 pb-10">
    <?php require __DIR__ . '/../layouts/flash.php'; ?>

    <?php if (empty($avisos)): ?>
    <!-- Empty state -->
    <div class="flex flex-col items-center justify-center text-center py-16">
        <span class="material-symbols-outlined text-[56px] text-on-surface-variant mb-4">campaign</span>
        <p class="text-base font-semibold text-on-surface">Nenhum aviso no momento</p>
        <p class="text-sm text-on-surface-variant mt-1">Fique de olho! Novidades em breve.</p>
    </div>
    <?php else: ?>
    <div class="space-y-3">
        <?php foreach ($avisos as $av): ?>
        <a href="/avisos/<?= $av['id'] ?>" class="block pib-card p-4 reveal-item group hover:border-primary/30 transition-all">
            <!-- Badge + Fixado -->
            <div class="flex items-center gap-2 mb-2.5">
                <span class="text-[10px] font-bold uppercase tracking-wider px-2 py-0.5 rounded-full <?= avisosBadgeClass($av['prioridade']) ?>">
                    <?= avisosBadgeLabel($av['prioridade']) ?>
                </span>
                <?php if ($av['fixado']): ?>
                <span class="material-symbols-outlined text-[14px] text-amber-500">push_pin</span>
                <?php endif; ?>
                <span class="ml-auto text-xs text-on-surface-variant">
                    <?= date('d M', strtotime($av['created_at'])) ?>
                </span>
            </div>

            <!-- Título -->
            <div class="flex items-start gap-2.5 mb-2">
                <span class="material-symbols-outlined text-[18px] text-primary flex-shrink-0 mt-0.5">campaign</span>
                <p class="text-sm font-semibold text-on-surface leading-snug line-clamp-2">
                    <?= htmlspecialchars($av['titulo']) ?>
                </p>
            </div>

            <!-- Descrição -->
            <?php if ($av['conteudo']): ?>
            <p class="text-xs text-on-surface-variant line-clamp-2 ml-7 mb-3">
                <?= htmlspecialchars(strip_tags($av['conteudo'])) ?>
            </p>
            <?php endif; ?>

            <!-- Rodapé: autor + reações -->
            <div class="flex items-center gap-2 ml-7">
                <div class="w-5 h-5 rounded-full flex items-center justify-center text-[9px] font-bold text-white flex-shrink-0" style="background-color: var(--primary);">
                    <?= mb_strtoupper(mb_substr($av['author_name'] ?? 'L', 0, 1)) ?>
                </div>
                <span class="text-[11px] text-on-surface-variant">Liderança</span>
                <div class="ml-auto flex items-center gap-3">
                    <span class="text-[11px] text-on-surface-variant flex items-center gap-1">👍 0</span>
                    <span class="text-[11px] text-on-surface-variant flex items-center gap-1">🙏 0</span>
                </div>
            </div>
        </a>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
</main>

<!-- FAB: Novo Aviso (somente admin) -->
<?php if (($_SESSION['user_role'] ?? '') === 'admin'): ?>
<a href="/avisos/novo"
   class="fixed bottom-6 right-4 w-14 h-14 rounded-2xl shadow-lg flex items-center justify-center transform hover:scale-105 active:scale-95 transition-all z-20"
   style="background-color: var(--primary);">
    <span class="material-symbols-outlined text-[24px] text-white">add</span>
</a>
<?php endif; ?>

<script src="/assets/js/app.js"></script>
</body>
</html>
