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
    presentation_order INT, -- Ordem no culto (1, 2, 3...)
    FOREIGN KEY (schedule_id) REFERENCES schedules(id) ON DELETE CASCADE,
    FOREIGN KEY (song_id) REFERENCES songs(id) ON DELETE CASCADE
);

-- ==========================================
-- 🚀 POPULANDO OS DADOS (SEED)
-- ==========================================

-- Inserindo Membros e Senhas (4 últimos dígitos)
INSERT INTO users (name, role, instrument, phone, password) VALUES
-- Admin
('Diego', 'admin', 'Violão/Bateria', '35 98452-9577', '9577'),

-- Vozes Femininas
('Aline', 'user', 'Voz (Fem)', '37 98838-4903', '4903'),
('Michelle', 'user', 'Voz (Fem)', '37 99145-1990', '1990'),
('Raquel', 'user', 'Voz (Fem)', '35 99237-6691', '6691'),
('Samara', 'user', 'Voz (Fem)', '37 9922-0252', '0252'),

-- Vozes Masculinas
('Wemerson', 'user', 'Voz (Masc)', '37 9988-5686', '5686'),
('Weberth', 'user', 'Voz (Masc)', '37 9105-2158', '2158'),
('Ananias', 'user', 'Voz (Masc)', '37 9959-1176', '1176'),
('Márcio', 'user', 'Voz (Masc)/Violão', '31 9328-6713', '6713'),

-- Instrumentistas
('Thalyta', 'user', 'Violão', '14 98165-3545', '3545'),
('Mariana', 'user', 'Teclado', '37 9988-5686', '5686');

-- Inserindo uma música de teste
INSERT INTO songs (title, artist, tone, category) VALUES 
('Bondade de Deus', 'Bethel Music', 'A', 'Adoração');

-- Inserindo uma escala de teste (Domingo que vem)
INSERT INTO schedules (event_date, notes) VALUES 
(DATE_ADD(CURDATE(), INTERVAL (7 - DAYOFWEEK(CURDATE()) + 1) DAY), 'Culto de Ceia - Todos de branco');
