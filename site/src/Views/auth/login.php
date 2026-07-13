<?php 
$title = "Login"; 
$bodyClass = "justify-center p-4"; // Centraliza verticalmente o login
require __DIR__ . '/../layouts/head.php'; 
?>

<!-- Container do Login -->
<div class="w-full max-w-md mx-auto pib-card p-8 md:p-10 relative overflow-hidden reveal-item">
    
    <!-- Linha azul superior decorativa -->
    <div class="absolute top-0 left-0 w-full h-1.5" style="background-color: var(--primary);"></div>

    <div class="flex flex-col items-center text-center mb-8">
        <h1 class="text-3xl font-bold text-on-surface mb-2 mt-4">Ministério de Louvor</h1>
        <p class="text-sm text-on-surface-variant flex items-center justify-center gap-1.5">
            A paz do Senhor! 🙏
        </p>
    </div>

    <?php require __DIR__ . '/../layouts/flash.php'; ?>

    <form action="/login" method="POST" class="space-y-6">
        <?= csrf_field() ?>
        
        <div>
            <label for="name" class="block text-xs font-bold text-on-surface-variant uppercase tracking-wider mb-2 ml-1">Usuário</label>
            <div class="relative">
                <span class="material-symbols-outlined absolute left-4 top-1/2 -translate-y-1/2 text-on-surface-variant text-[20px] pointer-events-none">person</span>
                <input type="text" id="name" name="name" placeholder="Seu nome cadastrado" required autocomplete="username" 
                       class="w-full bg-slate-50 border border-slate-200 rounded-[16px] pl-12 pr-4 py-3.5 text-on-surface placeholder:text-on-surface-variant/40 input-glow transition-all">
            </div>
        </div>
        
        <div>
            <label for="password" class="block text-xs font-bold text-on-surface-variant uppercase tracking-wider mb-2 ml-1">Senha</label>
            <div class="relative">
                <span class="material-symbols-outlined absolute left-4 top-1/2 -translate-y-1/2 text-on-surface-variant text-[20px] pointer-events-none">lock</span>
                <input type="password" id="password" name="password" placeholder="Sua senha de acesso" required autocomplete="current-password"
                       class="w-full bg-slate-50 border border-slate-200 rounded-[16px] pl-12 pr-4 py-3.5 text-on-surface placeholder:text-on-surface-variant/40 input-glow transition-all">
            </div>
        </div>

        <button type="submit" class="btn-primary w-full py-4 text-sm font-bold shadow-sm flex items-center justify-center gap-2 transform active:scale-95 transition-all mt-4">
            <span>Entrar</span>
            <span class="material-symbols-outlined text-[20px]">login</span>
        </button>

        <div class="text-center">
            <a href="/recuperar-senha" class="text-xs font-semibold text-primary hover:underline">Esqueceu a senha?</a>
        </div>
    </form>

    <div class="mt-8 flex flex-col items-center gap-2 border-t border-slate-100 pt-6">
        <span class="text-[11px] text-on-surface-variant/70">Desenvolvido por Diego T. N. Vilela</span>
        <a href="https://wa.me/5535984529577" target="_blank" class="text-xs font-bold text-primary hover:underline flex items-center gap-1">
            <span class="material-symbols-outlined text-[15px]">chat</span>
            Suporte no WhatsApp
        </a>
    </div>
</div>

<script src="/assets/js/app.js"></script>
</body>
</html>
