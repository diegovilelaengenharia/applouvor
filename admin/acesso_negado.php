<?php
// admin/acesso_negado.php
header('Content-Type: text/html; charset=utf-8');

// Inicia sessão se não estiver iniciada
if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.gc_maxlifetime', 2592000);
    session_set_cookie_params([
        'lifetime' => 2592000,
        'path' => '/',
        'secure' => true,
        'httponly' => true,
        'samesite' => 'Lax'
    ]);
    session_start();
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Acesso Restrito - APP Louvor</title>
    <!-- Google Fonts: Outfit & Inter -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Outfit:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- Lucide Icons -->
    <script src="https://unpkg.com/lucide@latest"></script>
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    colors: {
                        primary: '#0059b8', // Worship Blue
                        'primary-dark': '#004794',
                        surface: 'var(--bg-surface)',
                        muted: '#6b7280',
                    },
                    fontFamily: {
                        outfit: ['Outfit', 'sans-serif'],
                        inter: ['Inter', 'sans-serif'],
                    }
                }
            }
        }
    </script>
    <style>
        :root {
            --bg-surface: #f9fafb;
        }
        .dark {
            --bg-surface: #111827;
        }
    </style>
</head>
<body class="bg-gray-50 dark:bg-gray-950 font-inter min-h-screen flex items-center justify-center p-4 transition-colors duration-300">
    
    <div class="max-w-md w-full bg-white dark:bg-gray-900 border border-gray-100 dark:border-gray-800 shadow-2xl rounded-[32px] p-8 md:p-10 text-center animate-fade-in relative overflow-hidden">
        
        <!-- Gradiente decorativo superior -->
        <div class="absolute top-0 inset-x-0 h-2 bg-gradient-to-r from-blue-500 via-sky-500 to-blue-600"></div>
        
        <!-- Ícone / Escudo Animado -->
        <div class="mx-auto w-20 h-20 bg-blue-50 dark:bg-blue-950/40 rounded-3xl flex items-center justify-center text-blue-600 dark:text-blue-400 mb-8 border border-blue-100/50 dark:border-blue-900/30 shadow-sm relative group">
            <!-- Efeito de pulso de hardware no hover -->
            <div class="absolute inset-0 bg-blue-500/10 rounded-3xl scale-100 group-hover:scale-110 transition-transform duration-500 ease-out"></div>
            <i data-lucide="shield-alert" class="w-10 h-10 relative z-10 animate-[bounce_2s_infinite]"></i>
        </div>

        <!-- Conteúdo Principal -->
        <h1 class="font-outfit text-3xl font-extrabold text-gray-900 dark:text-white tracking-tight mb-3">
            Acesso Reservado
        </h1>
        
        <p class="text-sm md:text-base text-gray-500 dark:text-gray-400 leading-relaxed mb-8">
            Esta página é restrita a <strong>líderes e administradores</strong> do ministério. Caso você seja integrante da equipe e precise de permissão especial, fale com o responsável.
        </p>

        <!-- Bento Grid de Informações Rápidas -->
        <div class="grid grid-cols-2 gap-4 mb-8 bg-gray-50 dark:bg-gray-950 p-4 rounded-2xl border border-gray-100 dark:border-gray-800 text-left">
            <div>
                <span class="text-[10px] uppercase font-bold text-gray-400 dark:text-gray-500 tracking-wider block">Usuário</span>
                <span class="text-xs font-bold text-gray-700 dark:text-gray-300 truncate block">
                    <?= htmlspecialchars($_SESSION['user_name'] ?? 'Músico Convidado') ?>
                </span>
            </div>
            <div>
                <span class="text-[10px] uppercase font-bold text-gray-400 dark:text-gray-500 tracking-wider block">Função Atual</span>
                <span class="inline-flex items-center gap-1 text-xs font-bold text-blue-600 dark:text-blue-400 mt-0.5">
                    <span class="w-1.5 h-1.5 rounded-full bg-blue-600 dark:bg-blue-400"></span>
                    <?= ($_SESSION['user_role'] ?? 'user') === 'admin' ? 'Líder' : 'Voluntário' ?>
                </span>
            </div>
        </div>

        <!-- Ações -->
        <div class="flex flex-col gap-3">
            <a href="../app/index.php" class="w-full bg-[#0059b8] hover:bg-[#004794] active:scale-[0.98] text-white font-bold text-sm py-4 px-6 rounded-2xl transition-all duration-200 shadow-md shadow-blue-500/10 flex items-center justify-center gap-2">
                <i data-lucide="arrow-left" class="w-4 h-4"></i>
                <span>Voltar para o Painel</span>
            </a>
            
            <a href="../logout.php" class="w-full bg-gray-50 dark:bg-gray-950 hover:bg-red-500/10 hover:text-red-500 dark:hover:bg-red-950/20 active:scale-[0.98] text-gray-600 dark:text-gray-400 border border-gray-100 dark:border-gray-800 font-bold text-sm py-4 px-6 rounded-2xl transition-all duration-200 flex items-center justify-center gap-2">
                <i data-lucide="log-out" class="w-4 h-4"></i>
                <span>Entrar com Outra Conta</span>
            </a>
        </div>

    </div>

    <script>
        // Inicializar ícones do Lucide
        lucide.createIcons();

        // Sincronizar tema com localStorage ou preferência do sistema
        if (localStorage.getItem('theme-mode') === 'dark' || (!localStorage.getItem('theme-mode') && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
            document.documentElement.classList.add('dark');
        } else {
            document.documentElement.classList.remove('dark');
        }
    </script>
</body>
</html>
