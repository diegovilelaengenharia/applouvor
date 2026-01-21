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

        <!-- Open Graph / WhatsApp Sharing -->
        <meta property="og:type" content="website">
        <meta property="og:title" content="App Louvor PIB Oliveira">
        <meta property="og:description" content="Gestão de escalas, repertório e ministério de louvor da PIB Oliveira.">
        <meta property="og:image" content="https://app.piboliveira.com.br/assets/img/logo_pib_black.png"> <!-- Ajuste para URL absoluta real quando possível -->
        <meta property="og:url" content="https://app.piboliveira.com.br/">
        <meta name="theme-color" content="#2D7A4F">

        <!-- Ícones Lucide -->
        <script src="https://unpkg.com/lucide@latest"></script>

        <style>
            <style>

            /* --- DESIGN SYSTEM 2.0 (Moderate & Mobile First) --- */
            :root {
                /* Cores Principais - Emerald (Sofisticado) */
                --primary: #047857;
                /* Emerald 700 */
                --primary-hover: #065f46;
                /* Emerald 800 */
                --primary-light: #d1fae5;
                /* Emerald 100 */
                --primary-subtle: #ecfdf5;
                /* Emerald 50 */

                /* Tons Neutros - Slate (Leitura Confortável) */
                --bg-body: #f8fafc;
                /* Slate 50 */
                --bg-surface: #ffffff;
                /* White */
                --text-main: #334155;
                /* Slate 700 */
                --text-muted: #64748b;
                /* Slate 500 */
                --border-color: #e2e8f0;
                /* Slate 200 */

                /* Espaçamento & Touch (Mobile Friendly) */
                --touch-target: 48px;
                /* Mínimo para Samsung M34 */
                --radius-md: 12px;
                --radius-lg: 16px;
                --shadow-sm: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
                --shadow-md: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
            }

            /* Global Dark Mode */
            body.dark-mode {
                --bg-body: #0f172a;
                /* Slate 900 */
                --bg-surface: #1e293b;
                /* Slate 800 */
                --text-main: #f1f5f9;
                /* Slate 100 */
                --text-muted: #94a3b8;
                /* Slate 400 */
                --border-color: #334155;
                /* Slate 700 */
                --primary-light: #064e3b;
                /* Emerald 900 (fundo) */
                --primary-subtle: #064e3b;
            }

            * {
                box-sizing: border-box;
                -webkit-tap-highlight-color: transparent;
            }

            body {
                font-family: 'Inter', sans-serif;
                margin: 0;
                background-color: var(--bg-body);
                color: var(--text-main);
                font-size: 0.9375rem;
                /* 15px - Melhor leitura */
                line-height: 1.6;
                padding-bottom: 32px;
            }

            /* Tipografia Responsiva */
            h1,
            h2,
            h3,
            h4 {
                color: var(--text-main);
                margin: 0;
            }

            h1 {
                font-size: 1.5rem;
                font-weight: 700;
                letter-spacing: -0.5px;
            }

            /* 24px */
            h2 {
                font-size: 1.25rem;
                font-weight: 600;
                letter-spacing: -0.5px;
            }

            /* 20px */
            h3 {
                font-size: 1.125rem;
                font-weight: 600;
            }

            /* 18px */

            /* Main Content */
            #app-content {
                padding: 20px;
                /* Mais respiro */
                min-height: 100vh;
                margin-left: 0;
                transition: margin-left 0.3s ease;
            }

            @media (min-width: 1025px) {
                #app-content {
                    margin-left: 280px;
                }
            }

            /* Header Mobile */
            .mobile-header {
                display: none;
                align-items: center;
                gap: 16px;
                padding: 16px 20px;
                background: var(--bg-surface);
                position: sticky;
                top: 0;
                z-index: 90;
                border-bottom: 1px solid var(--border-color);
                box-shadow: var(--shadow-sm);
                margin: -20px -20px 24px -20px;
            }

            .btn-menu-trigger {
                width: 44px;
                height: 44px;
                /* Touch Target Grande */
                background: transparent;
                border: none;
                padding: 0;
                margin-left: -12px;
                cursor: pointer;
                color: var(--text-main);
                display: flex;
                align-items: center;
                justify-content: center;
                border-radius: 50%;
                transition: background 0.2s;
            }

            .btn-menu-trigger:active {
                background: var(--border-color);
            }

            .page-title {
                font-size: 1.125rem;
                /* 18px */
                font-weight: 700;
                color: var(--text-main);
                flex: 1;
            }

            @media (max-width: 1024px) {
                .mobile-header {
                    display: flex;
                }

                #app-content {
                    margin-left: 0 !important;
                }
            }
        </style>

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

    // Nova função para cabeçalhos padronizados (Clean Header)
    function renderPageHeader($title, $subtitle = 'Louvor PIB Oliveira', $rightAction = null)
    {
?>
    <header style="
            background: white; 
            padding: 20px 24px; 
            border-bottom: 1px solid #e2e8f0; 
            margin: -16px -16px 24px -16px; 
            display: flex; 
            justify-content: space-between; 
            align-items: center;
            position: sticky;
            top: 0;
            z-index: 20;
        ">
        <!-- Spacer para centralizar o título corretamente, compensando o botão da direita -->
        <div style="width: 40px;">
            <!-- Botão Voltar Profissional -->
            <button onclick="history.back()" class="ripple" style="
                background: transparent; 
                border: 1px solid #e2e8f0; 
                width: 40px; height: 40px; 
                border-radius: 12px; 
                display: flex; align-items: center; justify-content: center; 
                cursor: pointer; 
                color: #64748b;
                transition: all 0.2s;
            " onmouseover="this.style.background='#f1f5f9'; this.style.color='#1e293b'"
                onmouseout="this.style.background='transparent'; this.style.color='#64748b'">
                <i data-lucide="arrow-left" style="width: 20px;"></i>
            </button>
        </div>

        <div style="text-align: center;">
            <h1 style="margin: 0; font-size: 1.25rem; font-weight: 700; color: #1e293b; letter-spacing: -0.5px;"><?= htmlspecialchars($title) ?></h1>
            <?php if ($subtitle): ?>
                <p style="margin: 4px 0 0 0; font-size: 0.85rem; color: #64748b; font-weight: 500;"><?= htmlspecialchars($subtitle) ?></p>
            <?php endif; ?>
        </div>

        <!-- Ação à Direita (Filtros, Adicionar, etc) -->
        <div style="width: 40px; display: flex; justify-content: flex-end;">
            <?php if ($rightAction): ?>
                <?= $rightAction ?>
            <?php endif; ?>
        </div>
    </header>
<?php
    }
?>