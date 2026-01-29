<?php
// admin/notificacoes.php
require_once '../includes/auth.php';
require_once '../includes/db.php';
require_once '../includes/layout.php';
require_once '../includes/notification_system.php';

$userId = $_SESSION['user_id'];
$notificationSystem = new NotificationSystem($pdo);

// Processar Salvamento de Preferências
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'save_preferences') {
    try {
        $preferences = $_POST['prefs'] ?? [];
        
        // Primeiro, buscar todos os tipos possíveis para saber quais foram desmarcados
        // (Checkboxes não enviados significam desabilitado)
        $allTypes = [
            NotificationSystem::TYPE_WEEKLY_REPORT,
            NotificationSystem::TYPE_NEW_ESCALA,
            NotificationSystem::TYPE_ESCALA_UPDATE,
            NotificationSystem::TYPE_NEW_MUSIC,
            NotificationSystem::TYPE_NEW_AVISO,
            NotificationSystem::TYPE_AVISO_URGENT,
            NotificationSystem::TYPE_MEMBER_ABSENCE,
            NotificationSystem::TYPE_BIRTHDAY,
            NotificationSystem::TYPE_READING_REMINDER
        ];

        $pdo->beginTransaction();

        // Limpar preferências existentes (remover tudo para inserir o estado atual)
        // Isso assume que se não está na tabela é true (default), mas como vamos salvar o estado explicitamente:
        // Estratégia: Salvar apenas o que for FALSE (desabilitado) para economizar linhas, 
        // ou salvar tudo. Vamos salvar tudo para ser explícito.
        
        // Melhor abordagem: DELETE ALL for user AND INSERT new state
        $stmtDelete = $pdo->prepare("DELETE FROM notification_preferences WHERE user_id = ?");
        $stmtDelete->execute([$userId]);

        $stmtInsert = $pdo->prepare("INSERT INTO notification_preferences (user_id, type, enabled) VALUES (?, ?, ?)");

        foreach ($allTypes as $type) {
            $enabled = isset($preferences[$type]) ? 1 : 0;
            $stmtInsert->execute([$userId, $type, $enabled]);
        }

        $pdo->commit();
        $success = "Preferências atualizadas com sucesso!";
    } catch (Exception $e) {
        $pdo->rollBack();
        $error = "Erro ao salvar preferências: " . $e->getMessage();
    }
}

// Filtros de Notificações (mantendo funcionalidade existente)
$filterType = $_GET['type'] ?? 'all';
$filterStatus = $_GET['status'] ?? 'all';
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$perPage = 20;

// ... (Código de consulta de notificações existente mantido) ...
// Construir query
$where = ["user_id = ?"];
$params = [$userId];

if ($filterType !== 'all') {
    $where[] = "type = ?";
    $params[] = $filterType;
}

if ($filterStatus === 'unread') {
    $where[] = "is_read = 0";
} elseif ($filterStatus === 'read') {
    $where[] = "is_read = 1";
}

$whereClause = implode(' AND ', $where);

// Contar total
$stmtCount = $pdo->prepare("SELECT COUNT(*) FROM notifications WHERE $whereClause");
$stmtCount->execute($params);
$total = $stmtCount->fetchColumn();
$totalPages = ceil($total / $perPage);

