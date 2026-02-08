<?php
// admin/devocionais.php
require_once '../includes/auth.php';
require_once '../includes/db.php';
require_once '../includes/layout.php';
require_once '../includes/devotional_helpers.php';

checkLogin();

$userId = $_SESSION['user_id'] ?? 1;

// --- LÓGICA DE POST (CRUD) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            // --- DEVOCIONAIS ---
            case 'create':
                // Extrair referências de versículos do conteúdo
                $verseRefs = extractVerseReferences($_POST['content']);
                $verseRefsJson = !empty($verseRefs) ? json_encode($verseRefs) : NULL;
                
                // Criar devocional
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
                
                // Enviar notificações
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
                // Apenas o autor pode deletar (ou admin)
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
                // Verificar se tabela existe
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
$filterRead = $_GET['read_status'] ?? 'all'; // 'all', 'read', 'unread'

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

// Filtro avançado: Autor
if (!empty($filterAuthor)) {
    $sql .= " AND d.user_id = ?";
    $params[] = $filterAuthor;
}

// Filtro avançado: Data inicial
if (!empty($filterDateFrom)) {
    $sql .= " AND DATE(d.created_at) >= ?";
    $params[] = $filterDateFrom;
}

// Filtro avançado: Data final
if (!empty($filterDateTo)) {
    $sql .= " AND DATE(d.created_at) <= ?";
    $params[] = $filterDateTo;
}

// Filtro avançado: Versículos
if (!empty($filterVerse)) {
    $sql .= " AND (d.verse_references LIKE ? OR d.content LIKE ?)";
    $params[] = "%$filterVerse%";
    $params[] = "%[verso%$filterVerse%]%";
}



// Filtro: Status de leitura
if ($filterRead === 'read') {
    $sql .= " AND dr.id IS NOT NULL";
} elseif ($filterRead === 'unread') {
    $sql .= " AND dr.id IS NULL";
}

// Ordenação: Não lidas primeiro, depois por data
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

renderAppHeader('Espiritualidade');
?>

<!-- Quill CSS -->
<link href="https://cdn.quilljs.com/1.3.6/quill.snow.css" rel="stylesheet">
<link href="../assets/css/pages/devocionais.css?v=<?= time() ?>" rel="stylesheet">

<?php renderPageHeader('Espiritualidade', 'Louvor PIB Oliveira'); ?>

