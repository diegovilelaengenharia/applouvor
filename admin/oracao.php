<?php
// admin/oracao.php - Redesign Premium com funcionalidade completa
require_once '../includes/auth.php';
require_once '../includes/db.php';
require_once '../includes/layout.php';

checkLogin();

$userId = $_SESSION['user_id'] ?? 1;
$userName = $_SESSION['user_name'] ?? 'Usuário';

// Verificar se tabelas existem
$tableExists = true;
try {
    $pdo->query("SELECT 1 FROM prayer_requests LIMIT 1");
} catch (PDOException $e) {
    $tableExists = false;
}

// --- LÓGICA DE POST (CRUD) ---
if ($tableExists && $_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        try {
            switch ($_POST['action']) {
                case 'create':
                    $stmt = $pdo->prepare("INSERT INTO prayer_requests (user_id, title, description, category, is_urgent, is_anonymous, created_at) VALUES (?, ?, ?, ?, ?, ?, NOW())");
                    $stmt->execute([
                        $userId,
                        $_POST['title'],
                        $_POST['description'],
                        $_POST['category'],
                        isset($_POST['is_urgent']) ? 1 : 0,
                        isset($_POST['is_anonymous']) ? 1 : 0
                    ]);
                    header('Location: oracao.php?success=created');
                    exit;

                case 'pray':
                    $check = $pdo->prepare("SELECT id FROM prayer_interactions WHERE prayer_id = ? AND user_id = ? AND type = 'pray'");
                    $check->execute([$_POST['prayer_id'], $userId]);
                    if (!$check->fetch()) {
                        $stmt = $pdo->prepare("INSERT INTO prayer_interactions (prayer_id, user_id, type, created_at) VALUES (?, ?, 'pray', NOW())");
                        $stmt->execute([$_POST['prayer_id'], $userId]);
                        $pdo->prepare("UPDATE prayer_requests SET prayer_count = prayer_count + 1 WHERE id = ?")->execute([$_POST['prayer_id']]);
                    }
                    header('Location: oracao.php#prayer-' . $_POST['prayer_id']);
                    exit;

                case 'comment':
                    $stmt = $pdo->prepare("INSERT INTO prayer_interactions (prayer_id, user_id, type, comment, created_at) VALUES (?, ?, 'comment', ?, NOW())");
                    $stmt->execute([$_POST['prayer_id'], $userId, $_POST['comment']]);
                    header('Location: oracao.php#prayer-' . $_POST['prayer_id']);
                    exit;

                case 'answered':
                    $stmt = $pdo->prepare("UPDATE prayer_requests SET is_answered = 1, answered_at = NOW() WHERE id = ? AND user_id = ?");
                    $stmt->execute([$_POST['prayer_id'], $userId]);
                    header('Location: oracao.php?success=answered');
                    exit;

                case 'delete':
                    $stmt = $pdo->prepare("DELETE FROM prayer_requests WHERE id = ? AND user_id = ?");
                    $stmt->execute([$_POST['prayer_id'], $userId]);
                    header('Location: oracao.php?success=deleted');
                    exit;
            }
        } catch (PDOException $e) {
            // Silently fail
        }
    }
}

// --- FILTROS ---
$filterCategory = $_GET['category'] ?? 'all';
$filterIntercession = $_GET['intercession'] ?? 'all'; // all, interceded, not_interceded
$showAnswered = isset($_GET['answered']) && $_GET['answered'] === '1';

