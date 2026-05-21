-- Script para inserir tags padrão do sistema de avisos

INSERT INTO aviso_tags (name, color, icon, is_default) VALUES
('Geral', '#64748b', 'megaphone', TRUE),
('Música', '#ec4899', 'music', TRUE),
('Eventos', '#8b5cf6', 'calendar-days', TRUE),
('Espiritual', '#10b981', 'heart', TRUE),
('Urgente', '#ef4444', 'alert-circle', TRUE),
('Importante', '#f59e0b', 'star', TRUE)
ON DUPLICATE KEY UPDATE 
    color = VALUES(color),
    icon = VALUES(icon),
    is_default = VALUES(is_default);

-- Verificar tags inseridas
SELECT * FROM aviso_tags ORDER BY id;