<div class="container" style="padding-top: 10px; max-width: 700px; margin: 0 auto;">

    <!-- TABS -->
    <div class="page-tabs">
        <button class="tab-btn active" onclick="switchTab('word')" id="btn-tab-word">📖 Palavra</button>
        <button class="tab-btn" onclick="switchTab('prayer')" id="btn-tab-prayer">🙏 Oração</button>
    </div>

    <!-- CONTEÚDO: PALAVRA (DEVOCIONAIS) -->
    <div id="tab-word" class="tab-content active">
        <!-- Busca Inteligente Unificada -->
        <div style="margin-bottom: 20px;">
            <!-- Campo de Busca Principal -->
            <div style="position: relative;">
                <i data-lucide="search" style="position: absolute; left: 14px; top: 50%; transform: translateY(-50%); color: var(--text-muted); width: 20px;"></i>
                <input 
                    type="text" 
                    id="smartSearch" 
                    placeholder="Buscar por título ou conteúdo..." 
                    value="<?= htmlspecialchars($search) ?>"
                    style="width: 100%; padding: 14px 14px 14px 48px; border-radius: 14px; border: 1px solid var(--border-medium); font-size: 1rem; outline: none; transition: all 0.2s; background: var(--bg-surface); color: var(--text-primary); box-shadow: var(--shadow-sm);"
                    onfocus="this.style.borderColor='var(--primary)'; this.style.boxShadow='0 0 0 3px var(--primary-light)'"
                    onblur="this.style.borderColor='var(--border-medium)'; this.style.boxShadow='var(--shadow-sm)'"
                    oninput="handleSmartSearch(this.value)"
                >
            </div>

            <!-- Filtros Rápidos (Pills) -->
            <div style="display: flex; gap: 8px; margin-top: 12px; flex-wrap: wrap; align-items: center;">
                <span style="font-size: 0.85rem; color: var(--text-muted); font-weight: 600;">Filtros:</span>
                
                <!-- Status Pills -->
                <a href="?read_status=all&author=<?= $filterAuthor ?>&tag=<?= $filterTag ?>&search=<?= urlencode($search) ?>" 
                   class="filter-pill <?= $filterRead === 'all' ? 'active' : '' ?>">
                    Todas
                </a>
                <a href="?read_status=unread&author=<?= $filterAuthor ?>&tag=<?= $filterTag ?>&search=<?= urlencode($search) ?>" 
                   class="filter-pill <?= $filterRead === 'unread' ? 'active' : '' ?>">
                    Não Lidas
                </a>
                <a href="?read_status=read&author=<?= $filterAuthor ?>&tag=<?= $filterTag ?>&search=<?= urlencode($search) ?>" 
                   class="filter-pill <?= $filterRead === 'read' ? 'active' : '' ?>">
                    Lidas
                </a>

                <span style="width: 1px; height: 20px; background: var(--border-medium); margin: 0 4px;"></span>

                <!-- Autor Dropdown Compacto -->
                <select onchange="window.location.href='?author='+this.value+'&read_status=<?= $filterRead ?>&tag=<?= $filterTag ?>&search=<?= urlencode($search) ?>'" 
                        style="padding: 6px 32px 6px 12px; border-radius: 20px; border: 1px solid var(--border-medium); font-size: 0.85rem; font-weight: 600; background: var(--bg-surface); color: var(--text-primary); cursor: pointer; appearance: none; background-image: url('data:image/svg+xml,%3Csvg xmlns=%27http://www.w3.org/2000/svg%27 width=%2712%27 height=%2712%27 viewBox=%270 0 24 24%27 fill=%27none%27 stroke=%27%2364748b%27 stroke-width=%272%27%3E%3Cpolyline points=%276 9 12 15 18 9%27%3E%3C/polyline%3E%3C/svg%3E'); background-repeat: no-repeat; background-position: right 10px center;">
                    <option value="">👤 Todos</option>
                    <?php foreach ($allAuthors as $author): ?>
                        <option value="<?= $author['id'] ?>" <?= $filterAuthor == $author['id'] ? 'selected' : '' ?>><?= htmlspecialchars($author['name']) ?></option>
                    <?php endforeach; ?>
                </select>

                <!-- Tag Dropdown Compacto -->
                <select onchange="window.location.href='?tag='+this.value+'&read_status=<?= $filterRead ?>&author=<?= $filterAuthor ?>&search=<?= urlencode($search) ?>'" 
                        style="padding: 6px 32px 6px 12px; border-radius: 20px; border: 1px solid var(--border-medium); font-size: 0.85rem; font-weight: 600; background: var(--bg-surface); color: var(--text-primary); cursor: pointer; appearance: none; background-image: url('data:image/svg+xml,%3Csvg xmlns=%27http://www.w3.org/2000/svg%27 width=%2712%27 height=%2712%27 viewBox=%270 0 24 24%27 fill=%27none%27 stroke=%27%2364748b%27 stroke-width=%272%27%3E%3Cpolyline points=%276 9 12 15 18 9%27%3E%3C/polyline%3E%3C/svg%3E'); background-repeat: no-repeat; background-position: right 10px center;">
                    <option value="">🏷️ Todas</option>
                    <?php foreach ($allTags as $tag): ?>
                        <option value="<?= $tag['id'] ?>" <?= $filterTag == $tag['id'] ? 'selected' : '' ?>>#<?= htmlspecialchars($tag['name']) ?></option>
                    <?php endforeach; ?>
                </select>

                <!-- Limpar Filtros -->
                <?php if (!empty($search) || $filterRead !== 'all' || !empty($filterAuthor) || !empty($filterTag)): ?>
                <a href="?" style="padding: 6px 12px; border-radius: 20px; font-size: 0.85rem; font-weight: 600; background: var(--red-50); color: var(--red-600); text-decoration: none; display: inline-flex; align-items: center; gap: 4px;">
                    <i data-lucide="x" style="width: 14px;"></i> Limpar
                </a>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Feed de Devocionais -->
        <div class="devotionals-list">
            <?php if (count($devotionals) > 0): ?>
                <?php foreach ($devotionals as $dev): 
                    $tags = getDevotionalTags($pdo, $dev['id']);
                    $comments = getDevotionalComments($pdo, $dev['id']);
                    $authorName = $dev['author_name'] ?? 'Membro';
                    $authorAvatar = $dev['author_avatar'] ?? null;
                    if ($authorAvatar && strpos($authorAvatar, 'http') === false && strpos($authorAvatar, 'assets') === false) {
                        $authorAvatar = '../assets/uploads/' . $authorAvatar;
                    }
                ?>
                <div class="devotional-card collapsed <?= $dev['is_read'] ? 'read' : 'unread' ?>" id="dev-<?= $dev['id'] ?>" onclick="toggleDevotional(<?= $dev['id'] ?>, event)">
                    <!-- Header -->
                    <div class="dev-header">
                        <?php if ($authorAvatar): ?>
                            <img src="<?= htmlspecialchars($authorAvatar) ?>" class="dev-avatar" alt="">
                        <?php else: ?>
                            <div class="dev-avatar-placeholder"><?= strtoupper(substr($authorName, 0, 1)) ?></div>
                        <?php endif; ?>
                        
                        <div class="dev-author-info">
                            <div class="dev-author-name">
                                <?= htmlspecialchars($authorName) ?>
                                <?php if (!empty($dev['series_title'])): ?>
                                    <span style="font-weight: 400; color: var(--text-muted); font-size: 0.85rem;">
                                        em <strong style="color: <?= $dev['series_color'] ?: 'var(--primary)' ?>"><?= htmlspecialchars($dev['series_title']) ?></strong>
                                    </span>
                                <?php endif; ?>
                            </div>
                            <div class="dev-meta">
                                <span><?= date('d/m \à\s H:i', strtotime($dev['created_at'])) ?></span>
                                <?php if (!$dev['is_read']): ?>
                                    <span style="color: var(--green-600); font-weight: 600;">• Novo</span>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <?php if ($dev['user_id'] == $userId || $_SESSION['user_role'] === 'admin'): ?>
                        <div class="dev-options" onclick="event.stopPropagation()">
                             <form method="POST" onsubmit="return confirm('Excluir este devocional?');" style="display:inline;">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="id" value="<?= $dev['id'] ?>">
                                <button type="submit" style="background:none; border:none; color: var(--text-muted); cursor: pointer; padding: 4px;">
                                    <i data-lucide="trash-2" style="width: 18px;"></i>
                                </button>
                            </form>
                        </div>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Content -->
                    <div class="dev-content">
                        <h2 class="dev-title"><?= htmlspecialchars($dev['title']) ?></h2>
                        <div class="dev-preview"><?= strip_tags($dev['content']) ?></div>
                        <div class="dev-text"><?= $dev['content'] ?></div>
                        
                        <?php if ($dev['media_type'] === 'video' && !empty($dev['media_url'])): 
                            $embedUrl = str_replace('watch?v=', 'embed/', $dev['media_url']);
                        ?>
                        <div class="dev-media">
                            <iframe src="<?= htmlspecialchars($embedUrl) ?>" allowfullscreen></iframe>
                        </div>
                        <?php elseif ($dev['media_type'] === 'link' && !empty($dev['media_url'])): ?>
                        <div class="dev-media">
                            <a href="<?= htmlspecialchars($dev['media_url']) ?>" target="_blank" class="dev-link-preview" onclick="event.stopPropagation()">
                                <i data-lucide="external-link" style="width: 24px;"></i>
                                <div style="flex: 1; overflow: hidden;">
                                    <div style="font-weight: 600; truncate"><?= htmlspecialchars($dev['media_url']) ?></div>
                                    <div style="font-size: 0.85rem; color: var(--text-muted);">Clique para acessar o link externo</div>
                                </div>
                            </a>
                        </div>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Tags -->
                    <?php if (!empty($tags)): ?>
                    <div class="dev-tags">
                        <?php foreach ($tags as $tag): ?>
                            <span class="dev-tag">#<?= htmlspecialchars($tag['name']) ?></span>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                    
                    <!-- Expand Indicator -->
                    <div class="expand-indicator">Lear mais <i data-lucide="chevron-down" style="width: 14px;"></i></div>
                    
                    <!-- Footer -->
                    <div class="dev-footer">
                        <div class="dev-actions">
                            <button class="dev-action-btn" onclick="toggleComments('comments-<?= $dev['id'] ?>', event)">
                                <i data-lucide="message-circle" style="width: 18px;"></i>
                                <span><?= count($comments) ?> Comments</span>
                            </button>
                            <button class="dev-action-btn" onclick="shareDevotional(<?= $dev['id'] ?>, '<?= addslashes($dev['title']) ?>', event)">
                                <i data-lucide="share-2" style="width: 18px;"></i>
                                <span>Share</span>
                            </button>
                        </div>
                    </div>
                    
                    <!-- Comments -->
                    <div id="comments-<?= $dev['id'] ?>" class="comments-section" onclick="event.stopPropagation()">
                        <?php foreach ($comments as $comment): 
                             $commentAvatar = $comment['author_avatar'];
                             if ($commentAvatar && strpos($commentAvatar, 'http') === false) {
                                $commentAvatar = '../assets/uploads/' . $commentAvatar;
                            }
                        ?>
                        <div class="comment-item">
                            <?php if ($commentAvatar): ?>
                                <img src="<?= htmlspecialchars($commentAvatar) ?>" class="comment-avatar" alt="">
                            <?php else: ?>
                                <div class="comment-avatar" style="background: var(--slate-300); display: flex; align-items: center; justify-content: center; font-size: 0.75rem; font-weight: 700; color: #555;">
                                    <?= strtoupper(substr($comment['author_name'], 0, 1)) ?>
                                </div>
                            <?php endif; ?>
                            <div class="comment-content">
                                <div style="display: flex; justify-content: space-between;">
                                    <span class="comment-author"><?= htmlspecialchars($comment['author_name']) ?></span>
                                    <span class="comment-time"><?= date('d/m H:i', strtotime($comment['created_at'])) ?></span>
                                </div>
                                <p class="comment-text"><?= nl2br(htmlspecialchars($comment['comment'])) ?></p>
                            </div>
                             <?php if ($comment['user_id'] == $userId || $_SESSION['user_role'] === 'admin'): ?>
                            <div style="margin-left: 8px;">
                                <form method="POST" onsubmit="return confirm('Apagar comentário?');">
                                    <input type="hidden" name="action" value="delete_comment">
                                    <input type="hidden" name="comment_id" value="<?= $comment['id'] ?>">
                                    <button type="submit" style="background: none; border: none; color: var(--rose-400); cursor: pointer;">
                                        <i data-lucide="x" style="width: 14px;"></i>
                                    </button>
                                </form>
                            </div>
                            <?php endif; ?>
                        </div>
                        <?php endforeach; ?>
                        
                        <form method="POST" class="comment-form">
                            <input type="hidden" name="action" value="comment">
                            <input type="hidden" name="devotional_id" value="<?= $dev['id'] ?>">
                            <input type="text" name="comment" class="comment-input" placeholder="Escreva um comentário..." required>
                            <button type="submit" class="comment-submit">
                                <i data-lucide="send" style="width: 18px;"></i>
                            </button>
                        </form>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div style="text-align: center; padding: 60px 20px;">
                    <div style="background: var(--green-50); width: 80px; height: 80px; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 20px;">
                        <i data-lucide="book-open" style="color: var(--green-400); width: 40px; height: 40px;"></i>
                    </div>
                    <h3 style="color: var(--text-main); margin-bottom: 8px;">Nenhum devocional ainda</h3>
                    <p style="color: var(--text-muted); font-size: 0.95rem; max-width: 300px; margin: 0 auto 20px;">Seja o primeiro a compartilhar uma reflexão!</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- CONTEÚDO: ORAÇÃO -->
    <div id="tab-prayer" class="tab-content">
        <div class="prayer-list">
            <?php if ($prayerTableExists && count($prayers) > 0): ?>
                <?php foreach ($prayers as $prayer): 
                    $authorAvatar = $prayer['author_avatar'];
                     if ($authorAvatar && strpos($authorAvatar, 'http') === false && strpos($authorAvatar, 'assets') === false) {
                        $authorAvatar = '../assets/uploads/' . $authorAvatar;
                    }
                ?>
                <div class="prayer-card <?= ($prayer['is_answered']) ? 'answered' : '' ?> collapsed" onclick="this.classList.toggle('collapsed')">
                    <div class="prayer-header">
                        <div class="prayer-user-info">
                            <?php if ($authorAvatar): ?>
                                <img src="<?= $authorAvatar ?>" class="prayer-avatar">
                            <?php else: ?>
                                <div class="prayer-avatar" style="background: var(--primary-subtle); display: flex; align-items: center; justify-content: center; color: var(--primary); font-weight: 700;">
                                    <?= strtoupper(substr($prayer['author_name'] ?? 'A', 0, 1)) ?>
                                </div>
                            <?php endif; ?>
                            <div class="prayer-meta">
                                <h4>
                                    <?= $prayer['is_anonymous'] ? 'Anônimo' : htmlspecialchars($prayer['author_name'] ?? 'Anônimo') ?>
                                    <?php if($prayer['is_urgent']): ?>
                                        <span style="color: var(--rose-500);">🔥</span>
                                    <?php endif; ?>
                                </h4>
                                <span><?= date('d/m \à\s H:i', strtotime($prayer['created_at'])) ?></span>
                            </div>
                        </div>
                        
                        <span class="cat-badge cat-<?= $prayer['category'] ?? 'other' ?>">
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
                    <div class="prayer-content">
                        <h3 class="prayer-title"><?= htmlspecialchars($prayer['title']) ?></h3>
                        <?php if (!empty($prayer['description'])): ?>
                            <div class="prayer-description"><?= nl2br(htmlspecialchars($prayer['description'])) ?></div>
                        <?php endif; ?>
                        
                        <!-- Expand Indicator -->
                        <div class="expand-indicator">
                            Ler mais <i data-lucide="chevron-down" style="width: 14px;"></i>
                        </div>
                    </div>
                    
                    <!-- Footer -->
                    <div class="prayer-footer">
                        <div style="display: flex; align-items: center; gap: 10px;">
                            <button onclick="toggleIntercessionStatus(<?= $prayer['id'] ?>, this); event.stopPropagation();" class="pray-action-btn <?= $prayer['is_interceded'] ? 'active' : '' ?>">
                                <i data-lucide="heart" style="width: 16px; <?= $prayer['is_interceded'] ? 'fill: currentColor;' : '' ?>"></i>
                                <span><?= $prayer['is_interceded'] ? 'Intercedi' : 'Interceder' ?></span> (<?= $prayer['pray_count'] ?>)
                            </button>
                        </div>
                        
                        <?php if ($prayer['user_id'] == $userId && !$prayer['is_answered']): ?>
                        <form method="POST" style="margin: 0;" onclick="event.stopPropagation()">
                            <input type="hidden" name="action" value="answer_prayer">
                            <input type="hidden" name="prayer_id" value="<?= $prayer['id'] ?>">
                            <button type="submit" style="background: var(--primary-subtle); color: var(--primary); border: none; padding: 6px 12px; border-radius: 12px; font-size: 0.85rem; font-weight: 600; cursor: pointer;">
                                ✓ Marcar respondida
                            </button>
                        </form>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div style="text-align: center; padding: 60px 20px;">
                    <div style="background: var(--primary-subtle); width: 80px; height: 80px; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 20px;">
                        <i data-lucide="heart" style="color: var(--primary); width: 40px; height: 40px;"></i>
                    </div>
                    <h3 style="color: var(--text-main); margin-bottom: 8px;">Nenhum pedido de oração</h3>
                    <p style="color: var(--text-muted); font-size: 0.95rem; max-width: 300px; margin: 0 auto 20px;">
                        Compartilhe seus pedidos e deixe a igreja orar por você.
                    </p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <div style="height: 100px;"></div>

