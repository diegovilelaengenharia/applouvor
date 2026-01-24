<?php
// admin/devocionais.php
require_once '../includes/auth.php';
require_once '../includes/db.php';
require_once '../includes/layout.php';

checkLogin();

$userId = $_SESSION['user_id'] ?? 1;

// --- LÓGICA DE POST (CRUD) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'create':
                $stmt = $pdo->prepare("INSERT INTO devotionals (user_id, title, content, media_type, media_url, created_at) VALUES (?, ?, ?, ?, ?, NOW())");
                $stmt->execute([
                    $userId,
                    $_POST['title'],
                    $_POST['content'],
                    $_POST['media_type'],
                    !empty($_POST['media_url']) ? $_POST['media_url'] : NULL
                ]);
                $devotionalId = $pdo->lastInsertId();
                
                // Salvar tags
                if (!empty($_POST['tags'])) {
                    $tagStmt = $pdo->prepare("INSERT INTO devotional_tags (devotional_id, tag_id) VALUES (?, ?)");
                    foreach ($_POST['tags'] as $tagId) {
                        $tagStmt->execute([$devotionalId, $tagId]);
                    }
                }
                
                header('Location: devocionais.php?success=created');
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
                
                header('Location: devocionais.php?success=updated');
                exit;

            case 'delete':
                // Apenas o autor pode deletar (ou admin)
                $stmt = $pdo->prepare("DELETE FROM devotionals WHERE id = ? AND (user_id = ? OR ? = 'admin')");
                $stmt->execute([$_POST['id'], $userId, $_SESSION['user_role']]);
                header('Location: devocionais.php?success=deleted');
                exit;

            case 'comment':
                $stmt = $pdo->prepare("INSERT INTO devotional_comments (devotional_id, user_id, comment, created_at) VALUES (?, ?, ?, NOW())");
                $stmt->execute([
                    $_POST['devotional_id'],
                    $userId,
                    $_POST['comment']
                ]);
                header('Location: devocionais.php?success=commented#dev-' . $_POST['devotional_id']);
                exit;
                
            case 'delete_comment':
                $stmt = $pdo->prepare("DELETE FROM devotional_comments WHERE id = ? AND (user_id = ? OR ? = 'admin')");
                $stmt->execute([$_POST['comment_id'], $userId, $_SESSION['user_role']]);
                header('Location: devocionais.php?success=comment_deleted');
                exit;
        }
    }
}

// --- FILTROS ---
$filterTag = $_GET['tag'] ?? '';
$filterType = $_GET['type'] ?? 'all';
$search = $_GET['search'] ?? '';

// Buscar devocionais
$sql = "SELECT d.*, u.name as author_name, u.avatar as author_avatar,
        (SELECT COUNT(*) FROM devotional_comments WHERE devotional_id = d.id) as comment_count
        FROM devotionals d 
        LEFT JOIN users u ON d.user_id = u.id 
        WHERE 1=1";
$params = [];

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

$sql .= " ORDER BY d.created_at DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$devotionals = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Buscar tags existentes
$tagsStmt = $pdo->query("SELECT * FROM tags ORDER BY name");
$allTags = $tagsStmt->fetchAll(PDO::FETCH_ASSOC);

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

renderAppHeader('Devocionais');
?>

<!-- Quill CSS -->
<link href="https://cdn.quilljs.com/1.3.6/quill.snow.css" rel="stylesheet">

