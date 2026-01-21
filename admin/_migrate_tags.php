<?php
// admin/_migrate_tags.php
require_once '../includes/db.php';

echo "<h1>Opa! Iniciando Migração de Tags...</h1>";

// 1. Mapear Categorias Antigas para novas Tags
// Se a categoria antiga não for uma tag no banco, cria.
// Se for, usa o ID existente.

$songs = $pdo->query("SELECT id, category FROM songs WHERE category IS NOT NULL AND category != ''")->fetchAll(PDO::FETCH_ASSOC);

$count = 0;

foreach ($songs as $song) {
    if (empty($song['category'])) continue;

    $categoryName = trim($song['category']);

    // Verifica se a tag existe
    $stmtTag = $pdo->prepare("SELECT id FROM tags WHERE name = ?");
    $stmtTag->execute([$categoryName]);
    $tagId = $stmtTag->fetchColumn();

    // Se não existe, cria
    if (!$tagId) {
        // Gera uma cor aleatória ou padrão
        $colors = ['#047857', '#e11d48', '#2563eb', '#ca8a04', '#7c3aed'];
        $randomColor = $colors[array_rand($colors)];

        $stmtInsert = $pdo->prepare("INSERT INTO tags (name, color) VALUES (?, ?)");
        $stmtInsert->execute([$categoryName, $randomColor]);
        $tagId = $pdo->lastInsertId();
        echo "Tag criada: $categoryName<br>";
    }

    // Vincula a música à tag (se já não estiver)
    $stmtCheck = $pdo->prepare("SELECT COUNT(*) FROM song_tags WHERE song_id = ? AND tag_id = ?");
    $stmtCheck->execute([$song['id'], $tagId]);

    if ($stmtCheck->fetchColumn() == 0) {
        $pdo->prepare("INSERT INTO song_tags (song_id, tag_id) VALUES (?, ?)")->execute([$song['id'], $tagId]);
        $count++;
    }
}

echo "<h2>Sucesso! $count vínculos de tags foram criados automaticamente.</h2>";
echo "<a href='index.php'>Voltar para Dashboard</a>";
