-- ==========================================
-- SISTEMA DE AGENDA E EVENTOS
-- ==========================================
-- Criado em: 2026-01-29
-- Descrição: Estrutura para gerenciar eventos e compromissos do ministério
-- ==========================================

-- Tabela principal de eventos
CREATE TABLE IF NOT EXISTS events (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(200) NOT NULL,
    description TEXT,
    start_datetime DATETIME NOT NULL,
    end_datetime DATETIME,
    location VARCHAR(200),
    event_type ENUM('reuniao', 'ensaio_extra', 'confraternizacao', 'aniversario', 'treinamento', 'outro') DEFAULT 'outro',
    color VARCHAR(7) DEFAULT '#047857',
    all_day BOOLEAN DEFAULT FALSE,
    
    -- Recorrência (futura implementação)
    is_recurring BOOLEAN DEFAULT FALSE,
    recurrence_rule TEXT, -- Armazena regra RRULE (RFC 5545)
    
    -- Google Calendar Integration
    google_event_id VARCHAR(255),
    google_calendar_id VARCHAR(255),
    last_synced_at DATETIME,
    
    -- Metadados
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_start_datetime (start_datetime),
    INDEX idx_event_type (event_type),
    INDEX idx_google_event (google_event_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Participantes dos eventos
CREATE TABLE IF NOT EXISTS event_participants (
    id INT AUTO_INCREMENT PRIMARY KEY,
    event_id INT NOT NULL,
    user_id INT NOT NULL,
    status ENUM('pending', 'confirmed', 'declined') DEFAULT 'pending',
    notified_at DATETIME,
    responded_at DATETIME,
    
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (event_id) REFERENCES events(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_participant (event_id, user_id),
    INDEX idx_user_status (user_id, status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Tokens OAuth2 para Google Calendar
CREATE TABLE IF NOT EXISTS google_calendar_tokens (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    access_token TEXT NOT NULL,
    refresh_token TEXT,
    token_type VARCHAR(50) DEFAULT 'Bearer',
    expires_at DATETIME,
    scope TEXT,
    
    -- Configurações
    selected_calendar_id VARCHAR(255),
    auto_sync_enabled BOOLEAN DEFAULT TRUE,
    sync_direction ENUM('app_to_google', 'google_to_app', 'bidirectional') DEFAULT 'bidirectional',
    
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_user_token (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Log de sincronização
CREATE TABLE IF NOT EXISTS google_calendar_sync_log (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    event_id INT,
    action ENUM('created', 'updated', 'deleted', 'synced') NOT NULL,
    direction ENUM('app_to_google', 'google_to_app') NOT NULL,
    success BOOLEAN DEFAULT TRUE,
    error_message TEXT,
    synced_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (event_id) REFERENCES events(id) ON DELETE SET NULL,
    INDEX idx_synced_at (synced_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ==========================================
-- DADOS INICIAIS (SEED)
-- ==========================================

-- Dados de exemplo removidos para evitar erro de chave estrangeira
-- INSERT INTO events (title, description, start_datetime, end_datetime, location, event_type, color, created_by) VALUES
-- ('Reunião de Planejamento 2026', 'Planejamento estratégico do ministério para o primeiro semestre', '2026-02-15 14:00:00', '2026-02-15 16:00:00', 'Sala de Reuniões', 'reuniao', '#3b82f6', NULL),
-- ('Confraternização do Louvor', 'Momento de comunhão e integração da equipe', '2026-02-22 19:00:00', '2026-02-22 22:00:00', 'Salão de Eventos', 'confraternizacao', '#f59e0b', NULL);
