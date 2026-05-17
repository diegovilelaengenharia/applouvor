<?php
require_once __DIR__ . '/../includes/db.php';

try {
    echo "Criando tabela user_unavailability...\n";

    $sql = "CREATE TABLE IF NOT EXISTS user_unavailability (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        start_date DATE NOT NULL,
        end_date DATE NOT NULL,
        reason TEXT,
        replacement_id INT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
        FOREIGN KEY (replacement_id) REFERENCES users(id) ON DELETE SET NULL
    )";

    $pdo->exec($sql);
    echo "Tabela user_unavailability criada/verificada.\n";
} catch (Exception $e) {
    echo "Erro fatal: " . $e->getMessage() . "\n";
}
