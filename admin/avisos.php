<?php
// admin/avisos.php - Redesign com visual premium
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
                        
                        // Definir query de usuários baseado no público
                        $usersQuery = "SELECT id FROM users WHERE status = 'active'";
                        $usersParams = [];
                        
                        if ($targetAudience === 'admins') {
                            $usersQuery .= " AND role = 'admin'";
                        } elseif ($targetAudience === 'team') {
                            $usersQuery .= " AND role IN ('admin', 'member')"; // Assumindo 'member' como equipe
                        }
                        
                        $stmtUsers = $pdo->query($usersQuery);
                        $users = $stmtUsers->fetchAll(PDO::FETCH_COLUMN);
                        
                        foreach ($users as $uid) {
                            if ($uid == $userId) continue; // Não notificar o próprio criador
                            
                            $notificationSystem->createNotification(
                                $uid,
                                $notifType,
                                "Novo Aviso: $title",
                                strip_tags($_POST['message']), // Remover HTML da mensagem para notificação
                                "avisos.php" 
                            );
                        }
                    } catch (Exception $e) {
                         // Ignorar erros de notificação para não bloquear o aviso
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
$search = $_GET['search'] ?? '';

// Buscar todas as tags
$tags = $pdo->query("SELECT * FROM aviso_tags ORDER BY is_default DESC, name ASC")->fetchAll(PDO::FETCH_ASSOC);

// Construir Query
$sql = "SELECT a.*, u.name as author_name, u.avatar as author_avatar FROM avisos a LEFT JOIN users u ON a.created_by = u.id WHERE 1=1";
$params = [];

if ($showArchived) {
    $sql .= " AND a.archived_at IS NOT NULL";
} elseif ($showHistory) {
    $sql .= " AND (a.archived_at IS NOT NULL OR (a.expires_at IS NOT NULL AND a.expires_at < CURDATE()))";
} else {
    $sql .= " AND a.archived_at IS NULL AND (a.expires_at IS NULL OR a.expires_at >= CURDATE())";
}

// Filtro por tag
if ($filterTag !== 'all') {
    $sql .= " AND EXISTS (
        SELECT 1 FROM aviso_tag_relations r 
        JOIN aviso_tags t ON r.tag_id = t.id 
        WHERE r.aviso_id = a.id AND t.id = ?
    )";
    $params[] = $filterTag;
}

