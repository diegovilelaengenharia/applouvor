<?php
$title = "Relatórios";
$bodyClass = "";
require __DIR__ . '/../layouts/head.php';

$periodoLabels = ['7d' => '7 dias', '1m' => '1 mês', '3m' => '3 meses'];
?>

<header class="sticky top-0 z-10 w-full max-w-lg mx-auto bg-surface border-b border-slate-100 dark:border-slate-800 flex items-center gap-3 px-4 py-3.5">
    <a href="/lider" class="text-on-surface-variant hover:text-primary transition-colors">
        <span class="material-symbols-outlined text-[22px]">arrow_back_ios_new</span>
    </a>
    <h1 class="text-lg font-bold text-on-surface flex-1">Visão Geral</h1>
    <span class="material-symbols-outlined text-[22px] text-on-surface-variant">filter_list</span>
</header>

<main class="w-full max-w-lg mx-auto flex-grow px-4 pt-4 pb-10">
    <?php require __DIR__ . '/../layouts/flash.php'; ?>

    <!-- Filtro de período -->
    <div class="flex gap-2 mb-5">
        <?php foreach ($periodoLabels as $k => $l): ?>
        <a href="/relatorios?periodo=<?= $k ?>"
           class="flex-1 text-center py-2 rounded-xl text-xs font-semibold border transition-colors <?= $period === $k ? 'bg-primary text-white border-primary' : 'border-slate-300 dark:border-slate-600 text-on-surface-variant hover:border-primary/50' ?>">
            <?= $l ?>
        </a>
        <?php endforeach; ?>
    </div>

    <!-- KPI Grid -->
    <?php
    $kpis = [
        ['icon' => 'event_available', 'label' => 'Escalas',            'value' => $totalEscalas],
        ['icon' => 'music_note',      'label' => 'Músicas tocadas',    'value' => $totalMusicasTocadas],
        ['icon' => 'groups',          'label' => 'Membros escalados',  'value' => "{$totalMembrosEscalados}/{$totalMembros}"],
        ['icon' => 'analytics',       'label' => 'Presença média',     'value' => "{$totalPresencaMedia}%"],
        ['icon' => 'event_busy',      'label' => 'Faltas',             'value' => $totalFaltas],
        ['icon' => 'block',           'label' => 'Indisponibilidades', 'value' => $totalIndisponibilidades],
    ];
    ?>
    <div class="grid grid-cols-2 gap-3 mb-5">
        <?php foreach ($kpis as $k): ?>
        <div class="pib-card p-4 flex items-center gap-3 reveal-item">
            <div class="w-10 h-10 rounded-xl bg-primary/10 flex items-center justify-center flex-shrink-0">
                <span class="material-symbols-outlined text-[20px] text-primary"><?= $k['icon'] ?></span>
            </div>
            <div>
                <p class="text-xl font-bold text-on-surface"><?= $k['value'] ?></p>
                <p class="text-xs text-on-surface-variant leading-tight"><?= $k['label'] ?></p>
            </div>
        </div>
        <?php endforeach; ?>
    </div>

    <!-- Top Músicas -->
    <div class="pib-card p-4 mb-4">
        <p class="text-xs font-semibold text-on-surface-variant uppercase tracking-wider mb-3">Mais Tocadas</p>
        <?php if ($topMusicasRows): ?>
        <div class="space-y-3">
            <?php foreach ($topMusicasRows as $i => $m): ?>
            <div class="flex items-center gap-3 reveal-item">
                <span class="w-6 h-6 rounded-full flex items-center justify-center text-xs font-bold flex-shrink-0 <?= $i === 0 ? 'bg-primary text-white' : 'bg-surface-variant text-on-surface-variant' ?>">
                    <?= $i + 1 ?>
                </span>
                <div class="flex-1 min-w-0">
                    <p class="text-sm font-medium text-on-surface truncate"><?= htmlspecialchars($m['title']) ?></p>
                    <?php if ($m['artist']): ?>
                    <p class="text-xs text-on-surface-variant"><?= htmlspecialchars($m['artist']) ?></p>
                    <?php endif; ?>
                </div>
                <span class="text-xs text-on-surface-variant flex-shrink-0"><?= $m['vezes'] ?>×</span>
            </div>
            <?php endforeach; ?>
        </div>
        <?php else: ?>
        <p class="text-sm text-on-surface-variant text-center py-4">Nenhum dado neste período.</p>
        <?php endif; ?>
    </div>

    <!-- Exportar -->
    <button onclick="alert('Exportação disponível em breve.')"
            class="btn-outline w-full flex items-center justify-center gap-2">
        <span class="material-symbols-outlined text-[18px]">download</span>
        Exportar CSV
    </button>
</main>

<script src="/assets/js/app.js"></script>
</body>
</html>
