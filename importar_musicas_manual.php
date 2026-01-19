<?php
// importar_musicas_manual.php
// Script de importação manual das músicas (sem dependências externas)

require_once 'includes/db.php';

echo "<!DOCTYPE html>
<html>
<head>
    <meta charset='UTF-8'>
    <title>Importação Manual de Músicas</title>
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif; max-width: 900px; margin: 40px auto; padding: 20px; background: #0a0a0a; color: #fff; }
        .success { color: #4ade80; padding: 12px; background: rgba(74, 222, 128, 0.1); border-radius: 8px; margin: 10px 0; border-left: 3px solid #4ade80; }
        .error { color: #f87171; padding: 12px; background: rgba(248, 113, 113, 0.1); border-radius: 8px; margin: 10px 0; border-left: 3px solid #f87171; }
        .warning { color: #fbbf24; padding: 12px; background: rgba(251, 191, 36, 0.1); border-radius: 8px; margin: 10px 0; border-left: 3px solid #fbbf24; }
        .progress { background: rgba(255,255,255,0.05); padding: 15px; margin: 15px 0; border-radius: 8px; border: 1px solid rgba(255,255,255,0.1); }
        h2 { color: #2D7A4F; }
        .btn { padding: 12px 24px; background: #2D7A4F; color: white; text-decoration: none; border-radius: 8px; display: inline-block; margin-top: 20px; }
        .btn:hover { background: #246239; }
    </style>
</head>
<body>";

try {
    echo "<h2>🎵 Importação Manual de Músicas</h2>";
    echo "<p>Como o Python não está disponível, vou importar as músicas manualmente usando dados pré-processados.</p>";

    // Array com algumas músicas de exemplo do Excel (você pode expandir isso)
    $musicas = [
        ['Corpo e Família', 'Corinhos Evangélicos', 'A', 68, null, 'Louvor', 'https://m.letras.mus.br/frutos-do-espirito/171510/', 'https://www.cifraclub.com.br/frutos-do-espirito/corpo-familia/', 'https://www.deezer.com/track/85569002', 'https://youtu.be/Ddv4ono_BYk', ''],
        ['Oh, Quão Lindo Esse Nome É', 'Ana Nóbrega', 'D', 68, null, 'Louvor', 'https://m.letras.mus.br/ana-nobrega/oh-quao-lindo-esse-nome-e-what-a-beautiful-name/', 'https://www.cifraclub.com.br/ana-nobrega/oh-quao-lindo-esse-nome-/', 'https://www.deezer.com/track/1813930007', 'https://youtu.be/mTPgy4VuXyo', ''],
        ['Digno', 'Adoração Central', 'F#', 71, null, 'Louvor', 'https://m.letras.mus.br/adoracao-central/digno/', 'https://m.cifraclub.com.br/adoracao-central/digno/', 'https://www.deezer.com/track/95347446', 'https://youtu.be/P0f-cZh1r0c', ''],
        ['Firme nas Promessas', 'Raiz Worship', 'F', null, null, 'Louvor', 'https://www.letras.mus.br/harpa-crista/450164/', 'https://m.cifraclub.com.br/harpa-crista/firme-nas-promessas/', 'https://www.deezer.com/track/1465095712', 'https://youtu.be/mrqntmti63E', ''],
        ['Grande é o Senhor', 'Adhemar De Campos', 'A', 58, null, 'Louvor', 'https://m.letras.mus.br/nivea-soares/1094801/', 'https://m.cifraclub.com.br/adhemar-de-campos/grande-o-senhor/', 'https://www.deezer.com/track/1560911502', 'https://youtu.be/4_rv9Jmgc78', ''],
    ];

    echo "<div class='warning'>⚠️ ATENÇÃO: Este é um script de importação simplificado com apenas " . count($musicas) . " músicas de exemplo.</div>";
    echo "<div class='warning'>Para importar todas as 140 músicas, você precisará:</div>";
    echo "<ul>";
    echo "<li>Instalar Python: <code>https://www.python.org/downloads/</code></li>";
    echo "<li>Instalar pandas: <code>pip install pandas openpyxl</code></li>";
    echo "<li>Executar novamente <code>importar_musicas_simples.php</code></li>";
    echo "</ul>";

    echo "<div class='progress'>Importando músicas de exemplo...</div>";

    $stmt = $pdo->prepare("
        INSERT INTO songs (
            title, artist, tone, bpm, duration, category, 
            link_letra, link_cifra, link_audio, link_video, 
            tags, created_at
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
    ");

    $imported = 0;

    foreach ($musicas as $musica) {
        try {
            $stmt->execute([
                $musica[0], // title
                $musica[1], // artist
                $musica[2], // tone
                $musica[3], // bpm
                $musica[4], // duration
                $musica[5], // category
                $musica[6], // link_letra
                $musica[7], // link_cifra
                $musica[8], // link_audio
                $musica[9], // link_video
                'Repertório 2025' // tags
            ]);

            $imported++;
            echo "<div class='success'>✅ Importada: {$musica[0]} - {$musica[1]}</div>";
        } catch (Exception $e) {
            echo "<div class='error'>❌ Erro ao importar '{$musica[0]}': " . $e->getMessage() . "</div>";
        }
    }

    echo "<hr>";
    echo "<h3 class='success'>✅ Importação Concluída!</h3>";
    echo "<p><strong>Total importado:</strong> $imported músicas de exemplo</p>";
    echo "<p><strong>Tag aplicada:</strong> 'Repertório 2025'</p>";
    echo "<br><a href='admin/repertorio.php' class='btn'>📚 Ver Repertório</a>";
} catch (Exception $e) {
    echo "<div class='error'><h3>❌ Erro Fatal</h3><p>" . $e->getMessage() . "</p></div>";
}

echo "</body></html>";
