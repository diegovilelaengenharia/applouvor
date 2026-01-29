<?php
// admin/avisos.php - Redesign com visual roxo moderno
require_once '../includes/auth.php';
require_once '../includes/db.php';
require_once '../includes/layout.php';
require_once '../includes/notification_system.php';

checkLogin();

$userId = $_SESSION['user_id'] ?? 1;
$isAdmin = ($_SESSION['user_role'] ?? '') === 'admin';

// --- LÓGICA DE POST (CRUD) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'create':
                $stmt = $pdo->prepare("INSERT INTO avisos (title, message, priority, type, target_audience, expires_at, created_by, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, NOW())");
                $stmt->execute([
                    $_POST['title'],
                    $_POST['message'],
                    $_POST['priority'],
                    $_POST['type'],
                    $_POST['target_audience'],
                    !empty($_POST['expires_at']) ? $_POST['expires_at'] : NULL,
                    $userId
                ]);

                // Notificar usuários
                $avisoId = $pdo->lastInsertId();
                if ($avisoId) {
                    try {
                        $notificationSystem = new NotificationSystem($pdo);
                        $targetAudience = $_POST['target_audience'];
                        $priority = $_POST['priority'];
                        $title = $_POST['title'];
                        $notifType = $priority === 'urgent' ? 'aviso_urgent' : 'new_aviso';
                        
                        $usersQuery = "SELECT id FROM users WHERE status = 'active'";
                        $usersParams = [];
                        
                        if ($targetAudience === 'admins') {
                            $usersQuery .= " AND role = 'admin'";
                        } elseif ($targetAudience === 'team') {
                            $usersQuery .= " AND role IN ('admin', 'member')";
                        }
                        
                        $stmtUsers = $pdo->query($usersQuery);
                        $users = $stmtUsers->fetchAll(PDO::FETCH_COLUMN);
                        
                        foreach ($users as $uid) {
                            if ($uid == $userId) continue;
                            
                            $notificationSystem->createNotification(
                                $uid,
                                $notifType,
                                "Novo Aviso: $title",
                                strip_tags($_POST['message']),
                                "avisos.php"
                            );
                        }
                    } catch (Exception $e) {
                         error_log("Erro ao enviar notificações: " . $e->getMessage());
                    }
                }

                header('Location: avisos.php?success=created');
                exit;

            case 'update':
                $stmt = $pdo->prepare("UPDATE avisos SET title = ?, message = ?, priority = ?, type = ?, target_audience = ?, expires_at = ? WHERE id = ?");
                $stmt->execute([
                    $_POST['title'],
                    $_POST['message'],
                    $_POST['priority'],
                    $_POST['type'],
                    $_POST['target_audience'],
                    !empty($_POST['expires_at']) ? $_POST['expires_at'] : NULL,
                    $_POST['id']
                ]);
                header('Location: avisos.php?success=updated');
                exit;

            case 'delete':
                $stmt = $pdo->prepare("DELETE FROM avisos WHERE id = ?");
                $stmt->execute([$_POST['id']]);
                header('Location: avisos.php?success=deleted');
                exit;

            case 'archive':
                $stmt = $pdo->prepare("UPDATE avisos SET archived_at = NOW() WHERE id = ?");
                $stmt->execute([$_POST['id']]);
                header('Location: avisos.php?success=archived');
                exit;

            case 'unarchive':
                $stmt = $pdo->prepare("UPDATE avisos SET archived_at = NULL WHERE id = ?");
                $stmt->execute([$_POST['id']]);
                header('Location: avisos.php?success=unarchived');
                exit;
        }
    }
}

// --- FILTROS E BUSCA ---
$showArchived = isset($_GET['archived']) && $_GET['archived'] === '1';
$showHistory = isset($_GET['history']) && $_GET['history'] === '1';
$filterTag = $_GET['tag'] ?? 'all';
$filterType = $_GET['type'] ?? 'all';
$search = $_GET['search'] ?? '';

// Buscar todas as tags
$allTags = [];
try {
    $stmt = $pdo->query("SELECT DISTINCT name FROM aviso_tags ORDER BY name");
    $allTags = $stmt->fetchAll(PDO::FETCH_COLUMN);
} catch (Exception $e) {}