// Buscar pedidos
$prayers = [];
if ($tableExists) {
    try {
        $sql = "SELECT p.*, u.name as author_name, u.avatar as author_avatar,
                (SELECT COUNT(*) FROM prayer_interactions WHERE prayer_id = p.id AND type = 'pray') as pray_count,
                (SELECT COUNT(*) FROM prayer_interactions WHERE prayer_id = p.id AND type = 'comment') as comment_count,
                IF(pi_user.id IS NOT NULL, 1, 0) as is_interceded
                FROM prayer_requests p 
                LEFT JOIN users u ON p.user_id = u.id
                LEFT JOIN prayer_interactions pi_user ON p.id = pi_user.prayer_id 
                                                       AND pi_user.user_id = ?
                                                       AND pi_user.type = 'pray'
                WHERE 1=1";
        $params = [$userId];

        if ($showAnswered) {
            $sql .= " AND p.is_answered = 1";
        } else {
            $sql .= " AND p.is_answered = 0";
        }

        if ($filterCategory !== 'all') {
            $sql .= " AND p.category = ?";
            $params[] = $filterCategory;
        }
        
        // Filtro de Intercessão
        if ($filterIntercession === 'interceded') {
            $sql .= " AND pi_user.id IS NOT NULL";
        } elseif ($filterIntercession === 'not_interceded') {
            $sql .= " AND pi_user.id IS NULL";
        }

        // Ordenação: Não intercedidos primeiro, depois urgentes, depois mais recentes
        $sql .= " ORDER BY is_interceded ASC, p.is_urgent DESC, p.created_at DESC";

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $prayers = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        $prayers = [];
    }
}

// Verificar se usuário já orou por cada pedido
function userPrayed($pdo, $prayerId, $userId) {
    $stmt = $pdo->prepare("SELECT id FROM prayer_interactions WHERE prayer_id = ? AND user_id = ? AND type = 'pray'");
    $stmt->execute([$prayerId, $userId]);
    return $stmt->fetch() ? true : false;
}

