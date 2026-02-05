<?php
/**
 * Central de Gestão de Avisos (Admin)
 * Interface completa para criar, editar, gerenciar avisos e tags
 */

require_once '../includes/auth.php';
require_once '../includes/db.php';
require_once '../includes/layout.php';

checkLogin();

$userId = $_SESSION['user_id'] ?? 1;
$isAdmin = ($_SESSION['user_role'] ?? '') === 'admin';

if (!$isAdmin) {
    header('Location: avisos.php');
    exit;
}

// Buscar todas as tags
$tags = $pdo->query("SELECT * FROM aviso_tags ORDER BY is_default DESC, name ASC")->fetchAll(PDO::FETCH_ASSOC);

// Filtros
$filterTag = $_GET['tag'] ?? 'all';
$filterPriority = $_GET['priority'] ?? 'all';
$filterStatus = $_GET['status'] ?? 'active'; // active, archived, expired
$search = $_GET['search'] ?? '';

// Construir query
$sql = "SELECT a.*, u.name as author_name FROM avisos a LEFT JOIN users u ON a.created_by = u.id WHERE 1=1";
$params = [];

// Filtro de status
if ($filterStatus === 'active') {
    $sql .= " AND a.archived_at IS NULL AND (a.expires_at IS NULL OR a.expires_at >= CURDATE())";
} elseif ($filterStatus === 'archived') {
    $sql .= " AND a.archived_at IS NOT NULL";
} elseif ($filterStatus === 'expired') {
    $sql .= " AND a.archived_at IS NULL AND a.expires_at < CURDATE()";
}

// Filtro de prioridade
if ($filterPriority !== 'all') {
    $sql .= " AND a.priority = ?";
    $params[] = $filterPriority;
}

