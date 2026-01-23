<?php
// admin/avisos.php
require_once '../includes/auth.php';
require_once '../includes/db.php';
require_once '../includes/layout.php';

checkLogin();

// --- LÓGICA DE POST (CRUD) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        $userId = $_SESSION['user_id'] ?? 1;

        switch ($_POST['action']) {
            case 'create':
                $stmt = $pdo->prepare("INSERT INTO avisos (title, message, priority, type, target_audience, expires_at, created_by, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, NOW())");
                $stmt->execute([
                    $_POST['title'],
                    $_POST['message'], // HTML do Quill
                    $_POST['priority'],
                    $_POST['type'],
                    $_POST['target_audience'],
                    !empty($_POST['expires_at']) ? $_POST['expires_at'] : NULL,
                    $userId
                ]);
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

$sql .= " ORDER BY a.priority='urgent' DESC, a.created_at DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$avisos = $stmt->fetchAll(PDO::FETCH_ASSOC);

renderAppHeader('Avisos');
?>

<!-- Quill CSS -->
<link href="https://cdn.quilljs.com/1.3.6/quill.snow.css" rel="stylesheet">

<div class="container" style="padding-top: 24px; max-width: 800px; margin: 0 auto;">


    <?php
    // Header Limpo
    renderPageHeader('Mural de Avisos', 'Comunicação oficial e agenda');
    ?>

    <!-- Header Principal (Removido, substituído acima) -->


    <!-- Filtros de Navegação -->
    <div style="margin-bottom: 24px;">

        <!-- Busca -->
        <div style="margin-bottom: 16px; position: relative;">
            <i data-lucide="search" style="position: absolute; left: 16px; top: 50%; transform: translateY(-50%); color: var(--text-muted); width: 20px;"></i>
            <form onsubmit="return true;"> <!-- Submit via GET padrão -->
                <?php if ($showArchived): ?><input type="hidden" name="archived" value="1"><?php endif; ?>
                <?php if ($filterType !== 'all'): ?><input type="hidden" name="type" value="<?= $filterType ?>"><?php endif; ?>
                <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" placeholder="Buscar avisos..."
                    style="
                        width: 100%; padding: 12px 12px 12px 48px; border-radius: var(--radius-md); 
                        border: 1px solid var(--border-color); font-size: 1rem; outline: none; 
                        transition: border 0.2s; background: var(--bg-surface); color: var(--text-main);
                        box-shadow: var(--shadow-sm);
                    "
                    onfocus="this.style.borderColor='var(--primary)'" onblur="this.style.borderColor='var(--border-color)'">
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
                $bg = $isActive ? 'var(--primary)' : 'var(--bg-surface)';
                $color = $isActive ? 'white' : 'var(--text-muted)';
                $border = $isActive ? 'var(--primary)' : 'var(--border-color)';
            ?>
                <a href="?type=<?= $key ?><?= $showArchived ? '&archived=1' : '' ?><?= $search ? '&search=' . $search : '' ?>" class="ripple" style="
                    white-space: nowrap; padding: 8px 16px; border-radius: 20px; 
                    background: <?= $bg ?>; color: <?= $color ?>; 
                    border: 1px solid <?= $border ?>;
                    text-decoration: none; font-size: 0.85rem; font-weight: 600;
                    display: flex; align-items: center; gap: 6px;
                ">
                    <?= $data['icon'] ?> <?= $data['label'] ?>
                </a>
            <?php endforeach; ?>
        </div>

        <div style="margin-top: 12px; display: flex; align-items: center; gap: 12px; font-size: 0.85rem;">
            <a href="?archived=<?= $showArchived ? '0' : '1' ?>" style="color: var(--text-muted); font-weight: 600; text-decoration: none; display: flex; align-items: center; gap: 4px;">
                <i data-lucide="<?= $showArchived ? 'rotate-ccw' : 'archive' ?>" style="width: 14px;"></i>
                <?= $showArchived ? 'Ver Ativos' : 'Ver Arquivados' ?>
            </a>
        </div>

    </div>

    <!-- Lista de Avisos -->
    <div style="display: flex; flex-direction: column; gap: 16px;">
        <?php if (empty($avisos)): ?>
            <div style="text-align: center; padding: 48px; background: var(--bg-surface); border-radius: var(--radius-lg); border: 1px solid var(--border-color); color: var(--text-muted); box-shadow: var(--shadow-sm);">
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
            
            // Labels de Audience
            $audLabels = ['all'=>'Todos', 'admins'=>'Líderes', 'team'=>'Equipe', 'leaders'=>'Líderes'];
            $audLabel = $audLabels[$aviso['target_audience'] ?? 'all'] ?? 'Todos';
            
            $isExpired = !empty($aviso['expires_at']) && strtotime($aviso['expires_at']) < time();
            $opacity = $isExpired ? '0.6' : '1';
        ?>
            <div class="notice-card" style="opacity: <?= $opacity ?>; background: var(--bg-surface); border-radius: var(--radius-lg); border: 1px solid var(--border-color); padding: 16px; position: relative; box-shadow: var(--shadow-sm);">

                <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 8px;">
                    <div style="display: flex; gap: 6px;">
                        <span style="
                            background: <?= $pStyle['bg'] ?>; color: <?= $pStyle['text'] ?>; border: 1px solid <?= $pStyle['border'] ?>;
                            padding: 2px 8px; border-radius: 6px; font-size: 0.7rem; font-weight: 700; text-transform: uppercase;
                        ">
                            <?= $pStyle['label'] ?>
                        </span>
                        <?php if($aviso['target_audience'] !== 'all'): ?>
                            <span style="
                                background: #f3f4f6; color: #4b5563; border: 1px solid #e5e7eb;
                                padding: 2px 8px; border-radius: 6px; font-size: 0.7rem; font-weight: 600;
                            ">
                                <i data-lucide="users" width="10" style="display:inline; vertical-align:middle;"></i> <?= $audLabel ?>
                            </span>
                        <?php endif; ?>
                         <?php if($isExpired): ?>
                            <span style="background: #e5e7eb; color: #374151; padding: 2px 6px; border-radius: 4px; font-size: 0.65rem; font-weight: 700;">EXPIRADO</span>
                        <?php endif; ?>
                    </div>

                    <?php if ($_SESSION['user_role'] === 'admin'): ?>
                        <div style="position: relative;">
                            <button onclick="toggleMenu('menu-<?= $aviso['id'] ?>')" class="btn-icon ripple" style="color: var(--text-muted); padding: 4px;">
                                <i data-lucide="more-horizontal" style="width: 20px;"></i>
                            </button>
                            <!-- Menu Dropdown -->
                            <div id="menu-<?= $aviso['id'] ?>" class="dropdown-menu" style="
                                display: none; position: absolute; right: 0; 
                                background: var(--bg-surface); border: 1px solid var(--border-color); border-radius: var(--radius-md); 
                                box-shadow: var(--shadow-md); width: 140px; z-index: 10;
                            ">
                                <button onclick='openEditModal(<?= json_encode($aviso) ?>)' style="width: 100%; padding: 10px; text-align: left; background: none; border: none; cursor: pointer; display: flex; align-items: center; gap: 8px; font-size: 0.85rem; color: var(--text-main);">
                                    <i data-lucide="edit-3" style="width: 14px;"></i> Editar
                                </button>

                                <form method="POST" style="margin:0;">
                                    <input type="hidden" name="action" value="<?= $showArchived ? 'unarchive' : 'archive' ?>">
                                    <input type="hidden" name="id" value="<?= $aviso['id'] ?>">
                                    <button type="submit" style="width: 100%; padding: 10px; text-align: left; background: none; border: none; cursor: pointer; display: flex; align-items: center; gap: 8px; font-size: 0.85rem; color: var(--text-muted);">
                                        <i data-lucide="<?= $showArchived ? 'rotate-ccw' : 'archive' ?>" style="width: 14px;"></i> <?= $showArchived ? 'Desarquivar' : 'Arquivar' ?>
                                    </button>
                                </form>

                                <form method="POST" onsubmit="return confirm('Excluir?')" style="margin:0;">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="id" value="<?= $aviso['id'] ?>">
                                    <button type="submit" style="width: 100%; padding: 10px; text-align: left; background: none; border: none; cursor: pointer; display: flex; align-items: center; gap: 8px; font-size: 0.85rem; color: #ef4444; border-top: 1px solid var(--border-color);">
                                        <i data-lucide="trash-2" style="width: 14px;"></i> Excluir
                                    </button>
                                </form>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>

                <h2 style="font-size: 1rem; font-weight: 700; color: var(--text-main); margin: 0 0 8px 0;">
                    <?= htmlspecialchars($aviso['title']) ?>
                </h2>

                <div class="ql-editor" style="padding: 0; color: var(--text-muted); font-size: 0.9rem; line-height: 1.5; max-height: 200px; overflow: hidden; position: relative;">
                    <?= $aviso['message'] ?> <!-- HTML Seguro vindo do Quill -->
                </div>

                <div style="margin-top: 12px; padding-top: 12px; border-top: 1px solid var(--border-color); display: flex; justify-content: space-between; align-items: center; font-size: 0.75rem; color: var(--text-muted);">
                    <div style="display: flex; align-items: center; gap: 6px;">
                        <i data-lucide="user" style="width: 12px;"></i>
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

<?php if ($_SESSION['user_role'] === 'admin'): ?>
    <!-- Floating Action Button -->
    <button onclick="openCreateModal()" class="ripple" style="
        position: fixed; bottom: 32px; right: 24px;
        background: #166534; color: white; padding: 16px; border-radius: 50%;
        display: flex; align-items: center; justify-content: center;
        box-shadow: 0 4px 12px rgba(22, 101, 52, 0.4);
        border: none; cursor: pointer; z-index: 50; transition: transform 0.2s;
    " onmouseover="this.style.transform='scale(1.1)'" onmouseout="this.style.transform='scale(1)'">
        <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M5 12h14" />
            <path d="M12 5v14" />
        </svg>
    </button>
<?php endif; ?>

<!-- Modal Universal (Add/Edit) -->
<div id="avisoModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; z-index: 1000;">
    <div onclick="closeModal()" style="position: absolute; width: 100%; height: 100%; background: rgba(0,0,0,0.5); backdrop-filter: blur(2px);"></div>

    <div style="
        position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%);
        width: 90%; max-width: 600px; background: var(--bg-surface); border-radius: 24px; padding: 24px;
        box-shadow: var(--shadow-xl); max-height: 90vh; overflow-y: auto;
    ">
        <h2 id="modalTitle" style="margin: 0 0 16px 0; font-size: 1.25rem; font-weight: 800; color: var(--text-main);">Novo Aviso</h2>
        <form method="POST" id="avisoForm" onsubmit="return prepareSubmit()">
            <input type="hidden" name="action" id="formAction" value="create">
            <input type="hidden" name="id" id="avisoId">
            <input type="hidden" name="message" id="hiddenMessage">

            <div class="form-group" style="margin-bottom: 16px;">
                <label class="form-label" style="display: block; font-weight: 700; color: var(--text-main); margin-bottom: 6px;">Título</label>
                <input type="text" name="title" id="avisoTitle" required class="input-modern" style="
                    width: 100%; padding: 12px; border-radius: 10px; border: 1px solid var(--border-color); 
                    outline: none; background: var(--bg-body); color: var(--text-main);
                ">
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 16px;">
                <div>
                    <label class="form-label" style="display: block; font-weight: 700; color: var(--text-main); margin-bottom: 6px;">Tipo</label>
                    <select name="type" id="avisoType" style="
                        width: 100%; padding: 12px; border-radius: 10px; border: 1px solid var(--border-color); 
                        background: var(--bg-body); color: var(--text-main);
                    ">
                        <option value="general">Geral</option>
                        <option value="event">Evento</option>
                        <option value="music">Música</option>
                        <option value="spiritual">Espiritual</option>
                        <option value="urgent">Urgente</option>
                    </select>
                </div>
                <div>
                    <label class="form-label" style="display: block; font-weight: 700; color: var(--text-main); margin-bottom: 6px;">Prioridade</label>
                    <select name="priority" id="avisoPriority" style="
                        width: 100%; padding: 12px; border-radius: 10px; border: 1px solid var(--border-color); 
                        background: var(--bg-body); color: var(--text-main);
                    ">
                        <option value="info">Info</option>
                        <option value="important">Importante</option>
                        <option value="urgent">Urgente</option>
                    </select>
                </div>
            </div>

            <!-- NEW FIELDS: Audience & Expiration -->
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 16px;">
                <div>
                    <label class="form-label" style="display: block; font-weight: 700; color: var(--text-main); margin-bottom: 6px;">Público-Alvo</label>
                    <select name="target_audience" id="avisoTarget" style="
                        width: 100%; padding: 12px; border-radius: 10px; border: 1px solid var(--border-color); 
                        background: var(--bg-body); color: var(--text-main);
                    ">
                        <option value="all">Todos</option>
                        <option value="team">Equipe Louvor</option>
                        <option value="admins">Apenas Líderes</option>
                    </select>
                </div>
                <div>
                    <label class="form-label" style="display: block; font-weight: 700; color: var(--text-main); margin-bottom: 6px;">Expira em (Opcional)</label>
                    <input type="date" name="expires_at" id="avisoExpires" style="
                        width: 100%; padding: 12px; border-radius: 10px; border: 1px solid var(--border-color); 
                        background: var(--bg-body); color: var(--text-main);
                    ">
                </div>
            </div>

            <div class="form-group" style="margin-bottom: 24px;">
                <label class="form-label" style="display: block; font-weight: 700; color: var(--text-main); margin-bottom: 6px;">Mensagem</label>
                <div id="editor" style="height: 150px; background: white; color: black !important;"></div>
            </div>

            <div style="display: flex; gap: 12px;">
                <button type="button" onclick="closeModal()" style="
                    flex: 1; padding: 14px; border-radius: 12px; border: 1px solid var(--border-color); 
                    background: var(--bg-surface); font-weight: 600; cursor: pointer; color: var(--text-muted);
                ">Cancelar</button>
                <button type="submit" style="
                    flex: 2; padding: 14px; border-radius: 12px; border: none; 
                    background: var(--primary); color: white; font-weight: 700; cursor: pointer;
                ">Salvar Aviso</button>
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
        
        // New Fields
        document.getElementById('avisoTarget').value = aviso.target_audience || 'all';
        document.getElementById('avisoExpires').value = aviso.expires_at || '';

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