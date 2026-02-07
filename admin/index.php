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
    // Usando Query Builder para buscar avisos
    $avisos = App\DB::table('avisos')
        ->select('*')
        ->where('archived_at', '=', null)
        ->orderBy('created_at', 'DESC')
        ->limit(5)
        ->get();
    
    // Filtrar por audiência e data de expiração manualmente (Query Builder simples não tem WHERE IN)
    $avisos = array_filter($avisos, function($av) use ($userRole) {
        $validAudience = in_array($av['target_audience'], ['all', 'team']) || 
                        ($userRole === 'admin' && in_array($av['target_audience'], ['admins', 'leaders']));
        $notExpired = empty($av['expires_at']) || strtotime($av['expires_at']) >= strtotime('today');
        return $validAudience && $notExpired;
    });
    $avisos = array_slice($avisos, 0, 5);

    $totalAvisos = count($avisos);
    $ultimoAviso = $avisos[0]['title'] ?? 'Nenhum aviso novo';
    
    foreach ($avisos as $av) {
        if ($av['priority'] === 'urgent') {
            $popupAviso = $av;
            break; 
        }
    }
    
    // Contar avisos recentes (últimos 3 dias)
    $unreadCount = App\DB::table('avisos')
        ->where('archived_at', '=', null)
        ->count();
    // Filtrar manualmente por data (simplificado)
    $recentAvisos = array_filter($avisos, function($av) {
        return strtotime($av['created_at']) > strtotime('-3 days');
    });
    $unreadCount = count($recentAvisos);

} catch (Exception $e) {
    $totalAvisos = 0;
    $ultimoAviso = '';
}

