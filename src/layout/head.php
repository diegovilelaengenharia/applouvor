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
            "fontFamily": {
                "lyric-focus": ["Open Sans"],
                "display-lg-mobile": ["Hanken Grotesk"],
                "body-lg": ["Open Sans"],
                "label-sm": ["Open Sans"],
                "body-md": ["Open Sans"],
                "headline-md": ["Hanken Grotesk"],
                "display-lg": ["Hanken Grotesk"]
            }
        }
    }
}
</script>

<!-- Design System V3 — Fonte única de variáveis -->
<link rel="stylesheet" href="<?= APP_URL ?>/assets/css/stitch-theme.css?v=<?= time() ?>">
<link rel="stylesheet" href="<?= APP_URL ?>/assets/css/design-system.css?v=<?= $verDS ?>">
<!-- Main CSS -->
<link rel="stylesheet" href="<?= APP_URL ?>/assets/css/app-main.css?v=<?= $verMain ?>">
<!-- Barra Superior Global (Cache Busting direto para evitar cache antigo de importação) -->
<link rel="stylesheet" href="<?= APP_URL ?>/assets/css/components/page-sub-header.css?v=<?= time() ?>">
<!-- Mobile Bottom Nav + Sidebar -->
<link rel="stylesheet" href="<?= APP_URL ?>/assets/css/components/mobile-bottom-nav.css?v=<?= $verMain ?>">
<link rel="stylesheet" href="<?= APP_URL ?>/assets/css/components/sidebar.css?v=<?= $verMain ?>">
<link rel="stylesheet" href="<?= APP_URL ?>/assets/css/components/pib-cards.css?v=<?= time() ?>">
<link rel="stylesheet" href="<?= APP_URL ?>/assets/css/components/dashboard-hero.css?v=<?= time() ?>">

<!-- Scripts Globais Críticos -->
<script src="<?= APP_URL ?>/assets/js/theme-toggle.js?v=<?= time() ?>"></script>
<script src="<?= APP_URL ?>/assets/js/layout.js?v=<?= time() ?>"></script>
