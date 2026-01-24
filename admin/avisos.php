<?php
// admin/avisos.php
require_once '../includes/auth.php';
require_once '../includes/db.php';
require_once '../includes/layout.php';

checkLogin();

// --- LÓGICA DE POST (CRUD) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        $userId = $_SESSION['user_id'] ?? 1;

        switch ($_POST['action']) {
            case 'create':
                $stmt = $pdo->prepare("INSERT INTO avisos (title, message, priority, type, target_audience, expires_at, created_by, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, NOW())");
                $stmt->execute([
                    $_POST['title'],
                    $_POST['message'], // HTML do Quill
                    $_POST['priority'],
                    $_POST['type'],
                    $_POST['target_audience'],
                    !empty($_POST['expires_at']) ? $_POST['expires_at'] : NULL,
                    $userId
                ]);
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
$filterType = $_GET['type'] ?? 'all';
$search = $_GET['search'] ?? '';

// Construir Query
$sql = "SELECT a.*, u.name as author_name FROM avisos a LEFT JOIN users u ON a.created_by = u.id WHERE 1=1";
$params = [];

if ($showArchived) {
    $sql .= " AND a.archived_at IS NOT NULL";
} elseif ($showHistory) {
    $sql .= " AND (a.archived_at IS NOT NULL OR (a.expires_at IS NOT NULL AND a.expires_at < CURDATE()))";
} else {
    // Default: Not archived AND (Not expired OR today <= expires_at)
    $sql .= " AND a.archived_at IS NULL AND (a.expires_at IS NULL OR a.expires_at >= CURDATE())";
}

if ($filterType !== 'all') {
    $sql .= " AND a.type = ?";
    $params[] = $filterType;
}

if (!empty($search)) {
    $sql .= " AND (a.title LIKE ? OR a.message LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

$sql .= " ORDER BY 
    CASE WHEN a.priority = 'urgent' THEN 1 WHEN a.priority = 'important' THEN 2 ELSE 3 END ASC, 
    a.created_at DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$avisos = $stmt->fetchAll(PDO::FETCH_ASSOC);

renderAppHeader('Avisos');
?>

<!-- Quill CSS -->
<link href="https://cdn.quilljs.com/1.3.6/quill.snow.css" rel="stylesheet">

    <?php
    // Header Padrão
    renderPageHeader('Mural de Avisos', '');
    ?>

<div class="container" style="padding-top: 16px; max-width: 900px; margin: 0 auto;">

    <!-- Texto Explicativo -->
    <div style="margin-bottom: 24px;">
        <h2 style="font-size: 1.25rem; font-weight: 700; color: var(--text-main); margin: 0 0 6px 0;">
            Central de Comunicação
        </h2>
        <p style="margin: 0; color: var(--text-muted); font-size: 0.95rem; line-height: 1.5; max-width: 600px;">
            Gerencie avisos importantes, escalas e eventos da equipe. Mantenha todos alinhados e informados.
        </p>
    </div>


    <!-- Filtros de Navegação -->
    <div style="margin-bottom: 24px;">

        <!-- Busca e Ações -->
        <div style="display: flex; gap: 12px; margin-bottom: 24px; align-items: start;">
            <div style="flex: 1; position: relative;">
                <i data-lucide="search" style="position: absolute; left: 16px; top: 12px; color: var(--text-muted); width: 20px;"></i>
                <form onsubmit="return true;" style="margin:0">
                    <?php if ($showArchived): ?><input type="hidden" name="archived" value="1"><?php endif; ?>
                    <?php if ($showHistory): ?><input type="hidden" name="history" value="1"><?php endif; ?>
                    <?php if ($filterType !== 'all'): ?><input type="hidden" name="type" value="<?= $filterType ?>"><?php endif; ?>
                    <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" placeholder="Buscar avisos..."
                        style="
                            width: 100%; padding: 10px 12px 10px 48px; border-radius: var(--radius-md); 
                            border: 1px solid var(--border-color); font-size: 0.95rem; outline: none; 
                            transition: border 0.2s; background: var(--bg-surface); color: var(--text-main);
                            box-shadow: var(--shadow-sm); height: 42px;
                        "
                        onfocus="this.style.borderColor='var(--primary)'" onblur="this.style.borderColor='var(--border-color)'">
                </form>
            </div>
            
            <?php if ($_SESSION['user_role'] === 'admin'): ?>
            <button onclick="openCreateModal()" class="ripple" style="
                background: var(--primary); color: white; border: none; padding: 0 16px; height: 42px; 
                border-radius: var(--radius-md); font-weight: 600; font-size: 0.9rem; cursor: pointer;
                display: flex; align-items: center; gap: 8px; box-shadow: var(--shadow-sm);
                white-space: nowrap;
            ">
                <i data-lucide="plus" width="18"></i>
                <span>Novo Aviso</span>
            </button>
            <?php endif; ?>
        </div>

        <!-- Filtros (Estilo Abas/Tabs) -->
        <style>
            .filter-tab {
                padding: 8px 12px; 
                color: var(--text-muted); 
                text-decoration: none; 
                font-weight: 600; 
                font-size: 0.9rem;
                display: flex; align-items: center; gap: 6px;
                border-bottom: 2px solid transparent;
                transition: all 0.2s;
                opacity: 0.7;
            }
            .filter-tab:hover {
                color: var(--text-main);
                opacity: 1;
                background: rgba(0,0,0,0.02);
                border-radius: 6px 6px 0 0;
            }
            .filter-tab.active {
                color: var(--primary);
                font-weight: 700;
                border-bottom: 2px solid var(--primary);
                opacity: 1;
                background: transparent;
            }
        </style>
        <div style="display: flex; gap: 4px; overflow-x: auto; padding-bottom: 0; scrollbar-width: none; border-bottom: 1px solid var(--border-color); margin-bottom: 16px;">
            <?php
            $types = [
                'all' => ['label' => 'Todos', 'icon' => ''],
                'general' => ['label' => 'Geral', 'icon' => '📢'],
                'event' => ['label' => 'Eventos', 'icon' => '🎉'],
                'music' => ['label' => 'Música', 'icon' => '🎵'],
                'spiritual' => ['label' => 'Espiritual', 'icon' => '🙏'],
                'urgent' => ['label' => 'Urgente', 'icon' => '🚨'],
            ];
            foreach ($types as $key => $data):
                $isActive = $filterType === $key;
                $activeClass = $isActive ? 'active' : '';
            ?>
                <a href="?type=<?= $key ?><?= $showArchived ? '&archived=1' : '' ?><?= $search ? '&search=' . $search : '' ?>" class="filter-tab <?= $activeClass ?>">
                    <?php if($data['icon']): ?><span><?= $data['icon'] ?></span><?php endif; ?>
                    <?= $data['label'] ?>
                </a>
            <?php endforeach; ?>
        </div>

        <div style="margin-top: 12px; display: flex; align-items: center; gap: 12px; font-size: 0.85rem;">
            <a href="?history=<?= $showHistory ? '0' : '1' ?>" class="ripple" style="
                color: var(--text-muted); font-weight: 600; text-decoration: none; 
                display: flex; align-items: center; gap: 4px; padding: 6px 12px; 
                background: <?= $showHistory ? 'var(--bg-surface)' : 'transparent' ?>; 
                border-radius: 20px; border: 1px solid <?= $showHistory ? 'var(--border-color)' : 'transparent' ?>;
            ">
                <i data-lucide="clock" style="width: 14px;"></i>
                <?= $showHistory ? 'Ver Ativos' : 'Ver Histórico' ?>
            </a>
        </div>

    </div>

    <!-- LISTA DE AVISOS (Design Profissional) -->
    <div style="display: flex; flex-direction: column; gap: 16px;">
        <?php if (count($avisos) > 0): ?>
            <?php foreach ($avisos as $aviso): 
                // Configuração de Cores e Ícones
                $typeConfig = [
                    'general' => ['color' => '#64748b', 'bg' => '#f1f5f9', 'icon' => '📢', 'label' => 'Geral'],
                    'event' =>   ['color' => '#2563eb', 'bg' => '#eff6ff', 'icon' => '🎉', 'label' => 'Evento'],
                    'music' =>   ['color' => '#059669', 'bg' => '#ecfdf5', 'icon' => '🎵', 'label' => 'Música'],
                    'spiritual'=>['color' => '#7c3aed', 'bg' => '#f5f3ff', 'icon' => '🙏', 'label' => 'Espiritual'],
                    'urgent' =>  ['color' => '#dc2626', 'bg' => '#fef2f2', 'icon' => '🚨', 'label' => 'Urgente']
                ];
                $tc = $typeConfig[$aviso['type']] ?? $typeConfig['general'];

                // Prioridade
                $isUrgent = $aviso['priority'] === 'urgent';
                $isImportant = $aviso['priority'] === 'important';
                
                // Data Relativa
                $createdAt = new DateTime($aviso['created_at']);
                $now = new DateTime();
                $diff = $now->diff($createdAt);
                
                if ($diff->y > 0) $timeAgo = $diff->y . ' ano(s) atrás';
                elseif ($diff->m > 0) $timeAgo = $diff->m . ' mês(es) atrás';
                elseif ($diff->d > 0) $timeAgo = $diff->d . ' dia(s) atrás';
                elseif ($diff->h > 0) $timeAgo = $diff->h . 'h atrás';
                elseif ($diff->i > 0) $timeAgo = $diff->i . 'm atrás';
                else $timeAgo = 'Agorinha';

                // Expiração
                $expiryText = "";
                if ($aviso['expires_at']) {
                    $expiresAt = new DateTime($aviso['expires_at']);
                    $daysLeft = (int)$now->diff($expiresAt)->format('%r%a');
                    
                    if ($daysLeft < 0) $expiryText = "<span style='color:var(--text-muted)'>Expirado</span>";
                    elseif ($daysLeft == 0) $expiryText = "<span style='color:#dc2626'>Expira hoje</span>";
                    elseif ($daysLeft <= 3) $expiryText = "<span style='color:#d97706'>Expira em $daysLeft dias</span>";
                    else $expiryText = "<span style='color:var(--text-muted)'>Expira em " . $expiresAt->format('d/m') . "</span>";
                }
            ?>
            <div class="aviso-card" style="
                background: white; border-radius: 12px; 
                border: 1px solid var(--border-color);
                border-left: 4px solid <?= $tc['color'] ?>;
                box-shadow: 0 2px 5px rgba(0,0,0,0.02);
                padding: 0; overflow: hidden; position: relative;
                transition: transform 0.2s, box-shadow 0.2s;
            ">
                <!-- Header -->
                <div style="padding: 16px 16px 8px 16px; display: flex; justify-content: space-between; align-items: flex-start;">
                    <div style="display: flex; gap: 10px; align-items: start;">
                        <!-- Ícone/Avatar -->
                        <div style="
                            width: 40px; height: 40px; border-radius: 10px; 
                            background: <?= $tc['bg'] ?>; color: <?= $tc['color'] ?>;
                            display: flex; align-items: center; justify-content: center; font-size: 1.2rem;
                            flex-shrink: 0;
                        ">
                            <?= $tc['icon'] ?>
                        </div>
                        
                        <div>
                            <!-- Tipo e Badges -->
                            <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 4px; flex-wrap: wrap;">
                                <span style="
                                    font-size: 0.7rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px;
                                    color: <?= $tc['color'] ?>;
                                ">
                                    <?= $tc['label'] ?>
                                </span>
                                
                                <?php if($isUrgent): ?>
                                    <span style="font-size: 0.65rem; background: #fee2e2; color: #dc2626; padding: 2px 6px; border-radius: 4px; font-weight: 700;">URGENTE</span>
                                <?php elseif($isImportant): ?>
                                    <span style="font-size: 0.65rem; background: #fef3c7; color: #d97706; padding: 2px 6px; border-radius: 4px; font-weight: 700;">IMPORTANTE</span>
                                <?php endif; ?>

                                <?php if($aviso['target_audience'] !== 'all'): ?>
                                    <span style="font-size: 0.65rem; background: #f1f5f9; color: var(--text-muted); padding: 2px 6px; border-radius: 4px; font-weight: 600; display: flex; align-items: center; gap: 3px;">
                                        <i data-lucide="users" width="10"></i> 
                                        <?= $aviso['target_audience'] == 'admins' ? 'Líderes' : 'Equipe' ?>
                                    </span>
                                <?php endif; ?>
                            </div>

                            <h3 style="margin: 0; font-size: 1.05rem; font-weight: 700; color: var(--text-main); line-height: 1.3;">
                                <?= htmlspecialchars($aviso['title']) ?>
                            </h3>
                        </div>
                    </div>

                    <!-- Menu Ações -->
                     <?php if ($_SESSION['user_role'] === 'admin'): ?>
                    <div style="position: relative;">
                        <button class="btn-icon ripple" onclick="toggleMenu('menu-<?= $aviso['id'] ?>')" style="padding: 4px; border-radius: 50%;">
                            <i data-lucide="more-horizontal" style="color: var(--text-muted);"></i>
                        </button>
                        <!-- Dropdown -->
                        <div id="menu-<?= $aviso['id'] ?>" class="dropdown-menu" style="
                            display: none; position: absolute; right: 0; top: 100%; width: 140px;
                            background: white; border: 1px solid var(--border-color); border-radius: 8px;
                            box-shadow: 0 4px 12px rgba(0,0,0,0.1); z-index: 10;
                        ">
                            <a href="#" onclick="openEditModal(<?= htmlspecialchars(json_encode($aviso)) ?>)" style="display: flex; align-items: center; gap: 8px; padding: 10px; color: var(--text-main); text-decoration: none; font-size: 0.9rem;">
                                <i data-lucide="edit-2" width="16"></i> Editar
                            </a>
                            <a href="avisos_actions.php?action=delete&id=<?= $aviso['id'] ?>" onclick="return confirm('Tem certeza?')" style="display: flex; align-items: center; gap: 8px; padding: 10px; color: #dc2626; text-decoration: none; font-size: 0.9rem; border-top: 1px solid var(--border-color);">
                                <i data-lucide="trash-2" width="16"></i> Excluir
                            </a>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>

                <!-- Conteúdo / Mensagem -->
                <div style="padding: 0 16px 16px 66px;">
                    <div class="message-preview" style="color: var(--text-body); font-size: 0.95rem; line-height: 1.5;">
                        <?= $aviso['message'] ?>
                    </div>
                </div>

                <!-- Footer: Autor e Data -->
                <div style="
                    background: #f8fafc; padding: 10px 16px; border-top: 1px solid var(--border-color);
                    display: flex; justify-content: space-between; align-items: center; font-size: 0.8rem; color: var(--text-muted);
                ">
                    <div style="display: flex; align-items: center; gap: 6px;">
                        <?php if (!empty($aviso['avatar'])): ?>
                            <img src="<?= htmlspecialchars($aviso['avatar']) ?>" style="width: 20px; height: 20px; border-radius: 50%; object-fit: cover;">
                        <?php else: ?>
                            <div style="width: 20px; height: 20px; border-radius: 50%; background: #cbd5e1; display:flex; align-items:center; justify-content:center; color:white; font-size:0.6rem; font-weight:700;">
                                <?= substr($aviso['author_name'] ?? 'A', 0, 1) ?>
                            </div>
                        <?php endif; ?>
                        <span style="font-weight: 500;"><?= htmlspecialchars($aviso['author_name'] ?? 'Admin') ?></span>
                    </div>
                    
                    <div style="display: flex; align-items: center; gap: 12px;">
                        <?php if($expiryText): ?>
                            <span style="display: flex; align-items: center; gap: 4px; background:white; padding: 2px 8px; border-radius: 10px; border: 1px solid #e2e8f0;">
                                <i data-lucide="clock" width="12"></i> <?= $expiryText ?>
                            </span>
                        <?php endif; ?>
                        <span><?= $timeAgo ?></span>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="empty-state">
                <div style="background: var(--bg-surface); padding: 40px; border-radius: 16px; text-align: center; border: 1px dashed var(--border-color);">
                    <i data-lucide="bell-off" style="width: 48px; height: 48px; color: var(--text-muted); margin-bottom: 16px;"></i>
                    <h3 style="color: var(--text-main); margin-bottom: 8px;">Nenhum aviso encontrado</h3>
                    <p style="color: var(--text-muted); font-size: 0.95rem;">Tente mudar os filtros ou crie um novo aviso.</p>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <div style="height: 60px;"></div>
</div>



<!-- Modal Universal (Add/Edit) -->
<div id="avisoModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; z-index: 1000;">
    <div onclick="closeModal()" style="position: absolute; width: 100%; height: 100%; background: rgba(0,0,0,0.5); backdrop-filter: blur(2px);"></div>

    <div style="
        position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%);
        width: 90%; max-width: 600px; background: var(--bg-surface); border-radius: 24px; padding: 24px;
        box-shadow: var(--shadow-xl); max-height: 90vh; overflow-y: auto;
    ">
        <h2 id="modalTitle" style="margin: 0 0 16px 0; font-size: 1.25rem; font-weight: 800; color: var(--text-main);">Novo Aviso</h2>
        <form method="POST" id="avisoForm" onsubmit="return prepareSubmit()">
            <input type="hidden" name="action" id="formAction" value="create">
            <input type="hidden" name="id" id="avisoId">
            <input type="hidden" name="message" id="hiddenMessage">

            <div class="form-group" style="margin-bottom: 16px;">
                <label class="form-label" style="display: block; font-weight: 700; color: var(--text-main); margin-bottom: 6px;">Título</label>
                <input type="text" name="title" id="avisoTitle" required class="input-modern" style="
                    width: 100%; padding: 12px; border-radius: 10px; border: 1px solid var(--border-color); 
                    outline: none; background: var(--bg-body); color: var(--text-main);
                ">
            </div>

            <!-- SELETORES EM GRID (Dropdowns Compactos) -->
            <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 12px; margin-bottom: 16px;">
                
                <!-- TIPO -->
                <div>
                    <label class="form-label" style="display: block; font-size: 0.75rem; font-weight: 700; color: var(--text-muted); margin-bottom: 4px; text-transform: uppercase;">Tipo</label>
                    <input type="hidden" name="type" id="avisoType">
                    <div class="custom-dropdown" id="dropdownType">
                        <div class="dropdown-trigger ripple">
                            <span class="selected-label">Selecione...</span>
                            <i data-lucide="chevron-down" width="14"></i>
                        </div>
                        <div class="dropdown-options">
                            <div class="dropdown-item" data-value="general" data-label="📢 Geral" data-color="var(--text-main)">📢 Geral</div>
                            <div class="dropdown-item" data-value="event" data-label="🎉 Evento" data-color="#1d4ed8">🎉 Evento</div>
                            <div class="dropdown-item" data-value="music" data-label="🎵 Música" data-color="#047857">🎵 Música</div>
                            <div class="dropdown-item" data-value="spiritual" data-label="🙏 Espiritual" data-color="#6d28d9">🙏 Espiritual</div>
                            <div class="dropdown-item" data-value="urgent" data-label="🚨 Urgente" data-color="#b91c1c">🚨 Urgente</div>
                        </div>
                    </div>
                </div>

                <!-- PRIORIDADE -->
                <div>
                    <label class="form-label" style="display: block; font-size: 0.75rem; font-weight: 700; color: var(--text-muted); margin-bottom: 4px; text-transform: uppercase;">Prioridade</label>
                    <input type="hidden" name="priority" id="avisoPriority">
                    <div class="custom-dropdown" id="dropdownPriority">
                        <div class="dropdown-trigger ripple">
                            <span class="selected-label">Selecione...</span>
                            <i data-lucide="chevron-down" width="14"></i>
                        </div>
                        <div class="dropdown-options">
                            <div class="dropdown-item" data-value="info" data-label="ℹ Info" data-color="#2563eb">ℹ Info</div>
                            <div class="dropdown-item" data-value="important" data-label="⭐ Importante" data-color="#d97706">⭐ Importante</div>
                            <div class="dropdown-item" data-value="urgent" data-label="🔥 Urgente" data-color="#dc2626">🔥 Urgente</div>
                        </div>
                    </div>
                </div>

                <!-- PÚBLICO -->
                <div>
                    <label class="form-label" style="display: block; font-size: 0.75rem; font-weight: 700; color: var(--text-muted); margin-bottom: 4px; text-transform: uppercase;">Público</label>
                    <input type="hidden" name="target_audience" id="avisoTarget">
                    <div class="custom-dropdown" id="dropdownTarget">
                        <div class="dropdown-trigger ripple">
                            <span class="selected-label">Todos</span>
                            <i data-lucide="chevron-down" width="14"></i>
                        </div>
                        <div class="dropdown-options">
                            <div class="dropdown-item" data-value="all" data-label="Todos" data-color="var(--text-main)">Todos</div>
                            <div class="dropdown-item" data-value="team" data-label="Equipe" data-color="var(--text-main)">Equipe</div>
                            <div class="dropdown-item" data-value="admins" data-label="Admins" data-color="var(--text-main)">Líderes</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- EXPIRAÇÃO (Full Width agora, ou abaixo) -->
            <div style="margin-bottom: 12px;">
                <label class="form-label" style="display: block; font-size: 0.75rem; font-weight: 700; color: var(--text-muted); margin-bottom: 4px; text-transform: uppercase;">Validade (Opcional)</label>
                <input type="date" name="expires_at" id="avisoExpires" style="
                    width: 100%; padding: 10px; border-radius: 8px; border: 1px solid var(--border-color); 
                    background: var(--bg-body); color: var(--text-main); font-size: 0.9rem;
                    outline: none;
                ">
            </div>

            <style>
                .custom-dropdown {
                    position: relative;
                }
                .dropdown-trigger {
                    width: 100%;
                    padding: 10px;
                    background: var(--bg-body);
                    border: 1px solid var(--border-color);
                    border-radius: 8px;
                    display: flex; justify-content: space-between; align-items: center;
                    cursor: pointer;
                    font-size: 0.85rem; font-weight: 600; color: var(--text-main);
                    user-select: none;
                }
                .dropdown-options {
                    display: none;
                    position: absolute; top: 100%; left: 0; right: 0;
                    background: var(--bg-surface);
                    border: 1px solid var(--border-color);
                    border-radius: 8px;
                    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
                    z-index: 50;
                    margin-top: 4px;
                    overflow: hidden;
                    max-height: 200px; overflow-y: auto;
                }
                .custom-dropdown.open .dropdown-options {
                    display: block;
                }
                .dropdown-item {
                    padding: 8px 12px;
                    font-size: 0.85rem;
                    cursor: pointer;
                    transition: background 0.1s;
                    display: flex; align-items: center; gap: 6px;
                    color: var(--text-muted);
                }
                .dropdown-item:hover {
                    background: var(--bg-body);
                    color: var(--text-main);
                }
                .dropdown-item.selected {
                    background: var(--primary-subtle, #f0fdf4);
                    color: var(--primary, #166534);
                    font-weight: 700;
                }
            </style>

            <div class="form-group" style="margin-bottom: 24px;">
                <label class="form-label" style="display: block; font-weight: 700; color: var(--text-main); margin-bottom: 6px;">Mensagem</label>
                <div id="editor" style="height: 150px; background: white; color: black !important;"></div>
            </div>

            <div style="display: flex; gap: 12px;">
                <button type="button" onclick="closeModal()" style="
                    flex: 1; padding: 14px; border-radius: 12px; border: 1px solid var(--border-color); 
                    background: var(--bg-surface); font-weight: 600; cursor: pointer; color: var(--text-muted);
                ">Cancelar</button>
                <button type="submit" style="
                    flex: 2; padding: 14px; border-radius: 12px; border: none; 
                    background: var(--primary); color: white; font-weight: 700; cursor: pointer;
                ">Salvar Aviso</button>
            </div>
        </form>
    </div>
</div>

<script src="https://cdn.quilljs.com/1.3.6/quill.js"></script>
<script>
    // Quill Init
    var quill = new Quill('#editor', {
        theme: 'snow',
        modules: {
            toolbar: [
                ['bold', 'italic', 'underline'],
                [{
                    'list': 'ordered'
                }, {
                    'list': 'bullet'
                }],
                ['link']
            ]
        }
    });

    // Helper robusto para setar valor + UI sem depender de click() simulado
    function setSelectorValue(dropdownId, value) {
        const dropdown = document.getElementById(dropdownId);
        if(!dropdown) return;
        
        // Encontrar item alvo ou fallback para o primeiro
        const target = dropdown.querySelector(`.dropdown-item[data-value="${value}"]`) || dropdown.querySelector('.dropdown-item'); 
        if(!target) return;

        // Extrair dados
        const val = target.getAttribute('data-value');
        const label = target.getAttribute('data-label');
        const color = target.getAttribute('data-color');

        // Atualizar Input Hidden (Busca pelo ID ou Sibling)
        // Como definimos ID nos inputs hidden, vamos tentar usar o ID baseado no dropdown se possível, 
        // mas o sibling é o padrão atual.
        const hiddenInput = dropdown.previousElementSibling;
        if(hiddenInput) hiddenInput.value = val;

        // Atualizar Label Visível
        const labelSpan = dropdown.querySelector('.selected-label');
        if(labelSpan) {
            labelSpan.innerText = label;
            labelSpan.style.color = color || 'var(--text-main)';
        }
        
        // Atualizar Estado Visual do Menu
        dropdown.querySelectorAll('.dropdown-item').forEach(i => i.classList.remove('selected'));
        target.classList.add('selected');
    }

    function initSelectors() {
        document.querySelectorAll('.custom-dropdown').forEach(dropdown => {
            const trigger = dropdown.querySelector('.dropdown-trigger');
            const hiddenInput = dropdown.previousElementSibling; // Hidden input
            
            // Toggle
            trigger.addEventListener('click', (e) => {
                e.preventDefault(); e.stopPropagation();
                // Close others
                document.querySelectorAll('.custom-dropdown').forEach(d => {
                    if(d !== dropdown) d.classList.remove('open');
                });
                dropdown.classList.toggle('open');
            });
            
            // Select Option
            dropdown.querySelectorAll('.dropdown-item').forEach(item => {
                item.addEventListener('click', (e) => {
                    e.stopPropagation();
                    const value = item.getAttribute('data-value');
                    
                    // Usar o helper para garantir consistência
                    setSelectorValue(dropdown.id, value);
                    
                    // Close
                    dropdown.classList.remove('open');
                });
            });
        });

        // Click outside closes dropdowns
        document.addEventListener('click', () => {
             document.querySelectorAll('.custom-dropdown').forEach(d => d.classList.remove('open'));
        });
    }

    initSelectors();

    function prepareSubmit() {
        document.getElementById('hiddenMessage').value = quill.root.innerHTML;
        return true;
    }

    // Modal
    function openCreateModal() {
        document.getElementById('modalTitle').innerText = 'Novo Aviso';
        document.getElementById('formAction').value = 'create';
        document.getElementById('avisoForm').reset();
        document.getElementById('avisoId').value = '';
        quill.setContents([]);
        
        // Defaults
        setSelectorValue('dropdownType', 'general');
        setSelectorValue('dropdownPriority', 'info');
        setSelectorValue('dropdownTarget', 'all');
        
        document.getElementById('avisoModal').style.display = 'block';
    }

    function openEditModal(aviso) {
        document.getElementById('modalTitle').innerText = 'Editar Aviso';
        document.getElementById('formAction').value = 'update';
        document.getElementById('avisoId').value = aviso.id;
        document.getElementById('avisoTitle').value = aviso.title;
        
        // Set Selectors
        setSelectorValue('dropdownType', aviso.type);
        setSelectorValue('dropdownPriority', aviso.priority);
        setSelectorValue('dropdownTarget', aviso.target_audience || 'all');
        
        document.getElementById('avisoExpires').value = aviso.expires_at || '';

        quill.root.innerHTML = aviso.message; // Load HTML

        closeAllMenus();
        document.getElementById('avisoModal').style.display = 'block';
    }
    

    function closeModal() {
        document.getElementById('avisoModal').style.display = 'none';
    }

    // Menus
    function toggleMenu(id) {
        closeAllMenus();
        const menu = document.getElementById(id);
        menu.style.display = menu.style.display === 'block' ? 'none' : 'block';
    }

    function closeAllMenus() {
        document.querySelectorAll('.dropdown-menu').forEach(el => el.style.display = 'none');
    }

    window.onclick = function(event) {
        if (!event.target.matches('.btn-icon') && !event.target.closest('.btn-icon')) {
            closeAllMenus();
        }
    }
</script>

<?php renderAppFooter(); ?>