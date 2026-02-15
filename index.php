<?php
require_once 'includes/no-cache.php'; // Force refresh
require_once 'includes/db.php';
require_once 'includes/auth.php';

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
    <meta name="theme-color" content="#ffffff">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="default">
    <link rel="manifest" href="manifest.json">
    <link rel="icon" type="image/png" href="assets/icons/icon-192.png">
    <link rel="apple-touch-icon" href="assets/icons/icon-192.png">

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/lucide@latest"></script>
    <link rel="stylesheet" href="assets/css/design-system.css">
    <link rel="stylesheet" href="assets/css/pages/login.css">
</head>

<body>

    <div class="login-wrapper">
        <img src="assets/images/logo-black.png" alt="Logo" class="logo">

        <h1>Ministério de Louvor</h1>
        <p class="subtitle">A paz do Senhor! 🙏</p>

        <?php if ($error): ?>
            <div class="error">
                <i data-lucide="alert-circle" style="width: 18px;"></i>
                <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <form method="POST">
            <div class="input-wrapper">
                <i data-lucide="user" class="input-icon"></i>
                <input type="text" name="name" placeholder="Seu nome" required autocomplete="username">
            </div>
            <div class="input-wrapper">
                <i data-lucide="lock" class="input-icon"></i>
                <input type="password" name="password" placeholder="Senha" required autocomplete="current-password" pattern="[0-9]*" inputmode="numeric">
            </div>

            <button type="submit" class="btn-primary">
                Entrar
                <i data-lucide="arrow-right" style="width: 18px;"></i>
            </button>
        </form>

        <div class="install-wrapper">
            <button class="btn-install" id="btnInstall">
                <i data-lucide="smartphone" style="width: 18px;"></i>
                <span id="installText">Instalar App</span>
            </button>
        </div>

        <div class="credits">
            <span>Desenvolvido por Diego T. N. Vilela</span>
            <a href="https://wa.me/5535984529577" target="_blank">
                <i data-lucide="message-circle" style="width: 14px; display: inline; vertical-align: middle;"></i>
                Contato WhatsApp
            </a>
        </div>
    </div>

    <!-- iOS Help -->
    <div id="ios-modal">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:16px;">
            <h3 style="font-size: 1.1rem; font-weight: 700;">Instalar no iPhone</h3>
            <button onclick="toggleIOS()" style="background:none; border:none; color:var(--text-muted);"><i data-lucide="x"></i></button>
        </div>
        <p style="color:var(--text-muted); font-size: 0.95rem; line-height: 1.5; margin-bottom: 24px;">
            Para instalar, toque no botão <strong>Compartilhar</strong> <i data-lucide="share" style="display:inline; width:14px;"></i> e depois em <strong>Adicionar à Tela de Início</strong>.
        </p>
        <button onclick="toggleIOS()" style="width:100%; padding:14px; background:var(--input-bg); border:none; border-radius:12px; font-weight:600; color:var(--text-main);">Entendi</button>
    </div>

    <script>
        // Init Icons
        lucide.createIcons();

        // Theme Logic
        const savedTheme = localStorage.getItem('theme');
        if (savedTheme === 'dark') {
            document.body.classList.add('dark-mode');
        }

        // PWA Logic
        let deferredPrompt;
        const btnInstall = document.getElementById('btnInstall');
        const installText = document.getElementById('installText');
        const iosModal = document.getElementById('ios-modal');

        // Detect iOS
        const isIos = /iPad|iPhone|iPod/.test(navigator.userAgent) && !window.MSStream;
        const isStandalone = window.matchMedia('(display-mode: standalone)').matches || window.navigator.standalone;

        // Check if already installed
        if (isStandalone) {
            btnInstall.classList.add('installed');
            btnInstall.innerHTML = '<i data-lucide="check-circle" style="width: 18px;"></i><span>App Instalado</span>';
            btnInstall.disabled = true;
            lucide.createIcons();
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
                        btnInstall.classList.add('installed');
                        btnInstall.innerHTML = '<i data-lucide="check-circle" style="width: 18px;"></i><span>App Instalado</span>';
                        btnInstall.disabled = true;
                        lucide.createIcons();
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
            btnInstall.classList.add('installed');
            btnInstall.innerHTML = '<i data-lucide="check-circle" style="width: 18px;"></i><span>App Instalado</span>';
            btnInstall.disabled = true;
            lucide.createIcons();
        });

        function toggleIOS() {
            iosModal.classList.toggle('show');
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