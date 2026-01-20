-- ==========================================
-- DASHBOARD INFORMATIVO - NOVAS TABELAS
-- ==========================================

-- Tabela de Avisos
CREATE TABLE IF NOT EXISTS avisos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    message TEXT NOT NULL,
    priority ENUM('urgent', 'important', 'info') DEFAULT 'info',
    created_by INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_created_at (created_at),
    INDEX idx_priority (priority)
);

-- Tabela de Widgets Personalizáveis
CREATE TABLE IF NOT EXISTS user_widgets (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    widget_name VARCHAR(50) NOT NULL,
    position INT DEFAULT 0,
    enabled BOOLEAN DEFAULT TRUE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_user_widget (user_id, widget_name),
    INDEX idx_user_enabled (user_id, enabled)
);

-- Adicionar coluna event_time em schedules (se não existir)
ALTER TABLE schedules 
ADD COLUMN IF NOT EXISTS event_time TIME DEFAULT '19:00:00' AFTER event_date;

-- Adicionar coluna position em schedule_songs (se não existir)  
ALTER TABLE schedule_songs 
ADD COLUMN IF NOT EXISTS position INT DEFAULT 0 AFTER song_id;

-- Seed: Avisos de exemplo
INSERT INTO avisos (title, message, priority, created_by) VALUES
('Ensaio Extra', 'Ensaio extra na quarta-feira às 19h30. Presença obrigatória!', 'urgent', 1),
('Novo Repertório', 'Adicionadas 5 músicas novas ao repertório. Confiram!', 'important', 1),
('Aniversário', 'Parabéns ao irmão João que faz aniversário hoje!', 'info', 1);
