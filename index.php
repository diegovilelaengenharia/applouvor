<?php
require_once 'src/config/no-cache.php'; // Force refresh
require_once 'src/config/db.php';
require_once 'src/helpers/auth.php';

$validator = new App\Validator();
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $password = trim($_POST['password']);
    
    // Validação usando a nova classe Validator
    $validator->required($name, 'Nome');
    $validator->required($password, 'Senha');
    
    if (!$validator->hasErrors()) {
        if (login($name, $password, $pdo)) {
            // Verifica nivel e redireciona
            if ($_SESSION['user_role'] === 'admin') {
                header("Location: admin/index.php");
            } else {
                header("Location: app/index.php");
            }
            exit;
        } else {
            $error = "Dados incorretos.";
        }
    } else {
        $error = $validator->getFirstError();
    }
}
?>
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
    <link rel="manifest" href="manifest.json">
    <link rel="icon" type="image/png" href="assets/icons/icon-192.png">
    <link rel="apple-touch-icon" href="assets/icons/icon-192.png">

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Hanken+Grotesk:wght@600;700&family=Open+Sans:wght@400;600;700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet">

    <!-- Icons -->
    <script src="https://unpkg.com/lucide@latest"></script>

    <!-- Tailwind CSS (Stitch Theme) -->
    <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
    <script id="tailwind-config">
    tailwind.config = {
        darkMode: "class",
        theme: {
            extend: {
                "colors": {
                    "surface-bright": "#f9f9f9",
                    "tertiary-fixed": "#ffdf9e",
                    "surface-tint": "#005cbc",
                    "on-tertiary-fixed-variant": "#5b4300",
                    "on-tertiary": "#ffffff",
                    "secondary-fixed": "#e3e2e7",
                    "primary": "#0059b8",
                    "tertiary-container": "#946f00",
                    "on-primary-container": "#fefcff",
                    "inverse-on-surface": "#f0f1f1",
                    "error-container": "#ffdad6",
                    "on-error-container": "#93000a",
                    "outline-variant": "#c1c6d6",
                    "surface-container-high": "#e8e8e8",
                    "on-secondary": "#ffffff",
                    "secondary": "#5e5e63",
                    "tertiary-fixed-dim": "#fabd00",
                    "on-primary": "#ffffff",
                    "on-secondary-fixed-variant": "#46464b",
                    "secondary-container": "#e0dfe4",
                    "background": "#f9f9f9",
                    "on-primary-fixed": "#001b3f",
                    "on-secondary-fixed": "#1a1b1f",
                    "inverse-surface": "#2f3131",
                    "tertiary": "#755700",
                    "secondary-fixed-dim": "#c7c6cb",
                    "on-surface-variant": "#414753",
                    "deep-navy": "#1A1B1F",
                    "surface": "#f9f9f9",
                    "inverse-primary": "#abc7ff",
                    "surface-container-low": "#f3f3f3",
                    "surface-dim": "#dadada",
                    "surface-container-highest": "#e2e2e2",
                    "on-background": "#1a1c1c",
                    "outline": "#727785",
                    "on-surface": "#1a1c1c",
                    "primary-container": "#1872e0",
                    "primary-fixed": "#d7e2ff",
                    "ghost-gray": "#F4F4F5",
                    "error": "#ba1a1a",
                    "surface-container": "#eeeeee",
                    "altar-gold": "#FFC107",
                    "surface-variant": "#e2e2e2",
                    "primary-fixed-dim": "#abc7ff",
                    "worship-blue": "#2E7EED",
                    "on-tertiary-container": "#fffbff",
                    "on-tertiary-fixed": "#261a00",
                    "on-primary-fixed-variant": "#004590",
                    "surface-container-lowest": "#ffffff",
                    "on-secondary-container": "#626267",
                    "on-error": "#ffffff"
                },
                "borderRadius": {
                    "DEFAULT": "0.25rem",
                    "lg": "0.5rem",
                    "xl": "0.75rem",
                    "full": "9999px"
                },
                "spacing": {
                    "margin-mobile": "20px",
                    "unit": "8px",
                    "max-width": "1200px",
                    "gutter": "16px",
                    "margin-desktop": "64px"
                },
                "fontFamily": {
                    "lyric-focus": ["Open Sans"],
                    "display-lg-mobile": ["Hanken Grotesk"],
                    "body-lg": ["Open Sans"],
                    "label-sm": ["Open Sans"],
                    "body-md": ["Open Sans"],
                    "headline-md": ["Hanken Grotesk"],
                    "display-lg": ["Hanken Grotesk"],
                    "sans": ["Open Sans", "sans-serif"]
                },
                "fontSize": {
                    "lyric-focus": ["28px", { "lineHeight": "40px", "letterSpacing": "-0.01em", "fontWeight": "600" }],
                    "display-lg-mobile": ["32px", { "lineHeight": "40px", "letterSpacing": "-0.01em", "fontWeight": "700" }],
                    "body-lg": ["18px", { "lineHeight": "28px", "fontWeight": "400" }],
                    "label-sm": ["12px", { "lineHeight": "16px", "letterSpacing": "0.05em", "fontWeight": "700" }],
                    "body-md": ["16px", { "lineHeight": "24px", "fontWeight": "400" }],
                    "headline-md": ["24px", { "lineHeight": "32px", "fontWeight": "600" }],
                    "display-lg": ["48px", { "lineHeight": "56px", "letterSpacing": "-0.02em", "fontWeight": "700" }]
                }
            }
        }
    }
    </script>
    <style>
        .material-symbols-outlined {
            font-variation-settings: 'FILL' 0, 'wght' 400, 'GRAD' 0, 'opsz' 24;
        }
        .material-symbols-outlined.fill {
            font-variation-settings: 'FILL' 1, 'wght' 400, 'GRAD' 0, 'opsz' 24;
        }
        /* Autofill styles for Tailwind */
        input:-webkit-autofill,
        input:-webkit-autofill:hover, 
        input:-webkit-autofill:focus, 
        input:-webkit-autofill:active{
            -webkit-box-shadow: 0 0 0 30px #f3f3f3 inset !important;
            -webkit-text-fill-color: #1a1c1c !important;
        }
    </style>
