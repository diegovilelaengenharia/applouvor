-- Script para adicionar suporte a campos customizados nas músicas
-- Executar no banco de dados do Hostinger

-- Adicionar coluna para armazenar campos customizados em formato JSON
ALTER TABLE songs 
ADD COLUMN IF NOT EXISTS custom_fields TEXT AFTER notes;

-- Exemplo de estrutura JSON que será armazenada:
-- [
--   {"name": "Google Drive", "link": "https://drive.google.com/..."},
--   {"name": "Partitura", "link": "https://..."},
--   {"name": "Playback", "link": "https://..."}
-- ]
