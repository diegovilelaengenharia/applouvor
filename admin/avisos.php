<?php
// admin/avisos.php
require_once '../includes/auth.php';
require_once '../includes/db.php';
require_once '../includes/layout.php';

// ==========================================
// PROCESSAR AÇÕES (CREATE, UPDATE, DELETE)
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
                    $_POST['message'],
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
                    $_POST['message'],
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
// BUSCAR TODOS OS AVISOS
// ==========================================
$filterPriority = $_GET['priority'] ?? 'all';
$filterType = $_GET['type'] ?? 'all';
$showArchived = isset($_GET['archived']) && $_GET['archived'] === '1';

$sql = "
    SELECT a.*, u.name as author_name
    FROM avisos a
    JOIN users u ON a.created_by = u.id
    WHERE 1=1
";

// Filter by archived status
if ($showArchived) {
    $sql .= " AND a.archived_at IS NOT NULL";
} else {
    $sql .= " AND a.archived_at IS NULL";
}

// Filter by priority
if ($filterPriority !== 'all') {
    $sql .= " AND a.priority = :priority";
}

// Filter by type
if ($filterType !== 'all') {
    $sql .= " AND a.type = :type";
}

$sql .= " ORDER BY a.created_at DESC";

$stmt = $pdo->prepare($sql);
if ($filterPriority !== 'all') {
    $stmt->bindParam(':priority', $filterPriority);
}
if ($filterType !== 'all') {
    $stmt->bindParam(':type', $filterType);
}
$stmt->execute();
$avisos = $stmt->fetchAll(PDO::FETCH_ASSOC);

renderAppHeader('Avisos');
?>

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
    <!-- Navigation Row -->
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px;">
        <?php
        $backLink = 'index.php';
        $backText = 'Voltar';
        if (isset($_GET['from']) && $_GET['from'] === 'lider') {
            $backLink = 'lider.php';
            $backText = 'Painel Líder';
        }
        ?>
        <a href="<?= $backLink ?>" class="ripple" style="
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
            <i data-lucide="arrow-left" style="width: 16px;"></i> <?= $backText ?>
        </a>

        <?php if ($_SESSION['user_role'] === 'admin'): ?>
            <button onclick="openModal('modal-create')" class="ripple" style="
            padding: 10px 20px;
            border-radius: 50px; 
            display: flex; 
            align-items: center; 
            justify-content: center; 
            gap: 8px;
            color: white; 
            background: rgba(255,255,255,0.2); 
            border: 1px solid rgba(255,255,255,0.3);
            font-weight: 600;
            font-size: 0.9rem;
            cursor: pointer;
            backdrop-filter: blur(4px);
        ">
                <i data-lucide="plus" style="width: 16px;"></i> Novo
            </button>
        <?php endif; ?>
    </div>

    <div style="display: flex; justify-content: space-between; align-items: flex-start;">
        <div>
            <h1 style="color: white; margin: 0; font-size: 2rem; font-weight: 800; letter-spacing: -0.5px;">Quadro de Avisos</h1>
            <p style="color: rgba(255,255,255,0.9); margin-top: 4px; font-weight: 500; font-size: 0.95rem;">Atualizações do Ministério</p>
        </div>
    </div>
</div>

