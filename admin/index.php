<?php
// admin/index.php
require_once '../includes/auth.php';
require_once '../includes/db.php';
require_once '../includes/layout.php';

$userId = $_SESSION['user_id'] ?? 1;
// Detectar Perfil para Filtro de Avisos
$userRole = $_SESSION['user_role'] ?? 'user';
$audienceFilter = ($userRole === 'admin') 
    ? "('all', 'admins', 'team', 'leaders')"
    : "('all', 'team')"; 

// --- DADOS REAIS ---
// 1. Avisos
$avisos = [];
$popupAviso = null;
$unreadCount = 0;

try {
    $stmt = $pdo->prepare("
        SELECT * FROM avisos 
        WHERE archived_at IS NULL 
        AND (expires_at IS NULL OR expires_at >= CURDATE())
        AND target_audience IN $audienceFilter
        ORDER BY priority='urgent' DESC, created_at DESC
        LIMIT 5
    ");
    $stmt->execute();
    $avisos = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $totalAvisos = count($avisos);
    $ultimoAviso = $avisos[0]['title'] ?? 'Nenhum aviso novo';
    
    foreach ($avisos as $av) {
        if ($av['priority'] === 'urgent') {
            $popupAviso = $av;
            break; 
        }
    }
    
    $stmtCountRecent = $pdo->prepare("
        SELECT COUNT(*) FROM avisos 
        WHERE archived_at IS NULL 
        AND (expires_at IS NULL OR expires_at >= CURDATE())
        AND target_audience IN $audienceFilter
        AND created_at > DATE_SUB(NOW(), INTERVAL 3 DAY)
    ");
    $stmtCountRecent->execute();
    $unreadCount = $stmtCountRecent->fetchColumn();

} catch (Exception $e) {
    $totalAvisos = 0;
    $ultimoAviso = '';
}

// 2. Escalas
$nextSchedule = null;
$totalSchedules = 0;
try {
    $stmt = $pdo->prepare("
        SELECT s.* 
        FROM schedules s
        JOIN schedule_users su ON s.id = su.schedule_id
        WHERE su.user_id = ? AND s.event_date >= CURDATE()
        ORDER BY s.event_date ASC
        LIMIT 1
    ");
    $stmt->execute([$userId]);
    $nextSchedule = $stmt->fetch(PDO::FETCH_ASSOC);
    
    $stmtCount = $pdo->prepare("
        SELECT COUNT(*) 
        FROM schedules s
        JOIN schedule_users su ON s.id = su.schedule_id
        WHERE su.user_id = ? AND s.event_date >= CURDATE()
    ");
    $stmtCount->execute([$userId]);
    $totalSchedules = $stmtCount->fetchColumn();
} catch (Exception $e) {
}

// 3. Repertório
$totalMusicas = 0;
$ultimaMusica = null;
try {
    $stmt = $pdo->query("SELECT COUNT(*) FROM songs");
    $totalMusicas = $stmt->fetchColumn();
    
    $stmt = $pdo->query("SELECT title, artist FROM songs ORDER BY created_at DESC LIMIT 1");
    $ultimaMusica = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (Exception $e) {
}

// 4. Aniversariantes
$aniversariantes = [];
try {
    $stmt = $pdo->query("SELECT name, DAY(birth_date) as dia, avatar FROM users WHERE MONTH(birth_date) = MONTH(CURRENT_DATE()) ORDER BY dia ASC");
    $aniversariantes = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $niverCount = count($aniversariantes);
} catch (Exception $e) {
    $niverCount = 0;
}

// Saudação
$hora = date('H');
if ($hora >= 5 && $hora < 12) {
    $saudacao = "Bom dia";
} elseif ($hora >= 12 && $hora < 18) {
    $saudacao = "Boa tarde";
} else {
    $saudacao = "Boa noite";
}
$nomeUser = explode(' ', $_SESSION['user_name'])[0];

// 5. Buscar configurações de dashboard do usuário
require_once '../includes/dashboard_cards.php';
$userDashboardSettings = [];
try {
    $stmt = $pdo->prepare("
        SELECT card_id, is_visible, display_order 
        FROM user_dashboard_settings 
        WHERE user_id = ? AND is_visible = TRUE
        ORDER BY display_order ASC
    ");
    $stmt->execute([$userId]);
    $userDashboardSettings = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Se não houver configurações, usar padrão
    if (empty($userDashboardSettings)) {
        $defaultCards = ['escalas', 'repertorio', 'leitura', 'avisos', 'aniversariantes', 'devocional', 'oracao'];
        foreach ($defaultCards as $index => $cardId) {
            $userDashboardSettings[] = [
                'card_id' => $cardId,
                'is_visible' => true,
                'display_order' => $index + 1
            ];
        }
    }
} catch (Exception $e) {
    // Fallback para cards padrão em caso de erro
    $defaultCards = ['escalas', 'repertorio', 'leitura', 'avisos', 'aniversariantes', 'devocional', 'oracao'];
    foreach ($defaultCards as $index => $cardId) {
        $userDashboardSettings[] = [
            'card_id' => $cardId,
            'is_visible' => true,
            'display_order' => $index + 1
        ];
    }
}

// Obter definições completas dos cards
$allCardsDefinitions = getAllAvailableCards();

renderAppHeader('Visão Geral');
?>

<!-- MODAL URGENTE AUTOMÁTICO -->
<?php if ($popupAviso): ?>
<div id="urgentModal" style="
    display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; 
    z-index: 2000; background: rgba(0,0,0,0.8); backdrop-filter: blur(4px);
    align-items: center; justify-content: center;
">
    <div style="
        background: #fff; width: 90%; max-width: 400px; border-radius: 20px; 
        padding: 24px; position: relative; text-align: center;
        box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
        border-top: 6px solid #ef4444;
    ">
        <div style="background: #fee2e2; color: #dc2626; width: 50px; height: 50px; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 16px auto;">
            <i data-lucide="alert-triangle" width="24"></i>
        </div>
        <h3 style="margin: 0 0 8px 0; font-size: var(--font-h1); font-weight: 800; color: #1f2937;">Aviso Urgente</h3>
        <p style="margin: 0 0 16px 0; font-size: var(--font-h3); font-weight: 700; color: #374151;">
            <?= htmlspecialchars($popupAviso['title']) ?>
        </p>
        <div style="text-align: left; background: #f9fafb; padding: 12px; border-radius: 8px; font-size: var(--font-body); color: #4b5563; margin-bottom: 20px; max-height: 200px; overflow-y: auto;">
            <?= $popupAviso['message'] ?>
        </div>
        <button onclick="closeUrgentModal()" style="
            width: 100%; padding: 12px; background: #ef4444; color: white; border: none; border-radius: 12px;
            font-weight: 700; font-size: var(--font-h3); cursor: pointer;
        ">
            Entendido
        </button>
    </div>
</div>
<script>
    document.addEventListener('DOMContentLoaded', () => {
        if (!sessionStorage.getItem('seen_urgent_<?= $popupAviso['id'] ?>')) {
            document.getElementById('urgentModal').style.display = 'flex';
        }
    });
    function closeUrgentModal() {
        document.getElementById('urgentModal').style.display = 'none';
        sessionStorage.setItem('seen_urgent_<?= $popupAviso['id'] ?>', 'true');
    }
</script>
<?php endif; ?>

<style>
    /* Hero Section */
    .hero-section {
        background: linear-gradient(135deg, #10b981 0%, #34d399 100%);
        padding: 24px 20px;
        margin: -20px -20px 20px -20px;
        color: white;
        border-radius: 0 0 20px 20px;
        box-shadow: 0 2px 8px rgba(16, 185, 129, 0.12);
    }

    .hero-greeting {
        font-size: var(--font-h2);
        font-weight: 700;
        margin: 0;
        letter-spacing: -0.01em;
    }

    .hero-subtitle {
        font-size: var(--font-body);
        opacity: 0.95;
        font-weight: 500;
    }

    /* Quick Access Grid */
    .quick-access-grid {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 12px;
        margin-bottom: 24px;
    }

    .access-card {
        position: relative;
        padding: 16px;
        border-radius: 16px;
        text-decoration: none;
        color: #1f2937;
        overflow: hidden;
        transition: all 0.3s cubic-bezier(0.16, 1, 0.3, 1);
        box-shadow: 0 1px 4px rgba(0, 0, 0, 0.06);
        min-height: 110px;
        display: flex;
        flex-direction: column;
        justify-content: space-between;
    }

    .access-card::before {
        content: '';
        position: absolute;
        top: 0;
        left: -100%;
        width: 100%;
        height: 100%;
        background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.3), transparent);
        transition: left 0.5s;
    }

    .access-card:active {
        transform: scale(0.97);
    }

    .access-card:hover::before {
        left: 100%;
    }

    /* Cores seguindo padrão da barra de navegação */
    /* GESTÃO (Verde) - Escalas, Repertório */
    .card-blue { 
        background: linear-gradient(135deg, #ecfdf5 0%, #d1fae5 100%);
        border: 1px solid #6ee7b7;
    }
    .card-purple { 
        background: linear-gradient(135deg, #ecfdf5 0%, #d1fae5 100%);
        border: 1px solid #6ee7b7;
    }
    /* ESPÍRITO (Índigo) - Leitura, Devocional, Oração */
    .card-green { 
        background: linear-gradient(135deg, #eef2ff 0%, #e0e7ff 100%);
        border: 1px solid #a5b4fc;
    }
    .card-cyan { 
        background: linear-gradient(135deg, #eef2ff 0%, #e0e7ff 100%);
        border: 1px solid #a5b4fc;
    }
    .card-violet { 
        background: linear-gradient(135deg, #eef2ff 0%, #e0e7ff 100%);
        border: 1px solid #a5b4fc;
    }
    /* COMUNICA (Laranja) - Avisos, Aniversariantes */
    .card-orange { 
        background: linear-gradient(135deg, #fff7ed 0%, #fed7aa 100%);
        border: 1px solid #fdba74;
    }
    .card-pink { 
        background: linear-gradient(135deg, #fff7ed 0%, #fed7aa 100%);
        border: 1px solid #fdba74;
    }

    .card-icon {
        width: 40px;
        height: 40px;
        background: rgba(255, 255, 255, 0.5);
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        margin-bottom: 10px;
        backdrop-filter: blur(10px);
        border: 1px solid rgba(255, 255, 255, 0.8);
    }

    .card-icon i {
        color: currentColor;
    }

    .card-blue .card-icon { color: #047857; }
    .card-purple .card-icon { color: #047857; }
    .card-green .card-icon { color: #4338ca; }
    .card-orange .card-icon { color: #ea580c; }
    .card-pink .card-icon { color: #ea580c; }
    .card-cyan .card-icon { color: #4338ca; }
    .card-violet .card-icon { color: #4338ca; }

    .card-title {
        font-size: var(--font-body);
        font-weight: 700;
        margin: 0 0 4px 0;
        letter-spacing: -0.01em;
    }

    .card-info {
        font-size: var(--font-body-sm);
        opacity: 0.75;
        margin: 0;
        line-height: 1.3;
    }

    .card-badge {
        position: absolute;
        top: 12px;
        right: 12px;
        background: rgba(255, 255, 255, 0.9);
        padding: 3px 8px;
        border-radius: 10px;
        font-size: var(--font-caption);
        font-weight: 700;
        border: 1px solid rgba(0, 0, 0, 0.1);
        color: inherit;
    }

    .section-title {
        font-size: var(--font-h3);
        font-weight: 700;
        color: var(--text-main);
        margin: 0 0 12px 0;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    .section-action {
        font-size: var(--font-body-sm);
        color: var(--primary);
        text-decoration: none;
        font-weight: 600;
    }

    .highlight-card {
        background: var(--bg-surface);
        border-radius: 14px;
        padding: 14px;
        border: 1px solid var(--border-color);
        box-shadow: var(--shadow-sm);
        margin-bottom: 10px;
        display: flex;
        align-items: center;
        gap: 12px;
        text-decoration: none;
        color: inherit;
        transition: all 0.2s;
    }

    .highlight-card:active {
        transform: scale(0.98);
    }

    .highlight-icon {
        width: 44px;
        height: 44px;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
    }

    @media (max-width: 375px) {
        .quick-access-grid { gap: 10px; }
        .access-card { min-height: 100px; padding: 14px; }
    }
</style>

<?php renderPageHeader('Visão Geral', 'Acesso rápido às suas atividades'); ?>

<div style="max-width: 600px; margin: 0 auto;">


    <!-- QUICK ACCESS GRID -->
    <div class="quick-access-grid">
        
        <?php
        // Renderizar cards dinamicamente conforme configurações do usuário
        require_once '../includes/dashboard_render.php';
        
        // Preparar dados para renderização
        $renderData = [
            'pdo' => $pdo,
            'userId' => $userId,
            'nextSchedule' => $nextSchedule,
            'totalSchedules' => $totalSchedules,
            'ultimaMusica' => $ultimaMusica,
            'totalMusicas' => $totalMusicas,
            'ultimoAviso' => $ultimoAviso,
            'unreadCount' => $unreadCount,
            'niverCount' => $niverCount
        ];
        
        // Loop pelos cards configurados pelo usuário
        foreach ($userDashboardSettings as $setting) {
            renderDashboardCard($setting['card_id'], $renderData);
        }
        ?>
        
        <!-- Card: Oração -->
        <a href="oracao.php" class="access-card card-violet">
            <div>
                <div class="card-icon">
                    <i data-lucide="heart" style="width: 24px; height: 24px;"></i>
                </div>
                <h3 class="card-title">Oração</h3>
                <p class="card-info">Pedidos e intercessão</p>
            </div>
        </a>

    </div>


<!-- Script de Personalização de Dashboard -->
<script src="js/dashboard-customization.js"></script>

<?php renderAppFooter(); ?>