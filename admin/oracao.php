<?php
// admin/oracao.php - Redesign Premium com funcionalidade completa
require_once '../src/helpers/auth.php';
require_once '../src/config/db.php';
require_once '../src/layout/layout.php';

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
    // Validação CSRF
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        http_response_code(403);
        die('Ação não autorizada. Por favor, recarregue a página e tente novamente.');
    }

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

renderAppHeader('Mural de Ora<link href="https://cdn.quilljs.com/1.3.6/quill.snow.css" rel="stylesheet">

<style>
    /* Sacred Minimalist Base Overrides & GPU Transitions */
    @keyframes revealStagger {
        0% {
            opacity: 0;
            transform: translateY(8px) scale(0.99);
        }
        100% {
            opacity: 1;
            transform: translateY(0) scale(1);
        }
    }

    .reveal-item {
        opacity: 0;
        animation: revealStagger 0.35s cubic-bezier(0.16, 1, 0.3, 1) forwards;
        will-change: opacity, transform;
    }

    /* Stagger Delay Classes */
    .reveal-stagger-1 { animation-delay: 0.02s; }
    .reveal-stagger-2 { animation-delay: 0.04s; }
    .reveal-stagger-3 { animation-delay: 0.06s; }
    .reveal-stagger-4 { animation-delay: 0.08s; }

    /* Collapsed state for description box */
    .prayer-card.collapsed .prayer-description {
        display: -webkit-box;
        -webkit-line-clamp: 3;
        -webkit-box-orient: vertical;
        overflow: hidden;
        mask-image: linear-gradient(to bottom, black 50%, transparent 100%);
        -webkit-mask-image: linear-gradient(to bottom, black 50%, transparent 100%);
    }

    /* Quill Editor Custom Styling for Dark Mode */
    .ql-toolbar.ql-snow {
        background-color: #1C1D22 !important;
        border: 1px solid #26272B !important;
        border-top-left-radius: 2px !important;
        border-top-right-radius: 2px !important;
        padding: 8px 12px !important;
    }

    .ql-container.ql-snow {
        background-color: #121316 !important;
        border: 1px solid #26272B !important;
        border-bottom-left-radius: 2px !important;
        border-bottom-right-radius: 2px !important;
        font-family: inherit !important;
        font-size: 0.875rem !important;
    }

    .ql-editor {
        color: #D4D4D8 !important;
        min-height: 120px;
    }

    .ql-editor.ql-blank::before {
        color: #52525B !important;
        font-style: normal !important;
    }

    .ql-snow .ql-stroke {
        stroke: #A1A1AA !important;
    }

    .ql-snow .ql-fill {
        fill: #A1A1AA !important;
    }

    .ql-snow .ql-picker {
        color: #A1A1AA !important;
    }

    .ql-snow .ql-picker-options {
        background-color: #1C1D22 !important;
        border-color: #26272B !important;
    }

    /* Spring micro-interactions */
    .interactive-scale {
        transition: all 0.15s cubic-bezier(0.16, 1, 0.3, 1);
    }
    .interactive-scale:active {
        transform: scale(0.97);
    }
</style>

<?php renderPageHeader('Mural de Oração', 'Louvor PIB Oliveira'); ?>

<main class="max-w-3xl mx-auto px-4 sm:px-6 py-6 mb-32 space-y-6" id="oracao-container">
    
    <!-- Hero / Header Section Bento (Sacred Minimalist) -->
    <div class="reveal-item reveal-stagger-1 relative overflow-hidden rounded-[2px] bg-[#121316] text-white p-8 shadow-sm border border-neutral-800">
        <div class="absolute -right-16 -top-16 w-64 h-64 bg-[#2E7EED]/5 rounded-full blur-3xl pointer-events-none"></div>
        
        <div class="relative z-10 flex flex-col md:flex-row md:items-center justify-between gap-6">
            <div>
                <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-[2px] bg-[#2E7EED]/10 border border-[#2E7EED]/20 text-[#2E7EED] text-[10px] font-bold uppercase tracking-wider mb-3">
                    <i data-lucide="heart-handshake" class="w-3.5 h-3.5"></i> Espiritualidade
                </span>
                <h1 class="text-3xl font-bold tracking-tight font-sans">Mural de Oração</h1>
                <p class="text-neutral-400 mt-2 max-w-xl text-sm font-body leading-relaxed">Compartilhe suas necessidades, interceda pelos irmãos e celebre as respostas de oração em comunidade.</p>
            </div>
            
            <button onclick="openCreateModal()" class="interactive-scale shrink-0 inline-flex items-center gap-2 px-5 py-3 rounded-[2px] bg-[#2E7EED] hover:bg-[#1A6FD6] text-white font-bold text-sm shadow-sm transition-all duration-150 will-change-transform cursor-pointer">
                <i data-lucide="plus" class="w-4 h-4 shrink-0"></i>
                Pedir Oração
            </button>
        </div>
    </div>

    <?php if (!$tableExists): ?>
    <!-- Setup Required Message -->
    <div class="reveal-item reveal-stagger-2 text-center py-12 px-6 bg-[#18191D] border border-neutral-800 rounded-[2px] shadow-sm">
        <div class="bg-neutral-900 border border-neutral-800 w-16 h-16 rounded-[2px] flex items-center justify-center mx-auto mb-4">
            <i data-lucide="database" class="text-neutral-500 w-8 h-8"></i>
        </div>
        <h3 class="text-lg font-bold text-neutral-200 mb-2">Configuração Necessária</h3>
        <p class="text-neutral-450 text-sm max-w-sm mx-auto mb-6">
            Para utilizar o Mural de Oração, é preciso estruturar as tabelas no banco de dados.
        </p>
        <a href="../maintenance/run_all_setup.php" class="interactive-scale inline-flex items-center gap-2 bg-[#2E7EED] hover:bg-[#1A6FD6] text-white px-5 py-3 rounded-[2px] text-xs font-bold transition-all shadow-sm">
            <i data-lucide="settings" class="w-4 h-4"></i>
            Executar Setup
        </a>
    </div>
    <?php else: ?>
    
    <!-- Filtros e Barra de Ações (Sharp Bento Grid style) -->
    <div class="reveal-item reveal-stagger-2 grid grid-cols-1 sm:grid-cols-3 gap-4 items-center bg-[#18191D] p-4 rounded-[2px] border border-neutral-850 shadow-sm">
        
        <!-- Filtro Intercessão -->
        <div class="relative sm:col-span-2">
            <i data-lucide="heart" class="absolute left-4 top-1/2 -translate-y-1/2 text-neutral-500 w-4 h-4"></i>
            <select onchange="window.location.href='?intercession='+this.value+'&category=<?= $filterCategory ?><?= $showAnswered ? '&answered=1' : '' ?>'" class="w-full h-12 pl-12 pr-10 bg-[#121316] border border-neutral-800 rounded-[2px] text-xs font-bold text-neutral-300 focus:outline-none focus:border-[#2E7EED] cursor-pointer appearance-none bg-[url('data:image/svg+xml;charset=US-ASCII,%3Csvg%20width%3D%2220%22%20height%3D%2220%22%20xmlns%3D%22http%3A%2F%2Fwww.w3.org%2F2000%2Fsvg%22%3E%3Cpath%20d%3D%22M5%208l5%205%205-5%22%20stroke%3D%22%23727785%22%20stroke-width%3D%221.5%22%20fill%3D%22none%22%20stroke-linecap%3D%22round%22%20stroke-linejoin%3D%22round%22%2F%3E%3C%2Fsvg%3E')] bg-no-repeat bg-[position:right_0.75rem_center]">
                <option value="all" <?= $filterIntercession === 'all' ? 'selected' : '' ?>>Todos os pedidos</option>
                <option value="not_interceded" <?= $filterIntercession === 'not_interceded' ? 'selected' : '' ?>>Não Intercedidos</option>
                <option value="interceded" <?= $filterIntercession === 'interceded' ? 'selected' : '' ?>>Intercedidos por mim</option>
            </select>
        </div>
        
        <!-- Botão Filtros Avançados -->
        <div class="flex items-center gap-2 w-full">
            <button onclick="toggleAdvancedFilters()" class="interactive-scale flex-1 h-12 bg-[#121316] hover:bg-[#1C1D22] border border-neutral-800 rounded-[2px] text-neutral-350 hover:text-neutral-200 text-xs font-bold flex items-center justify-center gap-2 cursor-pointer transition-all relative <?= ($filterCategory !== 'all' || $showAnswered) ? 'border-[#2E7EED] bg-[#2E7EED]/5 text-[#2E7EED]' : '' ?>">
                <i data-lucide="sliders-horizontal" class="w-4 h-4"></i>
                <span>Filtros</span>
                <?php if ($filterCategory !== 'all' || $showAnswered): ?>
                    <span class="w-2 h-2 bg-[#2E7EED] rounded-full animate-pulse"></span>
                <?php endif; ?>
            </button>
            
            <!-- Botão Limpar Filtros -->
            <?php if ($filterCategory !== 'all' || $showAnswered): ?>
            <button onclick="window.location.href='oracao.php'" class="interactive-scale w-12 h-12 bg-neutral-900 border border-neutral-800 rounded-[2px] text-[#FF4D4D] hover:bg-[#FF4D4D]/10 flex items-center justify-center shrink-0 cursor-pointer transition-all" title="Limpar Filtros">
                <i data-lucide="x" class="w-4 h-4"></i>
            </button>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Painel de Filtros Avançados -->
    <div id="advanced-filters-panel" class="hidden reveal-item bg-[#18191D] border border-neutral-800 rounded-[2px] p-6 shadow-sm space-y-4">
        <div class="text-[10px] font-bold text-neutral-450 uppercase tracking-widest pl-1 mb-2">Filtrar por Categoria</div>
        <div class="grid grid-cols-2 sm:grid-cols-3 gap-2">
            <a href="?intercession=<?= $filterIntercession ?>&category=all<?= $showAnswered ? '&answered=1' : '' ?>" class="px-4 py-2.5 rounded-[2px] text-xs font-bold border transition-all text-center <?= $filterCategory === 'all' ? 'bg-[#2E7EED]/10 border-[#2E7EED]/30 text-[#2E7EED]' : 'bg-[#121316] border-neutral-800 text-neutral-400 hover:bg-neutral-850 hover:text-neutral-200' ?>">🙏 Todos</a>
            <a href="?intercession=<?= $filterIntercession ?>&category=health<?= $showAnswered ? '&answered=1' : '' ?>" class="px-4 py-2.5 rounded-[2px] text-xs font-bold border transition-all text-center <?= $filterCategory === 'health' ? 'bg-[#2E7EED]/10 border-[#2E7EED]/30 text-[#2E7EED]' : 'bg-[#121316] border-neutral-800 text-neutral-400 hover:bg-neutral-850 hover:text-neutral-200' ?>">❤️ Saúde</a>
            <a href="?intercession=<?= $filterIntercession ?>&category=family<?= $showAnswered ? '&answered=1' : '' ?>" class="px-4 py-2.5 rounded-[2px] text-xs font-bold border transition-all text-center <?= $filterCategory === 'family' ? 'bg-[#2E7EED]/10 border-[#2E7EED]/30 text-[#2E7EED]' : 'bg-[#121316] border-neutral-800 text-neutral-400 hover:bg-neutral-850 hover:text-neutral-200' ?>">👨‍👩‍👧 Família</a>
            <a href="?intercession=<?= $filterIntercession ?>&category=work<?= $showAnswered ? '&answered=1' : '' ?>" class="px-4 py-2.5 rounded-[2px] text-xs font-bold border transition-all text-center <?= $filterCategory === 'work' ? 'bg-[#2E7EED]/10 border-[#2E7EED]/30 text-[#2E7EED]' : 'bg-[#121316] border-neutral-800 text-neutral-400 hover:bg-neutral-850 hover:text-neutral-200' ?>">💼 Trabalho</a>
            <a href="?intercession=<?= $filterIntercession ?>&category=spiritual<?= $showAnswered ? '&answered=1' : '' ?>" class="px-4 py-2.5 rounded-[2px] text-xs font-bold border transition-all text-center <?= $filterCategory === 'spiritual' ? 'bg-[#2E7EED]/10 border-[#2E7EED]/30 text-[#2E7EED]' : 'bg-[#121316] border-neutral-800 text-neutral-400 hover:bg-neutral-850 hover:text-neutral-200' ?>">✨ Fé</a>
            <a href="?intercession=<?= $filterIntercession ?>&category=gratitude<?= $showAnswered ? '&answered=1' : '' ?>" class="px-4 py-2.5 rounded-[2px] text-xs font-bold border transition-all text-center <?= $filterCategory === 'gratitude' ? 'bg-[#2E7EED]/10 border-[#2E7EED]/30 text-[#2E7EED]' : 'bg-[#121316] border-neutral-800 text-neutral-400 hover:bg-neutral-850 hover:text-neutral-200' ?>">🙌 Gratidão</a>
        </div>
        <div class="border-t border-neutral-850 pt-4 flex justify-between items-center">
            <a href="?intercession=<?= $filterIntercession ?>&category=<?= $filterCategory ?><?= $showAnswered ? '' : '&answered=1' ?>" class="interactive-scale inline-flex items-center gap-1.5 px-4 py-2 bg-[#121316] hover:bg-[#1C1D22] border border-neutral-800 rounded-[2px] text-xs font-bold text-neutral-350 hover:text-neutral-200 transition-all">
                <i data-lucide="<?= $showAnswered ? 'clock' : 'check-circle-2' ?>" class="w-3.5 h-3.5 <?= $showAnswered ? 'text-neutral-500' : 'text-[#10B981]' ?>"></i>
                <span><?= $showAnswered ? 'Ver Pedidos Pendentes' : 'Ver Orações Respondidas 🎉' ?></span>
            </a>
        </div>
    </div>
    
    <!-- Feed de Pedidos (Bento Cards com cantos retos de 2px e sem roxo) -->
    <div class="space-y-4">
        <?php if (count($prayers) > 0): ?>
            <?php 
            $pIdx = 0;
            foreach ($prayers as $prayer): 
                $comments = getComments($pdo, $prayer['id']);
                $hasPrayed = userPrayed($pdo, $prayer['id'], $userId);
                
                // Tempo relativo
                $createdAt = new DateTime($prayer['created_at']);
                $now = new DateTime();
                $diff = $now->diff($createdAt);
                
                if ($diff->y > 0) $timeAgo = $diff->y . ' ano(s)';
                elseif ($diff->m > 0) $timeAgo = $diff->m . ' mês(es)';
                elseif ($diff->d > 0) $timeAgo = $diff->d . ' d';
                elseif ($diff->h > 0) $timeAgo = $diff->h . 'h';
                elseif ($diff->i > 0) $timeAgo = $diff->i . 'min';
                else $timeAgo = 'agora';
                
                // Avatar
                $authorAvatar = null;
                $authorName = $prayer['is_anonymous'] ? 'Anônimo' : ($prayer['author_name'] ?? 'Membro');
                if (!$prayer['is_anonymous'] && !empty($prayer['author_avatar'])) {
                    $authorAvatar = $prayer['author_avatar'];
                    if (strpos($authorAvatar, 'http') === false && strpos($authorAvatar, 'assets') === false) {
                        $authorAvatar = '../uploads/' . $authorAvatar;
                    }
                }
                
                // Category Config
                $catConfig = [
                    'health' => ['icon' => '❤️', 'label' => 'Saúde', 'bg' => 'bg-[#FF4D4D]/10 text-[#FF4D4D] border-[#FF4D4D]/20'],
                    'family' => ['icon' => '👨‍👩‍👧', 'label' => 'Família', 'bg' => 'bg-neutral-800 text-neutral-300 border-neutral-700'],
                    'work' => ['icon' => '💼', 'label' => 'Trabalho', 'bg' => 'bg-[#FFC107]/10 text-[#FFC107] border-[#FFC107]/20'],
                    'spiritual' => ['icon' => '✨', 'label' => 'Fé', 'bg' => 'bg-[#2E7EED]/10 text-[#2E7EED] border-[#2E7EED]/20'],
                    'gratitude' => ['icon' => '🙌', 'label' => 'Gratidão', 'bg' => 'bg-[#10B981]/10 text-[#10B981] border-[#10B981]/20'],
                    'other' => ['icon' => '🙏', 'label' => 'Outros', 'bg' => 'bg-neutral-800 text-neutral-300 border-neutral-700']
                ];
                $cat = $catConfig[$prayer['category']] ?? $catConfig['other'];
                
                // Border Accents
                $cardBorder = 'border border-neutral-800';
                $leftAccent = '';
                
                if ($prayer['is_answered']) {
                    $cardBorder = 'border border-[#10B981]/30';
                    $leftAccent = 'border-l-[3px] border-l-[#10B981]';
                } elseif ($prayer['is_urgent']) {
                    // Urgência Rubi Sóbria
                    $cardBorder = 'border border-[#B32424]';
                    $leftAccent = 'border-l-[3px] border-l-[#B32424]';
                } elseif ($prayer['is_interceded']) {
                    $cardBorder = 'border border-neutral-800 opacity-90';
                    $leftAccent = 'border-l-[3px] border-l-[#2E7EED]';
                } else {
                    $leftAccent = 'border-l-[3px] border-l-[#FFC107]'; // Não intercedido brilha em dourado
                }
                
                $staggerClass = 'reveal-stagger-' . min(4, max(1, ++$pIdx));
            ?>
            <div class="prayer-card collapsed reveal-item <?= $staggerClass ?> bg-[#18191D] p-6 shadow-sm hover:shadow-md transition-all duration-300 rounded-[2px] <?= $cardBorder ?> <?= $leftAccent ?> relative group cursor-pointer" 
                 id="prayer-<?= $prayer['id'] ?>" 
                 onclick="togglePrayerCard(<?= $prayer['id'] ?>, event)"
                 data-is-interceded="<?= $prayer['is_interceded'] ?>">
                
                <!-- Card Header -->
                <div class="flex items-center justify-between gap-4 pb-4 border-b border-neutral-850">
                    <div class="flex items-center gap-3">
                        <?php if ($authorAvatar): ?>
                            <img src="<?= htmlspecialchars($authorAvatar) ?>" class="w-10 h-10 rounded-[2px] object-cover border border-neutral-800 shadow-sm shrink-0" alt="">
                        <?php else: ?>
                            <div class="w-10 h-10 rounded-[2px] bg-[#2E7EED]/10 text-[#2E7EED] font-bold flex items-center justify-center text-sm shrink-0">
                                <?= $prayer['is_anonymous'] ? '?' : strtoupper(substr($authorName, 0, 1)) ?>
                            </div>
                        <?php endif; ?>
                        
                        <div>
                            <div class="text-sm font-bold text-neutral-200"><?= htmlspecialchars($authorName) ?></div>
                            <div class="flex items-center gap-2 text-[10px] text-neutral-500 font-bold uppercase tracking-wider mt-0.5">
                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-[2px] border text-[9px] <?= $cat['bg'] ?>">
                                    <?= $cat['icon'] ?> <?= $cat['label'] ?>
                                </span>
                                <span>• <?= $timeAgo ?></span>
                            </div>
                        </div>
                    </div>
                    
                    <div class="flex items-center gap-2">
                        <?php if ($prayer['is_answered']): ?>
                            <span class="inline-flex items-center gap-1 text-[9px] uppercase font-bold tracking-widest bg-[#10B981]/10 border border-[#10B981]/20 text-[#10B981] px-2.5 py-1 rounded-[2px]">✓ Respondida</span>
                        <?php elseif ($prayer['is_urgent']): ?>
                            <span class="inline-flex items-center gap-1 text-[9px] uppercase font-bold tracking-widest bg-[#B32424]/10 border border-[#B32424]/20 text-[#FF4D4D] px-2.5 py-1 rounded-[2px]">🚨 Urgente</span>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Card Content -->
                <div class="prayer-content relative py-4">
                    <h3 class="prayer-title text-base font-bold text-neutral-100 mb-2 leading-tight tracking-tight"><?= htmlspecialchars($prayer['title']) ?></h3>
                    <?php if (!empty($prayer['description'])): ?>
                        <div class="prayer-description text-neutral-400 text-sm break-words font-sans leading-relaxed"><?= nl2br(htmlspecialchars($prayer['description'])) ?></div>
                    <?php endif; ?>
                    
                    <!-- Expand Indicator -->
                    <div class="expand-indicator absolute bottom-0 left-0 right-0 h-10 bg-gradient-to-t from-[#18191D] to-transparent items-end justify-center pb-1 text-[10px] font-bold text-[#2E7EED] uppercase tracking-wider hidden">
                        <span>Ler mais <i data-lucide="chevron-down" class="inline-block w-3.5 h-3.5 align-middle"></i></span>
                    </div>
                </div>
                
                <!-- Card Footer Actions -->
                <div class="flex items-center justify-between gap-4 pt-4 border-t border-neutral-850">
                    <div class="flex items-center gap-3">
                        <button onclick="toggleIntercessionStatus(<?= $prayer['id'] ?>, this); event.stopPropagation();" class="interactive-scale inline-flex items-center gap-1.5 px-4 py-2 text-xs font-bold rounded-[2px] transition-all duration-150 transform will-change-transform cursor-pointer <?= $prayer['is_interceded'] ? 'bg-[#B32424]/10 border border-[#B32424]/20 text-[#FF4D4D]' : 'text-neutral-400 bg-neutral-900 border border-neutral-800 hover:bg-neutral-850' ?>" id="intercession-btn-<?= $prayer['id'] ?>">
                            <i data-lucide="heart" class="w-4 h-4 shrink-0 <?= $prayer['is_interceded'] ? 'fill-current' : '' ?>"></i>
                            <span><?= $prayer['is_interceded'] ? 'Intercedi' : 'Interceder' ?></span>
                            <span class="opacity-75 font-mono text-[10px] pl-0.5">(<?= $prayer['pray_count'] ?>)</span>
                        </button>
                        
                        <button onclick="toggleComments('comments-<?= $prayer['id'] ?>'); event.stopPropagation();" class="interactive-scale inline-flex items-center gap-1.5 px-3.5 py-2 text-xs font-bold rounded-[2px] text-neutral-400 bg-neutral-900 border border-neutral-800 hover:bg-neutral-850 cursor-pointer transition-all">
                            <i data-lucide="message-circle" class="w-4 h-4"></i>
                            <span>Comentários</span>
                            <span class="opacity-75 font-mono text-[10px] pl-0.5">(<?= count($comments) ?>)</span>
                        </button>
                    </div>
                    
                    <?php if ($prayer['user_id'] == $userId && !$prayer['is_answered']): ?>
                    <form method="POST" style="margin: 0;" onclick="event.stopPropagation()">
                        <?= App\AuthMiddleware::csrfField() ?>
                        <input type="hidden" name="action" value="answered">
                        <input type="hidden" name="prayer_id" value="<?= $prayer['id'] ?>">
                        <button type="submit" class="interactive-scale px-4 py-2 bg-[#10B981]/10 hover:bg-[#10B981]/20 border border-[#10B981]/20 text-[#10B981] rounded-[2px] text-xs font-bold cursor-pointer transition-all">
                            ✓ Marcar respondida
                        </button>
                    </form>
                    <?php endif; ?>
                </div>
                
                <!-- Comments Section (Bento style comments inside card) -->
                <div id="comments-<?= $prayer['id'] ?>" class="comments-section hidden mt-4 pt-4 border-t border-neutral-850 space-y-3" onclick="event.stopPropagation()">
                    <div class="text-[10px] font-bold text-neutral-450 uppercase tracking-widest pl-1 mb-2">Mensagens de Intercessão</div>
                    
                    <div class="space-y-2.5 max-h-60 overflow-y-auto pr-1">
                        <?php foreach ($comments as $comment): 
                            $commentAvatar = !empty($comment['avatar']) ? $comment['avatar'] : null;
                            if ($commentAvatar && strpos($commentAvatar, 'http') === false) {
                                $commentAvatar = '../uploads/' . $commentAvatar;
                            }
                        ?>
                        <div class="flex gap-3 bg-[#121316]/40 p-3 rounded-[2px] border border-neutral-850">
                            <?php if ($commentAvatar): ?>
                                <img src="<?= htmlspecialchars($commentAvatar) ?>" class="w-8 h-8 rounded-[2px] object-cover shrink-0 border border-neutral-800 shadow-sm" alt="">
                            <?php else: ?>
                                <div class="w-8 h-8 rounded-[2px] bg-neutral-800 text-neutral-450 font-bold flex items-center justify-center text-xs shrink-0">
                                    <?= strtoupper(substr($comment['name'] ?? 'U', 0, 1)) ?>
                                </div>
                            <?php endif; ?>
                            <div class="flex-1 min-w-0">
                                <div class="flex items-center justify-between gap-2">
                                    <span class="text-xs font-bold text-neutral-300"><?= htmlspecialchars($comment['name'] ?? 'Membro') ?></span>
                                    <span class="text-[9px] text-neutral-550 font-semibold"><?= date('d/m H:i', strtotime($comment['created_at'])) ?></span>
                                </div>
                                <p class="text-xs text-neutral-400 font-sans leading-relaxed mt-1 whitespace-pre-line"><?= htmlspecialchars($comment['comment']) ?></p>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    
                    <!-- Comment Input Form -->
                    <form method="POST" class="flex gap-2 pt-2">
                        <?= App\AuthMiddleware::csrfField() ?>
                        <input type="hidden" name="action" value="comment">
                        <input type="hidden" name="prayer_id" value="<?= $prayer['id'] ?>">
                        <input type="text" name="comment" placeholder="Deixe uma palavra de fé e apoio..." required class="flex-1 h-10 px-4 bg-[#121316] border border-neutral-800 rounded-[2px] text-xs focus:outline-none focus:border-[#2E7EED] transition-all placeholder-neutral-500 text-neutral-250 font-medium">
                        <button type="submit" class="interactive-scale w-10 h-10 rounded-[2px] bg-[#2E7EED] hover:bg-[#1A6FD6] text-white flex items-center justify-center shrink-0 shadow-sm transition-all cursor-pointer">
                            <i data-lucide="send" class="w-4 h-4"></i>
                        </button>
                    </form>
                </div>
            </div>
            <?php endforeach; ?>
        <?php else: ?>
            <!-- Empty State (Sacred Minimalist styling) -->
            <div class="reveal-item text-center py-16 px-6 bg-[#18191D] border border-neutral-850 rounded-[2px]">
                <div class="w-16 h-16 rounded-[2px] bg-[#10B981]/5 border border-[#10B981]/10 flex items-center justify-center mx-auto mb-4">
                    <i data-lucide="heart" class="text-[#10B981] w-8 h-8"></i>
                </div>
                <h3 class="text-base font-bold text-neutral-300 mb-1"><?= $showAnswered ? 'Nenhuma oração respondida' : 'Nenhum pedido registrado' ?></h3>
                <p class="text-xs text-neutral-500 max-w-xs mx-auto mb-6">
                    <?= $showAnswered ? 'As orações marcadas como respondidas aparecerão aqui para louvor de toda a igreja.' : 'Compartilhe suas necessidades espirituais e receba o apoio em oração da equipe.' ?>
                </p>
                <?php if (!$showAnswered): ?>
                <button onclick="openCreateModal()" class="interactive-scale inline-flex items-center gap-2 bg-[#2E7EED] hover:bg-[#1A6FD6] text-white px-5 py-3 rounded-[2px] text-xs font-bold transition-all shadow-sm cursor-pointer">
                    <i data-lucide="plus" class="w-4 h-4"></i>
                    Novo Pedido
                </button>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
    <?php endif; ?>
    
    <div class="h-20"></div>
</main>

<!-- Floating Action Button for Mobiles -->
<div class="fixed bottom-24 right-6 z-40 sm:hidden">
    <button onclick="openCreateModal()" class="active:scale-90 w-14 h-14 rounded-[2px] bg-[#2E7EED] text-white flex items-center justify-center shadow-2xl hover:bg-[#1A6FD6] transition-all transform duration-150 will-change-transform border border-white/10 cursor-pointer">
        <i data-lucide="plus" class="w-6 h-6"></i>
    </button>
</div>

<!-- Modal Create (Bottom Sheet Drawer Style on Mobile, Elegant Panel on Desktop) -->
<div id="prayerModal" class="fixed inset-0 z-[100] bg-[#000]/60 backdrop-blur-sm hidden transition-opacity duration-300 opacity-0 flex items-end sm:items-center justify-center p-0 sm:p-4" onclick="closeModal(event)">
    
    <!-- Drawer / Modal content panel -->
    <div id="prayerModalContent" class="bg-[#18191D] border-t sm:border border-neutral-800 w-full max-w-md rounded-t-[4px] sm:rounded-[2px] shadow-2xl flex flex-col max-h-[90vh] sm:max-h-[85vh] overflow-y-auto translate-y-full sm:translate-y-0 sm:scale-95 transition-all duration-300 ease-out" onclick="event.stopPropagation()">
        
        <!-- Drag Handle for Mobile -->
        <div class="w-12 h-1 bg-neutral-800 rounded-full mx-auto my-3 shrink-0 sm:hidden"></div>
        
        <div class="flex items-center justify-between px-6 pb-4 border-b border-neutral-850 shrink-0">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-[2px] bg-[#2E7EED]/10 border border-[#2E7EED]/20 text-[#2E7EED] flex items-center justify-center shrink-0">
                    <span class="text-lg">🙏</span>
                </div>
                <div>
                    <h2 class="text-base font-bold text-neutral-100 leading-tight">Novo Pedido</h2>
                    <p class="text-[9px] text-neutral-500 font-bold uppercase tracking-widest mt-0.5">Compartilhe na central de oração</p>
                </div>
            </div>
            <button onclick="closeModalForce()" class="w-8 h-8 flex items-center justify-center rounded-[2px] bg-[#121316] hover:bg-[#1C1D22] border border-neutral-800 text-neutral-400 transition-colors cursor-pointer">
                <i data-lucide="x" class="w-4 h-4"></i>
            </button>
        </div>
        
        <form method="POST" id="prayerForm" onsubmit="return preparePrayerSubmit()" class="flex flex-col flex-1 overflow-hidden p-6 space-y-4">
            <?= App\AuthMiddleware::csrfField() ?>
            <input type="hidden" name="action" value="create">
            <input type="hidden" name="description" id="hiddenDescription">
            
            <!-- Título -->
            <div class="space-y-1">
                <label class="text-[10px] font-bold text-neutral-450 uppercase tracking-widest pl-0.5">Título do Pedido</label>
                <input type="text" name="title" id="prayerTitle" required placeholder="Ex: Pela cirurgia da minha mãe" class="w-full px-4 py-3 bg-[#121316] border border-neutral-800 rounded-[2px] focus:border-[#2E7EED] outline-none transition-all text-sm text-neutral-200">
            </div>
            
            <!-- Editor de Descrição -->
            <div class="space-y-1">
                <label class="text-[10px] font-bold text-neutral-450 uppercase tracking-widest pl-0.5">Descrição / Motivo (Opcional)</label>
                <div id="prayerEditor" class="font-sans"></div>
            </div>
            
            <!-- Categoria Selector (Sharp Cards) -->
            <div class="space-y-2">
                <label class="text-[10px] font-bold text-neutral-450 uppercase tracking-widest pl-0.5">Categoria</label>
                <div class="grid grid-cols-3 gap-2">
                    <label class="cat-option border border-neutral-800 rounded-[2px] p-2.5 flex flex-col items-center justify-center gap-1.5 cursor-pointer bg-[#121316] transition-all text-center select-none text-neutral-400" onclick="selectFormCategory(this)">
                        <input type="radio" name="category" value="health" class="hidden">
                        <span class="text-base">❤️</span>
                        <span class="text-[9px] font-bold uppercase tracking-wider">Saúde</span>
                    </label>
                    <label class="cat-option border border-neutral-800 rounded-[2px] p-2.5 flex flex-col items-center justify-center gap-1.5 cursor-pointer bg-[#121316] transition-all text-center select-none text-neutral-400" onclick="selectFormCategory(this)">
                        <input type="radio" name="category" value="family" class="hidden">
                        <span class="text-base">👨‍👩‍👧</span>
                        <span class="text-[9px] font-bold uppercase tracking-wider">Família</span>
                    </label>
                    <label class="cat-option border border-neutral-800 rounded-[2px] p-2.5 flex flex-col items-center justify-center gap-1.5 cursor-pointer bg-[#121316] transition-all text-center select-none text-neutral-400" onclick="selectFormCategory(this)">
                        <input type="radio" name="category" value="work" class="hidden">
                        <span class="text-base">💼</span>
                        <span class="text-[9px] font-bold uppercase tracking-wider">Trabalho</span>
                    </label>
                    <label class="cat-option border border-neutral-800 rounded-[2px] p-2.5 flex flex-col items-center justify-center gap-1.5 cursor-pointer bg-[#121316] transition-all text-center select-none text-neutral-400" onclick="selectFormCategory(this)">
                        <input type="radio" name="category" value="spiritual" class="hidden">
                        <span class="text-base">✨</span>
                        <span class="text-[9px] font-bold uppercase tracking-wider">Fé</span>
                    </label>
                    <label class="cat-option border border-neutral-800 rounded-[2px] p-2.5 flex flex-col items-center justify-center gap-1.5 cursor-pointer bg-[#121316] transition-all text-center select-none text-neutral-400" onclick="selectFormCategory(this)">
                        <input type="radio" name="category" value="gratitude" class="hidden">
                        <span class="text-base">🙌</span>
                        <span class="text-[9px] font-bold uppercase tracking-wider">Gratidão</span>
                    </label>
                    <label class="cat-option border border-[#2E7EED] rounded-[2px] p-2.5 flex flex-col items-center justify-center gap-1.5 cursor-pointer bg-[#2E7EED]/5 transition-all text-center select-none text-[#2E7EED]" onclick="selectFormCategory(this)">
                        <input type="radio" name="category" value="other" checked class="hidden">
                        <span class="text-base">🙏</span>
                        <span class="text-[9px] font-bold uppercase tracking-wider">Outros</span>
                    </label>
                </div>
            </div>
            
            <!-- Opções Checkbox -->
            <div class="flex gap-4 pt-1 pb-2">
                <label class="flex items-center gap-2 cursor-pointer select-none">
                    <input type="checkbox" name="is_urgent" class="w-4 h-4 rounded-[2px] border-neutral-800 bg-[#121316] text-[#B32424] focus:ring-[#B32424] focus:ring-offset-0">
                    <span class="text-[10px] font-bold text-neutral-400 uppercase tracking-widest">🔥 Urgente</span>
                </label>
                <label class="flex items-center gap-2 cursor-pointer select-none">
                    <input type="checkbox" name="is_anonymous" class="w-4 h-4 rounded-[2px] border-neutral-800 bg-[#121316] text-[#2E7EED] focus:ring-[#2E7EED] focus:ring-offset-0">
                    <span class="text-[10px] font-bold text-neutral-400 uppercase tracking-widest">🔒 Anônimo</span>
                </label>
            </div>
            
            <!-- Botões Formulário -->
            <div class="flex gap-3 pt-4 border-t border-neutral-850 shrink-0">
                <button type="button" onclick="closeModalForce()" class="flex-1 py-3 px-4 bg-neutral-900 hover:bg-neutral-850 border border-neutral-800 rounded-[2px] font-bold text-xs text-neutral-400 transition-colors cursor-pointer">Cancelar</button>
                <button type="submit" class="flex-[2] py-3 px-4 bg-[#2E7EED] hover:bg-[#1A6FD6] text-white rounded-[2px] font-bold text-xs shadow-sm transition-colors active:scale-[0.97] transform will-change-transform cursor-pointer">Enviar Pedido</button>
            </div>
        </form>
    </div>
</div>

<script>
    // Form Category visual toggle
    function selectFormCategory(element) {
        document.querySelectorAll('.cat-option').forEach(el => {
            el.classList.remove('border-[#2E7EED]', 'bg-[#2E7EED]/5', 'text-[#2E7EED]');
            el.classList.add('border-neutral-800', 'bg-[#121316]', 'text-neutral-400');
        });
        
        element.classList.remove('border-neutral-800', 'bg-[#121316]', 'text-neutral-400');
        element.classList.add('border-[#2E7EED]', 'bg-[#2E7EED]/5', 'text-[#2E7EED]');
        
        const radio = element.querySelector('input[type="radio"]');
        if (radio) radio.checked = true;
    }
    
    // Dynamic modal behavior with GPU animation support
    function openCreateModal() {
        const modal = document.getElementById('prayerModal');
        const content = document.getElementById('prayerModalContent');
        
        modal.classList.remove('hidden');
        document.body.style.overflow = 'hidden';
        
        // Force Reflow
        modal.offsetHeight;
        
        modal.classList.remove('opacity-0');
        modal.classList.add('opacity-100');
        
        // Handle responsive layout transitions
        if (window.innerWidth < 640) {
            content.classList.remove('translate-y-full');
            content.classList.add('translate-y-0');
        } else {
            content.classList.remove('scale-95');
            content.classList.add('scale-100');
        }
        
        // Reinicializar Lucide Icons
        setTimeout(() => {
            if (typeof lucide !== 'undefined') {
                lucide.createIcons();
            }
        }, 50);
    }
    
    function closeModal(event) {
        if (event && event.target !== document.getElementById('prayerModal')) return;
        closeModalForce();
    }
    
    function closeModalForce() {
        const modal = document.getElementById('prayerModal');
        const content = document.getElementById('prayerModalContent');
        
        modal.classList.remove('opacity-100');
        modal.classList.add('opacity-0');
        
        if (window.innerWidth < 640) {
            content.classList.remove('translate-y-0');
            content.classList.add('translate-y-full');
        } else {
            content.classList.remove('scale-100');
            content.classList.add('scale-95');
        }
        
        setTimeout(() => {
            modal.classList.add('hidden');
            document.body.style.overflow = '';
        }, 300);
    }
    
    function toggleComments(id) {
        const section = document.getElementById(id);
        if (section) {
            section.classList.toggle('hidden');
        }
    }
    
    // Toggle prayer card description box expand/collapse
    function togglePrayerCard(id, event) {
        if (event.target.closest('button') || event.target.closest('a') || event.target.closest('input') || event.target.closest('form')) {
            return;
        }
        
        const card = document.getElementById('prayer-' + id);
        if (card) {
            card.classList.toggle('collapsed');
        }
    }
    
    // Toggle advanced filters
    function toggleAdvancedFilters() {
        const panel = document.getElementById('advanced-filters-panel');
        if (panel.classList.contains('hidden')) {
            panel.classList.remove('hidden');
        } else {
            panel.classList.add('hidden');
        }
    }
    
    // Intercession submit toggle (redirect / post fallback)
    function toggleIntercessionStatus(prayerId, btn) {
        const icon = btn.querySelector('svg');
        const textSpan = btn.querySelector('span');
        const isActive = btn.classList.contains('bg-[#B32424]/10');
        
        // Elastic UI feedback
        if (isActive) {
            btn.className = 'interactive-scale inline-flex items-center gap-1.5 px-4 py-2 text-xs font-bold rounded-[2px] transition-all duration-150 transform will-change-transform text-neutral-400 bg-neutral-900 border border-neutral-800 hover:bg-neutral-850';
            if (icon) icon.classList.remove('fill-current');
            if (textSpan) textSpan.innerText = 'Interceder';
        } else {
            btn.className = 'interactive-scale inline-flex items-center gap-1.5 px-4 py-2 text-xs font-bold rounded-[2px] transition-all duration-150 transform will-change-transform bg-[#B32424]/10 border border-[#B32424]/20 text-[#FF4D4D]';
            if (icon) icon.classList.add('fill-current');
            if (textSpan) textSpan.innerText = 'Intercedi';
        }
        
        // Create hidden form to handle state mutation
        const form = document.createElement('form');
        form.method = 'POST';
        form.style.display = 'none';
        
        const inputAction = document.createElement('input');
        inputAction.name = 'action';
        inputAction.value = 'pray';
        
        const inputId = document.createElement('input');
        inputId.name = 'prayer_id';
        inputId.value = prayerId;
        
        const csrfInput = document.createElement('input');
        csrfInput.type = 'hidden';
        csrfInput.name = 'csrf_token';
        csrfInput.value = '<?= htmlspecialchars($_SESSION["csrf_token"]) ?>';
        
        form.appendChild(inputAction);
        form.appendChild(inputId);
        form.appendChild(csrfInput);
        
        document.body.appendChild(form);
        form.submit();
    }
</script>

<!-- Quill JS Setup -->
<script src="https://cdn.quilljs.com/1.3.6/quill.js"></script>
<script>
    var quill;
    document.addEventListener('DOMContentLoaded', () => {
        quill = new Quill('#prayerEditor', {
            theme: 'snow',
            placeholder: 'Descreva os motivos da oração...',
            modules: {
                toolbar: [
                    ['bold', 'italic', 'underline'],
                    [{ 'list': 'ordered'}, { 'list': 'bullet' }]
                ]
            }
        });
        
        // Initial setup for Lucide Icons
        if (typeof lucide !== 'undefined') {
            lucide.createIcons();
        }
    });
    
    function preparePrayerSubmit() {
        var hiddenInput = document.getElementById('hiddenDescription');
        if (hiddenInput && quill) {
            hiddenInput.value = quill.getText().trim();
        }
        return true;
    }
</script>

<?php renderAppFooter(); ?> = document.querySelector('input[name=description]');
        description.value = quill.getText();
        return true;
    }
</script>

<?php renderAppFooter(); ?>