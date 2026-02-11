<?php
// admin/dashboard_data.php

require_once '../includes/auth.php';
require_once '../includes/db.php';

// Garantir que usuário está logado
checkLogin();

$userId = $_SESSION['user_id'] ?? 1;
$userRole = $_SESSION['user_role'] ?? 'user';

// ===================================
// 1. Avisos (Prioridade Alta e Recentes)
// ===================================
$avisos = [];
$popupAviso = null;
$unreadCount = 0;
$ultimoAviso = '';

try {
    // Buscar avisos ativos
    $avisosTodos = App\DB::table('avisos')
        ->select('*')
        ->where('archived_at', '=', null)
        ->orderBy('created_at', 'DESC')
        ->limit(10) // Buscar um pouco mais para garantir após filtro
        ->get();
    
    // Filtrar por audiência e data
    $avisosFiltrados = array_filter($avisosTodos, function($av) use ($userRole) {
        $validAudience = in_array($av['target_audience'], ['all', 'team']) || 
                        ($userRole === 'admin' && in_array($av['target_audience'], ['admins', 'leaders']));
        $notExpired = empty($av['expires_at']) || strtotime($av['expires_at']) >= strtotime('today');
        return $validAudience && $notExpired;
    });
    
    // Pegar os top 5 para exibição
    $avisos = array_slice($avisosFiltrados, 0, 5);
    $ultimoAviso = $avisos[0]['title'] ?? 'Nenhum aviso novo';
    
    // Identificar aviso urgente (popup)
    foreach ($avisos as $av) {
        if ($av['priority'] === 'urgent') {
            $popupAviso = $av;
            break; 
        }
    }
    
    // Contar não lidos (últimos 3 dias)
    $recentAvisos = array_filter($avisosFiltrados, function($av) {
        return strtotime($av['created_at']) > strtotime('-3 days');
    });
    $unreadCount = count($recentAvisos);

} catch (Exception $e) {
    // Fail silently ou log error
}

// ===================================
// 2. Escalas (Próxima)
// ===================================
$nextSchedule = null;
$totalSchedules = 0;

try {
    // Próxima escala geral
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
    
    // Total futuras
    $stmtCount = $pdo->query("SELECT COUNT(*) FROM schedules WHERE event_date >= CURDATE()");
    $totalSchedules = $stmtCount->fetchColumn();
} catch (Exception $e) { }

// ===================================
// 3. Repertório
// ===================================
$totalMusicas = 0;
$ultimaMusica = null;

try {
    $totalMusicas = App\DB::table('songs')->count();
    $ultimaMusica = App\DB::table('songs')
        ->select(['title', 'artist', 'tone'])
        ->orderBy('created_at', 'DESC')
        ->first();
} catch (Exception $e) { }

// ===================================
// 4. Membros (Stats)
// ===================================
$totalMembros = 0;
$statsMembros = ['vocals' => 0, 'instrumentalists' => 0];

