-- ================================================
-- MIGRATIONS: Melhorias na Área Devocional
-- Data: 2026-02-02
-- ================================================

-- 1. Tabela para Sistema de Reações
CREATE TABLE IF NOT EXISTS devotional_reactions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    devotional_id INT NOT NULL,
    user_id INT NOT NULL,
    reaction_type ENUM('amen', 'prayer', 'inspired') NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_user_reaction (devotional_id, user_id, reaction_type),
    FOREIGN KEY (devotional_id) REFERENCES devotionals(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_devotional (devotional_id),
    INDEX idx_user (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. Tabela para Séries de Devocionais
CREATE TABLE IF NOT EXISTS devotional_series (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    author_id INT NOT NULL,
    cover_color VARCHAR(7) DEFAULT '#667eea',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (author_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_author (author_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. Adicionar campos na tabela devotionals
ALTER TABLE devotionals 
ADD COLUMN IF NOT EXISTS series_id INT NULL,
ADD COLUMN IF NOT EXISTS verse_references TEXT NULL,
ADD COLUMN IF NOT EXISTS order_in_series INT DEFAULT 0,
ADD FOREIGN KEY (series_id) REFERENCES devotional_series(id) ON DELETE SET NULL;

-- 4. Índices adicionais para performance
ALTER TABLE devotionals ADD INDEX IF NOT EXISTS idx_series (series_id);
ALTER TABLE devotionals ADD INDEX IF NOT EXISTS idx_created_at (created_at);
ALTER TABLE devotionals ADD INDEX IF NOT EXISTS idx_author (user_id);

-- ================================================
-- Views úteis para queries rápidas
-- ================================================

-- View com contagem de reações por devocional
CREATE OR REPLACE VIEW devotional_reaction_counts AS
SELECT 
    devotional_id,
    SUM(CASE WHEN reaction_type = 'amen' THEN 1 ELSE 0 END) as amen_count,
    SUM(CASE WHEN reaction_type = 'prayer' THEN 1 ELSE 0 END) as prayer_count,
    SUM(CASE WHEN reaction_type = 'inspired' THEN 1 ELSE 0 END) as inspired_count,
    COUNT(*) as total_reactions
FROM devotional_reactions
GROUP BY devotional_id;

-- View com informações completas de séries
CREATE OR REPLACE VIEW series_with_stats AS
SELECT 
    s.*,
    u.name as author_name,
    COUNT(d.id) as devotional_count
FROM devotional_series s
LEFT JOIN users u ON s.author_id = u.id
LEFT JOIN devotionals d ON s.id = d.series_id
GROUP BY s.id;

-- ================================================
-- Dados iniciais (exemplos)
-- ================================================

-- Exemplo de série inicial
INSERT INTO devotional_series (title, description, author_id, cover_color) 
VALUES 
('Bem-vindo ao Devocional', 'Série de introdução aos devocionais da comunidade', 1, '#667eea')
ON DUPLICATE KEY UPDATE title=title;

-- ================================================
-- COMPLETED: Fase 1 - Backend Base
-- ================================================