<div class="container fade-in-up">

    <!-- Tabs: Ativos / Arquivados -->
    <div style="display: flex; gap: 8px; margin-bottom: 16px; border-bottom: 2px solid var(--border-subtle); padding-bottom: 0;">
        <a href="avisos.php" class="ripple" style="
            padding: 12px 20px;
            border-bottom: 3px solid <?= !$showArchived ? 'var(--primary-green)' : 'transparent' ?>;
            color: <?= !$showArchived ? 'var(--primary-green)' : 'var(--text-secondary)' ?>;
            text-decoration: none;
            font-weight: 700;
            font-size: 0.95rem;
            margin-bottom: -2px;
        ">
            📌 Ativos
        </a>
        <a href="avisos.php?archived=1" class="ripple" style="
            padding: 12px 20px;
            border-bottom: 3px solid <?= $showArchived ? 'var(--primary-green)' : 'transparent' ?>;
            color: <?= $showArchived ? 'var(--primary-green)' : 'var(--text-secondary)' ?>;
            text-decoration: none;
            font-weight: 700;
            font-size: 0.95rem;
            margin-bottom: -2px;
        ">
            📦 Arquivados
        </a>
    </div>

    <!-- Filtros de Tipo -->
    <div style="display: flex; gap: 8px; margin-bottom: 12px; overflow-x: auto; padding-bottom: 4px;">
        <a href="avisos.php?type=all<?= $showArchived ? '&archived=1' : '' ?>" class="ripple" style="
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
        <a href="avisos.php?type=general<?= $showArchived ? '&archived=1' : '' ?>" class="ripple" style="
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
        <a href="avisos.php?type=event<?= $showArchived ? '&archived=1' : '' ?>" class="ripple" style="
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
        <a href="avisos.php?type=schedule<?= $showArchived ? '&archived=1' : '' ?>" class="ripple" style="
            white-space: nowrap;
            padding: 8px 16px;
            border-radius: 20px;
            background: <?= $filterType === 'schedule' ? '#DBEAFE' : 'transparent' ?>;
            border: 1px solid <?= $filterType === 'schedule' ? '#3B82F6' : 'var(--border-subtle)' ?>;
            color: <?= $filterType === 'schedule' ? '#1E40AF' : '#3B82F6' ?>;
            text-decoration: none;
            font-size: 0.85rem;
            font-weight: 600;
        ">
            📅 Escala
        </a>
        <a href="avisos.php?type=music<?= $showArchived ? '&archived=1' : '' ?>" class="ripple" style="
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
        <a href="avisos.php?type=spiritual<?= $showArchived ? '&archived=1' : '' ?>" class="ripple" style="
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
        <a href="avisos.php?type=urgent<?= $showArchived ? '&archived=1' : '' ?>" class="ripple" style="
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

    <!-- Filtros de Prioridade -->
    <div style="display: flex; gap: 8px; margin-bottom: 24px; overflow-x: auto; padding-bottom: 4px;">
        <a href="avisos.php?priority=all<?= $filterType !== 'all' ? '&type=' . $filterType : '' ?><?= $showArchived ? '&archived=1' : '' ?>" class="ripple" style="
            white-space: nowrap;
            padding: 8px 16px;
            border-radius: 20px;
            background: <?= $filterPriority === 'all' ? 'var(--bg-secondary)' : 'transparent' ?>;
            border: 1px solid <?= $filterPriority === 'all' ? 'var(--border-focus)' : 'var(--border-subtle)' ?>;
            color: var(--text-primary);
            box-shadow: <?= $filterPriority === 'all' ? 'var(--shadow-sm)' : 'none' ?>;
            text-decoration: none;
            font-size: 0.85rem;
            font-weight: 600;
        ">
            Todos
        </a>
        <a href="avisos.php?priority=urgent<?= $filterType !== 'all' ? '&type=' . $filterType : '' ?><?= $showArchived ? '&archived=1' : '' ?>" class="ripple" style="
            white-space: nowrap;
            padding: 8px 16px;
            border-radius: 20px;
            background: <?= $filterPriority === 'urgent' ? '#FEF2F2' : 'transparent' ?>;
            border: 1px solid <?= $filterPriority === 'urgent' ? '#EF4444' : 'var(--border-subtle)' ?>;
            color: <?= $filterPriority === 'urgent' ? '#B91C1C' : '#EF4444' ?>;
            text-decoration: none;
            font-size: 0.85rem;
            font-weight: 600;
        ">
            🔴 Urgente
        </a>
        <a href="avisos.php?priority=important<?= $filterType !== 'all' ? '&type=' . $filterType : '' ?><?= $showArchived ? '&archived=1' : '' ?>" class="ripple" style="
            white-space: nowrap;
            padding: 8px 16px;
            border-radius: 20px;
            background: <?= $filterPriority === 'important' ? '#FFFBEB' : 'transparent' ?>;
            border: 1px solid <?= $filterPriority === 'important' ? '#F59E0B' : 'var(--border-subtle)' ?>;
            color: <?= $filterPriority === 'important' ? '#B45309' : '#F59E0B' ?>;
            text-decoration: none;
            font-size: 0.85rem;
            font-weight: 600;
        ">
            🟡 Importante
        </a>
        <a href="avisos.php?priority=info<?= $filterType !== 'all' ? '&type=' . $filterType : '' ?><?= $showArchived ? '&archived=1' : '' ?>" class="ripple" style="
            white-space: nowrap;
            padding: 8px 16px;
            border-radius: 20px;
            background: <?= $filterPriority === 'info' ? '#EFF6FF' : 'transparent' ?>;
            border: 1px solid <?= $filterPriority === 'info' ? '#3B82F6' : 'var(--border-subtle)' ?>;
            color: <?= $filterPriority === 'info' ? '#1D4ED8' : '#3B82F6' ?>;
            text-decoration: none;
            font-size: 0.85rem;
            font-weight: 600;
        ">
            🔵 Info
        </a>
    </div>

    <!-- Lista de Avisos -->
    <?php if (!empty($avisos)): ?>
        <div style="display: flex; flex-direction: column; gap: 16px;">
            <?php foreach ($avisos as $aviso): ?>
                <div class="aviso-card <?= $aviso['priority'] ?>">
                    <div class="aviso-header">
                        <h5 style="margin: 0; font-size: 1.1rem; font-weight: 700; color: var(--text-primary); line-height: 1.4;">
                            <?= htmlspecialchars($aviso['title']) ?>
                        </h5>
                        <?php if ($_SESSION['user_role'] === 'admin' || $_SESSION['user_id'] == $aviso['created_by']): ?>
                            <div style="position: relative;">
                                <button class="btn-icon ripple" onclick="toggleMenu(this, 'menu-<?= $aviso['id'] ?>')" style="width: 32px; height: 32px;">
                                    <i data-lucide="more-vertical" style="width: 16px;"></i>
                                </button>
                                <div id="menu-<?= $aviso['id'] ?>" class="dropdown-menu" style="right: 0; min-width: 160px;">
                                    <button onclick='openEditModal(<?= json_encode($aviso) ?>)' class="dropdown-item">
                                        <i data-lucide="edit-2" style="width: 14px;"></i> Editar
                                    </button>
                                    <?php if (!$showArchived): ?>
                                        <form method="POST" onsubmit="return confirm('Arquivar este aviso?')">
                                            <input type="hidden" name="action" value="archive">
                                            <input type="hidden" name="id" value="<?= $aviso['id'] ?>">
                                            <button type="submit" class="dropdown-item">
                                                <i data-lucide="archive" style="width: 14px;"></i> Arquivar
                                            </button>
                                        </form>
                                    <?php else: ?>
                                        <form method="POST" onsubmit="return confirm('Desarquivar este aviso?')">
                                            <input type="hidden" name="action" value="unarchive">
                                            <input type="hidden" name="id" value="<?= $aviso['id'] ?>">
                                            <button type="submit" class="dropdown-item">
                                                <i data-lucide="archive-restore" style="width: 14px;"></i> Desarquivar
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                    <form method="POST" onsubmit="return confirm('Excluir este aviso permanentemente?')">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="id" value="<?= $aviso['id'] ?>">
                                        <button type="submit" class="dropdown-item text-danger">
                                            <i data-lucide="trash-2" style="width: 14px;"></i> Excluir
                                        </button>
                                    </form>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- Type & Priority Badges -->
                    <div style="margin-bottom: 12px; display: flex; gap: 8px; flex-wrap: wrap;">
                        <?php
                        // Type badge
                        $typeLabels = [
                            'general' => ['icon' => '📢', 'label' => 'Geral', 'color' => '#6B7280'],
                            'event' => ['icon' => '🎉', 'label' => 'Evento', 'color' => '#F59E0B'],
                            'schedule' => ['icon' => '📅', 'label' => 'Escala', 'color' => '#3B82F6'],
                            'music' => ['icon' => '🎵', 'label' => 'Música', 'color' => '#EC4899'],
                            'spiritual' => ['icon' => '🙏', 'label' => 'Espiritual', 'color' => '#6366F1'],
                            'urgent' => ['icon' => '🚨', 'label' => 'Urgente', 'color' => '#EF4444']
                        ];
                        $type = $typeLabels[$aviso['type']] ?? $typeLabels['general'];
                        ?>
                        <span style="
                            background: <?= $type['color'] ?>15; 
                            border: 1px solid <?= $type['color'] ?>;
                            padding: 4px 10px;
                            border-radius: 12px;
                            font-size: 0.75rem;
                            font-weight: 600;
                            color: <?= $type['color'] ?>;
                        ">
                            <?= $type['icon'] ?> <?= $type['label'] ?>
                        </span>

                        <?php
                        // Priority badge
                        $priorityLabels = [
                            'urgent' => '🔴 Urgente',
                            'important' => '🟡 Importante',
                            'info' => '🔵 Info'
                        ];
                        ?>
                        <span class="badge-pill priority-<?= $aviso['priority'] ?>" style="
                            background: var(--bg-tertiary); 
                            border: 1px solid var(--border-subtle);
                            padding: 4px 10px;
                            border-radius: 12px;
                            font-size: 0.75rem;
                            color: var(--text-secondary);
                        ">
                            <?= $priorityLabels[$aviso['priority']] ?? 'Info' ?>
                        </span>
                    </div>

                    <p style="color: var(--text-secondary); font-size: 0.95rem; line-height: 1.6; margin-bottom: 16px;">
                        <?= nl2br(htmlspecialchars($aviso['message'])) ?>
                    </p>

                    <div style="
                    display: flex; 
                    align-items: center; 
                    gap: 12px; 
                    padding-top: 12px; 
                    border-top: 1px solid var(--border-subtle);
                    font-size: 0.85rem;
                    color: var(--text-muted);
                ">
                        <div style="display: flex; align-items: center; gap: 6px;">
                            <div style="
                            width: 24px; height: 24px; 
                            border-radius: 50%; 
                            background: var(--bg-tertiary);
                            display: flex; align-items: center; justify-content: center;
                            font-weight: 700; color: var(--text-secondary);
                            border: 1px solid var(--border-subtle);
                        ">
                                <?= substr($aviso['author_name'], 0, 1) ?>
                            </div>
                            <span style="font-weight: 600; color: var(--text-secondary);"><?= htmlspecialchars($aviso['author_name']) ?></span>
                        </div>
                        <span>•</span>
                        <div><?= date('d/m/Y \à\s H:i', strtotime($aviso['created_at'])) ?></div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <!-- Empty State Moderno -->
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

