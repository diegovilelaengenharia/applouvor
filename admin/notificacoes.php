<?php
// admin/notificacoes.php
require_once '../src/helpers/auth.php';
require_once '../src/config/db.php';
require_once '../src/layout/layout.php';
require_once '../src/helpers/notification_system.php';

$userId = $_SESSION['user_id'];
$notificationSystem = new NotificationSystem($pdo);

// Processar Salvamento de Preferências
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'save_preferences') {
    // Validação CSRF
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        http_response_code(403);
        die('Ação não autorizada. Por favor, recarregue a página e tente novamente.');
    }
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
    ORDER BY is_read ASC, created_at DESC 
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



<div class="notifications-container">
    
    <?php if (isset($success)): ?>
        <div class="alert alert-success" style="padding: 12px; border-radius: 8px; background: var(--sage-100); color: var(--sage-800); margin-bottom: 20px; border: 1px solid var(--sage-200);">
            <?= $success ?>
        </div>
    <?php endif; ?>
    
    <?php if (isset($error)): ?>
        <div class="alert alert-danger" style="padding: 12px; border-radius: 8px; background: var(--rose-100); color: var(--rose-700); margin-bottom: 20px; border: 1px solid var(--rose-200);">
            <?= $error ?>
        </div>
    <?php endif; ?>

    <!-- Botões de Ação -->
    <div style="display: flex; justify-content: flex-end; gap: 12px; margin-bottom: 24px; flex-wrap: wrap;">
        <!-- Botão para TODOS os usuários -->
        <button onclick="deleteAllNotifications()" class="btn" style="background: var(--yellow-500); color: white; border: none; display: flex; align-items: center; gap: 8px; padding: 8px 16px; border-radius: 8px; cursor: pointer; font-weight: 600;">
            <i data-lucide="trash-2" style="width: 16px;"></i>
            Apagar Todas
        </button>
        
        <!-- Botões apenas para ADMIN -->
        <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
        <button onclick="clearDatabaseAdmin()" class="btn" style="background: var(--rose-600); color: white; border: none; display: flex; align-items: center; gap: 8px; padding: 8px 16px; border-radius: 8px; cursor: pointer; font-weight: 600;">
            <i data-lucide="database" style="width: 16px;"></i>
            Limpar Banco
        </button>
        <button onclick="openViewsModal()" class="btn" style="background: #14b8a6; color: white; border: none; display: flex; align-items: center; gap: 8px; padding: 8px 16px; border-radius: 8px; cursor: pointer; font-weight: 600;">
            <i data-lucide="users" style="width: 16px;"></i>
            Visualizações
        </button>
        <?php endif; ?>
        
        <button onclick="openNotificationSettings()" class="btn btn-primary" style="background: var(--bg-surface); color: var(--text-main); border: 1px solid var(--border-color); display: flex; align-items: center; gap: 8px; padding: 8px 16px; border-radius: 8px; cursor: pointer; font-weight: 600;">
            <i data-lucide="sliders-horizontal" style="width: 18px;"></i>
            Gerenciar Preferências
        </button>
    </div>

    <!-- Container de Status de Notificação (Discreto) -->
    <div id="notificationStatusContainer" style="display: none; margin-bottom: 24px;">
        
        <!-- Banner Ativação (Default) -->
        <div id="statusCardDefault" style="display: none; background: var(--slate-50); border: 1px solid #bfdbfe; border-radius: 8px; padding: 12px 16px; color: var(--slate-700);">
            <div style="display: flex; align-items: center; justify-content: space-between; gap: 16px;">
                <div style="display: flex; align-items: center; gap: 12px;">
                    <i data-lucide="bell" style="width: 20px; color: var(--slate-500);"></i>
                    <span style="font-size: 0.9rem; font-weight: 500;">Ative as notificações para não perder nada.</span>
                </div>
                <button id="btnActivatePush" class="ripple" style="
                    background: var(--slate-500); color: white; border: none; padding: 6px 16px; border-radius: 6px; 
                    font-size: 0.85rem; font-weight: 600; cursor: pointer; white-space: nowrap;
                    transition: background 0.2s;
                ">
                    Ativar Agora
                </button>
            </div>
        </div>

        <!-- Banner Ativo (Granted) -->
        <div id="statusCardGranted" style="display: none; background: #ecfdf5; border: 1px solid #10b981; border-radius: 12px; padding: 16px; color: #065f46; box-shadow: 0 4px 6px -1px rgba(16, 185, 129, 0.1), 0 2px 4px -1px rgba(16, 185, 129, 0.06); animation: pulse-green 2s infinite;">
            <div style="display: flex; align-items: center; gap: 12px;">
                <div style="background: #10b981; color: white; padding: 6px; border-radius: 50%; display: flex; align-items: center; justify-content: center;">
                    <i data-lucide="check" style="width: 20px; height: 20px;"></i>
                </div>
                <div>
                    <span style="font-size: 1rem; font-weight: 700; display: block;">Notificações Ativadas!</span>
                    <span style="font-size: 0.85rem; opacity: 0.9;">Você receberá alertas neste dispositivo.</span>
                </div>
            </div>
        </div>
        

        <!-- Banner Bloqueado (Denied) -->
        <div id="statusCardDenied" style="display: none; background: var(--rose-50); border: 1px solid var(--rose-200); border-radius: 8px; padding: 12px 16px; color: var(--rose-700);">
            <div style="display: flex; align-items: center; gap: 12px;">
                <i data-lucide="bell-off" style="width: 18px; color: var(--rose-500);"></i>
                <span style="font-size: 0.9rem;">Notificações bloqueadas. <span style="font-weight: 600;">Clique no cadeado 🔒</span> na barra de endereço para liberar.</span>
            </div>
        </div>

        <!-- Banner Não Suportado -->
        <div id="statusCardUnsupported" style="display: none; background: var(--slate-50); border: 1px solid var(--slate-200); border-radius: 8px; padding: 12px 16px; color: var(--slate-500);">
            <div style="display: flex; align-items: center; gap: 12px;">
                <i data-lucide="info" style="width: 18px;"></i>
                <span style="font-size: 0.9rem;">Push notifications indisponíveis neste navegador.</span>
            </div>
        </div>
    </div>

    <!-- Stats -->
    <div class="stats-grid">
        <div class="stat-card total">
            <div class="stat-value" id="stat-total"><?= (int)$stats['total'] ?></div>
            <div class="stat-label">Total</div>
        </div>
        <div class="stat-card unread">
            <div class="stat-value" id="stat-unread"><?= (int)$stats['unread'] ?></div>
            <div class="stat-label">Não Lidas</div>
        </div>
        <div class="stat-card read">
            <div class="stat-value" id="stat-read"><?= (int)$stats['read'] ?></div>
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
            $config = $notificationSystem->typeConfig[$notif['type']] ?? ['icon' => 'bell', 'color' => 'var(--slate-500)'];
            $data = is_string($notif['data']) ? json_decode($notif['data'], true) : $notif['data'];
        ?>
            <div class="notification-item <?= !$notif['is_read'] ? 'unread' : '' ?>" data-id="<?= $notif['id'] ?>">
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
                            <?php 
                                $jsNotif = [
                                    'id' => $notif['id'],
                                    'title' => $notif['title'],
                                    'message' => $notif['message'],
                                    'created_at' => $notif['created_at'],
                                    'link' => $notif['link'],
                                    'is_read' => $notif['is_read'],
                                    'config' => $config
                                ];
                            ?>
                            <a href="javascript:void(0)" onclick='openNotificationModal(<?= json_encode($jsNotif) ?>)' class="btn-link" style="margin-left: auto; color: var(--primary); font-weight: 500; text-decoration: none;">Ver detalhes</a>
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
                    <?= App\AuthMiddleware::csrfField() ?>
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
                <button class="btn btn-secondary" onclick="closeNotificationSettings()" style="background: var(--slate-100); color: var(--slate-700); border: 1px solid var(--slate-200);">Cancelar</button>
                <button class="btn btn-primary" onclick="document.getElementById('notificationPrefsForm').submit()" style="background: var(--green-600); color: white; border: none; font-weight: 600;">Salvar Alterações</button>
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
            // Se já está permitido, não mostra nada (limpo)
            // Se precisar mostrar, o usuário pode ir em Preferências
            if(cardGranted) cardGranted.style.display = 'none';

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

    // Controle de Status com Listener Limpo
    const btnActivate = document.getElementById('btnActivatePush');
    if (btnActivate) {
        btnActivate.addEventListener('click', function(e) {
            e.preventDefault();
            console.log('Botão Ativar Clicado'); // Debug
            
            if (typeof requestNotificationPermission === 'function') {
                requestNotificationPermission();
            } else {
                alert('Erro: Função de ativação não encontrada. Recarregue a página.');
                console.error('requestNotificationPermission is not defined');
            }
        });
    }
    
    // Função global para solicitar permissão - Definida antes de qualquer uso
    window.requestNotificationPermission = function() {
        console.log('requestNotificationPermission chamada');
        if (!('Notification' in window)) {
            alert('Seu navegador não suporta notificações.');
            return;
        }

        Notification.requestPermission().then(function(permission) {
            console.log('Permissão:', permission);
            if (permission === 'granted') {
                // Atualizar UI se a função existir
                if (typeof updateNotificationStatus === 'function') {
                    updateNotificationStatus();
                } else {
                    location.reload();
                }
                
                // Feedback visual
                const btn = document.getElementById('btnActivatePush');
                if(btn) {
                    btn.innerText = 'Ativado!';
                    btn.style.background = 'var(--sage-500)';
                    setTimeout(() => btn.remove(), 2000);
                }
            } else {
                if (typeof updateNotificationStatus === 'function') {
                    updateNotificationStatus();
                }
                alert('Permissão negada. Você precisa habilitar manualmente nas configurações do navegador.');
            }
        });
    };

    if (navigator.permissions && navigator.permissions.query) {
        navigator.permissions.query({name: 'notifications'}).then(function(permissionStatus) {
            permissionStatus.onchange = updateNotificationStatus;
        });
    }
});



