<?php
/**
 * Central de Gestão de Avisos (Admin)
 * Interface moderna premium baseada no Sacred Minimalist, com Bento Grid e Tailwind CSS
 */

require_once '../src/helpers/auth.php';
require_once '../src/config/db.php';
require_once '../src/layout/layout.php';

checkLogin();

$userId = $_SESSION['user_id'] ?? 1;
$isAdmin = ($_SESSION['user_role'] ?? '') === 'admin';

if (!$isAdmin) {
    header('Location: avisos.php');
    exit;
}

// Validação CSRF para requisições POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrfFromHeader = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? null;
    $csrfFromPost   = $_POST['csrf_token'] ?? null;
    $csrfToken      = $csrfFromHeader ?? $csrfFromPost;
    if (!$csrfToken || !hash_equals($_SESSION['csrf_token'], $csrfToken)) {
        http_response_code(403);
        header('Content-Type: application/json');
        die(json_encode(['error' => 'Ação não autorizada. Token inválido.']));
    }
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

renderAppHeader('Gestão de Avisos', 'index.php');
?>

<!-- Quill CSS -->
<link href="https://cdn.quilljs.com/1.3.6/quill.snow.css" rel="stylesheet">

<main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 mb-24 space-y-8 animate-fade-in">
    <!-- Meta tag CSRF para fetch() AJAX -->
    <meta name="csrf-token" content="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">

    <!-- Hero / Header Section -->
    <div class="relative overflow-hidden rounded-3xl bg-gradient-to-br from-[#1A1B1F] to-[#2C2E35] text-white p-8 shadow-xl border border-white/10">
        <div class="absolute -right-16 -top-16 w-64 h-64 bg-[#2E7EED]/10 rounded-full blur-3xl pointer-events-none"></div>
        <div class="absolute -left-16 -bottom-16 w-64 h-64 bg-[#FFC107]/5 rounded-full blur-3xl pointer-events-none"></div>
        
        <div class="relative z-10 flex flex-col md:flex-row md:items-center justify-between gap-6">
            <div>
                <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-[#2E7EED]/20 border border-[#2E7EED]/30 text-[#2E7EED] text-xs font-bold uppercase tracking-wider mb-3">
                    🛡️ Painel do Administrador
                </span>
                <h1 class="text-3xl md:text-4xl font-extrabold tracking-tight font-sans">Central de Gestão de Avisos</h1>
                <p class="text-gray-400 mt-2 max-w-xl text-sm font-body">Crie, edite, gerencie e analise o alcance de avisos e comunicados da equipe do APP Louvor.</p>
            </div>
            
            <div class="flex flex-wrap gap-3">
                <button onclick="openTagManager()" class="inline-flex items-center gap-2 px-5 py-3 rounded-full bg-white/10 hover:bg-white/20 border border-white/10 text-white font-bold text-sm transition-all duration-200 cursor-pointer active:scale-95">
                    <i data-lucide="tags" class="w-4 h-4"></i>
                    Tags
                </button>
                <button onclick="openCreateModal()" class="inline-flex items-center gap-2 px-5 py-3 rounded-full bg-[#2E7EED] hover:bg-[#1A6FD6] text-white font-bold text-sm shadow-lg transition-all duration-200 cursor-pointer active:scale-95">
                    <i data-lucide="plus" class="w-4 h-4"></i>
                    Novo Comunicado
                </button>
            </div>
        </div>
    </div>

    <!-- Estatísticas (Bento Grid) -->
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
        <!-- Card Ativos -->
        <div class="bg-white p-6 rounded-3xl border border-gray-100 shadow-sm flex flex-col justify-between hover:shadow-md transition-all duration-300">
            <div class="flex items-center justify-between mb-4">
                <span class="text-sm font-semibold text-gray-500">Ativos</span>
                <div class="w-10 h-10 rounded-2xl bg-[#2E7EED]/10 text-[#2E7EED] flex items-center justify-center">
                    <i data-lucide="check-circle" class="w-5 h-5"></i>
                </div>
            </div>
            <div>
                <div class="text-3xl font-extrabold text-gray-800 tracking-tight mb-0.5"><?= $totalActive ?></div>
                <span class="text-xs text-gray-400 font-medium font-body">No ar e válidos</span>
            </div>
        </div>
        
        <!-- Card Arquivados -->
        <div class="bg-white p-6 rounded-3xl border border-gray-100 shadow-sm flex flex-col justify-between hover:shadow-md transition-all duration-300">
            <div class="flex items-center justify-between mb-4">
                <span class="text-sm font-semibold text-gray-500">Arquivados</span>
                <div class="w-10 h-10 rounded-2xl bg-gray-50 text-gray-500 flex items-center justify-center">
                    <i data-lucide="archive" class="w-5 h-5"></i>
                </div>
            </div>
            <div>
                <div class="text-3xl font-extrabold text-gray-800 tracking-tight mb-0.5"><?= $totalArchived ?></div>
                <span class="text-xs text-gray-400 font-medium font-body">Guardados no histórico</span>
            </div>
        </div>
        
        <!-- Card Expirados -->
        <div class="bg-white p-6 rounded-3xl border border-gray-100 shadow-sm flex flex-col justify-between hover:shadow-md transition-all duration-300">
            <div class="flex items-center justify-between mb-4">
                <span class="text-sm font-semibold text-gray-500">Expirados</span>
                <div class="w-10 h-10 rounded-2xl bg-[#FFC107]/10 text-[#FFC107] flex items-center justify-center">
                    <i data-lucide="clock" class="w-5 h-5"></i>
                </div>
            </div>
            <div>
                <div class="text-3xl font-extrabold text-gray-800 tracking-tight mb-0.5"><?= $totalExpired ?></div>
                <span class="text-xs text-gray-400 font-medium font-body">Passaram da validade</span>
            </div>
        </div>
        
        <!-- Card Tags -->
        <div class="bg-white p-6 rounded-3xl border border-gray-100 shadow-sm flex flex-col justify-between hover:shadow-md transition-all duration-300">
            <div class="flex items-center justify-between mb-4">
                <span class="text-sm font-semibold text-gray-500">Categorias/Tags</span>
                <div class="w-10 h-10 rounded-2xl bg-teal-50 text-teal-600 flex items-center justify-center">
                    <i data-lucide="tags" class="w-5 h-5"></i>
                </div>
            </div>
            <div>
                <div class="text-3xl font-extrabold text-gray-800 tracking-tight mb-0.5"><?= count($tags) ?></div>
                <span class="text-xs text-gray-400 font-medium font-body">Etiquetas de assunto</span>
            </div>
        </div>
    </div>

    <!-- Filtros -->
    <div class="bg-white p-6 rounded-3xl shadow-sm border border-gray-100 space-y-4">
        <div class="relative w-full">
            <i data-lucide="search" class="absolute left-4 top-1/2 -translate-y-1/2 text-gray-400 w-5 h-5"></i>
            <input type="text" id="searchInput" placeholder="Buscar avisos pelo título ou conteúdo..." value="<?= htmlspecialchars($search) ?>" class="w-full h-12 pl-12 pr-4 bg-gray-50 border border-gray-100 rounded-2xl text-sm focus:outline-none focus:border-[#2E7EED] focus:ring-1 focus:ring-[#2E7EED] transition-all placeholder-gray-400 text-gray-850 font-medium">
        </div>
        
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            <!-- Filtro Status -->
            <div class="flex flex-col gap-1.5">
                <label class="text-xs font-bold text-gray-400 uppercase tracking-wider pl-1">Status</label>
                <select class="w-full h-11 px-3 bg-gray-50 hover:bg-gray-100 border border-gray-100 rounded-2xl text-sm font-semibold text-gray-700 focus:outline-none focus:border-[#2E7EED] transition-all" onchange="window.location.href='?status=' + this.value + '&priority=<?= $filterPriority ?>'">
                    <option value="active" <?= $filterStatus === 'active' ? 'selected' : '' ?>>🟢 Ativos</option>
                    <option value="archived" <?= $filterStatus === 'archived' ? 'selected' : '' ?>>📁 Arquivados</option>
                    <option value="expired" <?= $filterStatus === 'expired' ? 'selected' : '' ?>>⏳ Expirados</option>
                </select>
            </div>
            
            <!-- Filtro Prioridade -->
            <div class="flex flex-col gap-1.5">
                <label class="text-xs font-bold text-gray-400 uppercase tracking-wider pl-1">Prioridade</label>
                <select class="w-full h-11 px-3 bg-gray-50 hover:bg-gray-100 border border-gray-100 rounded-2xl text-sm font-semibold text-gray-700 focus:outline-none focus:border-[#2E7EED] transition-all" onchange="window.location.href='?status=<?= $filterStatus ?>&priority=' + this.value">
                    <option value="all" <?= $filterPriority === 'all' ? 'selected' : '' ?>>✨ Todas</option>
                    <option value="urgent" <?= $filterPriority === 'urgent' ? 'selected' : '' ?>>🚨 Urgente</option>
                    <option value="important" <?= $filterPriority === 'important' ? 'selected' : '' ?>>⭐ Importante</option>
                    <option value="info" <?= $filterPriority === 'info' ? 'selected' : '' ?>>ℹ️ Normal</option>
                </select>
            </div>
            
            <!-- Filtro Multi-Tags -->
            <div class="flex flex-col gap-1.5">
                <label class="text-xs font-bold text-gray-400 uppercase tracking-wider pl-1">Tags</label>
                <div class="relative w-full">
                    <div onclick="toggleTagDropdown()" class="w-full h-11 px-4 bg-gray-50 hover:bg-gray-100 border border-gray-100 rounded-2xl text-sm font-semibold text-gray-700 flex items-center justify-between gap-2 transition-colors cursor-pointer">
                       <span id="tagFilterLabel" class="truncate">Todas as tags</span>
                       <i data-lucide="chevron-down" class="w-4 h-4 text-gray-500"></i>
                    </div>
                    <div id="tagDropdown" class="hidden absolute top-full left-0 right-0 mt-2 bg-white border border-gray-100 rounded-2xl shadow-xl z-50 flex flex-col max-h-[220px] overflow-y-auto divide-y divide-gray-50">
                       <div onclick="selectAllTags()" class="px-4 py-2.5 hover:bg-gray-50 text-sm text-gray-700 flex items-center gap-3 cursor-pointer">
                           <input type="checkbox" id="tag-all" checked class="w-4 h-4 rounded border-gray-300 text-[#2E7EED] focus:ring-[#2E7EED]">
                           <span class="font-bold flex-1">Todas</span>
                       </div>
                       <?php foreach ($tags as $tag): ?>
                           <div onclick="toggleTagFilter(<?= $tag['id'] ?>, event)" class="px-4 py-2.5 hover:bg-gray-50 text-sm text-gray-700 flex items-center gap-3 cursor-pointer">
                               <input type="checkbox" id="tag-<?= $tag['id'] ?>" class="tag-checkbox w-4 h-4 rounded border-gray-300 text-[#2E7EED] focus:ring-[#2E7EED]" checked>
                               <span class="flex-1 font-semibold" style="color: <?= $tag['color'] ?>;">
                                   <?= htmlspecialchars($tag['name']) ?>
                               </span>
                           </div>
                       <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Título da Listagem -->
    <div class="flex items-center justify-between pt-2">
        <h3 class="text-xs font-bold uppercase tracking-wider text-gray-400 bg-gray-50 px-4 py-1.5 rounded-full">Comunicados e Avisos Registrados</h3>
    </div>

    <!-- Lista de Avisos -->
    <div id="avisosList" class="space-y-4">
        <?php if (count($avisos) > 0): ?>
            <?php foreach ($avisos as $aviso): ?>
                <div class="aviso-item-card bg-white p-6 rounded-3xl border border-gray-100 shadow-sm hover:shadow-md transition-all duration-300 animate-fade-in" data-tags='<?= json_encode(array_column($aviso['tags'], 'id')) ?>'>
                    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
                        <div class="space-y-2 flex-1">
                            <div class="flex flex-wrap items-center gap-2">
                                <?php if ($aviso['is_pinned']): ?>
                                    <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full bg-[#FFC107]/10 border border-[#FFC107]/20 text-[#D97706] text-xs font-bold uppercase tracking-wider">
                                        📌 Fixado
                                    </span>
                                <?php endif; ?>
                                
                                <?php if ($aviso['priority'] === 'urgent'): ?>
                                    <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full bg-red-50 border border-red-150 text-red-500 text-xs font-bold uppercase tracking-wider">
                                        🚨 Urgente
                                    </span>
                                <?php elseif ($aviso['priority'] === 'important'): ?>
                                    <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full bg-[#2E7EED]/10 border border-[#2E7EED]/20 text-[#2E7EED] text-xs font-bold uppercase tracking-wider">
                                        ⭐ Importante
                                    </span>
                                <?php endif; ?>
                                
                                <?php foreach ($aviso['tags'] as $tag): ?>
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold" style="background: <?= $tag['color'] ?>15; color: <?= $tag['color'] ?>;">
                                        <?= htmlspecialchars($tag['name']) ?>
                                    </span>
                                <?php endforeach; ?>
                            </div>
                            
                            <h3 class="text-lg font-bold text-gray-800 leading-tight">
                                <?= htmlspecialchars($aviso['title']) ?>
                            </h3>
                            
                            <div class="flex flex-wrap items-center gap-3 text-xs text-gray-400 font-medium font-body">
                                <span class="flex items-center gap-1"><i data-lucide="user" class="w-3.5 h-3.5"></i> <?= htmlspecialchars($aviso['author_name']) ?></span>
                                <span>•</span>
                                <span class="flex items-center gap-1"><i data-lucide="calendar" class="w-3.5 h-3.5"></i> <?= date('d/m/Y', strtotime($aviso['created_at'])) ?></span>
                                <?php if ($aviso['expires_at']): ?>
                                    <span>•</span>
                                    <span class="flex items-center gap-1 text-[#D97706]"><i data-lucide="calendar-off" class="w-3.5 h-3.5"></i> Expira em: <?= date('d/m/Y', strtotime($aviso['expires_at'])) ?></span>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <div class="flex items-center gap-2 shrink-0">
                            <button onclick="viewStats(<?= $aviso['id'] ?>)" class="w-10 h-10 rounded-full bg-gray-50 hover:bg-[#2E7EED]/10 text-gray-500 hover:text-[#2E7EED] flex items-center justify-center border border-gray-150 transition-all duration-200 active:scale-90 cursor-pointer" title="Ver Estatísticas">
                                <i data-lucide="bar-chart-2" class="w-4 h-4"></i>
                            </button>
                            <button onclick="editAviso(<?= htmlspecialchars(json_encode($aviso)) ?>)" class="w-10 h-10 rounded-full bg-gray-50 hover:bg-[#FFC107]/10 text-gray-500 hover:text-[#D97706] flex items-center justify-center border border-gray-150 transition-all duration-200 active:scale-90 cursor-pointer" title="Editar">
                                <i data-lucide="edit-2" class="w-4 h-4"></i>
                            </button>
                            <button onclick="deleteAviso(<?= $aviso['id'] ?>)" class="w-10 h-10 rounded-full bg-gray-50 hover:bg-red-50 text-gray-500 hover:text-red-500 flex items-center justify-center border border-gray-150 transition-all duration-200 active:scale-90 cursor-pointer" title="Excluir">
                                <i data-lucide="trash-2" class="w-4 h-4"></i>
                            </button>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="bg-gray-50 border border-dashed border-gray-200 rounded-3xl p-12 text-center animate-fade-in">
                <div class="text-5xl mb-3">📭</div>
                <h4 class="text-lg font-bold text-gray-800 mb-1">Nenhum aviso encontrado</h4>
                <p class="text-sm text-gray-500">Crie um novo aviso para que o mural ganhe vida.</p>
            </div>
        <?php endif; ?>
    </div>
</main>

<!-- Modal Criar/Editar Aviso -->
<div id="avisoModal" class="hidden fixed inset-0 z-50 bg-slate-950/60 backdrop-blur-sm flex items-center justify-center p-4 transition-opacity duration-300 opacity-0">
    <div id="avisoModalContent" class="bg-white w-full max-w-lg rounded-3xl p-6 shadow-2xl transition-all transform scale-95 duration-300 max-h-[90vh] overflow-y-auto border border-gray-100">
        <div class="flex items-center justify-between mb-6 pb-4 border-b border-gray-100">
            <h2 id="modalTitle" class="text-xl font-bold text-gray-850">Novo Aviso</h2>
            <button onclick="closeModalForce('avisoModal')" class="w-8 h-8 rounded-full bg-gray-50 hover:bg-gray-100 flex items-center justify-center text-gray-500 transition-colors cursor-pointer border border-transparent hover:border-gray-200">
                <i data-lucide="x" class="w-4 h-4"></i>
            </button>
        </div>
        
        <form id="avisoForm" onsubmit="saveAviso(event)" class="space-y-4">
            <input type="hidden" id="avisoId">
            
            <div class="flex flex-col gap-1.5">
                <label class="text-xs font-bold text-gray-400 uppercase tracking-wider pl-1">Título</label>
                <input type="text" id="avisoTitle" class="w-full h-11 px-4 bg-gray-50 border border-gray-100 rounded-2xl text-sm focus:outline-none focus:border-[#2E7EED] focus:ring-1 focus:ring-[#2E7EED] transition-all placeholder-gray-400 text-gray-800 font-semibold" required placeholder="Ex: Ensaio especial neste sábado">
            </div>
            
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div class="flex flex-col gap-1.5">
                    <label class="text-xs font-bold text-gray-400 uppercase tracking-wider pl-1">Prioridade</label>
                    <select id="avisoPriority" class="w-full h-11 px-3 bg-gray-50 hover:bg-gray-100 border border-gray-100 rounded-2xl text-sm font-semibold text-gray-700 focus:outline-none focus:border-[#2E7EED] transition-all">
                        <option value="info">ℹ️ Normal</option>
                        <option value="important">⭐ Importante</option>
                        <option value="urgent">🚨 Urgente</option>
                    </select>
                </div>
                
                <div class="flex flex-col gap-1.5">
                    <label class="text-xs font-bold text-gray-400 uppercase tracking-wider pl-1">Público</label>
                    <select id="avisoTarget" class="w-full h-11 px-3 bg-gray-50 hover:bg-gray-100 border border-gray-100 rounded-2xl text-sm font-semibold text-gray-700 focus:outline-none focus:border-[#2E7EED] transition-all">
                        <option value="all">👥 Todos</option>
                        <option value="team">🎸 Equipe</option>
                        <option value="admins">👑 Líderes</option>
                    </select>
                </div>
            </div>
            
            <div class="flex flex-col gap-1.5">
                <label class="text-xs font-bold text-gray-400 uppercase tracking-wider pl-1">Tags</label>
                <div id="tagSelector" class="flex flex-wrap gap-2 p-3 bg-gray-50 border border-gray-100 rounded-2xl">
                    <?php foreach ($tags as $tag): ?>
                        <button type="button" data-tag-id="<?= $tag['id'] ?>" class="tag-option px-3.5 py-1.5 rounded-full text-xs font-semibold border border-gray-150 bg-white text-gray-600 transition-all active:scale-95 duration-200 cursor-pointer flex items-center gap-1.5" style="--tag-color: <?= $tag['color'] ?>;" onclick="toggleTagOption(this)">
                            <span class="w-2 h-2 rounded-full" style="background-color: <?= $tag['color'] ?>;"></span>
                            <?= htmlspecialchars($tag['name']) ?>
                        </button>
                    <?php endforeach; ?>
                </div>
            </div>
            
            <div class="flex flex-col gap-1.5">
                <label class="text-xs font-bold text-gray-400 uppercase tracking-wider pl-1">Expira em (opcional)</label>
                <input type="date" id="avisoExpires" class="w-full h-11 px-4 bg-gray-50 border border-gray-100 rounded-2xl text-sm focus:outline-none focus:border-[#2E7EED] focus:ring-1 focus:ring-[#2E7EED] transition-all text-gray-800 font-semibold">
            </div>
            
            <div class="flex flex-col gap-1.5">
                <label class="text-xs font-bold text-gray-400 uppercase tracking-wider pl-1">Mensagem</label>
                <div class="border border-gray-100 rounded-2xl overflow-hidden shadow-sm">
                    <div id="editor" style="height: 180px; background: white;" class="font-sans text-sm"></div>
                </div>
            </div>
            
            <div class="flex gap-3 pt-4 border-t border-gray-100">
                <button type="button" onclick="closeModalForce('avisoModal')" class="flex-1 py-3 bg-gray-50 hover:bg-gray-100 border border-gray-100 text-gray-700 font-bold text-sm rounded-2xl active:scale-95 transition-all cursor-pointer">Cancelar</button>
                <button type="submit" class="flex-[2] py-3 bg-[#2E7EED] hover:bg-[#1A6FD6] text-white font-bold text-sm rounded-2xl shadow-md active:scale-95 transition-all cursor-pointer">Publicar</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal Gerenciar Tags -->
<div id="tagModal" class="hidden fixed inset-0 z-50 bg-slate-950/60 backdrop-blur-sm flex items-center justify-center p-4 transition-opacity duration-300 opacity-0">
    <div id="tagModalContent" class="bg-white w-full max-w-md rounded-3xl p-6 shadow-2xl transition-all transform scale-95 duration-300 max-h-[85vh] overflow-y-auto border border-gray-100">
        <div class="flex items-center justify-between mb-6 pb-4 border-b border-gray-100">
            <h2 class="text-xl font-bold text-gray-850 flex items-center gap-2">🏷️ Gerenciar Tags</h2>
            <button onclick="closeModalForce('tagModal')" class="w-8 h-8 rounded-full bg-gray-50 hover:bg-gray-100 flex items-center justify-center text-gray-500 transition-colors cursor-pointer border border-transparent hover:border-gray-200">
                <i data-lucide="x" class="w-4 h-4"></i>
            </button>
        </div>
        
        <div id="tagsList" class="space-y-2 max-h-[50vh] overflow-y-auto pr-1"></div>
        
        <button onclick="createNewTag()" class="w-full mt-6 py-3 bg-[#2E7EED] hover:bg-[#1A6FD6] text-white font-bold text-sm rounded-2xl shadow-md active:scale-95 transition-all cursor-pointer flex items-center justify-center gap-2">
            <i data-lucide="plus" class="w-4 h-4"></i>
            Nova Tag
        </button>
    </div>
</div>

<!-- Quill Editor e Scripts -->
<script src="https://cdn.quilljs.com/1.3.6/quill.js"></script>
<script>
    // Quill Editor com visual limpo
    const quill = new Quill('#editor', {
        theme: 'snow',
        placeholder: 'Escreva a mensagem do aviso...',
        modules: {
            toolbar: [
                [{ 'header': [1, 2, 3, false] }],
                ['bold', 'italic', 'underline', 'strike'],
                [{ 'list': 'ordered'}, { 'list': 'bullet' }],
                ['link'],
                ['clean']
            ]
        }
    });
    
    let selectedTags = [];
    
    // Tag filter dropdown
    function toggleTagDropdown() {
        const dropdown = document.getElementById('tagDropdown');
        dropdown.classList.toggle('hidden');
    }
    
    // Close dropdown on click outside
    document.addEventListener('click', function(event) {
        const trigger = document.querySelector('[onclick="toggleTagDropdown()"]');
        const dropdown = document.getElementById('tagDropdown');
        if (trigger && dropdown && !trigger.contains(event.target) && !dropdown.contains(event.target)) {
            dropdown.classList.add('hidden');
        }
    });

    // Tag selector in Form
    function toggleTagOption(element) {
        const tagId = element.dataset.tagId;
        const color = element.style.getPropertyValue('--tag-color');
        
        if (selectedTags.includes(tagId)) {
            selectedTags = selectedTags.filter(id => id !== tagId);
            element.classList.remove('selected-tag');
            element.style.borderColor = 'transparent';
            element.style.backgroundColor = 'var(--bg-gray-50, #f9fafb)';
            element.style.color = '#4b5563';
        } else {
            selectedTags.push(tagId);
            element.classList.add('selected-tag');
            element.style.borderColor = color;
            element.style.backgroundColor = color + '15';
            element.style.color = color;
        }
    }
    
    // Multi-select Tags logic for filtering list
    let activeFilterTags = [];
    <?php foreach ($tags as $tag): ?>
        activeFilterTags.push(<?= $tag['id'] ?>);
    <?php endforeach; ?>
    
    function updateTagFilterLabel() {
        const checkboxes = document.querySelectorAll('.tag-checkbox');
        const checkedCount = Array.from(checkboxes).filter(cb => cb.checked).length;
        const label = document.getElementById('tagFilterLabel');
        
        if (checkedCount === checkboxes.length) {
            label.innerText = 'Todas as tags';
            document.getElementById('tag-all').checked = true;
        } else if (checkedCount === 0) {
            label.innerText = 'Nenhuma tag';
            document.getElementById('tag-all').checked = false;
        } else {
            label.innerText = `${checkedCount} tag(s) selecionada(s)`;
            document.getElementById('tag-all').checked = false;
        }
    }
    
    function selectAllTags() {
        const mainCheckbox = document.getElementById('tag-all');
        const isChecked = mainCheckbox.checked;
        const checkboxes = document.querySelectorAll('.tag-checkbox');
        
        checkboxes.forEach(cb => {
            cb.checked = isChecked;
        });
        
        activeFilterTags = [];
        if (isChecked) {
            checkboxes.forEach(cb => {
                const id = cb.id.replace('tag-', '');
                activeFilterTags.push(parseInt(id));
            });
        }
        
        updateTagFilterLabel();
        filterListByTags();
    }
    
    function toggleTagFilter(id, event) {
        event.stopPropagation();
        const cb = document.getElementById('tag-' + id);
        cb.checked = !cb.checked;
        
        if (cb.checked) {
            if (!activeFilterTags.includes(id)) activeFilterTags.push(id);
        } else {
            activeFilterTags = activeFilterTags.filter(t => t !== id);
        }
        
        updateTagFilterLabel();
        filterListByTags();
    }
    
    function filterListByTags() {
        const cards = document.querySelectorAll('.aviso-item-card');
        cards.forEach(card => {
            const cardTags = JSON.parse(card.dataset.tags || '[]');
            
            if (activeFilterTags.length === 0) {
                card.style.display = 'none';
                return;
            }
            
            // Se tiver pelo menos uma tag correspondente ou se não tiver tags nenhuma (só mostramos se activeFilterTags incluir)
            const hasMatch = cardTags.length === 0 || cardTags.some(tId => activeFilterTags.includes(parseInt(tId)));
            
            if (hasMatch) {
                card.style.display = '';
            } else {
                card.style.display = 'none';
            }
        });
    }

    // Modal Control with smooth animations
    function openModal(id) {
        const modal = document.getElementById(id);
        const content = document.getElementById(id + 'Content');
        
        modal.classList.remove('hidden');
        modal.classList.add('flex');
        
        document.body.style.overflow = 'hidden';
        
        // Reflow force
        modal.offsetHeight;
        
        modal.classList.remove('opacity-0');
        modal.classList.add('opacity-100');
        
        if (content) {
            content.classList.remove('scale-95');
            content.classList.add('scale-100');
        }
    }
    
    function closeModalForce(id) {
        const modal = document.getElementById(id);
        const content = document.getElementById(id + 'Content');
        
        modal.classList.remove('opacity-100');
        modal.classList.add('opacity-0');
        
        if (content) {
            content.classList.remove('scale-100');
            content.classList.add('scale-95');
        }
        
        setTimeout(() => {
            modal.classList.remove('flex');
            modal.classList.add('hidden');
            document.body.style.overflow = '';
        }, 300);
    }
    
    function openCreateModal() {
        document.getElementById('modalTitle').innerText = 'Novo Aviso';
        document.getElementById('avisoForm').reset();
        document.getElementById('avisoId').value = '';
        quill.setContents([]);
        selectedTags = [];
        
        document.querySelectorAll('.tag-option').forEach(btn => {
            btn.classList.remove('selected-tag');
            btn.style.borderColor = '#e5e7eb'; // border-gray-200
            btn.style.backgroundColor = '#ffffff';
            btn.style.color = '#4b5563';
        });
        
        openModal('avisoModal');
    }
    
    function editAviso(aviso) {
        document.getElementById('modalTitle').innerText = 'Editar Aviso';
        document.getElementById('avisoId').value = aviso.id;
        document.getElementById('avisoTitle').value = aviso.title;
        document.getElementById('avisoPriority').value = aviso.priority;
        document.getElementById('avisoTarget').value = aviso.target_audience || 'all';
        document.getElementById('avisoExpires').value = aviso.expires_at || '';
        quill.root.innerHTML = aviso.message || '';
        
        // Reset tags
        selectedTags = aviso.tags.map(t => String(t.id));
        document.querySelectorAll('.tag-option').forEach(btn => {
            const tagId = btn.dataset.tagId;
            if (selectedTags.includes(tagId)) {
                btn.classList.add('selected-tag');
                const color = btn.style.getPropertyValue('--tag-color');
                btn.style.borderColor = color;
                btn.style.backgroundColor = color + '15';
                btn.style.color = color;
            } else {
                btn.classList.remove('selected-tag');
                btn.style.borderColor = '#e5e7eb';
                btn.style.backgroundColor = '#ffffff';
                btn.style.color = '#4b5563';
            }
        });
        
        openModal('avisoModal');
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
        formData.append('type', 'geral'); // Tipo padrão do backend original
        formData.append('target_audience', document.getElementById('avisoTarget').value);
        formData.append('expires_at', document.getElementById('avisoExpires').value);
        formData.append('tags', JSON.stringify(selectedTags));
        
        const csrfToken = document.querySelector('meta[name="csrf-token"]').content;
        formData.append('csrf_token', csrfToken); // Enviar também em POST regular
        
        try {
            const response = await fetch('avisos.php', {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': csrfToken },
                body: formData
            });
            
            if (response.ok) {
                window.location.reload();
            } else {
                const text = await response.text();
                alert('Erro ao salvar aviso: ' + text);
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
        
        const csrfToken = document.querySelector('meta[name="csrf-token"]').content;
        formData.append('csrf_token', csrfToken);
        
        try {
            const response = await fetch('avisos.php', {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': csrfToken },
                body: formData
            });
            
            if (response.ok) {
                window.location.reload();
            } else {
                alert('Erro ao deletar aviso');
            }
        } catch (error) {
            alert('Erro ao deletar aviso');
        }
    }
    
    async function viewStats(id) {
        try {
            // Chamada de API AJAX corrigida para apontar para a pasta correta /api/admin/
            const response = await fetch(`../api/admin/avisos_api.php?action=get_aviso_stats&aviso_id=${id}`);
            const data = await response.json();
            
            if (data.success) {
                alert(`Estatísticas de Alcance:\n\nLeituras Confirmadas: ${data.read_count} de ${data.total_users} membros (${data.read_percentage}%)`);
            } else {
                alert('Não foi possível obter estatísticas.');
            }
        } catch (error) {
            alert('Erro ao carregar estatísticas');
        }
    }
    
    function openTagManager() {
        loadTags();
        openModal('tagModal');
    }
    
    async function loadTags() {
        try {
            // Chamada de API AJAX corrigida para apontar para /api/admin/
            const response = await fetch('../api/admin/avisos_api.php?action=list_tags');
            const data = await response.json();
            
            if (data.success) {
                const html = data.tags.map(tag => `
                    <div class="flex items-center justify-between p-3.5 bg-gray-50 border border-gray-100 rounded-2xl hover:bg-gray-100 transition-all">
                        <div class="flex items-center gap-3">
                            <div class="w-4 h-4 rounded-full border shadow-sm shrink-0" style="background: ${tag.color}; border-color: ${tag.color}44;"></div>
                            <div class="flex flex-col">
                                <div class="text-sm font-bold text-gray-800">${tag.name}</div>
                                <div class="text-[10px] text-gray-400 font-bold uppercase tracking-wider">${tag.is_default ? 'Padrão' : 'Personalizada'}</div>
                            </div>
                        </div>
                        ${!tag.is_default ? `
                            <button onclick="deleteTag(${tag.id})" class="w-8 h-8 rounded-full bg-white hover:bg-red-50 text-gray-500 hover:text-red-500 flex items-center justify-center border border-gray-150 transition-all cursor-pointer">
                                <i data-lucide="trash-2" class="w-3.5 h-3.5"></i>
                            </button>
                        ` : ''}
                    </div>
                `).join('');
                
                document.getElementById('tagsList').innerHTML = html || '<div class="text-center text-sm text-gray-400 py-6">Nenhuma tag cadastrada</div>';
                lucide.createIcons();
            }
        } catch (error) {
            console.error('Erro ao carregar tags:', error);
        }
    }
    
    function createNewTag() {
        const name = prompt('Nome da nova tag:');
        if (!name) return;
        
        const color = prompt('Cor em Hexadecimal (ex: #2E7EED):', '#2E7EED');
        if (!color) return;
        
        const formData = new FormData();
        formData.append('action', 'create_tag');
        formData.append('name', name);
        formData.append('color', color);
        formData.append('icon', 'tag');
        
        const csrfToken = document.querySelector('meta[name="csrf-token"]').content;
        
        fetch('../api/admin/avisos_api.php', {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': csrfToken },
            body: formData
        }).then(res => res.json())
          .then(data => {
              if (data.success) {
                  loadTags();
                  setTimeout(() => window.location.reload(), 300);
              } else {
                  alert('Erro ao criar tag: ' + (data.error || 'Erro desconhecido'));
              }
          });
    }
    
    async function deleteTag(id) {
        if (!confirm('Excluir esta tag permanentemente? Todos os avisos associados perderão esta etiqueta.')) return;
        
        const formData = new FormData();
        formData.append('action', 'delete_tag');
        formData.append('id', id);
        
        const csrfToken = document.querySelector('meta[name="csrf-token"]').content;
        
        try {
            const response = await fetch('../api/admin/avisos_api.php', {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': csrfToken },
                body: formData
            });
            const data = await response.json();
            
            if (data.success) {
                loadTags();
                setTimeout(() => window.location.reload(), 300);
            } else {
                alert('Erro ao deletar tag');
            }
        } catch (error) {
            alert('Erro ao deletar tag');
        }
    }
    
    // Filtro de busca na listagem nativa
    let searchTimeout;
    document.getElementById('searchInput').addEventListener('input', (e) => {
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(() => {
            window.location.href = `?search=${encodeURIComponent(e.target.value)}&status=<?= $filterStatus ?>&priority=<?= $filterPriority ?>`;
        }, 600);
    });
</script>

<?php renderAppFooter(); ?>
