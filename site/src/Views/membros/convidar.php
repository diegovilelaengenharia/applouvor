<?php
$title = "Convidar Membro";
$bodyClass = "";
require __DIR__ . '/../layouts/head.php';
?>

<header class="sticky top-0 z-10 w-full max-w-lg mx-auto bg-surface border-b border-slate-100 dark:border-slate-800 flex items-center gap-3 px-4 py-3.5">
    <a href="/membros" class="text-on-surface-variant hover:text-primary transition-colors">
        <span class="material-symbols-outlined text-[22px]">arrow_back_ios_new</span>
    </a>
    <h1 class="text-lg font-bold text-on-surface flex-1">Convidar Membro</h1>
</header>

<main class="w-full max-w-lg mx-auto flex-grow px-4 pt-6 pb-10">
    <?php require __DIR__ . '/../layouts/flash.php'; ?>

    <div class="pib-card p-4 mb-5 flex items-center gap-3">
        <div class="w-10 h-10 rounded-xl bg-primary/10 flex items-center justify-center flex-shrink-0">
            <span class="material-symbols-outlined text-[20px] text-primary">person_add</span>
        </div>
        <div>
            <p class="text-sm font-semibold text-on-surface">Criar acesso para novo membro</p>
            <p class="text-xs text-on-surface-variant">O membro usará estas credenciais para entrar no app.</p>
        </div>
    </div>

    <form method="POST" action="/membros/convidar" class="space-y-4">
        <?= csrf_field() ?>

        <div>
            <label class="block text-xs font-semibold text-on-surface-variant uppercase tracking-wider mb-1.5">Nome *</label>
            <input type="text" name="name" required placeholder="Nome completo" class="input-glow w-full">
        </div>

        <div>
            <label class="block text-xs font-semibold text-on-surface-variant uppercase tracking-wider mb-1.5">Instrumento</label>
            <input type="text" name="instrument" placeholder="Ex: Teclado / Voz" class="input-glow w-full">
        </div>

        <div>
            <label class="block text-xs font-semibold text-on-surface-variant uppercase tracking-wider mb-1.5">WhatsApp</label>
            <input type="tel" name="phone" placeholder="(00) 00000-0000" class="input-glow w-full">
        </div>

        <div>
            <label class="block text-xs font-semibold text-on-surface-variant uppercase tracking-wider mb-1.5">E-mail</label>
            <input type="email" name="email" placeholder="opcional" class="input-glow w-full">
        </div>

        <div>
            <label class="block text-xs font-semibold text-on-surface-variant uppercase tracking-wider mb-1.5">Senha inicial *</label>
            <input type="text" name="password" required placeholder="Mínimo 4 caracteres" class="input-glow w-full">
            <p class="text-[11px] text-on-surface-variant mt-1">O membro pode alterar a senha depois em Perfil → Alterar Senha.</p>
        </div>

        <div class="pt-2 flex gap-3">
            <a href="/membros" class="btn-outline flex-1 text-center">Cancelar</a>
            <button type="submit" class="btn-primary flex-1">Adicionar Membro</button>
        </div>
    </form>
</main>

<script src="/assets/js/app.js"></script>
</body>
</html>