// Função para apagar todas as notificações
async function deleteAllNotifications() {
    if (!confirm('Tem certeza que deseja apagar TODAS as notificações? Esta ação não pode ser desfeita.')) {
        return;
    }
    
    try {
        const response = await fetch('notifications_api.php?action=delete_all', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' }
        });
        
        const data = await response.json();
        
        if (data.success) {
            alert(`${data.count} notificação(ões) apagada(s) com sucesso!`);
            location.reload();
        } else {
            alert('Erro ao apagar notificações: ' + data.message);
        }
    } catch (error) {
        console.error('Erro:', error);
        alert('Erro ao apagar notificações');
    }
}

// Função ADMIN para limpar TODO o banco de dados
async function clearDatabaseAdmin() {
    if (!confirm('⚠️ ATENÇÃO: Você está prestes a APAGAR TODAS AS NOTIFICAÇÕES DE TODOS OS USUÁRIOS do banco de dados!\n\nEsta ação é IRREVERSÍVEL e afetará TODOS os membros.\n\nTem certeza absoluta?')) {
        return;
    }
    
    if (!confirm('Última confirmação: APAGAR TUDO?')) {
        return;
    }
    
    try {
        const response = await fetch('notifications_api.php?action=clear_database_admin', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' }
        });
        
        const data = await response.json();
        
        if (data.success) {
            alert(`✅ Banco de dados limpo com sucesso!\n${data.count} notificação(ões) removida(s).`);
            location.reload();
        } else {
            alert('❌ Erro: ' + data.message);
        }
    } catch (error) {
        console.error('Erro:', error);
        alert('❌ Erro ao limpar banco de dados');
    }
}

