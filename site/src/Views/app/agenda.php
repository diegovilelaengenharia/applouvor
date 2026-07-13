<?php
$title     = 'Agenda';
$bodyClass = '';
require __DIR__ . '/../layouts/head.php';

$dayNames   = ['Dom','Seg','Ter','Qua','Qui','Sex','Sáb'];
$monthNames = [
    1=>'Janeiro',2=>'Fevereiro',3=>'Março',4=>'Abril',
    5=>'Maio',6=>'Junho',7=>'Julho',8=>'Agosto',
    9=>'Setembro',10=>'Outubro',11=>'Novembro',12=>'Dezembro'
];

$today = date('Y-m-d');
?>

<header class="sticky top-0 z-10 w-full max-w-lg mx-auto bg-surface border-b border-slate-100 dark:border-slate-800 flex items-center gap-3 px-4 py-3.5">
    <a href="/dashboard" class="text-on-surface-variant hover:text-primary transition-colors">
        <span class="material-symbols-outlined text-[22px]">arrow_back_ios_new</span>
    </a>
    <h1 class="text-lg font-bold text-on-surface flex-1">Agenda</h1>
    <a href="/escalas/nova"
       class="w-9 h-9 rounded-xl bg-primary/10 flex items-center justify-center text-primary hover:bg-primary/20 transition-colors">
        <span class="material-symbols-outlined text-[18px]">add</span>
    </a>
</header>

<!-- Filter chips -->
<div class="sticky top-[57px] z-10 w-full max-w-lg mx-auto bg-surface border-b border-slate-100 dark:border-slate-800 px-4 py-2.5 flex gap-2">
    <a href="?filtro=proximos"
       class="px-4 py-1.5 rounded-xl text-sm font-semibold transition-colors flex-shrink-0
              <?= $filter === 'proximos' ? 'bg-primary text-white' : 'bg-slate-100 dark:bg-slate-800 text-on-surface-variant hover:bg-slate-200 dark:hover:bg-slate-700' ?>">
        Próximos
    </a>
    <a href="?filtro=passados"
       class="px-4 py-1.5 rounded-xl text-sm font-semibold transition-colors flex-shrink-0
              <?= $filter === 'passados' ? 'bg-primary text-white' : 'bg-slate-100 dark:bg-slate-800 text-on-surface-variant hover:bg-slate-200 dark:hover:bg-slate-700' ?>">
        Passados
    </a>
</div>

<main class="w-full max-w-lg mx-auto flex-grow px-4 pt-4 pb-10">

    <?php if (empty($grouped)): ?>
    <div class="flex flex-col items-center justify-center py-20 text-center">
        <span class="material-symbols-outlined text-[48px] text-on-surface-variant mb-3">
            <?= $filter === 'proximos' ? 'event_available' : 'event_note' ?>
        </span>
        <p class="text-sm font-medium text-on-surface mb-1">
            <?= $filter === 'proximos' ? 'Nenhum culto agendado' : 'Nenhum culto no histórico' ?>
        </p>
        <?php if ($filter === 'proximos'): ?>
        <a href="/escalas/nova" class="btn-primary mt-4 px-6 py-2.5 text-sm flex items-center gap-2">
            <span class="material-symbols-outlined text-[16px]">add</span>
            Criar escala
        </a>
        <?php endif; ?>
    </div>
    <?php else: ?>

    <div class="flex flex-col gap-6">
        <?php foreach ($grouped as $monthKey => $events): ?>
        <?php
        [$year, $month] = explode('-', $monthKey);
        $monthLabel = ($monthNames[(int)$month] ?? $month) . ' ' . $year;
        ?>

        <div>
            <!-- Month header -->
            <div class="flex items-center gap-3 mb-3">
                <div class="flex-1 h-px bg-slate-100 dark:bg-slate-800"></div>
                <p class="text-xs font-semibold text-on-surface-variant uppercase tracking-wider px-2">
                    <?= $monthLabel ?>
                </p>
                <div class="flex-1 h-px bg-slate-100 dark:bg-slate-800"></div>
            </div>

            <!-- Event cards -->
            <div class="flex flex-col gap-2">
                <?php foreach ($events as $event): ?>
                <?php
                $dt       = new DateTime($event['event_date']);
                $dayIdx   = (int)$dt->format('w');
                $dayName  = $dayNames[$dayIdx];
                $dateStr  = $dt->format('d/m');
                $timeStr  = $event['event_time'] ? substr($event['event_time'], 0, 5) : '';
                $isToday  = $event['event_date'] === $today;
                $isPast   = $event['event_date'] < $today;
                ?>
                <a href="/escalas/<?= (int)$event['id'] ?>"
                   class="pib-card p-4 flex items-center gap-4 hover:border-primary/30 transition-all reveal-item
                          <?= $isToday ? 'border-primary bg-primary/5' : '' ?>
                          <?= $isPast ? 'opacity-70' : '' ?>">

                    <!-- Date bubble -->
                    <div class="flex flex-col items-center justify-center w-12 flex-shrink-0">
                        <span class="text-[10px] font-bold uppercase <?= $isToday ? 'text-primary' : 'text-on-surface-variant' ?>">
                            <?= $dayName ?>
                        </span>
                        <span class="text-2xl font-bold <?= $isToday ? 'text-primary' : 'text-on-surface' ?> leading-tight">
                            <?= $dt->format('d') ?>
                        </span>
                        <?php if ($isToday): ?>
                        <span class="text-[9px] font-bold text-primary uppercase">Hoje</span>
                        <?php endif; ?>
                    </div>

                    <div class="w-px h-10 bg-slate-100 dark:bg-slate-800 flex-shrink-0"></div>

                    <!-- Info -->
                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-semibold text-on-surface truncate">
                            <?= htmlspecialchars($event['event_type'] ?? 'Culto') ?>
                        </p>
                        <div class="flex items-center gap-2 mt-0.5">
                            <?php if ($timeStr): ?>
                            <span class="text-xs text-on-surface-variant"><?= $timeStr ?></span>
                            <span class="text-on-surface-variant text-xs">·</span>
                            <?php endif; ?>
                            <?php if ($event['total_musicas'] > 0): ?>
                            <span class="text-xs text-on-surface-variant flex items-center gap-0.5">
                                <span class="material-symbols-outlined text-[12px]">music_note</span>
                                <?= (int)$event['total_musicas'] ?>
                            </span>
                            <?php endif; ?>
                            <?php if ($event['total_membros'] > 0): ?>
                            <span class="text-xs text-on-surface-variant flex items-center gap-0.5">
                                <span class="material-symbols-outlined text-[12px]">person</span>
                                <?= (int)$event['total_membros'] ?>
                            </span>
                            <?php endif; ?>
                        </div>
                    </div>

                    <span class="material-symbols-outlined text-[18px] text-on-surface-variant flex-shrink-0">chevron_right</span>
                </a>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endforeach; ?>
    </div>

    <?php endif; ?>

</main>

<script src="/assets/js/app.js"></script>
</body>
</html>
