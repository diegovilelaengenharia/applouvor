<?php
// includes/head.php
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

<!-- Fonte Inter (Google Fonts) -->
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>

<!-- Open Graph / WhatsApp Sharing -->
<meta property="og:type" content="website">
<meta property="og:title" content="App Louvor PIB Oliveira">
<meta property="og:description" content="Gestão de escalas, repertório e ministério de louvor da PIB Oliveira.">
<meta property="og:image" content="https://app.piboliveira.com.br/assets/img/logo_pib_black.png">
<meta property="og:url" content="https://app.piboliveira.com.br/">

<!-- PWA Meta Tags -->
<meta name="theme-color" content="#3b82f6">
<meta name="apple-mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
<meta name="apple-mobile-web-app-title" content="App Louvor PIB">
<meta name="mobile-web-app-capable" content="yes">
<meta name="view-transition" content="same-origin">
<link rel="manifest" href="<?= APP_URL ?>/manifest.json">
<link rel="apple-touch-icon" href="<?= APP_URL ?>/assets/img/logo_pib_black.png">

<!-- Ícones Lucide -->
<script src="https://unpkg.com/lucide@latest"></script>

<!-- APP URL for JS logic -->
<script>const APP_URL = '<?= APP_URL ?>';</script>

<!-- Design System V3 — Fonte única de variáveis -->
<link rel="stylesheet" href="<?= APP_URL ?>/assets/css/design-system.css?v=<?= $verDS ?>">
<!-- Main CSS -->
<link rel="stylesheet" href="<?= APP_URL ?>/assets/css/app-main.css?v=<?= $verMain ?>">
<!-- Mobile Bottom Nav + Sidebar -->
<link rel="stylesheet" href="<?= APP_URL ?>/assets/css/components/mobile-bottom-nav.css?v=<?= $verMain ?>">
<link rel="stylesheet" href="<?= APP_URL ?>/assets/css/components/sidebar.css?v=<?= $verMain ?>">
<link rel="stylesheet" href="<?= APP_URL ?>/assets/css/components/pib-cards.css?v=<?= time() ?>">

<!-- Scripts Globais Críticos -->
<script src="<?= APP_URL ?>/assets/js/theme-toggle.js?v=<?= time() ?>"></script>
<script src="<?= APP_URL ?>/assets/js/layout.js?v=<?= time() ?>"></script>
