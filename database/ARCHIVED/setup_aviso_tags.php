<?php
/**
 * Script para executar a criação das tabelas de tags de avisos
 * Execute via navegador: http://localhost:8000/database/setup_aviso_tags.php
 */

require_once '../includes/db.php';

echo "<h2>Setup do Sistema de Tags de Avisos</h2>";
echo "<pre>";

try {
    // 1. Criar tabela de tags
    echo "1. Criando tabela aviso_tags...\n";
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS aviso_tags (
            id INT PRIMARY KEY AUTO_INCREMENT,
            name VARCHAR(50) NOT NULL UNIQUE,
            color VARCHAR(7) NOT NULL,
            icon VARCHAR(50) NULL,
            is_default BOOLEAN DEFAULT FALSE,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    echo "   ✅ Tabela aviso_tags criada\n\n";

    // 2. Criar tabela de relações
    echo "2. Criando tabela aviso_tag_relations...\n";
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS aviso_tag_relations (
            aviso_id INT NOT NULL,
            tag_id INT NOT NULL,
            PRIMARY KEY (aviso_id, tag_id),
            FOREIGN KEY (aviso_id) REFERENCES avisos(id) ON DELETE CASCADE,
            FOREIGN KEY (tag_id) REFERENCES aviso_tags(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    echo "   ✅ Tabela aviso_tag_relations criada\n\n";

    // 3. Criar tabela de leituras
    echo "3. Criando tabela aviso_reads...\n";
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS aviso_reads (
            user_id INT NOT NULL,
            aviso_id INT NOT NULL,
            read_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (user_id, aviso_id),
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
            FOREIGN KEY (aviso_id) REFERENCES avisos(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    echo "   ✅ Tabela aviso_reads criada\n\n";

    // 4. Adicionar campo is_pinned
    echo "4. Adicionando campo is_pinned na tabela avisos...\n";
    try {
        $pdo->exec("ALTER TABLE avisos ADD COLUMN is_pinned BOOLEAN DEFAULT FALSE AFTER archived_at");
        echo "   ✅ Campo is_pinned adicionado\n\n";
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), 'Duplicate column') !== false) {
            echo "   ⚠️ Campo is_pinned já existe\n\n";
        } else {
            throw $e;
        }
    }

    // 5. Inserir tags padrão
    echo "5. Inserindo tags padrão...\n";
    $defaultTags = [
        ['Geral', '#64748b', 'megaphone'],
        ['Música', '#ec4899', 'music'],
        ['Eventos', '#8b5cf6', 'calendar-days'],
        ['Espiritual', '#10b981', 'heart'],
        ['Urgente', '#ef4444', 'alert-circle'],
        ['Importante', '#f59e0b', 'star']
    ];

    $stmt = $pdo->prepare("
        INSERT INTO aviso_tags (name, color, icon, is_default) 
        VALUES (?, ?, ?, TRUE)
        ON DUPLICATE KEY UPDATE color = VALUES(color), icon = VALUES(icon)
    ");

    foreach ($defaultTags as $tag) {
        $stmt->execute($tag);
        echo "   ✅ Tag '{$tag[0]}' inserida/atualizada\n";
    }

    echo "\n6. Verificando tags criadas...\n";
    $tags = $pdo->query("SELECT * FROM aviso_tags ORDER BY id")->fetchAll(PDO::FETCH_ASSOC);
    echo "   📋 Total de tags: " . count($tags) . "\n";
    foreach ($tags as $tag) {
        echo "   • {$tag['name']} - {$tag['color']} - {$tag['icon']}\n";
    }

    echo "\n✅ Setup concluído com sucesso!\n";
    echo "\n⚠️ IMPORTANTE: Após verificar, você pode deletar este arquivo por segurança.\n";

} catch (PDOException $e) {
    echo "❌ Erro: " . $e->getMessage() . "\n";
    echo "\nDetalhes: " . $e->getTraceAsString() . "\n";
}

echo "</pre>";
?>