</div>

<!-- DUAL FAB BUTTONS -->
<div class="fab-container">
    <button onclick="openCreatePrayerModal()" class="fab-btn fab-prayer" id="fab-prayer">
        <i data-lucide="heart" style="width: 20px; height: 20px;"></i> Novo Pedido
    </button>
    <button onclick="openCreateModal()" class="fab-btn fab-devotional" id="fab-word">
        <i data-lucide="feather" style="width: 20px; height: 20px;"></i> Novo Devocional
    </button>
</div>

<!-- Modal Create Devotional -->
<div id="devotionalModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; z-index: 1000;">
    <div onclick="closeModal()" style="position: absolute; width: 100%; height: 100%; background: rgba(0,0,0,0.5); backdrop-filter: blur(4px);"></div>
    <div style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); width: 90%; max-width: 600px; background: var(--bg-surface); border-radius: 24px; padding: 24px; box-shadow: 0 20px 60px rgba(0,0,0,0.2); max-height: 90vh; overflow-y: auto;">
        <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 20px;">
            <h2 style="margin: 0; font-size: 1.5rem; font-weight: 800; color: var(--text-main);">📖 Novo Devocional</h2>
            <button onclick="closeModal()" style="background: none; border: none; color: var(--text-muted); cursor: pointer; padding: 8px;">
                <i data-lucide="x" style="width: 20px;"></i>
            </button>
        </div>
        <form method="POST" id="devotionalForm" onsubmit="return prepareSubmit()">
            <input type="hidden" name="action" value="create">
            <input type="hidden" name="content" id="hiddenContent">
            
            <div style="margin-bottom: 16px;">
                <label style="display: block; font-weight: 700; color: var(--text-main); margin-bottom: 6px;">Título</label>
                <input type="text" name="title" required placeholder="Ex: A paz que excede todo entendimento">
            </div>
            
            <div style="margin-bottom: 16px;">
                <label style="display: block; font-weight: 700; color: var(--text-main); margin-bottom: 6px;">Conteúdo</label>
                <div id="editor"></div>
            </div>
            
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 16px;">
                <div>
                    <label style="display: block; font-weight: 700; color: var(--text-main); margin-bottom: 6px;">Tipo de Mídia</label>
                    <select name="media_type" onchange="toggleMediaInput(this.value)">
                        <option value="text">Apenas Texto</option>
                        <option value="video">Vídeo (YouTube)</option>
                        <option value="link">Link Externo</option>
                    </select>
                </div>
                <!-- URL Media Group -->
                 <div id="mediaUrlGroup" style="display: none;">
                    <label style="display: block; font-weight: 700; color: var(--text-main); margin-bottom: 6px;">URL da Mídia</label>
                    <input type="url" name="media_url" placeholder="https://...">
                </div>
            </div>
             <div style="margin-bottom: 24px; position: relative;">
                <label style="display: block; font-weight: 700; color: var(--text-main); margin-bottom: 8px;">Tags (Assuntos)</label>
                
                <div class="custom-dropdown" style="position: relative;">
                    <button type="button" onclick="toggleTagDropdown()" id="tagDropdownBtn" style="width: 100%; text-align: left; padding: 14px; background: var(--bg-body); border: 1px solid var(--border-color); border-radius: 12px; color: var(--text-muted); cursor: pointer; display: flex; justify-content: space-between; align-items: center;">
                        <span>Selecionar Tags...</span>
                        <i data-lucide="chevron-down" style="width: 16px;"></i>
                    </button>
                    
                    <div id="tagDropdownList" style="display: none; position: absolute; top: 100%; left: 0; width: 100%; background: var(--bg-surface); border: 1px solid var(--border-color); border-radius: 12px; max-height: 200px; overflow-y: auto; z-index: 10; padding: 8px; box-shadow: 0 10px 30px rgba(0,0,0,0.1); margin-top: 8px;">
                        <?php foreach ($allTags as $tag): ?>
                        <label style="display: flex; align-items: center; padding: 10px; cursor: pointer; border-radius: 8px; transition: background 0.2s;" onmouseover="this.style.background='var(--bg-body)'" onmouseout="this.style.background='transparent'">
                            <input type="checkbox" name="tags[]" value="<?= $tag['id'] ?>" style="margin-right: 12px; width: 18px; height: 18px; accent-color: var(--primary);" onchange="updateTagButton()">
                            <span style="color: var(--text-main); font-weight: 500; font-size: 0.95rem;"><?= htmlspecialchars($tag['name']) ?></span>
                        </label>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
            <div style="display: flex; gap: 12px;">
                <button type="button" onclick="closeModal()" style="flex: 1; padding: 14px; border-radius: 12px; border: 1px solid var(--border-color); background: var(--bg-surface); font-weight: 600; cursor: pointer; color: var(--text-muted);">Cancelar</button>
                <button type="submit" style="flex: 2; padding: 14px; border-radius: 12px; border: none; background: var(--primary); color: white; font-weight: 700; cursor: pointer;">Publicar</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal Create Prayer -->
