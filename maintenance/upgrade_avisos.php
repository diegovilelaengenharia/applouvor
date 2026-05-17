<?php
require_once 'includes/db.php';

try {
    $sql = file_get_contents('migrations/003_upgrade_avisos.sql');
    $pdo->exec($sql);
    echo "Tabela 'avisos' atualizada com sucesso (Novas colunas: type, archived_at)!";
} catch (PDOException $e) {
    echo "Erro ao atualizar tabela: " . $e->getMessage();
}