if (!empty($search)) {
    $sql .= " AND (a.title LIKE ? OR a.message LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

$sql .= " ORDER BY 
    a.is_pinned DESC,
    CASE WHEN a.priority = 'urgent' THEN 1 WHEN a.priority = 'important' THEN 2 ELSE 3 END ASC, 
    a.created_at DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$avisos = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Buscar tags de cada aviso
foreach ($avisos as &$aviso) {
    $stmt = $pdo->prepare("
        SELECT t.* FROM aviso_tags t
        JOIN aviso_tag_relations r ON t.id = r.tag_id
        WHERE r.aviso_id = ?
    ");
    $stmt->execute([$aviso['id']]);
    $aviso['tags'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

renderAppHeader('Avisos');
?>

<!-- Quill CSS -->
<link href="https://cdn.quilljs.com/1.3.6/quill.snow.css" rel="stylesheet">

<style>
    /* Aviso Cards - Premium Design */
    .aviso-card {
        background: var(--bg-surface);
        border-radius: 16px;
        border: 1px solid var(--border-color);
        overflow: hidden;
        box-shadow: var(--shadow-sm);
        transition: transform 0.2s, box-shadow 0.2s;
    }
    .aviso-card:hover {
        box-shadow: 0 8px 25px rgba(0,0,0,0.08);
    }
    
    /* Header do Card */
    .aviso-header {
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 16px;
        border-bottom: 1px solid var(--border-color);
    }
    .aviso-avatar {
        width: 44px;
        height: 44px;
        border-radius: 50%;
        object-fit: cover;
        border: 2px solid white;
        box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    }
    .aviso-avatar-placeholder {
        width: 44px;
        height: 44px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-weight: 700;
        color: white;
        font-weight: 700;
        font-size: var(--font-h3);
    }
    .aviso-author-info {
        flex: 1;
    }
    .aviso-author-name {
        font-weight: 700;
        font-weight: 700;
        color: var(--text-main);
        font-size: var(--font-body);
    }
    .aviso-meta {
        display: flex;
        align-items: center;
        align-items: center;
        gap: 8px;
        font-size: var(--font-body-sm);
        color: var(--text-muted);
    }
    .aviso-type-badge {
        display: inline-flex;
        align-items: center;
        gap: 4px;
        padding: 2px 8px;
        padding: 2px 8px;
        border-radius: 12px;
        font-size: var(--font-caption);
        font-weight: 600;
    }
    
    /* Content */
    .aviso-content {
        padding: 16px;
    }
    .aviso-title {
        font-size: var(--font-h2);
        font-weight: 700;
        color: var(--text-main);
        margin-bottom: 8px;
    }
    .aviso-text {
        color: var(--text-body);
        font-size: var(--font-body);
        line-height: 1.6;
    }
    .aviso-text p { margin: 0 0 8px; }
    
    /* Footer */
    .aviso-footer {
        padding: 12px 16px;
        background: #f8fafc;
        border-top: 1px solid var(--border-color);
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    
    /* Type Badges */
    .type-general { background: #f1f5f9; color: #64748b; }
    .type-event { background: #eff6ff; color: #2563eb; }
    .type-music { background: #ecfdf5; color: #059669; }
    .type-spiritual { background: #f5f3ff; color: #7c3aed; }
    .type-urgent { background: #fef2f2; color: #dc2626; }
    
    /* Priority Badges */
    .priority-urgent {
        background: #fef2f2;
        color: #dc2626;
        padding: 3px 8px;
        border-radius: 6px;
        font-size: var(--font-caption);
        font-weight: 700;
        text-transform: uppercase;
    }
    .priority-important {
        background: #fef3c7;
        color: #d97706;
        padding: 3px 8px;
        border-radius: 6px;
        font-size: var(--font-caption);
        font-weight: 700;
        text-transform: uppercase;
    }
    
    /* FAB */
    .fab-create {
        position: fixed;
        bottom: 90px;
        right: 20px;
        width: 56px;
        height: 56px;
        border-radius: 50%;
        background: linear-gradient(135deg, #f97316, #ea580c);
        color: white;
        border: none;
        box-shadow: 0 4px 20px rgba(249, 115, 22, 0.4);
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        z-index: 100;
        transition: transform 0.2s, box-shadow 0.2s;
    }
    .fab-create:hover {
        transform: scale(1.05);
        box-shadow: 0 6px 25px rgba(249, 115, 22, 0.5);
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
        background: linear-gradient(135deg, #f97316, #ea580c);
        color: white;
        border-color: transparent;
    }
    
    /* Dropdown Menu */
    .aviso-dropdown {
        display: none;
        position: absolute;
        right: 0;
        top: 100%;
        background: white;
        border-radius: 12px;
        box-shadow: 0 4px 20px rgba(0,0,0,0.12);
        border: 1px solid var(--border-color);
        min-width: 150px;
        z-index: 50;
        overflow: hidden;
    }
    .aviso-dropdown a, .aviso-dropdown button {
        display: flex;
        align-items: center;
        gap: 8px;
        padding: 12px 16px;
        color: var(--text-main);
        text-decoration: none;
        font-size: var(--font-body);
        width: 100%;
        background: none;
        border: none;
        cursor: pointer;
        text-align: left;
    }
    .aviso-dropdown a:hover, .aviso-dropdown button:hover {
        background: #f8fafc;
    }
    .aviso-dropdown .delete-btn {
        color: #dc2626;
        border-top: 1px solid var(--border-color);
    }
</style>

<?php renderPageHeader('Mural de Avisos', 'Louvor PIB Oliveira'); ?>

<div class="container" style="padding-top: 16px; max-width: 700px; margin: 0 auto;">
    
    <!-- Hero Section -->
    <div style="text-align: center; padding: 20px 0 30px;">
        <div style="background: linear-gradient(135deg, #f97316 0%, #ea580c 100%); width: 70px; height: 70px; border-radius: 20px; display: flex; align-items: center; justify-content: center; margin: 0 auto 16px; box-shadow: 0 8px 25px rgba(249, 115, 22, 0.3);">
            <i data-lucide="megaphone" style="color: white; width: 36px; height: 36px;"></i>
        </div>
        <h2 style="font-size: var(--font-h1); font-weight: 800; color: var(--text-main); margin: 0 0 6px;">Central de Comunicação</h2>
        <p style="color: var(--text-muted); font-size: var(--font-body); max-width: 400px; margin: 0 auto;">
            Avisos importantes, eventos e novidades do ministério. Fique por dentro de tudo!
        </p>
    </div>
    
    <!-- Search Bar -->
    <div style="margin-bottom: 20px;">
        <form style="display: flex; gap: 10px;">
            <?php if ($showArchived): ?><input type="hidden" name="archived" value="1"><?php endif; ?>
            <?php if ($showHistory): ?><input type="hidden" name="history" value="1"><?php endif; ?>
            <?php if ($filterType !== 'all'): ?><input type="hidden" name="type" value="<?= $filterType ?>"><?php endif; ?>
            <div style="flex: 1; position: relative;">
                <i data-lucide="search" style="position: absolute; left: 14px; top: 50%; transform: translateY(-50%); width: 18px; color: var(--text-muted);"></i>
                <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" placeholder="Buscar avisos..." style="
                    width: 100%; padding: 12px 12px 12px 44px; border: 1px solid var(--border-color);
                    border-radius: 12px; font-size: var(--font-body); background: var(--bg-surface);
                    outline: none; color: var(--text-main);
                ">
            </div>
        </form>
    </div>
    
    <!-- Filtros por Tag -->
    <div class="filter-tabs">
        <a href="?tag=all<?= $showHistory ? '&history=1' : '' ?>" class="filter-tab <?= $filterTag === 'all' ? 'active' : '' ?>">✨ Todos</a>
        <?php foreach ($tags as $tag): ?>
            <a href="?tag=<?= $tag['id'] ?><?= $showHistory ? '&history=1' : '' ?>" 
               class="filter-tab <?= $filterTag == $tag['id'] ? 'active' : '' ?>" 
               style="<?= $filterTag == $tag['id'] ? 'background: ' . $tag['color'] . '; color: white; border-color: transparent;' : '' ?>">
                <?= htmlspecialchars($tag['name']) ?>
            </a>
        <?php endforeach; ?>
    </div>
    
    <!-- Toggle Histórico -->
    <div style="margin-bottom: 20px;">
        <a href="?<?= $showHistory ? '' : 'history=1' ?><?= $filterType !== 'all' ? '&type=' . $filterType : '' ?>" style="
            display: inline-flex; align-items: center; gap: 6px;
            padding: 8px 14px; border-radius: 20px; font-size: var(--font-body-sm); font-weight: 600;
            text-decoration: none; color: var(--text-muted);
            background: <?= $showHistory ? 'var(--bg-surface)' : 'transparent' ?>;
            border: 1px solid <?= $showHistory ? 'var(--border-color)' : 'transparent' ?>;
        ">
            <i data-lucide="<?= $showHistory ? 'eye' : 'clock' ?>" style="width: 14px;"></i>
            <?= $showHistory ? 'Ver Ativos' : 'Ver Histórico' ?>
        </a>
    </div>
    
    <!-- Feed de Avisos -->
    <div style="display: flex; flex-direction: column; gap: 20px;">
        <?php if (count($avisos) > 0): ?>
            <?php foreach ($avisos as $aviso): 
                // Prioridade
                $isUrgent = $aviso['priority'] === 'urgent';
                $isImportant = $aviso['priority'] === 'important';
                
                // Data Relativa
                $createdAt = new DateTime($aviso['created_at']);
                $now = new DateTime();
                $diff = $now->diff($createdAt);
                
                if ($diff->y > 0) $timeAgo = $diff->y . ' ano(s)';
                elseif ($diff->m > 0) $timeAgo = $diff->m . ' mês(es)';
                elseif ($diff->d > 0) $timeAgo = $diff->d . ' dia(s)';
                elseif ($diff->h > 0) $timeAgo = $diff->h . 'h';
                elseif ($diff->i > 0) $timeAgo = $diff->i . 'min';
                else $timeAgo = 'agora';

                // Expiração
                $expiryText = "";
                $expiryClass = "";
                if ($aviso['expires_at']) {
                    $expiresAt = new DateTime($aviso['expires_at']);
                    $daysLeft = (int)$now->diff($expiresAt)->format('%r%a');
                    
                    if ($daysLeft < 0) { $expiryText = "Expirado"; $expiryClass = "color: var(--text-muted)"; }
                    elseif ($daysLeft == 0) { $expiryText = "Expira hoje"; $expiryClass = "color: #dc2626"; }
                    elseif ($daysLeft <= 3) { $expiryText = "Expira em $daysLeft dias"; $expiryClass = "color: #d97706"; }
                    else { $expiryText = "Expira " . $expiresAt->format('d/m'); $expiryClass = "color: var(--text-muted)"; }
                }
                
                // Avatar
                $authorAvatar = !empty($aviso['author_avatar']) ? $aviso['author_avatar'] : null;
                if ($authorAvatar && strpos($authorAvatar, 'http') === false && strpos($authorAvatar, 'assets') === false) {
                    $authorAvatar = '../assets/uploads/' . $authorAvatar;
                }
            ?>
            <div class="aviso-card animate-in">
                <!-- Header -->
                <div class="aviso-header">
                    <?php if ($authorAvatar): ?>
                        <img src="<?= htmlspecialchars($authorAvatar) ?>" class="aviso-avatar" alt="Avatar">
                    <?php else: 
                        $avatarColor = !empty($aviso['tags']) ? $aviso['tags'][0]['color'] : '#f97316';
                    ?>
                        <div class="aviso-avatar-placeholder" style="background: <?= $avatarColor ?>;">
                            <?= strtoupper(substr($aviso['author_name'] ?? 'A', 0, 1)) ?>
                        </div>
                    <?php endif; ?>
                    
                    <div class="aviso-author-info">
                        <div class="aviso-author-name"><?= htmlspecialchars($aviso['author_name'] ?? 'Admin') ?></div>
                        <div class="aviso-meta">
                            <?php foreach ($aviso['tags'] as $tag): ?>
                                <span class="aviso-type-badge" style="background: <?= $tag['color'] ?>22; color: <?= $tag['color'] ?>;">
                                    <?= htmlspecialchars($tag['name']) ?>
                                </span>
                            <?php endforeach; ?>
                            <span>• <?= $timeAgo ?></span>
                        </div>
                    </div>
                    
                    <?php if ($isAdmin): ?>
                    <div style="position: relative;">
                        <button onclick="toggleAvisoMenu('menu-<?= $aviso['id'] ?>')" style="background: none; border: none; padding: 8px; cursor: pointer; color: var(--text-muted); border-radius: 50%;">
                            <i data-lucide="more-vertical" style="width: 18px;"></i>
                        </button>
                        <div id="menu-<?= $aviso['id'] ?>" class="aviso-dropdown">
                            <a href="#" onclick="openEditModal(<?= htmlspecialchars(json_encode($aviso)) ?>); return false;">
                                <i data-lucide="edit-2" style="width: 16px;"></i> Editar
                            </a>
                            <?php if (!$aviso['archived_at']): ?>
                            <form method="POST" style="margin: 0;">
                                <input type="hidden" name="action" value="archive">
                                <input type="hidden" name="id" value="<?= $aviso['id'] ?>">
                                <button type="submit">
                                    <i data-lucide="archive" style="width: 16px;"></i> Arquivar
                                </button>
                            </form>
                            <?php else: ?>
                            <form method="POST" style="margin: 0;">
                                <input type="hidden" name="action" value="unarchive">
                                <input type="hidden" name="id" value="<?= $aviso['id'] ?>">
                                <button type="submit">
                                    <i data-lucide="archive-restore" style="width: 16px;"></i> Restaurar
                                </button>
                            </form>
                            <?php endif; ?>
                            <form method="POST" style="margin: 0;" onsubmit="return confirm('Excluir este aviso permanentemente?')">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="id" value="<?= $aviso['id'] ?>">
                                <button type="submit" class="delete-btn">
                                    <i data-lucide="trash-2" style="width: 16px;"></i> Excluir
                                </button>
                            </form>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
                
                <!-- Content -->
                <div class="aviso-content">
                    <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 8px; flex-wrap: wrap;">
                        <h3 class="aviso-title" style="margin: 0;"><?= htmlspecialchars($aviso['title']) ?></h3>
                        <?php if($isUrgent): ?>
                            <span class="priority-urgent">🔥 URGENTE</span>
                        <?php elseif($isImportant): ?>
                            <span class="priority-important">⭐ IMPORTANTE</span>
                        <?php endif; ?>
                    </div>
                    
                    <div class="aviso-text"><?= $aviso['message'] ?></div>
                </div>
                
                <!-- Footer -->
                <div class="aviso-footer">
                    <div style="display: flex; align-items: center; gap: 8px; font-size: var(--font-body-sm); color: var(--text-muted);">
                        <?php if($aviso['target_audience'] !== 'all'): ?>
                            <span style="display: flex; align-items: center; gap: 4px; background: #f1f5f9; padding: 4px 10px; border-radius: 12px;">
                                <i data-lucide="users" style="width: 12px;"></i>
                                <?= $aviso['target_audience'] == 'admins' ? 'Líderes' : 'Equipe' ?>
                            </span>
                        <?php endif; ?>
                    </div>
                    
                    <div style="display: flex; align-items: center; gap: 10px; font-size: var(--font-body-sm); color: var(--text-muted);">
                        <?php if($expiryText): ?>
                            <span style="display: flex; align-items: center; gap: 4px; <?= $expiryClass ?>">
                                <i data-lucide="clock" style="width: 12px;"></i>
                                <?= $expiryText ?>
                            </span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        <?php else: ?>
            <!-- Empty State -->
            <div style="text-align: center; padding: 60px 20px;">
                <div style="background: linear-gradient(135deg, #fef3c7 0%, #fed7aa 100%); width: 80px; height: 80px; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 20px;">
                    <i data-lucide="bell-off" style="color: #f97316; width: 40px; height: 40px;"></i>
                </div>
                <h3 style="color: var(--text-main); margin-bottom: 8px;">Nenhum aviso encontrado</h3>
                <p style="color: var(--text-muted); font-size: var(--font-body); max-width: 300px; margin: 0 auto 20px;">
                    <?= $showHistory ? 'Nenhum aviso no histórico.' : 'Não há avisos ativos no momento.' ?>
                </p>
                <?php if ($isAdmin): ?>
                <button onclick="openCreateModal()" style="background: linear-gradient(135deg, #f97316, #ea580c); color: white; border: none; padding: 12px 24px; border-radius: 24px; font-weight: 600; cursor: pointer;">
                    <i data-lucide="plus" style="width: 18px; display: inline-block; vertical-align: middle; margin-right: 6px;"></i>
                    Criar Aviso
                </button>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
    
    <div style="height: 100px;"></div>
</div>

<!-- FAB Button -->
<?php if ($isAdmin): ?>
<button onclick="openCreateModal()" class="fab-create">
    <i data-lucide="plus" style="width: 28px; height: 28px;"></i>
</button>
<?php endif; ?>

<!-- Modal Create/Edit -->
<div id="avisoModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; z-index: 1000;">
    <div onclick="closeModal()" style="position: absolute; width: 100%; height: 100%; background: rgba(0,0,0,0.5); backdrop-filter: blur(4px);"></div>
    
    <div style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); width: 90%; max-width: 550px; background: var(--bg-surface); border-radius: 24px; padding: 24px; box-shadow: 0 20px 60px rgba(0,0,0,0.2); max-height: 90vh; overflow-y: auto;">
        
        <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 20px;">
            <h2 id="modalTitle" style="margin: 0; font-size: var(--font-h1); font-weight: 800; color: var(--text-main);">📢 Novo Aviso</h2>
            <button onclick="closeModal()" style="background: none; border: none; color: var(--text-muted); cursor: pointer; padding: 8px;">
                <i data-lucide="x" style="width: 20px;"></i>
            </button>
        </div>
        
        <form method="POST" id="avisoForm" onsubmit="return prepareSubmit()">
            <input type="hidden" name="action" id="formAction" value="create">
            <input type="hidden" name="id" id="avisoId">
            <input type="hidden" name="message" id="hiddenMessage">
            
            <!-- Título -->
            <div style="margin-bottom: 16px;">
                <label style="display: block; font-weight: 700; color: var(--text-main); margin-bottom: 6px; font-size: var(--font-body);">Título</label>
                <input type="text" name="title" id="avisoTitle" required placeholder="Ex: Ensaio especial neste sábado" style="width: 100%; padding: 12px 14px; border: 1px solid var(--border-color); border-radius: 12px; font-size: var(--font-body); outline: none; background: var(--bg-body);">
            </div>
            
            <!-- Tipo e Prioridade -->
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 16px;">
                <!-- Tipo -->
                <div>
                    <label style="display: block; font-weight: 700; color: var(--text-main); margin-bottom: 6px; font-size: var(--font-body);">Tipo</label>
                    <select name="type" id="avisoType" style="width: 100%; padding: 12px; border: 1px solid var(--border-color); border-radius: 12px; font-size: var(--font-body); background: var(--bg-body); color: var(--text-main);">
                        <option value="general">📢 Geral</option>
                        <option value="event">🎉 Evento</option>
                        <option value="music">🎵 Música</option>
                        <option value="spiritual">🙏 Espiritual</option>
                        <option value="urgent">🚨 Urgente</option>
                    </select>
                </div>
                
                <!-- Prioridade -->
                <div>
                    <label style="display: block; font-weight: 700; color: var(--text-main); margin-bottom: 6px; font-size: var(--font-body);">Prioridade</label>
                    <select name="priority" id="avisoPriority" style="width: 100%; padding: 12px; border: 1px solid var(--border-color); border-radius: 12px; font-size: var(--font-body); background: var(--bg-body); color: var(--text-main);">
                        <option value="info">ℹ️ Normal</option>
                        <option value="important">⭐ Importante</option>
                        <option value="urgent">🔥 Urgente</option>
                    </select>
                </div>
            </div>
            
            <!-- Público e Validade -->
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 16px;">
                <!-- Público -->
                <div>
                    <label style="display: block; font-weight: 700; color: var(--text-main); margin-bottom: 6px; font-size: var(--font-body);">Público</label>
                    <select name="target_audience" id="avisoTarget" style="width: 100%; padding: 12px; border: 1px solid var(--border-color); border-radius: 12px; font-size: var(--font-body); background: var(--bg-body); color: var(--text-main);">
                        <option value="all">👥 Todos</option>
                        <option value="team">🎸 Equipe</option>
                        <option value="admins">👑 Líderes</option>
                    </select>
                </div>
                
                <!-- Validade -->
                <div>
                    <label style="display: block; font-weight: 700; color: var(--text-main); margin-bottom: 6px; font-size: var(--font-body);">Expira em</label>
                    <input type="date" name="expires_at" id="avisoExpires" style="width: 100%; padding: 12px; border: 1px solid var(--border-color); border-radius: 12px; font-size: var(--font-body); background: var(--bg-body); color: var(--text-main);">
                </div>
            </div>
            
            <!-- Editor de Texto -->
            <div style="margin-bottom: 20px;">
                <label style="display: block; font-weight: 700; color: var(--text-main); margin-bottom: 6px; font-size: var(--font-body);">Mensagem</label>
                <div id="editor" style="height: 150px; background: white; border-radius: 12px;"></div>
            </div>
            
            <!-- Buttons -->
            <div style="display: flex; gap: 12px;">
                <button type="button" onclick="closeModal()" style="flex: 1; padding: 14px; border-radius: 12px; border: 1px solid var(--border-color); background: var(--bg-surface); font-weight: 600; cursor: pointer; color: var(--text-muted);">
                    Cancelar
                </button>
                <button type="submit" style="flex: 2; padding: 14px; border-radius: 12px; border: none; background: linear-gradient(135deg, #f97316, #ea580c); color: white; font-weight: 700; cursor: pointer;">
                    Publicar
                </button>
            </div>
        </form>
    </div>
</div>

<script src="https://cdn.quilljs.com/1.3.6/quill.js"></script>
<script>
    // Quill Editor
    var quill = new Quill('#editor', {
        theme: 'snow',
        placeholder: 'Escreva a mensagem do aviso...',
        modules: {
            toolbar: [
                ['bold', 'italic', 'underline'],
                [{ 'list': 'ordered' }, { 'list': 'bullet' }],
                ['link']
            ]
        }
    });
    
    function prepareSubmit() {
        document.getElementById('hiddenMessage').value = quill.root.innerHTML;
        return true;
    }
    
    function openCreateModal() {
        document.getElementById('modalTitle').innerText = '📢 Novo Aviso';
        document.getElementById('formAction').value = 'create';
        document.getElementById('avisoForm').reset();
        document.getElementById('avisoId').value = '';
        quill.setContents([]);
        
        document.getElementById('avisoModal').style.display = 'block';
    }
    
    function openEditModal(aviso) {
        document.getElementById('modalTitle').innerText = '✏️ Editar Aviso';
        document.getElementById('formAction').value = 'update';
        document.getElementById('avisoId').value = aviso.id;
        document.getElementById('avisoTitle').value = aviso.title;
        document.getElementById('avisoType').value = aviso.type;
        document.getElementById('avisoPriority').value = aviso.priority;
        document.getElementById('avisoTarget').value = aviso.target_audience || 'all';
        document.getElementById('avisoExpires').value = aviso.expires_at || '';
        
        quill.root.innerHTML = aviso.message || '';
        
        closeAllMenus();
        document.getElementById('avisoModal').style.display = 'block';
    }
    
    function closeModal() {
        document.getElementById('avisoModal').style.display = 'none';
    }
    
    // Dropdown menus
    function toggleAvisoMenu(id) {
        closeAllMenus();
        const menu = document.getElementById(id);
        menu.style.display = menu.style.display === 'block' ? 'none' : 'block';
    }
    
    function closeAllMenus() {
        document.querySelectorAll('.aviso-dropdown').forEach(m => m.style.display = 'none');
    }
    
    window.onclick = function(e) {
        if (!e.target.closest('.aviso-dropdown') && !e.target.closest('[onclick*="toggleAvisoMenu"]')) {
            closeAllMenus();
        }
    }
</script>

<?php renderAppFooter(); ?>