// Função para abrir modal de visualizações
function openViewsModal() {
    // Criar modal dinamicamente
    const modal = document.createElement('div');
    modal.id = 'viewsModal';
    modal.style.cssText = `
        position: fixed; top: 0; left: 0; right: 0; bottom: 0;
        background: rgba(0,0,0,0.5); display: flex; align-items: center;
        justify-content: center; z-index: 9999; padding: 20px;
    `;
    
    modal.innerHTML = `
        <div style="background: white; border-radius: 12px; max-width: 600px; width: 100%; max-height: 80vh; overflow: auto; padding: 24px;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                <h3 style="margin: 0; font-size: 1.25rem; font-weight: 700;">Visualizações de Notificações</h3>
                <button onclick="closeViewsModal()" style="background: none; border: none; cursor: pointer; font-size: 24px; color: var(--slate-500);">&times;</button>
            </div>
            <div id="viewsContent" style="color: var(--slate-500);">
                <p>Selecione uma notificação da lista abaixo para ver quem visualizou:</p>
                <div id="notificationsList"></div>
            </div>
        </div>
    `;
    
    document.body.appendChild(modal);
    loadNotificationsForViews();
}

function closeViewsModal() {
    const modal = document.getElementById('viewsModal');
    if (modal) modal.remove();
}

