<?php
/**
 * Script Cron: Enviar Lembretes de Eventos
 * 
 * Executar a cada hora via cron ou manualmente
 * Exemplo crontab: 0 * * * * php /path/to/send_event_reminders.php
 */

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/notification_system.php';

$notificationSystem = new NotificationSystem($pdo);

// Buscar eventos nas próximas 24 horas (lembrete 24h)
$stmt24h = $pdo->prepare("
    SELECT e.*, GROUP_CONCAT(DISTINCT ep.user_id) as participant_ids
    FROM events e
    INNER JOIN event_participants ep ON e.id = ep.event_id
    WHERE e.start_datetime BETWEEN NOW() AND DATE_ADD(NOW(), INTERVAL 24 HOUR)
      AND e.start_datetime > DATE_ADD(NOW(), INTERVAL 23 HOUR)
      AND ep.status != 'declined'
    GROUP BY e.id
");
$stmt24h->execute();
$events24h = $stmt24h->fetchAll(PDO::FETCH_ASSOC);

foreach ($events24h as $event) {
    $participantIds = explode(',', $event['participant_ids']);
    $dateTime = new DateTime($event['start_datetime']);
    $dateFormatted = $dateTime->format('d/m/Y');
    $timeFormatted = $event['all_day'] ? 'Dia todo' : $dateTime->format('H:i');
    
    $title = "Lembrete: {$event['title']}";
    $message = "Amanhã às $timeFormatted - {$event['title']}";
    
    if (!empty($event['location'])) {
        $message .= " em {$event['location']}";
    }
    
    foreach ($participantIds as $userId) {
        $notificationSystem->create(
            $userId,
            NotificationSystem::TYPE_EVENT_REMINDER_24H,
            $title,
            $message,
            ['event_id' => $event['id']],
            "evento_detalhe.php?id={$event['id']}"
        );
    }
    
    echo "✓ Lembretes 24h enviados para evento: {$event['title']}\n";
}

// Buscar eventos na próxima hora (lembrete 1h)
$stmt1h = $pdo->prepare("
    SELECT e.*, GROUP_CONCAT(DISTINCT ep.user_id) as participant_ids
    FROM events e
    INNER JOIN event_participants ep ON e.id = ep.event_id
    WHERE e.start_datetime BETWEEN NOW() AND DATE_ADD(NOW(), INTERVAL 1 HOUR)
      AND e.start_datetime > DATE_ADD(NOW(), INTERVAL 55 MINUTE)
      AND ep.status != 'declined'
    GROUP BY e.id
");
$stmt1h->execute();
$events1h = $stmt1h->fetchAll(PDO::FETCH_ASSOC);

foreach ($events1h as $event) {
    $participantIds = explode(',', $event['participant_ids']);
    $dateTime = new DateTime($event['start_datetime']);
    $timeFormatted = $dateTime->format('H:i');
    
    $title = "🔔 {$event['title']} em breve!";
    $message = "O evento começa em menos de 1 hora (às $timeFormatted)";
    
    if (!empty($event['location'])) {
        $message .= ". Local: {$event['location']}";
    }
    
    foreach ($participantIds as $userId) {
        $notificationSystem->create(
            $userId,
            NotificationSystem::TYPE_EVENT_REMINDER_1H,
            $title,
            $message,
            ['event_id' => $event['id']],
            "evento_detalhe.php?id={$event['id']}"
        );
    }
    
    echo "✓ Lembretes 1h enviados para evento: {$event['title']}\n";
}

$total = count($events24h) +count($events1h);
echo "\n✅ Concluído! Total de eventos processados: $total\n";
