<?php
// src/layout/head.php
// Este arquivo contém o conteúdo da tag <head> e deve ser incluído dentro do contexto onde $title está definido

if (!isset($title)) {
    $title = 'App Louvor PIB';
}

// Cache Busting Inteligente
$dsPath = __DIR__ . '/../assets/css/design-system.css';
$pathMain = __DIR__ . '/../assets/css/app-main.css';
$verDS = file_exists($dsPath) ? filemtime($dsPath) : time();
$verMain = file_exists($pathMain) ? filemtime($pathMain) : time();
?>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= htmlspecialchars($title) ?></title>

<!-- Fontes do Stitch Theme (Sacred Minimalist) -->
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Hanken+Grotesk:wght@600;700&family=Open+Sans:wght@400;600;700&display=swap" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet">

<!-- Open Graph / WhatsApp Sharing -->
<meta property="og:type" content="website">
<meta property="og:title" content="App Louvor PIB Oliveira">
<meta property="og:description" content="Gestão de escalas, repertório e ministério de louvor da PIB Oliveira.">
<meta property="og:image" content="https://app.piboliveira.com.br/assets/images/logo_pib_black.png">
<meta property="og:url" content="https://app.piboliveira.com.br/">

<!-- PWA Meta Tags -->
<meta name="theme-color" content="#3b82f6">
<meta name="apple-mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
<meta name="apple-mobile-web-app-title" content="App Louvor PIB">
<meta name="mobile-web-app-capable" content="yes">
<meta name="view-transition" content="same-origin">
<link rel="manifest" href="<?= APP_URL ?>/manifest.json">
<link rel="apple-touch-icon" href="<?= APP_URL ?>/assets/images/logo_pib_black.png">

<!-- Ícones Lucide -->
<script src="https://unpkg.com/lucide@latest"></script>

<!-- APP URL for JS logic -->
<script>const APP_URL = '<?= APP_URL ?>';</script>

<!-- Dark mode auto-detect: aplica o tema salvo no localStorage no first-paint para evitar FOUC -->
<script>
    (function () {
        try {
            var theme = localStorage.getItem('theme');
            if (theme === 'dark') {
                document.documentElement.classList.add('dark');
            } else {
                document.documentElement.classList.remove('dark');
            }
        } catch (e) {}
    })();
</script>

