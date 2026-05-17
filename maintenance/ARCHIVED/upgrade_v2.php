<?php
// admin/upgrade_v2.php
require_once '../includes/db.php';

echo "<h2>Atualizando Banco de Dados de Leitura (V2)...</h2>";

try {
    // 1. Criar tabela se não existir
    $sqlCreate = "
    CREATE TABLE IF NOT EXISTS reading_progress (
        user_id INT NOT NULL,
        month_num INT NOT NULL,
        day_num INT NOT NULL,
        comment TEXT,
        completed_at DATETIME,
        PRIMARY KEY (user_id, month_num, day_num)
    )";
    $pdo->exec($sqlCreate);
    echo "✅ Tabela reading_progress verificada.<br>";

    // 2. Verificar e Atualizar Coluna verses_read
    try {
        $pdo->exec("ALTER TABLE reading_progress ADD COLUMN verses_read JSON DEFAULT NULL");
        echo "✅ Coluna 'verses_read' (JSON) adicionada.<br>";
    } catch (PDOException $e) {
        // Ignorar se já existe (Duplicate column name)
        echo "ℹ️ Alteração de coluna saltada (provavelmente já existe).<br>";
    }

    echo "<h3 style='color:green'>Sucesso Total!</h3>";

} catch (PDOException $e) {
    echo "<h3 style='color:red'>Erro Crítico: " . $e->getMessage() . "</h3>";
}
?>
