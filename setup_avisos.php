<?php
require_once 'includes/db.php';

try {
    $sql = file_get_contents('migrations/002_create_avisos_table.sql');
    $pdo->exec($sql);
    echo "Tabela 'avisos' criada com sucesso!";
} catch (PDOException $e) {
    echo "Erro ao criar tabela: " . $e->getMessage();
}
