<?php
$title = "Editar Perfil";
$activeNav = "perfil";
require __DIR__ . '/../layouts/head.php';
require __DIR__ . '/../layouts/top-app-bar.php';
?>

<main class="w-full max-w-lg mx-auto flex-grow px-4 pb-24">
    <?php require __DIR__ . '/../layouts/flash.php'; ?>

    <div class="mb-6">
        <a href="/perfil" class="text-primary text-sm font-bold flex items-center gap-1 mb-4">
            <span class="material-symbols-outlined text-[18px]">arrow_back</span> Cancelar
        </a>
        <h1 class="text-2xl font-bold text-on-surface">Editar Perfil</h1>
    </div>

    <form action="/perfil/editar" method="POST" class="space-y-6">
        <?= csrf_field() ?>

        <div class="pib-card p-6 space-y-4">
            <div>
                <label class="block text-xs font-bold text-on-surface-variant uppercase tracking-wider mb-2 ml-1">Nome completo</label>
                <input type="text" name="name" value="<?= htmlspecialchars($user['name'] ?? '') ?>" required
                       class="w-full bg-slate-50 border border-slate-200 rounded-[16px] px-4 py-3.5 text-on-surface input-glow">
            </div>

            <div>
                <label class="block text-xs font-bold text-on-surface-variant uppercase tracking-wider mb-2 ml-1">E-mail</label>
                <input type="email" name="email" value="<?= htmlspecialchars($user['email'] ?? '') ?>"
                       class="w-full bg-slate-50 border border-slate-200 rounded-[16px] px-4 py-3.5 text-on-surface input-glow">
            </div>

            <div class="flex gap-4">
                <div class="flex-1">
                    <label class="block text-xs font-bold text-on-surface-variant uppercase tracking-wider mb-2 ml-1">Telefone</label>
                    <input type="tel" name="phone" value="<?= htmlspecialchars($user['phone'] ?? '') ?>"
                           class="w-full bg-slate-50 border border-slate-200 rounded-[16px] px-4 py-3.5 text-on-surface input-glow">
                </div>
                <div class="flex-1">
                    <label class="block text-xs font-bold text-on-surface-variant uppercase tracking-wider mb-2 ml-1">Nascimento</label>
                    <input type="date" name="birth_date" value="<?= htmlspecialchars($user['birth_date'] ?? '') ?>"
                           class="w-full bg-slate-50 border border-slate-200 rounded-[16px] px-4 py-3.5 text-on-surface input-glow">
                </div>
            </div>

            <div>
                <label class="block text-xs font-bold text-on-surface-variant uppercase tracking-wider mb-2 ml-1">Instrumento / Função</label>
                <input type="text" name="instrument" value="<?= htmlspecialchars($user['instrument'] ?? '') ?>" placeholder="Ex.: Violão, Vocal, Bateria"
                       class="w-full bg-slate-50 border border-slate-200 rounded-[16px] px-4 py-3.5 text-on-surface input-glow">
            </div>

            <div>
                <label class="block text-xs font-bold text-on-surface-variant uppercase tracking-wider mb-2 ml-1">Bio</label>
                <textarea name="bio" rows="3" placeholder="Uma frase sobre você no ministério"
                          class="w-full bg-slate-50 border border-slate-200 rounded-[16px] px-4 py-3.5 text-on-surface input-glow"><?= htmlspecialchars($user['bio'] ?? '') ?></textarea>
            </div>
        </div>

        <button type="submit" class="btn-primary w-full py-4 text-sm font-bold shadow-sm flex items-center justify-center gap-2 transform active:scale-95">
            <span>Salvar Alterações</span>
            <span class="material-symbols-outlined text-[20px]">save</span>
        </button>
    </form>
</main>

<?php require __DIR__ . '/../layouts/bottom-nav.php'; ?>
