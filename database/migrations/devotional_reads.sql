-- ==========================================
-- MIGRATION: Sistema de Rastreamento de Leitura
-- ==========================================

-- Tabela para rastrear quais devocionais foram lidos por cada usuário
CREATE TABLE IF NOT EXISTS devotional_reads (
    id INT AUTO_INCREMENT PRIMARY KEY,
    devotional_id INT NOT NULL,
    user_id INT NOT NULL,
    read_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (devotional_id) REFERENCES devotionals(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_user_devotional (user_id, devotional_id),
    INDEX idx_user (user_id),
    INDEX idx_devotional (devotional_id),
    INDEX idx_read_at (read_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
