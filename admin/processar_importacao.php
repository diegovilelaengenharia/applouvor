<?php
// admin/processar_importacao.php
// Processar importação de músicas do Excel

require_once '../includes/db.php';
require_once '../includes/layout.php';

renderAppHeader('Importando Músicas');

echo "<style>
    .import-log {
        max-width: 800px;
        margin: 20px auto;
        padding: 20px;
    }
    .log-item {
        padding: 12px;
        margin-bottom: 8px;
        border-radius: 8px;
        border-left: 4px solid;
    }
    .log-success {
        background: rgba(74, 222, 128, 0.1);
        border-color: #4ade80;
        color: #4ade80;
    }
    .log-error {
        background: rgba(248, 113, 113, 0.1);
        border-color: #f87171;
        color: #f87171;
    }
    .log-warning {
        background: rgba(251, 191, 36, 0.1);
        border-color: #fbbf24;
        color: #fbbf24;
    }
</style>";

echo "<div class='import-log'>";
echo "<h2 style='text-align: center; margin-bottom: 24px;'>🎵 Importando Músicas</h2>";

try {
    if (!isset($_FILES['excel_file']) || $_FILES['excel_file']['error'] !== UPLOAD_ERR_OK) {
        throw new Exception('Nenhum arquivo foi enviado ou ocorreu um erro no upload.');
    }

    $file = $_FILES['excel_file']['tmp_name'];
    $autoFill = isset($_POST['auto_fill']);

    echo "<div class='log-item log-success'>✅ Arquivo recebido: " . htmlspecialchars($_FILES['excel_file']['name']) . "</div>";

    // Usar Python para ler o Excel
    $pythonScript = 'python -c "
import pandas as pd
import json
import sys
df = pd.read_excel(\'' . str_replace('\\', '/', $file) . '\')
df = df.fillna(\'\')
print(json.dumps(df.to_dict(\'records\'), ensure_ascii=False))
"';

    echo "<div class='log-item log-success'>📖 Lendo arquivo Excel...</div>";
    flush();

    $output = shell_exec($pythonScript);

    if (!$output) {
        throw new Exception('Erro ao processar arquivo Excel. Certifique-se de que Python e pandas estão instalados.');
    }

    $musicas = json_decode($output, true);

    if (!$musicas) {
        throw new Exception('Erro ao decodificar dados do Excel');
    }

    echo "<div class='log-item log-success'>✅ " . count($musicas) . " músicas encontradas no arquivo</div>";
    flush();

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

    echo "<div class='log-item log-success'>💾 Importando para o banco de dados...</div>";
    flush();

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
            $tags = 'Importado ' . date('Y-m-d');

            if (empty($title) || empty($artist)) {
                echo "<div class='log-item log-warning'>⚠️ Linha " . ($index + 2) . ": Música sem título ou artista - pulando</div>";
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

            if ($imported % 10 == 0) {
                echo "<div class='log-item log-success'>✅ Importadas: $imported músicas...</div>";
                flush();
            }
        } catch (Exception $e) {
            $errors++;
            echo "<div class='log-item log-error'>❌ Erro na música '" . ($title ?? 'desconhecida') . "': " . $e->getMessage() . "</div>";
        }
    }

    echo "<hr style='margin: 24px 0;'>";
    echo "<h3 style='color: #4ade80; text-align: center;'>✅ Importação Concluída!</h3>";
    echo "<div style='text-align: center; margin: 20px 0;'>";
    echo "<p style='font-size: 1.2rem;'><strong>Total importado:</strong> $imported músicas</p>";
    echo "<p style='font-size: 1.2rem;'><strong>Erros:</strong> $errors</p>";
    echo "</div>";
    echo "<div style='text-align: center; margin-top: 24px;'>";
    echo "<a href='repertorio.php' class='btn-primary ripple' style='padding: 12px 24px; text-decoration: none; display: inline-block;'>📚 Ver Repertório</a>";
    echo "</div>";
} catch (Exception $e) {
    echo "<div class='log-item log-error'><h3>❌ Erro Fatal</h3><p>" . $e->getMessage() . "</p></div>";
    echo "<div style='text-align: center; margin-top: 24px;'>";
    echo "<a href='importar_excel_page.php' class='btn-outline ripple' style='padding: 12px 24px; text-decoration: none; display: inline-block;'>← Voltar</a>";
    echo "</div>";
}

echo "</div>";

renderAppFooter();
