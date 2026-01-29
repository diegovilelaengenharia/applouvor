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

    <!-- Botão de Ação -->
    <div style="display: flex; justify-content: flex-end; margin-bottom: 24px;">
        <button onclick="openNotificationSettings()" class="btn btn-primary" style="background: var(--bg-surface); color: var(--text-main); border: 1px solid var(--border-color);">
            <i data-lucide="sliders-horizontal" style="width: 18px; margin-right: 8px;"></i>
            Gerenciar Preferências
        </button>
    </div>

    <!-- Container de Status de Notificação (Discreto) -->
    <div id="notificationStatusContainer" style="display: none; margin-bottom: 24px;">
        
        <!-- Banner Ativação (Default) -->
        <div id="statusCardDefault" style="display: none; background: #eff6ff; border: 1px solid #bfdbfe; border-radius: 8px; padding: 12px 16px; color: #1e40af;">
            <div style="display: flex; align-items: center; justify-content: space-between; gap: 16px;">
                <div style="display: flex; align-items: center; gap: 12px;">
                    <i data-lucide="bell" style="width: 20px; color: #3b82f6;"></i>
                    <span style="font-size: 0.9rem; font-weight: 500;">Ative as notificações para não perder nada.</span>
                </div>
                <button id="btnActivatePush" class="ripple" style="
                    background: #3b82f6; color: white; border: none; padding: 6px 16px; border-radius: 6px; 
                    font-size: 0.85rem; font-weight: 600; cursor: pointer; white-space: nowrap;
                    transition: background 0.2s;
                ">
                    Ativar Agora
                </button>
            </div>
        </div>

        <!-- Banner Ativo (Granted) -->
        <div id="statusCardGranted" style="display: none; background: #f0fdf4; border: 1px solid #bbf7d0; border-radius: 8px; padding: 12px 16px; color: #166534;">
            <div style="display: flex; align-items: center; gap: 12px;">
                <i data-lucide="check" style="width: 18px; color: #10b981;"></i>
                <span style="font-size: 0.9rem; font-weight: 500;">Notificações ativadas neste dispositivo.</span>
            </div>
        </div>

        <!-- Banner Bloqueado (Denied) -->
        <div id="statusCardDenied" style="display: none; background: #fef2f2; border: 1px solid #fecaca; border-radius: 8px; padding: 12px 16px; color: #991b1b;">
            <div style="display: flex; align-items: center; gap: 12px;">
                <i data-lucide="bell-off" style="width: 18px; color: #ef4444;"></i>
                <span style="font-size: 0.9rem;">Notificações bloqueadas. <span style="font-weight: 600;">Clique no cadeado 🔒</span> na barra de endereço para liberar.</span>
            </div>
        </div>

        <!-- Banner Não Suportado -->
        <div id="statusCardUnsupported" style="display: none; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 12px 16px; color: #64748b;">
            <div style="display: flex; align-items: center; gap: 12px;">
                <i data-lucide="info" style="width: 18px;"></i>
                <span style="font-size: 0.9rem;">Push notifications indisponíveis neste navegador.</span>
            </div>
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

