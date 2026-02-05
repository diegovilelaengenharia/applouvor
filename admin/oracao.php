<?php
// admin/oracao.php - Redesign Premium com funcionalidade completa
require_once '../includes/auth.php';
require_once '../includes/db.php';
require_once '../includes/layout.php';

checkLogin();

$userId = $_SESSION['user_id'] ?? 1;
$userName = $_SESSION['user_name'] ?? 'Usuário';

// Verificar se tabelas existem
$tableExists = true;
try {
    $pdo->query("SELECT 1 FROM prayer_requests LIMIT 1");
} catch (PDOException $e) {
    $tableExists = false;
}

// --- LÓGICA DE POST (CRUD) ---
if ($tableExists && $_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        try {
            switch ($_POST['action']) {
                case 'create':
                    $stmt = $pdo->prepare("INSERT INTO prayer_requests (user_id, title, description, category, is_urgent, is_anonymous, created_at) VALUES (?, ?, ?, ?, ?, ?, NOW())");
                    $stmt->execute([
                        $userId,
                        $_POST['title'],
                        $_POST['description'],
                        $_POST['category'],
                        isset($_POST['is_urgent']) ? 1 : 0,
                        isset($_POST['is_anonymous']) ? 1 : 0
                    ]);
                    header('Location: oracao.php?success=created');
                    exit;

                case 'pray':
                    $check = $pdo->prepare("SELECT id FROM prayer_interactions WHERE prayer_id = ? AND user_id = ? AND type = 'pray'");
                    $check->execute([$_POST['prayer_id'], $userId]);
                    if (!$check->fetch()) {
                        $stmt = $pdo->prepare("INSERT INTO prayer_interactions (prayer_id, user_id, type, created_at) VALUES (?, ?, 'pray', NOW())");
                        $stmt->execute([$_POST['prayer_id'], $userId]);
                        $pdo->prepare("UPDATE prayer_requests SET prayer_count = prayer_count + 1 WHERE id = ?")->execute([$_POST['prayer_id']]);
                    }
                    header('Location: oracao.php#prayer-' . $_POST['prayer_id']);
                    exit;

                case 'comment':
                    $stmt = $pdo->prepare("INSERT INTO prayer_interactions (prayer_id, user_id, type, comment, created_at) VALUES (?, ?, 'comment', ?, NOW())");
                    $stmt->execute([$_POST['prayer_id'], $userId, $_POST['comment']]);
                    header('Location: oracao.php#prayer-' . $_POST['prayer_id']);
                    exit;

                case 'answered':
                    $stmt = $pdo->prepare("UPDATE prayer_requests SET is_answered = 1, answered_at = NOW() WHERE id = ? AND user_id = ?");
                    $stmt->execute([$_POST['prayer_id'], $userId]);
                    header('Location: oracao.php?success=answered');
                    exit;

                case 'delete':
                    $stmt = $pdo->prepare("DELETE FROM prayer_requests WHERE id = ? AND user_id = ?");
                    $stmt->execute([$_POST['prayer_id'], $userId]);
                    header('Location: oracao.php?success=deleted');
                    exit;
            }
        } catch (PDOException $e) {
            // Silently fail
        }
    }
}

// --- FILTROS ---
$filterCategory = $_GET['category'] ?? 'all';
$filterIntercession = $_GET['intercession'] ?? 'all'; // all, interceded, not_interceded
$showAnswered = isset($_GET['answered']) && $_GET['answered'] === '1';

// Buscar pedidos
$prayers = [];
if ($tableExists) {
    try {
        $sql = "SELECT p.*, u.name as author_name, u.avatar as author_avatar,
                (SELECT COUNT(*) FROM prayer_interactions WHERE prayer_id = p.id AND type = 'pray') as pray_count,
                (SELECT COUNT(*) FROM prayer_interactions WHERE prayer_id = p.id AND type = 'comment') as comment_count,
                IF(pi_user.id IS NOT NULL, 1, 0) as is_interceded
                FROM prayer_requests p 
                LEFT JOIN users u ON p.user_id = u.id
                LEFT JOIN prayer_interactions pi_user ON p.id = pi_user.prayer_id 
                                                       AND pi_user.user_id = ?
                                                       AND pi_user.type = 'pray'
                WHERE 1=1";
        $params = [$userId];

        if ($showAnswered) {
            $sql .= " AND p.is_answered = 1";
        } else {
            $sql .= " AND p.is_answered = 0";
        }

        if ($filterCategory !== 'all') {
            $sql .= " AND p.category = ?";
            $params[] = $filterCategory;
        }
        
        // Filtro de Intercessão
        if ($filterIntercession === 'interceded') {
            $sql .= " AND pi_user.id IS NOT NULL";
        } elseif ($filterIntercession === 'not_interceded') {
            $sql .= " AND pi_user.id IS NULL";
        }

        // Ordenação: Não intercedidos primeiro, depois urgentes, depois mais recentes
        $sql .= " ORDER BY is_interceded ASC, p.is_urgent DESC, p.created_at DESC";

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $prayers = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        $prayers = [];
    }
}