try {
    $totalMembros = App\DB::table('users')->count();

    // Query otimizada para contar vocais vs instrumentistas
    $stmtV = $pdo->query("
        SELECT COUNT(DISTINCT u.id) 
        FROM users u
        WHERE EXISTS (
            SELECT 1 FROM user_roles ur
            INNER JOIN roles r ON ur.role_id = r.id
            WHERE ur.user_id = u.id
            AND (r.name LIKE '%Vocal%' OR r.name LIKE '%Ministro%' OR r.name LIKE '%Voz%')
        ) OR (
            u.instrument IS NOT NULL AND u.instrument != ''
            AND (u.instrument LIKE '%Voz%' OR u.instrument LIKE '%Vocal%' OR u.instrument LIKE '%Ministro%')
        )
    ");
    $statsMembros['vocals'] = $stmtV->fetchColumn();
    $statsMembros['instrumentalists'] = $totalMembros - $statsMembros['vocals']; // Simplificação segura
} catch (Exception $e) { }

// ===================================
// 5. Agenda & Eventos
// ===================================
$nextEvent = null;
$totalEvents = 0;

try {
    $stmt = $pdo->prepare("SELECT * FROM events WHERE start_datetime >= NOW() ORDER BY start_datetime ASC LIMIT 1");
    $stmt->execute();
    $nextEvent = $stmt->fetch(PDO::FETCH_ASSOC);
    
    $stmtCount = $pdo->query("SELECT COUNT(*) FROM events WHERE start_datetime >= NOW()");
    $totalEvents = $stmtCount->fetchColumn();
} catch (Exception $e) { }

// ===================================
// 6. Aniversariantes
// ===================================
$aniversariantesCount = 0;
$proximoAniversariante = null;

try {
    $stmt = $pdo->query("SELECT COUNT(*) FROM users WHERE MONTH(birth_date) = MONTH(CURRENT_DATE())");
    $aniversariantesCount = $stmt->fetchColumn();

    $stmtProx = $pdo->query("
        SELECT name, DAY(birth_date) as dia 
        FROM users 
        WHERE MONTH(birth_date) = MONTH(CURRENT_DATE()) AND DAY(birth_date) >= DAY(CURRENT_DATE())
        ORDER BY dia ASC 
        LIMIT 1
    ");
    $proximoAniversariante = $stmtProx->fetch(PDO::FETCH_ASSOC);
} catch (Exception $e) { }

// ===================================
// 7. Orações
// ===================================
$oracaoCount = 0;
try {
    $stmtCheck = $pdo->query("SHOW TABLES LIKE 'prayer_requests'");
    if ($stmtCheck->rowCount() > 0) {
        $stmt = $pdo->query("SELECT COUNT(*) FROM prayer_requests WHERE is_answered = 0");
        $oracaoCount = $stmt->fetchColumn();
    }
} catch (Exception $e) { }

// ===================================
// 8. Histórico & Sugestões
// ===================================
$historicoData = ['last_culto' => null, 'sugestoes_count' => 0];
try {
    $stmtLastCulto = $pdo->query("SELECT event_date, event_type FROM schedules WHERE event_date < CURDATE() ORDER BY event_date DESC LIMIT 1");
    $historicoData['last_culto'] = $stmtLastCulto->fetch(PDO::FETCH_ASSOC);

    $stmtSugCount = $pdo->query("
        SELECT COUNT(*) FROM (
            SELECT s.id FROM songs s
            LEFT JOIN schedule_songs ss ON s.id = ss.song_id
            LEFT JOIN schedules sc ON ss.schedule_id = sc.id AND sc.event_date < CURDATE()
            GROUP BY s.id
            HAVING MAX(sc.event_date) IS NULL OR MAX(sc.event_date) < DATE_SUB(CURDATE(), INTERVAL 60 DAY)
        ) as sub
    ");
    $historicoData['sugestoes_count'] = $stmtSugCount->fetchColumn();
} catch (Exception $e) { }

// ===================================
// 9. Configurações de Dashboard
// ===================================
require_once '../includes/dashboard_cards.php';
$userDashboardSettings = [];
try {
    $stmt = $pdo->prepare("SELECT card_id, is_visible, display_order FROM user_dashboard_settings WHERE user_id = ? AND is_visible = TRUE ORDER BY display_order ASC");
    $stmt->execute([$userId]);
    $userDashboardSettings = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) { }

// Fallback padrão se vazio
if (empty($userDashboardSettings)) {
    $defaultCards = ['escalas', 'repertorio', 'leitura', 'avisos', 'aniversarios', 'devocional', 'oracao'];
    foreach ($defaultCards as $index => $cardId) {
        $userDashboardSettings[] = [
            'card_id' => $cardId,
            'is_visible' => true,
            'display_order' => $index + 1
        ];
    }
}

// Saudação
$hora = date('H');
if ($hora >= 5 && $hora < 12) $saudacao = "Bom dia";
elseif ($hora >= 12 && $hora < 18) $saudacao = "Boa tarde";
else $saudacao = "Boa noite";
$nomeUser = explode(' ', $_SESSION['user_name'])[0];

// Retorno consolidado (para uso no index.php)
return [
    'avisos' => $avisos,
    'popupAviso' => $popupAviso,
    'unreadCount' => $unreadCount,
    'ultimoAviso' => $ultimoAviso,
    'nextSchedule' => $nextSchedule,
    'totalSchedules' => $totalSchedules,
    'totalMusicas' => $totalMusicas,
    'ultimaMusica' => $ultimaMusica,
    'totalMembros' => $totalMembros,
    'statsMembros' => $statsMembros,
    'nextEvent' => $nextEvent,
    'totalEvents' => $totalEvents,
    'aniversariantesCount' => $aniversariantesCount,
    'proximoAniversariante' => $proximoAniversariante,
    'oracaoCount' => $oracaoCount,
    'historicoData' => $historicoData,
    'userDashboardSettings' => $userDashboardSettings,
    'saudacao' => $saudacao,
    'nomeUser' => $nomeUser
];
