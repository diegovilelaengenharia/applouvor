<!-- Header bar -->
<header class="w-full max-w-lg mx-auto py-4 flex items-center justify-between border-b border-slate-100 mb-6 px-4">
    <a href="/dashboard" class="flex items-center gap-2">
        <span class="material-symbols-outlined text-[24px] text-on-surface">church</span>
        <h2 class="text-xl font-bold text-on-surface">PIB Oliveira</h2>
    </a>
    <div class="flex items-center gap-3">
        <a href="/notificacoes" class="relative text-on-surface-variant hover:text-primary transition-colors">
            <span class="material-symbols-outlined text-[24px]">notifications</span>
            <?php if (!empty($unreadCount) && $unreadCount > 0): ?>
                <span class="absolute -top-0.5 -right-0.5 w-2.5 h-2.5 bg-red-500 rounded-full border-2 border-surface"></span>
            <?php endif; ?>
        </a>
    </div>
</header>
