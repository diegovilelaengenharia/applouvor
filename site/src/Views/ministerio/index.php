<?php
$title = "Ministério";
$bodyClass = "";
require __DIR__ . '/../layouts/head.php';

$isAdmin = ($_SESSION['user_role'] ?? '') === 'admin';
$menuItems = [
    ['icon' => 'person_add',          'label' => 'Convidar Membros',  'href' => '/membros/convidar'],
    ['icon' => 'group',               'label' => 'Membros',           'href' => '/membros'],
    ['icon' => 'badge',               'label' => 'Funções',           'href' => '#'],
    ['icon' => 'label',               'label' => 'Classificações',    'href' => '#'],
    ['icon' => 'admin_panel_settings','label' => 'Administradores',   'href' => '#'],
    ['icon' => 'description',         'label' => 'Modelos de Roteiro','href' => '#'],
];
?>

<header class="sticky top-0 z-10 w-full max-w-lg mx-auto bg-surface border-b border-slate-100 dark:border-slate-800 flex items-center gap-3 px-4 py-3.5">
    <a href="/dashboard" class="text-on-surface-variant hover:text-primary transition-colors">
        <span class="material-symbols-outlined text-[22px]">arrow_back_ios_new</span>
    </a>
    <h1 class="text-lg font-bold text-on-surface flex-1">Ministério</h1>
</header>

<main class="w-full max-w-lg mx-auto flex-grow px-4 pt-4 pb-10">

    <!-- Banner -->
    <div class="rounded-2xl p-5 mb-4 reveal-item" style="background: linear-gradient(135deg, var(--primary) 0%, #1a5abf 100%);">
        <div class="flex items-center gap-3 mb-3">
            <div class="w-12 h-12 rounded-xl bg-white/20 flex items-center justify-center">
                <span class="material-symbols-outlined text-[24px] text-white">groups</span>
            </div>
            <div>
                <p class="text-base font-bold text-white">Louvor PIB Oliveira</p>
                <p class="text-xs text-white/80"><?= $totalMembros ?> membro<?= $totalMembros !== 1 ? 's' : '' ?> ativo<?= $totalMembros !== 1 ? 's' : '' ?></p>
            </div>
        </div>
    </div>

    <!-- Sobre -->
    <div class="pib-card p-4 mb-4 reveal-item">
        <p class="text-xs font-semibold text-on-surface-variant uppercase tracking-wider mb-2">Sobre</p>
        <p class="text-sm text-on-surface leading-relaxed">
            Nossa missão é conduzir a congregação a um encontro genuíno com o Criador através da excelência musical e da profundidade espiritual.
        </p>
        <div class="flex gap-6 mt-3 pt-3 border-t border-slate-100 dark:border-slate-800">
            <div>
                <p class="text-[11px] text-on-surface-variant">Fundação</p>
                <p class="text-sm font-semibold text-on-surface">2014</p>
            </div>
            <div>
                <p class="text-[11px] text-on-surface-variant">Cultos/semana</p>
                <p class="text-sm font-semibold text-on-surface">3</p>
            </div>
        </div>
    </div>

    <!-- Menu de gerenciamento (admin) -->
    <?php if ($isAdmin): ?>
    <div class="pib-card divide-y divide-slate-100 dark:divide-slate-800 reveal-item">
        <p class="text-xs font-semibold text-on-surface-variant uppercase tracking-wider px-4 py-3">Gerenciamento</p>
        <?php foreach ($menuItems as $item): ?>
        <a href="<?= $item['href'] ?>"
           class="flex items-center gap-3 px-4 py-3.5 hover:bg-surface-variant/50 transition-colors">
            <div class="w-9 h-9 rounded-xl bg-primary/10 flex items-center justify-center flex-shrink-0">
                <span class="material-symbols-outlined text-[18px] text-primary"><?= $item['icon'] ?></span>
            </div>
            <p class="text-sm font-medium text-on-surface flex-1"><?= $item['label'] ?></p>
            <span class="material-symbols-outlined text-[18px] text-on-surface-variant">chevron_right</span>
        </a>
        <?php endforeach; ?>
    </div>
    <?php else: ?>
    <div class="pib-card p-4 reveal-item">
        <div class="flex items-center gap-3">
            <span class="material-symbols-outlined text-[20px] text-primary">church</span>
            <p class="text-sm text-on-surface">Entre em contato com a liderança para mais informações sobre o ministério.</p>
        </div>
    </div>
    <?php endif; ?>

    <div class="flex items-center justify-center gap-2 mt-6 text-on-surface-variant">
        <span class="material-symbols-outlined text-[15px]">church</span>
        <p class="text-xs">Soli Deo Gloria</p>
    </div>
</main>

<script src="/assets/js/app.js"></script>
</body>
</html>
