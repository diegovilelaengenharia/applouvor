<?php
// admin/exportar_musicas.php
// Exportar músicas para Excel

require_once '../includes/db.php';

// Buscar todas as músicas
$stmt = $pdo->query("SELECT * FROM songs ORDER BY title ASC");
$songs = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Nome do arquivo
$filename = "repertorio_" . date('Y-m-d_His') . ".csv";

// Headers para download
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');

// Abrir output
$output = fopen('php://output', 'w');

// BOM para UTF-8
fprintf($output, chr(0xEF) . chr(0xBB) . chr(0xBF));

// Cabeçalho
fputcsv($output, [
    'nomeMusica',
    'nomeArtista',
    'tom',
    'bpm',
    'duracao',
    'classificacoes',
    'letra',
    'cifra',
    'audio',
    'video',
    'observacaoMusica',
    'tags'
]);

// Dados
foreach ($songs as $song) {
    fputcsv($output, [
        $song['title'],
        $song['artist'],
        $song['tone'],
        $song['bpm'],
        $song['duration'],
        $song['category'],
        $song['link_letra'],
        $song['link_cifra'],
        $song['link_audio'],
        $song['link_video'],
        $song['notes'],
        $song['tags']
    ]);
}

fclose($output);
exit;
