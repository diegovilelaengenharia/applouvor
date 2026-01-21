<?php
require_once 'includes/no-cache.php';
require_once 'includes/db.php';
require_once 'includes/auth.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $password = trim($_POST['password']);

    if (empty($name) || empty($password)) {
        $error = "Preencha todos os campos.";
    } else {
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
    <link rel="icon" type="image/png" href="assets/images/logo-black.png">

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/lucide@latest"></script>

    <style>
        :root {
            /* Light Theme */
            --bg: #ffffff;
            --text-main: #18181b;
            /* Zinc 900 */
            --text-muted: #71717a;
            /* Zinc 500 */
            --primary: #047857;
            /* Emerald 700 */
            --primary-light: #ecfdf5;
            /* Emerald 50 */
            --input-bg: #f4f4f5;
            /* Zinc 100 */
            --border: #e4e4e7;
            /* Zinc 200 */
            --error-bg: #fef2f2;
            --error-text: #ef4444;
        }

        body.dark-mode {
            /* Dark Theme */
            --bg: #09090b;
            /* Zinc 950 */
            --text-main: #f4f4f5;
            /* Zinc 100 */
            --text-muted: #a1a1aa;
            /* Zinc 400 */
            --primary: #34d399;
            /* Emerald 400 */
            --primary-light: #064e3b;
            /* Emerald 900 */
            --input-bg: #18181b;
            /* Zinc 900 */
            --border: #27272a;
            /* Zinc 800 */
            --error-bg: #450a0a;
            --error-text: #fca5a5;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            -webkit-tap-highlight-color: transparent;
        }

        body {
            font-family: 'Inter', sans-serif;
            background-color: var(--bg);
            color: var(--text-main);
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 24px;
            transition: background 0.3s, color 0.3s;
        }

        .login-wrapper {
            width: 100%;
            max-width: 380px;
            display: flex;
            flex-direction: column;
            align-items: center;
            text-align: center;
            animation: fadeIn 0.8s ease-out;
        }

        .logo {
            width: 80px;
            height: auto;
            margin-bottom: 32px;
            /* Invert logo in DM if using black png without transparency issues or swap src via JS. 
               Assuming PNG is dark. Filter handles simple inversion for dark mode if needed. */
        }

        body.dark-mode .logo {
            filter: invert(1) brightness(2);
        }

        h1 {
            font-size: 1.5rem;
            font-weight: 700;
            margin-bottom: 8px;
            letter-spacing: -0.025em;
        }

        p.subtitle {
            font-size: 0.95rem;
            color: var(--text-muted);
            margin-bottom: 40px;
        }

        form {
            width: 100%;
            display: flex;
            flex-direction: column;
            gap: 16px;
        }

        input {
            width: 100%;
            padding: 16px;
            background: var(--input-bg);
            border: 1px solid transparent;
            border-radius: 16px;
            font-size: 1rem;
            color: var(--text-main);
            outline: none;
            transition: all 0.2s;
        }

        input:focus {
            background: var(--bg);
            border-color: var(--primary);
            box-shadow: 0 0 0 4px var(--primary-light);
        }

        input::placeholder {
            color: var(--text-muted);
            opacity: 0.7;
        }

        button.btn-primary {
            width: 100%;
            padding: 16px;
            background: var(--text-main);
            /* Black in light, White in dark */
            color: var(--bg);
            /* White in light, Black in dark */
            border: none;
            border-radius: 16px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            margin-top: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            transition: transform 0.1s, opacity 0.2s;
        }

        button.btn-primary:active {
            transform: scale(0.98);
        }

        /* Install Button - Outline Style, Same Size */
        .install-wrapper {
            width: 100%;
            margin-top: 12px;
        }

        button.btn-install {
            width: 100%;
            padding: 16px;
            background: transparent;
            color: var(--primary);
            border: 2px solid var(--primary);
            border-radius: 16px;
            font-size: 1rem;
            font-weight: 600;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            cursor: pointer;
            transition: all 0.2s;
            opacity: 1;
        }

        button.btn-install:hover {
            background: var(--primary-light);
            transform: translateY(-1px);
        }

        .error {
            background: var(--error-bg);
            color: var(--error-text);
            padding: 12px;
            border-radius: 12px;
            font-size: 0.9rem;
            width: 100%;
            margin-bottom: 24px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .credits {
            margin-top: 48px;
            font-size: 0.75rem;
            color: var(--text-muted);
            display: flex;
            flex-direction: column;
            gap: 4px;
        }

        .credits a {
            color: var(--text-main);
            text-decoration: none;
            font-weight: 500;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(10px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* iOS Help Modal */
        #ios-modal {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            background: var(--bg);
            padding: 24px;
            border-radius: 24px 24px 0 0;
            box-shadow: 0 -10px 40px rgba(0, 0, 0, 0.1);
            transform: translateY(100%);
            transition: transform 0.3s cubic-bezier(0.16, 1, 0.3, 1);
            z-index: 100;
            border-top: 1px solid var(--border);
        }

        #ios-modal.show {
            transform: translateY(0);
        }
    </style>
</head>

<body>

    <div class="login-wrapper">
        <img src="assets/images/logo-black.png" alt="Logo" class="logo">

        <h1>Ministério de Louvor</h1>
        <p class="subtitle">Bem-vindo(a) de volta</p>

        <?php if ($error): ?>
            <div class="error">
                <i data-lucide="alert-circle" style="width: 18px;"></i>
                <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <form method="POST">
            <input type="text" name="name" placeholder="Seu nome" required autocomplete="username">
            <input type="password" name="password" placeholder="Senha" required autocomplete="current-password" pattern="[0-9]*" inputmode="numeric">

            <button type="submit" class="btn-primary">
                Entrar
                <i data-lucide="arrow-right" style="width: 18px;"></i>
            </button>
        </form>

        <div class="install-wrapper">
            <button class="btn-install" id="btnInstall">
                <i data-lucide="download" style="width: 16px;"></i>
                Instalar App
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
        const iosModal = document.getElementById('ios-modal');

        // Detect iOS
        const isIos = /iPad|iPhone|iPod/.test(navigator.userAgent) && !window.MSStream;
        const isStandalone = window.matchMedia('(display-mode: standalone)').matches || window.navigator.standalone;

        if (isStandalone) {
            // If already installed, we might hide the button or change text
            btnInstall.innerHTML = '<i data-lucide="check"></i> App Instalado';
            btnInstall.style.pointerEvents = 'none';
            btnInstall.style.opacity = '0.5';
            lucide.createIcons();
        }

        window.addEventListener('beforeinstallprompt', (e) => {
            e.preventDefault();
            deferredPrompt = e;
            // Button is already visible, just prepared
        });

        btnInstall.addEventListener('click', async () => {
            if (isIos && !isStandalone) {
                toggleIOS();
                return;
            }

            if (deferredPrompt) {
                deferredPrompt.prompt();
                const {
                    outcome
                } = await deferredPrompt.userChoice;
                deferredPrompt = null;
            } else {
                // If clicked but no prompt available (desktop chrome, firefox, or already installed)
                if (!isStandalone) {
                    alert('Para instalar: Utilize o menu do seu navegador e procure por "Instalar App" ou "Adicionar à Tela Inicial".');
                }
            }
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