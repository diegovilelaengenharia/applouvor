ALTER TABLE songs 
ADD COLUMN IF NOT EXISTS link_spotify VARCHAR(255) AFTER link_video,
ADD COLUMN IF NOT EXISTS link_youtube VARCHAR(255) AFTER link_spotify,
ADD COLUMN IF NOT EXISTS link_apple_music VARCHAR(255) AFTER link_youtube,
ADD COLUMN IF NOT EXISTS link_deezer VARCHAR(255) AFTER link_apple_music;
