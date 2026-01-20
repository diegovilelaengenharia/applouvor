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
                    INSERT INTO avisos (title, message, priority, created_by)
                    VALUES (?, ?, ?, ?)
                ");
                $stmt->execute([
                    $_POST['title'],
                    $_POST['message'],
                    $_POST['priority'],
                    $_SESSION['user_id']
                ]);
                header('Location: avisos.php?success=created');
                exit;

            case 'update':
                $stmt = $pdo->prepare("
                    UPDATE avisos 
                    SET title = ?, message = ?, priority = ?
                    WHERE id = ?
                ");
                $stmt->execute([
                    $_POST['title'],
                    $_POST['message'],
                    $_POST['priority'],
                    $_POST['id']
                ]);
                header('Location: avisos.php?success=updated');
                exit;

            case 'delete':
                $stmt = $pdo->prepare("DELETE FROM avisos WHERE id = ?");
                $stmt->execute([$_POST['id']]);
                header('Location: avisos.php?success=deleted');
                exit;
        }
    }
}

// ==========================================
// BUSCAR TODOS OS AVISOS
// ==========================================
$filterPriority = $_GET['priority'] ?? 'all';

$sql = "
    SELECT a.*, u.name as author_name
    FROM avisos a
    JOIN users u ON a.created_by = u.id
";

if ($filterPriority !== 'all') {
    $sql .= " WHERE a.priority = :priority";
}

$sql .= " ORDER BY a.created_at DESC";