</div>

<!-- Modal: Criar Aviso -->
<div id="modal-create" class="modal-overlay" onclick="closeModal('modal-create')">
    <div class="modal-content" onclick="event.stopPropagation()">
        <div class="modal-header">
            <h3 style="margin: 0; font-size: 1.2rem; color: var(--text-primary);">Novo Aviso</h3>
            <button onclick="closeModal('modal-create')" class="btn-icon ripple">
                <i data-lucide="x" style="width: 20px;"></i>
            </button>
        </div>
        <form method="POST" style="padding: 24px;">
            <input type="hidden" name="action" value="create">

            <div class="form-group">
                <label class="form-label">Título</label>
                <input type="text" name="title" class="form-input" placeholder="Ex: Ensaio de Sábado" required autofocus>
            </div>

            <div class="form-group">
                <label class="form-label">Mensagem</label>
                <textarea name="message" class="form-input" rows="5" placeholder="Digite os detalhes..." required style="resize: vertical;"></textarea>
            </div>

            <div class="form-group">
                <label class="form-label">Tipo</label>
                <select name="type" class="form-select" style="
                    padding: 12px;
                    border-radius: 12px;
                    border: 1px solid var(--border-subtle);
                    background: var(--bg-primary);
                    color: var(--text-primary);
                    font-size: 0.95rem;
                ">
                    <option value="general">📢 Geral</option>
                    <option value="event">🎉 Evento</option>
                    <option value="schedule">📅 Escala</option>
                    <option value="music">🎵 Música</option>
                    <option value="spiritual">🙏 Espiritual</option>
                    <option value="urgent">🚨 Urgente</option>
                </select>
            </div>

            <div class="form-group">
                <label class="form-label">Prioridade</label>
                <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 8px;">
                    <label style="cursor: pointer;">
                        <input type="radio" name="priority" value="info" checked style="display: none;" onchange="updatePriorityUI(this)">
                        <div class="priority-option selected" style="
                            text-align: center;
                            padding: 10px;
                            border-radius: 12px;
                            border: 2px solid #3B82F6;
                            background: #EFF6FF;
                            color: #3B82F6;
                            font-weight: 600;
                            font-size: 0.9rem;
                            transition: all 0.2s;
                        ">🔵 Info</div>
                    </label>
                    <label style="cursor: pointer;">
                        <input type="radio" name="priority" value="important" style="display: none;" onchange="updatePriorityUI(this)">
                        <div class="priority-option" style="
                            text-align: center;
                            padding: 10px;
                            border-radius: 12px;
                            border: 1px solid var(--border-subtle);
                            background: var(--bg-primary);
                            color: var(--text-secondary);
                            font-weight: 600;
                            font-size: 0.9rem;
                            transition: all 0.2s;
                        ">🟡 Importante</div>
                    </label>
                    <label style="cursor: pointer;">
                        <input type="radio" name="priority" value="urgent" style="display: none;" onchange="updatePriorityUI(this)">
                        <div class="priority-option" style="
                            text-align: center;
                            padding: 10px;
                            border-radius: 12px;
                            border: 1px solid var(--border-subtle);
                            background: var(--bg-primary);
                            color: var(--text-secondary);
                            font-weight: 600;
                            font-size: 0.9rem;
                            transition: all 0.2s;
                        ">🔴 Urgente</div>
                    </label>
                </div>
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
<div id="modal-edit" class="modal-overlay" onclick="closeModal('modal-edit')">
    <div class="modal-content" onclick="event.stopPropagation()">
        <div class="modal-header">
            <h3 style="margin: 0; font-size: 1.2rem; color: var(--text-primary);">Editar Aviso</h3>
            <button onclick="closeModal('modal-edit')" class="btn-icon ripple">
                <i data-lucide="x" style="width: 20px;"></i>
            </button>
        </div>
        <form method="POST" style="padding: 24px;">
            <input type="hidden" name="action" value="update">
            <input type="hidden" name="id" id="edit-id">

            <div class="form-group">
                <label class="form-label">Título</label>
                <input type="text" name="title" id="edit-title" class="form-input" required>
            </div>

            <div class="form-group">
                <label class="form-label">Mensagem</label>
                <textarea name="message" id="edit-message" class="form-input" rows="5" required style="resize: vertical;"></textarea>
            </div>

            <div class="form-group">
                <label class="form-label">Tipo</label>
                <select name="type" id="edit-type" class="form-select" style="
                    padding: 12px;
                    border-radius: 12px;
                    border: 1px solid var(--border-subtle);
                    background: var(--bg-primary);
                    color: var(--text-primary);
                    font-size: 0.95rem;
                ">
                    <option value="general">📢 Geral</option>
                    <option value="event">🎉 Evento</option>
                    <option value="schedule">📅 Escala</option>
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

