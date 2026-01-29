<?php
/**
 * Cron Job - Relatório Semanal
 * Deve ser executado toda segunda-feira de manhã (ex: 08:00)
 */

// Ajustar caminho se necessário
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/notification_system.php';

// Configurar timezone para garantir datas corretas
date_default_timezone_set('America/Sao_Paulo');

try {
    $notificationSystem = new NotificationSystem($pdo);
    
    // 1. Buscar eventos dos próximos 7 dias
    $startParams = date('Y-m-d');
    $endParams = date('Y-m-d', strtotime('+7 days'));
    
    $stmt = $pdo->prepare("
        SELECT * FROM schedules 
        WHERE event_date BETWEEN ? AND ? 
        ORDER BY event_date ASC
    ");
    $stmt->execute([$startParams, $endParams]);
    $events = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (count($events) === 0) {
        echo "Nenhum evento para esta semana. Relatório pulado.\n";
        exit;
    }
    
    // 2. Montar resumo
    $countEvents = count($events);
    $eventList = [];
    foreach ($events as $evt) {
        $date = date('d/m', strtotime($evt['event_date']));
        $time = isset($evt['event_time']) ? substr($evt['event_time'], 0, 5) : '';
        $type = $evt['event_type'];
        $eventList[] = "• $date - $type ($time)";
    }
    
    $message = "Olá! Temos $countEvents eventos programados para esta semana:\n\n" . implode("\n", $eventList) . "\n\nToque para ver a agenda completa.";
    
    // 3. Buscar usuários ativos
    $users = $pdo->query("SELECT id FROM users WHERE status = 'active'")->fetchAll(PDO::FETCH_COLUMN);
    
    $countSent = 0;
    
    foreach ($users as $userId) {
        // O método createNotification já verifica as preferências do usuário
        $sent = $notificationSystem->createNotification(
            $userId,
            'weekly_report',
            "📅 Resumo da Semana",
            $message,
            "escalas.php"
        );
        
        if ($sent) $countSent++;
    }
    
    echo "Relatório semanal gerado com sucesso!\n";
    echo "Eventos: $countEvents\n";
    echo "Notificações enviadas: $countSent\n";
    
} catch (Exception $e) {
    echo "Erro ao gerar relatório: " . $e->getMessage() . "\n";
    error_log("CRON ERROR (weekly_report): " . $e->getMessage());
}