async function loadNotificationsForViews() {
    try {
        const response = await fetch('notifications_api.php?action=list&limit=50');
        const data = await response.json();
        
        if (data.success && data.notifications.length > 0) {
            const list = document.getElementById('notificationsList');
            list.innerHTML = data.notifications.map(notif => `
                <div onclick="loadViewsForNotification('${notif.title.replace(/'/g, "\\'")}', '${notif.created_at}')" 
                     style="padding: 12px; border: 1px solid var(--slate-200); border-radius: 8px; margin-bottom: 8px; cursor: pointer; transition: background 0.2s;"
                     onmouseover="this.style.background='var(--slate-50)'" onmouseout="this.style.background='white'">
                    <div style="font-weight: 600; color: var(--slate-800);">${notif.title}</div>
                    <div style="font-size: 0.85rem; color: var(--slate-500);">${new Date(notif.created_at).toLocaleString('pt-BR')}</div>
                </div>
            `).join('');
        } else {
            document.getElementById('notificationsList').innerHTML = '<p style="color: var(--slate-400);">Nenhuma notificação encontrada.</p>';
        }
    } catch (error) {
        console.error('Erro:', error);
        document.getElementById('notificationsList').innerHTML = '<p style="color: var(--rose-500);">Erro ao carregar notificações</p>';
    }
}

async function loadViewsForNotification(title, createdAt) {
    const content = document.getElementById('viewsContent');
    content.innerHTML = '<p style="text-align: center; color: var(--slate-500);">Carregando...</p>';
    
    try {
        const response = await fetch(`notifications_api.php?action=get_notification_views&title=${encodeURIComponent(title)}&created_at=${encodeURIComponent(createdAt)}`);
        const data = await response.json();
        
        if (data.success && data.readers.length > 0) {
            const readCount = data.readers.filter(r => r.is_read == 1).length;
            const unreadCount = data.readers.filter(r => r.is_read == 0).length;
            
            content.innerHTML = `
                <div style="margin-bottom: 16px;">
                    <h4 style="margin: 0 0 8px 0; font-size: 1rem; font-weight: 600; color: var(--slate-800);">${title}</h4>
                    <div style="display: flex; gap: 16px; margin-bottom: 16px;">
                        <span style="color: var(--sage-500); font-weight: 600;">✓ ${readCount} leram</span>
                        <span style="color: var(--rose-500); font-weight: 600;">✗ ${unreadCount} não leram</span>
                    </div>
                </div>
                <div style="max-height: 400px; overflow-y: auto;">
                    ${data.readers.map(reader => `
                        <div style="padding: 10px; border-bottom: 1px solid var(--slate-200); display: flex; justify-content: space-between; align-items: center;">
                            <div>
                                <div style="font-weight: 600; color: var(--slate-800);">${reader.name}</div>
                                <div style="font-size: 0.85rem; color: var(--slate-500);">${reader.email}</div>
                            </div>
                            <div style="text-align: right;">
                                ${reader.is_read == 1 
                                    ? `<span style="color: var(--sage-500); font-weight: 600;">✓ Lida</span><br><span style="font-size: 0.75rem; color: var(--slate-500);">${reader.read_at ? new Date(reader.read_at).toLocaleString('pt-BR') : ''}</span>`
                                    : `<span style="color: var(--rose-500); font-weight: 600;">✗ Não lida</span>`
                                }
                            </div>
                        </div>
                    `).join('')}
                </div>
                <button onclick="loadNotificationsForViews()" style="margin-top: 16px; padding: 8px 16px; background: var(--slate-500); color: white; border: none; border-radius: 6px; cursor: pointer;">
                    ← Voltar para lista
                </button>
            `;
        } else {
            content.innerHTML = '<p style="color: var(--slate-400);">Nenhum dado de visualização encontrado.</p>';
        }
    } catch (error) {
        console.error('Erro:', error);
        content.innerHTML = '<p style="color: var(--rose-500);">Erro ao carregar visualizações</p>';
    }
}


</script>

<?php
renderAppFooter();
?>