// Busca
if (!empty($search)) {
    $sql .= " AND (a.title LIKE ? OR a.message LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

$sql .= " ORDER BY a.is_pinned DESC, 
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

// Estatísticas
$totalActive = $pdo->query("SELECT COUNT(*) FROM avisos WHERE archived_at IS NULL AND (expires_at IS NULL OR expires_at >= CURDATE())")->fetchColumn();
$totalArchived = $pdo->query("SELECT COUNT(*) FROM avisos WHERE archived_at IS NOT NULL")->fetchColumn();
$totalExpired = $pdo->query("SELECT COUNT(*) FROM avisos WHERE archived_at IS NULL AND expires_at < CURDATE()")->fetchColumn();

renderAppHeader('Gestão de Avisos');
?>

<style>
    /* Header removido - agora usamos avisos-header */
    
    .stats-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(70px, 1fr));
        gap: 8px;
        margin-bottom: 16px;
    }
    
    .stat-card {
        background: var(--bg-surface);
        padding: 12px 8px;
        border-radius: 12px;
        border: 1px solid var(--border-color);
        text-align: center;
        position: relative;
        overflow: hidden;
        transition: all 0.2s;
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
    
    .stat-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(0,0,0,0.08);
    }
    
    .stat-card.ativos {
        color: #10b981;
    }
    
    .stat-card.arquivados {
        color: var(--slate-500);
    }
    
    .stat-card.expirados {
        color: var(--yellow-500);
    }
    
    .stat-card.tags {
        color: var(--lavender-600);
    }
    
    .stat-icon {
        width: 24px;
        height: 24px;
        margin: 0 auto 6px;
        opacity: 0.8;
    }
    
    .stat-value {
        font-size: var(--font-h2);
        font-weight: 800;
        color: currentColor;
    }
    
    .stat-label {
        font-size: var(--font-caption);
        color: var(--text-muted);
        margin-top: 2px;
        font-weight: 600;
    }
    
    .filter-bar {
        background: var(--bg-surface);
        padding: 12px;
        border-radius: 12px;
        border: 1px solid var(--border-color);
        margin-bottom: 16px;
    }
    
    .filter-section {
        flex: 1;
        min-width: 0;
    }
    
    .filter-section-label {
        font-size: var(--font-caption);
        font-weight: 700;
        color: var(--text-muted);
        margin-bottom: 6px;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }
    
    .filter-select {
        width: 100%;
        padding: 10px 12px;
        border: 1px solid var(--border-color);
        border-radius: 12px;
        font-size: var(--font-body-sm);
        background: var(--bg-body);
        color: var(--text-main);
        font-weight: 600;
        cursor: pointer;
        transition: all 0.2s;
    }
    
    .filter-select:hover {
        border-color: #c4b5fd;
    }
    
    .filter-select:focus {
        outline: none;
        border-color: var(--lavender-600);
        box-shadow: 0 0 0 3px rgba(139, 92, 246, 0.1);
    }
    
    .filters-row {
        display: grid;
        grid-template-columns: 1fr 1fr 1fr;
        gap: 10px;
        margin-bottom: 12px;
    }
    
    .tag-multiselect {
        position: relative;
    }
    
    .tag-multiselect-trigger {
        width: 100%;
        padding: 10px 12px;
        border: 1px solid var(--border-color);
        border-radius: 12px;
        font-size: var(--font-body-sm);
        background: var(--bg-body);
        color: var(--text-main);
        font-weight: 600;
        cursor: pointer;
        transition: all 0.2s;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    
    .tag-multiselect-trigger:hover {
        border-color: #c4b5fd;
    }
    
    .tag-multiselect-dropdown {
        display: none;
        position: absolute;
        top: 100%;
        left: 0;
        right: 0;
        margin-top: 4px;
        background: var(--bg-surface);
        border: 1px solid var(--border-color);
        border-radius: 12px;
        box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        z-index: 100;
        max-height: 200px;
        overflow-y: auto;
    }
    
    .tag-multiselect-dropdown.active {
        display: block;
    }
    
    .tag-option-item {
        padding: 8px 12px;
        cursor: pointer;
        transition: background 0.2s;
        display: flex;
        align-items: center;
        gap: 8px;
        font-size: var(--font-body-sm);
    }
    
    .tag-option-item:hover {
        background: var(--bg-body);
    }
    
    .tag-option-item input[type="checkbox"] {
        cursor: pointer;
    }
    
    .filter-pill {
        padding: 6px 12px;
        border-radius: 16px;
        font-size: var(--font-caption);
        font-weight: 600;
        text-decoration: none;
        white-space: nowrap;
        transition: all 0.2s;
        background: var(--bg-body);
        color: var(--text-muted);
        border: 1px solid var(--border-color);
    }
    
    .filter-pill.active {
        background: #a78bfa;
        color: white;
        border-color: transparent;
    }
    
    .search-input {
        width: 100%;
        padding: 10px 12px;
        border: 1px solid var(--border-color);
        border-radius: 12px;
        font-size: var(--font-body-sm);
        background: var(--bg-body);
        color: var(--text-main);
    }
    
    .aviso-item {
        background: var(--bg-surface);
        border: 1px solid var(--border-color);
        border-radius: 12px;
        padding: 12px;
        margin-bottom: 10px;
        transition: all 0.2s;
    }
    
    .aviso-item:hover {
        box-shadow: 0 2px 8px rgba(139, 92, 246, 0.1);
        border-color: #c4b5fd;
    }
    
    .aviso-header-row {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        gap: 8px;
    }
    
    .aviso-title-section {
        flex: 1;
        min-width: 0;
    }
    
    .aviso-title {
        font-size: var(--font-body);
        font-weight: 700;
        color: var(--text-main);
        margin-bottom: 6px;
        line-height: 1.3;
    }
    
    .aviso-meta {
        display: flex;
        gap: 8px;
        flex-wrap: wrap;
        font-size: var(--font-caption);
        color: var(--text-muted);
        align-items: center;
    }
    
    .tag-badge {
        display: inline-flex;
        align-items: center;
        gap: 4px;
        padding: 3px 8px;
        border-radius: 10px;
        font-size: var(--font-caption);
        font-weight: 600;
    }
    
    .priority-badge {
        padding: 3px 8px;
        border-radius: 10px;
        font-size: var(--font-caption);
        font-weight: 700;
        text-transform: uppercase;
    }
    
    .priority-urgent { 
        background: var(--rose-50); 
        color: var(--rose-600); 
    }
    
    .priority-important { 
        background: var(--yellow-100); 
        color: var(--yellow-600); 
    }
    
    .pin-badge {
        background: var(--lavender-100);
        color: var(--lavender-600);
        padding: 3px 8px;
        border-radius: 10px;
        font-size: var(--font-caption);
        font-weight: 600;
    }
    
    .action-buttons {
        display: flex;
        gap: 4px;
        flex-shrink: 0;
    }
    
    .btn-icon {
        background: none;
        border: 1px solid var(--border-color);
        padding: 6px;
        border-radius: 8px;
        cursor: pointer;
        color: var(--text-muted);
        transition: all 0.2s;
        display: flex;
        align-items: center;
        justify-content: center;
    }
    
    .btn-icon:hover {
        background: var(--lavender-100);
        color: var(--lavender-600);
        border-color: #c4b5fd;
    }
    
    .create-aviso-btn {
        background: #10b981;
        color: white;
        border: none;
        padding: 12px 20px;
        border-radius: 12px;
        font-weight: 700;
        cursor: pointer;
        font-size: var(--font-body-sm);
        display: flex;
        align-items: center;
        gap: 8px;
        transition: all 0.2s;
        box-shadow: 0 2px 8px rgba(16, 185, 129, 0.3);
    }
    
    .create-aviso-btn:hover {
        background: var(--primary);
        box-shadow: 0 4px 12px rgba(16, 185, 129, 0.4);
    }
    
    .avisos-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 16px;
        gap: 10px;
    }
    
    .avisos-title {
        font-size: var(--font-h3);
        font-weight: 700;
        color: var(--text-main);
        margin: 0;
    }
    
    .header-actions {
        display: flex;
        gap: 8px;
    }
    
    .btn-tags {
        background: var(--bg-surface);
        color: var(--text-main);
        border: 1px solid var(--border-color);
        padding: 10px 16px;
        border-radius: 12px;
        font-weight: 600;
        cursor: pointer;
        font-size: var(--font-body-sm);
        display: flex;
        align-items: center;
        gap: 6px;
        transition: all 0.2s;
    }
    
    .btn-tags:hover {
        background: var(--lavender-100);
        border-color: #c4b5fd;
        color: var(--lavender-600);
    }
    
    .modal {
        display: none;
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        z-index: 1000;
        background: rgba(0,0,0,0.5);
        backdrop-filter: blur(4px);
    }
    
    .modal-content {
        position: absolute;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%);
        width: 90%;
        max-width: 500px;
        background: var(--bg-surface);
        border-radius: 20px;
        padding: 20px;
        max-height: 90vh;
        overflow-y: auto;
    }
    
    .modal-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 16px;
    }
    
    .modal-title {
        margin: 0;
        font-size: var(--font-h2);
        font-weight: 800;
        color: var(--text-main);
    }
    
    .form-group {
        margin-bottom: 12px;
    }
    
    .form-label {
        display: block;
        font-weight: 700;
        color: var(--text-main);
        margin-bottom: 6px;
        font-size: var(--font-body-sm);
    }
    
    .form-input, .form-select, .form-textarea {
        width: 100%;
        padding: 10px 12px;
        border: 1px solid var(--border-color);
        border-radius: 12px;
        font-size: var(--font-body-sm);
        background: var(--bg-body);
        color: var(--text-main);
    }
    
    .form-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 10px;
    }
    
    .tag-selector {
        display: flex;
        flex-wrap: wrap;
        gap: 6px;
        padding: 10px;
        border: 1px solid var(--border-color);
        border-radius: 12px;
        background: var(--bg-body);
    }
    
    .tag-option {
        padding: 5px 10px;
        border-radius: 10px;
        font-size: var(--font-caption);
        font-weight: 600;
        cursor: pointer;
        transition: all 0.2s;
        border: 2px solid transparent;
    }
    
    .tag-option.selected {
        border-color: currentColor;
    }
    
    .btn-primary {
        background: var(--lavender-600);
        color: white;
        border: none;
        padding: 12px 20px;
        border-radius: 12px;
        font-weight: 700;
        cursor: pointer;
        width: 100%;
        font-size: var(--font-body-sm);
    }
    
    .btn-primary:hover {
        background: var(--lavender-700);
    }
    
    .btn-secondary {
        background: var(--bg-surface);
        color: var(--text-muted);
        border: 1px solid var(--border-color);
        padding: 12px 20px;
        border-radius: 12px;
        font-weight: 600;
        cursor: pointer;
        font-size: var(--font-body-sm);
    }
    
    .tag-manager-item {
        display: flex;
        align-items: center;
        gap: 10px;
        padding: 10px;
        background: var(--bg-body);
        border-radius: 12px;
        margin-bottom: 8px;
    }
    
    .tag-color-preview {
        width: 28px;
        height: 28px;
        border-radius: 8px;
        flex-shrink: 0;
    }
    
    .tag-info {
        flex: 1;
        min-width: 0;
    }
    
    .tag-name {
        font-weight: 600;
        font-size: var(--font-body-sm);
        color: var(--text-main);
    }
    
    .tag-type {
        font-size: var(--font-caption);
        color: var(--text-muted);
    }
    
    /* Quill Editor Customization */
    .ql-toolbar {
        border-radius: 12px 12px 0 0 !important;
        border-color: var(--border-color) !important;
        background: var(--bg-body) !important;
    }
    
    .ql-container {
        border-radius: 0 0 12px 12px !important;
        border-color: var(--border-color) !important;
        background: white !important;
        font-size: var(--font-body-sm) !important;
    }
    
    .ql-editor {
        min-height: 150px;
        max-height: 300px;
        overflow-y: auto;
    }
    
    .ql-editor.ql-blank::before {
        color: var(--text-muted);
        font-style: normal;
    }
    
    @media (max-width: 768px) {
        .avisos-header {
            flex-direction: column;
            align-items: stretch;
            gap: 10px;
        }
        
        .header-actions {
            flex-direction: column;
        }
        
        .create-aviso-btn,
        .btn-tags {
            width: 100%;
            justify-content: center;
        }
        
        .filters-row {
            grid-template-columns: 1fr;
        }
        
        .admin-header h1 {
            font-size: var(--font-h3);
        }
        
        .stats-grid {
            grid-template-columns: repeat(4, 1fr);
            gap: 6px;
        }
        
        .stat-card {
            padding: 10px 6px;
        }
        
        .stat-value {
            font-size: var(--font-h3);
        }
        
        .filter-bar {
            padding: 10px;
        }
        
        .aviso-item {
            padding: 10px;
        }
        
        .modal-content {
            width: 95%;
            padding: 16px;
        }
    }
