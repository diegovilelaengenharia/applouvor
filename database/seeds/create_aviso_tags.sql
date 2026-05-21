-- Script para criar tabelas do novo sistema de avisos com tags

-- 1. Tabela de Tags/Categorias
CREATE TABLE IF NOT EXISTS aviso_tags (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(50) NOT NULL UNIQUE,
    color VARCHAR(7) NOT NULL, -- Hex color (ex: #3b82f6)
    icon VARCHAR(50) NULL, -- Nome do ícone Lucide (ex: megaphone)
    is_default BOOLEAN DEFAULT FALSE, -- Tags padrão não podem ser deletadas
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. Tabela de Relação Avisos ↔ Tags (muitos para muitos)
CREATE TABLE IF NOT EXISTS aviso_tag_relations (
    aviso_id INT NOT NULL,
    tag_id INT NOT NULL,
    PRIMARY KEY (aviso_id, tag_id),
    FOREIGN KEY (aviso_id) REFERENCES avisos(id) ON DELETE CASCADE,
    FOREIGN KEY (tag_id) REFERENCES aviso_tags(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. Tabela de Controle de Leitura
CREATE TABLE IF NOT EXISTS aviso_reads (
    user_id INT NOT NULL,
    aviso_id INT NOT NULL,
    read_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (user_id, aviso_id),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (aviso_id) REFERENCES avisos(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4. Adicionar campo is_pinned na tabela avisos (se não existir)
ALTER TABLE avisos 
ADD COLUMN IF NOT EXISTS is_pinned BOOLEAN DEFAULT FALSE AFTER archived_at;

-- 5. Verificar estrutura da tabela avisos
DESCRIBE avisos;