<!-- CSS crítico anti-FOUC: pinta fundo/superfície antes do Tailwind CDN compilar -->
<style>
    html { background-color: #f9f9f9; }
    html.dark { background-color: #0F1012; }
    body { background-color: inherit; color: #1a1c1c; font-family: 'Open Sans', sans-serif; }
    html.dark body { color: #F3F4F6; }

    /* Home no desktop (>=1025px): sidebar já traz marca + perfil, então
       ocultamos a barra superior (menu + WorshipFlow + avatar) e zeramos
       o espaço reservado por ela. No mobile a barra permanece. */
    @media (min-width: 1025px) {
        body.page-is-dashboard > #app-content > header { display: none; }
        body.page-is-dashboard { padding-top: 0; }
    }

    /* Dark mode ativado por padrão */
</style>

<!-- Tailwind CSS (Stitch Theme) -->
<script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
<script id="tailwind-config">
tailwind.config = {
    darkMode: "class",
    theme: {
        extend: {
            // Tokens apontam para variáveis CSS (stitch-theme.css define claro + dark).
            // O hex é apenas fallback. Assim utilitários como bg-surface/text-on-surface
            // se adaptam automaticamente ao dark mode via cascata de variáveis.
            "colors": {
                "surface-bright": "var(--surface-bright, #f9f9f9)",
                "tertiary-fixed": "var(--tertiary-fixed, #ffdf9e)",
                "surface-tint": "var(--surface-tint, #005cbc)",
                "on-tertiary-fixed-variant": "var(--on-tertiary-fixed-variant, #5b4300)",
                "on-tertiary": "var(--on-tertiary, #ffffff)",
                "secondary-fixed": "var(--secondary-fixed, #e3e2e7)",
                "primary": "var(--primary, #0059b8)",
                "tertiary-container": "var(--tertiary-container, #946f00)",
                "on-primary-container": "var(--on-primary-container, #fefcff)",
                "inverse-on-surface": "var(--inverse-on-surface, #f0f1f1)",
                "error-container": "var(--error-container, #ffdad6)",
                "on-error-container": "var(--on-error-container, #93000a)",
                "outline-variant": "var(--outline-variant, #c1c6d6)",
                "surface-container-high": "var(--surface-container-high, #e8e8e8)",
                "on-secondary": "var(--on-secondary, #ffffff)",
                "secondary": "var(--secondary, #5e5e63)",
                "tertiary-fixed-dim": "var(--tertiary-fixed-dim, #fabd00)",
                "on-primary": "var(--on-primary, #ffffff)",
                "on-secondary-fixed-variant": "var(--on-secondary-fixed-variant, #46464b)",
                "secondary-container": "var(--secondary-container, #e0dfe4)",
                "background": "var(--background, #f9f9f9)",
                "on-primary-fixed": "var(--on-primary-fixed, #001b3f)",
                "on-secondary-fixed": "var(--on-secondary-fixed, #1a1b1f)",
                "inverse-surface": "var(--inverse-surface, #2f3131)",
                "tertiary": "var(--tertiary, #755700)",
                "secondary-fixed-dim": "var(--secondary-fixed-dim, #c7c6cb)",
                "on-surface-variant": "var(--on-surface-variant, #414753)",
                "deep-navy": "var(--deep-navy, #1A1B1F)",
                "surface": "var(--surface, #f9f9f9)",
                "inverse-primary": "var(--inverse-primary, #abc7ff)",
                "surface-container-low": "var(--surface-container-low, #f3f3f3)",
                "surface-dim": "var(--surface-dim, #dadada)",
                "surface-container-highest": "var(--surface-container-highest, #e2e2e2)",
                "on-background": "var(--on-background, #1a1c1c)",
                "outline": "var(--outline, #727785)",
                "on-surface": "var(--on-surface, #1a1c1c)",
                "primary-container": "var(--primary-container, #1872e0)",
                "primary-fixed": "var(--primary-fixed, #d7e2ff)",
                "ghost-gray": "var(--ghost-gray, #F4F4F5)",
                "error": "var(--error, #ba1a1a)",
                "surface-container": "var(--surface-container, #eeeeee)",
                "altar-gold": "var(--altar-gold, #FFC107)",
                "surface-variant": "var(--surface-variant, #e2e2e2)",
                "primary-fixed-dim": "var(--primary-fixed-dim, #abc7ff)",
                "worship-blue": "var(--worship-blue, #2E7EED)",
                "on-tertiary-container": "var(--on-tertiary-container, #fffbff)",
                "on-tertiary-fixed": "var(--on-tertiary-fixed, #261a00)",
                "on-primary-fixed-variant": "var(--on-primary-fixed-variant, #004590)",
                "surface-container-lowest": "var(--surface-container-lowest, #ffffff)",
                "on-secondary-container": "var(--on-secondary-container, #626267)",
                "on-error": "var(--on-error, #ffffff)"
            },
            "fontFamily": {
                "outfit": ["Hanken Grotesk", "Open Sans", "sans-serif"],
                "lyric-focus": ["Open Sans"],
                "display-lg-mobile": ["Hanken Grotesk"],
                "body-lg": ["Open Sans"],
                "label-sm": ["Open Sans"],
                "body-md": ["Open Sans"],
                "headline-md": ["Hanken Grotesk"],
                "headline-md-mobile": ["Hanken Grotesk"],
                "display-lg": ["Hanken Grotesk"]
            },
            // Escala tipográfica do Stitch (Sacred Minimalist) — estava faltando no app
            "fontSize": {
                "lyric-focus": ["28px", { "lineHeight": "40px", "letterSpacing": "-0.01em", "fontWeight": "600" }],
                "display-lg-mobile": ["32px", { "lineHeight": "40px", "letterSpacing": "-0.01em", "fontWeight": "700" }],
                "body-lg": ["18px", { "lineHeight": "28px", "fontWeight": "400" }],
                "label-sm": ["12px", { "lineHeight": "16px", "letterSpacing": "0.05em", "fontWeight": "700" }],
                "body-md": ["16px", { "lineHeight": "24px", "fontWeight": "400" }],
                "headline-md": ["24px", { "lineHeight": "32px", "fontWeight": "600" }],
                "headline-md-mobile": ["20px", { "lineHeight": "28px", "fontWeight": "700" }],
                "display-lg": ["48px", { "lineHeight": "56px", "letterSpacing": "-0.02em", "fontWeight": "700" }]
            },
            // Espaçamento do Stitch (grid 8px, margens mobile/desktop)
            "spacing": {
                "margin-mobile": "20px",
                "unit": "8px",
                "max-width": "1200px",
                "gutter": "16px",
                "margin-desktop": "64px"
            },
            // Raios do Stitch
            "borderRadius": {
                "DEFAULT": "0.25rem",
                "lg": "0.5rem",
                "xl": "0.75rem",
                "full": "9999px"
            }
        }
    }
}
</script>

<!-- Design System V3 — Fonte única de variáveis do Stitch -->
<link rel="stylesheet" href="<?= APP_URL ?>/assets/css/stitch-theme.css?v=<?= time() ?>">
<!-- Main CSS (Ponto de entrada centralizado de componentes e páginas) -->
<link rel="stylesheet" href="<?= APP_URL ?>/assets/css/app-main.css?v=<?= $verMain ?>">

<!-- Scripts Globais Críticos -->
<script src="<?= APP_URL ?>/assets/js/theme-toggle.js?v=<?= time() ?>"></script>
<script src="<?= APP_URL ?>/assets/js/layout.js?v=<?= time() ?>"></script>

<!-- Dark mode ativado e sincronizado com o theme-toggle.js -->
