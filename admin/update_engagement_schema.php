<?php
// admin/update_engagement_schema.php
require_once '../includes/db.php';

echo "<h2>Atualizando Schema para Features de Engajamento...</h2>";

try {
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // 1. Add is_rehearsed to schedule_users
    // Check if column exists
    $stmt = $pdo->query("SHOW COLUMNS FROM schedule_users LIKE 'is_rehearsed'");
    if ($stmt->rowCount() == 0) {
        $pdo->exec("ALTER TABLE schedule_users ADD COLUMN is_rehearsed TINYINT(1) DEFAULT 0");
        echo "<p>✅ Coluna <code>is_rehearsed</code> adicionada em <code>schedule_users</code>.</p>";
    } else {
        echo "<p>ℹ️ Coluna <code>is_rehearsed</code> já existe.</p>";
    }

    // 2. Create schedule_comments table
    $pdo->exec("CREATE TABLE IF NOT EXISTS schedule_comments (
        id INT AUTO_INCREMENT PRIMARY KEY,
        schedule_id INT NOT NULL,
        user_id INT NOT NULL,
        comment TEXT NOT NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (schedule_id) REFERENCES schedules(id) ON DELETE CASCADE,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    echo "<p>✅ Tabela <code>schedule_comments</code> verificada/criada.</p>";

    // 3. Create aviso_reactions table
    $pdo->exec("CREATE TABLE IF NOT EXISTS aviso_reactions (
        id INT AUTO_INCREMENT PRIMARY KEY,
        aviso_id INT NOT NULL,
        user_id INT NOT NULL,
        reaction_type VARCHAR(20) NOT NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY unique_reaction (aviso_id, user_id, reaction_type),
        FOREIGN KEY (aviso_id) REFERENCES avisos(id) ON DELETE CASCADE,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    echo "<p>✅ Tabela <code>aviso_reactions</code> verificada/criada.</p>";

    echo "<h3>Concluído com sucesso!</h3>";
    echo "<a href='index.php'>Voltar ao Admin</a>";

} catch (PDOException $e) {
    echo "<h3 style='color:red'>Erro Inesperado: " . $e->getMessage() . "</h3>";
}
?>
