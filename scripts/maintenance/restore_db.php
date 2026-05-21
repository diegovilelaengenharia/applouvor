<?php
/**
 * MIGRATION DE RESTAURAÇÃO - App Louvor
 * Garante que todas as tabelas e colunas originais existam.
 */

require_once __DIR__ . '/../../src/config/db.php';

echo "🚀 Restaurando Tabelas e Colunas...\n";

try {
    // 1. Tabela de Avisos (Colunas completas)
    $pdo->exec("CREATE TABLE IF NOT EXISTS avisos (
        id INT AUTO_INCREMENT PRIMARY KEY,
        title VARCHAR(200) NOT NULL,
        message TEXT NOT NULL,
        priority ENUM('normal', 'important', 'urgent') DEFAULT 'normal',
        type VARCHAR(50) DEFAULT 'geral',
        target_audience VARCHAR(50) DEFAULT 'all',
        created_by INT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        expires_at DATETIME DEFAULT NULL,
        archived_at DATETIME DEFAULT NULL
    )");

    // Garantir colunas em avisos (caso a tabela já exista incompleta)
    $cols = $pdo->query("SHOW COLUMNS FROM avisos")->fetchAll(PDO::FETCH_COLUMN);
    if (!in_array('message', $cols) && in_array('content', $cols)) $pdo->exec("ALTER TABLE avisos CHANGE content message TEXT NOT NULL");
    if (!in_array('target_audience', $cols)) $pdo->exec("ALTER TABLE avisos ADD COLUMN target_audience VARCHAR(50) DEFAULT 'all'");
    if (!in_array('archived_at', $cols)) $pdo->exec("ALTER TABLE avisos ADD COLUMN archived_at DATETIME DEFAULT NULL");

    // 2. Reações de Avisos
    $pdo->exec("CREATE TABLE IF NOT EXISTS aviso_reactions (
        id INT AUTO_INCREMENT PRIMARY KEY,
        aviso_id INT NOT NULL,
        user_id INT NOT NULL,
        reaction_type VARCHAR(20) NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY user_aviso_reaction (aviso_id, user_id, reaction_type)
    )");

    // 3. Tags de Avisos
    $pdo->exec("CREATE TABLE IF NOT EXISTS aviso_tags (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(50) NOT NULL UNIQUE,
        color VARCHAR(20) DEFAULT '#3B82F6'
    )");

    $pdo->exec("CREATE TABLE IF NOT EXISTS aviso_tag_relations (
        aviso_id INT NOT NULL,
        tag_id INT NOT NULL,
        PRIMARY KEY (aviso_id, tag_id)
    )");

    // 4. Leitura Bíblica
    $pdo->exec("CREATE TABLE IF NOT EXISTS reading_progress (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        month_num INT NOT NULL,
        day_num INT NOT NULL,
        verses_read JSON DEFAULT NULL,
        comment TEXT DEFAULT NULL,
        note_title VARCHAR(255) DEFAULT NULL,
        completed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY user_day (user_id, month_num, day_num)
    )");

    // 5. Configurações de Usuário
    $pdo->exec("CREATE TABLE IF NOT EXISTS user_settings (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        setting_key VARCHAR(50) NOT NULL,
        setting_value TEXT,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        UNIQUE KEY user_setting (user_id, setting_key)
    )");

    echo "✅ Banco de dados sincronizado com sucesso!\n";

} catch (Exception $e) {
    echo "❌ ERRO: " . $e->getMessage() . "\n";
}
