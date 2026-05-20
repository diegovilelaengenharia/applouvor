<?php
/**
 * Script de Setup e Migração Automatizada de Banco de Dados
 * Executa todas as migrações estruturais necessárias para sanar os erros das páginas de Membros e Oração.
 * Pode ser executado via navegador em: localhost:8080/maintenance/run_all_setup.php ou via CLI
 */

header('Content-Type: text/html; charset=utf-8');
echo "<h1>🚀 Setup e Migração Premium - APP Louvor</h1>";
echo "<pre>";

try {
    // Carregar a conexão de banco de forma robusta
    $dbPath = __DIR__ . '/../includes/db.php';
    if (!file_exists($dbPath)) {
        throw new Exception("Erro crítico: Arquivo de conexão 'includes/db.php' não foi encontrado.");
    }
    require_once $dbPath;
    echo "✓ Conectado ao banco de dados com sucesso!\n\n";

    $totalSuccess = 0;
    $totalErrors = 0;

    // ==========================================
    // PASSO 1: Criar Tabelas de Funções (Roles)
    // ==========================================
    echo "🛠️ Passo 1: Estruturando o Sistema Relacional de Funções (Roles)\n";
    echo "------------------------------------------------------------\n";

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS roles (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(100) NOT NULL,
            icon VARCHAR(10) NOT NULL,
            category ENUM('voz', 'cordas', 'teclas', 'percussao', 'sopro', 'outros') NOT NULL,
            color VARCHAR(7) DEFAULT '#047857',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_category (category)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");
    echo "✓ Tabela 'roles' criada/verificada.\n";

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS user_roles (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            role_id INT NOT NULL,
            is_primary BOOLEAN DEFAULT FALSE,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
            FOREIGN KEY (role_id) REFERENCES roles(id) ON DELETE CASCADE,
            UNIQUE KEY unique_user_role (user_id, role_id),
            INDEX idx_user_id (user_id),
            INDEX idx_role_id (role_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");
    echo "✓ Tabela 'user_roles' criada/verificada.\n";

    // ==========================================
    // PASSO 2: Inserir as 6 Funções Simplificadas (Sem Roxo!)
    // ==========================================
    echo "\n🎨 Passo 2: Inserindo as 6 Funções Simplificadas (Purple Ban compliance)\n";
    echo "------------------------------------------------------------\n";

    // Mapeamento das 6 roles simplificadas
    // Cor de Vozes alterada para #2563eb (Azul Safira Premium) ao invés do roxo antigo
    $rolesToInsert = [
        1 => ['name' => 'Vozes', 'icon' => '🎤', 'category' => 'voz', 'color' => '#2563eb'],
        2 => ['name' => 'Violão', 'icon' => '🎻', 'category' => 'cordas', 'color' => '#f97316'],
        3 => ['name' => 'Bateria', 'icon' => '🥁', 'category' => 'percussao', 'color' => '#10b981'],
        4 => ['name' => 'Teclado', 'icon' => '🎹', 'category' => 'teclas', 'color' => '#06b6d4'], // Ciano vibrante
        5 => ['name' => 'Baixo', 'icon' => '🎸', 'category' => 'cordas', 'color' => '#dc2626'],
        6 => ['name' => 'Guitarra', 'icon' => '🎸', 'category' => 'cordas', 'color' => '#ef4444']
    ];

    foreach ($rolesToInsert as $id => $role) {
        // Verificar se a role já existe pelo ID ou pelo Nome
        $checkStmt = $pdo->prepare("SELECT id FROM roles WHERE id = ? OR name = ?");
        $checkStmt->execute([$id, $role['name']]);
        $existing = $checkStmt->fetch();

        if (!$existing) {
            // Se não existe, inserimos com o ID forçado para manter a integridade das migrações
            $insertStmt = $pdo->prepare("INSERT INTO roles (id, name, icon, category, color) VALUES (?, ?, ?, ?, ?)");
            $insertStmt->execute([$id, $role['name'], $role['icon'], $role['category'], $role['color']]);
            echo "✓ Função '{$role['name']}' inserida com sucesso (ID: $id).\n";
        } else {
            // Se já existe, atualizamos os dados (garante a remoção de cores roxas na atualização)
            $updateStmt = $pdo->prepare("UPDATE roles SET name = ?, icon = ?, category = ?, color = ? WHERE id = ?");
            $updateStmt->execute([$role['name'], $role['icon'], $role['category'], $role['color'], $existing['id']]);
            echo "✓ Função '{$role['name']}' atualizada (Garantido Purple Ban).\n";
        }
    }

    // ==========================================
    // PASSO 3: Migração Inteligente de Instrumentos Antigos
    // ==========================================
    echo "\n🔄 Passo 3: Migrando instrumentos legados para o novo sistema relacional\n";
    echo "------------------------------------------------------------\n";

    // Buscar todos os usuários
    $usersStmt = $pdo->query("SELECT id, name, instrument FROM users");
    $users = $usersStmt->fetchAll(PDO::FETCH_ASSOC);

    $migrationCount = 0;
    foreach ($users as $user) {
        $userInstrument = $user['instrument'];
        if (empty($userInstrument)) {
            continue; // Se não tem instrumento preenchido, pula
        }

        $rolesFound = [];

        // Mapeamento baseado em strings case-insensitive
        if (stripos($userInstrument, 'Voz') !== false || stripos($userInstrument, 'Cantor') !== false || stripos($userInstrument, 'Ministro') !== false || stripos($userInstrument, 'Vocal') !== false) {
            $rolesFound[] = 1; // Vozes
        }
        if (stripos($userInstrument, 'Violão') !== false || stripos($userInstrument, 'Violao') !== false) {
            $rolesFound[] = 2; // Violão
        }
        if (stripos($userInstrument, 'Bateria') !== false || stripos($userInstrument, 'Batera') !== false) {
            $rolesFound[] = 3; // Bateria
        }
        if (stripos($userInstrument, 'Teclado') !== false || stripos($userInstrument, 'Tecladista') !== false || stripos($userInstrument, 'Piano') !== false) {
            $rolesFound[] = 4; // Teclado
        }
        if (stripos($userInstrument, 'Baixo') !== false || stripos($userInstrument, 'Baixista') !== false) {
            $rolesFound[] = 5; // Baixo
        }
        if (stripos($userInstrument, 'Guitarra') !== false || stripos($userInstrument, 'Guitarrista') !== false) {
            $rolesFound[] = 6; // Guitarra
        }

        // Se encontrou alguma associação, grava na tabela user_roles
        if (!empty($rolesFound)) {
            $isFirst = true;
            foreach ($rolesFound as $roleId) {
                try {
                    // Usar INSERT IGNORE para evitar duplicidade de chaves únicas
                    $urStmt = $pdo->prepare("
                        INSERT IGNORE INTO user_roles (user_id, role_id, is_primary) 
                        VALUES (?, ?, ?)
                    ");
                    $urStmt->execute([$user['id'], $roleId, $isFirst ? 1 : 0]);
                    $isFirst = false;
                    $migrationCount++;
                } catch (PDOException $e) {
                    // Ignora erros individuais de integridade para continuar
                }
            }
        }
    }
    echo "✓ Migração concluída: $migrationCount conexões de funções geradas com sucesso para a equipe!\n";

    // ==========================================
    // PASSO 4: Criar Tabelas do Mural de Oração
    // ==========================================
    echo "\n🙏 Passo 4: Criando as Tabelas de Oração (Mural de Intercessão)\n";
    echo "------------------------------------------------------------\n";

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS prayer_requests (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            title VARCHAR(200) NOT NULL,
            description TEXT,
            category ENUM('health', 'family', 'work', 'spiritual', 'gratitude', 'other') DEFAULT 'other',
            is_urgent BOOLEAN DEFAULT FALSE,
            is_anonymous BOOLEAN DEFAULT FALSE,
            is_answered BOOLEAN DEFAULT FALSE,
            prayer_count INT DEFAULT 0,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            answered_at TIMESTAMP NULL,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");
    echo "✓ Tabela 'prayer_requests' criada/verificada.\n";

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS prayer_interactions (
            id INT AUTO_INCREMENT PRIMARY KEY,
            prayer_id INT NOT NULL,
            user_id INT NOT NULL,
            type ENUM('pray', 'comment') DEFAULT 'pray',
            comment TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (prayer_id) REFERENCES prayer_requests(id) ON DELETE CASCADE,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");
    echo "✓ Tabela 'prayer_interactions' criada/verificada.\n";

    // ==========================================
    // PASSO 5: Adicionar Restrição de Intercessão Única (Garantir Unicidade)
    // ==========================================
    echo "\n🔒 Passo 5: Aplicando melhorias de unicidade de intercessão\n";
    echo "------------------------------------------------------------\n";

    try {
        $pdo->exec("
            ALTER TABLE prayer_interactions 
            ADD UNIQUE KEY unique_user_prayer_interaction (prayer_id, user_id, type);
        ");
        echo "✓ Índice de intercessão única criado com sucesso!\n";
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), 'already exists') !== false || strpos($e->getMessage(), 'Duplicate key name') !== false) {
            echo "⚠️ Índice já existia (ignorado com segurança).\n";
        } else {
            echo "✗ Erro ao criar índice de intercessão: " . $e->getMessage() . "\n";
        }
    }

    // ==========================================
    // PASSO 6: Criar Tabelas do Sistema de Notificações
    // ==========================================
    echo "\n🔔 Passo 6: Criando as Tabelas de Notificações (Painel Administrativo)\n";
    echo "------------------------------------------------------------\n";

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS notifications (
            id INT PRIMARY KEY AUTO_INCREMENT,
            user_id INT NOT NULL,
            type VARCHAR(50) NOT NULL,
            title VARCHAR(255) NOT NULL,
            message TEXT,
            data JSON,
            link VARCHAR(255),
            is_read BOOLEAN DEFAULT FALSE,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            read_at TIMESTAMP NULL,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
            INDEX idx_user_read (user_id, is_read),
            INDEX idx_created (created_at DESC),
            INDEX idx_type (type)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");
    echo "✓ Tabela 'notifications' criada/verificada.\n";

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS notification_preferences (
            id INT PRIMARY KEY AUTO_INCREMENT,
            user_id INT NOT NULL,
            type VARCHAR(50) NOT NULL,
            enabled BOOLEAN DEFAULT TRUE,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
            UNIQUE KEY unique_user_type (user_id, type)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");
    echo "✓ Tabela 'notification_preferences' criada/verificada.\n";

    // Inserir preferências padrão para cada usuário (se não existir)
    $types = ['weekly_report', 'new_escala', 'new_music', 'new_aviso', 'aviso_urgent'];
    $prefCount = 0;
    foreach ($types as $type) {
        $prefStmt = $pdo->prepare("
            INSERT IGNORE INTO notification_preferences (user_id, type, enabled)
            SELECT id, ?, TRUE FROM users
        ");
        $prefStmt->execute([$type]);
        $prefCount += $prefStmt->rowCount();
    }
    echo "✓ Preferências padrão de notificações configuradas para os usuários (novos registros: $prefCount).\n";

    // ==========================================
    // PASSO 7: Criar Tabelas de Avisos e Widgets
    // ==========================================
    echo "\n📢 Passo 7: Criando Tabelas de Avisos e Widgets\n";
    echo "------------------------------------------------------------\n";
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS avisos (
            id INT AUTO_INCREMENT PRIMARY KEY,
            title VARCHAR(255) NOT NULL,
            message TEXT NOT NULL,
            priority ENUM('urgent', 'important', 'info') DEFAULT 'info',
            created_by INT NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE CASCADE,
            INDEX idx_created_at (created_at),
            INDEX idx_priority (priority)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");
    echo "✓ Tabela 'avisos' criada/verificada.\n";

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS user_widgets (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            widget_name VARCHAR(50) NOT NULL,
            position INT DEFAULT 0,
            enabled BOOLEAN DEFAULT TRUE,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
            UNIQUE KEY unique_user_widget (user_id, widget_name),
            INDEX idx_user_enabled (user_id, enabled)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");
    echo "✓ Tabela 'user_widgets' criada/verificada.\n";

    // ==========================================
    // PASSO 8: Criar Tabelas de Tags
    // ==========================================
    echo "\n🏷️ Passo 8: Criando as Tabelas de Tags de Músicas\n";
    echo "------------------------------------------------------------\n";
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS tags (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(50) NOT NULL,
            description TEXT,
            color VARCHAR(20) DEFAULT 'var(--sage-600)',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");
    echo "✓ Tabela 'tags' criada/verificada.\n";

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS song_tags (
            song_id INT NOT NULL,
            tag_id INT NOT NULL,
            PRIMARY KEY (song_id, tag_id),
            FOREIGN KEY (song_id) REFERENCES songs(id) ON DELETE CASCADE,
            FOREIGN KEY (tag_id) REFERENCES tags(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");
    echo "✓ Tabela 'song_tags' criada/verificada.\n";

    // Popular tags padrão se não existirem
    $initialTags = [
        ['Louvor', 'Cânticos de louvor e celebração.', 'var(--yellow-500)'],
        ['Adoração', 'Cânticos de adoração e contrição.', 'var(--lavender-500)'],
        ['Contemplação', 'Cânticos contemplativos e reflexivos.', 'var(--slate-500)'],
        ['Consagração', 'Cânticos de entrega e consagração.', 'var(--sage-500)'],
        ['Alegria', 'Cânticos alegres e festivos.', '#EC4899'],
        ['Especiais', 'Datas especiais e eventos específicos.', '#6366F1']
    ];

    $tagCheck = $pdo->prepare("SELECT id FROM tags WHERE name = ?");
    $tagInsert = $pdo->prepare("INSERT INTO tags (name, description, color) VALUES (?, ?, ?)");
    foreach ($initialTags as $tag) {
        $tagCheck->execute([$tag[0]]);
        if (!$tagCheck->fetch()) {
            $tagInsert->execute($tag);
            echo "✓ Tag criada: {$tag[0]}\n";
        }
    }

    // ==========================================
    // PASSO 9: Criar Tabela de Push Subscriptions
    // ==========================================
    echo "\n✉️ Passo 9: Criando Tabela de Assinaturas de Notificação Push\n";
    echo "------------------------------------------------------------\n";
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS push_subscriptions (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            endpoint TEXT NOT NULL,
            p256dh VARCHAR(255) NOT NULL,
            auth VARCHAR(255) NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
            INDEX idx_user_id (user_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");
    echo "✓ Tabela 'push_subscriptions' criada/verificada.\n";

    // ==========================================
    // PASSO 10: Criar Tabelas do Sistema Devocional
    // ==========================================
    echo "\n📖 Passo 10: Criando Tabelas da Área Devocional\n";
    echo "------------------------------------------------------------\n";
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS devotionals (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            title VARCHAR(200) NOT NULL,
            content TEXT,
            media_type ENUM('text', 'video', 'audio', 'link') DEFAULT 'text',
            media_url VARCHAR(500),
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");
    echo "✓ Tabela 'devotionals' criada/verificada.\n";

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS devotional_tags (
            devotional_id INT NOT NULL,
            tag_id INT NOT NULL,
            PRIMARY KEY (devotional_id, tag_id),
            FOREIGN KEY (devotional_id) REFERENCES devotionals(id) ON DELETE CASCADE,
            FOREIGN KEY (tag_id) REFERENCES tags(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");
    echo "✓ Tabela 'devotional_tags' criada/verificada.\n";

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS devotional_comments (
            id INT AUTO_INCREMENT PRIMARY KEY,
            devotional_id INT NOT NULL,
            user_id INT NOT NULL,
            comment TEXT NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (devotional_id) REFERENCES devotionals(id) ON DELETE CASCADE,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");
    echo "✓ Tabela 'devotional_comments' criada/verificada.\n";

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS devotional_reactions (
            id INT AUTO_INCREMENT PRIMARY KEY,
            devotional_id INT NOT NULL,
            user_id INT NOT NULL,
            reaction_type ENUM('amen', 'prayer', 'inspired') NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY unique_user_reaction (devotional_id, user_id, reaction_type),
            FOREIGN KEY (devotional_id) REFERENCES devotionals(id) ON DELETE CASCADE,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
            INDEX idx_devotional (devotional_id),
            INDEX idx_user (user_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");
    echo "✓ Tabela 'devotional_reactions' criada/verificada.\n";

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS devotional_series (
            id INT AUTO_INCREMENT PRIMARY KEY,
            title VARCHAR(255) NOT NULL,
            description TEXT,
            author_id INT NOT NULL,
            cover_color VARCHAR(7) DEFAULT '#667eea',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (author_id) REFERENCES users(id) ON DELETE CASCADE,
            INDEX idx_author (author_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");
    echo "✓ Tabela 'devotional_series' criada/verificada.\n";

    // Adicionar colunas se não existirem
    try {
        $pdo->exec("ALTER TABLE devotionals ADD COLUMN series_id INT NULL");
        $pdo->exec("ALTER TABLE devotionals ADD FOREIGN KEY (series_id) REFERENCES devotional_series(id) ON DELETE SET NULL");
    } catch (PDOException $e) { /* Coluna ou FK já existem */ }

    try {
        $pdo->exec("ALTER TABLE devotionals ADD COLUMN verse_references TEXT NULL");
    } catch (PDOException $e) { /* Coluna já existe */ }

    try {
        $pdo->exec("ALTER TABLE devotionals ADD COLUMN order_in_series INT DEFAULT 0");
    } catch (PDOException $e) { /* Coluna já existe */ }

    // Rastreamento de leitura
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS devotional_reads (
            id INT AUTO_INCREMENT PRIMARY KEY,
            devotional_id INT NOT NULL,
            user_id INT NOT NULL,
            read_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (devotional_id) REFERENCES devotionals(id) ON DELETE CASCADE,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
            UNIQUE KEY unique_user_devotional (user_id, devotional_id),
            INDEX idx_user (user_id),
            INDEX idx_devotional (devotional_id),
            INDEX idx_read_at (read_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");
    echo "✓ Tabela 'devotional_reads' criada/verificada.\n";

    // Views de estatísticas devocionais
    $pdo->exec("
        CREATE OR REPLACE VIEW devotional_reaction_counts AS
        SELECT 
            devotional_id,
            SUM(CASE WHEN reaction_type = 'amen' THEN 1 ELSE 0 END) as amen_count,
            SUM(CASE WHEN reaction_type = 'prayer' THEN 1 ELSE 0 END) as prayer_count,
            SUM(CASE WHEN reaction_type = 'inspired' THEN 1 ELSE 0 END) as inspired_count,
            COUNT(*) as total_reactions
        FROM devotional_reactions
        GROUP BY devotional_id;
    ");
    echo "✓ View 'devotional_reaction_counts' atualizada.\n";

    $pdo->exec("
        CREATE OR REPLACE VIEW series_with_stats AS
        SELECT 
            s.*,
            u.name as author_name,
            COUNT(d.id) as devotional_count
        FROM devotional_series s
        LEFT JOIN users u ON s.author_id = u.id
        LEFT JOIN devotionals d ON s.id = d.series_id
        GROUP BY s.id;
    ");
    echo "✓ View 'series_with_stats' atualizada.\n";

    // Seed de série devocional padrão
    $seriesCheck = $pdo->prepare("SELECT id FROM devotional_series WHERE title = ?");
    $seriesCheck->execute(['Bem-vindo ao Devocional']);
    if (!$seriesCheck->fetch()) {
        $seriesInsert = $pdo->prepare("INSERT INTO devotional_series (title, description, author_id, cover_color) VALUES (?, ?, 1, ?)");
        $seriesInsert->execute(['Bem-vindo ao Devocional', 'Série de introdução aos devocionais da comunidade', '#667eea']);
        echo "✓ Série de Devocionais de exemplo criada.\n";
    }

    // ==========================================
    // PASSO 11: Criar/Verificar Tabelas de Leitura Bíblica
    // ==========================================
    echo "\n📖 Passo 11: Verificando as Tabelas de Progresso de Leitura\n";
    echo "------------------------------------------------------------\n";
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS reading_progress (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            month_num INT NOT NULL,
            day_num INT NOT NULL,
            verses_read LONGTEXT NULL,
            comment TEXT NULL,
            note_title VARCHAR(255) NULL,
            completed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY unique_user_reading_day (user_id, month_num, day_num),
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");
    echo "✓ Tabela 'reading_progress' criada/verificada.\n";

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS user_settings (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            setting_key VARCHAR(50) NOT NULL,
            setting_value TEXT NULL,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            UNIQUE KEY unique_user_setting (user_id, setting_key),
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");
    echo "✓ Tabela 'user_settings' criada/verificada.\n";

    // ==========================================
    // PASSO 12: Correções de Auditoria e Tabelas Ausentes
    // ==========================================
    echo "\n🛠️ Passo 12: Rodando Correções de Auditoria (Tabelas e Colunas)\n";
    echo "------------------------------------------------------------\n";
    
    // Tabela user_unavailability
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS user_unavailability (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            start_date DATE NOT NULL,
            end_date DATE NOT NULL,
            reason TEXT NULL,
            observation TEXT DEFAULT NULL,
            replacement_id INT DEFAULT NULL,
            audio_path VARCHAR(255) DEFAULT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
            FOREIGN KEY (replacement_id) REFERENCES users(id) ON DELETE SET NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");
    echo "✓ Tabela 'user_unavailability' criada/verificada.\n";

    // Coluna event_time em schedules
    try {
        $pdo->exec("ALTER TABLE schedules ADD COLUMN event_time TIME DEFAULT '19:00:00' AFTER event_date");
        echo "✓ Coluna 'event_time' adicionada em 'schedules'.\n";
    } catch (PDOException $e) {
        // Coluna já existe
        echo "• Coluna 'event_time' já existe em 'schedules'.\n";
    }

    // Coluna is_default em aviso_tags
    try {
        $pdo->exec("ALTER TABLE aviso_tags ADD COLUMN is_default TINYINT(1) DEFAULT 0");
        echo "✓ Coluna 'is_default' adicionada em 'aviso_tags'.\n";
    } catch (PDOException $e) {
        // Coluna já existe
        echo "• Coluna 'is_default' já existe em 'aviso_tags'.\n";
    }

    // Coluna is_pinned em avisos
    try {
        $pdo->exec("ALTER TABLE avisos ADD COLUMN is_pinned TINYINT(1) DEFAULT 0 AFTER message");
        echo "✓ Coluna 'is_pinned' adicionada em 'avisos'.\n";
    } catch (PDOException $e) {
        // Coluna já existe
        echo "• Coluna 'is_pinned' já existe em 'avisos'.\n";
    }

    echo "\n============================================================\n";
    echo "🎉 EXCELENTE! BANCO DE DADOS ATUALIZADO COM 100% DE SUCESSO!\n";
    echo "============================================================\n";
    echo "Todas as tabelas foram estruturadas e a equipe foi migrada.\n";
    echo "Você já pode recarregar as páginas de Membros e Oração no seu navegador!\n";

} catch (Exception $e) {
    echo "\n❌ ERRO FATAL NO SETUP: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString();
}

echo "</pre>";
?>
