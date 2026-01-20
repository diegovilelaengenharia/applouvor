ALTER TABLE avisos ADD COLUMN type ENUM('general', 'event', 'schedule', 'music', 'spiritual', 'urgent') DEFAULT 'general' AFTER message;
ALTER TABLE avisos ADD COLUMN archived_at TIMESTAMP NULL DEFAULT NULL AFTER created_at;
