<?php
require_once 'includes/db.php';

try {
    $pdo->exec("ALTER TABLE schedules ADD COLUMN event_time TIME DEFAULT '19:00'");
    echo "Coluna event_time adicionada com sucesso.";
} catch (PDOException $e) {
    if (strpos($e->getMessage(), "Duplicate column name") !== false) {
        echo "Coluna event_time já existe.";
    } else {
        echo "Erro: " . $e->getMessage();
    }
}
?>
