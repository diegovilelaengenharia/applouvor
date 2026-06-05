<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Louvor PIB - Dashboard</title>

    <!-- PWA Config -->
    <meta name="theme-color" content="#f9f9f9">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="default">
    <link rel="manifest" href="manifest.json">

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Hanken+Grotesk:wght@600;700&family=Open+Sans:wght@400;600;700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet">

    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com?plugins=forms"></script>
    <link rel="stylesheet" href="assets/css/stitch-theme.css">
    
    <!-- Theme Manager (Fase 3) -->
    <script src="assets/js/theme.js"></script>
    
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
        }
        .btn-outline {
            border: 1.5px solid var(--primary);
            color: var(--primary);
            border-radius: var(--radius-full);
            transition: all 0.2s ease;
        }
        .btn-outline:hover {
            background-color: var(--primary);
            color: var(--on-primary);
        }
    </style>
</head>
<body class="antialiased min-h-[100dvh] flex flex-col justify-between p-4">

    <!-- Header bar -->
    <header class="w-full max-w-lg mx-auto py-4 flex items-center justify-between border-b border-slate-100 mb-6">
        <h2 class="text-xl font-bold text-on-surface">PIB Oliveira</h2>
        <div class="flex items-center gap-2">
            <span class="material-symbols-outlined text-[20px] text-primary fill">worship</span>
            <span class="text-xs font-semibold text-primary uppercase tracking-wider">Louvor</span>
        </div>
    </header>

    <!-- Main Content -->
    <main class="w-full max-w-lg mx-auto flex-grow flex flex-col justify-center">
        <div class="pib-card p-8 text-center reveal-item">
            <div class="w-16 h-16 bg-blue-50 text-primary border border-blue-100 rounded-2xl flex items-center justify-center mx-auto mb-6 shadow-sm">
                <span class="material-symbols-outlined text-[32px] fill">account_circle</span>
            </div>

            <h1 class="text-2xl font-bold text-on-surface mb-2">Olá, <?= htmlspecialchars($userName) ?>!</h1>
            <p class="text-sm text-on-surface-variant mb-6">
                Você fez login com sucesso como <span class="font-bold text-primary"><?= $userRole === 'admin' ? 'Administrador' : 'Músico' ?></span>.
            </p>

            <div class="bg-slate-50 border border-slate-100 rounded-2xl p-4 mb-8 text-left text-xs text-on-surface-variant space-y-1.5">
                <p>💡 <b>Status do Sistema:</b> Fase 2 Concluída!</p>
                <p>✅ Conexão com banco local OK.</p>
                <p>✅ Roteador Front Controller OK.</p>
                <p>✅ Proteções CSRF e Rate Limit Ativas.</p>
            </div>

            <a href="/logout" class="btn-outline w-full py-3.5 text-sm font-bold flex items-center justify-center gap-2 transform active:scale-95">
                <span>Sair da Conta</span>
                <span class="material-symbols-outlined text-[18px]">logout</span>
            </a>
        </div>
    </main>

    <!-- Footer -->
    <footer class="w-full max-w-lg mx-auto py-6 text-center text-[11px] text-on-surface-variant/50 border-t border-slate-100 mt-6">
        <p><?= APP_COPYRIGHT ?></p>
    </footer>

    <!-- PWA Registration -->
    <script src="assets/js/app.js"></script>
</body>
</html>