// Buscar comentários
function getComments($pdo, $prayerId) {
    $stmt = $pdo->prepare("SELECT pi.*, u.name, u.avatar FROM prayer_interactions pi 
                           LEFT JOIN users u ON pi.user_id = u.id 
                           WHERE pi.prayer_id = ? AND pi.type = 'comment' 
                           ORDER BY pi.created_at ASC");
    $stmt->execute([$prayerId]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

?>

<link href="https://cdn.quilljs.com/1.3.6/quill.snow.css" rel="stylesheet">
<link href="../assets/css/pages/oracao.css?v=<?= time() ?>" rel="stylesheet">
<?php renderPageHeader('Mural de Oração', 'Louvor PIB Oliveira'); ?>

<div class="container" style="padding-top: 10px; max-width: 700px; margin: 0 auto;">
    
    <?php if (!$tableExists): ?>
    <!-- Setup Required Message -->
    <div style="text-align: center; padding: 40px 20px; background: var(--slate-100); border-radius: 16px; margin-bottom: 20px;">
        <div style="background: white; width: 60px; height: 60px; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 16px; box-shadow: 0 4px 12px rgba(0,0,0,0.1);">
            <i data-lucide="database" style="color: var(--slate-600); width: 30px; height: 30px;"></i>
        </div>
        <h3 style="color: #1e3a8a; margin-bottom: 8px; font-weight: 700;">Configuração Necessária</h3>
        <p style="color: var(--slate-700); font-size: var(--font-body); max-width: 350px; margin: 0 auto 20px;">
            Para usar o Mural de Oração, é necessário criar as tabelas no banco de dados.
        </p>
        <a href="../setup_prayers.php" style="display: inline-flex; align-items: center; gap: 8px; background: var(--slate-600); color: white; padding: 12px 24px; border-radius: 24px; font-weight: 600; text-decoration: none; box-shadow: 0 4px 12px rgba(55, 106, 200, 0.3);">
            <i data-lucide="settings" style="width: 18px;"></i>
            Executar Setup
        </a>
    </div>
    <?php else: ?>
    
    <!-- Toolbar de Filtros Compacta -->
    <div class="filter-toolbar">
        <!-- Filtro Intercessão -->
        <div class="filter-group">
            <i class="filter-icon" data-lucide="heart" style="width: 16px; height: 16px;"></i>
            <select onchange="window.location.href='?intercession='+this.value+'&category=<?= $filterCategory ?><?= $showAnswered ? '&answered=1' : '' ?>'" class="filter-select">
                <option value="all" <?= $filterIntercession === 'all' ? 'selected' : '' ?>>Todos</option>
                <option value="not_interceded" <?= $filterIntercession === 'not_interceded' ? 'selected' : '' ?>>Não Intercedidos</option>
                <option value="interceded" <?= $filterIntercession === 'interceded' ? 'selected' : '' ?>>Intercedidos</option>
            </select>
        </div>
        
        <!-- Botão Filtros Avançados -->
        <button onclick="toggleAdvancedFilters()" class="btn-advanced-filter <?= ($filterCategory !== 'all' || $showAnswered) ? 'active' : '' ?>" title="Filtros Avançados">
            <i data-lucide="sliders-horizontal" style="width: 20px; height: 20px;"></i>
            <?php if ($filterCategory !== 'all' || $showAnswered): ?>
                <span style="position: absolute; top: 10px; right: 10px; width: 8px; height: 8px; background: var(--yellow-500); border-radius: 50%;"></span>
            <?php endif; ?>
        </button>
        
        <!-- Botão Limpar Filtros -->
        <?php if ($filterCategory !== 'all' || $showAnswered): ?>
        <button onclick="window.location.href='oracao.php'" class="btn-advanced-filter" style="color: var(--rose-500); border-color: var(--rose-200); background: var(--rose-50);" title="Limpar Filtros">
            <i data-lucide="x" style="width: 20px; height: 20px;"></i>
        </button>
        <?php endif; ?>
    </div>
    
    <!-- Painel de Filtros Avançados -->
    <div id="advanced-filters-panel" style="display: none; background: var(--bg-surface); border: 1px solid var(--border-color); border-radius: 14px; padding: 16px; margin-bottom: 16px;">
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(140px, 1fr)); gap: 8px; margin-bottom: 12px;">
            <a href="?intercession=<?= $filterIntercession ?>&category=all<?= $showAnswered ? '&answered=1' : '' ?>" class="filter-tab <?= $filterCategory === 'all' ? 'active' : '' ?>">🙏 Todos</a>
            <a href="?intercession=<?= $filterIntercession ?>&category=health<?= $showAnswered ? '&answered=1' : '' ?>" class="filter-tab <?= $filterCategory === 'health' ? 'active' : '' ?>">❤️ Saúde</a>
            <a href="?intercession=<?= $filterIntercession ?>&category=family<?= $showAnswered ? '&answered=1' : '' ?>" class="filter-tab <?= $filterCategory === 'family' ? 'active' : '' ?>">👨‍👩‍👧 Família</a>
            <a href="?intercession=<?= $filterIntercession ?>&category=work<?= $showAnswered ? '&answered=1' : '' ?>" class="filter-tab <?= $filterCategory === 'work' ? 'active' : '' ?>">💼 Trabalho</a>
            <a href="?intercession=<?= $filterIntercession ?>&category=spiritual<?= $showAnswered ? '&answered=1' : '' ?>" class="filter-tab <?= $filterCategory === 'spiritual' ? 'active' : '' ?>">✨ Espiritual</a>
            <a href="?intercession=<?= $filterIntercession ?>&category=gratitude<?= $filterCategory === 'gratitude' ? 'active' : '' ?>" class="filter-tab <?= $filterCategory === 'gratitude' ? 'active' : '' ?>">🙌 Gratidão</a>
        </div>
        <div style="border-top: 1px solid var(--border-color); padding-top: 12px;">
            <a href="?intercession=<?= $filterIntercession ?>&category=<?= $filterCategory ?><?= $showAnswered ? '' : '&answered=1' ?>" style="
                display: inline-flex; align-items: center; gap: 6px;
                padding: 8px 14px; border-radius: 20px; font-size: var(--font-body-sm); font-weight: 600;
                text-decoration: none; color: <?= $showAnswered ? 'var(--sage-500)' : 'var(--text-muted)' ?>;
                background: <?= $showAnswered ? 'var(--sage-50)' : 'transparent' ?>;
                border: 1px solid <?= $showAnswered ? 'var(--sage-500)' : 'var(--border-color)' ?>;
            ">
                <i data-lucide="<?= $showAnswered ? 'check-circle' : 'sparkles' ?>" style="width: 14px;"></i>
                <?= $showAnswered ? 'Ver Pendentes' : 'Ver Respondidas 🎉' ?>
            </a>
        </div>
    </div>
    
    <!-- Feed de Pedidos -->
    <div style="display: flex; flex-direction: column; gap: 16px;">
        <?php if (count($prayers) > 0): ?>
            <?php foreach ($prayers as $prayer): 
                $comments = getComments($pdo, $prayer['id']);
                $hasPrayed = userPrayed($pdo, $prayer['id'], $userId);
                
                // Tempo relativo
                $createdAt = new DateTime($prayer['created_at']);
                $now = new DateTime();
                $diff = $now->diff($createdAt);
                
                if ($diff->y > 0) $timeAgo = $diff->y . ' ano(s)';
                elseif ($diff->m > 0) $timeAgo = $diff->m . ' mês(es)';
                elseif ($diff->d > 0) $timeAgo = $diff->d . ' dia(s)';
                elseif ($diff->h > 0) $timeAgo = $diff->h . 'h';
                elseif ($diff->i > 0) $timeAgo = $diff->i . 'min';
                else $timeAgo = 'agora';
                
                // Avatar
                $authorAvatar = null;
                $authorName = $prayer['is_anonymous'] ? 'Anônimo' : ($prayer['author_name'] ?? 'Membro');
                if (!$prayer['is_anonymous'] && !empty($prayer['author_avatar'])) {
                    $authorAvatar = $prayer['author_avatar'];
                    if (strpos($authorAvatar, 'http') === false && strpos($authorAvatar, 'assets') === false) {
                        $authorAvatar = '../assets/uploads/' . $authorAvatar;
                    }
                }
                
                // Category config
                $catConfig = [
                    'health' => ['icon' => '❤️', 'label' => 'Saúde', 'class' => 'cat-health'],
                    'family' => ['icon' => '👨‍👩‍👧', 'label' => 'Família', 'class' => 'cat-family'],
                    'work' => ['icon' => '💼', 'label' => 'Trabalho', 'class' => 'cat-work'],
                    'spiritual' => ['icon' => '✨', 'label' => 'Espiritual', 'class' => 'cat-spiritual'],
                    'gratitude' => ['icon' => '🙌', 'label' => 'Gratidão', 'class' => 'cat-gratitude'],
                    'other' => ['icon' => '🙏', 'label' => 'Outros', 'class' => 'cat-other']
                ];
                $cat = $catConfig[$prayer['category']] ?? $catConfig['other'];
                $intercessionClass = $prayer['is_interceded'] ? 'interceded' : 'not-interceded';
            ?>
            <div class="prayer-card collapsed <?= $intercessionClass ?> <?= $prayer['is_urgent'] ? 'urgent' : '' ?> <?= $prayer['is_answered'] ? 'answered' : '' ?>" 
                 id="prayer-<?= $prayer['id'] ?>" 
                 onclick="togglePrayerCard(<?= $prayer['id'] ?>, event)"
                 data-is-interceded="<?= $prayer['is_interceded'] ?>">
                <!-- Header -->
                <div class="prayer-header">
                    <?php if ($authorAvatar): ?>
                        <img src="<?= htmlspecialchars($authorAvatar) ?>" class="prayer-avatar" alt="">
                    <?php else: ?>
                        <div class="prayer-avatar-placeholder" style="background: var(--primary);">
                            <?= $prayer['is_anonymous'] ? '?' : strtoupper(substr($authorName, 0, 1)) ?>
                        </div>
                    <?php endif; ?>
                    
                    <div style="flex: 1;">
                        <div style="font-weight: 700; color: var(--text-main); font-size: 0.95rem;"><?= htmlspecialchars($authorName) ?></div>
                        <div style="display: flex; align-items: center; gap: 8px; font-size: 0.75rem; color: var(--text-muted);">
                            <span class="category-badge <?= $cat['class'] ?>"><?= $cat['icon'] ?> <?= $cat['label'] ?></span>
                            <span>• <?= $timeAgo ?></span>
                        </div>
                    </div>
                    
                    <?php if ($prayer['is_answered']): ?>
                        <div style="background: var(--primary); color: white; padding: 4px 10px; border-radius: 12px; font-size: 0.7rem; font-weight: 700;">
                            ✓ RESPONDIDA
                        </div>
                    <?php elseif ($prayer['is_urgent']): ?>
                        <div style="background: var(--rose-600); color: white; padding: 4px 10px; border-radius: 12px; font-size: 0.7rem; font-weight: 700;">
                            🔥 URGENTE
                        </div>
                    <?php endif; ?>
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
                        <button onclick="toggleIntercessionStatus(<?= $prayer['id'] ?>, this); event.stopPropagation();" class="pray-btn <?= $prayer['is_interceded'] ? 'prayed' : 'not-prayed' ?>" id="intercession-btn-<?= $prayer['id'] ?>">
                            <i data-lucide="heart" style="width: 16px;"></i>
                            <span><?= $prayer['is_interceded'] ? 'Intercedi' : 'Interceder' ?></span> (<?= $prayer['pray_count'] ?>)
                        </button>
                        
                        <button onclick="toggleComments('comments-<?= $prayer['id'] ?>'); event.stopPropagation();" style="background: none; border: none; color: var(--text-muted); font-size: 0.85rem; font-weight: 600; cursor: pointer; display: flex; align-items: center; gap: 4px;">
                            <i data-lucide="message-circle" style="width: 16px;"></i>
                            <?= count($comments) ?>
                        </button>
                    </div>
                    
                    <?php if ($prayer['user_id'] == $userId && !$prayer['is_answered']): ?>
                    <form method="POST" style="margin: 0;" onclick="event.stopPropagation()">
                        <input type="hidden" name="action" value="answered">
                        <input type="hidden" name="prayer_id" value="<?= $prayer['id'] ?>">
                        <button type="submit" style="background: var(--primary-subtle); color: var(--primary); border: none; padding: 6px 12px; border-radius: 12px; font-size: 0.85rem; font-weight: 600; cursor: pointer;">
                            ✓ Marcar respondida
                        </button>
                    </form>
                    <?php endif; ?>
                </div>
                
                <!-- Comments Section -->
                <div id="comments-<?= $prayer['id'] ?>" class="comments-section">
                    <?php foreach ($comments as $comment): 
                        $commentAvatar = !empty($comment['avatar']) ? $comment['avatar'] : null;
                        if ($commentAvatar && strpos($commentAvatar, 'http') === false) {
                            $commentAvatar = '../assets/uploads/' . $commentAvatar;
                        }
                    ?>
                    <div class="comment-item">
                        <?php if ($commentAvatar): ?>
                            <img src="<?= htmlspecialchars($commentAvatar) ?>" style="width: 32px; height: 32px; border-radius: 50%; object-fit: cover;" alt="">
                        <?php else: ?>
                            <div style="width: 32px; height: 32px; border-radius: 50%; background: var(--slate-200); display: flex; align-items: center; justify-content: center; font-size: 0.75rem; font-weight: 700; color: #666;">
                                <?= strtoupper(substr($comment['name'] ?? 'U', 0, 1)) ?>
                            </div>
                        <?php endif; ?>
                        <div style="flex: 1;">
                            <span style="font-weight: 600; font-size: 0.85rem; color: var(--text-main);"><?= htmlspecialchars($comment['name'] ?? 'Membro') ?></span>
                            <p style="margin: 4px 0 0; font-size: 0.9rem; color: var(--text-body);"><?= nl2br(htmlspecialchars($comment['comment'])) ?></p>
                        </div>
                    </div>
                    <?php endforeach; ?>
                    
                    <!-- Comment Form -->
                    <form method="POST" style="display: flex; gap: 8px; margin-top: 12px;">
                        <input type="hidden" name="action" value="comment">
                        <input type="hidden" name="prayer_id" value="<?= $prayer['id'] ?>">
                        <input type="text" name="comment" placeholder="Deixe uma palavra..." required style="flex: 1; padding: 10px 14px; border: 1px solid var(--border-color); border-radius: 24px; font-size: 0.9rem; outline: none;">
                        <button type="submit" style="background: var(--primary); color: white; border: none; padding: 10px 16px; border-radius: 24px; font-weight: 600; cursor: pointer;">
                            <i data-lucide="send" style="width: 16px;"></i>
                        </button>
                    </form>
                </div>
            </div>
            <?php endforeach; ?>
        <?php else: ?>
            <!-- Empty State -->
            <div style="text-align: center; padding: 60px 20px;">
                <div style="background: var(--green-50); width: 80px; height: 80px; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 20px;">
                    <i data-lucide="heart-handshake" style="color: var(--green-400); width: 40px; height: 40px;"></i>
                </div>
                <h3 style="color: var(--text-main); margin-bottom: 8px;"><?= $showAnswered ? 'Nenhuma oração respondida' : 'Nenhum pedido ainda' ?></h3>
                <p style="color: var(--text-muted); font-size: 0.95rem; max-width: 300px; margin: 0 auto 20px;">
                    <?= $showAnswered ? 'As orações respondidas aparecerão aqui.' : 'Seja o primeiro a compartilhar um pedido de oração!' ?>
                </p>
                <?php if (!$showAnswered): ?>
                <button onclick="openCreateModal()" class="btn-new-prayer">
                    <i data-lucide="plus" style="width: 18px; display: inline-block; vertical-align: middle; margin-right: 6px;"></i>
                    Novo Pedido
                </button>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
    <?php endif; ?>
    
    <div style="height: 100px;"></div>
</div>

<!-- FAB Button -->
<button onclick="openCreateModal()" class="fab-create">
    <i data-lucide="plus" style="width: 28px; height: 28px;"></i>
</button>

<!-- Modal Create -->
<div id="prayerModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; z-index: 1000;">
    <div onclick="closeModal()" style="position: absolute; width: 100%; height: 100%; background: rgba(0,0,0,0.5); backdrop-filter: blur(4px);"></div>
    
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
            <button onclick="closeModal()" style="background: var(--bg-body); border: none; color: var(--text-muted); cursor: pointer; width: 32px; height: 32px; border-radius: 50%; display: flex; align-items: center; justify-content: center; transition: all 0.2s;">
                <i data-lucide="x" style="width: 18px;"></i>
            </button>
        </div>
        
        <form method="POST" id="prayerForm" onsubmit="return preparePrayerSubmit()">
            <input type="hidden" name="action" value="create">
            <input type="hidden" name="description" id="hiddenDescription">
            
            <!-- Título -->
            <div style="margin-bottom: 16px;">
                <label style="display: block; font-weight: 700; color: var(--text-main); margin-bottom: 6px; font-size: 0.95rem;">Título do Pedido</label>
                <input type="text" name="title" id="prayerTitle" required placeholder="Ex: Oração pela saúde do meu pai">
            </div>
            
            <!-- Editor de Descrição -->
            <div style="margin-bottom: 16px;">
                <label style="display: block; font-weight: 700; color: var(--text-main); margin-bottom: 6px; font-size: 0.95rem;">Descrição (opcional)</label>
               <div id="prayerEditor"></div>
            </div>
            
            <!-- Categoria -->
            <div style="margin-bottom: 16px;">
                <label style="display: block; font-weight: 700; color: var(--text-main); margin-bottom: 8px; font-size: 0.95rem;">Categoria</label>
                <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 8px;">
                    <label class="cat-option" style="display: flex; flex-direction: column; align-items: center; gap: 4px; padding: 12px 8px; border-radius: 12px; cursor: pointer;">
                        <input type="radio" name="category" value="health" style="display: none;">
                        <span style="font-size: 1.5rem;">❤️</span>
                        <span style="font-size: 0.75rem; font-weight: 600;">Saúde</span>
                    </label>
                    <label class="cat-option" style="display: flex; flex-direction: column; align-items: center; gap: 4px; padding: 12px 8px; border-radius: 12px; cursor: pointer;">
                        <input type="radio" name="category" value="family" style="display: none;">
                        <span style="font-size: 1.5rem;">👨‍👩‍👧</span>
                        <span style="font-size: 0.75rem; font-weight: 600;">Família</span>
                    </label>
                    <label class="cat-option" style="display: flex; flex-direction: column; align-items: center; gap: 4px; padding: 12px 8px; border-radius: 12px; cursor: pointer;">
                        <input type="radio" name="category" value="work" style="display: none;">
                        <span style="font-size: 1.5rem;">💼</span>
                        <span style="font-size: 0.75rem; font-weight: 600;">Trabalho</span>
                    </label>
                    <label class="cat-option" style="display: flex; flex-direction: column; align-items: center; gap: 4px; padding: 12px 8px; border-radius: 12px; cursor: pointer;">
                        <input type="radio" name="category" value="spiritual" style="display: none;">
                        <span style="font-size: 1.5rem;">✨</span>
                        <span style="font-size: 0.75rem; font-weight: 600;">Espiritual</span>
                    </label>
                    <label class="cat-option" style="display: flex; flex-direction: column; align-items: center; gap: 4px; padding: 12px 8px; border-radius: 12px; cursor: pointer;">
                        <input type="radio" name="category" value="gratitude" style="display: none;">
                        <span style="font-size: 1.5rem;">🙌</span>
                        <span style="font-size: 0.75rem; font-weight: 600;">Gratidão</span>
                    </label>
                    <label class="cat-option" style="display: flex; flex-direction: column; align-items: center; gap: 4px; padding: 12px 8px; border-radius: 12px; cursor: pointer;">
                        <input type="radio" name="category" value="other" checked style="display: none;">
                        <span style="font-size: 1.5rem;">🙏</span>
                        <span style="font-size: 0.75rem; font-weight: 600;">Outros</span>
                    </label>
                </div>
            </div>
            
            <!-- Options -->
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
            
            <!-- Buttons -->
            <div style="display: flex; gap: 12px;">
                <button type="button" onclick="closeModal()" style="flex: 1; padding: 14px; border-radius: 12px; border: 1px solid var(--border-color); background: var(--bg-surface); font-weight: 600; cursor: pointer; color: var(--text-muted);">
                    Cancelar
                </button>
                <button type="submit" style="flex: 2; padding: 14px; border-radius: 12px; border: none; background: var(--primary); color: white; font-weight: 700; cursor: pointer;">
                    Enviar Pedido
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    // Category selection visual
    document.querySelectorAll('.cat-option input').forEach(input => {
        input.addEventListener('change', function() {
            document.querySelectorAll('.cat-option').forEach(opt => {
                opt.style.borderColor = 'var(--border-color)';
                opt.style.background = 'transparent';
            });
            this.parentElement.style.borderColor = 'var(--primary)';
            this.parentElement.style.background = 'var(--primary-subtle)';
        });
    });
    
    // Initialize first selection
    if(document.querySelector('.cat-option input:checked')) {
        const checked = document.querySelector('.cat-option input:checked');
        checked.parentElement.style.borderColor = 'var(--primary)';
        checked.parentElement.style.background = 'var(--primary-subtle)';
    }
    
    function openCreateModal() {
        document.getElementById('prayerModal').style.display = 'block';
    }
    
    function closeModal() {
        document.getElementById('prayerModal').style.display = 'none';
    }
    
    function toggleComments(id) {
        const section = document.getElementById(id);
        section.classList.toggle('open');
    }
    
    // Toggle prayer card expansion/collapse
    function togglePrayerCard(id, event) {
        // Evitar toggle se clicar em botões, links ou inputs
        if (event.target.closest('button') || event.target.closest('a') || event.target.closest('input') || event.target.closest('.comments-section')) {
            return;
        }
        
        const card = document.getElementById('prayer-' + id);
        card.classList.toggle('collapsed');
    }
    
    // Toggle advanced filters
    function toggleAdvancedFilters() {
        const panel = document.getElementById('advanced-filters-panel');
        if (panel.style.display === 'none') {
            panel.style.display = 'block';
            panel.style.animation = 'slideDown 0.2s ease-out';
        } else {
            panel.style.display = 'none';
        }
    }
    
    // Intercession toggle
    function toggleIntercessionStatus(prayerId, btn) {
        // Aqui deveria ter uma chamada AJAX idealmente
        // Por enquanto, vamos apenas submeter um form oculto ou redirecionar
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
</script>

<style>
@keyframes slideDown {
    from { opacity: 0; transform: translateY(-10px); }
    to { opacity: 1; transform: translateY(0); }
}
</style>

<!-- Quill JS -->
<script src="https://cdn.quilljs.com/1.3.6/quill.js"></script>
<script>
    var quill = new Quill('#prayerEditor', {
        theme: 'snow',
        placeholder: 'Compartilhe seu pedido de oração...',
        modules: {
            toolbar: [
                ['bold', 'italic', 'underline'],
                [{ 'list': 'ordered'}, { 'list': 'bullet' }]
            ]
        }
    });
    
    function preparePrayerSubmit() {
        var description = document.querySelector('input[name=description]');
        description.value = quill.getText();
        return true;
    }
</script>

<?php renderAppFooter(); ?>