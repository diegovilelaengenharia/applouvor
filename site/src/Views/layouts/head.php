<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title><?= isset($title) ? htmlspecialchars($title) . ' - Louvor PIB' : 'Louvor PIB' ?></title>

    <!-- PWA Config -->
    <meta name="theme-color" content="#f9f9f9">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="default">
    <link rel="manifest" href="/manifest.json">

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Hanken+Grotesk:wght@600;700&family=Open+Sans:wght@400;600;700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet">

    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com?plugins=forms"></script>
    <link rel="stylesheet" href="/assets/css/stitch-theme.css">
    
    <!-- Theme Manager -->
    <script src="/assets/js/theme.js"></script>
    
    <style>
        body {
            font-family: var(--font-body);
            background-color: var(--surface);
            color: var(--on-surface);
        }
        h1, h2, h3 {
            font-family: var(--font-display);
        }
        .pib-card {
            background-color: var(--surface-container-lowest);
            border: 1px solid rgba(193, 198, 214, 0.4);
            border-radius: var(--radius-xl);
            box-shadow: var(--shadow-card);
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }
        .input-glow:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(46, 126, 237, 0.25);
            outline: none;
        }
        .btn-primary {
            background-color: var(--primary);
            color: var(--on-primary);
            border-radius: var(--radius-full);
            transition: background-color 0.2s ease, transform 0.1s ease;
        }
        .btn-primary:hover {
            background-color: var(--primary-container);
        }
        .btn-primary:active {
            transform: scale(0.98);
        }
        .btn-outline {
            border: 1.5px solid var(--primary);
            color: var(--primary);
            border-radius: var(--radius-full);
            transition: all 0.2s ease;
        }
        .btn-outline:hover {
            background-color: var(--primary);
            color: var(--on-primary);
        }
    </style>
</head>
<body class="antialiased min-h-[100dvh] flex flex-col <?= isset($bodyClass) ? htmlspecialchars($bodyClass) : '' ?>">