</style>

<?php renderPageHeader('Central de Gestão de Avisos', 'Admin'); ?>

<div class="container" style="padding-top: 16px; max-width: 900px; margin: 0 auto;">
    
    <!-- Estatísticas -->
    <div class="stats-grid">
        <div class="stat-card ativos">
            <div class="stat-icon">
                <i data-lucide="check-circle" style="width: 100%; height: 100%;"></i>
            </div>
            <div class="stat-value"><?= $totalActive ?></div>
            <div class="stat-label">Ativos</div>
        </div>
        <div class="stat-card arquivados">
            <div class="stat-icon">
                <i data-lucide="archive" style="width: 100%; height: 100%;"></i>
            </div>
            <div class="stat-value"><?= $totalArchived ?></div>
            <div class="stat-label">Arquivados</div>
        </div>
        <div class="stat-card expirados">
            <div class="stat-icon">
                <i data-lucide="clock" style="width: 100%; height: 100%;"></i>
            </div>
            <div class="stat-value"><?= $totalExpired ?></div>
            <div class="stat-label">Expirados</div>
        </div>
        <div class="stat-card tags">
            <div class="stat-icon">
                <i data-lucide="tags" style="width: 100%; height: 100%;"></i>
            </div>
            <div class="stat-value"><?= count($tags) ?></div>
            <div class="stat-label">Tags</div>
        </div>
    </div>
    
    <!-- Filtros -->
    <div class="filter-bar">
        <div class="filter-section" style="margin-bottom: 12px;">
            <input type="text" id="searchInput" placeholder="Buscar avisos..." value="<?= htmlspecialchars($search) ?>" class="search-input">
        </div>
        
        <div class="filters-row">
            <div class="filter-section">
                <div class="filter-section-label">Status</div>
                <select class="filter-select" onchange="window.location.href='?status=' + this.value + '&priority=<?= $filterPriority ?>'">
                    <option value="active" <?= $filterStatus === 'active' ? 'selected' : '' ?>>Ativos</option>
                    <option value="archived" <?= $filterStatus === 'archived' ? 'selected' : '' ?>>Arquivados</option>
                    <option value="expired" <?= $filterStatus === 'expired' ? 'selected' : '' ?>>Expirados</option>
                </select>
            </div>
            
            <div class="filter-section">
                <div class="filter-section-label">Prioridade</div>
                <select class="filter-select" onchange="window.location.href='?status=<?= $filterStatus ?>&priority=' + this.value">
                    <option value="all" <?= $filterPriority === 'all' ? 'selected' : '' ?>>Todas</option>
                    <option value="urgent" <?= $filterPriority === 'urgent' ? 'selected' : '' ?>>Urgente</option>
                    <option value="important" <?= $filterPriority === 'important' ? 'selected' : '' ?>>Importante</option>
                    <option value="info" <?= $filterPriority === 'info' ? 'selected' : '' ?>>Normal</option>
                </select>
            </div>
            
            <div class="filter-section">
                <div class="filter-section-label">Tags</div>
                <div class="tag-multiselect">
                    <div class="tag-multiselect-trigger" onclick="toggleTagDropdown()">
                        <span id="tagFilterLabel">Todas as tags</span>
                        <i data-lucide="chevron-down" style="width: 16px;"></i>
                    </div>
                    <div class="tag-multiselect-dropdown" id="tagDropdown">
                        <div class="tag-option-item" onclick="selectAllTags()">
                            <input type="checkbox" id="tag-all" checked>
                            <label for="tag-all" style="cursor: pointer; flex: 1;">Todas</label>
                        </div>
                        <?php foreach ($tags as $tag): ?>
                            <div class="tag-option-item" onclick="toggleTag(<?= $tag['id'] ?>)">
                                <input type="checkbox" id="tag-<?= $tag['id'] ?>" class="tag-checkbox" checked>
                                <label for="tag-<?= $tag['id'] ?>" style="cursor: pointer; flex: 1; color: <?= $tag['color'] ?>;">
                                    <?= htmlspecialchars($tag['name']) ?>
                                </label>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Header da Lista de Avisos -->
    <div class="avisos-header">
        <h3 class="avisos-title">📋 Lista de Avisos</h3>
        <div class="header-actions">
            <button onclick="openTagManager()" class="btn-tags">
                <i data-lucide="tags" style="width: 16px;"></i>
                Tags
            </button>
            <button onclick="openCreateModal()" class="create-aviso-btn">
                <i data-lucide="plus" style="width: 16px;"></i>
                Criar Novo Aviso
            </button>
        </div>
    </div>
    
    <!-- Lista de Avisos -->
    <div id="avisosList">
        <?php if (count($avisos) > 0): ?>
            <?php foreach ($avisos as $aviso): ?>
                <div class="aviso-item">
                    <div class="aviso-header-row">
                        <div class="aviso-title-section">
                            <div class="aviso-title">
                                <?php if ($aviso['is_pinned']): ?>
                                    <span class="pin-badge">📌 Fixado</span>
                                <?php endif; ?>
                                <?= htmlspecialchars($aviso['title']) ?>
                            </div>
                            <div class="aviso-meta">
                                <?php if ($aviso['priority'] === 'urgent'): ?>
                                    <span class="priority-badge priority-urgent">🔥 URGENTE</span>
                                <?php elseif ($aviso['priority'] === 'important'): ?>
                                    <span class="priority-badge priority-important">⭐ IMPORTANTE</span>
                                <?php endif; ?>
                                
                                <?php foreach ($aviso['tags'] as $tag): ?>
                                    <span class="tag-badge" style="background: <?= $tag['color'] ?>22; color: <?= $tag['color'] ?>;">
                                        <?= htmlspecialchars($tag['name']) ?>
                                    </span>
                                <?php endforeach; ?>
                                
                                <span>Por <?= htmlspecialchars($aviso['author_name']) ?></span>
                                <span>• <?= date('d/m/Y', strtotime($aviso['created_at'])) ?></span>
                            </div>
                        </div>
                        
                        <div class="action-buttons">
                            <button class="btn-icon" onclick="viewStats(<?= $aviso['id'] ?>)" title="Estatísticas">
                                <i data-lucide="bar-chart-2" style="width: 16px;"></i>
                            </button>
                            <button class="btn-icon" onclick="editAviso(<?= htmlspecialchars(json_encode($aviso)) ?>)" title="Editar">
                                <i data-lucide="edit-2" style="width: 16px;"></i>
                            </button>
                            <button class="btn-icon" onclick="deleteAviso(<?= $aviso['id'] ?>)" title="Deletar">
                                <i data-lucide="trash-2" style="width: 16px;"></i>
                            </button>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div style="text-align: center; padding: 60px 20px;">
                <div style="font-size: 48px; margin-bottom: 16px;">📭</div>
                <h3 style="color: var(--text-main); margin-bottom: 8px;">Nenhum aviso encontrado</h3>
                <p style="color: var(--text-muted);">Crie um novo aviso para começar</p>
            </div>
        <?php endif; ?>
    </div>
    
    <div style="height: 100px;"></div>