// Buscar notificações
$offset = ($page - 1) * $perPage;
$stmt = $pdo->prepare("
    SELECT * FROM notifications 
    WHERE $whereClause 
    ORDER BY created_at DESC 
    LIMIT $perPage OFFSET $offset
");
$stmt->execute($params);
$notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Estatísticas
$stmtStats = $pdo->prepare("
    SELECT 
        COUNT(*) as total,
        SUM(CASE WHEN is_read = 0 THEN 1 ELSE 0 END) as unread,
        SUM(CASE WHEN is_read = 1 THEN 1 ELSE 0 END) as `read`
    FROM notifications 
    WHERE user_id = ?
");
$stmtStats->execute([$userId]);
$stats = $stmtStats->fetch(PDO::FETCH_ASSOC);


// Buscar Preferências Atuais para o Modal
$stmtPrefs = $pdo->prepare("SELECT type, enabled FROM notification_preferences WHERE user_id = ?");
$stmtPrefs->execute([$userId]);
$userPrefs = $stmtPrefs->fetchAll(PDO::FETCH_KEY_PAIR); // [type => enabled]

// Definição dos Tipos para a UI
$notificationTypes = [
    'Escalas' => [
        NotificationSystem::TYPE_NEW_ESCALA => 'Novas Escalas',
        NotificationSystem::TYPE_ESCALA_UPDATE => 'Alterações em Escalas',
        NotificationSystem::TYPE_MEMBER_ABSENCE => 'Ausências de Membros'
    ],
    'Repertório' => [
        NotificationSystem::TYPE_NEW_MUSIC => 'Novas Músicas'
    ],
    'Comunicação' => [
        NotificationSystem::TYPE_NEW_AVISO => 'Novos Avisos',
        NotificationSystem::TYPE_AVISO_URGENT => 'Avisos Urgentes',
        NotificationSystem::TYPE_BIRTHDAY => 'Aniversariantes'
    ],
    'Espiritual' => [
        NotificationSystem::TYPE_READING_REMINDER => 'Lembrete de Leitura',
        NotificationSystem::TYPE_WEEKLY_REPORT => 'Relatório Semanal'
    ]
];

renderAppHeader('Notificações');
renderPageHeader('Gestor de Notificações', 'Louvor PIB Oliveira');
?>

<style>
    .notifications-container {
        max-width: 900px;
        margin: 0 auto;
        padding: 0 20px 20px 20px;
    }
    
    .stats-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
        gap: 12px;
        margin-bottom: 24px;
    }
    
    .stat-card {
        background: var(--bg-surface);
        padding: 16px;
        border-radius: 12px;
        border: 1px solid var(--border-color);
        position: relative;
        overflow: hidden;
    }
    
    .stat-card::before {
        content: '';
        position: absolute;
        top: 0; left: 0; right: 0; height: 3px;
        background: currentColor;
    }
    
    .stat-card.total { color: #3b82f6; }
    .stat-card.unread { color: #ef4444; }
    .stat-card.read { color: #10b981; }
    
    .stat-value {
        font-size: 1.5rem;
        font-weight: 700;
        color: var(--text-main);
        margin-bottom: 4px;
    }
    
    .stat-label {
        font-size: 0.875rem;
        color: var(--text-muted);
    }
    
    /* Config Card */
    .config-card {
        background: var(--bg-surface);
        border-radius: 12px;
        border: 1px solid var(--border-color);
        margin-bottom: 24px;
        overflow: hidden;
    }

    .config-header {
        padding: 16px 20px;
        border-bottom: 1px solid var(--border-color);
        display: flex;
        justify-content: space-between;
        align-items: center;
        background: #f8fafc;
        cursor: pointer;
    }

    .config-body {
        padding: 20px;
        display: none; /* Hidden by default */
    }
    
    .config-body.show {
        display: block;
    }

    .config-section-title {
        font-size: 0.85rem;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        color: var(--text-muted);
        font-weight: 700;
        margin-bottom: 12px;
        margin-top: 20px;
    }
    
    .config-section-title:first-child {
        margin-top: 0;
    }

    .notification-option {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 12px 0;
        border-bottom: 1px solid var(--border-color);
    }
    
    .notification-option:last-child {
        border-bottom: none;
    }

    /* Toggle Switch */
    .switch {
        position: relative;
        display: inline-block;
        width: 44px;
        height: 24px;
    }
    
    .switch input {
        opacity: 0;
        width: 0;
        height: 0;
    }
    
    .slider {
        position: absolute;
        cursor: pointer;
        top: 0; left: 0; right: 0; bottom: 0;
        background-color: #cbd5e1;
        transition: .4s;
        border-radius: 24px;
    }
    
    .slider:before {
        position: absolute;
        content: "";
        height: 18px;
        width: 18px;
        left: 3px;
        bottom: 3px;
        background-color: white;
        transition: .4s;
        border-radius: 50%;
    }
    
    input:checked + .slider {
        background-color: var(--primary);
    }
    
    input:checked + .slider:before {
        transform: translateX(20px);
    }

    /* Notification Item Styles (Mantido) */
    .notification-item {
        background: var(--bg-surface);
        border-radius: 12px;
        padding: 16px;
        margin-bottom: 12px;
        border: 1px solid var(--border-color);
        display: flex;
        gap: 16px;
        transition: all 0.2s;
        position: relative;
    }
    
    .notification-item.unread {
        border-left: 4px solid var(--primary);
        background: var(--primary-subtle);
    }
    
    .notification-icon {
        width: 40px; height: 40px;
        border-radius: 10px;
        display: flex; align-items: center; justify-content: center;
        flex-shrink: 0;
        color: white;
    }
    
    .notification-content { flex: 1; }
    
    .notification-title {
        font-weight: 600;
        color: var(--text-main);
        margin-bottom: 4px;
    }
    
    .notification-desc {
        font-size: 0.9rem;
        color: var(--text-muted);
        margin-bottom: 8px;
        line-height: 1.4;
    }
    
    .notification-time {
        font-size: 0.75rem;
        color: var(--text-muted);
        display: flex; align-items: center; gap: 4px;
    }
    
    .empty-state {
        text-align: center;
        padding: 48px 20px;
        color: var(--text-muted);
        background: var(--bg-surface);
        border-radius: 16px;
        border: 1px dashed var(--border-color);
    }
</style>

<div class="notifications-container">
    
    <?php if (isset($success)): ?>
        <div class="alert alert-success" style="padding: 12px; border-radius: 8px; background: #dcfce7; color: #166534; margin-bottom: 20px; border: 1px solid #bbf7d0;">
            <?= $success ?>
        </div>
    <?php endif; ?>
    
    <?php if (isset($error)): ?>
        <div class="alert alert-danger" style="padding: 12px; border-radius: 8px; background: #fee2e2; color: #991b1b; margin-bottom: 20px; border: 1px solid #fecaca;">
            <?= $error ?>
        </div>
    <?php endif; ?>

    <!-- Painel de Gerenciamento (Toggleable) -->
    <div class="config-card">
        <div class="config-header" onclick="toggleConfig()">
            <div style="display: flex; align-items: center; gap: 10px;">
                <i data-lucide="sliders-horizontal" style="color: var(--primary);"></i>
                <span style="font-weight: 600; color: var(--text-main);">Gerenciar Preferências</span>
            </div>
            <i data-lucide="chevron-down" id="configChevron"></i>
        </div>
        
        <div class="config-body" id="configBody">
            <form method="POST">
                <input type="hidden" name="action" value="save_preferences">
                
                <?php foreach ($notificationTypes as $category => $types): ?>
                    <div class="config-section-title"><?= $category ?></div>
                    <?php foreach ($types as $typeKey => $label): ?>
                        <div class="notification-option">
                            <label for="pref_<?= $typeKey ?>" style="cursor: pointer;"><?= $label ?></label>
                            <label class="switch">
                                <input type="checkbox" id="pref_<?= $typeKey ?>" name="prefs[<?= $typeKey ?>]" 
                                    <?= (!isset($userPrefs[$typeKey]) || $userPrefs[$typeKey]) ? 'checked' : '' ?>>
                                <span class="slider"></span>
                            </label>
                        </div>
                    <?php endforeach; ?>
                <?php endforeach; ?>
                
                <div style="margin-top: 20px; text-align: right;">
                    <button type="submit" class="btn btn-primary">Salvar Alterações</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Stats -->
    <div class="stats-grid">
        <div class="stat-card total">
            <div class="stat-value"><?= $stats['total'] ?></div>
            <div class="stat-label">Total</div>
        </div>
        <div class="stat-card unread">
            <div class="stat-value"><?= $stats['unread'] ?></div>
            <div class="stat-label">Não Lidas</div>
        </div>
        <div class="stat-card read">
            <div class="stat-value"><?= $stats['read'] ?></div>
            <div class="stat-label">Lidas</div>
        </div>
    </div>
    
    <!-- Filtros (Simplificado) -->
    <div style="background: var(--bg-surface); padding: 12px; border-radius: 12px; margin-bottom: 24px; border: 1px solid var(--border-color); display: flex; gap: 12px; flex-wrap: wrap;">
        <select onchange="window.location.href='?type='+this.value+'&status=<?= $filterStatus ?>'" 
                style="flex: 1; min-width: 150px; padding: 8px; border-radius: 8px; border: 1px solid var(--border-color);">
            <option value="all" <?= $filterType === 'all' ? 'selected' : '' ?>>Todos os Tipos</option>
            <option value="weekly_report" <?= $filterType === 'weekly_report' ? 'selected' : '' ?>>Relatórios</option>
            <option value="new_escala" <?= $filterType === 'new_escala' ? 'selected' : '' ?>>Escalas</option>
            <option value="new_music" <?= $filterType === 'new_music' ? 'selected' : '' ?>>Músicas</option>
        </select>
        
        <select onchange="window.location.href='?type=<?= $filterType ?>&status='+this.value"
                style="flex: 1; min-width: 150px; padding: 8px; border-radius: 8px; border: 1px solid var(--border-color);">
            <option value="all" <?= $filterStatus === 'all' ? 'selected' : '' ?>>Todas</option>
            <option value="unread" <?= $filterStatus === 'unread' ? 'selected' : '' ?>>Não Lidas</option>
            <option value="read" <?= $filterStatus === 'read' ? 'selected' : '' ?>>Lidas</option>
        </select>
    </div>

    <!-- Lista -->
    <?php if (empty($notifications)): ?>
        <div class="empty-state">
            <i data-lucide="bell-off" style="width: 48px; height: 48px; margin-bottom: 16px; opacity: 0.5;"></i>
            <h3>Nenhuma notificação</h3>
            <p>Você não tem notificações com os filtros selecionados.</p>
        </div>
    <?php else: ?>
        <?php foreach ($notifications as $notif): 
            $config = $notificationSystem->typeConfig[$notif['type']] ?? ['icon' => 'bell', 'color' => '#64748b'];
            $data = is_string($notif['data']) ? json_decode($notif['data'], true) : $notif['data'];
        ?>
            <div class="notification-item <?= !$notif['is_read'] ? 'unread' : '' ?>">
                <div class="notification-icon" style="background: <?= $config['color'] ?>;">
                    <i data-lucide="<?= $config['icon'] ?>" style="width: 20px;"></i>
                </div>
                <div class="notification-content">
                    <div class="notification-title"><?= htmlspecialchars($notif['title']) ?></div>
                    <div class="notification-desc"><?= htmlspecialchars($notif['message']) ?></div>
                    <div class="notification-time">
                        <i data-lucide="clock" style="width: 12px;"></i>
                        <?= date('d/m/Y H:i', strtotime($notif['created_at'])) ?>
                        
                        <?php if ($notif['link']): ?>
                            <a href="<?= $notif['link'] ?>" class="btn-link" style="margin-left: auto; color: var(--primary); font-weight: 500; text-decoration: none;">Ver detalhes</a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>

</div>

<script>
    lucide.createIcons();
    
    function toggleConfig() {
        const body = document.getElementById('configBody');
        const chevron = document.getElementById('configChevron');
        
        if (body.style.display === 'block') {
            body.style.display = 'none';
            chevron.setAttribute('data-lucide', 'chevron-down');
        } else {
            body.style.display = 'block';
            chevron.setAttribute('data-lucide', 'chevron-up');
        }
        lucide.createIcons();
    }
</script>

<?php require_once '../includes/bottom_navigation.php'; ?>
