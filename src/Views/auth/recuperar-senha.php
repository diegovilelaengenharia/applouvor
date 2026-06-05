<?php
$title = "Recuperar Senha";
$bodyClass = "justify-center p-4";
require __DIR__ . '/../layouts/head.php';
?>

<div class="w-full max-w-md mx-auto pib-card p-8 md:p-10 relative overflow-hidden reveal-item">
    <div class="absolute top-0 left-0 w-full h-1.5" style="background-color: var(--primary);"></div>

    <div class="flex flex-col items-center text-center mb-8">
        <div class="w-16 h-16 rounded-2xl mb-4 mt-4 flex items-center justify-center" style="background-color: rgba(46,126,237,0.12);">
            <span class="material-symbols-outlined text-[32px] text-primary">lock_reset</span>
        </div>
        <h1 class="text-2xl font-bold text-on-surface mb-2">Esqueceu a senha?</h1>
        <p class="text-sm text-on-surface-variant leading-relaxed">
            Por segurança, a redefinição de senha é feita pela liderança do ministério.
            Fale com um líder ou com o suporte para recuperar seu acesso.
        </p>
    </div>

    <div class="flex flex-col gap-3">
        <a href="https://wa.me/5535984529577" target="_blank" rel="noopener"
           class="btn-primary w-full py-3.5 text-sm font-bold flex items-center justify-center gap-2 transform active:scale-95">
            <span class="material-symbols-outlined text-[18px]">chat</span> Falar no WhatsApp
        </a>
        <a href="/" class="btn-outline w-full py-3.5 text-sm font-bold flex items-center justify-center gap-2">
            <span class="material-symbols-outlined text-[18px]">arrow_back</span> Voltar ao login
        </a>
    </div>
</div>

<script src="/assets/js/app.js"></script>
</body>
</html>