</div>

<!-- Modal Criar/Editar Aviso -->
<div id="avisoModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h2 id="modalTitle" class="modal-title">Novo Aviso</h2>
            <button onclick="closeModal('avisoModal')" style="background: none; border: none; cursor: pointer; color: var(--text-muted);">
                <i data-lucide="x" style="width: 20px;"></i>
            </button>
        </div>
        
        <form id="avisoForm" onsubmit="saveAviso(event)">
            <input type="hidden" id="avisoId">
            
            <div class="form-group">
                <label class="form-label">Título</label>
                <input type="text" id="avisoTitle" class="form-input" required placeholder="Ex: Ensaio especial neste sábado">
            </div>
            
            <div class="form-grid">
                <div class="form-group">
                    <label class="form-label">Prioridade</label>
                    <select id="avisoPriority" class="form-select">
                        <option value="info">ℹ️ Normal</option>
                        <option value="important">⭐ Importante</option>
                        <option value="urgent">🔥 Urgente</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Público</label>
                    <select id="avisoTarget" class="form-select">
                        <option value="all">👥 Todos</option>
                        <option value="team">🎸 Equipe</option>
                        <option value="admins">👑 Líderes</option>
                    </select>
                </div>
            </div>
            
            <div class="form-group">
                <label class="form-label">Tags</label>
                <div id="tagSelector" class="tag-selector">
                    <?php foreach ($tags as $tag): ?>
                        <div class="tag-option" data-tag-id="<?= $tag['id'] ?>" style="background: <?= $tag['color'] ?>22; color: <?= $tag['color'] ?>;" onclick="toggleTag(this)">
                            <?= htmlspecialchars($tag['name']) ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
            
            <div class="form-group">
                <label class="form-label">Expira em (opcional)</label>
                <input type="date" id="avisoExpires" class="form-input">
            </div>
            
            <div class="form-group">
                <label class="form-label">Mensagem</label>
                <div id="editor" style="height: 150px; background: white; border-radius: 12px;"></div>
            </div>
            
            <div style="display: flex; gap: 12px; margin-top: 20px;">
                <button type="button" onclick="closeModal('avisoModal')" class="btn-secondary" style="flex: 1;">Cancelar</button>
                <button type="submit" class="btn-primary" style="flex: 2;">Publicar</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal Gerenciar Tags -->
