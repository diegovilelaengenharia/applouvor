<?php
$title = "Configurações";
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
        <h1 class="text-2xl font-bold text-on-surface">Configurações</h1>
    </div>

    <!-- Conta -->
    <p class="text-xs font-bold uppercase tracking-wider text-on-surface-variant mb-2 ml-1">Conta</p>
    <div class="pib-card overflow-hidden mb-6">
        <a href="/perfil/editar" class="flex items-center justify-between px-4 py-3.5 border-b border-slate-100 hover:bg-slate-50 transition-colors">
            <span class="flex items-center gap-3 text-sm font-semibold text-on-surface"><span class="material-symbols-outlined text-[20px] text-primary">person</span> Editar perfil</span>
            <span class="material-symbols-outlined text-[18px] text-on-surface-variant">chevron_right</span>
        </a>
        <a href="/perfil/senha" class="flex items-center justify-between px-4 py-3.5 hover:bg-slate-50 transition-colors">
            <span class="flex items-center gap-3 text-sm font-semibold text-on-surface"><span class="material-symbols-outlined text-[20px] text-primary">lock</span> Alterar senha</span>
            <span class="material-symbols-outlined text-[18px] text-on-surface-variant">chevron_right</span>
        </a>
    </div>

    <!-- Preferências -->
    <p class="text-xs font-bold uppercase tracking-wider text-on-surface-variant mb-2 ml-1">Preferências</p>
    <div class="pib-card overflow-hidden mb-6">
        <a href="/configuracoes/notificacoes" class="flex items-center justify-between px-4 py-3.5 border-b border-slate-100 hover:bg-slate-50 transition-colors">
            <span class="flex items-center gap-3 text-sm font-semibold text-on-surface"><span class="material-symbols-outlined text-[20px] text-primary">notifications</span> Notificações</span>
            <span class="material-symbols-outlined text-[18px] text-on-surface-variant">chevron_right</span>
        </a>
        <a href="/indisponibilidades" class="flex items-center justify-between px-4 py-3.5 border-b border-slate-100 hover:bg-slate-50 transition-colors">
            <span class="flex items-center gap-3 text-sm font-semibold text-on-surface"><span class="material-symbols-outlined text-[20px] text-primary">event_busy</span> Indisponibilidades</span>
            <span class="material-symbols-outlined text-[18px] text-on-surface-variant">chevron_right</span>
        </a>
        <div class="flex items-center justify-between px-4 py-3.5">
            <span class="flex items-center gap-3 text-sm font-semibold text-on-surface"><span class="material-symbols-outlined text-[20px] text-primary">dark_mode</span> Tema escuro</span>
            <label class="relative inline-flex items-center cursor-pointer">
                <input type="checkbox" id="themeToggle" class="sr-only peer" onchange="toggleTheme()">
                <div class="w-11 h-6 bg-slate-200 rounded-full peer-checked:bg-primary transition-colors after:content-[''] after:absolute after:top-0.5 after:left-0.5 after:bg-white after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:after:translate-x-5"></div>
            </label>
        </div>
    </div>

    <!-- Sobre -->
    <p class="text-xs font-bold uppercase tracking-wider text-on-surface-variant mb-2 ml-1">Sobre</p>
    <div class="pib-card overflow-hidden mb-6">
        <a href="https://wa.me/" target="_blank" rel="noopener" class="flex items-center justify-between px-4 py-3.5 border-b border-slate-100 hover:bg-slate-50 transition-colors">
            <span class="flex items-center gap-3 text-sm font-semibold text-on-surface"><span class="material-symbols-outlined text-[20px] text-primary">support_agent</span> Suporte</span>
            <span class="material-symbols-outlined text-[18px] text-on-surface-variant">open_in_new</span>
        </a>
        <a href="/ajuda" class="flex items-center justify-between px-4 py-3.5 border-b border-slate-100 hover:bg-slate-50 transition-colors">
            <span class="flex items-center gap-3 text-sm font-semibold text-on-surface"><span class="material-symbols-outlined text-[20px] text-primary">help</span> Ajuda / FAQ</span>
            <span class="material-symbols-outlined text-[18px] text-on-surface-variant">chevron_right</span>
        </a>
        <div class="flex items-center justify-between px-4 py-3.5">
            <span class="flex items-center gap-3 text-sm text-on-surface-variant"><span class="material-symbols-outlined text-[20px]">info</span> Versão</span>
            <span class="text-sm text-on-surface-variant"><?= htmlspecialchars(APP_VERSION) ?></span>
        </div>
    </div>

    <a href="/logout" class="flex items-center justify-center gap-2 pib-card px-4 py-3.5 text-sm font-bold text-error hover:bg-red-50 transition-colors">
        <span class="material-symbols-outlined text-[20px]">logout</span> Sair da conta
    </a>
</main>

<script>
    // Sincroniza o toggle com o tema atual (gerido pelo theme.js via localStorage)
    document.addEventListener('DOMContentLoaded', function () {
        var t = document.getElementById('themeToggle');
        if (t) t.checked = document.documentElement.classList.contains('dark');
    });
</script>

<?php require __DIR__ . '/../layouts/bottom-nav.php'; ?>
