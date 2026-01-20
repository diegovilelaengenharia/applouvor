<?php
// admin/avisos.php
require_once '../includes/auth.php';
require_once '../includes/db.php';
require_once '../includes/layout.php';

// ==========================================
// PROCESSAR AÇÕES (CREATE, UPDATE, DELETE, ARCHIVE)
// ==========================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'create':
                $stmt = $pdo->prepare("
                    INSERT INTO avisos (title, message, priority, type, created_by)
                    VALUES (?, ?, ?, ?, ?)
                ");
                $stmt->execute([
                    $_POST['title'],
                    $_POST['message'], // Agora suporta HTML do Quill
                    $_POST['priority'],
                    $_POST['type'] ?? 'general',
                    $_SESSION['user_id']
                ]);
                header('Location: avisos.php?success=created');
                exit;

            case 'update':
                $stmt = $pdo->prepare("
                    UPDATE avisos 
                    SET title = ?, message = ?, priority = ?, type = ?
                    WHERE id = ?
                ");
                $stmt->execute([
                    $_POST['title'],
                    $_POST['message'], // HTML do Quill
                    $_POST['priority'],
                    $_POST['type'] ?? 'general',
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
                $stmt = $pdo->prepare("
                    UPDATE avisos 
                    SET archived_at = CURRENT_TIMESTAMP
                    WHERE id = ?
                ");
                $stmt->execute([$_POST['id']]);
                header('Location: avisos.php?success=archived');
                exit;

            case 'unarchive':
                $stmt = $pdo->prepare("
                    UPDATE avisos 
                    SET archived_at = NULL
                    WHERE id = ?
                ");
                $stmt->execute([$_POST['id']]);
                header('Location: avisos.php?success=unarchived');
                exit;
        }
    }
}

// ==========================================
// BUSCAR AVISOS
// ==========================================
$search = $_GET['search'] ?? '';
$filterPriority = $_GET['priority'] ?? 'all';
$filterType = $_GET['type'] ?? 'all';
$showArchived = isset($_GET['archived']) && $_GET['archived'] === '1';

$sql = "
    SELECT a.*, u.name as author_name
    FROM avisos a
    JOIN users u ON a.created_by = u.id
    WHERE 1=1
";

$params = [];

// Filter by archived status
if ($showArchived) {
    $sql .= " AND a.archived_at IS NOT NULL";
} else {
    $sql .= " AND a.archived_at IS NULL";
}

// Filter by search
if (!empty($search)) {
    $sql .= " AND (a.title LIKE ? OR a.message LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

// Filter by priority
if ($filterPriority !== 'all') {
    $sql .= " AND a.priority = ?";
    $params[] = $filterPriority;
}

// Filter by type
if ($filterType !== 'all') {
    $sql .= " AND a.type = ?";
    $params[] = $filterType;
}

$sql .= " ORDER BY a.created_at DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$avisos = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Count avisos
$countActive = $pdo->query("SELECT COUNT(*) FROM avisos WHERE archived_at IS NULL")->fetchColumn();
$countArchived = $pdo->query("SELECT COUNT(*) FROM avisos WHERE archived_at IS NOT NULL")->fetchColumn();

renderAppHeader('Avisos');
?>

<!-- Quill.js CSS -->
<link href="https://cdn.quilljs.com/1.3.6/quill.snow.css" rel="stylesheet">

<style>
    /* Quill Editor Styles */
    .ql-container {
        font-size: 0.95rem;
        min-height: 200px;
        border-bottom-left-radius: 12px;
        border-bottom-right-radius: 12px;
    }

    .ql-toolbar {
        border-top-left-radius: 12px;
        border-top-right-radius: 12px;
        background: var(--bg-tertiary);
    }

    .ql-editor {
        min-height: 200px;
    }

    /* Notice Card Styles */
    .notice-card {
        background: var(--bg-secondary);
        border: 1px solid var(--border-subtle);
        border-radius: 16px;
        overflow: hidden;
        margin-bottom: 12px;
        transition: all 0.2s;
        display: flex;
    }

    .notice-card:hover {
        border-color: var(--accent-interactive);
        box-shadow: var(--shadow-md);
    }

    .notice-type-bar {
        width: 6px;
        flex-shrink: 0;
    }

    .notice-content {
        flex: 1;
        padding: 16px;
    }

    .notice-header {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        margin-bottom: 12px;
    }

    .notice-title {
        font-size: 1.1rem;
        font-weight: 700;
        color: var(--text-primary);
        margin: 0;
        line-height: 1.4;
    }

    .notice-badges {
        display: flex;
        gap: 8px;
        flex-wrap: wrap;
        margin-bottom: 12px;
    }

    .notice-badge {
        padding: 4px 10px;
        border-radius: 12px;
        font-size: 0.75rem;
        font-weight: 600;
        border: 1px solid;
    }

    .notice-preview {
        color: var(--text-secondary);
        font-size: 0.9rem;
        line-height: 1.6;
        margin-bottom: 12px;
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
        overflow: hidden;
    }

    .notice-footer {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding-top: 12px;
        border-top: 1px solid var(--border-subtle);
        font-size: 0.85rem;
        color: var(--text-muted);
    }

    .notice-author {
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .author-avatar {
        width: 24px;
        height: 24px;
        border-radius: 50%;
        background: var(--bg-tertiary);
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 700;
        color: var(--text-secondary);
        border: 1px solid var(--border-subtle);
        font-size: 0.75rem;
    }

    /* Maintenance Modal */
    .maintenance-modal {
        text-align: center;
        padding: 40px 20px;
    }

    .maintenance-icon {
        width: 80px;
        height: 80px;
        background: var(--bg-tertiary);
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 0 auto 20px;
        color: var(--primary-green);
    }
</style>

<!-- Hero Header -->
<div style="
    background: linear-gradient(135deg, #047857 0%, #065f46 100%); 
    margin: -24px -16px 32px -16px; 
    padding: 32px 24px 64px 24px; 
    border-radius: 0 0 32px 32px; 
    box-shadow: var(--shadow-md);
    position: relative;
    overflow: visible;
">
    <!-- Navigation Buttons (Top Right) -->
    <div style="display: flex; justify-content: flex-end; margin-bottom: 16px;">
        <?php renderGlobalNavButtons(); ?>
    </div>
    <!-- Navigation Row -->
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px;">
        <a href="index.php" class="ripple" style="
            padding: 10px 20px;
            border-radius: 50px; 
            display: flex; 
            align-items: center; 
            justify-content: center; 
            gap: 8px;
            color: #047857; 
            background: white; 
            text-decoration: none;
            font-weight: 700;
            font-size: 0.9rem;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        ">
            <i data-lucide="arrow-left" style="width: 16px;"></i> Voltar
        </a>

        <div style="display: flex; gap: 8px; align-items: center;">
            <!-- Notification Bell (Maintenance) -->
            <button onclick="showMaintenanceModal()" class="ripple" style="
                background: rgba(255,255,255,0.2); 
                border: none; 
                width: 44px; 
                height: 44px; 
                border-radius: 12px; 
                display: flex; 
                align-items: center; 
                justify-content: center;
                color: white;
                backdrop-filter: blur(4px);
                cursor: pointer;
                position: relative;
            ">
                <i data-lucide="bell" style="width: 20px;"></i>
                <span style="
                    position: absolute;
                    top: 8px;
                    right: 8px;
                    width: 8px;
                    height: 8px;
                    background: #F59E0B;
                    border-radius: 50%;
                    border: 2px solid rgba(255,255,255,0.3);
                "></span>
            </button>

            <!-- User Avatar -->
            <div onclick="openSheet('sheet-perfil')" class="ripple" style="
                width: 40px; 
                height: 40px; 
                border-radius: 50%; 
                background: rgba(255,255,255,0.2); 
                display: flex; 
                align-items: center; 
                justify-content: center; 
                overflow: hidden; 
                cursor: pointer;
                border: 2px solid rgba(255,255,255,0.3);
            ">
                <?php if (!empty($_SESSION['user_avatar'])): ?>
                    <img src="../assets/uploads/<?= htmlspecialchars($_SESSION['user_avatar']) ?>" style="width: 100%; height: 100%; object-fit: cover;">
                <?php else: ?>
                    <span style="font-weight: 700; font-size: 0.9rem; color: white;">
                        <?= substr($_SESSION['user_name'] ?? 'U', 0, 1) ?>
                    </span>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div id="avisos-hero-header" style="display: flex; justify-content: space-between; align-items: flex-start; gap: 12px;">
        <div style="flex: 1; min-width: 0;">
            <h1 style="color: white; margin: 0; font-size: 2rem; font-weight: 800; letter-spacing: -0.5px;">Quadro de Avisos</h1>
            <p style="color: rgba(255,255,255,0.9); margin-top: 4px; font-weight: 500; font-size: 0.95rem;">Atualizações do Ministério</p>
        </div>
        <?php if ($_SESSION['user_role'] === 'admin'): ?>
            <button id="btn-create-aviso" onclick="openModal('modal-create')" class="ripple" style="
                background: linear-gradient(135deg, #FFC107 0%, #FFCA2C 100%);
                border: none;
                padding: 12px 24px;
                border-radius: 50px;
                display: flex;
                align-items: center;
                justify-content: center;
                gap: 8px;
                color: white;
                font-weight: 700;
                font-size: 0.95rem;
                cursor: pointer;
                box-shadow: 0 4px 16px rgba(255, 193, 7, 0.4);
                transition: all 0.3s ease;
                flex-shrink: 0;
            " onmouseover="this.style.transform='scale(1.05)'" onmouseout="this.style.transform='scale(1)'">
                <i data-lucide="plus" style="width: 20px;"></i> <span class="btn-text">Novo Aviso</span>
            </button>
        <?php endif; ?>
    </div>

    <!-- Floating Search Bar -->
    <div style="position: absolute; bottom: -28px; left: 20px; right: 20px; z-index: 10;">
        <form method="GET" style="margin: 0;">
            <input type="hidden" name="archived" value="<?= $showArchived ? '1' : '0' ?>">
            <?php if ($filterType !== 'all'): ?>
                <input type="hidden" name="type" value="<?= $filterType ?>">
            <?php endif; ?>
            <?php if ($filterPriority !== 'all'): ?>
                <input type="hidden" name="priority" value="<?= $filterPriority ?>">
            <?php endif; ?>
            <div style="
                background: var(--bg-secondary); 
                border-radius: 16px; 
                padding: 6px; 
                box-shadow: 0 10px 30px rgba(0,0,0,0.15); 
                display: flex; 
                align-items: center;
                border: 1px solid rgba(0,0,0,0.05);
            ">
                <div style="
                    width: 44px; 
                    height: 44px; 
                    display: flex; 
                    align-items: center; 
                    justify-content: center; 
                    color: var(--primary-green);
                ">
                    <i data-lucide="search" style="width: 22px;"></i>
                </div>

                <input
                    type="text"
                    name="search"
                    value="<?= htmlspecialchars($search) ?>"
                    placeholder="Buscar avisos..."
                    style="
                        border: none; 
                        background: transparent; 
                        padding: 12px 0; 
                        width: 100%; 
                        font-size: 1rem; 
                        color: var(--text-primary);
                        outline: none;
                        font-weight: 500;
                    ">

                <?php if (!empty($search)): ?>
                    <a href="?archived=<?= $showArchived ? '1' : '0' ?>" style="
                        width: 40px; 
                        height: 40px; 
                        display: flex; 
                        align-items: center; 
                        justify-content: center; 
                        color: var(--text-muted); 
                        text-decoration: none;
                        cursor: pointer;
                    ">
                        <i data-lucide="x" style="width: 18px;"></i>
                    </a>
                <?php endif; ?>
            </div>
        </form>
    </div>
</div>

<!-- Tabs: Ativos / Arquivados -->
<div style="background: var(--bg-tertiary); padding: 6px; border-radius: 16px; display: flex; margin-bottom: 20px;">
    <a href="avisos.php" class="ripple" style="flex: 1; text-align: center; padding: 12px; border-radius: 12px; font-size: 0.9rem; font-weight: 700; text-decoration: none; transition: all 0.2s; <?= !$showArchived ? 'background: var(--primary-green); color: white; box-shadow: var(--shadow-sm);' : 'color: var(--text-secondary);' ?>">
        📌 Ativos (<?= $countActive ?>)
    </a>
    <a href="avisos.php?archived=1" class="ripple" style="flex: 1; text-align: center; padding: 12px; border-radius: 12px; font-size: 0.9rem; font-weight: 700; text-decoration: none; transition: all 0.2s; <?= $showArchived ? 'background: var(--primary-green); color: white; box-shadow: var(--shadow-sm);' : 'color: var(--text-secondary);' ?>">
        📦 Arquivados (<?= $countArchived ?>)
    </a>
</div>

<!-- Filtros de Tipo -->
<div style="display: flex; gap: 8px; margin-bottom: 12px; overflow-x: auto; padding-bottom: 4px;">
    <a href="avisos.php?type=all<?= $showArchived ? '&archived=1' : '' ?><?= !empty($search) ? '&search=' . urlencode($search) : '' ?>" class="ripple" style="
        white-space: nowrap;
        padding: 8px 16px;
        border-radius: 20px;
        background: <?= $filterType === 'all' ? 'var(--bg-secondary)' : 'transparent' ?>;
        border: 1px solid <?= $filterType === 'all' ? 'var(--border-focus)' : 'var(--border-subtle)' ?>;
        color: var(--text-primary);
        box-shadow: <?= $filterType === 'all' ? 'var(--shadow-sm)' : 'none' ?>;
        text-decoration: none;
        font-size: 0.85rem;
        font-weight: 600;
    ">
        Todos
    </a>
    <a href="avisos.php?type=general<?= $showArchived ? '&archived=1' : '' ?><?= !empty($search) ? '&search=' . urlencode($search) : '' ?>" class="ripple" style="
        white-space: nowrap;
        padding: 8px 16px;
        border-radius: 20px;
        background: <?= $filterType === 'general' ? '#F3F4F6' : 'transparent' ?>;
        border: 1px solid <?= $filterType === 'general' ? '#6B7280' : 'var(--border-subtle)' ?>;
        color: <?= $filterType === 'general' ? '#374151' : '#6B7280' ?>;
        text-decoration: none;
        font-size: 0.85rem;
        font-weight: 600;
    ">
        📢 Geral
    </a>
    <a href="avisos.php?type=event<?= $showArchived ? '&archived=1' : '' ?><?= !empty($search) ? '&search=' . urlencode($search) : '' ?>" class="ripple" style="
        white-space: nowrap;
        padding: 8px 16px;
        border-radius: 20px;
        background: <?= $filterType === 'event' ? '#FEF3C7' : 'transparent' ?>;
        border: 1px solid <?= $filterType === 'event' ? '#F59E0B' : 'var(--border-subtle)' ?>;
        color: <?= $filterType === 'event' ? '#B45309' : '#F59E0B' ?>;
        text-decoration: none;
        font-size: 0.85rem;
        font-weight: 600;
    ">
        🎉 Evento
    </a>
    <a href="avisos.php?type=music<?= $showArchived ? '&archived=1' : '' ?><?= !empty($search) ? '&search=' . urlencode($search) : '' ?>" class="ripple" style="
        white-space: nowrap;
        padding: 8px 16px;
        border-radius: 20px;
        background: <?= $filterType === 'music' ? '#FCE7F3' : 'transparent' ?>;
        border: 1px solid <?= $filterType === 'music' ? '#EC4899' : 'var(--border-subtle)' ?>;
        color: <?= $filterType === 'music' ? '#BE185D' : '#EC4899' ?>;
        text-decoration: none;
        font-size: 0.85rem;
        font-weight: 600;
    ">
        🎵 Música
    </a>
    <a href="avisos.php?type=spiritual<?= $showArchived ? '&archived=1' : '' ?><?= !empty($search) ? '&search=' . urlencode($search) : '' ?>" class="ripple" style="
        white-space: nowrap;
        padding: 8px 16px;
        border-radius: 20px;
        background: <?= $filterType === 'spiritual' ? '#E0E7FF' : 'transparent' ?>;
        border: 1px solid <?= $filterType === 'spiritual' ? '#6366F1' : 'var(--border-subtle)' ?>;
        color: <?= $filterType === 'spiritual' ? '#4338CA' : '#6366F1' ?>;
        text-decoration: none;
        font-size: 0.85rem;
        font-weight: 600;
    ">
        🙏 Espiritual
    </a>
    <a href="avisos.php?type=urgent<?= $showArchived ? '&archived=1' : '' ?><?= !empty($search) ? '&search=' . urlencode($search) : '' ?>" class="ripple" style="
        white-space: nowrap;
        padding: 8px 16px;
        border-radius: 20px;
        background: <?= $filterType === 'urgent' ? '#FEE2E2' : 'transparent' ?>;
        border: 1px solid <?= $filterType === 'urgent' ? '#EF4444' : 'var(--border-subtle)' ?>;
        color: <?= $filterType === 'urgent' ? '#B91C1C' : '#EF4444' ?>;
        text-decoration: none;
        font-size: 0.85rem;
        font-weight: 600;
    ">
        🚨 Urgente
    </a>
</div>

<!-- Lista de Avisos -->
<?php if (!empty($avisos)): ?>
    <div style="display: flex; flex-direction: column;">
        <?php foreach ($avisos as $aviso):
            // Type colors
            $typeColors = [
                'general' => '#6B7280',
                'event' => '#F59E0B',
                'music' => '#EC4899',
                'spiritual' => '#6366F1',
                'urgent' => '#EF4444'
            ];
            $typeColor = $typeColors[$aviso['type']] ?? '#6B7280';

            // Type labels
            $typeLabels = [
                'general' => ['icon' => '📢', 'label' => 'Geral'],
                'event' => ['icon' => '🎉', 'label' => 'Evento'],
                'music' => ['icon' => '🎵', 'label' => 'Música'],
                'spiritual' => ['icon' => '🙏', 'label' => 'Espiritual'],
                'urgent' => ['icon' => '🚨', 'label' => 'Urgente']
            ];
            $type = $typeLabels[$aviso['type']] ?? $typeLabels['general'];

            // Priority labels
            $priorityLabels = [
                'urgent' => '🔴 Urgente',
                'important' => '🟡 Importante',
                'info' => '🔵 Info'
            ];

            // Preview text (strip HTML tags and limit to 150 chars)
            $preview = strip_tags($aviso['message']);
            $preview = mb_substr($preview, 0, 150) . (mb_strlen($preview) > 150 ? '...' : '');
        ?>
            <div class="notice-card">
                <div class="notice-type-bar" style="background: <?= $typeColor ?>;"></div>
                <div class="notice-content">
                    <div class="notice-header">
                        <h3 class="notice-title"><?= htmlspecialchars($aviso['title']) ?></h3>
                    </div>

                    <div class="notice-badges">
                        <span class="notice-badge" style="
                            background: <?= $typeColor ?>15; 
                            border-color: <?= $typeColor ?>;
                            color: <?= $typeColor ?>;
                        ">
                            <?= $type['icon'] ?> <?= $type['label'] ?>
                        </span>
                        <span class="notice-badge" style="
                            background: var(--bg-tertiary); 
                            border-color: var(--border-subtle);
                            color: var(--text-secondary);
                        ">
                            <?= $priorityLabels[$aviso['priority']] ?? 'Info' ?>
                        </span>
                    </div>

                    <div class="notice-preview"><?= htmlspecialchars($preview) ?></div>

                    <div class="notice-footer">
                        <div class="notice-author">
                            <div class="author-avatar">
                                <?= substr($aviso['author_name'], 0, 1) ?>
                            </div>
                            <span style="font-weight: 600; color: var(--text-secondary);"><?= htmlspecialchars($aviso['author_name']) ?></span>
                            <span>•</span>
                            <span><?= date('d/m/Y às H:i', strtotime($aviso['created_at'])) ?></span>
                        </div>

                        <?php if ($_SESSION['user_role'] === 'admin' || $_SESSION['user_id'] == $aviso['created_by']): ?>
                            <div style="display: flex; gap: 8px;">
                                <!-- Editar (Amarelo) -->
                                <button onclick='openEditModal(<?= json_encode($aviso) ?>)' class="ripple" style="
                                    padding: 6px 12px;
                                    border-radius: 8px;
                                    background: #FFC107;
                                    color: white;
                                    border: none;
                                    font-size: 0.75rem;
                                    font-weight: 600;
                                    cursor: pointer;
                                    display: flex;
                                    align-items: center;
                                    gap: 4px;
                                    transition: all 0.2s;
                                " onmouseover="this.style.background='#FFB300'" onmouseout="this.style.background='#FFC107'">
                                    <i data-lucide="edit-2" style="width: 12px;"></i> Editar
                                </button>

                                <!-- Arquivar/Desarquivar (Azul) -->
                                <?php if (!$showArchived): ?>
                                    <form method="POST" onsubmit="return confirm('Arquivar este aviso?')" style="margin: 0;">
                                        <input type="hidden" name="action" value="archive">
                                        <input type="hidden" name="id" value="<?= $aviso['id'] ?>">
                                        <button type="submit" class="ripple" style="
                                            padding: 6px 12px;
                                            border-radius: 8px;
                                            background: #2196F3;
                                            color: white;
                                            border: none;
                                            font-size: 0.75rem;
                                            font-weight: 600;
                                            cursor: pointer;
                                            display: flex;
                                            align-items: center;
                                            gap: 4px;
                                            transition: all 0.2s;
                                        " onmouseover="this.style.background='#1976D2'" onmouseout="this.style.background='#2196F3'">
                                            <i data-lucide="archive" style="width: 12px;"></i> Arquivar
                                        </button>
                                    </form>
                                <?php else: ?>
                                    <form method="POST" onsubmit="return confirm('Desarquivar este aviso?')" style="margin: 0;">
                                        <input type="hidden" name="action" value="unarchive">
                                        <input type="hidden" name="id" value="<?= $aviso['id'] ?>">
                                        <button type="submit" class="ripple" style="
                                            padding: 6px 12px;
                                            border-radius: 8px;
                                            background: #2196F3;
                                            color: white;
                                            border: none;
                                            font-size: 0.75rem;
                                            font-weight: 600;
                                            cursor: pointer;
                                            display: flex;
                                            align-items: center;
                                            gap: 4px;
                                            transition: all 0.2s;
                                        " onmouseover="this.style.background='#1976D2'" onmouseout="this.style.background='#2196F3'">
                                            <i data-lucide="archive-restore" style="width: 12px;"></i> Desarquivar
                                        </button>
                                    </form>
                                <?php endif; ?>

                                <!-- Deletar (Vermelho) -->
                                <form method="POST" onsubmit="return confirm('Excluir este aviso permanentemente?')" style="margin: 0;">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="id" value="<?= $aviso['id'] ?>">
                                    <button type="submit" class="ripple" style="
                                        padding: 6px 12px;
                                        border-radius: 8px;
                                        background: #EF4444;
                                        color: white;
                                        border: none;
                                        font-size: 0.75rem;
                                        font-weight: 600;
                                        cursor: pointer;
                                        display: flex;
                                        align-items: center;
                                        gap: 4px;
                                        transition: all 0.2s;
                                    " onmouseover="this.style.background='#DC2626'" onmouseout="this.style.background='#EF4444'">
                                        <i data-lucide="trash-2" style="width: 12px;"></i> Deletar
                                    </button>
                                </form>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php else: ?>
    <!-- Empty State -->
    <div style="
        text-align: center; 
        padding: 60px 20px; 
        background: var(--bg-secondary); 
        border-radius: 20px; 
        border: 2px dashed var(--border-subtle);
    ">
        <div style="
            width: 64px; height: 64px; 
            background: var(--bg-tertiary); 
            border-radius: 50%; 
            display: flex; align-items: center; justify-content: center;
            margin: 0 auto 16px;
            color: var(--text-muted);
        ">
            <i data-lucide="bell" style="width: 32px; height: 32px;"></i>
        </div>
        <h3 style="margin-bottom: 8px; color: var(--text-primary);">Tudo tranquilo por aqui</h3>
        <p style="color: var(--text-secondary);">Nenhum aviso encontrado no momento.</p>
    </div>
<?php endif; ?>



<!-- Modal: Criar Aviso -->
<div id="modal-create" class="bottom-sheet-overlay" onclick="closeModal('modal-create')">
    <div class="bottom-sheet-content" onclick="event.stopPropagation()" style="max-height: 90vh; overflow-y: auto;">
        <div class="sheet-header">
            <button onclick="closeModal('modal-create')" class="btn-icon ripple" style="position: absolute; left: 16px; top: 16px;">
                <i data-lucide="x"></i>
            </button>
            Novo Aviso
        </div>
        <form method="POST" style="padding: 24px;" onsubmit="return submitCreateForm()">
            <input type="hidden" name="action" value="create">
            <input type="hidden" name="message" id="create-message-hidden">

            <div class="form-group">
                <label class="form-label">Título</label>
                <input type="text" name="title" class="form-input" placeholder="Ex: Ensaio de Sábado" required autofocus>
            </div>

            <div class="form-group">
                <label class="form-label">Mensagem</label>
                <div id="editor-create"></div>
            </div>

            <div class="form-group">
                <label class="form-label">Tipo</label>
                <select name="type" class="form-select">
                    <option value="general">📢 Geral</option>
                    <option value="event">🎉 Evento</option>
                    <option value="music">🎵 Música</option>
                    <option value="spiritual">🙏 Espiritual</option>
                    <option value="urgent">🚨 Urgente</option>
                </select>
            </div>

            <div class="form-group">
                <label class="form-label">Prioridade</label>
                <select name="priority" class="form-select">
                    <option value="info">🔵 Informativo</option>
                    <option value="important">🟡 Importante</option>
                    <option value="urgent">🔴 Urgente</option>
                </select>
            </div>

            <div style="margin-top: 32px;">
                <button type="submit" class="btn-primary ripple w-full" style="justify-content: center;">
                    <i data-lucide="send"></i> Publicar Aviso
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Modal: Editar Aviso -->
<div id="modal-edit" class="bottom-sheet-overlay" onclick="closeModal('modal-edit')">
    <div class="bottom-sheet-content" onclick="event.stopPropagation()" style="max-height: 90vh; overflow-y: auto;">
        <div class="sheet-header">
            <button onclick="closeModal('modal-edit')" class="btn-icon ripple" style="position: absolute; left: 16px; top: 16px;">
                <i data-lucide="x"></i>
            </button>
            Editar Aviso
        </div>
        <form method="POST" style="padding: 24px;" onsubmit="return submitEditForm()">
            <input type="hidden" name="action" value="update">
            <input type="hidden" name="id" id="edit-id">
            <input type="hidden" name="message" id="edit-message-hidden">

            <div class="form-group">
                <label class="form-label">Título</label>
                <input type="text" name="title" id="edit-title" class="form-input" required>
            </div>

            <div class="form-group">
                <label class="form-label">Mensagem</label>
                <div id="editor-edit"></div>
            </div>

            <div class="form-group">
                <label class="form-label">Tipo</label>
                <select name="type" id="edit-type" class="form-select">
                    <option value="general">📢 Geral</option>
                    <option value="event">🎉 Evento</option>
                    <option value="music">🎵 Música</option>
                    <option value="spiritual">🙏 Espiritual</option>
                    <option value="urgent">🚨 Urgente</option>
                </select>
            </div>

            <div class="form-group">
                <label class="form-label">Prioridade</label>
                <select name="priority" id="edit-priority" class="form-select">
                    <option value="info">🔵 Informativo</option>
                    <option value="important">🟡 Importante</option>
                    <option value="urgent">🔴 Urgente</option>
                </select>
            </div>

            <div style="margin-top: 32px;">
                <button type="submit" class="btn-primary ripple w-full" style="justify-content: center; background: var(--primary-gray-dark);">
                    <i data-lucide="save"></i> Salvar Alterações
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Modal: Manutenção (Notification Bell) -->
<div id="modal-maintenance" class="bottom-sheet-overlay" onclick="closeModal('modal-maintenance')">
    <div class="bottom-sheet-content" onclick="event.stopPropagation()">
        <div class="maintenance-modal">
            <div class="maintenance-icon">
                <i data-lucide="wrench" style="width: 40px; height: 40px;"></i>
            </div>
            <h3 style="margin-bottom: 12px; color: var(--text-primary);">Funcionalidade em Desenvolvimento</h3>
            <p style="color: var(--text-secondary); margin-bottom: 24px; max-width: 300px; margin-left: auto; margin-right: auto;">
                O sistema de notificações está sendo implementado e estará disponível em breve!
            </p>
            <button onclick="closeModal('modal-maintenance')" class="btn-primary ripple" style="justify-content: center;">
                Entendi
            </button>
        </div>
    </div>
</div>

<!-- Quill.js Script -->
<script src="https://cdn.quilljs.com/1.3.6/quill.js"></script>

<script>
    // Initialize Quill editors
    const quillCreate = new Quill('#editor-create', {
        theme: 'snow',
        placeholder: 'Digite a mensagem do aviso...',
        modules: {
            toolbar: [
                ['bold', 'italic', 'underline'],
                [{
                    'list': 'ordered'
                }, {
                    'list': 'bullet'
                }],
                ['link'],
                ['clean']
            ]
        }
    });

    const quillEdit = new Quill('#editor-edit', {
        theme: 'snow',
        placeholder: 'Digite a mensagem do aviso...',
        modules: {
            toolbar: [
                ['bold', 'italic', 'underline'],
                [{
                    'list': 'ordered'
                }, {
                    'list': 'bullet'
                }],
                ['link'],
                ['clean']
            ]
        }
    });

    // Submit handlers
    function submitCreateForm() {
        const html = quillCreate.root.innerHTML;
        document.getElementById('create-message-hidden').value = html;
        return true;
    }

    function submitEditForm() {
        const html = quillEdit.root.innerHTML;
        document.getElementById('edit-message-hidden').value = html;
        return true;
    }

    // Modal functions
    function openModal(modalId) {
        document.getElementById(modalId).classList.add('active');
        lucide.createIcons();
    }

    function closeModal(modalId) {
        document.getElementById(modalId).classList.remove('active');
    }

    function showMaintenanceModal() {
        openModal('modal-maintenance');
    }

    function openEditModal(aviso) {
        document.getElementById('edit-id').value = aviso.id;
        document.getElementById('edit-title').value = aviso.title;
        document.getElementById('edit-type').value = aviso.type || 'general';
        document.getElementById('edit-priority').value = aviso.priority;

        // Set Quill content
        quillEdit.root.innerHTML = aviso.message;

        openModal('modal-edit');
    }

    function toggleMenu(btn, menuId) {
        // Close other menus
        document.querySelectorAll('.dropdown-menu').forEach(el => {
            if (el.id !== menuId) el.classList.remove('active');
        });

        const menu = document.getElementById(menuId);
        menu.classList.toggle('active');
        event.stopPropagation();
    }

    // Close menus when clicking outside
    document.addEventListener('click', () => {
        document.querySelectorAll('.dropdown-menu').forEach(el => el.classList.remove('active'));
    });

    // Real-time search
    const searchInput = document.querySelector('input[name="search"]');
    if (searchInput) {
        let searchTimeout;
        searchInput.addEventListener('input', function() {
            clearTimeout(searchTimeout);
            const value = this.value.trim();

            searchTimeout = setTimeout(() => {
                if (value.length >= 2 || value.length === 0) {
                    this.form.submit();
                }
            }, 500);
        });
    }
</script>

<?php renderAppFooter(); ?>