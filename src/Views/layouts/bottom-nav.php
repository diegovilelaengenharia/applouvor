<?php
    $activeNav = $activeNav ?? 'inicio';
?>
<!-- Bottom Navigation -->
<nav class="fixed bottom-0 w-full bg-surface border-t border-slate-100 z-50">
    <div class="max-w-lg mx-auto flex justify-between items-center px-6 py-2">
        <a href="/dashboard" class="flex flex-col items-center gap-1 p-2 <?= $activeNav === 'inicio' ? 'text-primary' : 'text-on-surface-variant hover:text-on-surface' ?>">
            <span class="material-symbols-outlined text-[24px] <?= $activeNav === 'inicio' ? 'fill' : '' ?>">home</span>
            <span class="text-[10px] font-bold">Início</span>
        </a>
        <a href="/escalas" class="flex flex-col items-center gap-1 p-2 <?= $activeNav === 'escalas' ? 'text-primary' : 'text-on-surface-variant hover:text-on-surface' ?>">
            <span class="material-symbols-outlined text-[24px] <?= $activeNav === 'escalas' ? 'fill' : '' ?>">calendar_month</span>
            <span class="text-[10px] font-bold">Escalas</span>
        </a>
        <a href="/repertorio" class="flex flex-col items-center gap-1 p-2 <?= $activeNav === 'repertorio' ? 'text-primary' : 'text-on-surface-variant hover:text-on-surface' ?>">
            <span class="material-symbols-outlined text-[24px] <?= $activeNav === 'repertorio' ? 'fill' : '' ?>">library_music</span>
            <span class="text-[10px] font-bold">Repertório</span>
        </a>
        <a href="/perfil" class="flex flex-col items-center gap-1 p-2 <?= $activeNav === 'perfil' ? 'text-primary' : 'text-on-surface-variant hover:text-on-surface' ?>">
            <span class="material-symbols-outlined text-[24px] <?= $activeNav === 'perfil' ? 'fill' : '' ?>">person</span>
            <span class="text-[10px] font-bold">Perfil</span>
        </a>
    </div>
</nav>

<!-- Padding bottom extra para o body não ficar escondido atrás da navbar fixa -->
<div class="h-20"></div>

<!-- PWA Registration / Scripts de encerramento -->
<script src="/assets/js/app.js"></script>
</body>
</html>