// Query de avisos
$query = "SELECT a.*, u.name as author_name, u.avatar as author_avatar 
          FROM avisos a 
          LEFT JOIN users u ON a.created_by = u.id 
          WHERE 1=1";

$params = [];

if ($showArchived) {
    $query .= " AND a.archived_at IS NOT NULL";
} else {
    $query .= " AND a.archived_at IS NULL";
}

if (!$showHistory) {
    $query .= " AND (a.expires_at IS NULL OR a.expires_at >= CURDATE())";
}

if ($filterType !== 'all') {
    $query .= " AND a.type = ?";
    $params[] = $filterType;
}

if (!empty($search)) {
    $query .= " AND (a.title LIKE ? OR a.message LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

$query .= " ORDER BY a.priority='urgent' DESC, a.created_at DESC";

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$avisos = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Buscar tags de cada aviso
foreach ($avisos as &$aviso) {
    $stmt = $pdo->prepare("
        SELECT t.name 
        FROM aviso_tags t
        INNER JOIN aviso_tag_relations atr ON t.id = atr.tag_id
        WHERE atr.aviso_id = ?
    ");
    $stmt->execute([$aviso['id']]);
    $aviso['tags'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

renderAppHeader('Avisos');
?>

<!-- Quill CSS -->
<link href="https://cdn.quilljs.com/1.3.6/quill.snow.css" rel="stylesheet">

<style>
    /* === PALETA ROXA MODERNA === */
    :root {
        --purple-primary: #7c3aed;
        --purple-light: #8b5cf6;
        --purple-bg: #f5f3ff;
        --purple-border: #e9d5ff;
    }


    /* === HERO SECTION SIMPLIFICADO === */
    .hero-avisos {
        background: #f5f3ff; /* Roxo muito claro */
        padding: 20px;
        margin: -20px -20px 20px -20px;
        border-radius: 0 0 16px 16px;
        border-bottom: 2px solid #e9d5ff;
    }

    .hero-content {
        display: flex;
        align-items: center;
        gap: 16px;
        max-width: 800px;
        margin: 0 auto;
    }

    .hero-icon {
        width: 48px;
        height: 48px;
        background: linear-gradient(135deg, #a78bfa 0%, #c4b5fd 100%);
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
    }

    .hero-icon i {
        color: white;
        width: 24px;
        height: 24px;
    }

    .hero-text {
        flex: 1;
    }

    .hero-title {
        font-size: 1.25rem;
        font-weight: 700;
        color: #5b21b6; /* Roxo escuro */
        margin: 0 0 4px;
        letter-spacing: -0.01em;
    }

    .hero-subtitle {
        font-size: 0.875rem;
        color: #7c3aed; /* Roxo médio */
        margin: 0;
        line-height: 1.4;
    }


    /* === SEARCH BAR === */
    .search-container {
        margin-bottom: 20px;
    }

    .search-wrapper {
        position: relative;
        width: 100%;
    }

    .search-icon {
        position: absolute;
        left: 16px;
        top: 50%;
        transform: translateY(-50%);
        width: 20px;
        color: var(--purple-primary);
        opacity: 0.6;
    }

    .search-input {
        width: 100%;
        padding: 14px 16px 14px 48px;
        border: 2px solid var(--purple-border);
        border-radius: 12px;
        font-size: 0.95rem;
        background: white;
        outline: none;
        color: var(--text-main);
        transition: all 0.2s;
    }

    .search-input:focus {
        border-color: var(--purple-primary);
        box-shadow: 0 0 0 4px rgba(124, 58, 237, 0.1);
    }

    .search-input::placeholder {
        color: var(--text-muted);
    }

    /* === FILTER TABS === */
    .filter-tabs {
        display: flex;
        gap: 8px;
        overflow-x: auto;
        padding-bottom: 4px;
        margin-bottom: 24px;
        -webkit-overflow-scrolling: touch;
        scrollbar-width: none;
    }

    .filter-tabs::-webkit-scrollbar {
        display: none;
    }

    .filter-tab {
        padding: 10px 20px;
        border-radius: 10px;
        text-decoration: none;
        font-size: 0.9rem;
        font-weight: 600;
        white-space: nowrap;
        transition: all 0.2s;
        background: white;
        color: var(--text-main);
        border: 2px solid var(--border-color);
    }

    .filter-tab:hover {
        border-color: var(--purple-light);
        color: var(--purple-primary);
        transform: translateY(-1px);
    }

    .filter-tab.active {
        background: linear-gradient(135deg, var(--purple-primary), var(--purple-light));
        color: white;
        border-color: transparent;
        box-shadow: 0 4px 12px rgba(124, 58, 237, 0.3);
    }

    /* === AVISO CARDS === */
    .aviso-card {
        background: white;
        border-radius: 16px;
        border: 1px solid var(--border-color);
        overflow: hidden;
        box-shadow: 0 2px 8px rgba(0,0,0,0.04);
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        position: relative;
        margin-bottom: 16px;
    }

    .aviso-card::before {
        content: '';
        position: absolute;
        left: 0;
        top: 0;
        bottom: 0;
        width: 4px;
        background: var(--priority-color, var(--purple-primary));
        transition: width 0.2s;
    }

    .aviso-card:hover {
        box-shadow: 0 8px 24px rgba(0,0,0,0.08);
        transform: translateY(-2px);
    }

    .aviso-card:hover::before {
        width: 6px;
    }

    /* Priority Colors */
    .aviso-card.urgent::before { background: #ef4444; }
    .aviso-card.important::before { background: #f59e0b; }
    .aviso-card.normal::before { background: var(--purple-primary); }

    /* Header do Card */
    .aviso-header {
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 16px;
        border-bottom: 1px solid var(--border-color);
    }

    .aviso-avatar {
        width: 40px;
        height: 40px;
        border-radius: 50%;
        object-fit: cover;
        border: 2px solid var(--purple-bg);
        box-shadow: 0 2px 8px rgba(124, 58, 237, 0.15);
    }

    .aviso-avatar-placeholder {
        width: 40px;
        height: 40px;
        border-radius: 50%;
        background: linear-gradient(135deg, var(--purple-primary), var(--purple-light));
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-weight: 700;
        font-size: 0.9rem;
        box-shadow: 0 2px 8px rgba(124, 58, 237, 0.15);
    }

    .aviso-author-info {
        flex: 1;
        min-width: 0;
    }

    .aviso-author-name {
        font-weight: 600;
        color: var(--text-main);
        font-size: 0.9rem;
        margin-bottom: 2px;
    }

    .aviso-meta {
        font-size: 0.8rem;
        color: var(--text-muted);
        display: flex;
        align-items: center;
        gap: 8px;
    }

    /* Body do Card */
    .aviso-body {
        padding: 16px;
    }

    .aviso-title {
        font-size: 1.1rem;
        font-weight: 700;
        color: var(--text-main);
        margin-bottom: 8px;
        line-height: 1.4;
    }

    .aviso-message {
        font-size: 0.95rem;
        color: var(--text-muted);
        line-height: 1.6;
        margin-bottom: 12px;
    }

    /* Tags */
    .aviso-tags {
        display: flex;
        flex-wrap: wrap;
        gap: 6px;
        margin-top: 12px;
    }

    .aviso-tag {
        padding: 4px 12px;
        border-radius: 6px;
        font-size: 0.75rem;
        font-weight: 600;
        background: var(--purple-bg);
        color: var(--purple-primary);
        border: 1px solid var(--purple-border);
    }

    /* Priority Badge */
    .priority-badge {
        padding: 4px 10px;
        border-radius: 6px;
        font-size: 0.75rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        display: inline-flex;
        align-items: center;
        gap: 4px;
    }

    .priority-badge.urgent {
        background: #fee2e2;
        color: #dc2626;
    }

    .priority-badge.important {
        background: #fef3c7;
        color: #d97706;
    }

    .priority-badge.normal {
        background: var(--purple-bg);
        color: var(--purple-primary);
    }

    /* Actions */
    .aviso-actions {
        padding: 12px 16px;
        border-top: 1px solid var(--border-color);
        display: flex;
        justify-content: space-between;
        align-items: center;
        background: var(--bg-body);
    }

    .action-btn {
        padding: 8px 16px;
        border-radius: 8px;
        font-size: 0.85rem;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.2s;
        border: 1px solid var(--border-color);
        background: white;
        color: var(--text-main);
        display: inline-flex;
        align-items: center;
        gap: 6px;
    }

    .action-btn:hover {
        background: var(--purple-primary);
        color: white;
        border-color: var(--purple-primary);
        transform: translateY(-1px);
        box-shadow: 0 4px 8px rgba(124, 58, 237, 0.2);
    }

    .action-btn.danger:hover {
        background: #ef4444;
        border-color: #ef4444;
    }

    /* Dropdown */
    .aviso-dropdown {
        position: relative;
    }

    .dropdown-toggle {
        width: 32px;
        height: 32px;
        border-radius: 8px;
        border: none;
        background: transparent;
        color: var(--text-muted);
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        transition: all 0.2s;
    }

    .dropdown-toggle:hover {
        background: var(--purple-bg);
        color: var(--purple-primary);
    }

    .dropdown-menu {
        display: none;
        position: absolute;
        top: 100%;
        right: 0;
        background: white;
        border-radius: 12px;
        box-shadow: 0 8px 24px rgba(0,0,0,0.12);
        border: 1px solid var(--border-color);
        min-width: 180px;
        z-index: 100;
        overflow: hidden;
        margin-top: 4px;
    }

    .dropdown-menu.show {
        display: block;
        animation: fadeInDown 0.2s ease-out;
    }

    .dropdown-item {
        padding: 12px 16px;
        font-size: 0.9rem;
        color: var(--text-main);
        cursor: pointer;
        transition: all 0.2s;
        display: flex;
        align-items: center;
        gap: 10px;
        border: none;
        background: none;
        width: 100%;
        text-align: left;
    }

    .dropdown-item:hover {
        background: var(--purple-bg);
        color: var(--purple-primary);
    }

    .dropdown-item.danger:hover {
        background: #fee2e2;
        color: #dc2626;
    }

    /* FAB (Floating Action Button) */
    .fab {
        position: fixed;
        bottom: 24px;
        right: 24px;
        width: 56px;
        height: 56px;
        background: linear-gradient(135deg, var(--purple-primary), var(--purple-light));
        border-radius: 50%;
        border: none;
        color: white;
        cursor: pointer;
        box-shadow: 0 8px 24px rgba(124, 58, 237, 0.4);
        display: flex;
        align-items: center;
        justify-content: center;
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        z-index: 50;
    }

    .fab:hover {
        transform: scale(1.1) rotate(90deg);
        box-shadow: 0 12px 32px rgba(124, 58, 237, 0.5);
    }

    .fab:active {
        transform: scale(0.95);
    }

    .fab i {
        width: 24px;
        height: 24px;
    }

    /* Empty State */
    .empty-state {
        text-align: center;
        padding: 60px 20px;
        color: var(--text-muted);
    }

    .empty-icon {
        width: 64px;
        height: 64px;
        margin: 0 auto 16px;
        opacity: 0.3;
        color: var(--purple-primary);
    }

    .empty-title {
        font-size: 1.1rem;
        font-weight: 600;
        color: var(--text-main);
        margin-bottom: 8px;
    }

    .empty-message {
        font-size: 0.9rem;
        color: var(--text-muted);
    }

    /* Modal */
    .modal {
        display: none;
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0,0,0,0.5);
        backdrop-filter: blur(4px);
        z-index: 1000;
        align-items: center;
        justify-content: center;
        padding: 20px;
    }

    .modal.show {
        display: flex;
    }

    .modal-content {
        background: white;
        border-radius: 20px;
        width: 100%;
        max-width: 600px;
        max-height: 90vh;
        overflow-y: auto;
        box-shadow: 0 20px 60px rgba(0,0,0,0.3);
    }

    .modal-header {
        background: linear-gradient(135deg, var(--purple-primary), var(--purple-light));
        color: white;
        padding: 24px;
        border-radius: 20px 20px 0 0;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    .modal-title {
        font-size: 1.3rem;
        font-weight: 700;
        margin: 0;
    }

    .modal-close {
        width: 32px;
        height: 32px;
        border-radius: 50%;
        background: rgba(255,255,255,0.2);
        border: none;
        color: white;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        transition: all 0.2s;
    }

    .modal-close:hover {
        background: rgba(255,255,255,0.3);
    }

    .modal-body {
        padding: 24px;
    }

    .form-group {
        margin-bottom: 20px;
    }

    .form-label {
        display: block;
        font-weight: 600;
        color: var(--text-main);
        margin-bottom: 8px;
        font-size: 0.9rem;
    }

    .form-input, .form-select, .form-textarea {
        width: 100%;
        padding: 12px;
        border: 2px solid var(--border-color);
        border-radius: 10px;
        font-size: 0.95rem;
        font-family: inherit;
        outline: none;
        transition: all 0.2s;
    }

    .form-input:focus, .form-select:focus, .form-textarea:focus {
        border-color: var(--purple-primary);
        box-shadow: 0 0 0 4px rgba(124, 58, 237, 0.1);
    }

    .form-textarea {
        min-height: 120px;
        resize: vertical;
    }

    .modal-footer {
        padding: 20px 24px;
        border-top: 1px solid var(--border-color);
        display: flex;
        gap: 12px;
        justify-content: flex-end;
    }

    .btn {
        padding: 12px 24px;
        border-radius: 10px;
        font-weight: 600;
        font-size: 0.95rem;
        cursor: pointer;
        transition: all 0.2s;
        border: none;
    }

    .btn-secondary {
        background: var(--bg-body);
        color: var(--text-main);
        border: 1px solid var(--border-color);
    }

    .btn-secondary:hover {
        background: var(--bg-surface);
    }

    .btn-primary {
        background: linear-gradient(135deg, var(--purple-primary), var(--purple-light));
        color: white;
        box-shadow: 0 4px 12px rgba(124, 58, 237, 0.3);
    }

    .btn-primary:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 16px rgba(124, 58, 237, 0.4);
    }

    /* Animations */
    @keyframes fadeInDown {
        from {
            opacity: 0;
            transform: translateY(-10px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    @keyframes fadeInUp {
        from {
            opacity: 0;
            transform: translateY(10px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    /* Responsive */
    @media (max-width: 768px) {
        .hero-title {
            font-size: 1.5rem;
        }

        .filter-tabs {
            gap: 6px;
        }

        .filter-tab {
            padding: 8px 16px;
            font-size: 0.85rem;
        }

        .fab {
            bottom: 80px;
            right: 20px;
        }
    }
</style>

<?php renderPageHeader('Mural de Avisos', 'Louvor PIB Oliveira'); ?>

<div class="container" style="padding-top: 0; max-width: 800px; margin: 0 auto;">
    
    <!-- Hero Section -->
    <div class="hero-avisos">
        <div class="hero-content">
            <div class="hero-icon">
                <i data-lucide="megaphone"></i>
            </div>
            <div class="hero-text">
                <h1 class="hero-title">Central de Comunicação</h1>
                <p class="hero-subtitle">Avisos importantes, eventos e novidades do ministério</p>
            </div>
        </div>
    </div>
    
    <!-- Search Bar -->
    <div class="search-container">
        <form method="GET">
            <?php if ($showArchived): ?><input type="hidden" name="archived" value="1"><?php endif; ?>
            <?php if ($showHistory): ?><input type="hidden" name="history" value="1"><?php endif; ?>
            <?php if ($filterType !== 'all'): ?><input type="hidden" name="type" value="<?= $filterType ?>"><?php endif; ?>
            <div class="search-wrapper">
                <i data-lucide="search" class="search-icon"></i>
                <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" 
                       placeholder="Buscar avisos..." class="search-input">
            </div>
        </form>
    </div>
    
    <!-- Filtros por Tipo -->
    <div class="filter-tabs">
        <a href="?<?= http_build_query(array_merge($_GET, ['type' => 'all'])) ?>" 
           class="filter-tab <?= $filterType === 'all' ? 'active' : '' ?>">
            ✨ Todos
        </a>
        <a href="?<?= http_build_query(array_merge($_GET, ['type' => 'espiritual'])) ?>" 
           class="filter-tab <?= $filterType === 'espiritual' ? 'active' : '' ?>">
            🙏 Espiritual
        </a>
        <a href="?<?= http_build_query(array_merge($_GET, ['type' => 'eventos'])) ?>" 
           class="filter-tab <?= $filterType === 'eventos' ? 'active' : '' ?>">
            🎉 Eventos
        </a>
        <a href="?<?= http_build_query(array_merge($_GET, ['type' => 'geral'])) ?>" 
           class="filter-tab <?= $filterType === 'geral' ? 'active' : '' ?>">
            📢 Geral
        </a>
        <a href="?<?= http_build_query(array_merge($_GET, ['type' => 'importante'])) ?>" 
           class="filter-tab <?= $filterType === 'importante' ? 'active' : '' ?>">
            ⭐ Importante
        </a>
        <a href="?<?= http_build_query(array_merge($_GET, ['type' => 'musica'])) ?>" 
           class="filter-tab <?= $filterType === 'musica' ? 'active' : '' ?>">
            🎵 Música
        </a>
        <a href="?<?= http_build_query(array_merge($_GET, ['type' => 'urgente'])) ?>" 
           class="filter-tab <?= $filterType === 'urgente' ? 'active' : '' ?>">
            🚨 Urgente
        </a>
    </div>

    <!-- Avisos List -->
    <?php if (empty($avisos)): ?>
        <div class="empty-state">
            <i data-lucide="inbox" class="empty-icon"></i>
            <div class="empty-title">Nenhum aviso encontrado</div>
            <div class="empty-message">
                <?= !empty($search) ? 'Tente ajustar sua busca' : 'Não há avisos no momento' ?>
            </div>
        </div>
    <?php else: ?>
        <?php foreach ($avisos as $aviso): ?>
            <div class="aviso-card <?= $aviso['priority'] ?>">
                <!-- Header -->
                <div class="aviso-header">
                    <?php if (!empty($aviso['author_avatar'])): ?>
                        <img src="<?= $aviso['author_avatar'] ?>" alt="<?= htmlspecialchars($aviso['author_name']) ?>" class="aviso-avatar">
                    <?php else: ?>
                        <div class="aviso-avatar-placeholder">
                            <?= strtoupper(substr($aviso['author_name'] ?? 'A', 0, 1)) ?>
                        </div>
                    <?php endif; ?>
                    
                    <div class="aviso-author-info">
                        <div class="aviso-author-name"><?= htmlspecialchars($aviso['author_name'] ?? 'Administrador') ?></div>
                        <div class="aviso-meta">
                            <span><?= date('d/m/Y', strtotime($aviso['created_at'])) ?></span>
                            <span>•</span>
                            <span class="priority-badge <?= $aviso['priority'] ?>">
                                <?= $aviso['priority'] === 'urgent' ? '🚨 Urgente' : ($aviso['priority'] === 'important' ? '⭐ Importante' : '📌 Normal') ?>
                            </span>
                        </div>
                    </div>

                    <?php if ($isAdmin): ?>
                        <div class="aviso-dropdown">
                            <button class="dropdown-toggle" onclick="toggleDropdown(this)">
                                <i data-lucide="more-vertical" style="width: 18px;"></i>
                            </button>
                            <div class="dropdown-menu">
                                <button class="dropdown-item" onclick="editAviso(<?= $aviso['id'] ?>)">
                                    <i data-lucide="edit" style="width: 16px;"></i>
                                    Editar
                                </button>
                                <form method="POST" style="margin: 0;">
                                    <input type="hidden" name="action" value="archive">
                                    <input type="hidden" name="id" value="<?= $aviso['id'] ?>">
                                    <button type="submit" class="dropdown-item">
                                        <i data-lucide="archive" style="width: 16px;"></i>
                                        Arquivar
                                    </button>
                                </form>
                                <form method="POST" style="margin: 0;" onsubmit="return confirm('Tem certeza que deseja deletar este aviso?')">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="id" value="<?= $aviso['id'] ?>">
                                    <button type="submit" class="dropdown-item danger">
                                        <i data-lucide="trash-2" style="width: 16px;"></i>
                                        Deletar
                                    </button>
                                </form>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Body -->
                <div class="aviso-body">
                    <div class="aviso-title"><?= htmlspecialchars($aviso['title']) ?></div>
                    <div class="aviso-message"><?= nl2br(htmlspecialchars($aviso['message'])) ?></div>
                    
                    <?php if (!empty($aviso['tags'])): ?>
                        <div class="aviso-tags">
                            <?php foreach ($aviso['tags'] as $tag): ?>
                                <span class="aviso-tag"><?= htmlspecialchars($tag['name']) ?></span>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<!-- FAB (Floating Action Button) -->
<?php if ($isAdmin): ?>
    <button class="fab" onclick="openCreateModal()">
        <i data-lucide="plus"></i>
    </button>
<?php endif; ?>

<!-- Modal de Criação/Edição -->
<div id="avisoModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3 class="modal-title" id="modalTitle">Novo Aviso</h3>
            <button class="modal-close" onclick="closeModal()">
                <i data-lucide="x" style="width: 18px;"></i>
            </button>
        </div>
        <form method="POST" id="avisoForm">
            <div class="modal-body">
                <input type="hidden" name="action" value="create" id="formAction">
                <input type="hidden" name="id" id="avisoId">
                
                <div class="form-group">
                    <label class="form-label">Título</label>
                    <input type="text" name="title" class="form-input" required id="avisoTitle">
                </div>
                
                <div class="form-group">
                    <label class="form-label">Mensagem</label>
                    <textarea name="message" class="form-textarea" required id="avisoMessage"></textarea>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Prioridade</label>
                    <select name="priority" class="form-select" id="avisoPriority">
                        <option value="normal">Normal</option>
                        <option value="important">Importante</option>
                        <option value="urgent">Urgente</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Tipo</label>
                    <select name="type" class="form-select" id="avisoType">
                        <option value="geral">Geral</option>
                        <option value="espiritual">Espiritual</option>
                        <option value="eventos">Eventos</option>
                        <option value="musica">Música</option>
                        <option value="importante">Importante</option>
                        <option value="urgente">Urgente</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Público-Alvo</label>
                    <select name="target_audience" class="form-select" id="avisoAudience">
                        <option value="all">Todos</option>
                        <option value="team">Equipe</option>
                        <option value="admins">Administradores</option>
                        <option value="leaders">Líderes</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Data de Expiração (Opcional)</label>
                    <input type="date" name="expires_at" class="form-input" id="avisoExpires">
                </div>
            </div>
            
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeModal()">Cancelar</button>
                <button type="submit" class="btn btn-primary">Salvar Aviso</button>
            </div>
        </form>
    </div>
</div>

<script>
    // Toggle Dropdown
    function toggleDropdown(btn) {
        const dropdown = btn.nextElementSibling;
        const allDropdowns = document.querySelectorAll('.dropdown-menu');
        
        allDropdowns.forEach(d => {
            if (d !== dropdown) d.classList.remove('show');
        });
        
        dropdown.classList.toggle('show');
    }

    // Close dropdowns when clicking outside
    document.addEventListener('click', (e) => {
        if (!e.target.closest('.aviso-dropdown')) {
            document.querySelectorAll('.dropdown-menu').forEach(d => d.classList.remove('show'));
        }
    });

    // Modal Functions
    function openCreateModal() {
        document.getElementById('modalTitle').textContent = 'Novo Aviso';
        document.getElementById('formAction').value = 'create';
        document.getElementById('avisoForm').reset();
        document.getElementById('avisoModal').classList.add('show');
        lucide.createIcons();
    }

    function editAviso(id) {
        // Aqui você implementaria a lógica para carregar os dados do aviso
        document.getElementById('modalTitle').textContent = 'Editar Aviso';
        document.getElementById('formAction').value = 'update';
        document.getElementById('avisoId').value = id;
        document.getElementById('avisoModal').classList.add('show');
        lucide.createIcons();
    }

    function closeModal() {
        document.getElementById('avisoModal').classList.remove('show');
    }

    // Close modal on outside click
    document.getElementById('avisoModal').addEventListener('click', (e) => {
        if (e.target.id === 'avisoModal') closeModal();
    });

    // Initialize Lucide icons
    document.addEventListener('DOMContentLoaded', () => {
        lucide.createIcons();
    });
</script>

<?php renderAppFooter(); ?>