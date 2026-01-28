-- Tabela para armazenar configurações de dashboard por usuário
CREATE TABLE IF NOT EXISTS user_dashboard_settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    card_id VARCHAR(50) NOT NULL,
    is_visible BOOLEAN DEFAULT TRUE,
    display_order INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_user_card (user_id, card_id),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_visible (user_id, is_visible),
    INDEX idx_user_order (user_id, display_order)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Inserir configuração padrão para todos os usuários existentes
-- Apenas os 7 cards principais ficam visíveis por padrão
INSERT INTO user_dashboard_settings (user_id, card_id, is_visible, display_order)
SELECT 
    u.id,
    cards.card_id,
    cards.is_visible,
    cards.display_order
FROM users u
CROSS JOIN (
    -- GESTÃO (Verde)
    SELECT 'escalas' as card_id, TRUE as is_visible, 1 as display_order
    UNION ALL SELECT 'repertorio', TRUE, 2
    UNION ALL SELECT 'membros', FALSE, 8
    UNION ALL SELECT 'stats_escalas', FALSE, 9
    UNION ALL SELECT 'stats_repertorio', FALSE, 10
    UNION ALL SELECT 'relatorios', FALSE, 11
    UNION ALL SELECT 'agenda', FALSE, 12
    UNION ALL SELECT 'indisponibilidades', FALSE, 13
    -- ESPÍRITO (Índigo)
    UNION ALL SELECT 'leitura', TRUE, 3
    UNION ALL SELECT 'devocional', TRUE, 6
    UNION ALL SELECT 'oracao', TRUE, 7
    UNION ALL SELECT 'config_leitura', FALSE, 14
    -- COMUNICA (Laranja)
    UNION ALL SELECT 'avisos', TRUE, 4
    UNION ALL SELECT 'aniversariantes', TRUE, 5
    UNION ALL SELECT 'chat', FALSE, 15
    -- ADMIN (Vermelho) - apenas para admins
    UNION ALL SELECT 'lider', FALSE, 16
    UNION ALL SELECT 'perfil', FALSE, 17
    UNION ALL SELECT 'configuracoes', FALSE, 18
    UNION ALL SELECT 'monitoramento', FALSE, 19
    UNION ALL SELECT 'pastas', FALSE, 20
    -- EXTRAS (Cinza)
    UNION ALL SELECT 'playlists', FALSE, 21
    UNION ALL SELECT 'artistas', FALSE, 22
    UNION ALL SELECT 'classificacoes', FALSE, 23
) as cards
ON DUPLICATE KEY UPDATE updated_at = CURRENT_TIMESTAMP;
