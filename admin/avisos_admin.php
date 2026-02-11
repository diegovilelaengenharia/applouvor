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

<!-- Quill CSS -->
<link href="https://cdn.quilljs.com/1.3.6/quill.snow.css" rel="stylesheet">
<link href="../assets/css/pages/avisos.css?v=<?= time() ?>" rel="stylesheet">

<?php renderPageHeader('Central de Gestão de Avisos', 'Admin'); ?>

<div class="container aviso-admin-container">
    
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
