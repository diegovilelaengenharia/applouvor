-- Migration: 007_migrate_old_instruments.sql
-- Objetivo: Migrar dados da coluna 'instrument' (string) para a tabela 'user_roles' (relacional)

-- 1. Limpar user_roles existentes para evitar duplicatas durante a migração (opcional, mas seguro dado o estado atual)
-- DELETE FROM user_roles; 
-- (Comentado para não apagar o que já foi feito manualmente, vamos usar INSERT IGNORE)

-- 2. Mapeamento de Strings para IDs (baseado na migration 005)
-- IDs esperados (confirme se sua tabela roles tem esses IDs, mas assumindo auto_increment sequencial padrão):
-- 1: Voz Principal, 2: Backing Vocal, 3: Coral
-- 4: Guitarra, 5: Violão, 6: Baixo, 7: Violino
-- 8: Teclado, 9: Piano, 10: Sintetizador
-- 11: Bateria, 12: Percussão, 13: Cajón
-- 14: Trompete, 15: Saxofone, 16: Trombone
-- 17: Operador de Som, 18: Mídia

-- Inserir Vozes
INSERT IGNORE INTO user_roles (user_id, role_id)
SELECT id, 1 FROM users WHERE instrument LIKE '%Voz%' OR instrument LIKE '%Cantor%' OR instrument LIKE '%Ministro%';

-- Inserir Violão
INSERT IGNORE INTO user_roles (user_id, role_id)
SELECT id, 5 FROM users WHERE instrument LIKE '%Violão%' OR instrument LIKE '%Violao%';

-- Inserir Guitarra
INSERT IGNORE INTO user_roles (user_id, role_id)
SELECT id, 4 FROM users WHERE instrument LIKE '%Guitarra%';

-- Inserir Baixo
INSERT IGNORE INTO user_roles (user_id, role_id)
SELECT id, 6 FROM users WHERE instrument LIKE '%Baixo%';

-- Inserir Teclado
INSERT IGNORE INTO user_roles (user_id, role_id)
SELECT id, 8 FROM users WHERE instrument LIKE '%Teclado%';

-- Inserir Bateria
INSERT IGNORE INTO user_roles (user_id, role_id)
SELECT id, 11 FROM users WHERE instrument LIKE '%Bateria%';

-- Inserir Percussão
INSERT IGNORE INTO user_roles (user_id, role_id)
SELECT id, 12 FROM users WHERE instrument LIKE '%Percussão%' OR instrument LIKE '%Percussao%';

-- Inserir Sonoplastia
INSERT IGNORE INTO user_roles (user_id, role_id)
SELECT id, 17 FROM users WHERE instrument LIKE '%Som%' OR instrument LIKE '%Sonoplastia%' OR instrument LIKE '%Mesa%';

-- Inserir Mídia
INSERT IGNORE INTO user_roles (user_id, role_id)
SELECT id, 18 FROM users WHERE instrument LIKE '%Projeci%';

-- Definir a principal como a primeira encontrada (lógica simplificada)
UPDATE user_roles SET is_primary = 1 WHERE id IN (
    SELECT id FROM (
        SELECT id, ROW_NUMBER() OVER (PARTITION BY user_id ORDER BY id ASC) as rn
        FROM user_roles
    ) t WHERE rn = 1
);