// Verificar se usuário já orou por cada pedido
function userPrayed($pdo, $prayerId, $userId) {
    $stmt = $pdo->prepare("SELECT id FROM prayer_interactions WHERE prayer_id = ? AND user_id = ? AND type = 'pray'");
    $stmt->execute([$prayerId, $userId]);
    return $stmt->fetch() ? true : false;
}

// Buscar comentários
function getComments($pdo, $prayerId) {
    $stmt = $pdo->prepare("SELECT pi.*, u.name, u.avatar FROM prayer_interactions pi 
                           LEFT JOIN users u ON pi.user_id = u.id 
                           WHERE pi.prayer_id = ? AND pi.type = 'comment' 
                           ORDER BY pi.created_at ASC");
    $stmt->execute([$prayerId]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

renderAppHeader('Pedidos de Oração');
?>

<style>
    /* Prayer Cards - Premium Design */
    .prayer-card {
        background: var(--bg-surface);
        border-radius: 14px;
        border: 1px solid var(--border-color);
        overflow: hidden;
        box-shadow: var(--shadow-sm);
        transition: transform 0.2s, box-shadow 0.2s;
    }
    .prayer-card:hover {
        box-shadow: 0 8px 25px rgba(0,0,0,0.08);
    }
    .prayer-card.urgent {
        border-left: 4px solid #dc2626;
    }
    .prayer-card.answered {
        background: linear-gradient(135deg, #f0fdf4 0%, #dcfce7 100%);
        border-color: #22c55e;
    }
    
    /* Header */
    .prayer-header {
        display: flex;
        align-items: center;
        gap: 10px;
        padding: 14px;
        border-bottom: 1px solid var(--border-color);
    }
    .prayer-avatar {
        width: 40px;
        height: 40px;
        border-radius: 50%;
        object-fit: cover;
        border: 2px solid white;
        box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    }
    .prayer-avatar-placeholder {
        width: 40px;
        height: 40px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-weight: 700;
        font-size: var(--font-body);
    }
    
    /* Content */
    .prayer-content {
        padding: 14px;
    }
    .prayer-title {
        font-size: var(--font-h3);
        font-weight: 700;
        color: var(--text-main);
        margin-bottom: 8px;
        display: flex;
        align-items: center;
        gap: 8px;
    }
    .prayer-description {
        color: var(--text-body);
        font-size: var(--font-body);
        line-height: 1.6;
    }
    
    /* Footer */
    .prayer-footer {
        padding: 12px 16px;
        background: #f8fafc;
        border-top: 1px solid var(--border-color);
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    
    /* Pray Button */
    .pray-btn {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 8px 16px;
        border-radius: 20px;
        font-size: var(--font-body-sm);
        font-weight: 600;
        cursor: pointer;
        transition: all 0.2s;
        border: none;
    }
    .pray-btn.not-prayed {
        background: linear-gradient(135deg, #376ac8, #2c55a0);
        color: white;
    }
    .pray-btn.prayed {
        background: #fef3c7;
        color: #d97706;
    }
    .pray-btn:hover {
        transform: scale(1.05);
    }
    
    /* Category Badges */
    .category-badge {
        padding: 4px 10px;
        border-radius: 12px;
        font-size: var(--font-caption);
        font-weight: 600;
        text-transform: uppercase;
    }
    .cat-health { background: #fee2e2; color: #dc2626; }
    .cat-family { background: #dbeafe; color: #2563eb; }
    .cat-work { background: #fef3c7; color: #d97706; }
    .cat-spiritual { background: #f5f3ff; color: #8b5cf6; }
    .cat-gratitude { background: #f0fdf4; color: #16a34a; }
    .cat-other { background: #f1f5f9; color: #64748b; }
    
    /* FAB */
    .fab-create {
        position: fixed;
        bottom: 90px;
        right: 20px;
        width: 56px;
        height: 56px;
        border-radius: 50%;
        background: linear-gradient(135deg, #376ac8, #2c55a0);
        color: white;
        border: none;
        box-shadow: 0 4px 20px rgba(55, 106, 200, 0.4);
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        z-index: 100;
        transition: transform 0.2s, box-shadow 0.2s;
    }
    .fab-create:hover {
        transform: scale(1.05);
        box-shadow: 0 6px 25px rgba(251, 191, 36, 0.5);
    }
    
    /* Filter Tabs */
    .filter-tabs {
        display: flex;
        gap: 4px;
        overflow-x: auto;
        padding-bottom: 8px;
        margin-bottom: 16px;
        scrollbar-width: none;
    }
    .filter-tab {
        padding: 8px 16px;
        border-radius: 20px;
        font-size: var(--font-body-sm);
        font-weight: 600;
        color: var(--text-muted);
        text-decoration: none;
        white-space: nowrap;
        transition: all 0.2s;
        background: var(--bg-surface);
        border: 1px solid var(--border-color);
    }
    .filter-tab:hover {
        background: var(--border-color);
    }
    .filter-tab.active {
        background: linear-gradient(135deg, #376ac8, #2c55a0);
        color: white;
        border-color: transparent;
    }
    
    /* Comments Section */
    .comments-section {
        display: none;
        padding: 16px;
        background: #f8fafc;
        border-top: 1px solid var(--border-color);
    }
    .comments-section.open {
        display: block;
    }
    .comment-item {
        display: flex;
        gap: 10px;
        padding: 10px 0;
        border-bottom: 1px solid var(--border-color);
    }
    .comment-item:last-child {
        border-bottom: none;
    }
    
    /* Collapsible Cards */
    .prayer-card.collapsed {
        cursor: pointer;
    }
    .prayer-card.collapsed .prayer-description {
        display: none;
    }
    .prayer-card.collapsed .comments-section {
        display: none !important;
    }
    .prayer-card.collapsed .prayer-footer {
        display: none;
    }
    
    /* Intercession States */
    .prayer-card.not-interceded {
        background: linear-gradient(to right, #ffffff, #fffbeb);
        border-left: 3px solid #f59e0b;
    }
    .prayer-card.interceded {
        opacity: 0.7;
        border: 1px dashed var(--border-color);
    }
    .prayer-card.interceded .prayer-title {
        color: var(--text-muted);
    }
    
    /* Filter Toolbar */
    .filter-toolbar {
        display: flex;
        gap: 10px;
        margin-bottom: 20px;
        align-items: center;
    }
    .filter-group {
        flex: 1;
        position: relative;
    }
    .filter-select {
        width: 100%;
        padding: 10px 12px 10px 36px;
        background-color: white;
        border: 1px solid #e2e8f0;
        border-radius: 12px;
        font-size: 0.9rem;
        font-weight: 500;
        color: #4a5568;
        appearance: none;
        cursor: pointer;
        transition: all 0.2s;
        box-shadow: 0 1px 2px rgba(0,0,0,0.05);
        background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='16' height='16' viewBox='0 0 24 24' fill='none' stroke='%23a0aec0' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpolyline points='6 9 12 15 18 9'%3E%3C/polyline%3E%3C/svg%3E");
        background-repeat: no-repeat;
        background-position: right 10px center;
    }
    .filter-select:focus {
        border-color: #f59e0b;
        box-shadow: 0 0 0 3px rgba(245, 158, 11, 0.1);
        outline: none;
    }
    .filter-icon {
        position: absolute;
        left: 10px;
        top: 50%;
        transform: translateY(-50%);
        pointer-events: none;
        color: #718096;
    }
    .btn-advanced-filter {
        width: 42px;
        height: 42px;
        display: flex;
        align-items: center;
        justify-content: center;
        background: white;
        border: 1px solid #e2e8f0;
        border-radius: 12px;
        color: #718096;
        cursor: pointer;
        transition: all 0.2s;
        box-shadow: 0 1px 2px rgba(0,0,0,0.05);
        flex-shrink: 0;
        position: relative;
    }
    .btn-advanced-filter.active {
        background: rgba(245, 158, 11, 0.1);
        color: #f59e0b;
        border-color: #f59e0b;
    }
    .btn-advanced-filter:hover {
        background: #f7fafc;
        transform: translateY(-1px);
    }
</style>

<link href="https://cdn.quilljs.com/1.3.6/quill.snow.css" rel="stylesheet">
<?php renderPageHeader('Mural de Oração', 'Louvor PIB Oliveira'); ?>

<div class="container" style="padding-top: 10px; max-width: 700px; margin: 0 auto;">
    
    <?php if (!$tableExists): ?>
    <!-- Setup Required Message -->
    <div style="text-align: center; padding: 40px 20px; background: linear-gradient(135deg, #ebf0fa, #d7e1f4); border-radius: 16px; margin-bottom: 20px;">
        <div style="background: white; width: 60px; height: 60px; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 16px; box-shadow: 0 4px 12px rgba(0,0,0,0.1);">
            <i data-lucide="database" style="color: #376ac8; width: 30px; height: 30px;"></i>
        </div>
        <h3 style="color: #1e3a8a; margin-bottom: 8px; font-weight: 700;">Configuração Necessária</h3>
        <p style="color: #1e40af; font-size: var(--font-body); max-width: 350px; margin: 0 auto 20px;">
            Para usar o Mural de Oração, é necessário criar as tabelas no banco de dados.
        </p>
        <a href="../setup_prayers.php" style="display: inline-flex; align-items: center; gap: 8px; background: linear-gradient(135deg, #376ac8, #2c55a0); color: white; padding: 12px 24px; border-radius: 24px; font-weight: 600; text-decoration: none; box-shadow: 0 4px 12px rgba(55, 106, 200, 0.3);">
            <i data-lucide="settings" style="width: 18px;"></i>
            Executar Setup
        </a>
    </div>
    <?php else: ?>
    
    <!-- Toolbar de Filtros Compacta -->
    <div class="filter-toolbar">
        <!-- Filtro Intercessão -->
        <div class="filter-group">
            <i class="filter-icon" data-lucide="heart" style="width: 16px; height: 16px;"></i>
            <select onchange="window.location.href='?intercession='+this.value+'&category=<?= $filterCategory ?><?= $showAnswered ? '&answered=1' : '' ?>'" class="filter-select">
                <option value="all" <?= $filterIntercession === 'all' ? 'selected' : '' ?>>Todos</option>
                <option value="not_interceded" <?= $filterIntercession === 'not_interceded' ? 'selected' : '' ?>>Não Intercedidos</option>
                <option value="interceded" <?= $filterIntercession === 'interceded' ? 'selected' : '' ?>>Intercedidos</option>
            </select>
        </div>
        
        <!-- Botão Filtros Avançados -->
        <button onclick="toggleAdvancedFilters()" class="btn-advanced-filter <?= ($filterCategory !== 'all' || $showAnswered) ? 'active' : '' ?>" title="Filtros Avançados">
            <i data-lucide="sliders-horizontal" style="width: 20px; height: 20px;"></i>
            <?php if ($filterCategory !== 'all' || $showAnswered): ?>
                <span style="position: absolute; top: 10px; right: 10px; width: 8px; height: 8px; background: #f59e0b; border-radius: 50%;"></span>
            <?php endif; ?>
        </button>
        
        <!-- Botão Limpar Filtros -->
        <?php if ($filterCategory !== 'all' || $showAnswered): ?>
        <button onclick="window.location.href='oracao.php'" class="btn-advanced-filter" style="color: #ef4444; border-color: #fecaca; background: #fef2f2;" title="Limpar Filtros">
            <i data-lucide="x" style="width: 20px; height: 20px;"></i>
        </button>
        <?php endif; ?>
    </div>
    
    <!-- Painel de Filtros Avançados -->
    
    <!-- Painel de Filtros Avançados -->
    <div id="advanced-filters-panel" style="display: none; background: var(--bg-surface); border: 1px solid var(--border-color); border-radius: 14px; padding: 16px; margin-bottom: 16px;">
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(140px, 1fr)); gap: 8px; margin-bottom: 12px;">
            <a href="?intercession=<?= $filterIntercession ?>&category=all<?= $showAnswered ? '&answered=1' : '' ?>" class="filter-tab <?= $filterCategory === 'all' ? 'active' : '' ?>">🙏 Todos</a>
            <a href="?intercession=<?= $filterIntercession ?>&category=health<?= $showAnswered ? '&answered=1' : '' ?>" class="filter-tab <?= $filterCategory === 'health' ? 'active' : '' ?>">❤️ Saúde</a>
            <a href="?intercession=<?= $filterIntercession ?>&category=family<?= $showAnswered ? '&answered=1' : '' ?>" class="filter-tab <?= $filterCategory === 'family' ? 'active' : '' ?>">👨‍👩‍👧 Família</a>
            <a href="?intercession=<?= $filterIntercession ?>&category=work<?= $showAnswered ? '&answered=1' : '' ?>" class="filter-tab <?= $filterCategory === 'work' ? 'active' : '' ?>">💼 Trabalho</a>
            <a href="?intercession=<?= $filterIntercession ?>&category=spiritual<?= $showAnswered ? '&answered=1' : '' ?>" class="filter-tab <?= $filterCategory === 'spiritual' ? 'active' : '' ?>">✨ Espiritual</a>
            <a href="?intercession=<?= $filterIntercession ?>&category=gratitude<?= $filterCategory === 'gratitude' ? 'active' : '' ?>" class="filter-tab <?= $filterCategory === 'gratitude' ? 'active' : '' ?>">🙌 Gratidão</a>
        </div>
        <div style="border-top: 1px solid var(--border-color); padding-top: 12px;">
            <a href="?intercession=<?= $filterIntercession ?>&category=<?= $filterCategory ?><?= $showAnswered ? '' : '&answered=1' ?>" style="
                display: inline-flex; align-items: center; gap: 6px;
                padding: 8px 14px; border-radius: 20px; font-size: var(--font-body-sm); font-weight: 600;
                text-decoration: none; color: <?= $showAnswered ? '#22c55e' : 'var(--text-muted)' ?>;
                background: <?= $showAnswered ? '#f0fdf4' : 'transparent' ?>;
                border: 1px solid <?= $showAnswered ? '#22c55e' : 'var(--border-color)' ?>;
            ">
                <i data-lucide="<?= $showAnswered ? 'check-circle' : 'sparkles' ?>" style="width: 14px;"></i>
                <?= $showAnswered ? 'Ver Pendentes' : 'Ver Respondidas 🎉' ?>
            </a>
        </div>
    </div>
    
    <!-- Feed de Pedidos -->
    <div style="display: flex; flex-direction: column; gap: 16px;">
        <?php if (count($prayers) > 0): ?>
            <?php foreach ($prayers as $prayer): 
                $comments = getComments($pdo, $prayer['id']);
                $hasPrayed = userPrayed($pdo, $prayer['id'], $userId);
                
                // Tempo relativo
                $createdAt = new DateTime($prayer['created_at']);
                $now = new DateTime();
                $diff = $now->diff($createdAt);
                
                if ($diff->y > 0) $timeAgo = $diff->y . ' ano(s)';
                elseif ($diff->m > 0) $timeAgo = $diff->m . ' mês(es)';
                elseif ($diff->d > 0) $timeAgo = $diff->d . ' dia(s)';
                elseif ($diff->h > 0) $timeAgo = $diff->h . 'h';
                elseif ($diff->i > 0) $timeAgo = $diff->i . 'min';
                else $timeAgo = 'agora';
                
                // Avatar
                $authorAvatar = null;
                $authorName = $prayer['is_anonymous'] ? 'Anônimo' : ($prayer['author_name'] ?? 'Membro');
                if (!$prayer['is_anonymous'] && !empty($prayer['author_avatar'])) {
                    $authorAvatar = $prayer['author_avatar'];
                    if (strpos($authorAvatar, 'http') === false && strpos($authorAvatar, 'assets') === false) {
                        $authorAvatar = '../assets/uploads/' . $authorAvatar;
                    }
                }
                
                // Category config
                $catConfig = [
                    'health' => ['icon' => '❤️', 'label' => 'Saúde', 'class' => 'cat-health'],
                    'family' => ['icon' => '👨‍👩‍👧', 'label' => 'Família', 'class' => 'cat-family'],
                    'work' => ['icon' => '💼', 'label' => 'Trabalho', 'class' => 'cat-work'],
                    'spiritual' => ['icon' => '✨', 'label' => 'Espiritual', 'class' => 'cat-spiritual'],
                    'gratitude' => ['icon' => '🙌', 'label' => 'Gratidão', 'class' => 'cat-gratitude'],
                    'other' => ['icon' => '🙏', 'label' => 'Outros', 'class' => 'cat-other']
                ];
                $cat = $catConfig[$prayer['category']] ?? $catConfig['other'];
                $intercessionClass = $prayer['is_interceded'] ? 'interceded' : 'not-interceded';
            ?>
            <div class="prayer-card collapsed <?= $intercessionClass ?> <?= $prayer['is_urgent'] ? 'urgent' : '' ?> <?= $prayer['is_answered'] ? 'answered' : '' ?>" 
                 id="prayer-<?= $prayer['id'] ?>" 
                 onclick="togglePrayerCard(<?= $prayer['id'] ?>, event)"
                 data-is-interceded="<?= $prayer['is_interceded'] ?>">
                <!-- Header -->
                <div class="prayer-header">
                    <?php if ($authorAvatar): ?>
                        <img src="<?= htmlspecialchars($authorAvatar) ?>" class="prayer-avatar" alt="">
                    <?php else: ?>
                        <div class="prayer-avatar-placeholder" style="background: linear-gradient(135deg, #fbbf24, #f59e0b);">
                            <?= $prayer['is_anonymous'] ? '?' : strtoupper(substr($authorName, 0, 1)) ?>
                        </div>
                    <?php endif; ?>
                    
                    <div style="flex: 1;">
                        <div style="font-weight: 700; color: var(--text-main); font-size: var(--font-body);"><?= htmlspecialchars($authorName) ?></div>
                        <div style="display: flex; align-items: center; gap: 8px; font-size: var(--font-caption); color: var(--text-muted);">
                            <span class="category-badge <?= $cat['class'] ?>"><?= $cat['icon'] ?> <?= $cat['label'] ?></span>
                            <span>• <?= $timeAgo ?></span>
                        </div>
                    </div>
                    
                    <?php if ($prayer['is_answered']): ?>
                        <div style="background: #22c55e; color: white; padding: 4px 10px; border-radius: 12px; font-size: var(--font-caption); font-weight: 700;">
                            ✓ RESPONDIDA
                        </div>
                    <?php elseif ($prayer['is_urgent']): ?>
                        <div style="background: #dc2626; color: white; padding: 4px 10px; border-radius: 12px; font-size: var(--font-caption); font-weight: 700;">
                            🔥 URGENTE
                        </div>
                    <?php endif; ?>
                </div>
                
                <!-- Content -->
                <div class="prayer-content">
                    <h3 class="prayer-title"><?= htmlspecialchars($prayer['title']) ?></h3>
                    <?php if (!empty($prayer['description'])): ?>
                        <p class="prayer-description"><?= nl2br(htmlspecialchars($prayer['description'])) ?></p>
                    <?php endif; ?>
                </div>
                
                <!-- Footer -->
                <div class="prayer-footer">
                    <div style="display: flex; align-items: center; gap: 10px;">
                        <button onclick="toggleIntercessionStatus(<?= $prayer['id'] ?>, this)" class="pray-btn <?= $prayer['is_interceded'] ? 'prayed' : 'not-prayed' ?>" id="intercession-btn-<?= $prayer['id'] ?>">
                            <i data-lucide="heart" style="width: 16px;"></i>
                            <span><?= $prayer['is_interceded'] ? 'Intercedi' : 'Interceder' ?></span> (<?= $prayer['pray_count'] ?>)
                        </button>
                        
                        <button onclick="toggleComments('comments-<?= $prayer['id'] ?>')" style="background: none; border: none; color: var(--text-muted); font-size: var(--font-body-sm); font-weight: 600; cursor: pointer; display: flex; align-items: center; gap: 4px;">
                            <i data-lucide="message-circle" style="width: 16px;"></i>
                            <?= count($comments) ?>
                        </button>
                    </div>
                    
                    <?php if ($prayer['user_id'] == $userId && !$prayer['is_answered']): ?>
                    <form method="POST" style="margin: 0;">
                        <input type="hidden" name="action" value="answered">
                        <input type="hidden" name="prayer_id" value="<?= $prayer['id'] ?>">
                        <button type="submit" style="background: #dcfce7; color: #16a34a; border: none; padding: 6px 12px; border-radius: 12px; font-size: var(--font-body-sm); font-weight: 600; cursor: pointer;">
                            ✓ Marcar respondida
                        </button>
                    </form>
                    <?php endif; ?>
                </div>
                
                <!-- Comments Section -->
                <div id="comments-<?= $prayer['id'] ?>" class="comments-section">
                    <?php foreach ($comments as $comment): 
                        $commentAvatar = !empty($comment['avatar']) ? $comment['avatar'] : null;
                        if ($commentAvatar && strpos($commentAvatar, 'http') === false) {
                            $commentAvatar = '../assets/uploads/' . $commentAvatar;
                        }
                    ?>
                    <div class="comment-item">
                        <?php if ($commentAvatar): ?>
                            <img src="<?= htmlspecialchars($commentAvatar) ?>" style="width: 32px; height: 32px; border-radius: 50%; object-fit: cover;" alt="">
                        <?php else: ?>
                            <div style="width: 32px; height: 32px; border-radius: 50%; background: linear-gradient(135deg, #a8edea, #fed6e3); display: flex; align-items: center; justify-content: center; font-size: var(--font-caption); font-weight: 700; color: #666;">
                                <?= strtoupper(substr($comment['name'] ?? 'U', 0, 1)) ?>
                            </div>
                        <?php endif; ?>
                        <div style="flex: 1;">
                            <span style="font-weight: 600; font-size: var(--font-body-sm); color: var(--text-main);"><?= htmlspecialchars($comment['name'] ?? 'Membro') ?></span>
                            <p style="margin: 4px 0 0; font-size: var(--font-body); color: var(--text-body);"><?= nl2br(htmlspecialchars($comment['comment'])) ?></p>
                        </div>
                    </div>
                    <?php endforeach; ?>
                    
                    <!-- Comment Form -->
                    <form method="POST" style="display: flex; gap: 8px; margin-top: 12px;">
                        <input type="hidden" name="action" value="comment">
                        <input type="hidden" name="prayer_id" value="<?= $prayer['id'] ?>">
                        <input type="text" name="comment" placeholder="Deixe uma palavra..." required style="flex: 1; padding: 10px 14px; border: 1px solid var(--border-color); border-radius: 24px; font-size: var(--font-body); outline: none;">
                        <button type="submit" style="background: linear-gradient(135deg, #fbbf24, #f59e0b); color: white; border: none; padding: 10px 16px; border-radius: 24px; font-weight: 600; cursor: pointer;">
                            <i data-lucide="send" style="width: 16px;"></i>
                        </button>
                    </form>
                </div>
            </div>
            <?php endforeach; ?>
        <?php else: ?>
            <!-- Empty State -->
            <div style="text-align: center; padding: 60px 20px;">
                <div style="background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%); width: 80px; height: 80px; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 20px;">
                    <i data-lucide="heart-handshake" style="color: #f59e0b; width: 40px; height: 40px;"></i>
                </div>
                <h3 style="color: var(--text-main); margin-bottom: 8px;"><?= $showAnswered ? 'Nenhuma oração respondida' : 'Nenhum pedido ainda' ?></h3>
                <p style="color: var(--text-muted); font-size: var(--font-body); max-width: 300px; margin: 0 auto 20px;">
                    <?= $showAnswered ? 'As orações respondidas aparecerão aqui.' : 'Seja o primeiro a compartilhar um pedido de oração!' ?>
                </p>
                <?php if (!$showAnswered): ?>
                <button onclick="openCreateModal()" style="background: linear-gradient(135deg, #fbbf24, #f59e0b); color: white; border: none; padding: 12px 24px; border-radius: 24px; font-weight: 600; cursor: pointer;">
                    <i data-lucide="plus" style="width: 18px; display: inline-block; vertical-align: middle; margin-right: 6px;"></i>
                    Novo Pedido
                </button>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
    <?php endif; ?>
    
    <div style="height: 100px;"></div>
</div>

<!-- FAB Button -->
<button onclick="openCreateModal()" class="fab-create">
    <i data-lucide="plus" style="width: 28px; height: 28px;"></i>
</button>

<!-- Modal Create -->
<div id="prayerModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; z-index: 1000;">
    <div onclick="closeModal()" style="position: absolute; width: 100%; height: 100%; background: rgba(0,0,0,0.5); backdrop-filter: blur(4px);"></div>
    
    <div style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); width: 90%; max-width: 500px; background: var(--bg-surface); border-radius: 24px; padding: 24px; box-shadow: 0 20px 60px rgba(0,0,0,0.2); max-height: 90vh; overflow-y: auto;">
        
        <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 20px;">
            <h2 style="margin: 0; font-size: var(--font-h2); font-weight: 800; color: var(--text-main);">🙏 Novo Pedido de Oração</h2>
            <button onclick="closeModal()" style="background: none; border: none; color: var(--text-muted); cursor: pointer; padding: 8px;">
                <i data-lucide="x" style="width: 20px;"></i>
            </button>
        </div>
        
        <form method="POST" id="prayerForm" onsubmit="return preparePrayerSubmit()">
            <input type="hidden" name="action" value="create">
            <input type="hidden" name="description" id="hiddenDescription">
            
            <!-- Título -->
            <div style="margin-bottom: 16px;">
                <label style="display: block; font-weight: 700; color: var(--text-main); margin-bottom: 6px; font-size: var(--font-body);">Título do Pedido</label>
                <input type="text" name="title" id="prayerTitle" required placeholder="Ex: Oração pela saúde do meu pai" style="width: 100%; padding: 12px 14px; border: 1px solid var(--border-color); border-radius: 12px; font-size: var(--font-body); outline: none; background: var(--bg-body);">
            </div>
            
            <!-- Editor de Descrição -->
            <div style="margin-bottom: 16px;">
                <label style="display: block; font-weight: 700; color: var(--text-main); margin-bottom: 6px; font-size: var(--font-body);">Descrição (opcional)</label>
               <div id="prayerEditor" style="min-height: 120px; background: white; border-radius: 12px;"></div>
            </div>
            
            <!-- Categoria -->
            <div style="margin-bottom: 16px;">
                <label style="display: block; font-weight: 700; color: var(--text-main); margin-bottom: 8px; font-size: var(--font-body);">Categoria</label>
                <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 8px;">
                    <label style="display: flex; flex-direction: column; align-items: center; gap: 4px; padding: 12px 8px; border: 2px solid var(--border-color); border-radius: 12px; cursor: pointer; transition: all 0.2s;" class="cat-option">
                        <input type="radio" name="category" value="health" style="display: none;">
                        <span style="font-size: var(--font-display);">❤️</span>
                        <span style="font-size: var(--font-caption); font-weight: 600;">Saúde</span>
                    </label>
                    <label style="display: flex; flex-direction: column; align-items: center; gap: 4px; padding: 12px 8px; border: 2px solid var(--border-color); border-radius: 12px; cursor: pointer; transition: all 0.2s;" class="cat-option">
                        <input type="radio" name="category" value="family" style="display: none;">
                        <span style="font-size: var(--font-display);">👨‍👩‍👧</span>
                        <span style="font-size: var(--font-caption); font-weight: 600;">Família</span>
                    </label>
                    <label style="display: flex; flex-direction: column; align-items: center; gap: 4px; padding: 12px 8px; border: 2px solid var(--border-color); border-radius: 12px; cursor: pointer; transition: all 0.2s;" class="cat-option">
                        <input type="radio" name="category" value="work" style="display: none;">
                        <span style="font-size: var(--font-display);">💼</span>
                        <span style="font-size: var(--font-caption); font-weight: 600;">Trabalho</span>
                    </label>
                    <label style="display: flex; flex-direction: column; align-items: center; gap: 4px; padding: 12px 8px; border: 2px solid var(--border-color); border-radius: 12px; cursor: pointer; transition: all 0.2s;" class="cat-option">
                        <input type="radio" name="category" value="spiritual" style="display: none;">
                        <span style="font-size: var(--font-display);">✨</span>
                        <span style="font-size: var(--font-caption); font-weight: 600;">Espiritual</span>
                    </label>
                    <label style="display: flex; flex-direction: column; align-items: center; gap: 4px; padding: 12px 8px; border: 2px solid var(--border-color); border-radius: 12px; cursor: pointer; transition: all 0.2s;" class="cat-option">
                        <input type="radio" name="category" value="gratitude" style="display: none;">
                        <span style="font-size: var(--font-display);">🙌</span>
                        <span style="font-size: var(--font-caption); font-weight: 600;">Gratidão</span>
                    </label>
                    <label style="display: flex; flex-direction: column; align-items: center; gap: 4px; padding: 12px 8px; border: 2px solid var(--border-color); border-radius: 12px; cursor: pointer; transition: all 0.2s;" class="cat-option">
                        <input type="radio" name="category" value="other" checked style="display: none;">
                        <span style="font-size: var(--font-display);">🙏</span>
                        <span style="font-size: var(--font-caption); font-weight: 600;">Outros</span>
                    </label>
                </div>
            </div>
            
            <!-- Options -->
            <div style="margin-bottom: 20px; display: flex; gap: 16px; flex-wrap: wrap;">
                <label style="display: flex; align-items: center; gap: 8px; cursor: pointer;">
                    <input type="checkbox" name="is_urgent" style="width: 18px; height: 18px; accent-color: #dc2626;">
                    <span style="font-size: var(--font-body); font-weight: 600; color: var(--text-main);">🔥 Urgente</span>
                </label>
                <label style="display: flex; align-items: center; gap: 8px; cursor: pointer;">
                    <input type="checkbox" name="is_anonymous" style="width: 18px; height: 18px; accent-color: #64748b;">
                    <span style="font-size: var(--font-body); font-weight: 600; color: var(--text-main);">🔒 Anônimo</span>
                </label>
            </div>
            
            <!-- Buttons -->
            <div style="display: flex; gap: 12px;">
                <button type="button" onclick="closeModal()" style="flex: 1; padding: 14px; border-radius: 12px; border: 1px solid var(--border-color); background: var(--bg-surface); font-weight: 600; cursor: pointer; color: var(--text-muted);">
                    Cancelar
                </button>
                <button type="submit" style="flex: 2; padding: 14px; border-radius: 12px; border: none; background: linear-gradient(135deg, #fbbf24, #f59e0b); color: white; font-weight: 700; cursor: pointer;">
                    Enviar Pedido
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    // Category selection visual
    document.querySelectorAll('.cat-option input').forEach(input => {
        input.addEventListener('change', function() {
            document.querySelectorAll('.cat-option').forEach(opt => {
                opt.style.borderColor = 'var(--border-color)';
                opt.style.background = 'transparent';
            });
            this.parentElement.style.borderColor = '#fbbf24';
            this.parentElement.style.background = '#fef3c7';
        });
    });
    
    // Initialize first selection
    document.querySelector('.cat-option input:checked').parentElement.style.borderColor = '#fbbf24';
    document.querySelector('.cat-option input:checked').parentElement.style.background = '#fef3c7';
    
    function openCreateModal() {
        document.getElementById('prayerModal').style.display = 'block';
    }
    
    function closeModal() {
        document.getElementById('prayerModal').style.display = 'none';
    }
    
    function toggleComments(id) {
        const section = document.getElementById(id);
        section.classList.toggle('open');
    }
    
    // Toggle prayer card expansion/collapse
    function togglePrayerCard(id, event) {
        // Evitar toggle se clicar em botões, links ou inputs
        if (event.target.closest('button, a, input, textarea, select')) {
            return;
        }
        
        const card = document.getElementById('prayer-' + id);
        card.classList.toggle('collapsed');
        
        // Se expandiu, scroll suave até o card
        if (!card.classList.contains('collapsed')) {
            setTimeout(() => {
                card.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
            }, 100);
        }
    }
    
    // Toggle intercession status (AJAX)
    function toggleIntercessionStatus(prayerId, btn) {
        const card = document.getElementById('prayer-' + prayerId);
        const btnSpan = btn.querySelector('span');
        const counter = btn.textContent.match(/\((\d+)\)/)[1];
        
        // Feedback visual imediato
        btn.disabled = true;
        btn.style.opacity = '0.7';
        
        fetch('../api/toggle_intercession.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: 'prayer_id=' + prayerId
        })
        .then(response => response.json())
        .then(data => {
            btn.disabled = false;
            btn.style.opacity = '1';
            
            if (data.success) {
                const currentCount = parseInt(counter);
                const newCount = data.is_interceded ? currentCount + 1 : currentCount - 1;
                
                if (data.is_interceded) {
                    // Marcou como intercedido
                    card.classList.remove('not-interceded');
                    card.classList.add('interceded');
                    card.dataset.isInterceded = '1';
                    btn.classList.remove('not-prayed');
                    btn.classList.add('prayed');
                    btnSpan.textContent = 'Intercedi';
                } else {
                    // Desmarcou
                    card.classList.remove('interceded');
                    card.classList.add('not-interceded');
                    card.dataset.isInterceded = '0';
                    btn.classList.remove('prayed');
                    btn.classList.add('not-prayed');
                    btnSpan.textContent = 'Interceder';
                }
                
                // Atualizar contador
                btn.innerHTML = btn.innerHTML.replace(/\(\d+\)/, `(${newCount})`);
                
                // Recriar ícones Lucide
                if (window.lucide) {
                    lucide.createIcons();
                }
            }
        })
        .catch(error => {
            btn.disabled = false;
            btn.style.opacity = '1';
            console.error('Erro ao atualizar intercessão:', error);
        });
    }
    
    // Toggle advanced filters panel
    function toggleAdvancedFilters() {
        const panel = document.getElementById('advanced-filters-panel');
        panel.style.display = panel.style.display === 'none' ? 'block' : 'none';
    }
</script>

<script src="https://cdn.quilljs.com/1.3.6/quill.js"></script>
<script>
    // Inicializar Quill Editor para Oração
    var prayerQuill = new Quill('#prayerEditor', {
        theme: 'snow',
        modules: {
            toolbar: [
                ['bold', 'italic', 'underline'],
                [{ 'list': 'ordered'}, { 'list': 'bullet' }],
                ['link']
            ]
        },
        placeholder: 'Compartilhe mais detalhes se desejar...'
    });
    
    // Preparar submit do formulário de oração
    function preparePrayerSubmit() {
        document.getElementById('hiddenDescription').value = prayerQuill.root.innerHTML;
        return true;
    }
    
    // Limpar editor ao abrir/fechar modal
    const originalOpenModal = openCreateModal;
    openCreateModal = function() {
        originalOpenModal();
        prayerQuill.setContents([]);
        document.getElementById('prayerTitle').value = '';
    };
</script>

<?php renderAppFooter(); ?>