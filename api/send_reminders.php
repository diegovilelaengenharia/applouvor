<?php
// api/send_reminders.php — Envia push de lembrete para participantes 'pending' de escalas nos próximos 2 dias
header('Content-Type: application/json');
require_once '../includes/auth.php';
require_once '../includes/db.php';
require_once '../includes/web_push_helper.php';

// Apenas admin pode enviar lembretes
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Acesso negado']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
$scheduleId = isset($data['schedule_id']) ? (int)$data['schedule_id'] : 0;

// Buscar VAPID keys da config/env
$vapidPublic  = defined('VAPID_PUBLIC_KEY')  ? VAPID_PUBLIC_KEY  : (getenv('VAPID_PUBLIC_KEY')  ?: '');
$vapidPrivate = defined('VAPID_PRIVATE_KEY') ? VAPID_PRIVATE_KEY : (getenv('VAPID_PRIVATE_KEY') ?: '');

if (empty($vapidPublic) || empty($vapidPrivate)) {
    error_log('send_reminders.php: VAPID keys não configuradas');
    echo json_encode(['success' => false, 'message' => 'Push não configurado. Configure as VAPID keys.']);
    exit;
}

try {
    // Buscar escalas nos próximos 2 dias com participantes pending
    if ($scheduleId > 0) {
        // Lembrete para uma escala específica
        $stmt = $pdo->prepare("
            SELECT su.user_id, s.event_type, s.event_date, s.event_time
            FROM schedule_users su
            JOIN schedules s ON s.id = su.schedule_id
            WHERE su.schedule_id = ?
              AND su.status = 'pending'
        ");
        $stmt->execute([$scheduleId]);
    } else {
        // Lembrete para todas as escalas nos próximos 2 dias
        $stmt = $pdo->prepare("
            SELECT su.user_id, s.id as schedule_id, s.event_type, s.event_date, s.event_time
            FROM schedule_users su
            JOIN schedules s ON s.id = su.schedule_id
            WHERE s.event_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 2 DAY)
              AND su.status = 'pending'
        ");
        $stmt->execute();
    }
    $pendingUsers = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (empty($pendingUsers)) {
        echo json_encode(['success' => true, 'message' => 'Nenhum participante pendente encontrado.', 'sent' => 0]);
        exit;
    }

    $helper = new WebPushHelper($vapidPublic, $vapidPrivate, 'mailto:contato@pibolveira.com');
    $sent   = 0;
    $failed = 0;

    foreach ($pendingUsers as $pu) {
        // Buscar subscriptions do usuário
        $stmtSub = $pdo->prepare("SELECT endpoint, p256dh, auth FROM push_subscriptions WHERE user_id = ?");
        $stmtSub->execute([$pu['user_id']]);
        $subscriptions = $stmtSub->fetchAll(PDO::FETCH_ASSOC);

        if (empty($subscriptions)) continue;

        $eventDate = date('d/m', strtotime($pu['event_date']));
        $eventTime = substr($pu['event_time'], 0, 5);
        $payload = [
            'title' => 'Lembrete de Escala',
            'body'  => "Voce nao confirmou presenca no " . $pu['event_type'] . " de $eventDate as $eventTime. Confirme no app!",
            'url'   => '/applouvor/admin/escalas.php',
        ];

        foreach ($subscriptions as $sub) {
            $ok = $helper->sendNotification($sub, $payload);
            if ($ok) $sent++;
            else $failed++;
        }
    }

    echo json_encode([
        'success' => true,
        'message' => "Lembretes enviados: $sent. Falhas: $failed.",
        'sent'    => $sent,
        'failed'  => $failed,
    ]);

} catch (Exception $e) {
    error_log('send_reminders.php error: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Erro interno: ' . $e->getMessage()]);
}
