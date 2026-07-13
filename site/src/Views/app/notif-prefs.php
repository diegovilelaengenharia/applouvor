<?php
$title = "Notificações";
$activeNav = "perfil";

// Helper: liga por padrão se a chave nunca foi salva
$on = fn(string $k) => (($settings[$k] ?? '1') === '1');
$paused = (($settings['notif_paused'] ?? '0') === '1');
$channel = $settings['notif_channel'] ?? 'push';

require __DIR__ . '/../layouts/head.php';
require __DIR__ . '/../layouts/top-app-bar.php';
?>

<main class="w-full max-w-lg mx-auto flex-grow px-4 pb-24">
    <?php require __DIR__ . '/../layouts/flash.php'; ?>

    <div class="mb-6">
        <a href="/configuracoes" class="text-primary text-sm font-bold flex items-center gap-1 mb-4">
            <span class="material-symbols-outlined text-[18px]">arrow_back</span> Voltar
        </a>
        <h1 class="text-2xl font-bold text-on-surface">Notificações</h1>
    </div>

    <form action="/configuracoes/notificacoes" method="POST" class="space-y-6">
        <?= csrf_field() ?>

        <!-- Master + canal -->
        <div class="pib-card p-4 space-y-4">
            <div class="flex items-center justify-between">
                <span class="flex items-center gap-3 text-sm font-bold text-on-surface"><span class="material-symbols-outlined text-[20px] text-primary">notifications_paused</span> Pausar todas</span>
                <label class="relative inline-flex items-center cursor-pointer">
                    <input type="checkbox" name="notif_paused" value="1" class="sr-only peer" <?= $paused ? 'checked' : '' ?>>
                    <div class="w-11 h-6 bg-slate-200 rounded-full peer-checked:bg-primary transition-colors after:content-[''] after:absolute after:top-0.5 after:left-0.5 after:bg-white after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:after:translate-x-5"></div>
                </label>
            </div>
            <div class="flex items-center justify-between border-t border-slate-100 pt-4">
                <span class="text-sm font-semibold text-on-surface">Canal</span>
                <div class="flex bg-slate-100 rounded-full p-1 text-xs font-bold">
                    <label class="cursor-pointer">
                        <input type="radio" name="notif_channel" value="push" class="sr-only peer" <?= $channel === 'push' ? 'checked' : '' ?>>
                        <span class="block px-4 py-1.5 rounded-full peer-checked:bg-primary peer-checked:text-on-primary text-on-surface-variant">Push</span>
                    </label>
                    <label class="cursor-pointer">
                        <input type="radio" name="notif_channel" value="email" class="sr-only peer" <?= $channel === 'email' ? 'checked' : '' ?>>
                        <span class="block px-4 py-1.5 rounded-full peer-checked:bg-primary peer-checked:text-on-primary text-on-surface-variant">E-mail</span>
                    </label>
                </div>
            </div>
        </div>

        <!-- Grupos de toggles -->
        <?php foreach ($groups as $groupName => $items): ?>
            <div>
                <p class="text-xs font-bold uppercase tracking-wider text-on-surface-variant mb-2 ml-1"><?= htmlspecialchars($groupName) ?></p>
                <div class="pib-card overflow-hidden">
                    <?php $i = 0; $count = count($items); foreach ($items as $key => $label): ?>
                        <div class="flex items-center justify-between px-4 py-3.5 <?= ++$i < $count ? 'border-b border-slate-100' : '' ?>">
                            <div class="pr-3">
                                <p class="text-sm font-semibold text-on-surface"><?= htmlspecialchars($label[0]) ?></p>
                                <p class="text-xs text-on-surface-variant"><?= htmlspecialchars($label[1]) ?></p>
                            </div>
                            <label class="relative inline-flex items-center cursor-pointer shrink-0">
                                <input type="checkbox" name="<?= htmlspecialchars($key) ?>" value="1" class="sr-only peer" <?= $on($key) ? 'checked' : '' ?>>
                                <div class="w-11 h-6 bg-slate-200 rounded-full peer-checked:bg-primary transition-colors after:content-[''] after:absolute after:top-0.5 after:left-0.5 after:bg-white after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:after:translate-x-5"></div>
                            </label>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endforeach; ?>

        <button type="submit" class="btn-primary w-full py-4 text-sm font-bold shadow-sm flex items-center justify-center gap-2 transform active:scale-95">
            <span>Salvar Preferências</span>
            <span class="material-symbols-outlined text-[20px]">save</span>
        </button>
    </form>
</main>

<?php require __DIR__ . '/../layouts/bottom-nav.php'; ?>
