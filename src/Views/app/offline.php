<?php
$title = "Offline";
$bodyClass = "justify-center p-4 text-center";
require __DIR__ . '/../layouts/head.php';
?>

<div class="w-full max-w-md mx-auto reveal-item">
    <div class="w-20 h-20 rounded-2xl mx-auto mb-6 flex items-center justify-center" style="background-color: rgba(46,126,237,0.12);">
        <span class="material-symbols-outlined text-[40px] text-primary">cloud_off</span>
    </div>
    <h1 class="text-2xl font-bold text-on-surface">Você está offline</h1>
    <p class="text-sm text-on-surface-variant mt-2 mb-8 leading-relaxed">
        Algumas funções não estão disponíveis sem internet. Você ainda pode ver as cifras e escalas já abertas.
    </p>

    <div class="flex flex-col gap-3">
        <button onclick="window.location.reload()" class="btn-primary inline-flex items-center justify-center gap-2 px-6 py-3.5 text-sm font-bold">
            <span class="material-symbols-outlined text-[18px]">refresh</span> Tentar novamente
        </button>
        <a href="/dashboard" class="btn-outline inline-flex items-center justify-center gap-2 px-6 py-3.5 text-sm font-bold">
            Ver conteúdo salvo
        </a>
    </div>

    <p class="text-[11px] text-on-surface-variant/60 mt-10"><?= htmlspecialchars(CHURCH_NAME) ?> · Louvor</p>
</div>

<script src="/assets/js/app.js"></script>
</body>
</html>
