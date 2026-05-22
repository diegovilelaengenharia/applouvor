<?php
// admin/avisos.php - Redesign com visual roxo moderno e Reações
require_once '../src/helpers/auth.php';
require_once '../src/config/db.php';
require_once '../src/layout/layout.php';
require_once '../src/helpers/notification_system.php';

checkLogin();

$userId = $_SESSION['user_id'] ?? 1;
$isAdmin = ($_SESSION['user_role'] ?? '') === 'admin';

// --- LÓGICA DE POST (CRUD & REAÇÕES) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validação CSRF
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        http_response_code(403);
        die('Ação não autorizada. Por favor, recarregue a página e tente novamente.');
    }

    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'toggle_reaction':
                $avisoId = $_POST['aviso_id'];
                $type = $_POST['reaction_type'];
                
                // Toggle logic
                $stmt = $pdo->prepare("SELECT id FROM aviso_reactions WHERE aviso_id=? AND user_id=? AND reaction_type=?");
                $stmt->execute([$avisoId, $userId, $type]);
                if ($stmt->fetch()) {
                    $pdo->prepare("DELETE FROM aviso_reactions WHERE aviso_id=? AND user_id=? AND reaction_type=?")->execute([$avisoId, $userId, $type]);
                } else {
                    $pdo->prepare("INSERT INTO aviso_reactions (aviso_id, user_id, reaction_type) VALUES (?, ?, ?)")->execute([$avisoId, $userId, $type]);
                }
                // Redirect to avoid resubmission
                header("Location: avisos.php" . (isset($_SERVER['QUERY_STRING']) ? '?'.$_SERVER['QUERY_STRING'] : '')); 
                exit;

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

// Fetch Reactions for Logic
if (!empty($avisos)) {
    $avisoIds = array_column($avisos, 'id');
    $placeholders = str_repeat('?,', count($avisoIds) - 1) . '?';
    try {
        $stmtReactions = $pdo->prepare("SELECT * FROM aviso_reactions WHERE aviso_id IN ($placeholders)");
        $stmtReactions->execute($avisoIds);
        $allReactions = $stmtReactions->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        // Table might not exist if migration failed, fallback gracefully
        $allReactions = [];
    }
    
    // Map reactions to avisos
    foreach ($avisos as &$av) {
        // Init default structure
        $av['reactions'] = ['like' => 0, 'confirm' => 0];
        $av['user_reacted'] = ['like' => false, 'confirm' => false];
        $av['tags'] = []; // Placeholder for tags query below
        
        foreach ($allReactions as $r) {
            if ($r['aviso_id'] == $av['id']) {
                if (isset($av['reactions'][$r['reaction_type']])) {
                    $av['reactions'][$r['reaction_type']]++;
                }
                if ($r['user_id'] == $userId) {
                    $av['user_reacted'][$r['reaction_type']] = true;
                }
            }
        }
    }
    unset($av); // Quebrar a referência
}


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
unset($aviso); // Quebrar a referência

renderAppHeader('Avisos');
?>

