-- Script para criar tabelas no Hostinger (sem CREATE DATABASE)

-- ==========================================
-- 1. TABELA DE USUÁRIOS (Membros)
-- ==========================================
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    role ENUM('admin', 'user') DEFAULT 'user',
    instrument VARCHAR(100), -- Ex: Voz, Violão, Bateria
    phone VARCHAR(20),
    password VARCHAR(255) NOT NULL, -- Senha
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
    tone VARCHAR(10), -- Tom da música
    link VARCHAR(255), -- Link do YouTube/CifraClub
    category VARCHAR(50), -- Ex: Adoração, Celebração, Hino
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
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
    order_index INT, -- Ordem no culto (1, 2, 3...)
    FOREIGN KEY (schedule_id) REFERENCES schedules(id) ON DELETE CASCADE,
    FOREIGN KEY (song_id) REFERENCES songs(id) ON DELETE CASCADE
);

-- ==========================================
-- DADOS INICIAIS
-- ==========================================

-- Inserindo Usuário Admin Padrão
INSERT INTO users (name, role, instrument, phone, password) VALUES
('Admin', 'admin', 'Sistema', '', 'applouvor');
