-- Migration 003: Tabela para o Roteiro de Culto
-- Cada escala pode ter um roteiro ordenado com itens de tipos diferentes.
-- song_id é NULL para itens não-musicais (oração, palavra, etc.)
-- nota_interna é visível apenas para admin — não expor na view do músico.

CREATE TABLE IF NOT EXISTS schedule_roteiro (
    id INT AUTO_INCREMENT PRIMARY KEY,
    schedule_id INT NOT NULL,
    order_position SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    item_type ENUM('musica','oracao','palavra','anuncio','intervalo','livre') NOT NULL DEFAULT 'musica',
    title VARCHAR(255) NULL COMMENT 'Título do item (obrigatório para não-músicas; opcional para músicas — usa title da songs)',
    song_id INT NULL COMMENT 'FK para songs. NULL se item_type != musica',
    custom_tone VARCHAR(10) NULL COMMENT 'Tom customizado para esta escala. NULL = usar songs.tone padrão',
    nota_interna TEXT NULL COMMENT 'Nota visível apenas para admin (líder). Nunca exibir para músicos.',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    KEY idx_schedule_order (schedule_id, order_position),
    CONSTRAINT fk_roteiro_schedule FOREIGN KEY (schedule_id) REFERENCES schedules(id) ON DELETE CASCADE,
    CONSTRAINT fk_roteiro_song FOREIGN KEY (song_id) REFERENCES songs(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
