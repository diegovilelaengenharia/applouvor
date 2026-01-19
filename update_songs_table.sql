-- Script para atualizar tabela songs com novos campos
-- Executar no banco de dados do Hostinger

-- Verificar se as colunas já existem antes de adicionar
ALTER TABLE songs 
ADD COLUMN IF NOT EXISTS bpm INT AFTER tone,
ADD COLUMN IF NOT EXISTS duration VARCHAR(10) AFTER bpm,
ADD COLUMN IF NOT EXISTS category VARCHAR(50) DEFAULT 'Louvor' AFTER link,
ADD COLUMN IF NOT EXISTS link_letra VARCHAR(500) AFTER category,
ADD COLUMN IF NOT EXISTS link_cifra VARCHAR(500) AFTER link_letra,
ADD COLUMN IF NOT EXISTS link_audio VARCHAR(500) AFTER link_cifra,
ADD COLUMN IF NOT EXISTS link_video VARCHAR(500) AFTER link_audio,
ADD COLUMN IF NOT EXISTS tags VARCHAR(255) AFTER link_video,
ADD COLUMN IF NOT EXISTS notes TEXT AFTER tags;

-- Renomear coluna 'link' para manter compatibilidade
ALTER TABLE songs CHANGE COLUMN link link_youtube VARCHAR(255);