$stmt = $pdo->prepare($sql);
if ($filterPriority !== 'all') {
    $stmt->bindParam(':priority', $filterPriority);
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

        <button onclick="openModal('modal-create')" class="ripple" style="
            padding: 10px 20px;
            border-radius: 50px; 
            display: flex; 
            align-items: center; 
            justify-content: center; 
            gap: 8px;
            color: white; 
            background: #FFC107; 
            border: none;
            font-weight: 700;
            font-size: 0.9rem;
            box-shadow: 0 2px 8px rgba(255,193,7,0.3);
            cursor: pointer;
        ">
            <i data-lucide="plus" style="width: 16px;"></i> Novo Aviso
        </button>
    </div>

    <div style="display: flex; justify-content: space-between; align-items: flex-start;">
        <div>
            <h1 style="color: white; margin: 0; font-size: 2rem; font-weight: 800; letter-spacing: -0.5px;">Avisos</h1>
            <p style="color: rgba(255,255,255,0.9); margin-top: 4px; font-weight: 500; font-size: 0.95rem;">Louvor PIB Oliveira</p>
        </div>
    </div>
</div>

<div class="container fade-in-up">

    <!-- Filtros -->
    <div style="display: flex; gap: 8px; margin-bottom: 20px; flex-wrap: wrap;">
        <a href="avisos.php?priority=all" class="ripple" style="
            padding: 8px 16px;
            border-radius: 20px;
            background: <?= $filterPriority === 'all' ? 'var(--bg-tertiary)' : 'transparent' ?>;
            border: 1px solid var(--border-subtle);
            color: var(--text-primary);
            text-decoration: none;
            font-size: 0.85rem;
            font-weight: 600;
        ">
            Todos
        </a>
        <a href="avisos.php?priority=urgent" class="ripple" style="
            padding: 8px 16px;
            border-radius: 20px;
            background: <?= $filterPriority === 'urgent' ? 'rgba(220, 53, 69, 0.1)' : 'transparent' ?>;
            border: 1px solid <?= $filterPriority === 'urgent' ? '#DC3545' : 'var(--border-subtle)' ?>;
            color: <?= $filterPriority === 'urgent' ? '#DC3545' : 'var(--text-primary)' ?>;
            text-decoration: none;
            font-size: 0.85rem;
            font-weight: 600;
        ">
            🔴 Urgente
        </a>
        <a href="avisos.php?priority=important" class="ripple" style="
            padding: 8px 16px;
            border-radius: 20px;
            background: <?= $filterPriority === 'important' ? 'rgba(255, 193, 7, 0.1)' : 'transparent' ?>;
            border: 1px solid <?= $filterPriority === 'important' ? '#FFC107' : 'var(--border-subtle)' ?>;
            color: <?= $filterPriority === 'important' ? '#FFC107' : 'var(--text-primary)' ?>;
            text-decoration: none;
            font-size: 0.85rem;
            font-weight: 600;
        ">
            🟡 Importante
        </a>
        <a href="avisos.php?priority=info" class="ripple" style="
            padding: 8px 16px;
            border-radius: 20px;
            background: <?= $filterPriority === 'info' ? 'rgba(13, 110, 253, 0.1)' : 'transparent' ?>;
            border: 1px solid <?= $filterPriority === 'info' ? '#0D6EFD' : 'var(--border-subtle)' ?>;
            color: <?= $filterPriority === 'info' ? '#0D6EFD' : 'var(--text-primary)' ?>;
            text-decoration: none;
            font-size: 0.85rem;
            font-weight: 600;
        ">
            🔵 Info
        </a>
    </div>

    <!-- Lista de Avisos -->
    <?php if (!empty($avisos)): ?>
        <?php foreach ($avisos as $aviso): ?>
            <div class="aviso-item <?= $aviso['priority'] ?>" style="margin-bottom: 16px;">
                <div class="aviso-header">
                    <h5 class="aviso-title"><?= htmlspecialchars($aviso['title']) ?></h5>
                    <span class="priority-badge priority-<?= $aviso['priority'] ?>">
                        <?php
                        $priorityLabels = [
                            'urgent' => '🔴 Urgente',
                            'important' => '🟡 Importante',
                            'info' => '🔵 Info'
                        ];
                        echo $priorityLabels[$aviso['priority']] ?? 'Info';
                        ?>
                    </span>
                </div>
                <p class="aviso-message"><?= nl2br(htmlspecialchars($aviso['message'])) ?></p>
                <div class="aviso-meta">
                    <i data-lucide="user" style="width: 12px;"></i>
                    <?= htmlspecialchars($aviso['author_name']) ?>
                    <span>•</span>
                    <i data-lucide="clock" style="width: 12px;"></i>
                    <?= date('d/m/Y H:i', strtotime($aviso['created_at'])) ?>
                </div>

                <!-- Ações (apenas para admin ou criador) -->
                <?php if ($_SESSION['user_role'] === 'admin' || $_SESSION['user_id'] == $aviso['created_by']): ?>
                    <div style="display: flex; gap: 8px; margin-top: 12px; padding-top: 12px; border-top: 1px solid var(--border-subtle);">
                        <button onclick='openEditModal(<?= json_encode($aviso) ?>)' class="ripple" style="
                            padding: 6px 12px;
                            border-radius: 8px;
                            background: var(--bg-tertiary);
                            border: none;
                            color: var(--text-primary);
                            font-size: 0.8rem;
                            font-weight: 600;
                            cursor: pointer;
                            display: flex;
                            align-items: center;
                            gap: 4px;
                        ">
                            <i data-lucide="edit-2" style="width: 14px;"></i>
                            Editar
                        </button>
                        <form method="POST" style="display: inline;" onsubmit="return confirm('Excluir este aviso?')">
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="id" value="<?= $aviso['id'] ?>">
                            <button type="submit" class="ripple" style="
                                padding: 6px 12px;
                                border-radius: 8px;
                                background: rgba(220, 53, 69, 0.1);
                                border: none;
                                color: #DC3545;
                                font-size: 0.8rem;
                                font-weight: 600;
                                cursor: pointer;
                                display: flex;
                                align-items: center;
                                gap: 4px;
                            ">
                                <i data-lucide="trash-2" style="width: 14px;"></i>
                                Excluir
                            </button>
                        </form>
                    </div>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    <?php else: ?>
        <!-- Empty State -->
        <div class="empty-state">
            <div class="empty-state-icon">
                <i data-lucide="bell-off" style="width: 40px; color: var(--text-muted);"></i>
            </div>
            <h4 class="empty-state-title">Nenhum aviso encontrado</h4>
            <p class="empty-state-text">
                <?= $filterPriority !== 'all' ? 'Não há avisos com esta prioridade' : 'Crie o primeiro aviso para o ministério' ?>
            </p>
        </div>
    <?php endif; ?>

</div>

<!-- Modal: Criar Aviso -->
<div id="modal-create" class="modal-overlay" onclick="closeModal('modal-create')">
    <div class="modal-content" onclick="event.stopPropagation()" style="max-width: 500px;">
        <div class="modal-header">
            <h3 style="margin: 0; font-size: 1.3rem;">Novo Aviso</h3>
            <button onclick="closeModal('modal-create')" class="ripple" style="
                width: 32px;
                height: 32px;
                border-radius: 8px;
                border: none;
                background: var(--bg-tertiary);
                cursor: pointer;
                display: flex;
                align-items: center;
                justify-content: center;
            ">
                <i data-lucide="x" style="width: 18px;"></i>
            </button>
        </div>
        <form method="POST" style="padding: 20px;">
            <input type="hidden" name="action" value="create">

            <div style="margin-bottom: 16px;">
                <label style="display: block; font-weight: 600; margin-bottom: 8px; color: var(--text-primary);">Título</label>
                <input type="text" name="title" required style="
                    width: 100%;
                    padding: 12px;
                    border: 1px solid var(--border-subtle);
                    border-radius: 8px;
                    font-size: 0.95rem;
                    background: var(--bg-secondary);
                    color: var(--text-primary);
                ">
            </div>

            <div style="margin-bottom: 16px;">
                <label style="display: block; font-weight: 600; margin-bottom: 8px; color: var(--text-primary);">Mensagem</label>
                <textarea name="message" required rows="4" style="
                    width: 100%;
                    padding: 12px;
                    border: 1px solid var(--border-subtle);
                    border-radius: 8px;
                    font-size: 0.95rem;
                    background: var(--bg-secondary);
                    color: var(--text-primary);
                    resize: vertical;
                "></textarea>
            </div>

            <div style="margin-bottom: 20px;">
                <label style="display: block; font-weight: 600; margin-bottom: 8px; color: var(--text-primary);">Prioridade</label>
                <select name="priority" required style="
                    width: 100%;
                    padding: 12px;
                    border: 1px solid var(--border-subtle);
                    border-radius: 8px;
                    font-size: 0.95rem;
                    background: var(--bg-secondary);
                    color: var(--text-primary);
                ">
                    <option value="info">🔵 Informativo</option>
                    <option value="important">🟡 Importante</option>
                    <option value="urgent">🔴 Urgente</option>
                </select>
            </div>

            <button type="submit" class="ripple" style="
                width: 100%;
                padding: 14px;
                background: linear-gradient(135deg, #FFC107 0%, #FFCA2C 100%);
                color: white;
                border: none;
                border-radius: 8px;
                font-weight: 700;
                font-size: 1rem;
                cursor: pointer;
            ">
                Criar Aviso
            </button>
        </form>
    </div>
</div>

<!-- Modal: Editar Aviso -->
<div id="modal-edit" class="modal-overlay" onclick="closeModal('modal-edit')">
    <div class="modal-content" onclick="event.stopPropagation()" style="max-width: 500px;">
        <div class="modal-header">
            <h3 style="margin: 0; font-size: 1.3rem;">Editar Aviso</h3>
            <button onclick="closeModal('modal-edit')" class="ripple" style="
                width: 32px;
                height: 32px;
                border-radius: 8px;
                border: none;
                background: var(--bg-tertiary);
                cursor: pointer;
                display: flex;
                align-items: center;
                justify-content: center;
            ">
                <i data-lucide="x" style="width: 18px;"></i>
            </button>
        </div>
        <form method="POST" style="padding: 20px;">
            <input type="hidden" name="action" value="update">
            <input type="hidden" name="id" id="edit-id">

            <div style="margin-bottom: 16px;">
                <label style="display: block; font-weight: 600; margin-bottom: 8px; color: var(--text-primary);">Título</label>
                <input type="text" name="title" id="edit-title" required style="
                    width: 100%;
                    padding: 12px;
                    border: 1px solid var(--border-subtle);
                    border-radius: 8px;
                    font-size: 0.95rem;
                    background: var(--bg-secondary);
                    color: var(--text-primary);
                ">
            </div>

            <div style="margin-bottom: 16px;">
                <label style="display: block; font-weight: 600; margin-bottom: 8px; color: var(--text-primary);">Mensagem</label>
                <textarea name="message" id="edit-message" required rows="4" style="
                    width: 100%;
                    padding: 12px;
                    border: 1px solid var(--border-subtle);
                    border-radius: 8px;
                    font-size: 0.95rem;
                    background: var(--bg-secondary);
                    color: var(--text-primary);
                    resize: vertical;
                "></textarea>
            </div>

            <div style="margin-bottom: 20px;">
                <label style="display: block; font-weight: 600; margin-bottom: 8px; color: var(--text-primary);">Prioridade</label>
                <select name="priority" id="edit-priority" required style="
                    width: 100%;
                    padding: 12px;
                    border: 1px solid var(--border-subtle);
                    border-radius: 8px;
                    font-size: 0.95rem;
                    background: var(--bg-secondary);
                    color: var(--text-primary);
                ">
                    <option value="info">🔵 Informativo</option>
                    <option value="important">🟡 Importante</option>
                    <option value="urgent">🔴 Urgente</option>
                </select>
            </div>

            <button type="submit" class="ripple" style="
                width: 100%;
                padding: 14px;
                background: linear-gradient(135deg, #047857 0%, #065f46 100%);
                color: white;
                border: none;
                border-radius: 8px;
                font-weight: 700;
                font-size: 1rem;
                cursor: pointer;
            ">
                Salvar Alterações
            </button>
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
        openModal('modal-edit');
    }
</script>

<?php renderAppFooter(); ?>