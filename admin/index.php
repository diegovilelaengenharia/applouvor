<?php
// admin/index.php
header('Content-Type: text/html; charset=utf-8');
require_once '../includes/auth.php';
require_once '../includes/db.php';
require_once '../includes/layout.php';

// CRÍTICO: Carregar definições de cards ANTES de tudo
require_once '../includes/dashboard_cards.php';

// Carregar dados da Dashboard (Controller)
$dashboardData = require 'dashboard_data.php';
extract($dashboardData);

// Obter definições completas dos cards
$allCardsDefinitions = getAllAvailableCards();

renderAppHeader('Página Inicial');
?>

<!-- VERSÃO ATUALIZADA V2.1 - PÁGINA INICIAL -->
<link rel="stylesheet" href="<?= APP_URL ?>/assets/css/pages/dashboard.css?v=<?= time() ?>">
<link rel="stylesheet" href="<?= APP_URL ?>/assets/css/components/dashboard-cards.css?v=<?= time() ?>">

<!-- MODAL URGENTE AUTOMÁTICO -->
<?php if ($popupAviso): ?>
<div id="urgentModal" class="urgent-modal-overlay">
    <div class="urgent-modal-card">
        <div class="urgent-icon-wrapper">
            <i data-lucide="alert-triangle" width="28" height="28"></i>
        </div>
        <h3 class="urgent-title">Aviso Urgente</h3>
        <p class="urgent-subtitle">
            <?= htmlspecialchars($popupAviso['title']) ?>
        </p>
        <div class="urgent-body">
            <?= $popupAviso['message'] ?>
        </div>
        <button onclick="closeUrgentModal()" class="btn-urgent-action">
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

<?php renderPageHeader('Página Inicial', 'Acesso rápido às suas atividades'); ?>

<!-- DASHBOARD CONTAINER (Removido max-width inline) -->
<div class="dashboard-container">
    <?php
    require_once '../includes/dashboard_render.php';
    
    // Dados para renderização
    $renderData = [
        'pdo' => $pdo,
        'userId' => $userId,
        'nextSchedule' => $nextSchedule,
        'totalSchedules' => $totalSchedules,
        'ultimaMusica' => $ultimaMusica,
        'totalMusicas' => $totalMusicas,
        'ultimoAviso' => $ultimoAviso,
        'unreadCount' => $unreadCount,
        'niverCount' => $aniversariantesCount,
        'proximoNiver' => $proximoAniversariante,
        'totalMembros' => $totalMembros,
        'statsMembros' => $statsMembros,
        'oracaoCount' => $oracaoCount,
        'nextEvent' => $nextEvent,
        'totalEvents' => $totalEvents,
        'historicoData' => $historicoData
    ];

    // 1. Agrupar cards configurados pelo usuário
    $groupedCards = [];
    $cardsOrder = []; 

    // Mapeamento de categorias e ordem de exibição
    // ATUALIZADOS para corresponder dashboard_cards.php
    $categoryOrder = ['Gestão', 'Espiritualidade', 'Comunicação'];
    
    // Preparar grupos
    foreach ($categoryOrder as $cat) {
        $groupedCards[$cat] = [];
    }

    // Distribuir cards nos grupos
    foreach ($userDashboardSettings as $setting) {
        $cardId = $setting['card_id'];
        if (isset($allCardsDefinitions[$cardId])) {
            $cardDef = $allCardsDefinitions[$cardId];
            $catName = $cardDef['category_name'];
            
            // Fallback para 'Comunicação' se categoria desconhecida
            if (!isset($groupedCards[$catName])) {
                $catName = 'Comunicação';
                if (!isset($groupedCards['Comunicação'])) $groupedCards['Comunicação'] = [];
            }
            
            $groupedCards[$catName][] = $cardId;
        }
    }

    // 2. Renderizar Se Seções com cores personalizadas
    foreach ($categoryOrder as $categoryName) {
        if (empty($groupedCards[$categoryName])) continue;
        
        // Definir cor da borda esquerda por categoria
        $categoryColor = match($categoryName) {
            'Gestão' => '#2563EB', // Azul
            'Espiritualidade' => '#10B981', // Verde
            'Comunicação' => '#F59E0B', // Amarelo
            default => '#2563EB'
        };
        
        // Título da Seção com borda colorida
        echo "<h2 class='section-title' style='border-left-color: {$categoryColor};'>{$categoryName}</h2>";
       
        // Grid da Seção
        echo '<div class="quick-access-grid">';
        foreach ($groupedCards[$categoryName] as $cardId) {
            renderDashboardCard($cardId, $renderData);
        }
        echo '</div>';
    }
    ?>
</div>

<?php renderAppFooter(); ?>