<?php
// setup_prayers.php - Executa uma única vez para criar tabela de orações
require_once 'includes/db.php';

echo "<h2>Setup das Tabelas de Oração</h2>";

try {
    // Tabela de pedidos de oração
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS prayer_requests (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            title VARCHAR(200) NOT NULL,
            description TEXT,
            category ENUM('health', 'family', 'work', 'spiritual', 'gratitude', 'other') DEFAULT 'other',
            is_urgent BOOLEAN DEFAULT FALSE,
            is_anonymous BOOLEAN DEFAULT FALSE,
            is_answered BOOLEAN DEFAULT FALSE,
            prayer_count INT DEFAULT 0,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            answered_at TIMESTAMP NULL,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        )
    ");
    echo "<p>✅ Tabela <b>prayer_requests</b> criada/verificada.</p>";

    // Tabela de quem orou
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS prayer_interactions (
            id INT AUTO_INCREMENT PRIMARY KEY,
            prayer_id INT NOT NULL,
            user_id INT NOT NULL,
            type ENUM('pray', 'comment') DEFAULT 'pray',
            comment TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (prayer_id) REFERENCES prayer_requests(id) ON DELETE CASCADE,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        )
    ");
    echo "<p>✅ Tabela <b>prayer_interactions</b> criada/verificada.</p>";

    echo "<br><p style='color:green; font-weight:bold;'>🎉 Setup concluído com sucesso!</p>";
    echo "<p><a href='admin/oracao.php'>Ir para Mural de Oração →</a></p>";

} catch (PDOException $e) {
    echo "<p style='color:red;'>❌ Erro: " . $e->getMessage() . "</p>";
}
?>
