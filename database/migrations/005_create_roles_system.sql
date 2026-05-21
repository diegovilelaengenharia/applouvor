-- Migration: Create roles system
-- Permite que membros tenham múltiplas funções (instrumentos/vozes)

-- Tabela de funções disponíveis
CREATE TABLE IF NOT EXISTS roles (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    icon VARCHAR(10) NOT NULL,
    category ENUM('voz', 'cordas', 'teclas', 'percussao', 'sopro', 'outros') NOT NULL,
    color VARCHAR(7) DEFAULT '#047857',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_category (category)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabela de relação usuário-funções (N:N)
CREATE TABLE IF NOT EXISTS user_roles (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    role_id INT NOT NULL,
    is_primary BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (role_id) REFERENCES roles(id) ON DELETE CASCADE,
    UNIQUE KEY unique_user_role (user_id, role_id),
    INDEX idx_user_id (user_id),
    INDEX idx_role_id (role_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Popular tabela de funções
INSERT INTO roles (name, icon, category, color) VALUES
-- Vozes
('Voz Principal', '🎤', 'voz', '#8b5cf6'),
('Backing Vocal', '🎙️', 'voz', '#a78bfa'),
('Coral', '👥', 'voz', '#c4b5fd'),

-- Cordas
('Guitarra', '🎸', 'cordas', '#ef4444'),
('Violão', '🎻', 'cordas', '#f97316'),
('Baixo', '🎸', 'cordas', '#dc2626'),
('Violino', '🎻', 'cordas', '#fb923c'),

-- Teclas
('Teclado', '🎹', 'teclas', '#3b82f6'),
('Piano', '🎹', 'teclas', '#2563eb'),
('Sintetizador', '🎛️', 'teclas', '#60a5fa'),

-- Percussão
('Bateria', '🥁', 'percussao', '#10b981'),
('Percussão', '🪘', 'percussao', '#34d399'),
('Cajón', '📯', 'percussao', '#6ee7b7'),

-- Sopro
('Trompete', '🎺', 'sopro', '#f59e0b'),
('Saxofone', '🎷', 'sopro', '#fbbf24'),
('Trombone', '🎺', 'sopro', '#fcd34d'),

-- Outros
('Operador de Som', '🎧', 'outros', '#6b7280'),
('Mídia', '🎬', 'outros', '#9ca3af');
