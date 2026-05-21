<?php
/**
 * Helper Functions para Devocionais
 * Funções auxiliares para reações, séries, versículos, etc.
 */

// ==========================================
// REAÇÕES
// ==========================================

/**
 * Adicionar ou remover reação
 */
function toggleDevotionalReaction($pdo, $devotionalId, $userId, $reactionType) {
    // Verificar se já existe
    $stmt = $pdo->prepare("
        SELECT id FROM devotional_reactions 
        WHERE devotional_id = ? AND user_id = ? AND reaction_type = ?
    ");
    $stmt->execute([$devotionalId, $userId, $reactionType]);
    
    if ($stmt->fetch()) {
        // Já existe, remover
        $deleteStmt = $pdo->prepare("
            DELETE FROM devotional_reactions 
            WHERE devotional_id = ? AND user_id = ? AND reaction_type = ?
        ");
        $deleteStmt->execute([$devotionalId, $userId, $reactionType]);
        return ['action' => 'removed', 'type' => $reactionType];
    } else {
        // Não existe, adicionar
        $insertStmt = $pdo->prepare("
            INSERT INTO devotional_reactions (devotional_id, user_id, reaction_type) 
            VALUES (?, ?, ?)
        ");
        $insertStmt->execute([$devotionalId, $userId, $reactionType]);
        return ['action' => 'added', 'type' => $reactionType];
    }
}

/**
 * Obter contadores de reações de um devocional
 */
function getDevotionalReactionCounts($pdo, $devotionalId) {
    $stmt = $pdo->prepare("
        SELECT * FROM devotional_reaction_counts 
        WHERE devotional_id = ?
    ");
    $stmt->execute([$devotionalId]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$result) {
        return [
            'amen_count' => 0,
            'prayer_count' => 0,
            'inspired_count' => 0,
            'total_reactions' => 0
        ];
    }
    
    return $result;
}

/**
 * Obter reações do usuário atual para um devocional
 */
function getUserReactions($pdo, $devotionalId, $userId) {
    $stmt = $pdo->prepare("
        SELECT reaction_type FROM devotional_reactions 
        WHERE devotional_id = ? AND user_id = ?
    ");
    $stmt->execute([$devotionalId, $userId]);
    return $stmt->fetchAll(PDO::FETCH_COLUMN);
}

// ==========================================
// SÉRIES
// ==========================================

/**
 * Criar nova série
 */
function createDevotionalSeries($pdo, $title, $description, $authorId, $coverColor = '#667eea') {
    $stmt = $pdo->prepare("
        INSERT INTO devotional_series (title, description, author_id, cover_color) 
        VALUES (?, ?, ?, ?)
    ");
    $stmt->execute([$title, $description, $authorId, $coverColor]);
    return $pdo->lastInsertId();
}

/**
 * Obter informações de uma série
 */
function getSeriesInfo($pdo, $seriesId) {
    $stmt = $pdo->prepare("SELECT * FROM series_with_stats WHERE id = ?");
    $stmt->execute([$seriesId]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

/**
 * Obter devocionais de uma série
 */
function getSeriesDevotonals($pdo, $seriesId) {
    $stmt = $pdo->prepare("
        SELECT d.*, u.name as author_name 
        FROM devotionals d
        LEFT JOIN users u ON d.user_id = u.id
        WHERE d.series_id = ?
        ORDER BY d.order_in_series ASC, d.created_at ASC
    ");
    $stmt->execute([$seriesId]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Obter próximo/anterior devocional na série
 */
function getSeriesNavigation($pdo, $currentDevotionalId, $seriesId) {
    // Get current order
    $stmt = $pdo->prepare("SELECT order_in_series FROM devotionals WHERE id = ?");
    $stmt->execute([$currentDevotionalId]);
    $current = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$current) return null;
    
    $result = ['prev' => null, 'next' => null];
    
    // Previous
    $stmt = $pdo->prepare("
        SELECT id, title FROM devotionals 
        WHERE series_id = ? AND order_in_series < ?
        ORDER BY order_in_series DESC LIMIT 1
    ");
    $stmt->execute([$seriesId, $current['order_in_series']]);
    $result['prev'] = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Next
    $stmt = $pdo->prepare("
        SELECT id, title FROM devotionals 
        WHERE series_id = ? AND order_in_series > ?
        ORDER BY order_in_series ASC LIMIT 1
    ");
    $stmt->execute([$seriesId, $current['order_in_series']]);
    $result['next'] = $stmt->fetch(PDO::FETCH_ASSOC);
    
    return $result;
}

// ==========================================
// VERSÍCULOS BÍBLICOS
// ==========================================

/**
 * Parser para shortcode de versículos
 * Formato: [verso João 3:16] ou [verso João 3:16-17]
 */
function parseVerseShortcodes($content) {
    $pattern = '/\[verso\s+([^]]+)\]/i';
    
    return preg_replace_callback($pattern, function($matches) {
        $reference = trim($matches[1]);
        return renderVerseCard($reference);
    }, $content);
}

/**
 * Renderizar card de versículo
 */
function renderVerseCard($reference) {
    $safeRef = htmlspecialchars($reference);
    
    return <<<HTML
    <div style="
        background: #f0f9ff;
        border-left: 4px solid #0ea5e9;
        padding: 16px;
        border-radius: 12px;
        margin: 16px 0;
        font-family: 'Georgia', serif;
    ">
        <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 8px;">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#0ea5e9" stroke-width="2">
                <path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"></path>
                <path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"></path>
            </svg>
            <span style="font-weight: 700; color: #0369a1; font-size: 0.9rem;">
                {$safeRef}
            </span>
        </div>
        <div style="
            color: #0c4a6e;
            font-size: 1.05rem;
            line-height: 1.6;
            font-style: italic;
        ">
            <!-- Placeholder: integrar com API de Bíblia se disponível -->
            <em>"{$safeRef}"</em>
        </div>
    </div>
HTML;
}

/**
 * Extrair referências de versículos do conteúdo
 */
function extractVerseReferences($content) {
    $pattern = '/\[verso\s+([^]]+)\]/i';
    preg_match_all($pattern, $content, $matches);
    return $matches[1] ?? [];
}

// ==========================================
// COMPARTILHAMENTO
// ==========================================

/**
 * Gerar link de compartilhamento WhatsApp
 */
function getWhatsAppShareLink($devotionalId, $title, $preview = '') {
    $baseUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") 
               . "://" . $_SERVER['HTTP_HOST'];
    $devotionalUrl = $baseUrl . "/admin/devocionais.php?id=" . $devotionalId;
    
    $message = "📖 *{$title}*\n\n";
    if (!empty($preview)) {
        $message .= strip_tags($preview) . "\n\n";
    }
    $message .= "Leia o devocional completo: {$devotionalUrl}";
    
    return "https://wa.me/?text=" . urlencode($message);
}

// ==========================================
// NOTIFICAÇÕES
// ==========================================

/**
 * Enviar notificação de novo devocional para todos
 */
function notifyNewDevotional($pdo, $devotionalId, $title, $authorName) {
    // Verificar se sistema de notificações existe
    $tables = $pdo->query("SHOW TABLES LIKE 'notifications'")->fetchAll();
    if (empty($tables)) {
        return false;
    }
    
    // Buscar todos os usuários (exceto autor)
    $stmt = $pdo->prepare("SELECT id FROM users WHERE id != ?");
    $stmt->execute([$_SESSION['user_id'] ?? 1]);
    $users = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    // Criar notificação para cada usuário
    $notifStmt = $pdo->prepare("
        INSERT INTO notifications (user_id, type, title, message, link, created_at) 
        VALUES (?, 'devotional', ?, ?, ?, NOW())
    ");
    
    $notifTitle = '📖 Novo Devocional';
    $notifMessage = "{$authorName} publicou: {$title}";
    $notifLink = "/admin/devocionais.php?id={$devotionalId}";
    
    foreach ($users as $userId) {
        $notifStmt->execute([$userId, $notifTitle, $notifMessage, $notifLink]);
    }
    
    return true;
}