<script>
    function openModal(modalId) {
        document.getElementById(modalId).classList.add('active');
    }

    function closeModal(modalId) {
        document.getElementById(modalId).classList.remove('active');
    }

    function openEditModal(aviso) {
        document.getElementById('edit-id').value = aviso.id;
        document.getElementById('edit-title').value = aviso.title;
        document.getElementById('edit-message').value = aviso.message;
        document.getElementById('edit-priority').value = aviso.priority;
        document.getElementById('edit-type').value = aviso.type || 'general';
        openModal('modal-edit');
    }

    function toggleMenu(btn, menuId) {
        // Fechar outros menus
        document.querySelectorAll('.dropdown-menu').forEach(el => {
            if (el.id !== menuId) el.classList.remove('active');
        });

        const menu = document.getElementById(menuId);
        menu.classList.toggle('active');
        event.stopPropagation();
    }

    // Fechar menus ao clicar fora
    document.addEventListener('click', () => {
        document.querySelectorAll('.dropdown-menu').forEach(el => el.classList.remove('active'));
    });

    // Auto-Open Modal from URL
    window.addEventListener('load', () => {
        const urlParams = new URLSearchParams(window.location.search);
        if (urlParams.get('action_modal') === 'create') {
            openModal('modal-create');
            // Clean URL
            window.history.replaceState({}, document.title, window.location.pathname);
        }
    });

    // Priority Selection UI (Radio logic visual fix)
    function updatePriorityUI(radio) {
        // Reset all
        document.querySelectorAll('.priority-option').forEach(el => {
            el.style.border = '1px solid var(--border-subtle)';
            el.style.background = 'var(--bg-primary)';
            el.style.color = 'var(--text-secondary)';
        });

        // Set active
        const active = radio.nextElementSibling;
        const val = radio.value;
        if (val === 'info') {
            active.style.borderColor = '#3B82F6';
            active.style.background = '#EFF6FF';
            active.style.color = '#3B82F6';
        } else if (val === 'important') {
            active.style.borderColor = '#F59E0B';
            active.style.background = '#FFFBEB';
            active.style.color = '#B45309';
        } else if (val === 'urgent') {
            active.style.borderColor = '#EF4444';
            active.style.background = '#FEF2F2';
            active.style.color = '#B91C1C';
        }
    }
</script>

<?php renderAppFooter(); ?>