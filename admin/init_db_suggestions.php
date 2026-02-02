<?php
// admin/init_db_suggestions.php
// Script de auto-inicialização da tabela song_suggestions
// Deve ser incluído onde for necessário garantir que a tabela exista

if (!isset($pdo)) {
    return;
}

try {
    // Tenta selecionar 1 registro apenas para testar se a tabela existe
    // Usamos query silenciada ou try-catch
    $pdo->query("SELECT 1 FROM song_suggestions LIMIT 1");
} catch (Exception $e) {
    // Se der erro (provavelmente tabela não existe), cria
    try {
        $sql = "
        CREATE TABLE IF NOT EXISTS song_suggestions (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            title VARCHAR(255) NOT NULL,
            artist VARCHAR(255) NOT NULL,
            tone VARCHAR(10),
            youtube_link TEXT,
            spotify_link TEXT,
            reason TEXT,
            status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
            reviewed_by INT DEFAULT NULL,
            reviewed_at TIMESTAMP NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
            FOREIGN KEY (reviewed_by) REFERENCES users(id) ON DELETE SET NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ";
        $pdo->exec($sql);
    } catch (Exception $ex) {
        // Silenciar erro de criação se falhar (ex: permissão), para não quebrar a página
        error_log("Erro ao criar tabela song_suggestions: " . $ex->getMessage());
    }
}
?>
