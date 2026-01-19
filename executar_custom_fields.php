<?php
// executar_custom_fields.php
// Script para adicionar coluna de campos customizados

require_once 'includes/db.php';

echo "<!DOCTYPE html>
<html>
<head>
    <meta charset='UTF-8'>
    <title>Atualização - Campos Customizados</title>
    <style>
        body { font-family: sans-serif; max-width: 800px; margin: 40px auto; padding: 20px; }
        .success { color: green; padding: 10px; background: #e8f5e9; border-radius: 5px; margin: 10px 0; }
        .error { color: red; padding: 10px; background: #ffebee; border-radius: 5px; margin: 10px 0; }
    </style>
</head>
<body>";

try {
    echo "<h2>🔧 Adicionando Suporte a Campos Customizados</h2>";

    $sql = "ALTER TABLE songs ADD COLUMN IF NOT EXISTS custom_fields TEXT AFTER notes";
    $pdo->exec($sql);

    echo "<div class='success'>✅ Coluna 'custom_fields' adicionada com sucesso!</div>";
    echo "<p>Agora você pode adicionar campos personalizados nas músicas, como:</p>";
    echo "<ul>";
    echo "<li>Google Drive</li>";
    echo "<li>Partitura</li>";
    echo "<li>Playback</li>";
    echo "<li>Qualquer outro link customizado</li>";
    echo "</ul>";

    echo "<br><a href='admin/repertorio.php' style='padding: 12px 24px; background: #2D7A4F; color: white; text-decoration: none; border-radius: 8px; display: inline-block;'>➡️ Ir para Repertório</a>";
} catch (PDOException $e) {
    echo "<div class='error'><h3>❌ Erro</h3><p>" . $e->getMessage() . "</p></div>";

    if (strpos($e->getMessage(), 'Duplicate column') !== false) {
        echo "<div class='success'>✅ A coluna já existe. Pode prosseguir!</div>";
        echo "<br><a href='admin/repertorio.php' style='padding: 12px 24px; background: #2D7A4F; color: white; text-decoration: none; border-radius: 8px; display: inline-block;'>➡️ Ir para Repertório</a>";
    }
}

echo "</body></html>";
