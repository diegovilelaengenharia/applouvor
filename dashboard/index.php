<?php
/**
 * Painel do Líder — PIB Louvor
 * 
 * Este arquivo atua como o ponto de entrada principal em produção,
 * carregando de forma transparente o index compilado do React (Vite Build)
 * e evitando que o git pull do webhook da Hostinger sobrescreva a build de produção.
 */

$prodIndex = __DIR__ . '/index.prod.html';

if (file_exists($prodIndex)) {
    // Define cabeçalhos para evitar cache agressivo de HTML em navegadores/LiteSpeed
    header("Cache-Control: no-cache, no-store, must-revalidate");
    header("Pragma: no-cache");
    header("Expires: 0");
    
    // Injeta o arquivo HTML gerado no build de produção do Vite
    include $prodIndex;
} else {
    // Fallback amigável caso a build do React ainda não tenha sido enviada via FTP
    header("HTTP/1.1 503 Service Unavailable");
    echo '<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Painel do Líder — PIB Louvor</title>
    <style>
        body {
            background-color: #121316;
            color: #E2E8F0;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            height: 100vh;
            margin: 0;
        }
        .container {
            text-align: center;
            padding: 24px;
        }
        .spinner {
            width: 32px;
            height: 32px;
            border: 3px solid #2e7eed;
            border-top-color: transparent;
            border-radius: 50%;
            animation: spin 0.8s linear infinite;
            margin: 0 auto 24px auto;
        }
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        h1 {
            font-size: 14px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.1em;
            color: #A0AEC0;
            margin: 0;
        }
        p {
            color: #718096;
            font-size: 11px;
            font-weight: 600;
            margin: 8px 0 0 0;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="spinner"></div>
        <h1>Preparando o Painel do Líder...</h1>
        <p>A build de produção está sendo inicializada</p>
    </div>
</body>
</html>';
}