<div id="prayerModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; z-index: 1000;">
    <div onclick="closePrayerModal()" style="position: absolute; width: 100%; height: 100%; background: rgba(0,0,0,0.5); backdrop-filter: blur(4px);"></div>
    <div style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); width: 90%; max-width: 500px; background: var(--bg-surface); border-radius: 24px; padding: 24px; box-shadow: 0 20px 60px rgba(0,0,0,0.2); max-height: 90vh; overflow-y: auto;">
        <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 24px; padding-bottom: 16px; border-bottom: 1px solid var(--border-color);">
            <div style="display: flex; align-items: center; gap: 12px;">
                <div style="width: 40px; height: 40px; border-radius: 12px; background: var(--primary-subtle); display: flex; align-items: center; justify-content: center;">
                    <span style="font-size: 1.5rem;">🙏</span>
                </div>
                <div>
                    <h2 style="margin: 0; font-size: 1.25rem; font-weight: 800; color: var(--text-main);">Novo Pedido</h2>
                    <p style="margin: 0; font-size: 0.85rem; color: var(--text-muted);">Compartilhe com a igreja</p>
                </div>
            </div>
            <button onclick="closePrayerModal()" style="background: var(--bg-body); border: none; color: var(--text-muted); cursor: pointer; width: 32px; height: 32px; border-radius: 50%; display: flex; align-items: center; justify-content: center; transition: all 0.2s;">
                <i data-lucide="x" style="width: 18px;"></i>
            </button>
        </div>
        <form method="POST">
            <input type="hidden" name="action" value="create_prayer">
            <div style="margin-bottom: 16px;">
                <label style="display: block; font-weight: 700; color: var(--text-main); margin-bottom: 6px; font-size: 0.95rem;">Título do Pedido</label>
                <input type="text" name="title" required placeholder="Ex: Oração pela saúde do meu pai">
            </div>
            <div style="margin-bottom: 16px;">
                <label style="display: block; font-weight: 700; color: var(--text-main); margin-bottom: 6px; font-size: 0.95rem;">Descrição (opcional)</label>
                <textarea name="description" rows="4" style="width: 100%; border-radius: 12px; border: 1px solid var(--border-color); padding: 12px; resize: none;" placeholder="Detalhes do pedido..."></textarea>
            </div>
            <div style="margin-bottom: 16px;">
                <label style="display: block; font-weight: 700; color: var(--text-main); margin-bottom: 8px; font-size: 0.95rem;">Categoria</label>
                <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 8px;">
                     <!-- Options (simplified for brevity) -->
                    <label class="cat-option" style="display: flex; flex-direction: column; align-items: center; gap: 4px; padding: 12px 8px; border-radius: 12px; cursor: pointer; border: 1px solid var(--border-color);">
                        <input type="radio" name="category" value="health" style="display: none;">
                        <span style="font-size: 1.5rem;">❤️</span>
                        <span style="font-size: 0.75rem; font-weight: 600;">Saúde</span>
                    </label>
                     <label class="cat-option" style="display: flex; flex-direction: column; align-items: center; gap: 4px; padding: 12px 8px; border-radius: 12px; cursor: pointer; border: 1px solid var(--border-color);">
                        <input type="radio" name="category" value="family" style="display: none;">
                        <span style="font-size: 1.5rem;">👨‍👩‍👧</span>
                        <span style="font-size: 0.75rem; font-weight: 600;">Família</span>
                    </label>
                    <label class="cat-option" style="display: flex; flex-direction: column; align-items: center; gap: 4px; padding: 12px 8px; border-radius: 12px; cursor: pointer; border: 1px solid var(--border-color);">
                        <input type="radio" name="category" value="other" checked style="display: none;">
                        <span style="font-size: 1.5rem;">🙏</span>
                        <span style="font-size: 0.75rem; font-weight: 600;">Outros</span>
                    </label>
                </div>
            </div>
            <div style="margin-bottom: 20px; display: flex; gap: 16px; flex-wrap: wrap;">
                <label style="display: flex; align-items: center; gap: 8px; cursor: pointer;">
                    <input type="checkbox" name="is_urgent" style="width: 18px; height: 18px; accent-color: var(--rose-600);">
                    <span style="font-size: 0.95rem; font-weight: 600; color: var(--text-main);">🔥 Urgente</span>
                </label>
                <label style="display: flex; align-items: center; gap: 8px; cursor: pointer;">
                    <input type="checkbox" name="is_anonymous" style="width: 18px; height: 18px; accent-color: var(--slate-500);">
                    <span style="font-size: 0.95rem; font-weight: 600; color: var(--text-main);">🔒 Anônimo</span>
                </label>
            </div>
            <div style="display: flex; gap: 12px;">
                <button type="button" onclick="closePrayerModal()" style="flex: 1; padding: 14px; border-radius: 12px; border: 1px solid var(--border-color); background: var(--bg-surface); font-weight: 600; cursor: pointer; color: var(--text-muted);">Cancelar</button>
                <button type="submit" style="flex: 2; padding: 14px; border-radius: 12px; border: none; background: var(--primary); color: white; font-weight: 700; cursor: pointer;">Enviar Pedido</button>
            </div>
        </form>
    </div>