<!-- Modal de Preferências -->
<div id="notificationSettingsModal" style="display: none;">
    <div style="position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.7); backdrop-filter: blur(4px); z-index: 9999; display: flex; align-items: center; justify-content: center; animation: fadeIn 0.2s;">
        <div style="background: var(--bg-surface); width: 90%; max-width: 600px; max-height: 90vh; border-radius: 20px; overflow: hidden; box-shadow: 0 25px 50px -12px rgba(0,0,0,0.25); display: flex; flex-direction: column;">
            
            <div style="padding: 24px; border-bottom: 1px solid var(--border-color); display: flex; align-items: center; justify-content: space-between;">
                <h2 style="margin: 0; font-size: 1.25rem; font-weight: 700; color: var(--text-main);">⚙️ Configurar Notificações</h2>
                <button onclick="closeNotificationSettings()" style="background: none; border: none; cursor: pointer; color: var(--text-muted); padding: 8px;">
                    <i data-lucide="x" style="width: 20px;"></i>
                </button>
            </div>
            
            <div style="padding: 20px; overflow-y: auto; flex: 1;">
                <form id="notificationPrefsForm" method="POST">
                    <input type="hidden" name="action" value="save_preferences">
                    
                    <?php foreach ($notificationTypes as $category => $types): ?>
                        <div style="margin-bottom: 24px;">
                            <div style="font-size: 0.75rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; color: var(--text-muted); margin-bottom: 12px;"><?= $category ?></div>
                            <div style="background: var(--bg-body); border: 1px solid var(--border-color); border-radius: 12px; overflow: hidden;">
                                <?php foreach ($types as $typeKey => $label): ?>
                                    <div style="display: flex; align-items: center; justify-content: space-between; padding: 12px 16px; border-bottom: 1px solid var(--border-color);">
                                        <label for="pref_<?= $typeKey ?>" style="cursor: pointer; font-weight: 500; font-size: 0.95rem; color: var(--text-main); flex: 1;"><?= $label ?></label>
                                        <label class="switch">
                                            <input type="checkbox" id="pref_<?= $typeKey ?>" name="prefs[<?= $typeKey ?>]" 
                                                <?= (!isset($userPrefs[$typeKey]) || $userPrefs[$typeKey]) ? 'checked' : '' ?>>
                                            <span class="slider"></span>
                                        </label>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </form>
            </div>
            
            <div style="padding: 20px; border-top: 1px solid var(--border-color); display: flex; gap: 12px; justify-content: flex-end;">
                <button class="btn btn-secondary" onclick="closeNotificationSettings()">Cancelar</button>
                <button class="btn btn-primary" onclick="document.getElementById('notificationPrefsForm').submit()">Salvar Alterações</button>
            </div>
            
        </div>
    </div>
</div>

<script>
    lucide.createIcons();
    
    function openNotificationSettings() {
        document.getElementById('notificationSettingsModal').style.display = 'block';
    }
    
    function closeNotificationSettings() {
        const modal = document.getElementById('notificationSettingsModal');
        modal.querySelector('div[style*="position: fixed"]').style.animation = 'fadeOut 0.2s';
        setTimeout(() => {
            modal.style.display = 'none';
            modal.querySelector('div[style*="position: fixed"]').style.animation = 'fadeIn 0.2s';
        }, 200);
    }
    
    // Configurações de estilo para animation
    const style = document.createElement('style');
    style.innerHTML = `
        @keyframes fadeIn { from { opacity: 0; } to { opacity: 1; } }
        @keyframes fadeOut { from { opacity: 1; } to { opacity: 0; } }
        .switch input:checked + .slider { background-color: var(--primary); }
    `;
    document.head.appendChild(style);
</script>

<!-- Script de Controle de Status de Notificação -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    const container = document.getElementById('notificationStatusContainer');
    const cardDefault = document.getElementById('statusCardDefault');
    const cardGranted = document.getElementById('statusCardGranted');
    const cardDenied = document.getElementById('statusCardDenied');
    const cardUnsupported = document.getElementById('statusCardUnsupported');

    if (!container) return;

    function updateNotificationStatus() {
        container.style.display = 'block';
        
        // Esconder todos primeiro
        if(cardDefault) cardDefault.style.display = 'none';
        if(cardGranted) cardGranted.style.display = 'none';
        if(cardDenied) cardDenied.style.display = 'none';
        if(cardUnsupported) cardUnsupported.style.display = 'none';

        if (!('Notification' in window) || !('serviceWorker' in navigator)) {
            if(cardUnsupported) cardUnsupported.style.display = 'block';
            return;
        }

        if (Notification.permission === 'granted') {
            if(cardGranted) cardGranted.style.display = 'block';
            if (typeof ensurePushSubscription === 'function') {
                ensurePushSubscription();
            }
        } else if (Notification.permission === 'denied') {
            if(cardDenied) cardDenied.style.display = 'block';
        } else { // default
            if(cardDefault) cardDefault.style.display = 'block';
        }
    }

    updateNotificationStatus();
    
    if (navigator.permissions && navigator.permissions.query) {
        navigator.permissions.query({name: 'notifications'}).then(function(permissionStatus) {
            permissionStatus.onchange = updateNotificationStatus;
        });
    }
});
</script>


