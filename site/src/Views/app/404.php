<?php
$title = "Página não encontrada";
$bodyClass = "justify-center p-4 text-center";
require __DIR__ . '/../layouts/head.php';
?>

<div class="w-full max-w-md mx-auto reveal-item">
    <div class="w-20 h-20 rounded-2xl bg-primary-light mx-auto mb-6 flex items-center justify-center" style="background-color: rgba(46,126,237,0.12);">
        <span class="material-symbols-outlined text-[40px] text-primary">travel_explore</span>
    </div>
    <h1 class="text-5xl font-bold text-on-surface">404</h1>
    <p class="text-lg font-bold text-on-surface mt-3">Página não encontrada</p>
    <p class="text-sm text-on-surface-variant mt-2 mb-8">O endereço que você tentou abrir não existe ou foi movido.</p>

    <a href="/dashboard" class="btn-primary inline-flex items-center justify-center gap-2 px-6 py-3.5 text-sm font-bold">
        <span class="material-symbols-outlined text-[18px]">home</span> Voltar ao início
    </a>
</div>

<script src="/assets/js/app.js"></script>
</body>
</html>
