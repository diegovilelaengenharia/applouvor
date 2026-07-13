<?php
$title = "Painel do Líder";
$bodyClass = "";
require __DIR__ . '/../layouts/head.php';
?>

<header class="sticky top-0 z-10 w-full max-w-lg mx-auto bg-surface border-b border-slate-100 dark:border-slate-800 flex items-center gap-3 px-4 py-3.5">
    <a href="/dashboard" class="text-on-surface-variant hover:text-primary transition-colors">
        <span class="material-symbols-outlined text-[22px]">arrow_back_ios_new</span>
    </a>
    <h1 class="text-lg font-bold text-on-surface flex-1">Painel do Líder</h1>
</header>

<main class="w-full max-w-lg mx-auto flex-grow px-4 pt-4 pb-10">

    <!-- Pendências -->
    <div class="mb-5">
        <p class="text-xs font-semibold text-on-surface-variant uppercase tracking-wider mb-3">Gestão do Ministério</p>
        <?php
        $pendencias = [
            ['icon' => 'lightbulb',   'label' => 'sugestão pendente',           'pluralLabel' => 'sugestões pendentes',   'value' => $pendingSugestoes,      'href' => '/sugestoes'],
            ['icon' => 'how_to_reg',  'label' => 'escala sem faltas registradas','pluralLabel' => 'escalas sem faltas',    'value' => $escalaSemFaltas,       'href' => '/escalas'],
            ['icon' => 'group_add',   'label' => 'confirmação pendente',         'pluralLabel' => 'confirmações pendentes','value' => $confirmacoesPendentes, 'href' => '/escalas'],
        ];
        $hasPendencias = array_filter($pendencias, fn($p) => $p['value'] > 0);
        ?>
        <?php if ($hasPendencias): ?>
        <div class="space-y-2">
            <?php foreach ($pendencias as $p): ?>
            <?php if ($p['value'] <= 0) continue; ?>
            <a href="<?= $p['href'] ?>"
               class="flex items-center gap-3 pib-card p-3.5 border-amber-200 dark:border-amber-800/50 hover:border-amber-300 transition-all reveal-item">
                <div class="w-10 h-10 rounded-xl bg-amber-100 dark:bg-amber-900/30 flex items-center justify-center flex-shrink-0">
                    <span class="material-symbols-outlined text-[20px] text-amber-600 dark:text-amber-400"><?= $p['icon'] ?></span>
                </div>
                <div class="flex-1">
                    <p class="text-sm font-medium text-on-surface">
                        <?= $p['value'] ?> <?= $p['value'] === 1 ? $p['label'] : $p['pluralLabel'] ?>
                    </p>
                </div>
                <span class="material-symbols-outlined text-[18px] text-on-surface-variant">chevron_right</span>
            </a>
            <?php endforeach; ?>
        </div>
        <?php else: ?>
        <div class="pib-card p-4 flex items-center gap-3">
            <span class="material-symbols-outlined text-[22px] text-emerald-500">check_circle</span>
            <p class="text-sm text-on-surface">Tudo em dia! Sem pendências no momento.</p>
        </div>
        <?php endif; ?>
    </div>

    <!-- Atalhos Rápidos -->
    <div>
        <p class="text-xs font-semibold text-on-surface-variant uppercase tracking-wider mb-3">Atalhos Rápidos</p>
        <?php
        $atalhos = [
            ['icon' => 'calendar_month', 'label' => 'Nova Escala',       'href' => '/escalas/nova'],
            ['icon' => 'music_note',     'label' => 'Nova Música',        'href' => '/musicas/nova'],
            ['icon' => 'campaign',       'label' => 'Novo Aviso',         'href' => '/avisos/novo'],
            ['icon' => 'how_to_reg',     'label' => 'Registrar Faltas',   'href' => '/escalas'],
            ['icon' => 'group',          'label' => 'Membros',            'href' => '/membros'],
            ['icon' => 'bar_chart',      'label' => 'Relatórios',         'href' => '/relatorios'],
            ['icon' => 'lightbulb',      'label' => 'Sugestões',          'href' => '/sugestoes'],
            ['icon' => 'event_busy',     'label' => 'Indisponibilidades', 'href' => '/indisponibilidades'],
        ];
        ?>
        <div class="grid grid-cols-2 gap-3">
            <?php foreach ($atalhos as $a): ?>
            <a href="<?= $a['href'] ?>"
               class="pib-card p-4 flex items-center gap-3 hover:border-primary/30 transition-all reveal-item">
                <div class="w-9 h-9 rounded-xl bg-primary/10 flex items-center justify-center flex-shrink-0">
                    <span class="material-symbols-outlined text-[18px] text-primary"><?= $a['icon'] ?></span>
                </div>
                <p class="text-sm font-medium text-on-surface leading-tight"><?= $a['label'] ?></p>
            </a>
            <?php endforeach; ?>
        </div>
    </div>
</main>

<script src="/assets/js/app.js"></script>
</body>
</html>
