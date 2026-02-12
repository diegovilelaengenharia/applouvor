<?php
require_once __DIR__ . '/../includes/db.php';

try {
    echo "Criando tabela notifications...\n";

    $sql = "CREATE TABLE IF NOT EXISTS notifications (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        title VARCHAR(100),
        message TEXT NOT NULL,
        link VARCHAR(255) NULL,
        is_read TINYINT(1) DEFAULT 0,
        type VARCHAR(50) DEFAULT 'info',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    )";

    $pdo->exec($sql);
    echo "Tabela notifications criada/verificada.\n";
} catch (Exception $e) {
    echo "Erro fatal: " . $e->getMessage() . "\n";
}
