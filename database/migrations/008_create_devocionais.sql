-- Migration: Criação das tabelas para sistema de devocionais
-- Data: 2026-01-24

-- Tabela principal de devocionais
CREATE TABLE IF NOT EXISTS devotionals (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    title VARCHAR(200) NOT NULL,
    content TEXT,                    -- Texto rico (HTML)
    media_type ENUM('text', 'video', 'audio', 'link') DEFAULT 'text',
    media_url VARCHAR(500),          -- URL do vídeo/áudio/link
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Relacionamento Devocional-Tags (reutiliza tabela tags existente)
CREATE TABLE IF NOT EXISTS devotional_tags (
    devotional_id INT NOT NULL,
    tag_id INT NOT NULL,
    PRIMARY KEY (devotional_id, tag_id),
    FOREIGN KEY (devotional_id) REFERENCES devotionals(id) ON DELETE CASCADE,
    FOREIGN KEY (tag_id) REFERENCES tags(id) ON DELETE CASCADE
);

-- Comentários nos devocionais
CREATE TABLE IF NOT EXISTS devotional_comments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    devotional_id INT NOT NULL,
    user_id INT NOT NULL,
    comment TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (devotional_id) REFERENCES devotionals(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Índices para performance
CREATE INDEX idx_devotionals_user ON devotionals(user_id);
CREATE INDEX idx_devotionals_created ON devotionals(created_at DESC);
CREATE INDEX idx_comments_devotional ON devotional_comments(devotional_id);
