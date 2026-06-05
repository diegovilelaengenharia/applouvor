<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Louvor PIB - Login</title>

    <!-- PWA Config -->
    <meta name="theme-color" content="#f9f9f9">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="default">
    <link rel="manifest" href="manifest.json" onerror="this.href='';">

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Hanken+Grotesk:wght@600;700&family=Open+Sans:wght@400;600;700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet">

    <!-- Tailwind CSS (CDN para utilitários de layout) -->
    <script src="https://cdn.tailwindcss.com?plugins=forms"></script>
    <link rel="stylesheet" href="assets/css/stitch-theme.css">
    
    <style>
        body {
            font-family: var(--font-body);
            background-color: var(--surface);
            color: var(--on-surface);
        }
        h1, h2, h3 {
            font-family: var(--font-display);
        }
        .pib-card {
            background-color: var(--surface-container-lowest);
            border: 1px solid rgba(193, 198, 214, 0.4);
            border-radius: var(--radius-xl);
            box-shadow: var(--shadow-card);
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }
        .input-glow:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(46, 126, 237, 0.25);
            outline: none;
        }
        .btn-primary {
            background-color: var(--primary);
            color: var(--on-primary);
            border-radius: var(--radius-full);
            transition: background-color 0.2s ease, transform 0.1s ease;
        }
        .btn-primary:hover {
            background-color: var(--primary-container);
        }
        .btn-primary:active {
            transform: scale(0.98);
        }
    </style>
</head>
<body class="antialiased min-h-[100dvh] flex items-center justify-center p-4">

    <!-- Container do Login -->
    <div class="w-full max-w-md pib-card p-8 md:p-10 relative overflow-hidden reveal-item">
        
        <!-- Linha azul superior decorativa -->
        <div class="absolute top-0 left-0 w-full h-1.5" style="background-color: var(--primary);"></div>

        <div class="flex flex-col items-center text-center mb-8">
            <h1 class="text-3xl font-bold text-on-surface mb-2 mt-4">Ministério de Louvor</h1>
            <p class="text-sm text-on-surface-variant flex items-center justify-center gap-1.5">
                A paz do Senhor! 🙏
            </p>
        </div>

        <?php if (!empty($error)): ?>
            <div class="bg-red-50 text-red-700 p-4 rounded-xl flex items-start gap-3 mb-6 border border-red-200/50 text-sm">
                <span class="material-symbols-outlined text-[20px] shrink-0 mt-0.5">error</span>
                <span class="font-semibold leading-tight"><?= htmlspecialchars($error) ?></span>
            </div>
        <?php endif; ?>

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
        </form>

        <div class="mt-8 flex flex-col items-center gap-2 border-t border-slate-100 pt-6">
            <span class="text-[11px] text-on-surface-variant/70">Desenvolvido por Diego T. N. Vilela</span>
            <a href="https://wa.me/5535984529577" target="_blank" class="text-xs font-bold text-primary hover:underline flex items-center gap-1">
                <span class="material-symbols-outlined text-[15px]">chat</span>
                Suporte no WhatsApp
            </a>
        </div>
    </div>

</body>
</html>
