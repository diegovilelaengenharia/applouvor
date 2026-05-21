-- Script SQL para limpar cards removidos do dashboard dos usuários
-- Execute este script no banco de dados para remover cards antigos

-- Lista de cards removidos que devem ser deletados das configurações dos usuários
DELETE FROM user_dashboard_settings 
WHERE card_id IN (
    'stats_escalas',
    'stats_repertorio',
    'relatorios',
    'config_leitura',
    'chat',
    'configuracoes',
    'monitoramento',
    'pastas',
    'playlists',
    'artistas',
    'classificacoes',
    'lider',
    'perfil',
    'indisponibilidades',  -- Renomeado para 'ausencias'
    'aniversariantes'      -- Renomeado para 'aniversarios'
);

-- Atualizar cards renomeados
UPDATE user_dashboard_settings 
SET card_id = 'ausencias' 
WHERE card_id = 'indisponibilidades';

UPDATE user_dashboard_settings 
SET card_id = 'aniversarios' 
WHERE card_id = 'aniversariantes';

-- Verificar cards restantes (apenas para conferência)
SELECT DISTINCT card_id FROM user_dashboard_settings ORDER BY card_id;

-- Cards válidos esperados:
-- - escalas
-- - repertorio
-- - membros
-- - agenda
-- - ausencias
-- - leitura
-- - devocional
-- - oracao
-- - avisos
-- - aniversarios
