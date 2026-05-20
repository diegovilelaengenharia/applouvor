<?php
// admin/dashboard_data.php
// Controlador robusto que alimenta o Dashboard Premium preservando TODAS as funcionalidades originais.

if (!isset($pdo)) {
    require_once '../includes/db.php';
}

$userId = $_SESSION['user_id'] ?? 0;
$userRole = $_SESSION['user_role'] ?? 'user';

// 1. Saudação
$hour = date('H');
if ($hour >= 5 && $hour < 12) $salutation = "Bom dia";
elseif ($hour >= 12 && $hour < 18) $salutation = "Boa tarde";
else $salutation = "Boa noite";

// 2. Escalas (Próxima + Fallback + Contagem)
$nextSchedule = null;
$totalSchedules = 0;
try {
    $stmt = $pdo->prepare("
        SELECT s.*, su.status as my_status, su.role as my_role
        FROM schedules s
        JOIN schedule_users su ON s.id = su.schedule_id
        WHERE su.user_id = ? AND s.event_date >= CURDATE()
        ORDER BY s.event_date ASC
        LIMIT 1
    ");
    $stmt->execute([$userId]);
    $nextSchedule = $stmt->fetch(PDO::FETCH_ASSOC);

    $stmtCount = $pdo->prepare("SELECT COUNT(*) FROM schedule_users su JOIN schedules s ON s.id = su.schedule_id WHERE su.user_id = ? AND s.event_date >= CURDATE()");
    $stmtCount->execute([$userId]);
    $totalSchedules = $stmtCount->fetchColumn();
} catch (Exception $e) {}

// 3. Repertório
$totalMusicas = 0;
$ultimaMusica = null;
try {
    $totalMusicas = $pdo->query("SELECT COUNT(*) FROM songs")->fetchColumn();
    $ultimaMusica = $pdo->query("SELECT * FROM songs ORDER BY created_at DESC LIMIT 1")->fetchColumn(); // Simplified for card
    $stmtLast = $pdo->query("SELECT title, artist, tone FROM songs ORDER BY created_at DESC LIMIT 1");
    $ultimaMusica = $stmtLast->fetch(PDO::FETCH_ASSOC);
} catch (Exception $e) {}

// 4. Membros e Estatísticas de Equipe
$totalMembros = 0;
$statsMembros = ['vocals' => 0, 'instrumentalists' => 0];
try {
    $totalMembros = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
    // Exemplo de lógica original: contar por instrumento/função
    $statsMembros['vocals'] = $pdo->query("SELECT COUNT(*) FROM users WHERE instrument LIKE '%Vocal%' OR instrument LIKE '%Voz%'")->fetchColumn();
    $statsMembros['instrumentalists'] = $totalMembros - $statsMembros['vocals'];
} catch (Exception $e) {}

// 5. Aniversariantes
$niverCount = 0;
$proximoNiver = null;
try {
    $stmtNiver = $pdo->query("SELECT name, DAY(birth_date) as dia FROM users WHERE MONTH(birth_date) = MONTH(CURRENT_DATE()) ORDER BY dia ASC");
    $aniversariantes = $stmtNiver->fetchAll(PDO::FETCH_ASSOC);
    $niverCount = count($aniversariantes);
    
    // Achar o próximo a partir de hoje
    foreach ($aniversariantes as $n) {
        if ($n['dia'] >= (int)date('d')) {
            $proximoNiver = $n;
            break;
        }
    }
} catch (Exception $e) {}

// 6. Agenda (Eventos)
$nextEvent = null;
$totalEvents = 0;
try {
    // Tabela 'events' ou similar se existir
    $stmtEv = $pdo->query("SELECT * FROM schedules WHERE event_date >= CURDATE() ORDER BY event_date ASC LIMIT 1");
    $nextEvent = $stmtEv->fetch(PDO::FETCH_ASSOC);
    // Adaptar campos para o renderizador de agenda (que espera start_datetime)
    if ($nextEvent) {
        $nextEvent['start_datetime'] = $nextEvent['event_date'] . ' ' . ($nextEvent['event_time'] ?? '19:00:00');
        $nextEvent['title'] = $nextEvent['event_type'];
    }
    $totalEvents = $pdo->query("SELECT COUNT(*) FROM schedules WHERE event_date >= CURDATE()")->fetchColumn();
} catch (Exception $e) {}

// 7. Leitura Bíblica (Progresso detalhado)
$leituraData = [
    'percentYear' => 0,
    'percentToday' => 0,
    'todayProgress' => 0,
    'todayTotal' => 0,
    'displayDayGlobal' => 1
];
try {
    // Buscar config de início
    $stmtSet = $pdo->prepare("SELECT setting_value FROM user_settings WHERE user_id = ? AND setting_key = 'reading_plan_start_date'");
    $stmtSet->execute([$userId]);
    $sDate = $stmtSet->fetchColumn() ?: date('Y-01-01');
    $startDT = new DateTime($sDate);
    $diffDays = $startDT->diff(new DateTime())->days;
    $leituraData['displayDayGlobal'] = max(1, $diffDays + 1);

    $totalDaysRead = $pdo->prepare("SELECT COUNT(DISTINCT month_num, day_num) FROM reading_progress WHERE user_id = ?");
    $totalDaysRead->execute([$userId]);
    $readCount = $totalDaysRead->fetchColumn();
    $leituraData['percentYear'] = round(($readCount / 365) * 100);
} catch (Exception $e) {}

// 8. Avisos (Contagem de não lidos)
$unreadCount = 0;
$ultimoAviso = "Nenhum aviso novo";
try {
    $unreadCount = $pdo->query("SELECT COUNT(*) FROM avisos WHERE created_at > DATE_SUB(NOW(), INTERVAL 3 DAY)")->fetchColumn();
    $ultimoAviso = $pdo->query("SELECT title FROM avisos ORDER BY created_at DESC LIMIT 1")->fetchColumn() ?: $ultimoAviso;
} catch (Exception $e) {}

// 9. Histórico
$historicoData = ['last_culto' => null, 'sugestoes_count' => 0];
try {
    $historicoData['last_culto'] = $pdo->query("SELECT * FROM schedules WHERE event_date < CURDATE() ORDER BY event_date DESC LIMIT 1")->fetch(PDO::FETCH_ASSOC);
    $historicoData['sugestoes_count'] = $pdo->query("SELECT COUNT(*) FROM song_suggestions WHERE status = 'pending'")->fetchColumn();
} catch (Exception $e) {}

// Sugestões pendentes (para badge no dashboard — admin only)
$pendingSuggestions = ($userRole === 'admin') ? (int)$historicoData['sugestoes_count'] : 0;

// 10. Oração
$oracaoCount = 0;
try {
    // Se existir tabela de oração
    // $oracaoCount = $pdo->query("SELECT COUNT(*) FROM prayers WHERE status = 'active'")->fetchColumn();
} catch (Exception $e) {}

// Foto do Usuário
$userPhoto = $_SESSION['user_avatar'] ?? '';
if (empty($userPhoto)) {
    $userPhoto = 'https://ui-avatars.com/api/?name=' . urlencode($_SESSION['user_name'] ?? 'M') . '&background=3b82f6&color=fff';
} elseif (strpos($userPhoto, 'http') === false) {
    if (strpos($userPhoto, 'assets') === false && strpos($userPhoto, 'uploads') === false) {
        $userPhoto = '../assets/uploads/' . $userPhoto;
    } else {
        $userPhoto = '../' . $userPhoto;
    }
}

return [
    'salutation' => $salutation,
    'userName' => $_SESSION['user_name'] ?? 'Músico',
    'userRole' => $userRole,
    'userPhoto' => $userPhoto,
    // Data for Original Card Functions
    'nextSchedule' => $nextSchedule,
    'totalSchedules' => $totalSchedules,
    'ultimaMusica' => $ultimaMusica,
    'totalMusicas' => $totalMusicas,
    'totalMembros' => $totalMembros,
    'statsMembros' => $statsMembros,
    'niverCount' => $niverCount,
    'proximoNiver' => $proximoNiver,
    'nextEvent' => $nextEvent,
    'totalEvents' => $totalEvents,
    'leituraData' => $leituraData,
    'unreadCount' => $unreadCount,
    'ultimoAviso' => $ultimoAviso,
    'historicoData' => $historicoData,
    'oracaoCount' => $oracaoCount,
    'pendingSuggestions' => $pendingSuggestions
];
