<?php
// importar_musicas_excel.php
// Script para importar músicas do Excel para o banco de dados

require_once 'includes/db.php';
require_once 'vendor/autoload.php'; // PhpSpreadsheet

use PhpOffice\PhpSpreadsheet\IOFactory;

$excelFile = 'banco de dados/Musicas_Louveapp_1768828036289.xlsx';

try {
    echo "<h2>Importação de Músicas do Excel</h2>";
    echo "<p>Lendo arquivo: $excelFile</p>";

    $spreadsheet = IOFactory::load($excelFile);
    $worksheet = $spreadsheet->getActiveSheet();
    $rows = $worksheet->toArray();

    // Remover cabeçalho
    $header = array_shift($rows);

    echo "<p>Total de músicas a importar: " . count($rows) . "</p>";

    $stmt = $pdo->prepare("
        INSERT INTO songs (
            title, artist, tone, bpm, duration, category, 
            link_letra, link_cifra, link_audio, link_video, 
            tags, notes, created_at
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
    ");

    $imported = 0;
    $errors = 0;

    foreach ($rows as $index => $row) {
        try {
            // Mapear colunas do Excel
            $title = $row[0] ?? ''; // nomeMusica
            $artist = $row[1] ?? ''; // nomeArtista
            $notes = $row[2] ?? ''; // observacaoMusica
            $tone = $row[5] ?? ''; // tom
            $bpm = !empty($row[6]) ? (int)$row[6] : null; // bpm
            $duration = $row[7] ?? null; // duracao
            $category = $row[8] ?? 'Louvor'; // classificacoes
            $link_letra = $row[9] ?? null; // letra
            $link_cifra = $row[10] ?? null; // cifra
            $link_audio = $row[11] ?? null; // audio
            $link_video = $row[12] ?? null; // video

            // Adicionar tag "Repertório 2025"
            $tags = 'Repertório 2025';

            // Validar campos obrigatórios
            if (empty($title) || empty($artist)) {
                echo "<p style='color: orange;'>⚠️ Linha " . ($index + 2) . ": Música sem título ou artista - pulando</p>";
                continue;
            }

            $stmt->execute([
                $title,
                $artist,
                $tone,
                $bpm,
                $duration,
                $category,
                $link_letra,
                $link_cifra,
                $link_audio,
                $link_video,
                $tags,
                $notes
            ]);

            $imported++;

            if ($imported % 20 == 0) {
                echo "<p>✅ Importadas: $imported músicas...</p>";
                flush();
            }
        } catch (Exception $e) {
            $errors++;
            echo "<p style='color: red;'>❌ Erro na linha " . ($index + 2) . ": " . $e->getMessage() . "</p>";
        }
    }

    echo "<hr>";
    echo "<h3>✅ Importação Concluída!</h3>";
    echo "<p><strong>Total importado:</strong> $imported músicas</p>";
    echo "<p><strong>Erros:</strong> $errors</p>";
    echo "<p><strong>Tag aplicada:</strong> 'Repertório 2025'</p>";
    echo "<br><a href='admin/repertorio.php' style='padding: 10px 20px; background: #2D7A4F; color: white; text-decoration: none; border-radius: 8px;'>Ver Repertório</a>";
} catch (Exception $e) {
    echo "<p style='color: red;'><strong>Erro Fatal:</strong> " . $e->getMessage() . "</p>";
}
