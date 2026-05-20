-- Fase 4: Integridade do Banco de Dados
-- Garante que relacionamentos tenham chaves estrangeiras com CASCADE e cria índices essenciais

-- 1. Garante que os FKs da tabela `schedule_users` tenham CASCADE (caso o schema original não tenha criado corretamente)
ALTER TABLE `schedule_users` DROP FOREIGN KEY IF EXISTS `schedule_users_ibfk_1`;
ALTER TABLE `schedule_users` DROP FOREIGN KEY IF EXISTS `schedule_users_ibfk_2`;

ALTER TABLE `schedule_users`
  ADD CONSTRAINT `schedule_users_ibfk_1` FOREIGN KEY (`schedule_id`) REFERENCES `schedules` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `schedule_users_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

-- 2. Garante que os FKs da tabela `schedule_songs` tenham CASCADE
ALTER TABLE `schedule_songs` DROP FOREIGN KEY IF EXISTS `schedule_songs_ibfk_1`;
ALTER TABLE `schedule_songs` DROP FOREIGN KEY IF EXISTS `schedule_songs_ibfk_2`;

ALTER TABLE `schedule_songs`
  ADD CONSTRAINT `schedule_songs_ibfk_1` FOREIGN KEY (`schedule_id`) REFERENCES `schedules` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `schedule_songs_ibfk_2` FOREIGN KEY (`song_id`) REFERENCES `songs` (`id`) ON DELETE CASCADE;

-- 3. Tabela de Comentários em Escalas (caso não exista via schema manual anterior)
CREATE TABLE IF NOT EXISTS `schedule_comments` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `schedule_id` INT NOT NULL,
    `user_id` INT NOT NULL,
    `comment` TEXT NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`schedule_id`) REFERENCES `schedules`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
);

-- 4. Criação de Índices para alta performance em Repertório e Escalas

-- Índices para buscas na tabela `songs` (Artistas, Tons e ordenação por Título)
CREATE INDEX IF NOT EXISTS `idx_songs_artist` ON `songs` (`artist`);
CREATE INDEX IF NOT EXISTS `idx_songs_tone` ON `songs` (`tone`);
CREATE INDEX IF NOT EXISTS `idx_songs_title` ON `songs` (`title`);

-- Índice para a tabela `schedules` (Busca por data de evento, que é a mais executada na página de Escalas)
CREATE INDEX IF NOT EXISTS `idx_schedules_event_date` ON `schedules` (`event_date`);

-- Índices na tabela associativa `song_tags` (Para carregar filtros de TAGs mais rápido)
CREATE INDEX IF NOT EXISTS `idx_song_tags_song` ON `song_tags` (`song_id`);
CREATE INDEX IF NOT EXISTS `idx_song_tags_tag` ON `song_tags` (`tag_id`);

-- Índice para a tabela de comentários e roteiros otimizarem o carregamento da escala
CREATE INDEX IF NOT EXISTS `idx_schedule_comments_schedule` ON `schedule_comments` (`schedule_id`);
CREATE INDEX IF NOT EXISTS `idx_schedule_roteiro_schedule` ON `schedule_roteiro` (`schedule_id`);