// 2. Escalas (Próxima + Detalhes)
$nextSchedule = null;
$totalSchedules = 0;
try {
    // Buscar próxima escala (TODAS, não só as do usuário)
    $stmt = $pdo->prepare("
        SELECT s.*,
               (SELECT r.name 
                FROM schedule_users su 
                JOIN user_roles ur ON su.user_id = ur.user_id
                JOIN roles r ON ur.role_id = r.id 
                WHERE su.schedule_id = s.id AND su.user_id = ? 
                ORDER BY ur.is_primary DESC 
                LIMIT 1) as my_role
        FROM schedules s
        WHERE s.event_date >= CURDATE()
        ORDER BY s.event_date ASC, s.event_time ASC
        LIMIT 1
    ");
    $stmt->execute([$userId]);
    $nextSchedule = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Contagem total de escalas futuras
    $stmtCount = $pdo->query("
        SELECT COUNT(*) 
        FROM schedules
        WHERE event_date >= CURDATE()
    ");
    $totalSchedules = $stmtCount->fetchColumn();
} catch (Exception $e) {
}

// 3. Repertório (Última + Tom + Contagem)
$totalMusicas = 0;
$ultimaMusica = null;
try {
    // Usando Query Builder
    $totalMusicas = App\DB::table('songs')->count();
    
    // Buscar última música com tom
    $ultimaMusica = App\DB::table('songs')
        ->select(['title', 'artist', 'tone'])
        ->orderBy('created_at', 'DESC')
        ->first();
} catch (Exception $e) {
}

// 4. Membros (Stats - Vocais vs Instrumentistas)
$totalMembros = 0;
$statsMembros = ['vocals' => 0, 'instrumentalists' => 0];
try {
    // Usando Query Builder
    $totalMembros = App\DB::table('users')->count();

    // Contar vocais: incluir tanto user_roles quanto coluna instrument (sistema legado)
    $stmtV = $pdo->query("
        SELECT COUNT(DISTINCT u.id) 
        FROM users u
        WHERE (
            -- Vocais da tabela user_roles
            EXISTS (
                SELECT 1 FROM user_roles ur
                INNER JOIN roles r ON ur.role_id = r.id
                WHERE ur.user_id = u.id
                AND (r.name LIKE '%Vocal%' OR r.name LIKE '%Ministro%' OR r.name LIKE '%Voz%')
            )
            -- OU vocais da coluna instrument (legado)
            OR (
                u.instrument IS NOT NULL 
                AND u.instrument != ''
                AND (u.instrument LIKE '%Voz%' OR u.instrument LIKE '%Vocal%' OR u.instrument LIKE '%Ministro%')
            )
        )
    ");
    $statsMembros['vocals'] = $stmtV->fetchColumn();

    // Contar instrumentistas: qualquer um que NÃO seja vocal (roles ou instrument)
    $stmtI = $pdo->query("
        SELECT COUNT(DISTINCT u.id) 
        FROM users u
        WHERE (
            -- Tem role de instrumentista
            EXISTS (
                SELECT 1 FROM user_roles ur
                INNER JOIN roles r ON ur.role_id = r.id
                WHERE ur.user_id = u.id
                AND r.name NOT LIKE '%Vocal%' 
                AND r.name NOT LIKE '%Ministro%' 
                AND r.name NOT LIKE '%Voz%'
            )
            -- OU tem instrumento legado que não seja vocal
            OR (
                u.instrument IS NOT NULL 
                AND u.instrument != ''
                AND u.instrument NOT LIKE '%Voz%' 
                AND u.instrument NOT LIKE '%Vocal%' 
                AND u.instrument NOT LIKE '%Ministro%'
            )
        )
    ");
    $statsMembros['instrumentalists'] = $stmtI->fetchColumn();
} catch (Exception $e) {
    // Fallback se não houver tabela de roles
    try {
        $stmt = $pdo->query("SELECT COUNT(*) FROM users");
        $totalMembros = $stmt->fetchColumn();
        $stmtV = $pdo->query("SELECT COUNT(*) FROM users WHERE (instrument LIKE '%Voz%' OR instrument LIKE '%Vocal%' OR instrument LIKE '%Ministro%')");
        $statsMembros['vocals'] = $stmtV->fetchColumn();
        $stmtI = $pdo->query("SELECT COUNT(*) FROM users WHERE instrument IS NOT NULL AND instrument != '' AND instrument NOT LIKE '%Voz%' AND instrument NOT LIKE '%Vocal%' AND instrument NOT LIKE '%Ministro%'");
        $statsMembros['instrumentalists'] = $stmtI->fetchColumn();
    } catch (Exception $e2) {
    }
}

// 5. Agenda (Próximo Evento Coletivo)
$nextEvent = null;
$totalEvents = 0;
try {
    // Buscar próximo evento da agenda (tabela events)
    $stmt = $pdo->prepare("
        SELECT * FROM events
        WHERE start_datetime >= NOW()
        ORDER BY start_datetime ASC
        LIMIT 1
    ");
    $stmt->execute();
    $nextEvent = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Contar total de eventos futuros
    $stmtCount = $pdo->query("
        SELECT COUNT(*) FROM events
        WHERE start_datetime >= NOW()
    ");
    $totalEvents = $stmtCount->fetchColumn();
} catch (Exception $e) {
    // Tabela events pode não existir ainda
}


// 5. Aniversariantes (Próximo)
$aniversariantesCount = 0;
$proximoAniversariante = null;
try {
    // Contagem Mês
    $stmt = $pdo->query("SELECT COUNT(*) FROM users WHERE MONTH(birth_date) = MONTH(CURRENT_DATE())");
    $aniversariantesCount = $stmt->fetchColumn();

    // Próximo a partir de hoje
    $stmtProx = $pdo->query("
        SELECT name, DAY(birth_date) as dia 
        FROM users 
        WHERE MONTH(birth_date) = MONTH(CURRENT_DATE()) AND DAY(birth_date) >= DAY(CURRENT_DATE())
        ORDER BY dia ASC 
        LIMIT 1
    ");
    $proximoAniversariante = $stmtProx->fetch(PDO::FETCH_ASSOC);
} catch (Exception $e) {
}

// 6. Orações (Contagem Ativa)
$oracaoCount = 0;
try {
    // Verificar se tabela existe primeiro (já que é novo)
    $stmtCheck = $pdo->query("SHOW TABLES LIKE 'prayer_requests'");
    if ($stmtCheck->rowCount() > 0) {
        $stmt = $pdo->query("SELECT COUNT(*) FROM prayer_requests WHERE is_answered = 0");
        $oracaoCount = $stmt->fetchColumn();
    }
} catch (Exception $e) {
    $oracaoCount = 0;
}

// 7. Histórico (Dados Rápidos)
$historicoData = ['last_culto' => null, 'sugestoes_count' => 0];
try {
    // Último culto realizado
    $stmtLastCulto = $pdo->query("
        SELECT event_date, event_type 
        FROM schedules 
        WHERE event_date < CURDATE() 
        ORDER BY event_date DESC 
        LIMIT 1
    ");
    $historicoData['last_culto'] = $stmtLastCulto->fetch(PDO::FETCH_ASSOC);

    // Contagem de Sugestões (nunca tocadas ou > 60 dias)
    $stmtSugCount = $pdo->query("
        SELECT COUNT(*) FROM (
            SELECT s.id
            FROM songs s
            LEFT JOIN schedule_songs ss ON s.id = ss.song_id
            LEFT JOIN schedules sc ON ss.schedule_id = sc.id AND sc.event_date < CURDATE()
            GROUP BY s.id
            HAVING MAX(sc.event_date) IS NULL OR MAX(sc.event_date) < DATE_SUB(CURDATE(), INTERVAL 60 DAY)
        ) as sub
    ");
    $historicoData['sugestoes_count'] = $stmtSugCount->fetchColumn();
} catch (Exception $e) {
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

<?php renderPageHeader('Visão Geral', 'Acesso rápido às suas atividades'); ?>

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
    $categoryOrder = ['Gestão', 'Espírito', 'Comunica', 'Admin', 'Extras'];
    
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
            
            // Fallback para 'Extras' se categoria desconhecida
            if (!isset($groupedCards[$catName])) {
                $catName = 'Extras';
                if (!isset($groupedCards['Extras'])) $groupedCards['Extras'] = [];
            }
            
            $groupedCards[$catName][] = $cardId;
        }
    }

    // 2. Renderizar Seções
    foreach ($categoryOrder as $categoryName) {
        if (empty($groupedCards[$categoryName])) continue;
        
        // Título da Seção
        echo "<h2 class='section-title'>{$categoryName}</h2>";
        
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