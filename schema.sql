-- Criação do Banco de Dados
CREATE DATABASE IF NOT EXISTS pibo_louvor;
USE pibo_louvor;

-- ==========================================
-- 1. TABELA DE USUÁRIOS (Membros)
-- ==========================================
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    role ENUM('admin', 'user') DEFAULT 'user',
    instrument VARCHAR(100), -- Ex: Voz, Violão, Bateria
    phone VARCHAR(20),
    password VARCHAR(255) NOT NULL, -- Senha (4 últimos dígitos)
    avatar_color VARCHAR(7) DEFAULT '#D4AF37', -- Cor para o avatar (iniciais)
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- ==========================================
-- 2. TABELA DE MÚSICAS (Repertório Geral)
-- ==========================================
CREATE TABLE IF NOT EXISTS songs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(150) NOT NULL,
    artist VARCHAR(100),
    bpm INT,
    duration VARCHAR(10),
    tone VARCHAR(10), -- Tom da música
    link_letra VARCHAR(255),
    link_cifra VARCHAR(255),
    link_audio VARCHAR(255),
    link_video VARCHAR(255),
    category VARCHAR(50), -- Mantida por compatibilidade (mas usamos tags agora)
    tags TEXT, -- Tags antigas como string (ex: "Repertório 2025")
    notes TEXT,
    custom_fields JSON,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- ==========================================
-- 2.1 TABELA DE TAGS (Categorias Coloridas)
-- ==========================================
CREATE TABLE IF NOT EXISTS tags (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50) NOT NULL,
    color VARCHAR(7) DEFAULT '#047857',
    description TEXT
);

-- ==========================================
-- 2.2 RELACIONAMENTO MÚSICA-TAGS
-- ==========================================
CREATE TABLE IF NOT EXISTS song_tags (
    song_id INT NOT NULL,
    tag_id INT NOT NULL,
    PRIMARY KEY (song_id, tag_id),
    FOREIGN KEY (song_id) REFERENCES songs(id) ON DELETE CASCADE,
    FOREIGN KEY (tag_id) REFERENCES tags(id) ON DELETE CASCADE
);

-- ==========================================
-- 3. TABELA DE ESCALAS (Datas dos Cultos)
-- ==========================================
CREATE TABLE IF NOT EXISTS schedules (
    id INT AUTO_INCREMENT PRIMARY KEY,
    event_date DATE NOT NULL,
    event_type VARCHAR(50) DEFAULT 'Culto de Domingo',
    notes TEXT, -- Observações do líder
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- ==========================================
-- 4. RELACIONAMENTO: QUEM TOCA NA ESCALA
-- ==========================================
CREATE TABLE IF NOT EXISTS schedule_users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    schedule_id INT NOT NULL,
    user_id INT NOT NULL,
    status ENUM('pending', 'confirmed', 'declined') DEFAULT 'pending',
    FOREIGN KEY (schedule_id) REFERENCES schedules(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- ==========================================
-- 5. RELACIONAMENTO: MÚSICAS DO DIA
-- ==========================================
CREATE TABLE IF NOT EXISTS schedule_songs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    schedule_id INT NOT NULL,
    song_id INT NOT NULL,
    presentation_order INT, -- Ordem no culto (1, 2, 3...)
    FOREIGN KEY (schedule_id) REFERENCES schedules(id) ON DELETE CASCADE,
    FOREIGN KEY (song_id) REFERENCES songs(id) ON DELETE CASCADE
);

-- ==========================================
-- 🚀 POPULANDO OS DADOS (SEED)
-- ==========================================

-- Inserindo Usuário Admin Padrão
INSERT INTO users (name, role, instrument, phone, password) VALUES
('Admin', 'admin', 'Sistema', '', 'applouvor');
