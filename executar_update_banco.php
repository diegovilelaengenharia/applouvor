<?php
// executar_update_banco.php
// Script para executar o UPDATE da tabela songs

require_once 'includes/db.php';

echo "<!DOCTYPE html>
<html>
<head>
    <meta charset='UTF-8'>
    <title>Atualização do Banco</title>
    <style>
        body { font-family: sans-serif; max-width: 800px; margin: 40px auto; padding: 20px; }
        .success { color: green; padding: 10px; background: #e8f5e9; border-radius: 5px; margin: 10px 0; }
        .error { color: red; padding: 10px; background: #ffebee; border-radius: 5px; margin: 10px 0; }
    </style>
</head>
<body>";

try {
    echo "<h2>🔧 Atualização da Estrutura do Banco de Dados</h2>";

    // Executar ALTER TABLE
    $sql = "
        ALTER TABLE songs 
        ADD COLUMN IF NOT EXISTS bpm INT AFTER tone,
        ADD COLUMN IF NOT EXISTS duration VARCHAR(10) AFTER bpm,
        ADD COLUMN IF NOT EXISTS link_letra VARCHAR(500) AFTER category,
        ADD COLUMN IF NOT EXISTS link_cifra VARCHAR(500) AFTER link_letra,
        ADD COLUMN IF NOT EXISTS link_audio VARCHAR(500) AFTER link_cifra,
        ADD COLUMN IF NOT EXISTS link_video VARCHAR(500) AFTER link_audio,
        ADD COLUMN IF NOT EXISTS tags VARCHAR(255) AFTER link_video,
        ADD COLUMN IF NOT EXISTS notes TEXT AFTER tags
    ";

    $pdo->exec($sql);

    echo "<div class='success'>✅ Tabela 'songs' atualizada com sucesso!</div>";
    echo "<p>Novas colunas adicionadas:</p>";
    echo "<ul>";
    echo "<li>bpm (INT)</li>";
    echo "<li>duration (VARCHAR)</li>";
    echo "<li>link_letra (VARCHAR)</li>";
    echo "<li>link_cifra (VARCHAR)</li>";
    echo "<li>link_audio (VARCHAR)</li>";
    echo "<li>link_video (VARCHAR)</li>";
    echo "<li>tags (VARCHAR)</li>";
    echo "<li>notes (TEXT)</li>";
    echo "</ul>";

    echo "<br><a href='importar_musicas_simples.php' style='padding: 12px 24px; background: #2D7A4F; color: white; text-decoration: none; border-radius: 8px; display: inline-block;'>➡️ Próximo: Importar Músicas</a>";
} catch (PDOException $e) {
    echo "<div class='error'><h3>❌ Erro ao atualizar banco</h3><p>" . $e->getMessage() . "</p></div>";

    // Se o erro for que as colunas já existem, tudo bem
    if (strpos($e->getMessage(), 'Duplicate column') !== false) {
        echo "<div class='success'>✅ As colunas já existem no banco. Pode prosseguir!</div>";
        echo "<br><a href='importar_musicas_simples.php' style='padding: 12px 24px; background: #2D7A4F; color: white; text-decoration: none; border-radius: 8px; display: inline-block;'>➡️ Importar Músicas</a>";
    }
}

echo "</body></html>";
