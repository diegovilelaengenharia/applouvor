-- ============================================================
-- App Louvor PIB Oliveira — Schema Producao v6.0
-- Para uso no Hostinger: nao contem CREATE DATABASE nem USE.
-- Rodar no banco: u884436813_applouvor
-- ============================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ============================================================
-- 1. USUARIOS
-- ============================================================
CREATE TABLE IF NOT EXISTS `users` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(100) NOT NULL,
    `role` ENUM('admin','user') DEFAULT 'user',
    `instrument` VARCHAR(100),
    `phone` VARCHAR(20),
    `email` VARCHAR(150) NULL,
    `password` VARCHAR(255) NOT NULL,
    `avatar_color` VARCHAR(7) DEFAULT '#2E7EED',
    `avatar` VARCHAR(255) NULL,
    `bio` TEXT NULL,
    `birth_date` DATE NULL,
    `last_login` DATETIME NULL,
    `login_count` INT DEFAULT 0,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 2. CONFIGURACOES DO USUARIO (chave-valor)
-- ============================================================
CREATE TABLE IF NOT EXISTS `user_settings` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NOT NULL,
    `setting_key` VARCHAR(100) NOT NULL,
    `setting_value` TEXT,
    UNIQUE KEY `unique_user_setting` (`user_id`, `setting_key`),
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 3. MUSICAS (Repertorio Geral)
-- ============================================================
CREATE TABLE IF NOT EXISTS `songs` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `title` VARCHAR(150) NOT NULL,
    `artist` VARCHAR(100),
    `bpm` INT,
    `duration` VARCHAR(10),
    `tone` VARCHAR(10),
    `link_letra` VARCHAR(500),
    `link_cifra` VARCHAR(500),
    `link_audio` VARCHAR(500),
    `link_video` VARCHAR(500),
    `notes` TEXT,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `tags` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(50) NOT NULL,
    `color` VARCHAR(7) DEFAULT '#047857',
    `description` TEXT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `song_tags` (
    `song_id` INT NOT NULL,
    `tag_id` INT NOT NULL,
    PRIMARY KEY (`song_id`, `tag_id`),
    FOREIGN KEY (`song_id`) REFERENCES `songs`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`tag_id`) REFERENCES `tags`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 4. ESCALAS
-- ============================================================
CREATE TABLE IF NOT EXISTS `schedules` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `event_date` DATE NOT NULL,
    `event_time` TIME DEFAULT '09:00:00',
    `event_type` VARCHAR(50) DEFAULT 'Culto de Domingo',
    `notes` TEXT,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `schedule_users` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `schedule_id` INT NOT NULL,
    `user_id` INT NOT NULL,
    `status` ENUM('pending','confirmed','declined','absent','absent_justified') DEFAULT 'pending',
    `assigned_instrument` VARCHAR(100) NULL,
    `absence_note` TEXT NULL,
    `is_rehearsed` TINYINT(1) DEFAULT 0,
    FOREIGN KEY (`schedule_id`) REFERENCES `schedules`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `schedule_songs` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `schedule_id` INT NOT NULL,
    `song_id` INT NOT NULL,
    `presentation_order` INT DEFAULT 0,
    FOREIGN KEY (`schedule_id`) REFERENCES `schedules`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`song_id`) REFERENCES `songs`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `schedule_roteiro` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `schedule_id` INT NOT NULL,
    `item_order` INT DEFAULT 0,
    `item_type` ENUM('musica','oracao','palavra','anuncio','intervalo','livre') DEFAULT 'livre',
    `title` VARCHAR(200) NULL,
    `song_id` INT NULL,
    `custom_tone` VARCHAR(10) NULL,
    `nota_interna` TEXT NULL,
    FOREIGN KEY (`schedule_id`) REFERENCES `schedules`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`song_id`) REFERENCES `songs`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `schedule_comments` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `schedule_id` INT NOT NULL,
    `user_id` INT NOT NULL,
    `comment` TEXT NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`schedule_id`) REFERENCES `schedules`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 5. INDISPONIBILIDADE
-- ============================================================
CREATE TABLE IF NOT EXISTS `user_unavailability` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NOT NULL,
    `start_date` DATE NOT NULL,
    `end_date` DATE NOT NULL,
    `reason` TEXT NULL,
    `replacement_id` INT NULL,
    `audio_path` VARCHAR(255) NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`replacement_id`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 6. AVISOS
-- ============================================================
CREATE TABLE IF NOT EXISTS `avisos` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `titulo` VARCHAR(200) NOT NULL,
    `conteudo` LONGTEXT,
    `tipo` VARCHAR(50) DEFAULT 'geral',
    `prioridade` ENUM('baixa','media','alta','urgente') DEFAULT 'media',
    `fixado` TINYINT(1) DEFAULT 0,
    `data_expiracao` DATE NULL,
    `user_id` INT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 7. NOTIFICACOES PUSH
