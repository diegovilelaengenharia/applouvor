-- ============================================================================
-- RESET DASHBOARD CACHE - Execução Direta no phpMyAdmin
-- ============================================================================
-- Este script limpa o cache antigo e insere a configuração correta dos cards
-- Execute este script completo no phpMyAdmin do seu banco de dados
-- ============================================================================

-- 1. Limpar configurações antigas do seu usuário
DELETE FROM user_dashboard_settings WHERE user_id = 1;

-- 2. Inserir nova configuração com as 3 categorias corretas
-- GESTÃO (Azul #2563EB) - 6 cards
INSERT INTO user_dashboard_settings (user_id, card_id, is_visible, display_order) VALUES
(1, 'escalas', 1, 1),
(1, 'repertorio', 1, 2),
(1, 'historico', 1, 3),
(1, 'membros', 1, 4),
(1, 'ausencias', 1, 5),
(1, 'agenda', 1, 6);

-- ESPIRITUALIDADE (Verde #10B981) - 3 cards
INSERT INTO user_dashboard_settings (user_id, card_id, is_visible, display_order) VALUES
(1, 'leitura', 1, 7),
(1, 'devocional', 1, 8),
(1, 'oracao', 1, 9);

-- COMUNICAÇÃO (Amarelo #F59E0B) - 2 cards
INSERT INTO user_dashboard_settings (user_id, card_id, is_visible, display_order) VALUES
(1, 'avisos', 1, 10),
(1, 'aniversarios', 1, 11);

-- 3. Verificar resultado
SELECT 
    card_id,
    is_visible,
    display_order
FROM user_dashboard_settings 
WHERE user_id = 1 
ORDER BY display_order;

-- ============================================================================
-- RESULTADO ESPERADO: 11 cards na ordem correta
-- ============================================================================
