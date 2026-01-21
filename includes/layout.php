<?php
// includes/layout.php

// Inicia sessão se não estiver iniciada
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once 'db.php';

function renderAppHeader($title, $backUrl = null)
{
    global $pdo; // Correção: Garante acesso ao banco de dados dentro da função
?>
    <!DOCTYPE html>
    <html lang="pt-BR">

    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
        <title>App Louvor PIB</title>

        <!-- Fonte Inter (Google Fonts) -->
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">

        <!-- Ícones Lucide -->
        <script src="https://unpkg.com/lucide@latest"></script>

        <style>
            :root {
                --primary-green: #047857;
                --primary-dark: #064e3b;
                --bg-light: #f8fafc;
                --text-primary: #1e293b;
                --text-secondary: #64748b;
            }

            * {
                box-sizing: border-box;
                -webkit-tap-highlight-color: transparent;
            }

            body {
                font-family: 'Inter', sans-serif;
                margin: 0;
                background-color: var(--bg-light);
                color: var(--text-primary);
                padding-bottom: 24px;
                /* Espaço footer */
            }

            /* Main Content Container */
            #app-content {
                padding: 16px;
                min-height: 100vh;
                transition: margin-left 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            }

            /* Header Mobile */
            .mobile-header {
                display: none;
                /* Desktop usa sidebar fixa */
                align-items: center;
                gap: 16px;
                padding: 16px;
                background: white;
                position: sticky;
                top: 0;
                z-index: 90;
                box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
                margin: -16px -16px 24px -16px;
                /* Negativo para encostar nas bordas */
            }

            .btn-menu-trigger {
                background: transparent;
                border: none;
                padding: 8px;
                margin-left: -8px;
                cursor: pointer;
                color: var(--text-primary);
                display: flex;
                align-items: center;
                justify-content: center;
                border-radius: 8px;
            }

            .btn-menu-trigger:active {
                background: #f1f5f9;
            }

            .page-title {
                font-size: 1.1rem;
                font-weight: 700;
                color: var(--text-primary);
                flex: 1;
            }

            @media (max-width: 1024px) {
                .mobile-header {
                    display: flex;
                }

                /* Aparece no Mobile */
                #app-content {
                    margin-left: 0 !important;
                }

                /* Remove margem desktop */
            }

            /* Utilitários Universais */
            .ripple {
                position: relative;
                overflow: hidden;
                transform: translate3d(0, 0, 0);
            }

            .ripple:after {
                content: "";
                display: block;
                position: absolute;
                width: 100%;
                height: 100%;
                top: 0;
                left: 0;
                pointer-events: none;
                background-image: radial-gradient(circle, #fff 10%, transparent 10.01%);
                background-repeat: no-repeat;
                background-position: 50%;
                transform: scale(10, 10);
                opacity: 0;
                transition: transform .5s, opacity 1s;
            }

            .ripple:active:after {
                transform: scale(0, 0);
                opacity: 0.2;
                transition: 0s;
            }
        </style>
    </head>

    <body>

        <!-- Incluir Sidebar -->
        <?php include_once 'sidebar.php'; ?>

        <div id="app-content">
            <!-- Header Mobile (Só visível em telas menores) -->
            <header class="mobile-header">
                <button class="btn-menu-trigger" onclick="toggleSidebar()">
                    <i data-lucide="menu" style="width: 24px; height: 24px;"></i>
                </button>
                <div class="page-title"><?= htmlspecialchars($title) ?></div>

                <!-- Ações do Header (opcional) -->
                <?php if (strpos($_SERVER['PHP_SELF'], 'repertorio.php') !== false): ?>
                    <div style="background: #ecfdf5; padding: 6px; border-radius: 8px;">
                        <i data-lucide="search" style="color: #047857; width: 20px;"></i>
                    </div>
                <?php endif; ?>
            </header>

        <?php
    }

    function renderAppFooter()
    {
        ?>
        </div> <!-- Fim #app-content -->

        <!-- Inicializar Ícones -->
        <script>
            lucide.createIcons();
        </script>
    </body>

    </html>
<?php
    }

    // Funções de Helper Antigas (Manter por compatibilidade)
    function renderGlobalNavButtons()
    {
        // Deprecated - Agora controlado pela Sidebar
    }
?>