-- ============================================================
CREATE TABLE IF NOT EXISTS `push_subscriptions` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NOT NULL,
    `endpoint` TEXT NOT NULL,
    `p256dh` TEXT NOT NULL,
    `auth` VARCHAR(255) NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `notifications` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NOT NULL,
    `type` VARCHAR(50) NOT NULL,
    `title` VARCHAR(200) NOT NULL,
    `body` TEXT,
    `data` JSON NULL,
    `is_read` TINYINT(1) DEFAULT 0,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 8. DEVOCIONAIS
-- ============================================================
CREATE TABLE IF NOT EXISTS `devotionals` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NOT NULL,
    `title` VARCHAR(200) NOT NULL,
    `content` LONGTEXT,
    `media_type` ENUM('text','video','link') DEFAULT 'text',
    `media_url` VARCHAR(500) NULL,
    `series_id` INT NULL,
    `verse_references` JSON NULL,
    `order_in_series` INT DEFAULT 0,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `devotional_comments` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `devotional_id` INT NOT NULL,
    `user_id` INT NOT NULL,
    `comment` TEXT NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`devotional_id`) REFERENCES `devotionals`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `devotional_tags` (
    `devotional_id` INT NOT NULL,
    `tag_id` INT NOT NULL,
    PRIMARY KEY (`devotional_id`, `tag_id`),
    FOREIGN KEY (`devotional_id`) REFERENCES `devotionals`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`tag_id`) REFERENCES `tags`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `devotional_reads` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `devotional_id` INT NOT NULL,
    `user_id` INT NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY `unique_read` (`devotional_id`, `user_id`),
    FOREIGN KEY (`devotional_id`) REFERENCES `devotionals`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 9. PEDIDOS DE ORACAO
-- ============================================================
CREATE TABLE IF NOT EXISTS `prayer_requests` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NOT NULL,
    `title` VARCHAR(200) NOT NULL,
    `description` TEXT,
    `category` VARCHAR(50) DEFAULT 'other',
    `is_urgent` TINYINT(1) DEFAULT 0,
    `is_anonymous` TINYINT(1) DEFAULT 0,
    `is_answered` TINYINT(1) DEFAULT 0,
    `prayer_count` INT DEFAULT 0,
    `answered_at` DATETIME NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `prayer_interactions` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `prayer_id` INT NOT NULL,
    `user_id` INT NOT NULL,
    `type` ENUM('pray','comment') NOT NULL,
    `comment` TEXT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`prayer_id`) REFERENCES `prayer_requests`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 10. PLANO DE LEITURA BIBLICA
-- ============================================================
CREATE TABLE IF NOT EXISTS `reading_progress` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NOT NULL,
    `plan_key` VARCHAR(100) NOT NULL,
    `chapter_ref` VARCHAR(50) NOT NULL,
    `verses_read` JSON NULL,
    `completed_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 11. SUGESTOES DE MUSICAS
-- ============================================================
CREATE TABLE IF NOT EXISTS `song_suggestions` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NOT NULL,
    `title` VARCHAR(200) NOT NULL,
    `artist` VARCHAR(100),
    `link` VARCHAR(500) NULL,
    `notes` TEXT NULL,
    `status` ENUM('pending','approved','rejected') DEFAULT 'pending',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 12. TENTATIVAS DE LOGIN (rate limiting)
-- ============================================================
CREATE TABLE IF NOT EXISTS `login_attempts` (
    `ip`       VARCHAR(45)  NOT NULL,
    `attempts` TINYINT      NOT NULL DEFAULT 1,
    `since`    INT UNSIGNED NOT NULL,
    `blocked`  INT UNSIGNED NOT NULL DEFAULT 0,
    PRIMARY KEY (`ip`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;

-- ============================================================
-- SEED — Dados minimos obrigatorios
-- ============================================================

-- Admin padrao: usuario=Diego Vilela, senha=applouvor
INSERT IGNORE INTO `users` (`id`, `name`, `role`, `instrument`, `phone`, `password`) VALUES
(1, 'Diego Vilela', 'admin', 'Lider', '35984529577', '$2y$10$zi1WViTPmfiO.BRFwGxx9eIBc3wC.t.QPghXQqBD1Oyi4Tj6PTLZ2');

-- Tags padrao do repertorio
INSERT IGNORE INTO `tags` (`name`, `color`) VALUES
('Adoracao', '#2E7EED'),
('Louvor', '#FFC107'),
('Santa Ceia', '#D32F2F'),
('Ofertorio', '#388E3C'),
('Oracao', '#0288D1');
