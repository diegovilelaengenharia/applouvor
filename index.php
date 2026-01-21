<?php
require_once 'includes/no-cache.php';
require_once 'includes/db.php';
require_once 'includes/auth.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $password = trim($_POST['password']);

    if (empty($name) || empty($password)) {
        $error = "Por favor, preencha todos os campos.";
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
            $error = "Nome ou senha incorretos.";
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

    <!-- PWA Fullscreen & Mobile -->
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="default">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="theme-color" content="#ffffff">

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    <link rel="icon" type="image/png" href="assets/images/logo-black.png">
    <link rel="manifest" href="manifest.json">

    <!-- Icons -->
    <script src="https://unpkg.com/lucide@latest"></script>

    <style>
        /* --- Login Page Styles (Light & Clean) --- */
        :root {
            --primary: #047857;
            /* Emerald 700 */
            --primary-hover: #065f46;
            /* Emerald 800 */
            --bg-body: #f8fafc;
            /* Slate 50 */
            --bg-surface: #ffffff;
            --text-main: #1e293b;
            /* Slate 800 */
            --text-muted: #64748b;
            /* Slate 500 */
            --border: #e2e8f0;
            /* Slate 200 */
            --shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
        }

        body.dark-mode {
            --primary: #34d399;
            /* Emerald 400 */
            --primary-hover: #10b981;
            /* Emerald 500 */
            --bg-body: #0f172a;
            /* Slate 900 */
            --bg-surface: #1e293b;
            /* Slate 800 */
            --text-main: #f1f5f9;
            /* Slate 100 */
            --text-muted: #94a3b8;
            /* Slate 400 */
            --border: #334155;
            /* Slate 700 */
            --shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.5);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            -webkit-tap-highlight-color: transparent;
        }

        body {
            font-family: 'Inter', sans-serif;
            background-color: var(--bg-body);
            color: var(--text-main);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .login-container {
            width: 100%;
            max-width: 400px;
            background: var(--bg-surface);
            border-radius: 24px;
            padding: 40px 32px;
            box-shadow: var(--shadow);
            text-align: center;
            border: 1px solid var(--border);
            animation: fadeIn 0.6s ease-out;
        }

        .brand-logo {
            width: 90px;
            height: auto;
            margin-bottom: 24px;
        }

        .app-title {
            font-size: 1.5rem;
            font-weight: 800;
            color: var(--text-main);
            margin-bottom: 8px;
            letter-spacing: -0.5px;
        }

        .app-subtitle {
            font-size: 0.95rem;
            color: var(--text-muted);
            margin-bottom: 32px;
            font-weight: 500;
        }

        .form-group {
            margin-bottom: 20px;
            text-align: left;
        }

        .form-label {
            display: block;
            font-size: 0.85rem;
            font-weight: 600;
            color: var(--text-main);
            margin-bottom: 8px;
        }

        .form-input {
            width: 100%;
            padding: 14px 16px;
            border: 2px solid var(--border);
            border-radius: 12px;
            font-size: 1rem;
            color: var(--text-main);
            outline: none;
            transition: all 0.2s;
            background: #f8fafc;
        }

        .form-input:focus {
            border-color: var(--primary);
            background: #fff;
            box-shadow: 0 0 0 4px rgba(4, 120, 87, 0.1);
        }

        .btn-primary {
            width: 100%;
            padding: 16px;
            background: var(--primary);
            color: white;
            border: none;
            border-radius: 12px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: background 0.2s, transform 0.1s;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            margin-top: 12px;
        }

        .btn-primary:hover {
            background: var(--primary-hover);
        }

        .btn-primary:active {
            transform: scale(0.98);
        }

        .btn-secondary {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            padding: 8px 16px;
            background: transparent;
            color: var(--primary);
            border: 1px dashed var(--border);
            /* Modern "dashed" look or just transparent */
            border-radius: 20px;
            /* Pill shape */
            font-size: 0.85rem;
            font-weight: 500;
            cursor: pointer;
            text-decoration: none;
            transition: all 0.2s;
            margin-top: 0;
        }

        .btn-secondary:hover {
            background: rgba(4, 120, 87, 0.05);
            border-color: var(--primary);
        }

        /* PWA Install Area - Minimalist */
        #pwa-install-area,
        #pwa-ios-area {
            margin-top: 16px;
            padding-top: 0;
            border-top: none;
            display: flex;
            justify-content: center;
        }

        .ios-instruction {
            margin-top: 12px;
            background: #f8fafc;
            padding: 16px;
            border-radius: 12px;
            font-size: 0.85rem;
            color: var(--text-muted);
            text-align: left;
            border: 1px solid var(--border);
        }
    </style>
