<?php
// setup_reading_db.php
require_once 'includes/db.php';

try {
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS reading_progress (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            month_num INT NOT NULL,
            day_num INT NOT NULL,
            completed_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            comment TEXT,
            UNIQUE KEY unique_user_reading (user_id, month_num, day_num),
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ");
    echo "Tabela 'reading_progress' criada ou já existente.<br>";

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS user_settings (
            user_id INT NOT NULL,
            setting_key VARCHAR(50) NOT NULL,
            setting_value TEXT,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (user_id, setting_key),
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ");
    echo "Tabela 'user_settings' criada ou já existente.<br>";

    // Add notification_time column if not using user_settings table? 
    // The user_settings table is more flexible.

    echo "Configuração de Banco de Dados concluída com sucesso.";

} catch (PDOException $e) {
    echo "Erro ao criar tabelas: " . $e->getMessage();
}
?>
