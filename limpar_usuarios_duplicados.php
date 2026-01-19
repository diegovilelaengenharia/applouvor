<?php
// limpar_usuarios_duplicados.php
// Remove usuários duplicados mantendo apenas o mais recente

require_once 'includes/db.php';

echo "<!DOCTYPE html>
<html>
<head>
    <meta charset='UTF-8'>
    <title>Limpeza de Usuários Duplicados</title>
    <style>
        body { font-family: sans-serif; max-width: 800px; margin: 40px auto; padding: 20px; }
        .success { color: green; padding: 10px; background: #e8f5e9; border-radius: 5px; margin: 10px 0; }
        .error { color: red; padding: 10px; background: #ffebee; border-radius: 5px; margin: 10px 0; }
        .info { color: blue; padding: 10px; background: #e3f2fd; border-radius: 5px; margin: 10px 0; }
    </style>
</head>
<body>";

try {
    echo "<h2>🧹 Limpeza de Usuários Duplicados</h2>";

    // Encontrar duplicados
    $duplicates = $pdo->query("
        SELECT name, COUNT(*) as total 
        FROM users 
        GROUP BY name 
        HAVING COUNT(*) > 1
    ")->fetchAll(PDO::FETCH_ASSOC);

    if (empty($duplicates)) {
        echo "<div class='success'>✅ Não há usuários duplicados!</div>";
    } else {
        echo "<div class='info'>📋 Encontrados " . count($duplicates) . " nomes duplicados:</div>";

        foreach ($duplicates as $dup) {
            echo "<div class='info'>- {$dup['name']} ({$dup['total']} registros)</div>";
        }

        $removed = 0;

        foreach ($duplicates as $dup) {
            // Manter apenas o registro mais recente
            $stmt = $pdo->prepare("
                DELETE FROM users 
                WHERE name = ? 
                AND id NOT IN (
                    SELECT id FROM (
                        SELECT id FROM users 
                        WHERE name = ? 
                        ORDER BY id DESC 
                        LIMIT 1
                    ) as keep
                )
            ");

            $stmt->execute([$dup['name'], $dup['name']]);
            $removed += $stmt->rowCount();
        }

        echo "<div class='success'>✅ Removidos $removed registros duplicados!</div>";
        echo "<div class='success'>✅ Mantido 1 registro de cada pessoa (o mais recente)</div>";
    }

    // Mostrar total atual
    $total = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
    echo "<div class='info'>👥 Total de membros únicos: $total</div>";

    echo "<br><a href='admin/membros.php' style='padding: 12px 24px; background: #2D7A4F; color: white; text-decoration: none; border-radius: 8px; display: inline-block;'>Ver Membros</a>";
} catch (Exception $e) {
    echo "<div class='error'>❌ Erro: " . $e->getMessage() . "</div>";
}

echo "</body></html>";
