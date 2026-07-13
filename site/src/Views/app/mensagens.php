<?php
$title = "Mensagens";
$bodyClass = "";
require __DIR__ . '/../layouts/head.php';
?>

<header class="sticky top-0 z-10 w-full max-w-lg mx-auto bg-surface border-b border-slate-100 dark:border-slate-800 flex items-center gap-3 px-4 py-3.5">
    <a href="/dashboard" class="text-on-surface-variant hover:text-primary transition-colors">
        <span class="material-symbols-outlined text-[22px]">arrow_back_ios_new</span>
    </a>
    <h1 class="text-lg font-bold text-on-surface flex-1">Mensagens</h1>
    <span class="material-symbols-outlined text-[22px] text-on-surface-variant">more_vert</span>
</header>

<main class="w-full max-w-lg mx-auto flex-grow flex flex-col items-center justify-center px-6 py-16 text-center">
    <div class="w-20 h-20 rounded-2xl bg-primary/10 flex items-center justify-center mb-5">
        <span class="material-symbols-outlined text-[40px] text-primary">forum</span>
    </div>
    <h2 class="text-lg font-bold text-on-surface mb-2">Chat em breve</h2>
    <p class="text-sm text-on-surface-variant max-w-xs leading-relaxed">
        O mural de mensagens do grupo estará disponível na próxima versão do app. Por enquanto, use o grupo do WhatsApp para comunicação rápida.
    </p>
    <a href="https://wa.me" target="_blank"
       class="btn-primary mt-6 flex items-center gap-2 px-6">
        <span class="material-symbols-outlined text-[18px]">chat</span>
        Abrir WhatsApp
    </a>
</main>

<script src="/assets/js/app.js"></script>
</body>
</html>
