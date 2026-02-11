<?php
// importar_musicas_simples.php
// Script simplificado para importar músicas (requer CSV gerado do Excel)

require_once 'includes/db.php';

echo "<!DOCTYPE html>
<html>
<head>
    <meta charset='UTF-8'>
    <title>Importação de Músicas</title>
    
</head>
<body>";

try {
    echo "<h2>🎵 Importação de Músicas do Excel</h2>";

    // Primeiro, converter Excel para array usando Python
    echo "<div class='progress'>Convertendo Excel para dados...</div>";

    $pythonScript = 'python -c "
import pandas as pd
import json
df = pd.read_excel(\'banco de dados/Musicas_Louveapp_1768828036289.xlsx\')
df = df.fillna(\'\')
print(json.dumps(df.to_dict(\'records\'), ensure_ascii=False))
"';

    $output = shell_exec($pythonScript);

    if (!$output) {
        throw new Exception("Erro ao ler arquivo Excel. Certifique-se de que Python e pandas estão instalados.");
    }

    $musicas = json_decode($output, true);

    if (!$musicas) {
        throw new Exception("Erro ao decodificar dados do Excel");
    }

    echo "<div class='success'>✅ " . count($musicas) . " músicas encontradas no Excel</div>";

    // Preparar statement
    $stmt = $pdo->prepare("
        INSERT INTO songs (
            title, artist, tone, bpm, duration, category, 
            link_letra, link_cifra, link_audio, link_video, 
            tags, notes, created_at
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
    ");

    $imported = 0;
    $errors = 0;

    echo "<div class='progress'>Importando para o banco de dados...</div>";

    foreach ($musicas as $index => $musica) {
        try {
            $title = trim($musica['nomeMusica'] ?? '');
            $artist = trim($musica['nomeArtista'] ?? '');
            $tone = trim($musica['tom'] ?? '');
            $bpm = !empty($musica['bpm']) ? (int)$musica['bpm'] : null;
            $duration = $musica['duracao'] ?? null;
            $category = trim($musica['classificacoes'] ?? 'Louvor');
            $link_letra = trim($musica['letra'] ?? '');
            $link_cifra = trim($musica['cifra'] ?? '');
            $link_audio = trim($musica['audio'] ?? '');
            $link_video = trim($musica['video'] ?? '');
            $notes = trim($musica['observacaoMusica'] ?? '');

            // Tag "Repertório 2025"
            $tags = 'Repertório 2025';

            // Validar
            if (empty($title) || empty($artist)) {
                echo "<div class='warning'>⚠️ Música sem título ou artista na linha " . ($index + 2) . " - pulando</div>";
                continue;
            }

            $stmt->execute([
                $title,
                $artist,
                $tone ?: null,
                $bpm,
                $duration,
                $category,
                $link_letra ?: null,
                $link_cifra ?: null,
                $link_audio ?: null,
                $link_video ?: null,
                $tags,
                $notes ?: null
            ]);

            $imported++;

            if ($imported % 20 == 0) {
                echo "<div class='success'>✅ Importadas: $imported músicas...</div>";
                flush();
            }
        } catch (Exception $e) {
            $errors++;
            echo "<div class='error'>❌ Erro na música '" . ($title ?? 'desconhecida') . "': " . $e->getMessage() . "</div>";
        }
    }

    echo "<hr>";
    echo "<h3 class='success'>✅ Importação Concluída!</h3>";
    echo "<p><strong>Total importado:</strong> $imported músicas</p>";
    echo "<p><strong>Erros:</strong> $errors</p>";
    echo "<p><strong>Tag aplicada:</strong> 'Repertório 2025'</p>";
    echo "<br><a href='admin/repertorio.php' style='padding: 12px 24px; background: #2D7A4F; color: white; text-decoration: none; border-radius: 8px; display: inline-block;'>📚 Ver Repertório</a>";
} catch (Exception $e) {
    echo "<div class='error'><h3>❌ Erro Fatal</h3><p>" . $e->getMessage() . "</p></div>";
    echo "<p>Certifique-se de que:</p>";
    echo "<ul>";
    echo "<li>Python está instalado</li>";
    echo "<li>Biblioteca pandas está instalada (pip install pandas openpyxl)</li>";
    echo "<li>O arquivo Excel está no caminho correto</li>";
    echo "</ul>";
}

echo "</body></html>";