</div>

<script src="https://cdn.quilljs.com/1.3.6/quill.js"></script>
<script>
    // Quill
    var quill;
    document.addEventListener('DOMContentLoaded', function() {
        quill = new Quill('#editor', {
            theme: 'snow',
            placeholder: 'Compartilhe sua reflexão... Use [verso Romanos 8:28] para citar Bíblia.',
            modules: { toolbar: [['bold', 'italic', 'underline'], [{ 'list': 'ordered'}, { 'list': 'bullet' }], ['link', 'clean']] }
        });
        
        // CHECK URL PARAMS FOR TAB
        const urlParams = new URLSearchParams(window.location.search);
        const tab = urlParams.get('tab');
        if (tab === 'prayer') {
            switchTab('prayer');
        }
    });

    function switchTab(tabName) {
        // Hide all
        document.querySelectorAll('.tab-content').forEach(el => el.classList.remove('active'));
        document.querySelectorAll('.tab-btn').forEach(el => el.classList.remove('active'));
        
        // Show target
        document.getElementById('tab-' + tabName).classList.add('active');
        document.getElementById('btn-tab-' + tabName).classList.add('active');
        
        // Update URL without reload
        const url = new URL(window.location);
        url.searchParams.set('tab', tabName);
        window.history.pushState({}, '', url);
    }

    // Modal Functions
    function openCreateModal() { document.getElementById('devotionalModal').style.display = 'block'; }
    function closeModal() { document.getElementById('devotionalModal').style.display = 'none'; }
    function openCreatePrayerModal() { document.getElementById('prayerModal').style.display = 'block'; }
    function closePrayerModal() { document.getElementById('prayerModal').style.display = 'none'; }
    
    // Form prep
    function prepareSubmit() {
        var content = document.querySelector('input[name=content]');
        content.value = quill.root.innerHTML;
        return true;
    }

    function toggleMediaInput(type) {
        const group = document.getElementById('mediaUrlGroup');
        group.style.display = (type === 'text') ? 'none' : 'block';
    }
    
    // Interactions
    function toggleDevotional(id, event) {
        if (event.target.closest('button') || event.target.closest('a') || event.target.closest('input') || event.target.closest('.comments-section')) return;
        document.getElementById('dev-' + id).classList.toggle('collapsed');
    }
    
    function toggleComments(id, event) {
        if(event) event.stopPropagation();
        document.getElementById(id).classList.toggle('open');
    }
    
    function shareDevotional(id, title, event) {
        event.stopPropagation();
        if (navigator.share) {
            navigator.share({ title: title, text: 'Devocional: ' + title, url: window.location.href.split('?')[0] + '?id=' + id });
        } else {
            alert('Copie o link!');
        }
    }
    
    function toggleAdvancedFilters() {
        const panel = document.getElementById('advanced-filters-panel');
        panel.style.display = (panel.style.display === 'none') ? 'block' : 'none';
    }
    
    // Prayer Interactions
    function toggleIntercessionStatus(prayerId, btn) {
        // Optimistic UI
        const icon = btn.querySelector('svg');
        const textSpan = btn.querySelector('span');
        const isActive = btn.classList.contains('active');
        
        btn.classList.toggle('active');
        if (isActive) {
           textSpan.innerText = 'Interceder';
           icon.style.fill = 'none';
        } else {
           textSpan.innerText = 'Intercedi';
           icon.style.fill = 'currentColor';
        }
        
        const form = document.createElement('form');
        form.method = 'POST';
        form.style.display = 'none';
        const inputAction = document.createElement('input');
        inputAction.name = 'action';
        inputAction.value = 'pray';
        const inputId = document.createElement('input');
        inputId.name = 'prayer_id';
        inputId.value = prayerId;
        form.appendChild(inputAction);
        form.appendChild(inputId);
        document.body.appendChild(form);
        form.submit();
    }
    
    // Tag Dropdown Logic
    function toggleTagDropdown() {
        const list = document.getElementById('tagDropdownList');
        const isOpen = list.style.display === 'block';
        list.style.display = isOpen ? 'none' : 'block';
    }

    function updateTagButton() {
        const checkboxes = document.querySelectorAll('#tagDropdownList input[name="tags[]"]:checked');
        const btnSpan = document.querySelector('#tagDropdownBtn span');
        
        if (checkboxes.length === 0) {
            btnSpan.innerText = 'Selecionar Tags...';
            btnSpan.style.color = 'var(--text-muted)';
        } else {
            btnSpan.innerText = checkboxes.length + ' tag(s) selecionada(s)';
            btnSpan.style.color = 'var(--text-main)';
        }
    }

    document.addEventListener('click', function(e) {
        const container = document.querySelector('.custom-dropdown');
        const list = document.getElementById('tagDropdownList');
        
        if (container && !container.contains(e.target) && list && list.style.display === 'block') {
            list.style.display = 'none';
        }
    });
    
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
        }, 800); // Aguarda 800ms após o usuário parar de digitar
    }
    
    // Atalho de teclado para busca (Ctrl+K ou Cmd+K)
    document.addEventListener('keydown', function(e) {
        if ((e.ctrlKey || e.metaKey) && e.key === 'k') {
            e.preventDefault();
            document.getElementById('smartSearch').focus();
        }
    });
</script>

<?php renderAppFooter(); ?>