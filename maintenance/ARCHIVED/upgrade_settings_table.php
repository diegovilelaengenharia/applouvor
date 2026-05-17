<?php
// admin/upgrade_settings_table.php
require_once '../includes/db.php';

echo "<h2>Atualizando Tabela de Configurações...</h2>";

try {
    $sql = "
    CREATE TABLE IF NOT EXISTS user_settings (
        user_id INT NOT NULL,
        setting_key VARCHAR(50) NOT NULL,
        setting_value TEXT,
        PRIMARY KEY (user_id, setting_key)
    )";
    $pdo->exec($sql);
    echo "✅ Tabela user_settings verificada/criada com sucesso.<br>";

    // Inserir configuração padrão para o admin se não existir
    $pdo->exec("INSERT IGNORE INTO user_settings (user_id, setting_key, setting_value) VALUES (1, 'reading_plan_start_date', '" . date('Y-01-01') . "')");
    echo "✅ Configuração padrão inserida.<br>";

    echo "<h3 style='color:green'>Sucesso!</h3>";

} catch (PDOException $e) {
    echo "<h3 style='color:red'>Erro: " . $e->getMessage() . "</h3>";
}
?>
