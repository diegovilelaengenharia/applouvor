<?php
// admin/notificacoes.php
require_once '../includes/auth.php';
require_once '../includes/db.php';
require_once '../includes/layout.php';
require_once '../includes/notification_system.php';

$userId = $_SESSION['user_id'];
$notificationSystem = new NotificationSystem($pdo);

// Filtros
$filterType = $_GET['type'] ?? 'all';
$filterStatus = $_GET['status'] ?? 'all';
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$perPage = 20;

// Construir query
$where = ["user_id = ?"];
$params = [$userId];

if ($filterType !== 'all') {
    $where[] = "type = ?";
    $params[] = $filterType;
}

if ($filterStatus === 'unread') {
    $where[] = "is_read = 0";
} elseif ($filterStatus === 'read') {
    $where[] = "is_read = 1";
}

$whereClause = implode(' AND ', $where);

// Contar total
$stmtCount = $pdo->prepare("SELECT COUNT(*) FROM notifications WHERE $whereClause");
$stmtCount->execute($params);
$total = $stmtCount->fetchColumn();
$totalPages = ceil($total / $perPage);

// Buscar notificações
$offset = ($page - 1) * $perPage;
$stmt = $pdo->prepare("
    SELECT * FROM notifications 
    WHERE $whereClause 
    ORDER BY created_at DESC 
    LIMIT $perPage OFFSET $offset
");
$stmt->execute($params);
$notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Estatísticas
$stmtStats = $pdo->prepare("
    SELECT 
        COUNT(*) as total,
        SUM(CASE WHEN is_read = 0 THEN 1 ELSE 0 END) as unread,
        SUM(CASE WHEN is_read = 1 THEN 1 ELSE 0 END) as read
    FROM notifications 
    WHERE user_id = ?
");
$stmtStats->execute([$userId]);
$stats = $stmtStats->fetch(PDO::FETCH_ASSOC);

renderAppHeader('Notificações');
?>

<style>
    .notifications-container {
        max-width: 900px;
        margin: 0 auto;
        padding: 20px;
    }
    
    .stats-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
        gap: 12px;
        margin-bottom: 24px;
    }
    
    .stat-card {
        background: var(--bg-surface);
        padding: 16px;
        border-radius: 12px;
        border: 1px solid var(--border-color);
        position: relative;
        overflow: hidden;
    }
    
    .stat-card::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        height: 3px;
        background: currentColor;
    }
    
    .stat-card.total { color: #3b82f6; }
    .stat-card.unread { color: #ef4444; }
    .stat-card.read { color: #10b981; }
    
    .stat-value {
        font-size: 2rem;
        font-weight: 700;
        margin-bottom: 4px;
    }
    
    .stat-label {
        font-size: 0.85rem;
        color: var(--text-muted);
        font-weight: 500;
    }
    
    .filters-bar {
        background: var(--bg-surface);
        padding: 16px;
        border-radius: 12px;
        border: 1px solid var(--border-color);
        margin-bottom: 20px;
    }
    
    .filters-grid {
        display: grid;
        grid-template-columns: 1fr 1fr auto;
        gap: 12px;
        align-items: end;
    }
    
    .filter-group label {
        display: block;
        font-size: 0.85rem;
        font-weight: 600;
        color: var(--text-main);
        margin-bottom: 6px;
    }
    
    .filter-group select {
        width: 100%;
        padding: 10px 12px;
        border: 1px solid var(--border-color);
        border-radius: 8px;
        font-size: 0.9rem;
        background: var(--bg-body);
        color: var(--text-main);
    }
    
    .btn-filter {
        padding: 10px 20px;
        background: var(--primary);
        color: white;
        border: none;
        border-radius: 8px;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.2s;
    }
    
    .btn-filter:hover {
        background: #059669;
        transform: translateY(-1px);
    }
    
    .notification-list {
        background: var(--bg-surface);
        border-radius: 12px;
        border: 1px solid var(--border-color);
        overflow: hidden;
    }
    
    .notification-item {
        padding: 16px;
        border-bottom: 1px solid var(--border-color);
        display: flex;
        gap: 16px;
        transition: background 0.2s;
    }
    
    .notification-item:last-child {
        border-bottom: none;
    }
    
    .notification-item:hover {
        background: var(--bg-body);
    }
    
    .notification-item.unread {
        background: #eff6ff;
    }
    
    .notification-icon {
        width: 48px;
        height: 48px;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
    }
    
    .notification-content {
        flex: 1;
        min-width: 0;
    }
    
    .notification-header {
        display: flex;
        justify-content: space-between;
        align-items: start;
        margin-bottom: 6px;
    }
    
    .notification-title {
        font-size: 1rem;
        font-weight: 600;
        color: var(--text-main);
    }
    
    .notification-time {
        font-size: 0.8rem;
        color: var(--text-muted);
        white-space: nowrap;
    }
    
    .notification-message {
        font-size: 0.9rem;
        color: var(--text-muted);
        line-height: 1.5;
        margin-bottom: 8px;
    }
    
    .notification-actions {
        display: flex;
        gap: 8px;
    }
    
    .btn-action {
        padding: 6px 12px;
        font-size: 0.8rem;
        border: 1px solid var(--border-color);
        border-radius: 6px;
        background: var(--bg-body);
        color: var(--text-main);
        cursor: pointer;
        transition: all 0.2s;
        font-weight: 500;
    }
    
    .btn-action:hover {
        background: var(--primary);
        color: white;
        border-color: var(--primary);
    }
    
    .btn-action.danger:hover {
        background: #ef4444;
        border-color: #ef4444;
    }
    
    .pagination {
        display: flex;
        justify-content: center;
        align-items: center;
        gap: 8px;
        margin-top: 24px;
    }
    
    .pagination a,
    .pagination span {
        padding: 8px 12px;
        border: 1px solid var(--border-color);
        border-radius: 6px;
        text-decoration: none;
        color: var(--text-main);
        font-weight: 500;
        transition: all 0.2s;
    }
    
    .pagination a:hover {
        background: var(--primary);
        color: white;
        border-color: var(--primary);
    }
    
    .pagination .current {
        background: var(--primary);
        color: white;
        border-color: var(--primary);
    }
    
    .empty-state {
        text-align: center;
        padding: 60px 20px;
        color: var(--text-muted);
    }
    
    .empty-state i {
        width: 64px;
        height: 64px;
        margin: 0 auto 16px;
        opacity: 0.3;
    }
    
    @media (max-width: 768px) {
        .filters-grid {
            grid-template-columns: 1fr;
        }
        
        .notification-item {
            flex-direction: column;
        }
        
        .notification-header {
            flex-direction: column;
            gap: 4px;
        }
    }
</style>

<div class="notifications-container">
    <!-- Estatísticas -->
    <div class="stats-grid">
        <div class="stat-card total">
            <div class="stat-value"><?= $stats['total'] ?></div>
            <div class="stat-label">Total</div>
        </div>
        <div class="stat-card unread">
            <div class="stat-value"><?= $stats['unread'] ?></div>
            <div class="stat-label">Não Lidas</div>
        </div>
        <div class="stat-card read">
            <div class="stat-value"><?= $stats['read'] ?></div>
            <div class="stat-label">Lidas</div>
        </div>
    </div>
    
    <!-- Filtros -->
    <div class="filters-bar">
        <form method="GET" class="filters-grid">
            <div class="filter-group">
                <label>Tipo</label>
                <select name="type">
                    <option value="all" <?= $filterType === 'all' ? 'selected' : '' ?>>Todos</option>
                    <option value="new_escala" <?= $filterType === 'new_escala' ? 'selected' : '' ?>>Nova Escala</option>
                    <option value="new_music" <?= $filterType === 'new_music' ? 'selected' : '' ?>>Nova Música</option>
                    <option value="new_aviso" <?= $filterType === 'new_aviso' ? 'selected' : '' ?>>Novo Aviso</option>
                    <option value="aviso_urgent" <?= $filterType === 'aviso_urgent' ? 'selected' : '' ?>>Aviso Urgente</option>
                    <option value="weekly_report" <?= $filterType === 'weekly_report' ? 'selected' : '' ?>>Relatório Semanal</option>
                </select>
            </div>
            
            <div class="filter-group">
                <label>Status</label>
                <select name="status">
                    <option value="all" <?= $filterStatus === 'all' ? 'selected' : '' ?>>Todas</option>
                    <option value="unread" <?= $filterStatus === 'unread' ? 'selected' : '' ?>>Não Lidas</option>
                    <option value="read" <?= $filterStatus === 'read' ? 'selected' : '' ?>>Lidas</option>
                </select>
            </div>
            
            <button type="submit" class="btn-filter">Filtrar</button>
        </form>
    </div>
    
    <!-- Lista de Notificações -->
    <div class="notification-list">
        <?php if (empty($notifications)): ?>
            <div class="empty-state">
                <i data-lucide="bell-off"></i>
                <div style="font-size: 1.1rem; font-weight: 600; margin-bottom: 8px;">Nenhuma notificação</div>
                <div>Você não tem notificações com os filtros selecionados.</div>
            </div>
        <?php else: ?>
            <?php foreach ($notifications as $notification): ?>
                <?php
                $config = $notificationSystem->getNotificationConfig($notification['type']);
                $unreadClass = !$notification['is_read'] ? 'unread' : '';
                $timeAgo = getTimeAgo($notification['created_at']);
                ?>
                <div class="notification-item <?= $unreadClass ?>" data-id="<?= $notification['id'] ?>">
                    <div class="notification-icon" style="background: <?= $config['color'] ?>20; color: <?= $config['color'] ?>;">
                        <i data-lucide="<?= $config['icon'] ?>" style="width: 24px; height: 24px;"></i>
                    </div>
                    
                    <div class="notification-content">
                        <div class="notification-header">
                            <div class="notification-title"><?= htmlspecialchars($notification['title']) ?></div>
                            <div class="notification-time"><?= $timeAgo ?></div>
                        </div>
                        
                        <?php if ($notification['message']): ?>
                            <div class="notification-message"><?= htmlspecialchars($notification['message']) ?></div>
                        <?php endif; ?>
                        
                        <div class="notification-actions">
                            <?php if (!$notification['is_read']): ?>
                                <button class="btn-action" onclick="markAsRead(<?= $notification['id'] ?>)">
                                    <i data-lucide="check" style="width: 14px; height: 14px;"></i> Marcar como lida
                                </button>
                            <?php endif; ?>
                            
                            <?php if ($notification['link']): ?>
                                <a href="<?= htmlspecialchars($notification['link']) ?>" class="btn-action">
                                    <i data-lucide="external-link" style="width: 14px; height: 14px;"></i> Ver detalhes
                                </a>
                            <?php endif; ?>
                            
                            <button class="btn-action danger" onclick="deleteNotification(<?= $notification['id'] ?>)">
                                <i data-lucide="trash-2" style="width: 14px; height: 14px;"></i> Excluir
                            </button>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
    
    <!-- Paginação -->
    <?php if ($totalPages > 1): ?>
        <div class="pagination">
            <?php if ($page > 1): ?>
                <a href="?page=<?= $page - 1 ?>&type=<?= $filterType ?>&status=<?= $filterStatus ?>">
                    <i data-lucide="chevron-left" style="width: 16px;"></i>
                </a>
            <?php endif; ?>
            
            <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
                <?php if ($i === $page): ?>
                    <span class="current"><?= $i ?></span>
                <?php else: ?>
                    <a href="?page=<?= $i ?>&type=<?= $filterType ?>&status=<?= $filterStatus ?>"><?= $i ?></a>
                <?php endif; ?>
            <?php endfor; ?>
            
            <?php if ($page < $totalPages): ?>
                <a href="?page=<?= $page + 1 ?>&type=<?= $filterType ?>&status=<?= $filterStatus ?>">
                    <i data-lucide="chevron-right" style="width: 16px;"></i>
                </a>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</div>

<script>
function getTimeAgo(dateString) {
    const date = new Date(dateString);
    const now = new Date();
    const seconds = Math.floor((now - date) / 1000);
    
    if (seconds < 60) return 'Agora';
    if (seconds < 3600) return `${Math.floor(seconds / 60)}m atrás`;
    if (seconds < 86400) return `${Math.floor(seconds / 3600)}h atrás`;
    if (seconds < 604800) return `${Math.floor(seconds / 86400)}d atrás`;
    
    return date.toLocaleDateString('pt-BR', {day: '2-digit', month: 'short', year: 'numeric'});
}

async function markAsRead(id) {
    try {
        const response = await fetch('notifications_api.php?action=mark_read', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({id})
        });
        
        const data = await response.json();
        if (data.success) {
            location.reload();
        }
    } catch (error) {
        console.error('Erro:', error);
        alert('Erro ao marcar como lida');
    }
}

async function deleteNotification(id) {
    if (!confirm('Deseja realmente excluir esta notificação?')) return;
    
    try {
        const response = await fetch('notifications_api.php?action=delete', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({id})
        });
        
        const data = await response.json();
        if (data.success) {
            location.reload();
        }
    } catch (error) {
        console.error('Erro:', error);
        alert('Erro ao excluir notificação');
    }
}

lucide.createIcons();
</script>

<?php
function getTimeAgo($dateString) {
    $date = new DateTime($dateString);
    $now = new DateTime();
    $diff = $now->diff($date);
    
    if ($diff->days == 0) {
        if ($diff->h == 0) {
            if ($diff->i == 0) return 'Agora';
            return $diff->i . 'm atrás';
        }
        return $diff->h . 'h atrás';
    }
    
    if ($diff->days < 7) return $diff->days . 'd atrás';
    
    return $date->format('d/m/Y');
}

renderAppFooter();
?>
