<?php
// admin/devocionais.php - Redesign Premium Sacred Minimalist (Tailwind CSS, Bento Grid & GPU transitions)
require_once '../src/helpers/auth.php';
require_once '../src/config/db.php';
require_once '../src/layout/layout.php';
require_once '../src/helpers/devotional_helpers.php';

checkLogin();

$userId = $_SESSION['user_id'] ?? 1;
$isAdmin = ($_SESSION['user_role'] ?? '') === 'admin';

// --- LÓGICA DE POST (CRUD) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validação CSRF
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        http_response_code(403);
        die('Ação não autorizada. Por favor, recarregue a página e tente novamente.');
    }
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            // --- DEVOCIONAIS ---
            case 'create':
                $verseRefs = extractVerseReferences($_POST['content']);
                $verseRefsJson = !empty($verseRefs) ? json_encode($verseRefs) : NULL;
                
                $stmt = $pdo->prepare("
                    INSERT INTO devotionals (user_id, title, content, media_type, media_url, series_id, verse_references, order_in_series, created_at) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())
                ");
                $stmt->execute([
                    $userId,
                    $_POST['title'],
                    $_POST['content'],
                    $_POST['media_type'],
                    !empty($_POST['media_url']) ? $_POST['media_url'] : NULL,
                    !empty($_POST['series_id']) ? $_POST['series_id'] : NULL,
                    $verseRefsJson,
                    !empty($_POST['order_in_series']) ? $_POST['order_in_series'] : 0
                ]);
                $devotionalId = $pdo->lastInsertId();
                
                // Salvar tags
                if (!empty($_POST['tags'])) {
                    $tagStmt = $pdo->prepare("INSERT INTO devotional_tags (devotional_id, tag_id) VALUES (?, ?)");
                    foreach ($_POST['tags'] as $tagId) {
                        $tagStmt->execute([$devotionalId, $tagId]);
                    }
                }
                
                $authorName = $_SESSION['user_name'] ?? 'Alguém';
                notifyNewDevotional($pdo, $devotionalId, $_POST['title'], $authorName);
                
                header('Location: devocionais.php?tab=word&success=created');
                exit;

            case 'update':
                $stmt = $pdo->prepare("UPDATE devotionals SET title = ?, content = ?, media_type = ?, media_url = ? WHERE id = ? AND user_id = ?");
                $stmt->execute([
                    $_POST['title'],
                    $_POST['content'],
                    $_POST['media_type'],
                    !empty($_POST['media_url']) ? $_POST['media_url'] : NULL,
                    $_POST['id'],
                    $userId
                ]);
                
                // Atualizar tags
                $pdo->prepare("DELETE FROM devotional_tags WHERE devotional_id = ?")->execute([$_POST['id']]);
                if (!empty($_POST['tags'])) {
                    $tagStmt = $pdo->prepare("INSERT INTO devotional_tags (devotional_id, tag_id) VALUES (?, ?)");
                    foreach ($_POST['tags'] as $tagId) {
                        $tagStmt->execute([$_POST['id'], $tagId]);
                    }
                }
                
                header('Location: devocionais.php?tab=word&success=updated');
                exit;

            case 'delete':
                $stmt = $pdo->prepare("DELETE FROM devotionals WHERE id = ? AND (user_id = ? OR ? = 'admin')");
                $stmt->execute([$_POST['id'], $userId, $_SESSION['user_role']]);
                header('Location: devocionais.php?tab=word&success=deleted');
                exit;

            case 'comment':
                $stmt = $pdo->prepare("INSERT INTO devotional_comments (devotional_id, user_id, comment, created_at) VALUES (?, ?, ?, NOW())");
                $stmt->execute([
                    $_POST['devotional_id'],
                    $userId,
                    $_POST['comment']
                ]);
                header('Location: devocionais.php?tab=word&success=commented#dev-' . $_POST['devotional_id']);
                exit;
                
            case 'delete_comment':
                $stmt = $pdo->prepare("DELETE FROM devotional_comments WHERE id = ? AND (user_id = ? OR ? = 'admin')");
                $stmt->execute([$_POST['comment_id'], $userId, $_SESSION['user_role']]);
                header('Location: devocionais.php?tab=word&success=comment_deleted');
                exit;

            // --- ORAÇÃO ---
            case 'create_prayer':
                try {
                    $pdo->query("SELECT 1 FROM prayer_requests LIMIT 1");
                    $stmt = $pdo->prepare("INSERT INTO prayer_requests (user_id, title, description, category, is_urgent, is_anonymous, created_at) VALUES (?, ?, ?, ?, ?, ?, NOW())");
                    $stmt->execute([
                        $userId,
                        $_POST['title'],
                        $_POST['description'],
                        $_POST['category'],
                        isset($_POST['is_urgent']) ? 1 : 0,
                        isset($_POST['is_anonymous']) ? 1 : 0
                    ]);
                    header('Location: devocionais.php?tab=prayer&success=created');
                    exit;
                } catch (Exception $e) {
                    // Ignore
                }
                break;

            case 'pray':
                $check = $pdo->prepare("SELECT id FROM prayer_interactions WHERE prayer_id = ? AND user_id = ? AND type = 'pray'");
                $check->execute([$_POST['prayer_id'], $userId]);
                if (!$check->fetch()) {
                    $stmt = $pdo->prepare("INSERT INTO prayer_interactions (prayer_id, user_id, type, created_at) VALUES (?, ?, 'pray', NOW())");
                    $stmt->execute([$_POST['prayer_id'], $userId]);
                    $pdo->prepare("UPDATE prayer_requests SET prayer_count = prayer_count + 1 WHERE id = ?")->execute([$_POST['prayer_id']]);
                }
                header('Location: devocionais.php?tab=prayer#prayer-' . $_POST['prayer_id']);
                exit;

            case 'comment_prayer':
                $stmt = $pdo->prepare("INSERT INTO prayer_interactions (prayer_id, user_id, type, comment, created_at) VALUES (?, ?, 'comment', ?, NOW())");
                $stmt->execute([$_POST['prayer_id'], $userId, $_POST['comment']]);
                header('Location: devocionais.php?tab=prayer#prayer-' . $_POST['prayer_id']);
                exit;

            case 'answer_prayer':
                $stmt = $pdo->prepare("UPDATE prayer_requests SET is_answered = 1, answered_at = NOW() WHERE id = ? AND user_id = ?");
                $stmt->execute([$_POST['prayer_id'], $userId]);
                header('Location: devocionais.php?tab=prayer&success=answered');
                exit;
                
            case 'delete_prayer':
                $stmt = $pdo->prepare("DELETE FROM prayer_requests WHERE id = ? AND user_id = ?");
                $stmt->execute([$_POST['prayer_id'], $userId]);
                header('Location: devocionais.php?tab=prayer&success=deleted');
                exit;
        }
    }
}

// --- FILTROS ---
$filterTag = $_GET['tag'] ?? '';
$filterType = $_GET['type'] ?? 'all';
$search = $_GET['search'] ?? '';
$filterAuthor = $_GET['author'] ?? '';
$filterDateFrom = $_GET['date_from'] ?? '';
$filterDateTo = $_GET['date_to'] ?? '';
$filterVerse = $_GET['verse'] ?? '';
$filterSeries = $_GET['series'] ?? '';
$filterRead = $_GET['read_status'] ?? 'all';

// Buscar devocionais
$sql = "SELECT d.*, u.name as author_name, u.avatar as author_avatar,
        s.title as series_title, s.cover_color as series_color,
        (SELECT COUNT(*) FROM devotional_comments WHERE devotional_id = d.id) as comment_count,
        IF(dr.id IS NOT NULL, 1, 0) as is_read
        FROM devotionals d 
        LEFT JOIN users u ON d.user_id = u.id 
        LEFT JOIN devotional_series s ON d.series_id = s.id
        LEFT JOIN devotional_reads dr ON d.id = dr.devotional_id AND dr.user_id = ?
        WHERE 1=1";
$params = [$userId];

if (!empty($filterTag)) {
    $sql .= " AND d.id IN (SELECT devotional_id FROM devotional_tags WHERE tag_id = ?)";
    $params[] = $filterTag;
}

if ($filterType !== 'all') {
    $sql .= " AND d.media_type = ?";
    $params[] = $filterType;
}