</head>

<body class="bg-surface text-on-surface font-body-md antialiased min-h-[100dvh] flex items-center justify-center p-4">

    <!-- Minimalist Login Container -->
    <div class="w-full max-w-md bg-surface-container-lowest border border-surface-container-highest rounded-[2rem] p-8 md:p-10 shadow-sm relative overflow-hidden">
        
        <!-- Top accent line -->
        <div class="absolute top-0 left-0 w-full h-1.5 bg-primary"></div>

        <div class="flex flex-col items-center text-center mb-10">
            <!-- Logo area -->
            <div class="w-20 h-20 mb-6 bg-surface-container-lowest border border-surface-container-highest rounded-2xl flex items-center justify-center shadow-inner relative">
                <!-- Using a text placeholder if logo image is missing -->
                <span class="font-display-lg text-primary text-3xl font-bold">L</span>
                <!-- Or if there is a logo image (uncomment to use) -->
                <img src="assets/images/logo_pib_black.png" alt="Logo" class="absolute inset-0 w-full h-full object-contain p-2" onerror="this.style.display='none';">
            </div>

            <h1 class="font-headline-md text-2xl font-bold text-on-surface mb-2">Ministério de Louvor</h1>
            <p class="font-body-md text-on-surface-variant flex items-center justify-center gap-2">
                A paz do Senhor! 🙏
            </p>
        </div>

        <?php if ($error): ?>
            <div class="bg-error-container text-on-error-container p-4 rounded-xl flex items-center gap-3 mb-6 border border-error-container/50">
                <span class="material-symbols-outlined text-[20px]">error</span>
                <span class="font-label-sm font-bold text-[13px]"><?= htmlspecialchars($error) ?></span>
            </div>
        <?php endif; ?>

        <form method="POST" class="space-y-5">
            <div>
                <label class="block font-label-sm text-on-surface-variant font-bold mb-2 ml-1">Usuário</label>
                <div class="relative">
                    <span class="material-symbols-outlined absolute left-4 top-1/2 -translate-y-1/2 text-on-surface-variant text-[20px] pointer-events-none">person</span>
                    <input type="text" name="name" placeholder="Seu nome" required autocomplete="username" 
                           class="w-full bg-surface-container-low border border-surface-container-highest rounded-xl pl-12 pr-4 py-3.5 font-body-md text-on-surface placeholder:text-on-surface-variant/50 focus:outline-none focus:border-primary focus:ring-1 focus:ring-primary transition-colors">
                </div>
            </div>
            
            <div>
                <label class="block font-label-sm text-on-surface-variant font-bold mb-2 ml-1">Senha</label>
                <div class="relative">
                    <span class="material-symbols-outlined absolute left-4 top-1/2 -translate-y-1/2 text-on-surface-variant text-[20px] pointer-events-none">lock</span>
                    <input type="password" name="password" placeholder="Senha" required autocomplete="current-password" pattern="[0-9]*" inputmode="numeric"
                           class="w-full bg-surface-container-low border border-surface-container-highest rounded-xl pl-12 pr-4 py-3.5 font-body-md text-on-surface placeholder:text-on-surface-variant/50 focus:outline-none focus:border-primary focus:ring-1 focus:ring-primary transition-colors">
                </div>
            </div>

            <button type="submit" class="w-full mt-6 bg-primary text-on-primary font-label-sm font-bold py-4 rounded-xl shadow-md hover:bg-primary-container hover:text-on-primary-container transition-all transform active:scale-[0.98] flex items-center justify-center gap-2">
                <span>Entrar</span>
                <span class="material-symbols-outlined text-[20px]">login</span>
            </button>
        </form>

        <div class="mt-8 border-t border-surface-container-highest pt-6">
            <button class="w-full bg-surface-container border border-surface-container-highest text-on-surface font-label-sm font-bold py-3.5 rounded-xl hover:bg-surface-container-high transition-colors flex items-center justify-center gap-2" id="btnInstall">
                <span class="material-symbols-outlined text-[20px]">download</span>
                <span id="installText">Instalar App</span>
            </button>
        </div>

        <div class="mt-8 flex flex-col items-center gap-2">
            <span class="font-body-sm text-[11px] text-on-surface-variant">Desenvolvido por Diego T. N. Vilela</span>
            <a href="https://wa.me/5535984529577" target="_blank" class="font-label-sm font-bold text-[11px] text-primary hover:underline flex items-center gap-1">
                <span class="material-symbols-outlined text-[14px]">chat</span>
                Contato WhatsApp
            </a>
        </div>
    </div>

    <!-- iOS Help Modal -->
    <div id="ios-modal" class="fixed inset-0 bg-black/50 backdrop-blur-sm z-50 hidden items-center justify-center px-4 opacity-0 transition-opacity duration-300">
        <div class="bg-surface w-full max-w-sm rounded-3xl p-6 shadow-2xl transform translate-y-4 transition-transform duration-300" id="ios-modal-card">
            <div class="flex justify-between items-center mb-4">
                <h3 class="font-headline-md text-lg font-bold text-on-surface">Instalar no iPhone</h3>
                <button onclick="toggleIOS()" class="text-on-surface-variant hover:bg-surface-container-high p-1.5 rounded-full transition-colors">
                    <span class="material-symbols-outlined text-[20px]">close</span>
                </button>
            </div>
            <p class="font-body-md text-on-surface-variant leading-relaxed mb-6">
                Para instalar, toque no botão <strong>Compartilhar</strong> <span class="material-symbols-outlined inline-block align-middle text-[18px]">ios_share</span> e depois em <strong>Adicionar à Tela de Início</strong> <span class="material-symbols-outlined inline-block align-middle text-[18px]">add_box</span>.
            </p>
            <button onclick="toggleIOS()" class="w-full bg-primary text-on-primary font-label-sm font-bold py-3.5 rounded-xl shadow-sm hover:bg-primary-container hover:text-on-primary-container transition-colors">
                Entendi
            </button>
        </div>
    </div>

    <script>
        // Init Icons
        lucide.createIcons();

        // Theme Logic
        const savedTheme = localStorage.getItem('theme');
        if (savedTheme === 'dark') {
            document.body.classList.add('dark');
        }

        // PWA Logic
        let deferredPrompt;
        const btnInstall = document.getElementById('btnInstall');
        const installText = document.getElementById('installText');
        const iosModal = document.getElementById('ios-modal');
        const iosModalCard = document.getElementById('ios-modal-card');

        // Detect iOS
        const isIos = /iPad|iPhone|iPod/.test(navigator.userAgent) && !window.MSStream;
        const isStandalone = window.matchMedia('(display-mode: standalone)').matches || window.navigator.standalone;

        // Check if already installed
        if (isStandalone) {
            btnInstall.classList.add('opacity-50', 'pointer-events-none');
            btnInstall.innerHTML = '<span class="material-symbols-outlined text-[20px] text-green-600">check_circle</span><span>App Instalado</span>';
            btnInstall.disabled = true;
        }

        // Listen for install prompt
        window.addEventListener('beforeinstallprompt', (e) => {
            e.preventDefault();
            deferredPrompt = e;
            console.log('Install prompt captured');
        });

        // Handle install button click
        btnInstall.addEventListener('click', async () => {
            // iOS devices - show manual instructions
            if (isIos && !isStandalone) {
                toggleIOS();
                return;
            }

            // If we have the install prompt (Android Chrome, Edge, etc)
            if (deferredPrompt) {
                try {
                    deferredPrompt.prompt();
                    const { outcome } = await deferredPrompt.userChoice;
                    
                    if (outcome === 'accepted') {
                        console.log('User accepted the install prompt');
                        btnInstall.classList.add('opacity-50', 'pointer-events-none');
                        btnInstall.innerHTML = '<span class="material-symbols-outlined text-[20px] text-green-600">check_circle</span><span>App Instalado</span>';
                        btnInstall.disabled = true;
                    } else {
                        console.log('User dismissed the install prompt');
                    }
                    
                    deferredPrompt = null;
                } catch (err) {
                    console.error('Error showing install prompt:', err);
                }
            } else if (!isStandalone) {
                // No install prompt available - show browser-specific instructions
                const userAgent = navigator.userAgent.toLowerCase();
                let message = '';
                
                if (userAgent.includes('chrome')) {
                    message = '📱 Para instalar:\n\n1. Toque no menu (⋮) no canto superior direito\n2. Selecione "Instalar app" ou "Adicionar à tela inicial"';
                } else if (userAgent.includes('firefox')) {
                    message = '📱 Para instalar:\n\n1. Toque no ícone de casa com +\n2. Ou use o menu e selecione "Instalar"';
                } else if (userAgent.includes('safari')) {
                    message = '📱 Para instalar:\n\n1. Toque no botão Compartilhar (□↑)\n2. Selecione "Adicionar à Tela de Início"';
                } else {
                    message = '📱 Para instalar:\n\nUtilize o menu do seu navegador e procure por "Instalar App" ou "Adicionar à Tela Inicial"';
                }
                
                alert(message);
            }
        });

        // Listen for successful installation
        window.addEventListener('appinstalled', (evt) => {
            console.log('App successfully installed');
            btnInstall.classList.add('opacity-50', 'pointer-events-none');
            btnInstall.innerHTML = '<span class="material-symbols-outlined text-[20px] text-green-600">check_circle</span><span>App Instalado</span>';
            btnInstall.disabled = true;
        });

        function toggleIOS() {
            if (iosModal.classList.contains('hidden')) {
                iosModal.classList.remove('hidden');
                iosModal.classList.add('flex');
                // Trigger reflow
                void iosModal.offsetWidth;
                iosModal.classList.remove('opacity-0');
                iosModalCard.classList.remove('translate-y-4');
            } else {
                iosModal.classList.add('opacity-0');
                iosModalCard.classList.add('translate-y-4');
                setTimeout(() => {
                    iosModal.classList.add('hidden');
                    iosModal.classList.remove('flex');
                }, 300);
            }
        }

        // SW Register
        if ('serviceWorker' in navigator) {
            window.addEventListener('load', () => {
                navigator.serviceWorker.register('sw.js').then(reg => {
                    reg.onupdatefound = () => {
                        const newWorker = reg.installing;
                        newWorker.onstatechange = () => {
                            if (newWorker.state === 'installed' && navigator.serviceWorker.controller) {
                                window.location.reload();
                            }
                        };
                    };
                });
            });
        }
    </script>
</body>

</html>