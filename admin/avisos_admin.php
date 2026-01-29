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
    .admin-header {
        background: linear-gradient(135deg, #a78bfa 0%, #8b5cf6 100%);
        padding: 16px;
        margin: -20px -20px 16px -20px;
        color: white;
        border-radius: 0 0 16px 16px;
    }
    
    .admin-header h1 {
        margin: 0 0 4px;
        font-size: var(--font-h2);
        font-weight: 800;
    }
    
    .admin-header p {
        margin: 0;
        opacity: 0.9;
        font-size: var(--font-body-sm);
    }
    
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
    }
    
    .stat-value {
        font-size: var(--font-h2);
        font-weight: 800;
        color: #8b5cf6;
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
        margin-bottom: 12px;
    }
    
    .filter-section:last-child {
        margin-bottom: 0;
    }
    
    .filter-section-label {
        font-size: var(--font-caption);
        font-weight: 700;
        color: var(--text-muted);
        margin-bottom: 6px;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }
    
    .filter-pills {
        display: flex;
        gap: 6px;
        overflow-x: auto;
        padding-bottom: 4px;
        scrollbar-width: none;
    }
    
    .filter-pills::-webkit-scrollbar {
        display: none;
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
        background: linear-gradient(135deg, #a78bfa, #8b5cf6);
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
        background: #fef2f2; 
        color: #dc2626; 
    }
    
    .priority-important { 
        background: #fef3c7; 
        color: #d97706; 
    }
    
    .pin-badge {
        background: #f3e8ff;
        color: #8b5cf6;
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
        background: #f3e8ff;
        color: #8b5cf6;
        border-color: #c4b5fd;
    }
    
    .fab {
        position: fixed;
        bottom: 90px;
        right: 16px;
        width: 52px;
        height: 52px;
        border-radius: 50%;
        background: linear-gradient(135deg, #a78bfa, #8b5cf6);
        color: white;
        border: none;
        box-shadow: 0 4px 16px rgba(139, 92, 246, 0.4);
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        z-index: 100;
        transition: transform 0.2s;
    }
    
    .fab:hover {
        transform: scale(1.05);
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
        background: linear-gradient(135deg, #a78bfa, #8b5cf6);
        color: white;
        border: none;
        padding: 12px 20px;
        border-radius: 12px;
        font-weight: 700;
        cursor: pointer;
        width: 100%;
        font-size: var(--font-body-sm);
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
    
    @media (max-width: 768px) {
        .admin-header {
            padding: 12px;
            margin: -20px -20px 12px -20px;
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
    
    <!-- Header -->
    <div class="admin-header">
        <div style="display: flex; justify-content: space-between; align-items: center;">
            <div>
                <h1>📢 Gestão de Avisos</h1>
                <p>Central de gerenciamento</p>
            </div>
            <button onclick="openTagManager()" style="background: rgba(255,255,255,0.2); border: 1px solid rgba(255,255,255,0.3); color: white; padding: 8px 12px; border-radius: 10px; font-weight: 600; cursor: pointer; font-size: var(--font-caption);">
                <i data-lucide="tags" style="width: 14px; display: inline-block; vertical-align: middle;"></i>
                Tags
            </button>
        </div>
    </div>
    
    <!-- Estatísticas -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-value"><?= $totalActive ?></div>
            <div class="stat-label">Ativos</div>
        </div>
        <div class="stat-card">
            <div class="stat-value"><?= $totalArchived ?></div>
            <div class="stat-label">Arquivados</div>
        </div>
        <div class="stat-card">
            <div class="stat-value"><?= $totalExpired ?></div>
            <div class="stat-label">Expirados</div>
        </div>
        <div class="stat-card">
            <div class="stat-value"><?= count($tags) ?></div>
            <div class="stat-label">Tags</div>
        </div>
    </div>
    
    <!-- Filtros -->
    <div class="filter-bar">
        <div class="filter-section">
            <input type="text" id="searchInput" placeholder="Buscar avisos..." value="<?= htmlspecialchars($search) ?>" class="search-input">
        </div>
        
        <div class="filter-section">
            <div class="filter-section-label">Status</div>
            <div class="filter-pills">
                <a href="?status=active" class="filter-pill <?= $filterStatus === 'active' ? 'active' : '' ?>">Ativos</a>
                <a href="?status=archived" class="filter-pill <?= $filterStatus === 'archived' ? 'active' : '' ?>">Arquivados</a>
                <a href="?status=expired" class="filter-pill <?= $filterStatus === 'expired' ? 'active' : '' ?>">Expirados</a>
            </div>
        </div>
        
        <div class="filter-section">
            <div class="filter-section-label">Prioridade</div>
            <div class="filter-pills">
                <a href="?priority=all&status=<?= $filterStatus ?>" class="filter-pill <?= $filterPriority === 'all' ? 'active' : '' ?>">Todas</a>
                <a href="?priority=urgent&status=<?= $filterStatus ?>" class="filter-pill <?= $filterPriority === 'urgent' ? 'active' : '' ?>">Urgente</a>
                <a href="?priority=important&status=<?= $filterStatus ?>" class="filter-pill <?= $filterPriority === 'important' ? 'active' : '' ?>">Importante</a>
                <a href="?priority=info&status=<?= $filterStatus ?>" class="filter-pill <?= $filterPriority === 'info' ? 'active' : '' ?>">Normal</a>
            </div>
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

<!-- FAB -->
<button onclick="openCreateModal()" class="fab">
    <i data-lucide="plus" style="width: 28px; height: 28px;"></i>
</button>

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
        
        <button onclick="createNewTag()" class="btn-primary" style="margin-top: 16px;">
            <i data-lucide="plus" style="width: 16px; display: inline-block; vertical-align: middle;"></i>
            Nova Tag
        </button>
    </div>
</div>

<script src="https://cdn.quilljs.com/1.3.6/quill.js"></script>
<script>
    // Quill Editor
    const quill = new Quill('#editor', {
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
        
        const color = prompt('Cor (hex):', '#3b82f6');
        
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
