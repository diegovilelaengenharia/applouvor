<?php
$title = "Notificações";
$bodyClass = "";
require __DIR__ . '/../layouts/head.php';

$filters = [
    'todas'    => ['label' => 'Todas',    'icon' => 'notifications'],
    'escalas'  => ['label' => 'Escalas',  'icon' => 'calendar_month'],
    'avisos'   => ['label' => 'Avisos',   'icon' => 'campaign'],
    'lembretes'=> ['label' => 'Lembretes','icon' => 'alarm'],
];

function notifIcon(string $type): string {
    return match(true) {
        str_starts_with($type, 'escala')   => 'calendar_month',
        str_starts_with($type, 'aviso')    => 'campaign',
        str_starts_with($type, 'musica')   => 'library_music',
        str_starts_with($type, 'lembrete') => 'alarm',
        default                            => 'notifications',
    };
}

function notifIconColor(string $type): string {
    return match(true) {
        str_starts_with($type, 'escala')   => 'text-blue-500',
        str_starts_with($type, 'aviso')    => 'text-orange-500',
        str_starts_with($type, 'musica')   => 'text-purple-500',
        str_starts_with($type, 'lembrete') => 'text-amber-500',
        default                            => 'text-primary',
    };
}

function timeAgo(string $datetime): string {
    $diff = time() - strtotime($datetime);
    if ($diff < 60)       return 'agora';
    if ($diff < 3600)     return 'há ' . round($diff/60) . 'min';
    if ($diff < 86400)    return 'há ' . round($diff/3600) . 'h';
    if ($diff < 604800)   return 'ontem';
    return date('d M', strtotime($datetime));
}

// Agrupar notificações: hoje vs esta semana
$hoje = [];
$semana = [];
foreach ($notifications as $n) {
    $date = strtotime($n['created_at']);
    if ($date >= strtotime('today')) {
        $hoje[] = $n;
    } else {
        $semana[] = $n;
    }
}
?>

<!-- Top bar -->
<header class="sticky top-0 z-10 w-full max-w-lg mx-auto bg-surface border-b border-slate-100 dark:border-slate-800 px-4 py-3.5">
    <div class="flex items-center gap-3 mb-3">
        <a href="/dashboard" class="text-on-surface-variant hover:text-primary transition-colors">
            <span class="material-symbols-outlined text-[22px]">arrow_back_ios_new</span>
        </a>
        <h1 class="text-lg font-bold text-on-surface flex-1">Notificações</h1>
        <?php if ($unreadCount > 0): ?>
        <form method="POST" action="/notificacoes/ler-todas" class="flex items-center">
            <?= csrf_field() ?>
            <button type="submit" class="text-xs font-semibold text-primary hover:underline">
                Marcar todas como lidas
            </button>
        </form>
        <?php endif; ?>
    </div>

    <!-- Filter chips -->
    <div class="flex gap-2 overflow-x-auto pb-0.5 hide-scrollbar">
        <?php foreach ($filters as $key => $f): ?>
        <a href="/notificacoes?tipo=<?= $key ?>"
           class="flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-semibold whitespace-nowrap transition-all
                  <?= $filter === $key
                      ? 'text-white' . ' ' . ''
                      : 'bg-slate-100 text-on-surface-variant hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700' ?>"
           <?= $filter === $key ? 'style="background-color: var(--primary);"' : '' ?>>
            <span class="material-symbols-outlined text-[13px]"><?= $f['icon'] ?></span>
            <?= $f['label'] ?>
        </a>
        <?php endforeach; ?>
    </div>
</header>

<main class="w-full max-w-lg mx-auto flex-grow px-4 pt-4 pb-10">
    <?php require __DIR__ . '/../layouts/flash.php'; ?>

    <?php if (empty($notifications)): ?>
    <!-- Empty state -->
    <div class="flex flex-col items-center justify-center text-center py-16">
        <span class="material-symbols-outlined text-[56px] text-on-surface-variant mb-4">notifications_off</span>
        <p class="text-base font-semibold text-on-surface">Tudo em dia!</p>
        <p class="text-sm text-on-surface-variant mt-1">Nenhuma notificação no momento.</p>
    </div>

    <?php else: ?>

    <?php
    function renderNotifGroup(array $items, string $groupLabel): void {
        if (empty($items)) return;
        ?>
        <div class="mb-2">
            <p class="text-xs font-bold text-on-surface-variant uppercase tracking-widest mb-2 mt-4"><?= $groupLabel ?></p>
            <div class="space-y-2">
                <?php foreach ($items as $n): ?>
                <div class="pib-card p-3.5 flex items-start gap-3 reveal-item <?= !$n['is_read'] ? 'border-primary/20' : '' ?>">
                    <!-- Ícone de tipo -->
                    <div class="w-9 h-9 rounded-xl flex items-center justify-center flex-shrink-0"
                         style="background-color: rgba(46,126,237,0.10);">
                        <span class="material-symbols-outlined text-[18px] <?= notifIconColor($n['type']) ?>">
                            <?= notifIcon($n['type']) ?>
                        </span>
                    </div>

                    <!-- Conteúdo -->
                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-semibold text-on-surface leading-snug <?= !$n['is_read'] ? '' : 'text-on-surface-variant' ?>">
                            <?= htmlspecialchars($n['title']) ?>
                        </p>
                        <?php if ($n['body']): ?>
                        <p class="text-xs text-on-surface-variant mt-0.5 line-clamp-2">
                            <?= htmlspecialchars($n['body']) ?>
                        </p>
                        <?php endif; ?>
                        <p class="text-[10px] text-on-surface-variant mt-1"><?= timeAgo($n['created_at']) ?></p>
                    </div>

                    <!-- Bolinha de não-lido -->
                    <?php if (!$n['is_read']): ?>
                    <form method="POST" action="/notificacoes/<?= $n['id'] ?>/ler" class="flex-shrink-0">
                        <?= csrf_field() ?>
                        <input type="hidden" name="redirect" value="/notificacoes">
                        <button type="submit" class="w-2.5 h-2.5 rounded-full bg-primary mt-1.5 hover:scale-125 transition-transform" title="Marcar como lida"></button>
                    </form>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php
    }

    renderNotifGroup($hoje, 'Hoje');
    renderNotifGroup($semana, 'Esta Semana');
    ?>

    <?php endif; ?>
</main>

<script src="/assets/js/app.js"></script>
</body>
</html>
