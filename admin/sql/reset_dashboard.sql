-- Script para resetar configurações do dashboard
-- Execute este script no banco de dados para forçar atualização

-- Opção 1: Limpar todas as configurações (força fallback com novos cards)
TRUNCATE TABLE user_dashboard_settings;

-- Opção 2: Atualizar cards existentes para novos IDs (se necessário)
-- DELETE FROM user_dashboard_settings WHERE card_id NOT IN ('escalas', 'repertorio', 'historico', 'membros', 'ausencias', 'agenda', 'leitura', 'devocional', 'oracao', 'avisos', 'aniversarios');

-- Opção 3: Inserir configuração padrão para seu usuário (ID 1)
DELETE FROM user_dashboard_settings WHERE user_id = 1;

INSERT INTO user_dashboard_settings (user_id, card_id, is_visible, display_order) VALUES
-- Gestão (azul)
(1, 'escalas', 1, 1),
(1, 'repertorio', 1, 2),
(1, 'historico', 1, 3),
(1, 'membros', 1, 4),
(1, 'ausencias', 1, 5),
(1, 'agenda', 1, 6),
-- Espiritualidade (verde)
(1, 'leitura', 1, 7),
(1, 'devocional', 1, 8),
(1, 'oracao', 1, 9),
-- Comunicação (amarelo)
(1, 'avisos', 1, 10),
(1, 'aniversarios', 1, 11);