if (!empty($search)) {
    $sql .= " AND (d.title LIKE ? OR d.content LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

if (!empty($filterAuthor)) {
    $sql .= " AND d.user_id = ?";
    $params[] = $filterAuthor;
}

if (!empty($filterDateFrom)) {
    $sql .= " AND DATE(d.created_at) >= ?";
    $params[] = $filterDateFrom;
}

if (!empty($filterDateTo)) {
    $sql .= " AND DATE(d.created_at) <= ?";
    $params[] = $filterDateTo;
}

if (!empty($filterVerse)) {
    $sql .= " AND (d.verse_references LIKE ? OR d.content LIKE ?)";
    $params[] = "%$filterVerse%";
    $params[] = "%[verso%$filterVerse%]%";
}

if ($filterRead === 'read') {
    $sql .= " AND dr.id IS NOT NULL";
} elseif ($filterRead === 'unread') {
    $sql .= " AND dr.id IS NULL";
}

$sql .= " ORDER BY is_read ASC, d.created_at DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$devotionals = $stmt->fetchAll(PDO::FETCH_ASSOC);

// --- BUSCAR ORAÇÕES ---
$prayers = [];
$prayerTableExists = true;
try {
    $pdo->query("SELECT 1 FROM prayer_requests LIMIT 1");
    
    $pSql = "SELECT p.*, u.name as author_name, u.avatar as author_avatar,
            (SELECT COUNT(*) FROM prayer_interactions WHERE prayer_id = p.id AND type = 'pray') as pray_count,
            (SELECT COUNT(*) FROM prayer_interactions WHERE prayer_id = p.id AND type = 'comment') as comment_count,
            IF(pi_user.id IS NOT NULL, 1, 0) as is_interceded
            FROM prayer_requests p 
            LEFT JOIN users u ON p.user_id = u.id
            LEFT JOIN prayer_interactions pi_user ON p.id = pi_user.prayer_id 
                                                   AND pi_user.user_id = ?
                                                   AND pi_user.type = 'pray'
            WHERE p.is_answered = 0
            ORDER BY is_interceded ASC, p.is_urgent DESC, p.created_at DESC";
    $pStmt = $pdo->prepare($pSql);
    $pStmt->execute([$userId]);
    $prayers = $pStmt->fetchAll(PDO::FETCH_ASSOC);

} catch (Exception $e) {
    $prayerTableExists = false;
}

// Buscar tags existentes
$tagsStmt = $pdo->query("SELECT * FROM tags ORDER BY name");
$allTags = $tagsStmt->fetchAll(PDO::FETCH_ASSOC);

// Buscar séries disponíveis
try {
    $seriesStmt = $pdo->query("SELECT * FROM series_with_stats ORDER BY created_at DESC");
    $allSeries = $seriesStmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $allSeries = [];
}

// Buscar autores que já publicaram devocionais
$authorsStmt = $pdo->query("
    SELECT DISTINCT u.id, u.name 
    FROM users u
    INNER JOIN devotionals d ON u.id = d.user_id
    ORDER BY u.name ASC
");
$allAuthors = $authorsStmt->fetchAll(PDO::FETCH_ASSOC);

// Buscar tags de cada devocional
function getDevotionalTags($pdo, $devotionalId) {
    $stmt = $pdo->prepare("SELECT t.* FROM tags t 
                           INNER JOIN devotional_tags dt ON t.id = dt.tag_id 
                           WHERE dt.devotional_id = ?");
    $stmt->execute([$devotionalId]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Buscar comentários de cada devocional
function getDevotionalComments($pdo, $devotionalId) {
    $stmt = $pdo->prepare("SELECT c.*, u.name as author_name, u.avatar as author_avatar 
                           FROM devotional_comments c 
                           LEFT JOIN users u ON c.user_id = u.id 
                           WHERE c.devotional_id = ? 
                           ORDER BY c.created_at ASC");
    $stmt->execute([$devotionalId]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Buscar comentários de oração
function getPrayerComments($pdo, $prayerId) {
    $stmt = $pdo->prepare("SELECT pi.*, u.name, u.avatar FROM prayer_interactions pi 
                           LEFT JOIN users u ON pi.user_id = u.id 
                           WHERE pi.prayer_id = ? AND pi.type = 'comment' 
                           ORDER BY pi.created_at ASC");
    $stmt->execute([$prayerId]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

renderAppHeader('Espiritualidade', 'index.php');
?>

<!-- Quill CSS -->
<link href="https://cdn.quilljs.com/1.3.6/quill.snow.css" rel="stylesheet">

<!-- Estilos Customizados Sacred Minimalist (Transições GPU & Dark Mode Quill) -->
<style>
    /* Animações e Efeitos Sacred Minimalist */
    @keyframes revealStagger {
        0% {
            opacity: 0;
            transform: translateY(12px) scale(0.98);
        }
        100% {
            opacity: 1;
            transform: translateY(0) scale(1);
        }
    }

    .reveal-item {
        opacity: 0;
        animation: revealStagger 0.4s cubic-bezier(0.16, 1, 0.3, 1) forwards;
        will-change: opacity, transform;
    }

    /* Delays dinâmicos em stagger */
    .reveal-item:nth-child(1) { animation-delay: 0.03s; }
    .reveal-item:nth-child(2) { animation-delay: 0.06s; }
    .reveal-item:nth-child(3) { animation-delay: 0.09s; }
    .reveal-item:nth-child(4) { animation-delay: 0.12s; }
    .reveal-item:nth-child(5) { animation-delay: 0.15s; }
    .reveal-item:nth-child(6) { animation-delay: 0.18s; }

    /* Transições GPU de Abas */
    .tab-content {
        transition: opacity 0.25s cubic-bezier(0.4, 0, 0.2, 1), transform 0.25s cubic-bezier(0.4, 0, 0.2, 1);
        will-change: opacity, transform;
    }

    .tab-content.hidden {
        display: none;
        opacity: 0;
        transform: translateY(8px);
    }

    .tab-content.block {
        display: block;
        opacity: 1;
        transform: translateY(0);
    }

    /* Customização do Quill Editor para Dark Mode */
    .dark .ql-toolbar.ql-snow {
        background-color: #2C2C2E !important;
        border-color: #3A3A3C !important;
        border-top-left-radius: 1rem;
        border-top-right-radius: 1rem;
    }

    .dark .ql-container.ql-snow {
        background-color: #1A1B1F !important;
        border-color: #3A3A3C !important;
        border-bottom-left-radius: 1rem;
        border-bottom-right-radius: 1rem;
    }

    .dark .ql-editor {
        color: #F4F4F5 !important;
    }

    .dark .ql-editor.ql-blank::before {
        color: #8E8E93 !important;
    }

    .dark .ql-snow .ql-stroke {
        stroke: #E5E5EA !important;
    }

    .dark .ql-snow .ql-fill {
        fill: #E5E5EA !important;
    }

    .dark .ql-snow .ql-picker {
        color: #E5E5EA !important;
    }

    .dark .ql-snow .ql-picker-options {
        background-color: #2C2C2E !important;
        border-color: #3A3A3C !important;
    }

    /* Micro-movimento de toque */
    .interactive-scale {
        transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
    }
    .interactive-scale:active {
        transform: scale(0.97);
    }
</style>

<main class="max-w-4xl mx-auto px-4 sm:px-6 py-8 mb-32 space-y-8 animate-fade-in" id="espiritualidade-container">
    
    <!-- Hero / Header Section Bento Premium -->
    <div class="relative overflow-hidden rounded-3xl bg-white dark:bg-[#1A1B1F] text-gray-800 dark:text-white p-8 shadow-sm dark:shadow-xl border border-gray-100 dark:border-white/5 reveal-item">
        <div class="absolute -right-16 -top-16 w-64 h-64 bg-[#2E7EED]/10 rounded-full blur-3xl pointer-events-none"></div>
        <div class="absolute -left-16 -bottom-16 w-64 h-64 bg-[#FFC107]/5 rounded-full blur-3xl pointer-events-none"></div>
        
        <div class="relative z-10 flex flex-col md:flex-row md:items-center justify-between gap-6">
            <div>
                <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-[#2E7EED]/10 dark:bg-[#2E7EED]/20 border border-[#2E7EED]/20 dark:border-[#2E7EED]/30 text-[#2E7EED] text-xs font-bold uppercase tracking-wider mb-3">
                    📖 Espiritualidade e Devocionais
                </span>
                <h1 class="text-3xl md:text-4xl font-extrabold tracking-tight font-sans">Vida e Oração em Comunidade</h1>
                <p class="text-gray-500 dark:text-gray-400 mt-2 max-w-xl text-sm font-body">Mantenha a fé ativa compartilhando reflexões da palavra de Deus e intercedendo uns pelos outros.</p>
            </div>
        </div>
    </div>

    <!-- TABS Flutuantes Bento Premium -->
    <div class="flex items-center gap-1 p-1 bg-gray-100 dark:bg-[#121316] rounded-2xl w-fit shadow-sm border border-transparent dark:border-white/5 reveal-item">
        <button class="px-5 py-2.5 rounded-xl text-sm font-extrabold transition-all duration-200 active:scale-95 cursor-pointer tab-btn active" onclick="switchTab('word')" id="btn-tab-word">📖 Palavra</button>
        <button class="px-5 py-2.5 rounded-xl text-sm font-extrabold transition-all duration-200 active:scale-95 cursor-pointer tab-btn text-gray-500 dark:text-gray-400" onclick="switchTab('prayer')" id="btn-tab-prayer">🙏 Oração</button>
    </div>

    <!-- CONTEÚDO: PALAVRA (DEVOCIONAIS) -->
    <div id="tab-word" class="tab-content block space-y-6">
        <!-- Busca e Filtros Bento -->
        <div class="bg-white dark:bg-[#1A1B1F] p-6 rounded-3xl shadow-sm border border-gray-100 dark:border-[#2C2C2E] space-y-4 reveal-item">
            <div class="relative w-full">
                <i data-lucide="search" class="absolute left-4 top-1/2 -translate-y-1/2 text-gray-400 w-5 h-5"></i>
                <input 
                    type="text" 
                    id="smartSearch" 
                    placeholder="Buscar devocional por título ou conteúdo... (Ctrl+K)" 
                    value="<?= htmlspecialchars($search) ?>"
                    class="w-full h-12 pl-12 pr-4 bg-gray-50 dark:bg-[#2C2C2E] border border-gray-100 dark:border-[#3A3A3C] rounded-2xl text-sm focus:outline-none focus:border-[#2E7EED] dark:focus:border-[#2E7EED] focus:ring-1 focus:ring-[#2E7EED] transition-all placeholder-gray-400 text-gray-800 dark:text-white font-medium"
                    oninput="handleSmartSearch(this.value)"
                >
            </div>

            <!-- Filtros Rápidos (Pills e Selects) -->
            <div class="flex flex-wrap items-center gap-3 pt-1">
                <span class="text-xs font-bold text-gray-400 dark:text-gray-500 uppercase tracking-wider pl-1">Filtrar:</span>
                
                <div class="flex gap-1.5 p-0.5 bg-gray-50 dark:bg-[#2C2C2E] rounded-xl border border-gray-100 dark:border-[#3A3A3C]">
                    <a href="?read_status=all&author=<?= $filterAuthor ?>&tag=<?= $filterTag ?>&search=<?= urlencode($search) ?>&tab=word" 
                       class="px-3.5 py-1.5 rounded-lg text-xs font-bold transition-all <?= $filterRead === 'all' ? 'bg-[#2E7EED]/10 text-[#2E7EED]' : 'text-gray-500 dark:text-gray-400 hover:text-gray-800 dark:hover:text-white' ?>">
                        Todas
                    </a>
                    <a href="?read_status=unread&author=<?= $filterAuthor ?>&tag=<?= $filterTag ?>&search=<?= urlencode($search) ?>&tab=word" 
                       class="px-3.5 py-1.5 rounded-lg text-xs font-bold transition-all <?= $filterRead === 'unread' ? 'bg-[#2E7EED]/10 text-[#2E7EED]' : 'text-gray-500 dark:text-gray-400 hover:text-gray-800 dark:hover:text-white' ?>">
                        Não Lidas
                    </a>
                    <a href="?read_status=read&author=<?= $filterAuthor ?>&tag=<?= $filterTag ?>&search=<?= urlencode($search) ?>&tab=word" 
                       class="px-3.5 py-1.5 rounded-lg text-xs font-bold transition-all <?= $filterRead === 'read' ? 'bg-[#2E7EED]/10 text-[#2E7EED]' : 'text-gray-500 dark:text-gray-400 hover:text-gray-800 dark:hover:text-white' ?>">
                        Lidas
                    </a>
                </div>

                <span class="w-[1px] h-5 bg-gray-200 dark:bg-[#3A3A3C]"></span>

                <!-- Autor Select -->
                <select onchange="window.location.href='?author='+this.value+'&read_status=<?= $filterRead ?>&tag=<?= $filterTag ?>&search=<?= urlencode($search) ?>&tab=word'" 
                        class="h-8 pl-3 pr-8 bg-gray-50 dark:bg-[#2C2C2E] border border-gray-100 dark:border-[#3A3A3C] rounded-full text-xs font-bold text-gray-600 dark:text-gray-300 focus:outline-none focus:border-[#2E7EED] cursor-pointer appearance-none bg-[url('data:image/svg+xml,%3Csvg xmlns=%27http://www.w3.org/2000/svg%27 width=%2712%27 height=%2712%27 viewBox=%270 0 24 24%27 fill=%27none%27 stroke=%27%2364748b%27 stroke-width=%272%27%3E%3Cpolyline points=%276 9 12 15 18 9%27%3E%3C/polyline%3E%3C/svg%3E')] bg-no-repeat bg-[position:right_10px_center]">
                    <option value="">👤 Todos Autores</option>
                    <?php foreach ($allAuthors as $author): ?>
                        <option value="<?= $author['id'] ?>" <?= $filterAuthor == $author['id'] ? 'selected' : '' ?>><?= htmlspecialchars($author['name']) ?></option>
                    <?php endforeach; ?>
                </select>

                <!-- Tag Select -->
                <select onchange="window.location.href='?tag='+this.value+'&read_status=<?= $filterRead ?>&author=<?= $filterAuthor ?>&search=<?= urlencode($search) ?>&tab=word'" 
                        class="h-8 pl-3 pr-8 bg-gray-50 dark:bg-[#2C2C2E] border border-gray-100 dark:border-[#3A3A3C] rounded-full text-xs font-bold text-gray-600 dark:text-gray-300 focus:outline-none focus:border-[#2E7EED] cursor-pointer appearance-none bg-[url('data:image/svg+xml,%3Csvg xmlns=%27http://www.w3.org/2000/svg%27 width=%2712%27 height=%2712%27 viewBox=%270 0 24 24%27 fill=%27none%27 stroke=%27%2364748b%27 stroke-width=%272%27%3E%3Cpolyline points=%276 9 12 15 18 9%27%3E%3C/polyline%3E%3C/svg%3E')] bg-no-repeat bg-[position:right_10px_center]">
                    <option value="">🏷️ Todas Tags</option>
                    <?php foreach ($allTags as $tag): ?>
                        <option value="<?= $tag['id'] ?>" <?= $filterTag == $tag['id'] ? 'selected' : '' ?>>#<?= htmlspecialchars($tag['name']) ?></option>
                    <?php endforeach; ?>
                </select>

                <!-- Limpar Filtros -->
                <?php if (!empty($search) || $filterRead !== 'all' || !empty($filterAuthor) || !empty($filterTag)): ?>
                <a href="?tab=word" class="h-8 px-3.5 rounded-full text-xs font-bold bg-red-50 dark:bg-red-950/20 hover:bg-red-100 dark:hover:bg-red-900/30 text-red-500 dark:text-red-400 flex items-center gap-1 transition-colors">
                    <i data-lucide="x" class="w-3.5 h-3.5"></i> Limpar
                </a>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Feed de Devocionais -->
        <div class="space-y-4">
            <?php if (count($devotionals) > 0): ?>
                <?php foreach ($devotionals as $dev): 
                    $tags = getDevotionalTags($pdo, $dev['id']);
                    $comments = getDevotionalComments($pdo, $dev['id']);
                    $authorName = $dev['author_name'] ?? 'Membro';
                    $authorAvatar = $dev['author_avatar'] ?? null;
                    if ($authorAvatar && strpos($authorAvatar, 'http') === false && strpos($authorAvatar, 'assets') === false) {
                        $authorAvatar = '../uploads/' .                 <div class="devotional-card bg-white dark:bg-[#1A1B1F] rounded-3xl border border-gray-100 dark:border-[#2C2C2E] shadow-sm p-6 space-y-4 hover:shadow-md dark:hover:shadow-black/20 hover:scale-[1.005] active:scale-[0.998] transition-all duration-300 reveal-item <?= $dev['is_read'] ? 'opacity-85' : 'border-l-4 border-l-[#2E7EED]' ?>" id="dev-<?= $dev['id'] ?>">
                    <!-- Header -->
                    <div class="flex items-center justify-between gap-4">
                        <div class="flex items-center gap-3">
                            <?php if ($authorAvatar): ?>
                                <img src="<?= htmlspecialchars($authorAvatar) ?>" class="w-10 h-10 rounded-full object-cover border border-gray-100 dark:border-white/5 shadow-sm shrink-0" alt="">
                            <?php else: ?>
                                <div class="w-10 h-10 rounded-full bg-[#2E7EED]/10 text-[#2E7EED] font-extrabold flex items-center justify-center text-sm border border-transparent shadow-sm shrink-0"><?= strtoupper(substr($authorName, 0, 1)) ?></div>
                            <?php endif; ?>
                            
                            <div class="flex flex-col">
                                <span class="text-sm font-bold text-gray-800 dark:text-gray-100 flex items-center flex-wrap gap-1.5 leading-tight">
                                    <?= htmlspecialchars($authorName) ?>
                                    <?php if (!empty($dev['series_title'])): ?>
                                        <span class="text-xs font-bold text-gray-400 dark:text-gray-500">em</span>
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-extrabold" style="background: <?= $dev['series_color'] ?: '#2E7EED' ?>12; color: <?= $dev['series_color'] ?: '#2E7EED' ?>;">
                                            📚 <?= htmlspecialchars($dev['series_title']) ?>
                                        </span>
                                    <?php endif; ?>
                                </span>
                                <div class="flex items-center gap-1.5 text-[11px] text-gray-400 dark:text-gray-500 font-bold uppercase tracking-wider mt-0.5">
                                    <span><?= date('d/m \à\s H:i', strtotime($dev['created_at'])) ?></span>
                                    <?php if (!$dev['is_read']): ?>
                                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
                                        <span class="text-emerald-500">Novo</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        
                        <?php if ($dev['user_id'] == $userId || $isAdmin): ?>
                        <div class="shrink-0">
                             <form method="POST" onsubmit="return confirm('Excluir este devocional permanentemente?');" class="m-0">
                                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="id" value="<?= $dev['id'] ?>">
                                <button type="submit" class="w-8 h-8 rounded-full bg-gray-50 dark:bg-[#2C2C2E] hover:bg-red-50 dark:hover:bg-red-950/30 text-gray-400 hover:text-red-500 flex items-center justify-center transition-colors cursor-pointer">
                                    <i data-lucide="trash-2" class="w-4 h-4"></i>
                                </button>
                             </form>
                        </div>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Content -->
                    <div class="space-y-3">
                        <h2 class="text-xl font-extrabold text-gray-850 dark:text-white leading-snug"><?= htmlspecialchars($dev['title']) ?></h2>
                        <div class="text-gray-600 dark:text-gray-300 text-sm leading-relaxed font-body devotional-text-box">
                            <?= $dev['content'] ?>
                        </div>
                        
                        <!-- Media Embed -->
                        <?php if ($dev['media_type'] === 'video' && !empty($dev['media_url'])): 
                            $embedUrl = str_replace('watch?v=', 'embed/', $dev['media_url']);
                        ?>
                        <div class="aspect-video w-full rounded-2xl overflow-hidden shadow-sm border border-gray-100 dark:border-[#2C2C2E] bg-gray-50 dark:bg-[#2C2C2E]">
                            <iframe src="<?= htmlspecialchars($embedUrl) ?>" class="w-full h-full border-0" allowfullscreen></iframe>
                        </div>
                        <?php elseif ($dev['media_type'] === 'link' && !empty($dev['media_url'])): ?>
                        <div class="pt-2">
                            <a href="<?= htmlspecialchars($dev['media_url']) ?>" target="_blank" class="flex items-center gap-3 p-4 bg-gray-50 dark:bg-[#2C2C2E] hover:bg-[#2E7EED]/5 dark:hover:bg-[#2E7EED]/10 border border-gray-100 dark:border-[#3A3A3C] hover:border-[#2E7EED]/20 rounded-2xl text-slate-800 dark:text-gray-200 transition-all duration-200 cursor-pointer">
                                <div class="w-10 h-10 rounded-xl bg-white dark:bg-[#1A1B1F] text-[#2E7EED] border border-gray-150 dark:border-[#3A3A3C] flex items-center justify-center shrink-0">
                                    <i data-lucide="external-link" class="w-5 h-5"></i>
                                </div>
                                <div class="flex-1 min-w-0">
                                    <div class="text-xs font-bold text-gray-400 dark:text-gray-550 uppercase tracking-wider mb-0.5">Link Adicional</div>
                                    <div class="text-xs font-bold text-gray-700 dark:text-gray-300 truncate font-body"><?= htmlspecialchars($dev['media_url']) ?></div>
                                </div>
                            </a>
                        </div>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Tags -->
                    <?php if (!empty($tags)): ?>
                    <div class="flex flex-wrap gap-1.5 pt-2">
                        <?php foreach ($tags as $tag): ?>
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full bg-slate-50 dark:bg-[#2C2C2E] border border-slate-100 dark:border-[#3A3A3C] text-slate-500 dark:text-gray-400 text-xs font-bold">
                                #<?= htmlspecialchars($tag['name']) ?>
                            </span>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                    
                    <!-- Footer Actions -->
                    <div class="flex items-center gap-3 pt-3 border-t border-gray-50 dark:border-[#2C2C2E]">
                        <button class="inline-flex items-center gap-1.5 text-xs font-bold text-gray-500 dark:text-gray-400 hover:text-[#2E7EED] bg-gray-50 dark:bg-[#2C2C2E] px-3.5 py-2 rounded-xl transition-colors cursor-pointer" onclick="toggleComments('comments-<?= $dev['id'] ?>', event)">
                            <i data-lucide="message-square" class="w-4 h-4"></i>
                            <span>Comentários (<?= count($comments) ?>)</span>
                        </button>
                        <button class="inline-flex items-center gap-1.5 text-xs font-bold text-gray-500 dark:text-gray-400 hover:text-emerald-500 bg-gray-50 dark:bg-[#2C2C2E] px-3.5 py-2 rounded-xl transition-colors cursor-pointer" onclick="shareDevotional(<?= $dev['id'] ?>, '<?= addslashes($dev['title']) ?>', event)">
                            <i data-lucide="share-2" class="w-4 h-4"></i>
                            <span>Compartilhar</span>
                        </button>
                    </div>
                    
                    <!-- Comments Section -->
                    <div id="comments-<?= $dev['id'] ?>" class="hidden pt-4 border-t border-gray-50 dark:border-[#2C2C2E] space-y-3">
                        <h4 class="text-xs font-bold uppercase tracking-wider text-gray-400 dark:text-gray-500 pl-1 mb-2">Comentários</h4>
                        
                        <div class="space-y-3 max-h-[300px] overflow-y-auto pr-1">
                            <?php foreach ($comments as $comment): 
                                 $commentAvatar = $comment['author_avatar'];
                                 if ($commentAvatar && strpos($commentAvatar, 'http') === false) {
                                    $commentAvatar = '../uploads/' . $commentAvatar;
                                }
                            ?>
                            <div class="flex gap-3 bg-gray-50/50 dark:bg-[#2C2C2E]/40 p-3 rounded-2xl border border-gray-100 dark:border-[#3A3A3C]">
                                <?php if ($commentAvatar): ?>
                                    <img src="<?= htmlspecialchars($commentAvatar) ?>" class="w-8 h-8 rounded-full object-cover shrink-0 border border-gray-150 dark:border-white/5 shadow-sm" alt="">
                                <?php else: ?>
                                    <div class="w-8 h-8 rounded-full bg-slate-200 dark:bg-[#2C2C2E] text-slate-500 dark:text-gray-400 font-extrabold flex items-center justify-center text-xs shrink-0"><?= strtoupper(substr($comment['author_name'], 0, 1)) ?></div>
                                <?php endif; ?>
                                
                                <div class="flex-1 min-w-0">
                                    <div class="flex items-center justify-between gap-2">
                                        <span class="text-xs font-bold text-gray-700 dark:text-gray-250"><?= htmlspecialchars($comment['author_name']) ?></span>
                                        <span class="text-[10px] text-gray-400 dark:text-gray-500 font-semibold"><?= date('d/m H:i', strtotime($comment['created_at'])) ?></span>
                                    </div>
                                    <p class="text-xs text-gray-600 dark:text-gray-300 font-body leading-relaxed mt-1"><?= nl2br(htmlspecialchars($comment['comment'])) ?></p>
                                </div>
                                
                                <?php if ($comment['user_id'] == $userId || $isAdmin): ?>
                                <div class="shrink-0">
                                    <form method="POST" onsubmit="return confirm('Excluir este comentário?');" class="m-0">
                                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
                                        <input type="hidden" name="action" value="delete_comment">
                                        <input type="hidden" name="comment_id" value="<?= $comment['id'] ?>">
                                        <button type="submit" class="w-6 h-6 rounded-full bg-white dark:bg-[#2C2C2E] hover:bg-red-50 dark:hover:bg-red-950/30 text-gray-400 hover:text-red-500 flex items-center justify-center border border-gray-150 dark:border-[#3A3A3C] transition-colors cursor-pointer">
                                            <i data-lucide="x" class="w-3 h-3"></i>
                                        </button>
                                    </form>
                                </div>
                                <?php endif; ?>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        
                        <!-- Add Comment Form -->
                        <form method="POST" class="flex gap-2 pt-2">
                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
                            <input type="hidden" name="action" value="comment">
                            <input type="hidden" name="devotional_id" value="<?= $dev['id'] ?>">
                            <input type="text" name="comment" class="flex-1 h-10 px-4 bg-gray-50 dark:bg-[#2C2C2E] border border-gray-150 dark:border-[#3A3A3C] rounded-xl text-xs focus:outline-none focus:border-[#2E7EED] focus:ring-1 focus:ring-[#2E7EED] transition-all placeholder-gray-400 text-gray-800 dark:text-white font-medium" placeholder="Escreva uma mensagem..." required>
                            <button type="submit" class="w-10 h-10 rounded-xl bg-[#2E7EED] hover:bg-[#1A6FD6] text-white flex items-center justify-center shrink-0 shadow-sm active:scale-95 transition-all cursor-pointer">
                                <i data-lucide="send" class="w-4 h-4"></i>
                            </button>
                        </form>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="bg-gray-50 dark:bg-[#1A1B1F] border border-dashed border-gray-200 dark:border-[#2C2C2E] rounded-3xl p-12 text-center reveal-item">
                    <div class="w-16 h-16 rounded-full bg-[#2E7EED]/10 text-[#2E7EED] flex items-center justify-center mx-auto mb-4 border border-transparent">
                        <i data-lucide="book-open" class="w-8 h-8"></i>
                    </div>
                    <h3 class="text-lg font-bold text-gray-800 dark:text-white mb-1">Nenhum devocional ainda</h3>
                    <p class="text-sm text-gray-500 dark:text-gray-400 max-w-sm mx-auto mb-6">Compartilhe uma reflexão espiritual da palavra de Deus com toda a equipe do louvor!</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- CONTEÚDO: ORAÇÃO -->
    <div id="tab-prayer" class="tab-content hidden space-y-6">
        <!-- Feed de Pedidos de Oração (Mural Contemplativo) -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <?php if ($prayerTableExists && count($prayers) > 0): ?>
                <?php foreach ($prayers as $prayer): 
                    $authorAvatar = $prayer['author_avatar'];
                    if ($authorAvatar && strpos($authorAvatar, 'http') === false && strpos($authorAvatar, 'assets') === false) {
                        $authorAvatar = '../uploads/' . $authorAvatar;
                    }
                ?>
                <div class="prayer-card bg-white dark:bg-[#1A1B1F] p-6 rounded-3xl border border-gray-100 dark:border-[#2C2C2E] shadow-sm flex flex-col justify-between hover:shadow-md dark:hover:shadow-black/20 hover:scale-[1.005] active:scale-[0.998] transition-all duration-300 reveal-item <?= $prayer['is_urgent'] ? 'border-l-4 border-l-[#FFC107]' : '' ?>" id="prayer-<?= $prayer['id'] ?>">
                    <div class="space-y-4 flex-1">
                        <!-- Top Info -->
                        <div class="flex items-start justify-between gap-4">
                            <div class="flex items-center gap-3">
                                <?php if ($authorAvatar): ?>
                                    <img src="<?= $authorAvatar ?>" class="w-9 h-9 rounded-full object-cover border border-gray-100 dark:border-white/5 shadow-sm shrink-0">
                                <?php else: ?>
                                    <div class="w-9 h-9 rounded-full bg-[#FFC107]/10 text-[#D97706] font-extrabold flex items-center justify-center text-xs shrink-0">
                                        <?= strtoupper(substr($prayer['author_name'] ?? 'A', 0, 1)) ?>
                                    </div>
                                <?php endif; ?>
                                <div class="flex flex-col">
                                    <h4 class="text-xs font-bold text-gray-800 dark:text-gray-100 leading-tight">
                                        <?= $prayer['is_anonymous'] ? '🔒 Anônimo' : htmlspecialchars($prayer['author_name'] ?? 'Anônimo') ?>
                                    </h4>
                                    <span class="text-[9px] text-gray-400 dark:text-gray-500 font-bold uppercase tracking-wider mt-0.5"><?= date('d/m \à\s H:i', strtotime($prayer['created_at'])) ?></span>
                                </div>
                            </div>
                            
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[10px] font-extrabold bg-[#2E7EED]/10 dark:bg-[#2E7EED]/20 text-[#2E7EED] uppercase tracking-wider">
                                <?php
                                    $cats = [
                                        'health' => 'Saúde', 'family' => 'Família', 
                                        'work' => 'Trabalho', 'spiritual' => 'Espiritual',
                                        'gratitude' => 'Gratidão', 'other' => 'Outros'
                                    ];
                                    echo $cats[$prayer['category']] ?? 'Outros';
                                ?>
                            </span>
                        </div>
                        
                        <!-- Content -->
                        <div class="space-y-2">
                            <h3 class="text-base font-extrabold text-gray-850 dark:text-white flex items-center gap-1.5 leading-tight">
                                <?php if($prayer['is_urgent']): ?>
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full bg-[#FFC107]/20 border border-[#FFC107]/30 text-[#D97706] text-[9px] font-black uppercase tracking-widest shrink-0 animate-pulse">🔥 Urgente</span>
                                <?php endif; ?>
                                <?= htmlspecialchars($prayer['title']) ?>
                            </h3>
                            <?php if (!empty($prayer['description'])): ?>
                                <p class="text-xs text-gray-500 dark:text-gray-300 font-body leading-relaxed whitespace-pre-line"><?= htmlspecialchars($prayer['description']) ?></p>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <!-- Footer / Intercessão -->
                    <div class="flex items-center justify-between gap-3 pt-4 mt-4 border-t border-gray-50 dark:border-[#2C2C2E] shrink-0">
                        <button onclick="toggleIntercessionStatus(<?= $prayer['id'] ?>, this); event.stopPropagation();" class="inline-flex items-center gap-2 text-xs font-bold text-gray-500 dark:text-gray-400 hover:text-red-500 bg-gray-50 dark:bg-[#2C2C2E] hover:bg-red-50 dark:hover:bg-red-950/20 border border-gray-150 dark:border-[#3A3A3C] px-4 py-2.5 rounded-full transition-all duration-200 active:scale-95 cursor-pointer <?= $prayer['is_interceded'] ? 'active bg-red-50 dark:bg-red-950/30 border-red-200 dark:border-red-900/40 text-red-500 dark:text-red-400' : '' ?>">
                            <i data-lucide="heart" class="w-4 h-4 transition-transform duration-300 <?= $prayer['is_interceded'] ? 'fill-red-500 scale-110' : '' ?>"></i>
                            <span><?= $prayer['is_interceded'] ? 'Intercedi' : 'Interceder' ?></span>
                            <span class="font-black font-body opacity-80">(<?= $prayer['pray_count'] ?>)</span>
                        </button>
                        
                        <?php if ($prayer['user_id'] == $userId && !$prayer['is_answered']): ?>
                        <form method="POST" class="m-0" onclick="event.stopPropagation()">
                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
                            <input type="hidden" name="action" value="answer_prayer">
                            <input type="hidden" name="prayer_id" value="<?= $prayer['id'] ?>">
                            <button type="submit" class="inline-flex items-center gap-1 px-3 py-2 bg-emerald-50 dark:bg-emerald-950/20 border border-emerald-100 dark:border-emerald-900/30 text-emerald-600 dark:text-emerald-400 font-bold text-xs rounded-full active:scale-95 transition-all cursor-pointer">
                                <i data-lucide="check" class="w-3.5 h-3.5"></i> Respondida
                            </button>
                        </form>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="col-span-1 md:col-span-2 bg-gray-50 dark:bg-[#1A1B1F] border border-dashed border-gray-200 dark:border-[#2C2C2E] rounded-3xl p-12 text-center reveal-item">
                    <div class="w-16 h-16 rounded-full bg-[#FFC107]/10 text-[#D97706] flex items-center justify-center mx-auto mb-4">
                        <i data-lucide="heart" class="w-8 h-8"></i>
                    </div>
                    <h3 class="text-lg font-bold text-gray-800 dark:text-white mb-1">Nenhum pedido de oração ativo</h3>
                    <p class="text-sm text-gray-500 dark:text-gray-400 max-w-sm mx-auto">Compartilhe suas necessidades de oração com a equipe e deixe todos intercederem por você.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</main>

<!-- DUAL FAB BUTTONS (Sacred Minimalist Dynamic Fixed Box) -->
<div class="fixed bottom-6 right-6 flex flex-col sm:flex-row gap-3 z-40">
    <button onclick="openCreatePrayerModal()" class="inline-flex items-center justify-center gap-2 px-5 py-3.5 rounded-full bg-[#FFC107] hover:bg-[#E5B006] text-slate-900 font-extrabold text-sm shadow-xl hover:shadow-2xl active:scale-95 transition-all duration-200 cursor-pointer" id="fab-prayer" style="display: none;">
        <i data-lucide="heart" class="w-4 h-4 fill-slate-900"></i> Novo Pedido
    </button>
    <button onclick="openCreateModal()" class="inline-flex items-center justify-center gap-2 px-5 py-3.5 rounded-full bg-[#2E7EED] hover:bg-[#1A6FD6] text-white font-extrabold text-sm shadow-xl hover:shadow-2xl active:scale-95 transition-all duration-200 cursor-pointer" id="fab-word">
        <i data-lucide="feather" class="w-4 h-4"></i> Novo Devocional
    </button>
</div>

<!-- Modal Create Devotional -->
<div id="devotionalModal" class="hidden fixed inset-0 z-50 bg-slate-950/60 backdrop-blur-sm flex items-center justify-center p-4 transition-opacity duration-300 opacity-0">
    <div id="devotionalModalContent" class="bg-white dark:bg-[#1A1B1F] w-full max-w-2xl rounded-3xl p-6 shadow-2xl transition-all transform scale-95 duration-300 max-h-[90vh] overflow-y-auto border border-gray-100 dark:border-white/5">
        <div class="flex items-center justify-between mb-6 pb-4 border-b border-gray-100 dark:border-white/5">
            <h2 class="text-xl font-bold text-gray-850 dark:text-white flex items-center gap-2">📖 Novo Devocional</h2>
            <button onclick="closeModalForce('devotionalModal')" class="w-8 h-8 rounded-full bg-gray-50 hover:bg-gray-100 dark:bg-[#2C2C2E] dark:hover:bg-[#3A3A3C] flex items-center justify-center text-gray-500 dark:text-gray-400 transition-colors cursor-pointer border border-transparent hover:border-gray-200 dark:hover:border-[#3A3A3C]">
                <i data-lucide="x" class="w-4 h-4"></i>
            </button>
        </div>
        
        <form method="POST" id="devotionalForm" onsubmit="return prepareSubmit()" class="space-y-4">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
            <input type="hidden" name="action" value="create">
            <input type="hidden" name="content" id="hiddenContent">
            
            <div class="flex flex-col gap-1.5">
                <label class="text-xs font-bold text-gray-400 dark:text-gray-500 uppercase tracking-wider pl-1">Título</label>
                <input type="text" name="title" required placeholder="Ex: A paz que excede todo entendimento" class="w-full h-11 px-4 bg-gray-50 dark:bg-[#2C2C2E] border border-gray-100 dark:border-[#3A3A3C] rounded-2xl text-sm focus:outline-none focus:border-[#2E7EED] dark:focus:border-[#2E7EED] focus:ring-1 focus:ring-[#2E7EED] transition-all placeholder-gray-400 dark:placeholder-gray-500 text-gray-800 dark:text-white font-semibold">
            </div>
            
            <div class="flex flex-col gap-1.5">
                <label class="text-xs font-bold text-gray-400 dark:text-gray-500 uppercase tracking-wider pl-1">Mensagem/Conteúdo</label>
                <div class="border border-gray-100 dark:border-[#3A3A3C] rounded-2xl overflow-hidden shadow-sm">
                    <div id="editor" style="height: 180px; background: white;" class="font-sans text-sm dark:bg-[#1A1B1F]"></div>
                </div>
            </div>
            
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div class="flex flex-col gap-1.5">
                    <label class="text-xs font-bold text-gray-400 dark:text-gray-500 uppercase tracking-wider pl-1">Tipo de Mídia</label>
                    <select name="media_type" onchange="toggleMediaInput(this.value)" class="w-full h-11 px-3 bg-gray-50 hover:bg-gray-100 dark:bg-[#2C2C2E] dark:hover:bg-[#3A3A3C] border border-gray-100 dark:border-[#3A3A3C] rounded-2xl text-sm font-semibold text-gray-700 dark:text-gray-300 focus:outline-none focus:border-[#2E7EED] transition-all">
                        <option value="text">Apenas Texto</option>
                        <option value="video">Vídeo (YouTube)</option>
                        <option value="link">Link Externo</option>
                    </select>
                </div>
                <!-- URL Media Group -->
                <div id="mediaUrlGroup" class="hidden flex flex-col gap-1.5 animate-fade-in">
                    <label class="text-xs font-bold text-gray-400 dark:text-gray-500 uppercase tracking-wider pl-1">URL da Mídia</label>
                    <input type="url" name="media_url" placeholder="https://youtube.com/watch?v=..." class="w-full h-11 px-4 bg-gray-50 dark:bg-[#2C2C2E] border border-gray-100 dark:border-[#3A3A3C] rounded-2xl text-sm focus:outline-none focus:border-[#2E7EED] dark:focus:border-[#2E7EED] focus:ring-1 focus:ring-[#2E7EED] transition-all placeholder-gray-400 dark:placeholder-gray-500 text-gray-800 dark:text-white font-semibold">
                </div>
            </div>
            
            <!-- Tags Selection Multi-Select -->
            <div class="flex flex-col gap-1.5">
                <label class="text-xs font-bold text-gray-400 dark:text-gray-500 uppercase tracking-wider pl-1">Tags (Assuntos)</label>
                <div class="relative w-full custom-dropdown">
                    <div onclick="toggleTagDropdownForm()" id="tagDropdownBtn" class="w-full h-11 px-4 bg-gray-50 hover:bg-gray-100 dark:bg-[#2C2C2E] dark:hover:bg-[#3A3A3C] border border-gray-100 dark:border-[#3A3A3C] rounded-2xl text-sm font-semibold text-gray-700 dark:text-gray-300 flex items-center justify-between gap-2 transition-colors cursor-pointer">
                        <span class="dark:text-gray-400">Selecionar Tags...</span>
                        <i data-lucide="chevron-down" class="w-4 h-4 text-gray-500"></i>
                    </div>
                    <div id="tagDropdownList" class="hidden absolute top-full left-0 right-0 mt-2 bg-white dark:bg-[#1A1B1F] border border-gray-100 dark:border-[#3A3A3C] rounded-2xl shadow-xl z-50 flex flex-col max-h-[180px] overflow-y-auto divide-y divide-gray-50 dark:divide-white/5">
                        <?php foreach ($allTags as $tag): ?>
                        <div onclick="toggleTagCheckboxForm(<?= $tag['id'] ?>, event)" class="px-4 py-2.5 hover:bg-gray-50 dark:hover:bg-[#2C2C2E] text-sm text-gray-700 dark:text-gray-300 flex items-center gap-3 cursor-pointer">
                            <input type="checkbox" name="tags[]" value="<?= $tag['id'] ?>" class="tag-form-checkbox w-4 h-4 rounded border-gray-300 dark:border-white/10 dark:bg-[#2C2C2E] text-[#2E7EED] focus:ring-[#2E7EED]">
                            <span class="flex-1 font-semibold text-gray-700 dark:text-gray-300">#<?= htmlspecialchars($tag['name']) ?></span>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
            
            <div class="flex gap-3 pt-4 border-t border-gray-100 dark:border-white/5">
                <button type="button" onclick="closeModalForce('devotionalModal')" class="flex-1 py-3 bg-gray-50 hover:bg-gray-100 dark:bg-[#2C2C2E] dark:hover:bg-[#3A3A3C] border border-gray-100 dark:border-[#3A3A3C] text-gray-700 dark:text-gray-300 font-bold text-sm rounded-2xl active:scale-95 transition-all cursor-pointer">Cancelar</button>
                <button type="submit" class="flex-[2] py-3 bg-[#2E7EED] hover:bg-[#1A6FD6] text-white font-bold text-sm rounded-2xl shadow-md shadow-[#2E7EED]/10 active:scale-95 transition-all cursor-pointer">Publicar</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal Create Prayer -->
<div id="prayerModal" class="hidden fixed inset-0 z-50 bg-slate-950/60 backdrop-blur-sm flex items-center justify-center p-4 transition-opacity duration-300 opacity-0">
    <div id="prayerModalContent" class="bg-white dark:bg-[#1A1B1F] w-full max-w-md rounded-3xl p-6 shadow-2xl transition-all transform scale-95 duration-300 max-h-[85vh] overflow-y-auto border border-gray-100 dark:border-white/5">
        <div class="flex items-center justify-between mb-6 pb-4 border-b border-gray-100 dark:border-white/5">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-2xl bg-[#FFC107]/10 text-[#D97706] flex items-center justify-center shrink-0 border border-transparent">
                    <span class="text-xl">🙏</span>
                </div>
                <div>
                    <h2 class="text-lg font-bold text-gray-850 dark:text-white leading-tight">Novo Pedido</h2>
                    <p class="text-[10px] text-gray-400 dark:text-gray-550 font-bold uppercase tracking-wider">Compartilhe na central de oração</p>
                </div>
            </div>
            <button onclick="closeModalForce('prayerModal')" class="w-8 h-8 rounded-full bg-gray-50 hover:bg-gray-100 dark:bg-[#2C2C2E] dark:hover:bg-[#3A3A3C] flex items-center justify-center text-gray-500 dark:text-gray-400 transition-colors cursor-pointer border border-transparent hover:border-gray-200 dark:hover:border-[#3A3A3C]">
                <i data-lucide="x" class="w-4 h-4"></i>
            </button>
        </div>
        
        <form method="POST" class="space-y-4">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
            <input type="hidden" name="action" value="create_prayer">
            
            <div class="flex flex-col gap-1.5">
                <label class="text-xs font-bold text-gray-400 dark:text-gray-500 uppercase tracking-wider pl-1">Título do Pedido</label>
                <input type="text" name="title" required placeholder="Ex: Oração pela saúde do meu pai" class="w-full h-11 px-4 bg-gray-50 dark:bg-[#2C2C2E] border border-gray-100 dark:border-[#3A3A3C] rounded-2xl text-sm focus:outline-none focus:border-[#2E7EED] dark:focus:border-[#2E7EED] focus:ring-1 focus:ring-[#2E7EED] transition-all placeholder-gray-400 dark:placeholder-gray-500 text-gray-800 dark:text-white font-semibold">
            </div>
            
            <div class="flex flex-col gap-1.5">
                <label class="text-xs font-bold text-gray-400 dark:text-gray-500 uppercase tracking-wider pl-1">Descrição detalhada</label>
                <textarea name="description" rows="4" placeholder="Adicione detalhes adicionais se desejar..." class="w-full border-gray-100 dark:border-[#3A3A3C] bg-gray-50 dark:bg-[#2C2C2E] border rounded-2xl text-sm focus:outline-none focus:border-[#2E7EED] dark:focus:border-[#2E7EED] focus:ring-1 focus:ring-[#2E7EED] transition-all placeholder-gray-400 dark:placeholder-gray-500 text-gray-850 dark:text-gray-200 p-4 resize-none font-body leading-relaxed"></textarea>
            </div>
            
            <div class="flex flex-col gap-1.5">
                <label class="text-xs font-bold text-gray-400 dark:text-gray-500 uppercase tracking-wider pl-1">Categoria</label>
                <div class="grid grid-cols-3 gap-2">
                    <label class="cat-option border border-gray-150 dark:border-white/10 rounded-2xl p-2.5 flex flex-col items-center justify-center gap-1.5 cursor-pointer bg-white dark:bg-[#1A1B1F] transition-all text-center select-none text-gray-500 dark:text-gray-400" onclick="selectFormCategory(this)">
                        <input type="radio" name="category" value="health" class="hidden">
                        <span class="text-lg">❤️</span>
                        <span class="text-[10px] font-bold uppercase tracking-wider">Saúde</span>
                    </label>
                    <label class="cat-option border border-gray-150 dark:border-white/10 rounded-2xl p-2.5 flex flex-col items-center justify-center gap-1.5 cursor-pointer bg-white dark:bg-[#1A1B1F] transition-all text-center select-none text-gray-500 dark:text-gray-400" onclick="selectFormCategory(this)">
                        <input type="radio" name="category" value="family" class="hidden">
                        <span class="text-lg">👨‍👩‍👧</span>
                        <span class="text-[10px] font-bold uppercase tracking-wider">Família</span>
                    </label>
                    <label class="cat-option border border-gray-150 dark:border-white/10 rounded-2xl p-2.5 flex flex-col items-center justify-center gap-1.5 cursor-pointer bg-white dark:bg-[#1A1B1F] transition-all text-center select-none text-gray-500 dark:text-gray-400" onclick="selectFormCategory(this)">
                        <input type="radio" name="category" value="work" class="hidden">
                        <span class="text-lg">💼</span>
                        <span class="text-[10px] font-bold uppercase tracking-wider">Trabalho</span>
                    </label>
                    <label class="cat-option border border-gray-150 dark:border-white/10 rounded-2xl p-2.5 flex flex-col items-center justify-center gap-1.5 cursor-pointer bg-white dark:bg-[#1A1B1F] transition-all text-center select-none text-gray-500 dark:text-gray-400" onclick="selectFormCategory(this)">
                        <input type="radio" name="category" value="spiritual" class="hidden">
                        <span class="text-lg">🙏</span>
                        <span class="text-[10px] font-bold uppercase tracking-wider">Fé</span>
                    </label>
                    <label class="cat-option border border-gray-150 dark:border-white/10 rounded-2xl p-2.5 flex flex-col items-center justify-center gap-1.5 cursor-pointer bg-white dark:bg-[#1A1B1F] transition-all text-center select-none text-gray-500 dark:text-gray-400" onclick="selectFormCategory(this)">
                        <input type="radio" name="category" value="gratitude" class="hidden">
                        <span class="text-lg">🎉</span>
                        <span class="text-[10px] font-bold uppercase tracking-wider">Gratidão</span>
                    </label>
                    <label class="cat-option border border-[#2E7EED] dark:border-[#2E7EED] rounded-2xl p-2.5 flex flex-col items-center justify-center gap-1.5 cursor-pointer bg-[#2E7EED]/5 dark:bg-[#2E7EED]/10 transition-all text-center select-none text-[#2E7EED]" onclick="selectFormCategory(this)">
                        <input type="radio" name="category" value="other" checked class="hidden">
                        <span class="text-lg">✨</span>
                        <span class="text-[10px] font-bold uppercase tracking-wider">Outros</span>
                    </label>
                </div>
            </div>
            
            <div class="flex gap-4 pt-1 pb-2">
                <label class="flex items-center gap-2 cursor-pointer select-none">
                    <input type="checkbox" name="is_urgent" class="w-4.5 h-4.5 rounded border-gray-300 dark:border-white/10 dark:bg-[#2C2C2E] text-[#FFC107] focus:ring-[#FFC107]">
                    <span class="text-xs font-bold text-gray-600 dark:text-gray-300 uppercase tracking-wider">🔥 Urgente</span>
                </label>
                <label class="flex items-center gap-2 cursor-pointer select-none">
                    <input type="checkbox" name="is_anonymous" class="w-4.5 h-4.5 rounded border-gray-300 dark:border-white/10 dark:bg-[#2C2C2E] text-slate-500 focus:ring-slate-500">
                    <span class="text-xs font-bold text-gray-600 dark:text-gray-300 uppercase tracking-wider">🔒 Anônimo</span>
                </label>
            </div>
            
            <div class="flex gap-3 pt-4 border-t border-gray-100 dark:border-white/5">
                <button type="button" onclick="closeModalForce('prayerModal')" class="flex-1 py-3 bg-gray-50 hover:bg-gray-100 dark:bg-[#2C2C2E] dark:hover:bg-[#3A3A3C] border border-gray-100 dark:border-[#3A3A3C] text-gray-700 dark:text-gray-300 font-bold text-sm rounded-2xl active:scale-95 transition-all cursor-pointer">Cancelar</button>
                <button type="submit" class="flex-[2] py-3 bg-[#2E7EED] hover:bg-[#1A6FD6] text-white font-bold text-sm rounded-2xl shadow-md active:scale-95 transition-all cursor-pointer">Enviar Pedido</button>
            </div>
        </form>
    </div>
</div>

<script src="https://cdn.quilljs.com/1.3.6/quill.js"></script>
<script>
    var quill;
    document.addEventListener('DOMContentLoaded', function() {
        quill = new Quill('#editor', {
            theme: 'snow',
            placeholder: 'Compartilhe sua reflexão... Use [verso Romanos 8:28] para citar a Bíblia.',
            modules: { 
                toolbar: [
                    ['bold', 'italic', 'underline'], 
                    [{ 'list': 'ordered'}, { 'list': 'bullet' }], 
                    ['link', 'clean']
                ] 
            }
        });
        
        // CHECK URL PARAMS FOR TAB
        const urlParams = new URLSearchParams(window.location.search);
        const tab = urlParams.get('tab');
        if (tab === 'prayer') {
            switchTab('prayer');
        } else {
            switchTab('word');
        }
    });

    function switchTab(tabName) {
        // Hide all
        document.querySelectorAll('.tab-content').forEach(el => {
            el.classList.add('hidden');
            el.classList.remove('block');
        });
        document.querySelectorAll('.tab-btn').forEach(el => {
            el.classList.remove('active', 'bg-white', 'text-gray-800', 'shadow-sm', 'dark:bg-[#1A1B1F]', 'dark:text-white');
            el.classList.add('text-gray-500', 'dark:text-gray-400');
        });
        
        // Show target
        const activeTab = document.getElementById('tab-' + tabName);
        activeTab.classList.remove('hidden');
        activeTab.classList.add('block');
        
        const activeBtn = document.getElementById('btn-tab-' + tabName);
        activeBtn.classList.add('active', 'bg-white', 'text-gray-800', 'shadow-sm', 'dark:bg-[#1A1B1F]', 'dark:text-white');
        activeBtn.classList.remove('text-gray-500', 'dark:text-gray-400');
        
        // Stagger cascade triggers
        const activeCards = activeTab.querySelectorAll('.devotional-card, .prayer-card, .reveal-item');
        activeCards.forEach((card, index) => {
            card.style.animation = 'none';
            card.offsetHeight; /* trigger reflow */
            card.style.animation = '';
        });

        // Toggle FABs visibility
        const fabWord = document.getElementById('fab-word');
        const fabPrayer = document.getElementById('fab-prayer');
        if (tabName === 'prayer') {
            if (fabWord) fabWord.style.display = 'none';
            if (fabPrayer) fabPrayer.style.display = 'inline-flex';
        } else {
            if (fabWord) fabWord.style.display = 'inline-flex';
            if (fabPrayer) fabPrayer.style.display = 'none';
        }

        // Update URL without reload
        const url = new URL(window.location);
        url.searchParams.set('tab', tabName);
        window.history.pushState({}, '', url);
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

    function openCreateModal() { openModal('devotionalModal'); }
    function openCreatePrayerModal() { openModal('prayerModal'); }
    
    // Form prep
    function prepareSubmit() {
        var content = document.getElementById('hiddenContent');
        content.value = quill.root.innerHTML;
        return true;
    }

    function toggleMediaInput(type) {
        const group = document.getElementById('mediaUrlGroup');
        if (type === 'text') {
            group.classList.add('hidden');
        } else {
            group.classList.remove('hidden');
        }
    }
    
    function toggleComments(id, event) {
        if(event) event.stopPropagation();
        const section = document.getElementById(id);
        section.classList.toggle('hidden');
        section.classList.toggle('animate-fade-in');
    }
    
    function shareDevotional(id, title, event) {
        event.stopPropagation();
        if (navigator.share) {
            navigator.share({ title: title, text: 'Devocional: ' + title, url: window.location.href.split('?')[0] + '?id=' + id });
        } else {
            // Fallback copy
            const dummy = document.createElement('input');
            const text = window.location.href.split('?')[0] + '?id=' + id;
            document.body.appendChild(dummy);
            dummy.value = text;
            dummy.select();
            document.execCommand('copy');
            document.body.removeChild(dummy);
            alert('Link copiado para a área de transferência!');
        }
    }
    
    // Prayer Interactions
    function toggleIntercessionStatus(prayerId, btn) {
        const icon = btn.querySelector('svg');
        const textSpan = btn.querySelector('span');
        const isActive = btn.classList.contains('active');
        
        btn.classList.toggle('active');
        if (isActive) {
           textSpan.innerText = 'Interceder';
           icon.classList.remove('fill-red-500', 'scale-110');
           btn.classList.remove('bg-red-50', 'border-red-200', 'text-red-500');
        } else {
           textSpan.innerText = 'Intercedi';
           icon.classList.add('fill-red-500', 'scale-110');
           btn.classList.add('bg-red-50', 'border-red-200', 'text-red-500');
        }
        
        const form = document.createElement('form');
        form.method = 'POST';
        form.style.display = 'none';
        
        const csrfInput = document.createElement('input');
        csrfInput.name = 'csrf_token';
        csrfInput.value = '<?= htmlspecialchars($_SESSION['csrf_token']) ?>';
        
        const inputAction = document.createElement('input');
        inputAction.name = 'action';
        inputAction.value = 'pray';
        
        const inputId = document.createElement('input');
        inputId.name = 'prayer_id';
        inputId.value = prayerId;
        
        form.appendChild(csrfInput);
        form.appendChild(inputAction);
        form.appendChild(inputId);
        document.body.appendChild(form);
        form.submit();
    }
    
    // Tag Dropdown Logic in Form
    function toggleTagDropdownForm() {
        const list = document.getElementById('tagDropdownList');
        list.classList.toggle('hidden');
    }

    function toggleTagCheckboxForm(id, event) {
        event.stopPropagation();
        const list = document.getElementById('tagDropdownList');
        const cb = list.querySelector(`input[value="${id}"]`);
        cb.checked = !cb.checked;
        updateTagButtonLabel();
    }

    function updateTagButtonLabel() {
        const checkboxes = document.querySelectorAll('#tagDropdownList input[name="tags[]"]:checked');
        const btnSpan = document.querySelector('#tagDropdownBtn span');
        
        if (checkboxes.length === 0) {
            btnSpan.innerText = 'Selecionar Tags...';
            btnSpan.style.color = '#9ca3af'; // gray-400
        } else {
            btnSpan.innerText = checkboxes.length + ' tag(s) selecionada(s)';
            btnSpan.style.color = '#1f2937'; // gray-800
        }
    }

    // Close tag list on click outside
    document.addEventListener('click', function(e) {
        const container = document.querySelector('.custom-dropdown');
        const list = document.getElementById('tagDropdownList');
        
        if (container && !container.contains(e.target) && list && !list.classList.contains('hidden')) {
            list.classList.add('hidden');
        }
    });
    
    // Category Selector style logic in Form
    function selectFormCategory(element) {
        document.querySelectorAll('.cat-option').forEach(el => {
            el.classList.remove('border-[#2E7EED]', 'bg-[#2E7EED]/5', 'text-[#2E7EED]', 'dark:border-[#2E7EED]', 'dark:bg-[#2E7EED]/10', 'dark:text-[#2E7EED]');
            el.classList.add('border-gray-150', 'bg-white', 'text-gray-500', 'dark:border-white/10', 'dark:bg-[#1A1B1F]', 'dark:text-gray-400');
            const text = el.querySelector('span:last-child');
            text.classList.remove('text-[#2E7EED]');
            text.classList.add('text-gray-500', 'dark:text-gray-400');
        });
        
        element.classList.remove('border-gray-150', 'bg-white', 'text-gray-500', 'dark:border-white/10', 'dark:bg-[#1A1B1F]', 'dark:text-gray-400');
        element.classList.add('border-[#2E7EED]', 'bg-[#2E7EED]/5', 'text-[#2E7EED]', 'dark:border-[#2E7EED]', 'dark:bg-[#2E7EED]/10', 'dark:text-[#2E7EED]');
        
        const text = element.querySelector('span:last-child');
        text.classList.remove('text-gray-500', 'dark:text-gray-400');
        text.classList.add('text-[#2E7EED]');
        
        const radio = element.querySelector('input[type="radio"]');
        radio.checked = true;
    }
    
    // Smart Search com Debounce
    let searchTimeout;
    function handleSmartSearch(value) {
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(() => {
            const currentUrl = new URL(window.location.href);
            const params = new URLSearchParams(currentUrl.search);
            
            if (value.trim()) {
                params.set('search', value);
            } else {
                params.delete('search');
            }
            
            window.location.href = '?' + params.toString();
        }, 800);
    }
    
    // Ctrl+K to Search shortcut
    document.addEventListener('keydown', function(e) {
        if ((e.ctrlKey || e.metaKey) && e.key === 'k') {
            e.preventDefault();
            const input = document.getElementById('smartSearch');
            if (input) input.focus();
        }
    });
</script>

<?php renderAppFooter(); ?>