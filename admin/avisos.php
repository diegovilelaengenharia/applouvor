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
$query = "SELECT a.*, u.name as author_name, u.photo as author_avatar 
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
renderPageHeader('Mural de Avisos', 'Louvor PIB Oliveira');
?>

<!-- Quill CSS -->
<link href="https://cdn.quilljs.com/1.3.6/quill.snow.css" rel="stylesheet">
<link href="../assets/css/pages/avisos.css?v=<?= time() ?>" rel="stylesheet">

<div class="container aviso-container">
    
    <!-- Search and Filter Row -->
    <div class="search-filter-row">
        <!-- Search Bar -->
        <div style="flex: 1;">
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
        
        <!-- Filter Dropdown -->
        <div class="filter-container filter-container-fixed">
            <button type="button" class="filter-button" id="filterButton" onclick="toggleFilterDropdown()">
                <span>
                    <?php
                        $filterLabels = [
                            'all' => '✨ Todos',
                            'espiritual' => '🙏 Espiritual',
                            'eventos' => '🎉 Eventos',
                            'geral' => '📢 Geral',
                            'importante' => '⭐ Importante',
                            'musica' => '🎵 Música',
                            'urgente' => '🚨 Urgente'
                        ];
                        echo $filterLabels[$filterType] ?? '✨ Todos';
                    ?>
                </span>
                <i data-lucide="chevron-down" class="icon-sm"></i>
            </button>
            <div class="filter-dropdown" id="filterDropdown">
                <a href="?<?= http_build_query(array_merge($_GET, ['type' => 'all'])) ?>" 
                   class="filter-option <?= $filterType === 'all' ? 'active' : '' ?>">
                    ✨ Todos
                </a>
                <a href="?<?= http_build_query(array_merge($_GET, ['type' => 'espiritual'])) ?>" 
                   class="filter-option <?= $filterType === 'espiritual' ? 'active' : '' ?>">
                    🙏 Espiritual
                </a>
                <a href="?<?= http_build_query(array_merge($_GET, ['type' => 'eventos'])) ?>" 
                   class="filter-option <?= $filterType === 'eventos' ? 'active' : '' ?>">
                    🎉 Eventos
                </a>
                <a href="?<?= http_build_query(array_merge($_GET, ['type' => 'geral'])) ?>" 
                   class="filter-option <?= $filterType === 'geral' ? 'active' : '' ?>">
                    📢 Geral
                </a>
                <a href="?<?= http_build_query(array_merge($_GET, ['type' => 'importante'])) ?>" 
                   class="filter-option <?= $filterType === 'importante' ? 'active' : '' ?>">
                    ⭐ Importante
                </a>
                <a href="?<?= http_build_query(array_merge($_GET, ['type' => 'musica'])) ?>" 
                   class="filter-option <?= $filterType === 'musica' ? 'active' : '' ?>">
                    🎵 Música
                </a>
                <a href="?<?= http_build_query(array_merge($_GET, ['type' => 'urgente'])) ?>" 
                   class="filter-option <?= $filterType === 'urgente' ? 'active' : '' ?>">
                    🚨 Urgente
                </a>
            </div>
        </div>
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
                <!-- Header -->
                <div class="aviso-header">
                    <?php 
                        $avatarPath = $aviso['author_avatar'];
                        if ($avatarPath && !filter_var($avatarPath, FILTER_VALIDATE_URL) && strpos($avatarPath, 'data:') !== 0) {
                            // Se não for URL completa nem base64, e não começar com /, adiciona ../ já que estamos em /admin
                            if (strpos($avatarPath, '/') !== 0) {
                                $avatarPath = '../' . $avatarPath;
                            }
                        }
                    ?>
                    <?php if (!empty($avatarPath)): ?>
                        <img src="<?= htmlspecialchars($avatarPath) ?>" alt="<?= htmlspecialchars($aviso['author_name']) ?>" class="aviso-avatar">
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
    // Toggle Filter Dropdown
    function toggleFilterDropdown() {
        const dropdown = document.getElementById('filterDropdown');
        const button = document.getElementById('filterButton');
        dropdown.classList.toggle('show');
        button.classList.toggle('active');
    }

    // Toggle Aviso Dropdown
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
        // Close filter dropdown
        if (!e.target.closest('.filter-container')) {
            const filterDropdown = document.getElementById('filterDropdown');
            const filterButton = document.getElementById('filterButton');
            if (filterDropdown) filterDropdown.classList.remove('show');
            if (filterButton) filterButton.classList.remove('active');
        }
        
        // Close aviso dropdowns
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