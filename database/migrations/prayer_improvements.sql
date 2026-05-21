-- Migration: Melhoria no sistema de intercessões
-- Garantir que cada usuário possa interceder apenas uma vez por pedido

-- Adicionar índice único para evitar duplicatas de intercessão
ALTER TABLE prayer_interactions 
ADD UNIQUE KEY unique_user_prayer_interaction (prayer_id, user_id, type);