<div id="tagModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h2 class="modal-title">🏷️ Gerenciar Tags</h2>
            <button onclick="closeModal('tagModal')" style="background: none; border: none; cursor: pointer; color: var(--text-muted);">
                <i data-lucide="x" style="width: 20px;"></i>
            </button>
        </div>
        
        <div id="tagsList"></div>
        
        <button onclick="createNewTag()" class="btn-success" style="margin-top: 16px; width: 100%;">
            <i data-lucide="plus" style="width: 16px; display: inline-block; vertical-align: middle;"></i>
            Nova Tag
        </button>
    </div>
</div>

<link href="https://cdn.quilljs.com/1.3.6/quill.snow.css" rel="stylesheet">
<script src="https://cdn.quilljs.com/1.3.6/quill.js"></script>
<script>
    // Quill Editor com formatação profissional
    const quill = new Quill('#editor', {
        theme: 'snow',
        placeholder: 'Escreva a mensagem do aviso...',
        modules: {
            toolbar: [
                [{ 'header': [1, 2, 3, false] }],
                ['bold', 'italic', 'underline', 'strike'],
                [{ 'color': [] }, { 'background': [] }],
                [{ 'list': 'ordered'}, { 'list': 'bullet' }],
                [{ 'indent': '-1'}, { 'indent': '+1' }],
                [{ 'align': [] }],
                ['link'],
                ['clean']
            ]
        }
    });
    
    let selectedTags = [];
    
    function toggleTag(element) {
        const tagId = element.dataset.tagId;
        element.classList.toggle('selected');
        
        if (selectedTags.includes(tagId)) {
            selectedTags = selectedTags.filter(id => id !== tagId);
        } else {
            selectedTags.push(tagId);
        }
    }
    
    function openCreateModal() {
        document.getElementById('modalTitle').innerText = 'Novo Aviso';
        document.getElementById('avisoForm').reset();
        document.getElementById('avisoId').value = '';
        quill.setContents([]);
        selectedTags = [];
        document.querySelectorAll('.tag-option').forEach(el => el.classList.remove('selected'));
        document.getElementById('avisoModal').style.display = 'block';
    }
    
    function editAviso(aviso) {
        document.getElementById('modalTitle').innerText = 'Editar Aviso';
        document.getElementById('avisoId').value = aviso.id;
        document.getElementById('avisoTitle').value = aviso.title;
        document.getElementById('avisoPriority').value = aviso.priority;
        document.getElementById('avisoTarget').value = aviso.target_audience || 'all';
        document.getElementById('avisoExpires').value = aviso.expires_at || '';
        quill.root.innerHTML = aviso.message || '';
        
        // Selecionar tags
        selectedTags = aviso.tags.map(t => String(t.id));
        document.querySelectorAll('.tag-option').forEach(el => {
            if (selectedTags.includes(el.dataset.tagId)) {
                el.classList.add('selected');
            } else {
                el.classList.remove('selected');
            }
        });
        
        document.getElementById('avisoModal').style.display = 'block';
    }
    
    async function saveAviso(e) {
        e.preventDefault();
        
        const id = document.getElementById('avisoId').value;
        const formData = new FormData();
        formData.append('action', id ? 'update' : 'create');
        if (id) formData.append('id', id);
        formData.append('title', document.getElementById('avisoTitle').value);
        formData.append('message', quill.root.innerHTML);
        formData.append('priority', document.getElementById('avisoPriority').value);
        formData.append('target_audience', document.getElementById('avisoTarget').value);
        formData.append('expires_at', document.getElementById('avisoExpires').value);
        formData.append('tags', JSON.stringify(selectedTags));
        
        try {
            const response = await fetch('avisos.php', {
                method: 'POST',
                body: formData
            });
            
            if (response.ok) {
                window.location.reload();
            }
        } catch (error) {
            alert('Erro ao salvar aviso');
        }
    }
    
    async function deleteAviso(id) {
        if (!confirm('Excluir este aviso permanentemente?')) return;
        
        const formData = new FormData();
        formData.append('action', 'delete');
        formData.append('id', id);
        
        try {
            const response = await fetch('avisos.php', {
                method: 'POST',
                body: formData
            });
            
            if (response.ok) {
                window.location.reload();
            }
        } catch (error) {
            alert('Erro ao deletar aviso');
        }
    }
    
    async function viewStats(id) {
        try {
            const response = await fetch(`avisos_api.php?action=get_aviso_stats&aviso_id=${id}`);
            const data = await response.json();
            
            if (data.success) {
                alert(`Estatísticas:\n\nLeituras: ${data.read_count}/${data.total_users} (${data.read_percentage}%)`);
            }
        } catch (error) {
            alert('Erro ao carregar estatísticas');
        }
    }
    
    function openTagManager() {
        loadTags();
        document.getElementById('tagModal').style.display = 'block';
    }
    
    async function loadTags() {
        try {
            const response = await fetch('avisos_api.php?action=list_tags');
            const data = await response.json();
            
            if (data.success) {
                const html = data.tags.map(tag => `
                    <div class="tag-manager-item">
                        <div class="tag-color-preview" style="background: ${tag.color};"></div>
                        <div class="tag-info">
                            <div class="tag-name">${tag.name}</div>
                            <div class="tag-type">${tag.is_default ? 'Tag padrão' : 'Tag customizada'}</div>
                        </div>
                        ${!tag.is_default ? `<button onclick="deleteTag(${tag.id})" class="btn-icon"><i data-lucide="trash-2" style="width: 14px;"></i></button>` : ''}
                    </div>
                `).join('');
                
                document.getElementById('tagsList').innerHTML = html;
                lucide.createIcons();
            }
        } catch (error) {
            console.error('Erro ao carregar tags:', error);
        }
    }
    
    function createNewTag() {
        const name = prompt('Nome da tag:');
        if (!name) return;
        
        const color = prompt('Cor (hex):', 'var(--slate-500)');
        
        const formData = new FormData();
        formData.append('action', 'create_tag');
        formData.append('name', name);
        formData.append('color', color);
        formData.append('icon', 'tag');
        
        fetch('avisos_api.php', {
            method: 'POST',
            body: formData
        }).then(() => {
            loadTags();
            window.location.reload();
        });
    }
    
    async function deleteTag(id) {
        if (!confirm('Deletar esta tag?')) return;
        
        const formData = new FormData();
        formData.append('action', 'delete_tag');
        formData.append('id', id);
        
        try {
            await fetch('avisos_api.php', {
                method: 'POST',
                body: formData
            });
            loadTags();
            window.location.reload();
        } catch (error) {
            alert('Erro ao deletar tag');
        }
    }
    
    function closeModal(id) {
        document.getElementById(id).style.display = 'none';
    }
    
    // Busca
    let searchTimeout;
    document.getElementById('searchInput').addEventListener('input', (e) => {
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(() => {
            window.location.href = `?search=${encodeURIComponent(e.target.value)}&status=<?= $filterStatus ?>&priority=<?= $filterPriority ?>`;
        }, 500);
    });
</script>

<?php renderAppFooter(); ?>
