-- Criação do Banco de Dados
CREATE DATABASE IF NOT EXISTS u884436813_applouvor;
USE u884436813_applouvor;

-- Tabela de Usuários
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    password VARCHAR(255) NOT NULL,
    role ENUM('admin', 'user') DEFAULT 'user',
    category ENUM('voz_feminina', 'voz_masculina', 'violao', 'teclado', 'bateria', 'baixo', 'guitarra', 'outros') NOT NULL,
    phone VARCHAR(20),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Tabela de Escalas
CREATE TABLE IF NOT EXISTS scales (
    id INT AUTO_INCREMENT PRIMARY KEY,
    event_date DATE NOT NULL,
    event_type VARCHAR(50) DEFAULT 'Culto de Domingo',
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Tabela de Membros da Escala (Quem toca no dia)
CREATE TABLE IF NOT EXISTS scale_members (
    id INT AUTO_INCREMENT PRIMARY KEY,
    scale_id INT NOT NULL,
    user_id INT NOT NULL,
    instrument VARCHAR(50), -- O que vai tocar no dia (ex: Violão pode tocar Baixo)
    confirmed TINYINT(1) DEFAULT 0, -- 0: Pendente, 1: Confirmado, 2: Recusado
    FOREIGN KEY (scale_id) REFERENCES scales(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Tabela de Repertórios
CREATE TABLE IF NOT EXISTS repertories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    scale_id INT, -- Ligado a uma escala específica
    planner_id INT, -- Quem montou (Líder)
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (scale_id) REFERENCES scales(id) ON DELETE SET NULL,
    FOREIGN KEY (planner_id) REFERENCES users(id) ON DELETE SET NULL
);

-- Tabela de Músicas do Repertório
CREATE TABLE IF NOT EXISTS songs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    repertory_id INT,
    title VARCHAR(150) NOT NULL,
    artist VARCHAR(100),
    key_note VARCHAR(10), -- Tom da música
    link_cifra VARCHAR(255),
    link_youtube VARCHAR(255),
    observation TEXT,
    order_index INT DEFAULT 0,
    FOREIGN KEY (repertory_id) REFERENCES repertories(id) ON DELETE CASCADE
);

-- Inserção de Usuários Iniciais
-- Senhas são os 4 últimos dígitos do telefone
-- Admin
INSERT INTO users (name, password, role, category, phone) VALUES 
('Diego', '9577', 'admin', 'violao', '35 98452 9577'); -- Também toca Bateria

-- Vozes Femininas
INSERT INTO users (name, password, role, category, phone) VALUES 
('Aline', '4903', 'user', 'voz_feminina', '37 98838-4903'),
('Michelle', '1990', 'user', 'voz_feminina', '37 99145-1990'),
('Raquel', '6691', 'user', 'voz_feminina', '35 99237-6691'),
('Samara', '0252', 'user', 'voz_feminina', '37 9922-0252');

-- Vozes Masculinas
INSERT INTO users (name, password, role, category, phone) VALUES 
('Wemerson', '5686', 'user', 'voz_masculina', '37 9988-5686'),
('Weberth', '2158', 'user', 'voz_masculina', '37 9105-2158'),
('Ananias', '1176', 'user', 'voz_masculina', '37 9959-1176'),
('Márcio', '6713', 'user', 'voz_masculina', '31 9328-6713'); -- Também toca Violão

-- Instrumentistas (que não estão acima)
INSERT INTO users (name, password, role, category, phone) VALUES 
('Thalyta', '3545', 'admin', 'violao', '14 98165-3545'),
('Mariana', '5686', 'user', 'teclado', '37 9988-5686');