<main class="max-w-4xl mx-auto px-4 sm:px-6 py-8 mb-32 space-y-8" id="mural-container">
    
    <!-- Hero / Header Section (Sacred Minimalist) -->
    <div class="reveal-item relative overflow-hidden rounded-[2px] bg-[#121316] text-white p-8 shadow-sm border border-neutral-800">
        <div class="absolute -right-16 -top-16 w-64 h-64 bg-[#2E7EED]/5 rounded-full blur-3xl pointer-events-none"></div>
        
        <div class="relative z-10 flex flex-col md:flex-row md:items-center justify-between gap-6">
            <div>
                <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-[2px] bg-[#2E7EED]/10 border border-[#2E7EED]/20 text-[#2E7EED] text-[10px] font-bold uppercase tracking-wider mb-3">
                    <i data-lucide="megaphone" class="w-3.5 h-3.5"></i> Mural Virtual
                </span>
                <h1 class="text-3xl font-bold tracking-tight font-sans">Mural de Avisos</h1>
                <p class="text-neutral-400 mt-2 max-w-xl text-sm font-body leading-relaxed">Fique por dentro das atualizações, devocionais e avisos importantes da nossa equipe de louvor.</p>
            </div>
            
            <?php if ($isAdmin): ?>
            <button onclick="openCreateDrawer()" class="active:scale-[0.97] shrink-0 inline-flex items-center gap-2 px-5 py-3 rounded-[2px] bg-[#2E7EED] hover:bg-[#1A6FD6] text-white font-bold text-sm shadow-sm transition-all duration-150 will-change-transform">
                <i data-lucide="plus" class="w-4 h-4 shrink-0"></i>
                Novo Comunicado
            </button>
            <?php endif; ?>
        </div>
    </div>

    <!-- Header Actions (Search & Filter - Sharp Bento style) -->
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 items-center bg-[#18191D] p-4 rounded-[2px] border border-neutral-850 shadow-sm">
        <!-- Search Bar -->
        <div class="relative sm:col-span-2">
            <form method="GET" class="m-0">
                <?php if ($showArchived): ?><input type="hidden" name="archived" value="1"><?php endif; ?>
                <?php if ($showHistory): ?><input type="hidden" name="history" value="1"><?php endif; ?>
                <?php if ($filterType !== 'all'): ?><input type="hidden" name="type" value="<?= $filterType ?>"><?php endif; ?>
                <i data-lucide="search" class="absolute left-4 top-1/2 -translate-y-1/2 text-neutral-500 w-4 h-4"></i>
                <input name="search" value="<?= htmlspecialchars($search) ?>" class="w-full h-12 pl-12 pr-4 bg-[#121316] border border-neutral-800 rounded-[2px] text-sm focus:outline-none focus:border-[#2E7EED] transition-all placeholder-neutral-500 text-neutral-200" placeholder="Buscar avisos ou comunicados..." type="text"/>
            </form>
        </div>
        
        <!-- Filter Dropdown -->
        <div class="relative w-full">
            <button type="button" class="active:scale-[0.98] w-full h-12 px-4 bg-[#121316] hover:bg-[#1C1D22] border border-neutral-800 rounded-[2px] text-sm font-semibold text-neutral-300 flex items-center justify-between gap-2 transition-all will-change-transform" id="filterButton" onclick="toggleFilterDropdown()">
                <span class="flex items-center gap-2">
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
                <i data-lucide="chevron-down" class="w-4 h-4 text-neutral-500 transition-transform duration-200" id="filterArrow"></i>
            </button>
            <div class="hidden absolute right-0 left-0 mt-2 bg-[#18191D] border border-neutral-800 rounded-[2px] shadow-xl z-50 flex-col overflow-hidden divide-y divide-neutral-850" id="filterDropdown">
                <?php foreach ($filterLabels as $key => $label): ?>
                    <a href="?<?= http_build_query(array_merge($_GET, ['type' => $key])) ?>" class="px-4 py-3 hover:bg-[#1E2026] text-sm text-neutral-350 flex items-center gap-2 transition-colors <?= $filterType === $key ? 'bg-[#2E7EED]/10 text-[#2E7EED] font-bold' : '' ?>">
                        <?= $label ?>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <!-- Timeline Divider -->
    <div class="flex justify-center items-center py-2">
        <span class="text-[10px] font-bold uppercase tracking-widest text-neutral-400 bg-neutral-900 border border-neutral-800 px-4 py-1.5 rounded-[2px]">Comunicados Recentes</span>
    </div>

    <!-- Avisos List (Bento Timeline) -->
    <?php if (empty($avisos)): ?>
        <div class="bg-[#121316] border border-dashed border-neutral-850 rounded-[2px] p-12 text-center">
            <i data-lucide="inbox" class="w-12 h-12 text-neutral-600 mx-auto mb-3"></i>
            <h4 class="text-base font-bold text-neutral-300 mb-1">Nenhum aviso encontrado</h4>
            <p class="text-xs text-neutral-500 mb-6"><?= !empty($search) ? 'Tente ajustar os termos da sua busca.' : 'Não há avisos registrados no momento.' ?></p>
        </div>
    <?php else: ?>
        <div class="space-y-6">
        <?php 
        $index = 0;
        foreach ($avisos as $aviso): 
            $isMe = ($aviso['created_by'] == $userId);
            
            // Setup priority design - STRICT PURPLE BAN & RUBI URGENCY
            $cardBorder = 'border border-neutral-850';
            $leftAccent = '';
            
            if ($aviso['priority'] === 'urgent') {
                // Rubi sóbria para urgência
                $cardBorder = 'border border-[#B32424]';
                $leftAccent = 'border-l-[3px] border-l-[#B32424]';
            } elseif ($aviso['priority'] === 'important') {
                $cardBorder = 'border border-[#2E7EED]/30';
                $leftAccent = 'border-l-[3px] border-l-[#2E7EED]';
            }
            
            $staggerClass = 'reveal-stagger-' . min(4, max(1, ++$index));
        ?>
            <!-- Bento Announcement Card -->
            <div class="reveal-item <?= $staggerClass ?> bg-[#18191D] rounded-[2px] p-6 shadow-sm hover:shadow-md transition-all duration-300 <?= $cardBorder ?> <?= $leftAccent ?> relative group">
                
                <!-- Card Header -->
                <div class="flex items-start justify-between gap-4">
                    <div class="flex items-center gap-3">
                        <!-- Avatar (Sharp geometry) -->
                        <?php 
                            $avatarPath = $aviso['author_avatar'];
                            if ($avatarPath && !filter_var($avatarPath, FILTER_VALIDATE_URL) && strpos($avatarPath, 'data:') !== 0) {
                                if (strpos($avatarPath, '/') !== 0) { $avatarPath = '../' . $avatarPath; }
                            }
                        ?>
                        <?php if (!empty($avatarPath)): ?>
                            <img src="<?= htmlspecialchars($avatarPath) ?>" alt="Foto de <?= htmlspecialchars($aviso['author_name'] ?? 'Autor') ?>" class="w-10 h-10 rounded-[2px] object-cover shadow-sm shrink-0" onerror="this.onerror=null;this.src='https://ui-avatars.com/api/?name=<?= urlencode($aviso['author_name'] ?? 'A') ?>&background=2E7EED&color=fff&bold=true';">
                        <?php else: ?>
                            <div class="w-10 h-10 rounded-[2px] bg-[#2E7EED]/10 text-[#2E7EED] flex items-center justify-center font-bold text-sm shrink-0">
                                <?= strtoupper(substr($aviso['author_name'] ?? 'A', 0, 1)) ?>
                            </div>
                        <?php endif; ?>
                        
                        <div>
                            <div class="text-sm font-bold text-neutral-200"><?= htmlspecialchars($aviso['author_name'] ?? 'Administrador') ?></div>
                            <div class="text-[10px] text-neutral-500 font-bold uppercase tracking-wider"><?= date('d/m/Y \à\s H:i', strtotime($aviso['created_at'])) ?></div>
                        </div>
                    </div>

                    <!-- Badges -->
                    <div class="flex items-center gap-2">
                        <?php if ($aviso['priority'] === 'urgent'): ?>
                            <span class="inline-flex items-center gap-1 text-[9px] uppercase font-bold tracking-widest bg-[#B32424]/10 border border-[#B32424]/20 text-[#FF4D4D] px-2.5 py-1 rounded-[2px]">🚨 Urgente</span>
                        <?php elseif ($aviso['priority'] === 'important'): ?>
                            <span class="inline-flex items-center gap-1 text-[9px] uppercase font-bold tracking-widest bg-[#2E7EED]/10 border border-[#2E7EED]/20 text-[#2E7EED] px-2.5 py-1 rounded-[2px]">⭐ Importante</span>
                        <?php endif; ?>
                        
                        <!-- Admin Action Trigger -->
                        <?php if ($isAdmin): ?>
                        <div class="relative shrink-0">
                            <button class="p-1.5 rounded-[2px] hover:bg-neutral-850 text-neutral-450 admin-dropdown-btn active:scale-90 transition-all" onclick="toggleAdminMenu('admin-menu-<?= $aviso['id'] ?>', event)">
                                <i data-lucide="more-vertical" class="w-4 h-4"></i>
                            </button>
                            
                            <!-- Admin Dropdown Menu (Sacred sharp style) -->
                            <div id="admin-menu-<?= $aviso['id'] ?>" class="hidden absolute right-0 mt-1 w-36 bg-[#1C1D22] border border-neutral-800 rounded-[2px] shadow-xl z-50 overflow-hidden divide-y divide-neutral-850">
                                <button onclick="editAviso(<?= htmlspecialchars(json_encode($aviso)) ?>)" class="w-full text-left px-4 py-2.5 hover:bg-neutral-800 text-xs font-semibold text-neutral-300 flex items-center gap-2 transition-colors">
                                    <i data-lucide="edit-2" class="w-3.5 h-3.5"></i> Editar
                                </button>
                                <form method="POST" class="m-0 w-full">
                                    <?= App\AuthMiddleware::csrfField() ?>
                                    <input type="hidden" name="action" value="archive">
                                    <input type="hidden" name="id" value="<?= $aviso['id'] ?>">
                                    <button type="submit" class="w-full text-left px-4 py-2.5 hover:bg-neutral-800 text-xs font-semibold text-neutral-300 flex items-center gap-2 transition-colors">
                                        <i data-lucide="archive" class="w-3.5 h-3.5"></i> Arquivar
                                    </button>
                                </form>
                                <form method="POST" class="m-0 w-full" onsubmit="return confirm('Tem certeza que deseja deletar este aviso?')">
                                    <?= App\AuthMiddleware::csrfField() ?>
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="id" value="<?= $aviso['id'] ?>">
                                    <button type="submit" class="w-full text-left px-4 py-2.5 hover:bg-red-950/20 text-xs font-semibold text-[#FF4D4D] flex items-center gap-2 transition-colors">
                                        <i data-lucide="trash-2" class="w-3.5 h-3.5"></i> Deletar
                                    </button>
                                </form>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Card Content -->
                <div class="mt-4 space-y-3">
                    <h3 class="text-base font-bold text-neutral-100 leading-tight tracking-tight"><?= htmlspecialchars($aviso['title']) ?></h3>
                    <?php
                    // Mensagem segura do editor WYSIWYG
                    $msgSafe = trim(strip_tags($aviso['message'] ?? '', '<p><br><strong><em><b><i><ul><ol><li>'));
                    if ($msgSafe === '') { $msgSafe = htmlspecialchars(trim(strip_tags($aviso['message'] ?? ''))); }
                    ?>
                    <div class="text-neutral-400 text-sm break-words font-sans leading-relaxed [&_p]:mb-2 [&_ul]:list-disc [&_ul]:pl-5 [&_ol]:list-decimal [&_ol]:pl-5"><?= $msgSafe ?></div>
                </div>
                
                <!-- Tags -->
                <?php if (!empty($aviso['tags'])): ?>
                    <div class="flex flex-wrap gap-1.5 mt-4">
                        <?php foreach ($aviso['tags'] as $tag): ?>
                            <span class="inline-flex items-center text-[9px] uppercase font-bold tracking-widest text-[#2E7EED] bg-[#2E7EED]/10 border border-[#2E7EED]/20 px-2.5 py-0.5 rounded-[2px]">#<?= htmlspecialchars($tag['name']) ?></span>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <!-- Elastic Reactions Footer (Spring Scale active:scale-[0.96]) -->
                <div class="flex items-center gap-4 mt-6 pt-4 border-t border-neutral-850">
                    <form method="POST" class="m-0">
                        <?= App\AuthMiddleware::csrfField() ?>
                        <input type="hidden" name="action" value="toggle_reaction">
                        <input type="hidden" name="aviso_id" value="<?= $aviso['id'] ?>">
                        <input type="hidden" name="reaction_type" value="like">
                        <button type="submit" class="active:scale-[0.96] inline-flex items-center gap-1.5 px-4 py-2 text-xs font-bold rounded-[2px] transition-all duration-150 transform will-change-transform <?= $aviso['user_reacted']['like'] ? 'bg-[#2E7EED]/10 border border-[#2E7EED]/20 text-[#2E7EED]' : 'text-neutral-400 bg-neutral-900 border border-neutral-800 hover:bg-neutral-850' ?>">
                            <i data-lucide="heart" class="w-4 h-4 shrink-0 <?= $aviso['user_reacted']['like'] ? 'fill-current' : '' ?>"></i>
                            <span><?= $aviso['reactions']['like'] ?: 'Curtir' ?></span>
                        </button>
                    </form>
                    
                    <form method="POST" class="m-0">
                        <?= App\AuthMiddleware::csrfField() ?>
                        <input type="hidden" name="action" value="toggle_reaction">
                        <input type="hidden" name="aviso_id" value="<?= $aviso['id'] ?>">
                        <input type="hidden" name="reaction_type" value="confirm">
                        <button type="submit" class="active:scale-[0.96] inline-flex items-center gap-1.5 px-4 py-2 text-xs font-bold rounded-[2px] transition-all duration-150 transform will-change-transform <?= $aviso['user_reacted']['confirm'] ? 'bg-emerald-500/10 border border-emerald-500/20 text-[#10B981]' : 'text-neutral-400 bg-neutral-900 border border-neutral-800 hover:bg-neutral-850' ?>">
                            <i data-lucide="check-circle" class="w-4 h-4 shrink-0 <?= $aviso['user_reacted']['confirm'] ? 'fill-current' : '' ?>"></i>
                            <span><?= $aviso['reactions']['confirm'] ?: 'Confirmar Leitura' ?></span>
                        </button>
                    </form>
                </div>

            </div>
        <?php endforeach; ?>
        </div>
    <?php endif; ?>

</main>

<!-- Floating Admin Trigger for Mobiles (Sharp geometry) -->
<?php if ($isAdmin): ?>
<div class="fixed bottom-24 right-6 z-40 sm:hidden">
    <button onclick="openCreateDrawer()" class="active:scale-90 w-14 h-14 rounded-[2px] bg-[#2E7EED] text-white flex items-center justify-center shadow-2xl hover:bg-[#1A6FD6] transition-all transform duration-150 will-change-transform border border-white/10">
        <i data-lucide="edit-3" class="w-6 h-6"></i>
    </button>
</div>
<?php endif; ?>

<!-- Bottom-Sheet Drawer Container -->
<div id="avisoModal" class="fixed inset-0 bg-[#000]/60 backdrop-blur-sm z-[100] hidden transition-opacity duration-300 opacity-0 flex items-end justify-center" onclick="closeDrawer(event)">
    
    <!-- Drawer Panel (slides from bottom - Sharp geometry) -->
    <div id="avisoDrawer" class="bg-[#18191D] border-t border-l border-r border-neutral-800 w-full max-w-md rounded-t-[4px] shadow-2xl flex flex-col max-h-[90vh] translate-y-full transition-transform duration-300 ease-out" onclick="event.stopPropagation()">
        
        <!-- Touch Drag Handle -->
        <div class="w-12 h-1 bg-neutral-800 rounded-full mx-auto my-3 shrink-0"></div>
        
        <div class="flex items-center justify-between px-6 pb-4 border-b border-neutral-850">
            <h3 class="text-base font-bold text-neutral-100" id="modalTitle">Novo Aviso</h3>
            <button onclick="closeDrawerForce()" class="w-8 h-8 flex items-center justify-center rounded-[2px] bg-neutral-900 hover:bg-neutral-850 border border-neutral-800 text-neutral-400 transition-colors">
                <i data-lucide="x" class="w-4 h-4"></i>
            </button>
        </div>
        
        <form method="POST" id="avisoForm" class="flex flex-col flex-1 overflow-hidden">
            <?= App\AuthMiddleware::csrfField() ?>
            <div class="p-6 overflow-y-auto space-y-4">
                <input type="hidden" name="action" value="create" id="formAction">
                <input type="hidden" name="id" id="avisoId">
                
                <div class="space-y-1">
                    <label class="text-[10px] font-bold text-neutral-450 uppercase tracking-widest">Título</label>
                    <input type="text" name="title" class="w-full px-4 py-3 bg-[#121316] border border-neutral-800 rounded-[2px] focus:border-[#2E7EED] outline-none transition-all text-sm text-neutral-200 font-sans" required id="avisoTitle" placeholder="Ex: Alteração no repertório de Domingo">
                </div>
                
                <div class="space-y-1">
                    <label class="text-[10px] font-bold text-neutral-450 uppercase tracking-widest">Mensagem</label>
                    <textarea name="message" class="w-full px-4 py-3 bg-[#121316] border border-neutral-800 rounded-[2px] focus:border-[#2E7EED] outline-none transition-all text-sm text-neutral-200 min-h-[120px] resize-y font-sans" required id="avisoMessage" placeholder="Escreva a sua mensagem aqui..."></textarea>
                </div>
                
                <div class="grid grid-cols-2 gap-4">
                    <div class="space-y-1">
                        <label class="text-[10px] font-bold text-neutral-450 uppercase tracking-widest">Prioridade</label>
                        <select name="priority" class="w-full px-4 py-3 bg-[#121316] border border-neutral-800 rounded-[2px] focus:border-[#2E7EED] outline-none transition-all text-sm text-neutral-300 appearance-none bg-[url('data:image/svg+xml;charset=US-ASCII,%3Csvg%20width%3D%2220%22%20height%3D%2220%22%20xmlns%3D%22http%3A%2F%2Fwww.w3.org%2F2000%2Fsvg%22%3E%3Cpath%20d%3D%22M5%208l5%205%205-5%22%20stroke%3D%22%23727785%22%20stroke-width%3D%221.5%22%20fill%3D%22none%22%20stroke-linecap%3D%22round%22%20stroke-linejoin%3D%22round%22%2F%3E%3C%2Fsvg%3E')] bg-no-repeat bg-[position:right_0.5rem_center]" id="avisoPriority">
                            <option value="normal" class="bg-[#18191D]">Normal</option>
                            <option value="important" class="bg-[#18191D]">⭐ Importante</option>
                            <option value="urgent" class="bg-[#18191D]">🚨 Urgente</option>
                        </select>
                    </div>
                    
                    <div class="space-y-1">
                        <label class="text-[10px] font-bold text-neutral-450 uppercase tracking-widest">Tipo</label>
                        <select name="type" class="w-full px-4 py-3 bg-[#121316] border border-neutral-800 rounded-[2px] focus:border-[#2E7EED] outline-none transition-all text-sm text-neutral-300 appearance-none bg-[url('data:image/svg+xml;charset=US-ASCII,%3Csvg%20width%3D%2220%22%20height%3D%2220%22%20xmlns%3D%22http%3A%2F%2Fwww.w3.org%2F2000%2Fsvg%22%3E%3Cpath%20d%3D%22M5%208l5%205%205-5%22%20stroke%3D%22%23727785%22%20stroke-width%3D%221.5%22%20fill%3D%22none%22%20stroke-linecap%3D%22round%22%20stroke-linejoin%3D%22round%22%2F%3E%3C%2Fsvg%3E')] bg-no-repeat bg-[position:right_0.5rem_center]" id="avisoType">
                            <option value="geral" class="bg-[#18191D]">Geral</option>
                            <option value="espiritual" class="bg-[#18191D]">🙏 Espiritual</option>
                            <option value="eventos" class="bg-[#18191D]">🎉 Eventos</option>
                            <option value="musica" class="bg-[#18191D]">🎵 Música</option>
                        </select>
                    </div>
                </div>
                
                <div class="space-y-1">
                    <label class="text-[10px] font-bold text-neutral-450 uppercase tracking-widest">Público-Alvo</label>
                    <select name="target_audience" class="w-full px-4 py-3 bg-[#121316] border border-neutral-800 rounded-[2px] focus:border-[#2E7EED] outline-none transition-all text-sm text-neutral-300 appearance-none bg-[url('data:image/svg+xml;charset=US-ASCII,%3Csvg%20width%3D%2220%22%20height%3D%2220%22%20xmlns%3D%22http%3A%2F%2Fwww.w3.org%2F2000%2Fsvg%22%3E%3Cpath%20d%3D%22M5%208l5%205%205-5%22%20stroke%3D%22%23727785%22%20stroke-width%3D%221.5%22%20fill%3D%22none%22%20stroke-linecap%3D%22round%22%20stroke-linejoin%3D%22round%22%2F%3E%3C%2Fsvg%3E')] bg-no-repeat bg-[position:right_0.5rem_center]" id="avisoAudience">
                        <option value="all" class="bg-[#18191D]">Todos</option>
                        <option value="team" class="bg-[#18191D]">Equipe</option>
                        <option value="admins" class="bg-[#18191D]">Administradores</option>
                    </select>
                </div>
                
                <div class="space-y-1">
                    <label class="text-[10px] font-bold text-neutral-450 uppercase tracking-widest">Expiração (Opcional)</label>
                    <input type="date" name="expires_at" class="w-full px-4 py-3 bg-[#121316] border border-neutral-800 rounded-[2px] focus:border-[#2E7EED] outline-none transition-all text-sm text-neutral-200" id="avisoExpires">
                </div>
            </div>
            
            <div class="p-6 border-t border-neutral-850 flex gap-3">
                <button type="button" class="flex-1 py-3 px-4 bg-neutral-900 hover:bg-neutral-850 border border-neutral-800 rounded-[2px] font-bold text-sm text-neutral-400 transition-colors" onclick="closeDrawerForce()">Cancelar</button>
                <button type="submit" class="flex-1 py-3 px-4 bg-[#2E7EED] hover:bg-[#1A6FD6] text-white rounded-[2px] font-bold text-sm shadow-sm transition-colors active:scale-[0.97] transform will-change-transform">Salvar</button>
            </div>
        </form>
    </div>
</div>

<script>
    // Toggle Filter Dropdown
    function toggleFilterDropdown() {
        const dropdown = document.getElementById('filterDropdown');
        const arrow = document.getElementById('filterArrow');
        dropdown.classList.toggle('hidden');
        dropdown.classList.toggle('flex');
        
        if (arrow) {
            arrow.classList.toggle('rotate-180');
        }
    }

    // Toggle Admin Menu
    function toggleAdminMenu(menuId, event) {
        if (event) event.stopPropagation();
        
        // Close others first
        document.querySelectorAll('.admin-dropdown-btn + div').forEach(m => {
            if (m.id !== menuId) { m.classList.add('hidden'); }
        });
        const menu = document.getElementById(menuId);
        if (menu) menu.classList.toggle('hidden');
    }

    // Close dropdowns when clicking outside
    document.addEventListener('click', (e) => {
        // Close filter dropdown
        if (!e.target.closest('#filterButton') && !e.target.closest('#filterDropdown')) {
            const filterDropdown = document.getElementById('filterDropdown');
            const arrow = document.getElementById('filterArrow');
            if (filterDropdown && !filterDropdown.classList.contains('hidden')) {
                filterDropdown.classList.add('hidden');
                filterDropdown.classList.remove('flex');
                if (arrow) arrow.classList.remove('rotate-180');
            }
        }
        
        // Close admin dropdowns
        if (!e.target.closest('.admin-dropdown-btn') && !e.target.closest('div[id^="admin-menu-"]')) {
            document.querySelectorAll('div[id^="admin-menu-"]').forEach(m => m.classList.add('hidden'));
        }
    });

    // Elegant bottom-sheet Drawer functions (Spring dynamic feel)
    function openCreateDrawer() {
        document.getElementById('modalTitle').textContent = 'Novo Aviso';
        document.getElementById('formAction').value = 'create';
        document.getElementById('avisoForm').reset();
        
        showDrawer();
    }

    function editAviso(aviso) {
        document.getElementById('modalTitle').textContent = 'Editar Aviso';
        document.getElementById('formAction').value = 'update';
        document.getElementById('avisoId').value = aviso.id;
        document.getElementById('avisoTitle').value = aviso.title;
        document.getElementById('avisoMessage').value = aviso.message;
        document.getElementById('avisoPriority').value = aviso.priority;
        document.getElementById('avisoType').value = aviso.type;
        document.getElementById('avisoAudience').value = aviso.target_audience;
        document.getElementById('avisoExpires').value = aviso.expires_at || '';
        
        showDrawer();
    }

    function showDrawer() {
        const modal = document.getElementById('avisoModal');
        const drawer = document.getElementById('avisoDrawer');
        
        modal.classList.remove('hidden');
        document.body.style.overflow = 'hidden';
        
        // Force Reflow
        modal.offsetHeight;
        
        modal.classList.remove('opacity-0');
        modal.classList.add('opacity-100');
        
        drawer.classList.remove('translate-y-full');
        drawer.classList.add('translate-y-0');
        
        setTimeout(() => {
            if (typeof lucide !== 'undefined') {
                lucide.createIcons();
            }
        }, 50);
    }

    function closeDrawer(event) {
        if (event && event.target !== document.getElementById('avisoModal')) return;
        closeDrawerForce();
    }

    function closeDrawerForce() {
        const modal = document.getElementById('avisoModal');
        const drawer = document.getElementById('avisoDrawer');
        
        modal.classList.remove('opacity-100');
        modal.classList.add('opacity-0');
        
        drawer.classList.remove('translate-y-0');
        drawer.classList.add('translate-y-full');
        
        // Wait for animation to finish
        setTimeout(() => {
            modal.classList.add('hidden');
            document.body.style.overflow = '';
        }, 300);
    }
    
    // Incializar ícones
    document.addEventListener('DOMContentLoaded', () => {
        if (typeof lucide !== 'undefined') {
            lucide.createIcons();
        }
    });
</script>

<?php renderAppFooter(); ?>
