<?php
// admin/avisos.php
require_once '../includes/auth.php';
require_once '../includes/db.php';
require_once '../includes/layout.php';

checkLogin();

// --- LÓGICA DE POST (CRUD) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        // Obter ID do usuário logado
        $userId = $_SESSION['user_id'] ?? 1; // Fallback temporário

        switch ($_POST['action']) {
            case 'create':
                $stmt = $pdo->prepare("INSERT INTO avisos (title, message, priority, type, created_by, created_at) VALUES (?, ?, ?, ?, ?, NOW())");
                $stmt->execute([
                    $_POST['title'],
                    $_POST['message'], // HTML do Quill
                    $_POST['priority'],
                    $_POST['type'],
                    $userId
                ]);
                header('Location: avisos.php?success=created');
                exit;

            case 'update':
                $stmt = $pdo->prepare("UPDATE avisos SET title = ?, message = ?, priority = ?, type = ? WHERE id = ?");
                $stmt->execute([
                    $_POST['title'],
                    $_POST['message'],
                    $_POST['priority'],
                    $_POST['type'],
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
$filterType = $_GET['type'] ?? 'all';
$search = $_GET['search'] ?? '';

// Construir Query
$sql = "SELECT a.*, u.name as author_name FROM avisos a LEFT JOIN users u ON a.created_by = u.id WHERE 1=1";
$params = [];

if ($showArchived) {
    $sql .= " AND a.archived_at IS NOT NULL";
} else {
    $sql .= " AND a.archived_at IS NULL";
}

if ($filterType !== 'all') {
    $sql .= " AND a.type = ?";
    $params[] = $filterType;
}

if (!empty($search)) {
    $sql .= " AND (a.title LIKE ? OR a.message LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

$sql .= " ORDER BY a.created_at DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$avisos = $stmt->fetchAll(PDO::FETCH_ASSOC);

renderAppHeader('Avisos');
?>

<!-- Quill CSS -->
<link href="https://cdn.quilljs.com/1.3.6/quill.snow.css" rel="stylesheet">

<div class="container" style="padding-top: 24px; max-width: 800px; margin: 0 auto;">

    <!-- Header Principal -->
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px;">
        <div>
            <h1 style="font-size: 1.5rem; font-weight: 800; color: #1e293b; margin: 0;">Mural de Avisos</h1>
            <p style="color: #64748b; margin-top: 4px;">Fique por dentro do que acontece</p>
        </div>

        <?php if ($_SESSION['user_role'] === 'admin'): ?>
            <button onclick="openCreateModal()" class="ripple" style="
                background: linear-gradient(135deg, #059669 0%, #047857 100%); 
                color: white; border: none; padding: 12px 20px; 
                border-radius: 12px; font-weight: 700; font-size: 0.9rem; 
                display: flex; align-items: center; gap: 8px; 
                box-shadow: 0 4px 12px rgba(4, 120, 87, 0.25);
                cursor: pointer;
            ">
                <i data-lucide="plus" style="width: 18px;"></i>
                <span style="display: none; @media(min-width: 480px) { display: inline; }">Novo Aviso</span>
            </button>
        <?php endif; ?>
    </div>

    <!-- Filtros de Navegação -->
    <div style="margin-bottom: 24px;">

        <!-- Busca -->
        <div style="margin-bottom: 16px; position: relative;">
            <i data-lucide="search" style="position: absolute; left: 16px; top: 50%; transform: translateY(-50%); color: #94a3b8; width: 20px;"></i>
            <form onsubmit="return true;"> <!-- Submit via GET padrão -->
                <?php if ($showArchived): ?><input type="hidden" name="archived" value="1"><?php endif; ?>
                <?php if ($filterType !== 'all'): ?><input type="hidden" name="type" value="<?= $filterType ?>"><?php endif; ?>
                <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" placeholder="Buscar avisos..."
                    style="
                        width: 100%; padding: 12px 12px 12px 48px; border-radius: 12px; 
                        border: 1px solid #e2e8f0; font-size: 1rem; outline: none; 
                        transition: border 0.2s; background: white;
                    "
                    onfocus="this.style.borderColor='#3b82f6'" onblur="this.style.borderColor='#e2e8f0'">
            </form>
        </div>

        <!-- Categorias (Filtros Rápidos) -->
        <div style="display: flex; gap: 8px; overflow-x: auto; padding-bottom: 4px; scrollbar-width: none;">
            <?php
            $types = [
                'all' => ['label' => 'Todos', 'icon' => ''],
                'general' => ['label' => 'Geral', 'icon' => '📢'],
                'event' => ['label' => 'Eventos', 'icon' => '🎉'],
                'music' => ['label' => 'Música', 'icon' => '🎵'],
                'spiritual' => ['label' => 'Espiritual', 'icon' => '🙏'],
                'urgent' => ['label' => 'Urgente', 'icon' => '🚨'],
            ];
            foreach ($types as $key => $data):
                $isActive = $filterType === $key;
                $bg = $isActive ? '#1e293b' : 'white';
                $color = $isActive ? 'white' : '#64748b';
            ?>
                <a href="?type=<?= $key ?><?= $showArchived ? '&archived=1' : '' ?><?= $search ? '&search=' . $search : '' ?>" class="ripple" style="
                    white-space: nowrap; padding: 8px 16px; border-radius: 20px; 
                    background: <?= $bg ?>; color: <?= $color ?>; 
                    border: 1px solid <?= $isActive ? '#1e293b' : '#e2e8f0' ?>;
                    text-decoration: none; font-size: 0.85rem; font-weight: 600;
                    display: flex; align-items: center; gap: 6px;
                ">
                    <?= $data['icon'] ?> <?= $data['label'] ?>
                </a>
            <?php endforeach; ?>
        </div>

        <div style="margin-top: 12px; display: flex; align-items: center; gap: 12px; font-size: 0.85rem;">
            <a href="?archived=<?= $showArchived ? '0' : '1' ?>" style="color: #64748b; font-weight: 600; text-decoration: none; display: flex; align-items: center; gap: 4px;">
                <i data-lucide="<?= $showArchived ? 'rotate-ccw' : 'archive' ?>" style="width: 14px;"></i>
                <?= $showArchived ? 'Ver Ativos' : 'Ver Arquivados' ?>
            </a>
        </div>

    </div>

    <!-- Lista de Avisos -->
    <div style="display: flex; flex-direction: column; gap: 16px;">
        <?php if (empty($avisos)): ?>
            <div style="text-align: center; padding: 48px; background: white; border-radius: 16px; border: 1px solid #e2e8f0; color: #64748b;">
                <i data-lucide="inbox" style="width: 48px; height: 48px; margin-bottom: 12px; opacity: 0.5;"></i>
                <p>Nenhum aviso encontrado.</p>
            </div>
        <?php endif; ?>

        <?php foreach ($avisos as $aviso):
            $priorityColors = [
                'urgent' => ['bg' => '#fef2f2', 'text' => '#dc2626', 'border' => '#fecaca', 'label' => 'Urgente'],
                'important' => ['bg' => '#fffbeb', 'text' => '#d97706', 'border' => '#fde68a', 'label' => 'Importante'],
                'info' => ['bg' => '#eff6ff', 'text' => '#2563eb', 'border' => '#bfdbfe', 'label' => 'Info'],
            ];
            $pStyle = $priorityColors[$aviso['priority']] ?? $priorityColors['info'];
        ?>
            <div class="notice-card" style="background: white; border-radius: 16px; border: 1px solid #e2e8f0; padding: 20px; position: relative;">

                <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 12px;">
                    <span style="
                    background: <?= $pStyle['bg'] ?>; color: <?= $pStyle['text'] ?>; border: 1px solid <?= $pStyle['border'] ?>;
                    padding: 4px 10px; border-radius: 8px; font-size: 0.75rem; font-weight: 700; text-transform: uppercase;
                ">
                        <?= $pStyle['label'] ?>
                    </span>

                    <?php if ($_SESSION['user_role'] === 'admin'): ?>
                        <div style="position: relative;">
                            <button onclick="toggleMenu('menu-<?= $aviso['id'] ?>')" class="btn-icon ripple" style="color: #94a3b8; padding: 4px;">
                                <i data-lucide="more-horizontal" style="width: 20px;"></i>
                            </button>
                            <!-- Menu Dropdown -->
                            <div id="menu-<?= $aviso['id'] ?>" class="dropdown-menu" style="display: none; position: absolute; right: 0; background: white; border: 1px solid #e2e8f0; border-radius: 8px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1); width: 140px; z-index: 10;">
                                <button onclick='openEditModal(<?= json_encode($aviso) ?>)' style="width: 100%; padding: 10px; text-align: left; background: none; border: none; cursor: pointer; display: flex; align-items: center; gap: 8px; font-size: 0.85rem;">
                                    <i data-lucide="edit-3" style="width: 14px;"></i> Editar
                                </button>

                                <form method="POST" style="margin:0;">
                                    <input type="hidden" name="action" value="<?= $showArchived ? 'unarchive' : 'archive' ?>">
                                    <input type="hidden" name="id" value="<?= $aviso['id'] ?>">
                                    <button type="submit" style="width: 100%; padding: 10px; text-align: left; background: none; border: none; cursor: pointer; display: flex; align-items: center; gap: 8px; font-size: 0.85rem; color: #475569;">
                                        <i data-lucide="<?= $showArchived ? 'rotate-ccw' : 'archive' ?>" style="width: 14px;"></i> <?= $showArchived ? 'Desarquivar' : 'Arquivar' ?>
                                    </button>
                                </form>

                                <form method="POST" onsubmit="return confirm('Excluir?')" style="margin:0;">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="id" value="<?= $aviso['id'] ?>">
                                    <button type="submit" style="width: 100%; padding: 10px; text-align: left; background: none; border: none; cursor: pointer; display: flex; align-items: center; gap: 8px; font-size: 0.85rem; color: #ef4444; border-top: 1px solid #f1f5f9;">
                                        <i data-lucide="trash-2" style="width: 14px;"></i> Excluir
                                    </button>
                                </form>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>

                <h2 style="font-size: 1.1rem; font-weight: 700; color: #1e293b; margin: 0 0 12px 0;">
                    <?= htmlspecialchars($aviso['title']) ?>
                </h2>

                <div class="ql-editor" style="padding: 0; color: #475569; font-size: 0.95rem; line-height: 1.6; max-height: 200px; overflow: hidden; position: relative;">
                    <?= $aviso['message'] ?> <!-- HTML Seguro vindo do Quill -->
                </div>

                <div style="margin-top: 16px; padding-top: 16px; border-top: 1px solid #f1f5f9; display: flex; justify-content: space-between; align-items: center; font-size: 0.8rem; color: #94a3b8;">
                    <div style="display: flex; align-items: center; gap: 6px;">
                        <i data-lucide="user" style="width: 14px;"></i>
                        <?= htmlspecialchars($aviso['author_name'] ?: 'Admin') ?>
                    </div>
                    <div>
                        <?= date('d/m H:i', strtotime($aviso['created_at'])) ?>
                    </div>
                </div>

            </div>
        <?php endforeach; ?>
    </div>

    <div style="height: 60px;"></div>
</div>

<!-- Modal Universal (Add/Edit) -->
<div id="avisoModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; z-index: 1000;">
    <div onclick="closeModal()" style="position: absolute; width: 100%; height: 100%; background: rgba(0,0,0,0.5); backdrop-filter: blur(2px);"></div>

    <div style="
        position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%);
        width: 90%; max-width: 600px; background: white; border-radius: 24px; padding: 24px;
        box-shadow: 0 20px 25px -5px rgba(0,0,0,0.1); max-height: 90vh; overflow-y: auto;
    ">
        <h2 id="modalTitle" style="margin: 0 0 16px 0; font-size: 1.25rem; font-weight: 800; color: #1e293b;">Novo Aviso</h2>
        <form method="POST" id="avisoForm" onsubmit="return prepareSubmit()">
            <input type="hidden" name="action" id="formAction" value="create">
            <input type="hidden" name="id" id="avisoId">
            <input type="hidden" name="message" id="hiddenMessage">

            <div class="form-group" style="margin-bottom: 16px;">
                <label class="form-label" style="display: block; font-weight: 700; color: #334155; margin-bottom: 6px;">Título</label>
                <input type="text" name="title" id="avisoTitle" required class="input-modern" style="width: 100%; padding: 12px; border-radius: 10px; border: 1px solid #cbd5e1; outline: none;">
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 16px;">
                <div>
                    <label class="form-label" style="display: block; font-weight: 700; color: #334155; margin-bottom: 6px;">Tipo</label>
                    <select name="type" id="avisoType" style="width: 100%; padding: 12px; border-radius: 10px; border: 1px solid #cbd5e1;">
                        <option value="general">Geral</option>
                        <option value="event">Evento</option>
                        <option value="music">Música</option>
                        <option value="spiritual">Espiritual</option>
                        <option value="urgent">Urgente</option>
                    </select>
                </div>
                <div>
                    <label class="form-label" style="display: block; font-weight: 700; color: #334155; margin-bottom: 6px;">Prioridade</label>
                    <select name="priority" id="avisoPriority" style="width: 100%; padding: 12px; border-radius: 10px; border: 1px solid #cbd5e1;">
                        <option value="info">Info</option>
                        <option value="important">Importante</option>
                        <option value="urgent">Urgente</option>
                    </select>
                </div>
            </div>

            <div class="form-group" style="margin-bottom: 24px;">
                <label class="form-label" style="display: block; font-weight: 700; color: #334155; margin-bottom: 6px;">Mensagem</label>
                <div id="editor" style="height: 150px; background: white;"></div>
            </div>

            <div style="display: flex; gap: 12px;">
                <button type="button" onclick="closeModal()" style="flex: 1; padding: 14px; border-radius: 12px; border: 1px solid #cbd5e1; background: white; font-weight: 600; cursor: pointer;">Cancelar</button>
                <button type="submit" style="flex: 2; padding: 14px; border-radius: 12px; border: none; background: #1e293b; color: white; font-weight: 700; cursor: pointer;">Salvar Aviso</button>
            </div>
        </form>
    </div>
</div>

<script src="https://cdn.quilljs.com/1.3.6/quill.js"></script>
<script>
    // Quill Init
    var quill = new Quill('#editor', {
        theme: 'snow',
        modules: {
            toolbar: [
                ['bold', 'italic', 'underline'],
                [{
                    'list': 'ordered'
                }, {
                    'list': 'bullet'
                }],
                ['link']
            ]
        }
    });

    function prepareSubmit() {
        document.getElementById('hiddenMessage').value = quill.root.innerHTML;
        return true;
    }

    // Modal
    function openCreateModal() {
        document.getElementById('modalTitle').innerText = 'Novo Aviso';
        document.getElementById('formAction').value = 'create';
        document.getElementById('avisoForm').reset();
        document.getElementById('avisoId').value = '';
        quill.setContents([]);
        document.getElementById('avisoModal').style.display = 'block';
    }

    function openEditModal(aviso) {
        document.getElementById('modalTitle').innerText = 'Editar Aviso';
        document.getElementById('formAction').value = 'update';
        document.getElementById('avisoId').value = aviso.id;
        document.getElementById('avisoTitle').value = aviso.title;
        document.getElementById('avisoType').value = aviso.type;
        document.getElementById('avisoPriority').value = aviso.priority;
        quill.root.innerHTML = aviso.message; // Load HTML

        closeAllMenus();
        document.getElementById('avisoModal').style.display = 'block';
    }

    function closeModal() {
        document.getElementById('avisoModal').style.display = 'none';
    }

    // Menus
    function toggleMenu(id) {
        closeAllMenus();
        const menu = document.getElementById(id);
        menu.style.display = menu.style.display === 'block' ? 'none' : 'block';
    }

    function closeAllMenus() {
        document.querySelectorAll('.dropdown-menu').forEach(el => el.style.display = 'none');
    }

    window.onclick = function(event) {
        if (!event.target.matches('.btn-icon') && !event.target.closest('.btn-icon')) {
            closeAllMenus();
        }
    }
</script>

<?php renderAppFooter(); ?>