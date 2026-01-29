-- Schema para Sistema de Notificações
-- Criado em: 2026-01-28

-- Tabela principal de notificações
CREATE TABLE IF NOT EXISTS notifications (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    type VARCHAR(50) NOT NULL,
    title VARCHAR(255) NOT NULL,
    message TEXT,
    data JSON,
    link VARCHAR(255),
    is_read BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    read_at TIMESTAMP NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_read (user_id, is_read),
    INDEX idx_created (created_at DESC),
    INDEX idx_type (type)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabela de preferências de notificação por usuário
CREATE TABLE IF NOT EXISTS notification_preferences (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    type VARCHAR(50) NOT NULL,
    enabled BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_user_type (user_id, type)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Inserir preferências padrão para tipos de notificação
INSERT INTO notification_preferences (user_id, type, enabled)
SELECT id, 'weekly_report', TRUE FROM users
WHERE NOT EXISTS (
    SELECT 1 FROM notification_preferences 
    WHERE user_id = users.id AND type = 'weekly_report'
);

INSERT INTO notification_preferences (user_id, type, enabled)
SELECT id, 'new_escala', TRUE FROM users
WHERE NOT EXISTS (
    SELECT 1 FROM notification_preferences 
    WHERE user_id = users.id AND type = 'new_escala'
);

INSERT INTO notification_preferences (user_id, type, enabled)
SELECT id, 'new_music', TRUE FROM users
WHERE NOT EXISTS (
    SELECT 1 FROM notification_preferences 
    WHERE user_id = users.id AND type = 'new_music'
);

INSERT INTO notification_preferences (user_id, type, enabled)
SELECT id, 'new_aviso', TRUE FROM users
WHERE NOT EXISTS (
    SELECT 1 FROM notification_preferences 
    WHERE user_id = users.id AND type = 'new_aviso'
);

INSERT INTO notification_preferences (user_id, type, enabled)
SELECT id, 'aviso_urgent', TRUE FROM users
WHERE NOT EXISTS (
    SELECT 1 FROM notification_preferences 
    WHERE user_id = users.id AND type = 'aviso_urgent'
);
