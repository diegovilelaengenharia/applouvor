-- Limpar roles existentes
DELETE FROM roles;
ALTER TABLE roles AUTO_INCREMENT = 1;

-- Inserir novas roles simplificadas (sem categorias complexas, ou usando categoria 'Geral' se necessário, mas vou manter as categorias internas para cor se o código depender, ou simplificar)
-- O código PHP ainda usa categorias para agrupar. Vou manter categorias simples ou apenas uma 'Principal' se o usuário não quiser agrupamento.
-- O usuário disse: "Coloque apenas. Vozes, Violão, Bateria, Teclado, Baixo, Guitarra"
-- Vou manter as categorias para as cores funcionarem, mas posso simplificar os nomes.

INSERT INTO roles (name, icon, category, color) VALUES
('Vozes', '🎤', 'voz', '#8b5cf6'),
('Violão', '🎻', 'cordas', '#f97316'),
('Bateria', '🥁', 'percussao', '#10b981'),
('Teclado', '🎹', 'teclas', '#3b82f6'),
('Baixo', '🎸', 'cordas', '#dc2626'),
('Guitarra', '🎸', 'cordas', '#ef4444');
