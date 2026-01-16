<?php
require_once 'includes/db.php';

try {
    // 1. Tabela de Músicas (Biblioteca) - REFAZENDO com estrutura nova
    $pdo->exec("DROP TABLE IF EXISTS library_songs");
    $sqlLibrary = "CREATE TABLE library_songs (
        id INT AUTO_INCREMENT PRIMARY KEY,
        title VARCHAR(150) NOT NULL,
        artist VARCHAR(100),
        version VARCHAR(100),
        key_note VARCHAR(10),
        bpm INT,
        category VARCHAR(50),
        link_lyrics VARCHAR(255),
        link_cifra VARCHAR(255),
        link_audio VARCHAR(255),
        link_video VARCHAR(255),
        observation TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";
    $pdo->exec($sqlLibrary);
    echo "Tabela 'library_songs' recriada com sucesso.<br>";

    // 2. Garantir Estrutura Core (Escalas) - REFAZENDO para garantir
    // Dropando para evitar conflitos de chave estrangeira mal formada
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 0");
    $pdo->exec("DROP TABLE IF EXISTS songs"); // Tabela antiga ligada a escalas
    $pdo->exec("DROP TABLE IF EXISTS repertories");
    $pdo->exec("DROP TABLE IF EXISTS scale_members");
    $pdo->exec("DROP TABLE IF EXISTS scales");

    $sqlScales = "CREATE TABLE scales (
        id INT AUTO_INCREMENT PRIMARY KEY,
        event_date DATE NOT NULL,
        event_type VARCHAR(50) DEFAULT 'Culto de Domingo',
        description TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";
    $pdo->exec($sqlScales);

    $sqlScaleMembers = "CREATE TABLE scale_members (
        id INT AUTO_INCREMENT PRIMARY KEY,
        scale_id INT NOT NULL,
        user_id INT NOT NULL,
        instrument VARCHAR(50),
        confirmed TINYINT(1) DEFAULT 0,
        FOREIGN KEY (scale_id) REFERENCES scales(id) ON DELETE CASCADE,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    )";
    $pdo->exec($sqlScaleMembers);

    // Tabela de Repertório da Escala (agora referenciando a biblioteca)
    // Vamos simplificar: Uma escala tem itens de repertório que apontam para a library
    $sqlRepertoryItems = "CREATE TABLE repertory_items (
        id INT AUTO_INCREMENT PRIMARY KEY,
        scale_id INT NOT NULL,
        song_id INT NOT NULL, -- Referência à Library
        order_index INT DEFAULT 0,
        observation TEXT, -- Obs específica para o dia
        FOREIGN KEY (scale_id) REFERENCES scales(id) ON DELETE CASCADE,
        FOREIGN KEY (song_id) REFERENCES library_songs(id) ON DELETE CASCADE
    )";
    $pdo->exec($sqlRepertoryItems);

    $pdo->exec("SET FOREIGN_KEY_CHECKS = 1");
    echo "Tabelas Core (scales, scale_members, repertory_items) recriadas.<br>";

    // 3. Inserir Música de Teste
    $stmt = $pdo->prepare("INSERT INTO library_songs 
        (title, artist, version, key_note, bpm, category, link_lyrics, link_cifra, link_audio, link_video, observation) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

    $stmt->execute([
        'Bondade de Deus',
        'Isaías Saad',
        'Ao Vivo',
        'A',
        68,
        'Adoração',
        'https://letras.mus.br/isaias-saad/bondade-de-deus/',
        'https://cifraclub.com.br/isaias-saad/bondade-de-deus/',
        'https://open.spotify.com/track/xyz',
        'https://youtube.com/watch?v=xyz',
        'Início suave apenas com piano.'
    ]);
    echo "Música de teste 'Bondade de Deus' inserida.<br>";

    // 4. Inserir Usuário de Teste (Cristãozinho)
    $stmtUser = $pdo->prepare("INSERT INTO users 
        (name, password, role, category, phone, address_street, address_number, address_neighborhood, avatar) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");

    $stmtUser->execute([
        'Cristãozinho',
        '1234', // Senha simples
        'user',
        'voz_masculina',
        '35 99999-9999',
        'Rua da Alegria',
        '33',
        'Centro',
        'cristaozinho.jpg' // Avatar placeholder
    ]);
    echo "Usuário de teste 'Cristãozinho' inserido.<br>";
} catch (PDOException $e) {
    die("Erro ao configurar BD: " . $e->getMessage());
}