<style>
    /* Devocionais Cards */
    .devotional-card {
        background: var(--bg-surface);
        border-radius: 16px;
        border: 1px solid var(--border-color);
        overflow: hidden;
        box-shadow: var(--shadow-sm);
        transition: transform 0.2s, box-shadow 0.2s;
    }
    .devotional-card:hover {
        box-shadow: 0 8px 25px rgba(0,0,0,0.08);
    }
    
    /* Header do Card */
    .dev-header {
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 16px;
        border-bottom: 1px solid var(--border-color);
    }
    .dev-avatar {
        width: 44px;
        height: 44px;
        border-radius: 50%;
        object-fit: cover;
        border: 2px solid white;
        box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    }
    .dev-avatar-placeholder {
        width: 44px;
        height: 44px;
        border-radius: 50%;
        background: linear-gradient(135deg, #667eea, #764ba2);
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-weight: 700;
        font-size: 1rem;
    }
    .dev-author-info {
        flex: 1;
    }
    .dev-author-name {
        font-weight: 700;
        color: var(--text-main);
        font-size: 0.95rem;
    }
    .dev-meta {
        display: flex;
        align-items: center;
        gap: 8px;
        font-size: 0.8rem;
        color: var(--text-muted);
    }
    .dev-type-badge {
        display: inline-flex;
        align-items: center;
        gap: 4px;
        padding: 2px 8px;
        border-radius: 12px;
        font-size: 0.7rem;
        font-weight: 600;
    }
    
    /* Content */
    .dev-content {
        padding: 16px;
    }
    .dev-title {
        font-size: 1.1rem;
        font-weight: 700;
        color: var(--text-main);
        margin-bottom: 8px;
    }
    .dev-text {
        color: var(--text-body);
        font-size: 0.95rem;
        line-height: 1.6;
    }
    .dev-text p { margin: 0 0 8px; }
    
    /* Media Embed */
    .dev-media {
        margin: 12px 0;
        border-radius: 12px;
        overflow: hidden;
    }
    .dev-media iframe {
        width: 100%;
        aspect-ratio: 16/9;
        border: none;
    }
    .dev-link-preview {
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 12px;
        background: var(--bg-body);
        border-radius: 10px;
        text-decoration: none;
        color: var(--text-main);
        transition: background 0.2s;
    }
    .dev-link-preview:hover {
        background: var(--border-color);
    }
    
    /* Tags */
    .dev-tags {
        display: flex;
        flex-wrap: wrap;
        gap: 6px;
        padding: 0 16px 16px;
    }
    .dev-tag {
        font-size: 0.75rem;
        padding: 4px 10px;
        border-radius: 20px;
        font-weight: 600;
    }
    
    /* Footer/Comments */
    .dev-footer {
        padding: 12px 16px;
        background: #f8fafc;
        border-top: 1px solid var(--border-color);
    }
    .dev-actions {
        display: flex;
        align-items: center;
        gap: 16px;
    }
    .dev-action-btn {
        display: flex;
        align-items: center;
        gap: 6px;
        background: none;
        border: none;
        color: var(--text-muted);
        font-size: 0.85rem;
        font-weight: 600;
        cursor: pointer;
        padding: 6px 10px;
        border-radius: 8px;
        transition: all 0.2s;
    }
    .dev-action-btn:hover {
        background: var(--border-color);
        color: var(--text-main);
    }
    
    /* Comments Section */
    .comments-section {
        display: none;
        padding: 16px;
        background: #f8fafc;
        border-top: 1px solid var(--border-color);
    }
    .comments-section.open {
        display: block;
    }
    .comment-item {
        display: flex;
        gap: 10px;
        padding: 10px 0;
        border-bottom: 1px solid var(--border-color);
    }
    .comment-item:last-child {
        border-bottom: none;
    }
    .comment-avatar {
        width: 32px;
        height: 32px;
        border-radius: 50%;
        object-fit: cover;
    }
    .comment-content {
        flex: 1;
    }
    .comment-author {
        font-weight: 600;
        font-size: 0.85rem;
        color: var(--text-main);
    }
    .comment-text {
        font-size: 0.9rem;
        color: var(--text-body);
        margin: 2px 0;
    }
    .comment-time {
        font-size: 0.75rem;
        color: var(--text-muted);
    }
    .comment-form {
        display: flex;
        gap: 8px;
        margin-top: 12px;
    }
    .comment-input {
        flex: 1;
        padding: 10px 14px;
        border: 1px solid var(--border-color);
        border-radius: 24px;
        font-size: 0.9rem;
        outline: none;
        background: white;
    }
    .comment-submit {
        padding: 10px 16px;
        background: var(--primary);
        color: white;
        border: none;
        border-radius: 24px;
        font-weight: 600;
        cursor: pointer;
    }
    
    /* Type Badges */
    .type-text { background: #ecfdf5; color: #047857; }
    .type-video { background: #fef2f2; color: #dc2626; }
    .type-audio { background: #f5f3ff; color: #7c3aed; }
    .type-link { background: #eff6ff; color: #2563eb; }
    
    /* FAB */
    .fab-create {
        position: fixed;
        bottom: 90px;
        right: 20px;
        width: 56px;
        height: 56px;
        border-radius: 50%;
        background: linear-gradient(135deg, #667eea, #764ba2);
        color: white;
        border: none;
        box-shadow: 0 4px 20px rgba(102, 126, 234, 0.4);
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        z-index: 100;
        transition: transform 0.2s, box-shadow 0.2s;
    }
    .fab-create:hover {
        transform: scale(1.05);
        box-shadow: 0 6px 25px rgba(102, 126, 234, 0.5);
    }
    
    /* Filter Tabs */
    .filter-tabs {
        display: flex;
        gap: 4px;
        overflow-x: auto;
        padding-bottom: 8px;
        margin-bottom: 16px;
        scrollbar-width: none;
    }
    .filter-tab {
        padding: 8px 16px;
        border-radius: 20px;
        font-size: 0.85rem;
        font-weight: 600;
        color: var(--text-muted);
        text-decoration: none;
        white-space: nowrap;
        transition: all 0.2s;
        background: var(--bg-surface);
        border: 1px solid var(--border-color);
    }
    .filter-tab:hover {
        background: var(--border-color);
    }
    .filter-tab.active {
        background: var(--primary);
        color: white;
        border-color: var(--primary);
    }
</style>

<?php renderPageHeader('Devocionais', 'Louvor PIB Oliveira'); ?>

<div class="container" style="padding-top: 16px; max-width: 700px; margin: 0 auto;">
    
    <!-- Hero Section -->
    <div style="text-align: center; padding: 20px 0 30px;">
        <div style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); width: 70px; height: 70px; border-radius: 20px; display: flex; align-items: center; justify-content: center; margin: 0 auto 16px; box-shadow: 0 8px 25px rgba(102, 126, 234, 0.3);">
            <i data-lucide="book-heart" style="color: white; width: 36px; height: 36px;"></i>
        </div>
        <h2 style="font-size: 1.3rem; font-weight: 800; color: var(--text-main); margin: 0 0 6px;">Devocionais da Comunidade</h2>
        <p style="color: var(--text-muted); font-size: 0.9rem; max-width: 400px; margin: 0 auto;">
            Compartilhe reflexões, versículos e momentos com Deus. Edifique e seja edificado!
        </p>
    </div>
    
    <!-- Filtros por Tipo -->
    <div class="filter-tabs">
        <a href="?type=all" class="filter-tab <?= $filterType === 'all' ? 'active' : '' ?>">✨ Todos</a>
        <a href="?type=text" class="filter-tab <?= $filterType === 'text' ? 'active' : '' ?>">📝 Textos</a>
        <a href="?type=video" class="filter-tab <?= $filterType === 'video' ? 'active' : '' ?>">🎬 Vídeos</a>
        <a href="?type=audio" class="filter-tab <?= $filterType === 'audio' ? 'active' : '' ?>">🎵 Áudios</a>
        <a href="?type=link" class="filter-tab <?= $filterType === 'link' ? 'active' : '' ?>">🔗 Links</a>
    </div>
    
    <!-- Feed de Devocionais -->
    <div style="display: flex; flex-direction: column; gap: 20px;">
        <?php if (count($devotionals) > 0): ?>
            <?php foreach ($devotionals as $dev): 
                $devTags = getDevotionalTags($pdo, $dev['id']);
                $comments = getDevotionalComments($pdo, $dev['id']);
                
                // Tempo relativo
                $createdAt = new DateTime($dev['created_at']);
                $now = new DateTime();
                $diff = $now->diff($createdAt);
                
                if ($diff->y > 0) $timeAgo = $diff->y . ' ano(s)';
                elseif ($diff->m > 0) $timeAgo = $diff->m . ' mês(es)';
                elseif ($diff->d > 0) $timeAgo = $diff->d . ' dia(s)';
                elseif ($diff->h > 0) $timeAgo = $diff->h . 'h';
                elseif ($diff->i > 0) $timeAgo = $diff->i . 'min';
                else $timeAgo = 'agora';
                
                // Avatar
                $authorAvatar = !empty($dev['author_avatar']) ? $dev['author_avatar'] : null;
                if ($authorAvatar && strpos($authorAvatar, 'http') === false) {
                    $authorAvatar = '../assets/uploads/' . $authorAvatar;
                }
                
                // Type config
                $typeConfig = [
                    'text' => ['icon' => '📝', 'label' => 'Texto', 'class' => 'type-text'],
                    'video' => ['icon' => '🎬', 'label' => 'Vídeo', 'class' => 'type-video'],
                    'audio' => ['icon' => '🎵', 'label' => 'Áudio', 'class' => 'type-audio'],
                    'link' => ['icon' => '🔗', 'label' => 'Link', 'class' => 'type-link']
                ];
                $tc = $typeConfig[$dev['media_type']] ?? $typeConfig['text'];
            ?>
            <div class="devotional-card animate-in" id="dev-<?= $dev['id'] ?>">
                <!-- Header -->
                <div class="dev-header">
                    <?php if ($authorAvatar): ?>
                        <img src="<?= htmlspecialchars($authorAvatar) ?>" class="dev-avatar" alt="Avatar">
                    <?php else: ?>
                        <div class="dev-avatar-placeholder"><?= strtoupper(substr($dev['author_name'] ?? 'U', 0, 1)) ?></div>
                    <?php endif; ?>
                    
                    <div class="dev-author-info">
                        <div class="dev-author-name"><?= htmlspecialchars($dev['author_name'] ?? 'Anônimo') ?></div>
                        <div class="dev-meta">
                            <span class="dev-type-badge <?= $tc['class'] ?>"><?= $tc['icon'] ?> <?= $tc['label'] ?></span>
                            <span>• <?= $timeAgo ?></span>
                        </div>
                    </div>
                    
                    <?php if ($dev['user_id'] == $userId || $_SESSION['user_role'] === 'admin'): ?>
                    <div style="position: relative;">
                        <button onclick="toggleDevMenu('menu-<?= $dev['id'] ?>')" style="background: none; border: none; padding: 8px; cursor: pointer; color: var(--text-muted); border-radius: 50%;">
                            <i data-lucide="more-vertical" style="width: 18px;"></i>
                        </button>
                        <div id="menu-<?= $dev['id'] ?>" class="dev-dropdown" style="display: none; position: absolute; right: 0; top: 100%; background: white; border-radius: 10px; box-shadow: 0 4px 15px rgba(0,0,0,0.1); border: 1px solid var(--border-color); min-width: 140px; z-index: 50;">
                            <a href="#" onclick="openEditModal(<?= htmlspecialchars(json_encode($dev)) ?>)" style="display: flex; align-items: center; gap: 8px; padding: 10px 14px; color: var(--text-main); text-decoration: none; font-size: 0.9rem;">
                                <i data-lucide="edit-2" style="width: 16px;"></i> Editar
                            </a>
                            <form method="POST" style="margin: 0;" onsubmit="return confirm('Tem certeza que deseja excluir?')">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="id" value="<?= $dev['id'] ?>">
                                <button type="submit" style="width: 100%; display: flex; align-items: center; gap: 8px; padding: 10px 14px; color: #dc2626; background: none; border: none; border-top: 1px solid var(--border-color); font-size: 0.9rem; cursor: pointer; text-align: left;">
                                    <i data-lucide="trash-2" style="width: 16px;"></i> Excluir
                                </button>
                            </form>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
                
                <!-- Content -->
                <div class="dev-content">
                    <h3 class="dev-title"><?= htmlspecialchars($dev['title']) ?></h3>
                    
                    <?php if ($dev['media_type'] === 'video' && !empty($dev['media_url'])): ?>
                        <div class="dev-media">
                            <?php
                            $videoUrl = $dev['media_url'];
                            // YouTube
                            if (preg_match('/(?:youtube\.com\/watch\?v=|youtu\.be\/)([a-zA-Z0-9_-]+)/', $videoUrl, $matches)) {
                                $videoId = $matches[1];
                                echo '<iframe src="https://www.youtube.com/embed/' . $videoId . '" allowfullscreen></iframe>';
                            } else {
                                echo '<a href="' . htmlspecialchars($videoUrl) . '" target="_blank" class="dev-link-preview">
                                    <i data-lucide="play-circle" style="width: 24px; color: #dc2626;"></i>
                                    <span>Assistir vídeo</span>
                                </a>';
                            }
                            ?>
                        </div>
                    <?php endif; ?>
                    
                    <?php if ($dev['media_type'] === 'audio' && !empty($dev['media_url'])): ?>
                        <div class="dev-media">
                            <?php
                            $audioUrl = $dev['media_url'];
                            // Spotify
                            if (strpos($audioUrl, 'spotify.com') !== false) {
                                $embedUrl = str_replace('/track/', '/embed/track/', $audioUrl);
                                echo '<iframe src="' . htmlspecialchars($embedUrl) . '" height="80" frameborder="0" allow="encrypted-media" style="border-radius: 12px; width: 100%;"></iframe>';
                            } else {
                                echo '<audio controls style="width: 100%;">
                                    <source src="' . htmlspecialchars($audioUrl) . '">
                                </audio>';
                            }
                            ?>
                        </div>
                    <?php endif; ?>
                    
                    <?php if ($dev['media_type'] === 'link' && !empty($dev['media_url'])): ?>
                        <a href="<?= htmlspecialchars($dev['media_url']) ?>" target="_blank" class="dev-link-preview">
                            <i data-lucide="external-link" style="width: 20px; color: #2563eb;"></i>
                            <span style="flex: 1; overflow: hidden; text-overflow: ellipsis;"><?= htmlspecialchars($dev['media_url']) ?></span>
                            <i data-lucide="chevron-right" style="width: 16px; color: var(--text-muted);"></i>
                        </a>
                    <?php endif; ?>
                    
                    <?php if (!empty($dev['content'])): ?>
                        <div class="dev-text"><?= $dev['content'] ?></div>
                    <?php endif; ?>
                </div>
                
                <!-- Tags -->
                <?php if (!empty($devTags)): ?>
                <div class="dev-tags">
                    <?php foreach ($devTags as $tag): ?>
                        <a href="?tag=<?= $tag['id'] ?>" class="dev-tag" style="background: <?= $tag['color'] ?>20; color: <?= $tag['color'] ?>;">
                            🏷️ <?= htmlspecialchars($tag['name']) ?>
                        </a>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
                
                <!-- Footer Actions -->
                <div class="dev-footer">
                    <div class="dev-actions">
                        <button class="dev-action-btn" onclick="toggleComments('comments-<?= $dev['id'] ?>')">
                            <i data-lucide="message-circle" style="width: 18px;"></i>
                            <span><?= count($comments) ?> comentário(s)</span>
                        </button>
                    </div>
                </div>
                
                <!-- Comments Section -->
                <div id="comments-<?= $dev['id'] ?>" class="comments-section">
                    <?php foreach ($comments as $comment): 
                        $commentAvatar = !empty($comment['author_avatar']) ? $comment['author_avatar'] : null;
                        if ($commentAvatar && strpos($commentAvatar, 'http') === false) {
                            $commentAvatar = '../assets/uploads/' . $commentAvatar;
                        }
                        
                        $commentTime = new DateTime($comment['created_at']);
                        $commentDiff = $now->diff($commentTime);
                        if ($commentDiff->d > 0) $commentTimeAgo = $commentDiff->d . 'd';
                        elseif ($commentDiff->h > 0) $commentTimeAgo = $commentDiff->h . 'h';
                        else $commentTimeAgo = $commentDiff->i . 'min';
                    ?>
                    <div class="comment-item">
                        <?php if ($commentAvatar): ?>
                            <img src="<?= htmlspecialchars($commentAvatar) ?>" class="comment-avatar" alt="">
                        <?php else: ?>
                            <div class="comment-avatar" style="background: linear-gradient(135deg, #a8edea, #fed6e3); display: flex; align-items: center; justify-content: center; font-size: 0.7rem; font-weight: 700; color: #666;">
                                <?= strtoupper(substr($comment['author_name'] ?? 'U', 0, 1)) ?>
                            </div>
                        <?php endif; ?>
                        <div class="comment-content">
                            <span class="comment-author"><?= htmlspecialchars($comment['author_name'] ?? 'Anônimo') ?></span>
                            <span class="comment-time">• <?= $commentTimeAgo ?></span>
                            <p class="comment-text"><?= nl2br(htmlspecialchars($comment['comment'])) ?></p>
                        </div>
                    </div>
                    <?php endforeach; ?>
                    
                    <!-- Comment Form -->
                    <form method="POST" class="comment-form">
                        <input type="hidden" name="action" value="comment">
                        <input type="hidden" name="devotional_id" value="<?= $dev['id'] ?>">
                        <input type="text" name="comment" class="comment-input" placeholder="Escreva um comentário..." required>
                        <button type="submit" class="comment-submit">
                            <i data-lucide="send" style="width: 16px;"></i>
                        </button>
                    </form>
                </div>
            </div>
            <?php endforeach; ?>
        <?php else: ?>
            <!-- Empty State -->
            <div style="text-align: center; padding: 60px 20px;">
                <div style="background: linear-gradient(135deg, #f3e7e9 0%, #e3eeff 100%); width: 80px; height: 80px; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 20px;">
                    <i data-lucide="book-open" style="color: #667eea; width: 40px; height: 40px;"></i>
                </div>
                <h3 style="color: var(--text-main); margin-bottom: 8px;">Nenhum devocional ainda</h3>
                <p style="color: var(--text-muted); font-size: 0.9rem; max-width: 300px; margin: 0 auto 20px;">
                    Seja o primeiro a compartilhar uma reflexão com a comunidade!
                </p>
                <button onclick="openCreateModal()" style="background: linear-gradient(135deg, #667eea, #764ba2); color: white; border: none; padding: 12px 24px; border-radius: 24px; font-weight: 600; cursor: pointer;">
                    <i data-lucide="plus" style="width: 18px; display: inline-block; vertical-align: middle; margin-right: 6px;"></i>
                    Criar Devocional
                </button>
            </div>
        <?php endif; ?>
    </div>
    
    <div style="height: 100px;"></div>
</div>

<!-- FAB Button -->
<button onclick="openCreateModal()" class="fab-create">
    <i data-lucide="plus" style="width: 28px; height: 28px;"></i>
</button>

<!-- Modal Create/Edit -->
<div id="devotionalModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; z-index: 1000;">
    <div onclick="closeModal()" style="position: absolute; width: 100%; height: 100%; background: rgba(0,0,0,0.5); backdrop-filter: blur(4px);"></div>
    
    <div style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); width: 90%; max-width: 550px; background: var(--bg-surface); border-radius: 24px; padding: 24px; box-shadow: 0 20px 60px rgba(0,0,0,0.2); max-height: 90vh; overflow-y: auto;">
        
        <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 20px;">
            <h2 id="modalTitle" style="margin: 0; font-size: 1.2rem; font-weight: 800; color: var(--text-main);">✨ Nova Devocional</h2>
            <button onclick="closeModal()" style="background: none; border: none; color: var(--text-muted); cursor: pointer; padding: 8px;">
                <i data-lucide="x" style="width: 20px;"></i>
            </button>
        </div>
        
        <form method="POST" id="devotionalForm" onsubmit="return prepareSubmit()">
            <input type="hidden" name="action" id="formAction" value="create">
            <input type="hidden" name="id" id="devId">
            <input type="hidden" name="content" id="hiddenContent">
            
            <!-- Título -->
            <div style="margin-bottom: 16px;">
                <label style="display: block; font-weight: 700; color: var(--text-main); margin-bottom: 6px; font-size: 0.9rem;">Título</label>
                <input type="text" name="title" id="devTitle" required placeholder="Ex: A paz que excede o entendimento" style="width: 100%; padding: 12px 14px; border: 1px solid var(--border-color); border-radius: 12px; font-size: 0.95rem; outline: none; background: var(--bg-body);">
            </div>
            
            <!-- Tipo de Mídia -->
            <div style="margin-bottom: 16px;">
                <label style="display: block; font-weight: 700; color: var(--text-main); margin-bottom: 8px; font-size: 0.9rem;">Tipo de Conteúdo</label>
                <div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 8px;">
                    <label class="type-option" style="display: flex; flex-direction: column; align-items: center; gap: 6px; padding: 12px 8px; border: 2px solid var(--border-color); border-radius: 12px; cursor: pointer; transition: all 0.2s;">
                        <input type="radio" name="media_type" value="text" checked style="display: none;">
                        <span style="font-size: 1.5rem;">📝</span>
                        <span style="font-size: 0.75rem; font-weight: 600;">Texto</span>
                    </label>
                    <label class="type-option" style="display: flex; flex-direction: column; align-items: center; gap: 6px; padding: 12px 8px; border: 2px solid var(--border-color); border-radius: 12px; cursor: pointer; transition: all 0.2s;">
                        <input type="radio" name="media_type" value="video" style="display: none;">
                        <span style="font-size: 1.5rem;">🎬</span>
                        <span style="font-size: 0.75rem; font-weight: 600;">Vídeo</span>
                    </label>
                    <label class="type-option" style="display: flex; flex-direction: column; align-items: center; gap: 6px; padding: 12px 8px; border: 2px solid var(--border-color); border-radius: 12px; cursor: pointer; transition: all 0.2s;">
                        <input type="radio" name="media_type" value="audio" style="display: none;">
                        <span style="font-size: 1.5rem;">🎵</span>
                        <span style="font-size: 0.75rem; font-weight: 600;">Áudio</span>
                    </label>
                    <label class="type-option" style="display: flex; flex-direction: column; align-items: center; gap: 6px; padding: 12px 8px; border: 2px solid var(--border-color); border-radius: 12px; cursor: pointer; transition: all 0.2s;">
                        <input type="radio" name="media_type" value="link" style="display: none;">
                        <span style="font-size: 1.5rem;">🔗</span>
                        <span style="font-size: 0.75rem; font-weight: 600;">Link</span>
                    </label>
                </div>
            </div>
            
            <!-- URL de Mídia (condicional) -->
            <div id="mediaUrlField" style="margin-bottom: 16px; display: none;">
                <label style="display: block; font-weight: 700; color: var(--text-main); margin-bottom: 6px; font-size: 0.9rem;">
                    <span id="mediaUrlLabel">URL do Vídeo</span>
                </label>
                <input type="url" name="media_url" id="devMediaUrl" placeholder="https://youtube.com/watch?v=..." style="width: 100%; padding: 12px 14px; border: 1px solid var(--border-color); border-radius: 12px; font-size: 0.95rem; outline: none; background: var(--bg-body);">
                <p id="mediaUrlHint" style="margin: 6px 0 0; font-size: 0.8rem; color: var(--text-muted);">Cole o link do YouTube, Vimeo, etc.</p>
            </div>
            
            <!-- Editor de Texto -->
            <div style="margin-bottom: 16px;">
                <label style="display: block; font-weight: 700; color: var(--text-main); margin-bottom: 6px; font-size: 0.9rem;">Conteúdo</label>
                <div id="editor" style="height: 150px; background: white; border-radius: 12px;"></div>
            </div>
            
            <!-- Tags -->
            <div style="margin-bottom: 20px;">
                <label style="display: block; font-weight: 700; color: var(--text-main); margin-bottom: 8px; font-size: 0.9rem;">Tags (opcional)</label>
                <div style="display: flex; flex-wrap: wrap; gap: 8px;">
                    <?php foreach ($allTags as $tag): ?>
                    <label style="display: inline-flex; align-items: center; gap: 6px; padding: 6px 12px; border-radius: 20px; cursor: pointer; transition: all 0.2s; background: <?= $tag['color'] ?>15; border: 1px solid <?= $tag['color'] ?>40;">
                        <input type="checkbox" name="tags[]" value="<?= $tag['id'] ?>" style="display: none;" class="tag-checkbox">
                        <span style="color: <?= $tag['color'] ?>; font-size: 0.85rem; font-weight: 600;"><?= htmlspecialchars($tag['name']) ?></span>
                    </label>
                    <?php endforeach; ?>
                </div>
                <?php if (empty($allTags)): ?>
                <p style="color: var(--text-muted); font-size: 0.85rem;">Nenhuma tag disponível. Crie tags no Repertório primeiro.</p>
                <?php endif; ?>
            </div>
            
            <!-- Buttons -->
            <div style="display: flex; gap: 12px;">
                <button type="button" onclick="closeModal()" style="flex: 1; padding: 14px; border-radius: 12px; border: 1px solid var(--border-color); background: var(--bg-surface); font-weight: 600; cursor: pointer; color: var(--text-muted);">
                    Cancelar
                </button>
                <button type="submit" style="flex: 2; padding: 14px; border-radius: 12px; border: none; background: linear-gradient(135deg, #667eea, #764ba2); color: white; font-weight: 700; cursor: pointer;">
                    Publicar
                </button>
            </div>
        </form>
    </div>
</div>

<script src="https://cdn.quilljs.com/1.3.6/quill.js"></script>
<script>
    // Quill Editor
    var quill = new Quill('#editor', {
        theme: 'snow',
        placeholder: 'Escreva sua reflexão, versículo ou pensamento...',
        modules: {
            toolbar: [
                ['bold', 'italic', 'underline'],
                [{ 'list': 'ordered' }, { 'list': 'bullet' }],
                ['link', 'blockquote']
            ]
        }
    });
    
    // Type Selection
    document.querySelectorAll('.type-option input').forEach(input => {
        input.addEventListener('change', function() {
            // Update visual selection
            document.querySelectorAll('.type-option').forEach(opt => {
                opt.style.borderColor = 'var(--border-color)';
                opt.style.background = 'transparent';
            });
            this.parentElement.style.borderColor = '#667eea';
            this.parentElement.style.background = '#667eea10';
            
            // Show/hide media URL field
            const mediaField = document.getElementById('mediaUrlField');
            const labelEl = document.getElementById('mediaUrlLabel');
            const hintEl = document.getElementById('mediaUrlHint');
            
            if (this.value === 'video') {
                mediaField.style.display = 'block';
                labelEl.textContent = 'URL do Vídeo';
                hintEl.textContent = 'Cole o link do YouTube, Vimeo, etc.';
            } else if (this.value === 'audio') {
                mediaField.style.display = 'block';
                labelEl.textContent = 'URL do Áudio';
                hintEl.textContent = 'Cole o link do Spotify, SoundCloud, ou arquivo MP3.';
            } else if (this.value === 'link') {
                mediaField.style.display = 'block';
                labelEl.textContent = 'URL do Link';
                hintEl.textContent = 'Cole qualquer link externo.';
            } else {
                mediaField.style.display = 'none';
            }
        });
    });
    
    // Initialize first selection
    document.querySelector('.type-option input:checked').dispatchEvent(new Event('change'));
    
    // Tag selection visual
    document.querySelectorAll('.tag-checkbox').forEach(cb => {
        cb.addEventListener('change', function() {
            if (this.checked) {
                this.parentElement.style.boxShadow = '0 0 0 2px ' + this.parentElement.style.borderColor;
            } else {
                this.parentElement.style.boxShadow = 'none';
            }
        });
    });
    
    function prepareSubmit() {
        document.getElementById('hiddenContent').value = quill.root.innerHTML;
        return true;
    }
    
    function openCreateModal() {
        document.getElementById('modalTitle').innerText = '✨ Nova Devocional';
        document.getElementById('formAction').value = 'create';
        document.getElementById('devotionalForm').reset();
        document.getElementById('devId').value = '';
        document.getElementById('devMediaUrl').value = '';
        quill.setContents([]);
        
        // Reset type selection
        document.querySelector('.type-option input[value="text"]').checked = true;
        document.querySelector('.type-option input[value="text"]').dispatchEvent(new Event('change'));
        
        // Reset tags
        document.querySelectorAll('.tag-checkbox').forEach(cb => {
            cb.checked = false;
            cb.parentElement.style.boxShadow = 'none';
        });
        
        document.getElementById('devotionalModal').style.display = 'block';
    }
    
    function openEditModal(dev) {
        document.getElementById('modalTitle').innerText = '✏️ Editar Devocional';
        document.getElementById('formAction').value = 'update';
        document.getElementById('devId').value = dev.id;
        document.getElementById('devTitle').value = dev.title;
        document.getElementById('devMediaUrl').value = dev.media_url || '';
        
        // Set type
        const typeRadio = document.querySelector(`.type-option input[value="${dev.media_type}"]`);
        if (typeRadio) {
            typeRadio.checked = true;
            typeRadio.dispatchEvent(new Event('change'));
        }
        
        // Set content
        quill.root.innerHTML = dev.content || '';
        
        closeAllMenus();
        document.getElementById('devotionalModal').style.display = 'block';
    }
    
    function closeModal() {
        document.getElementById('devotionalModal').style.display = 'none';
    }
    
    // Comments toggle
    function toggleComments(id) {
        const section = document.getElementById(id);
        section.classList.toggle('open');
    }
    
    // Dropdown menus
    function toggleDevMenu(id) {
        closeAllMenus();
        const menu = document.getElementById(id);
        menu.style.display = menu.style.display === 'block' ? 'none' : 'block';
    }
    
    function closeAllMenus() {
        document.querySelectorAll('.dev-dropdown').forEach(m => m.style.display = 'none');
    }
    
    window.onclick = function(e) {
        if (!e.target.closest('.dev-dropdown') && !e.target.closest('[onclick*="toggleDevMenu"]')) {
            closeAllMenus();
        }
    }
</script>

<?php renderAppFooter(); ?>