</head>

<body>

    <div class="login-container">
        <img src="assets/images/logo-black.png" alt="Logo PIB Oliveira" class="brand-logo">
        <h1 class="app-title">Ministério de Louvor</h1>
        <p class="app-subtitle">Acesso Restrito à Equipe</p>

        <?php if ($error): ?>
            <div class="error-message">
                <i data-lucide="alert-circle" style="width: 18px;"></i>
                <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <form method="POST">
            <div class="form-group">
                <label class="form-label">Seu Nome</label>
                <input type="text" name="name" class="form-input" placeholder="Ex: Diego" required autocomplete="username">
            </div>

            <div class="form-group">
                <label class="form-label">Senha de Acesso</label>
                <input type="password" name="password" class="form-input" placeholder="••••" required autocomplete="current-password" inputmode="numeric" pattern="[0-9]*">
            </div>

            <button type="submit" class="btn-primary">
                Entrar
                <i data-lucide="arrow-right" style="width: 18px;"></i>
            </button>
        </form>

        <!-- PWA Install -->
        <div id="pwa-install-area" style="display: none;">
            <button id="btnInstallAndroid" class="btn-secondary">
                <i data-lucide="download" style="width: 16px;"></i> Instalar App
            </button>
        </div>

        <div id="pwa-ios-area" style="display: none;">
            <button class="btn-secondary" onclick="toggleIOSHelp()">
                <i data-lucide="share" style="width: 16px;"></i> Instalar no iPhone
            </button>
            <div id="iosHelp" class="ios-instruction" style="display: none;">
                <p><strong>Para instalar:</strong></p>
                <ol style="margin-top: 8px; margin-left: 20px; line-height: 1.6;">
                    <li>Toque no botão <strong>Compartilhar</strong> <i data-lucide="share" style="width: 12px; display: inline;"></i> abaixo.</li>
                    <li>Escolha <strong>"Adicionar à Tela de Início"</strong>.</li>
                </ol>
            </div>
        </div>

        <div class="footer-credits">
            <p>Desenvolvido por <span style="font-weight: 600;">Diego T. N. Vilela</span></p>
            <a href="https://wa.me/5535984529577" target="_blank" style="margin-top: 8px; display: inline-block;">
                Suporte Técnico
            </a>
            <p style="margin-top: 8px; opacity: 0.6;">v2.1.0 • 2026</p>
        </div>
    </div>

    <script>
        // Check saved theme and apply
        const savedTheme = localStorage.getItem('theme');
        if (savedTheme === 'dark') {
            document.body.classList.add('dark-mode');
        }

        lucide.createIcons();

        // PWA Registration & Logic
        if ('serviceWorker' in navigator) {
            window.addEventListener('load', () => {
                navigator.serviceWorker.register('sw.js').then(reg => {
                    // Check for updates
                    reg.onupdatefound = () => {
                        const newWorker = reg.installing;
                        newWorker.onstatechange = () => {
                            if (newWorker.state === 'installed' && navigator.serviceWorker.controller) {
                                // New version available, reload to update
                                window.location.reload();
                            }
                        };
                    };
                });
            });
        }

        // Install Prompts
        let deferredPrompt;
        const installArea = document.getElementById('pwa-install-area');
        const btnAndroid = document.getElementById('btnInstallAndroid');

        window.addEventListener('beforeinstallprompt', (e) => {
            e.preventDefault();
            deferredPrompt = e;
            installArea.style.display = 'block';
        });

        if (btnAndroid) {
            btnAndroid.addEventListener('click', async () => {
                if (deferredPrompt) {
                    deferredPrompt.prompt();
                    const {
                        outcome
                    } = await deferredPrompt.userChoice;
                    deferredPrompt = null;
                    installArea.style.display = 'none';
                }
            });
        }

        // iOS Detection
        const isIos = /iPad|iPhone|iPod/.test(navigator.userAgent) && !window.MSStream;
        const isStandalone = window.matchMedia('(display-mode: standalone)').matches || window.navigator.standalone;

        if (isIos && !isStandalone) {
            document.getElementById('pwa-ios-area').style.display = 'block';
        }

        function toggleIOSHelp() {
            const help = document.getElementById('iosHelp');
            help.style.display = help.style.display === 'none' ? 'block' : 'none';
        }
    </script>
</body>

</html>