<?php
require_once __DIR__ . '/../../src/config/db.php';

try {
    $sql = file_get_contents('migrations/004_upgrade_avisos_richtext.sql');
    $pdo->exec($sql);
    echo "Tabela 'avisos' atualizada com sucesso (Campo message agora suporta HTML)!";
} catch (PDOException $e) {
    echo "Erro ao atualizar tabela: " . $e->getMessage();
}
