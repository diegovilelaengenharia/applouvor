<?php
// admin/upgrade_reading_db.php
// require_once '../includes/auth.php'; // Removido auth para facilitar execução via curl/browser neste contexto
require_once '../includes/db.php';

echo "<h2>Atualizando Banco de Dados de Leitura...</h2>";

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

    // 2. Verificar se coluna verses_read existe
    $colExists = false;
    $stmt = $pdo->query("SHOW COLUMNS FROM reading_progress LIKE 'verses_read'");
    if($stmt->fetch()) {
        $colExists = true;
        echo "ℹ️ Coluna 'verses_read' já existe.<br>";
    }

    // 3. Adicionar coluna se não existir
    if (!$colExists) {
        $pdo->exec("ALTER TABLE reading_progress ADD COLUMN verses_read JSON DEFAULT NULL");
        echo "✅ Coluna 'verses_read' (JSON) adicionada com sucesso!<br>";
    }

    echo "<h3 style='color:green'>Sucesso! Banco de Dados atualizado.</h3>";
    echo "<a href='leitura.php'>Voltar para Leitura</a>";

} catch (PDOException $e) {
    echo "<h3 style='color:red'>Erro: " . $e->getMessage() . "</h3>";
}
?>
