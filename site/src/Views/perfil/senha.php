<?php
$title = "Alterar Senha";
$activeNav = "perfil";
require __DIR__ . '/../layouts/head.php';
require __DIR__ . '/../layouts/top-app-bar.php';
?>

<main class="w-full max-w-lg mx-auto flex-grow px-4 pb-24">
    <?php require __DIR__ . '/../layouts/flash.php'; ?>

    <div class="mb-6">
        <a href="/perfil" class="text-primary text-sm font-bold flex items-center gap-1 mb-4">
            <span class="material-symbols-outlined text-[18px]">arrow_back</span> Voltar
        </a>
        <h1 class="text-2xl font-bold text-on-surface">Alterar Senha</h1>
        <p class="text-sm text-on-surface-variant mt-1">Use uma senha forte e que você não use em outros sites.</p>
    </div>

    <form action="/perfil/senha" method="POST" class="space-y-6">
        <?= csrf_field() ?>

        <div class="pib-card p-6 space-y-4">
            <div>
                <label class="block text-xs font-bold text-on-surface-variant uppercase tracking-wider mb-2 ml-1">Senha atual</label>
                <input type="password" name="current_password" required autocomplete="current-password"
                       class="w-full bg-slate-50 border border-slate-200 rounded-[16px] px-4 py-3.5 text-on-surface input-glow">
            </div>
            <div>
                <label class="block text-xs font-bold text-on-surface-variant uppercase tracking-wider mb-2 ml-1">Nova senha</label>
                <input type="password" name="new_password" required minlength="8" autocomplete="new-password"
                       class="w-full bg-slate-50 border border-slate-200 rounded-[16px] px-4 py-3.5 text-on-surface input-glow">
                <p class="text-[11px] text-on-surface-variant mt-1.5 ml-1 flex items-center gap-1">
                    <span class="material-symbols-outlined text-[14px]">info</span> Mínimo de 8 caracteres.
                </p>
            </div>
            <div>
                <label class="block text-xs font-bold text-on-surface-variant uppercase tracking-wider mb-2 ml-1">Confirmar nova senha</label>
                <input type="password" name="confirm_password" required minlength="8" autocomplete="new-password"
                       class="w-full bg-slate-50 border border-slate-200 rounded-[16px] px-4 py-3.5 text-on-surface input-glow">
            </div>
        </div>

        <button type="submit" class="btn-primary w-full py-4 text-sm font-bold shadow-sm flex items-center justify-center gap-2 transform active:scale-95">
            <span>Salvar Nova Senha</span>
            <span class="material-symbols-outlined text-[20px]">lock</span>
        </button>
    </form>
</main>

<?php require __DIR__ . '/../layouts/bottom-nav.php'; ?>
