<?php
// admin/_setup_tags_db.php
require_once '../includes/db.php';

try {
    // Criar tabela tags
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS tags (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(50) NOT NULL,
            description TEXT,
            color VARCHAR(20) DEFAULT 'var(--sage-600)',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");

    // Criar tabela de relacionamento músicas-tags
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS song_tags (
            song_id INT NOT NULL,
            tag_id INT NOT NULL,
            PRIMARY KEY (song_id, tag_id),
            FOREIGN KEY (song_id) REFERENCES songs(id) ON DELETE CASCADE,
            FOREIGN KEY (tag_id) REFERENCES tags(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");

    // Popular com alguns dados iniciais baseados na imagem
    $initialTags = [
        ['Louvor', 'São cânticos cujas letras expressam elogio e agradecimento por aquilo que Deus fez.', 'var(--yellow-500)'], // Amarelo (mantido)
        ['Adoração', 'São cânticos cujas letras expressam reconhecimento a Deus por aquilo que Ele é.', 'var(--lavender-500)'], // Purple 500 - Vibrante
        ['Contemplação', 'São cânticos que se concentram em meditar na Pessoa de Deus.', 'var(--slate-500)'], // Blue 500 (mantido)
        ['Consagração', 'Tratam da dedicação de nossas vidas a Deus.', 'var(--sage-500)'], // Green 500 - Vibrante
        ['Alegria', 'Expressam alegria pelo Senhor e seus feitos.', '#EC4899'], // Pink (mantido)
        ['Especiais', 'Temas como casamento, batizados, etc.', '#6366F1'] // Indigo (mantido)
    ];

    $stmt = $pdo->prepare("INSERT INTO tags (name, description, color) VALUES (?, ?, ?)");

    foreach ($initialTags as $tag) {
        // Verificar se já existe
        $check = $pdo->prepare("SELECT id FROM tags WHERE name = ?");
        $check->execute([$tag[0]]);
        if (!$check->fetch()) {
            $stmt->execute($tag);
            echo "Tag criada: " . $tag[0] . "<br>";
        }
    }

    echo "Tabelas de Tags configuradas com sucesso!";
} catch (PDOException $e) {
    echo "Erro: " . $e->getMessage();
}
