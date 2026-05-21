<?php
/**
 * Script Cron: Sincronização com Google Calendar
 * 
 * Executar periodicamente via cron ou manualmente
 * Exemplo crontab: */30 * * * * php /path/to/sync_google_calendar.php
 */

require_once __DIR__ . '/../src/config/db.php';
require_once __DIR__ . '/../src/helpers/google_calendar.php';

echo "🔄 Iniciando sincronização com Google Calendar...\n\n";

// Buscar usuários com sincronização ativa
$stmtUsers = $pdo->query("
    SELECT DISTINCT user_id 
    FROM google_calendar_tokens 
    WHERE auto_sync_enabled = TRUE
");
$users = $stmtUsers->fetchAll(PDO::FETCH_COLUMN);

if (empty($users)) {
    echo "⚠️  Nenhum usuário com sincronização ativa.\n";
    exit(0);
}

$totalEventsSynced = 0;
$totalErrors = 0;

foreach ($users as $userId) {
    try {
        $googleCal = new GoogleCalendarIntegration($pdo, $userId);
        
        // Buscar eventos modificados desde última sincronização (últimas 24h)
        $stmt = $pdo->prepare("
            SELECT * FROM events 
            WHERE created_by = ? 
              AND (updated_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR) 
                   OR created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR))
        ");
        $stmt->execute([$userId]);
        $events = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($events as $event) {
            try {
                if (empty($event['google_event_id'])) {
                    // Criar no Google Calendar
                    $googleEventId = $googleCal->createEvent($event);
                    
                    if ($googleEventId) {
                        // Salvar ID do Google no banco
                        $pdo->prepare("UPDATE events SET google_event_id = ?, last_synced_at = NOW() WHERE id = ?")
                            ->execute([$googleEventId, $event['id']]);
                        
                        echo "  ✓ Evento criado: {$event['title']}\n";
                        $totalEventsSynced++;
                    }
                } else {
                    // Atualizar no Google Calendar
                    $success = $googleCal->updateEvent($event, $event['google_event_id']);
                    
                    if ($success) {
                        $pdo->prepare("UPDATE events SET last_synced_at = NOW() WHERE id = ?")
                            ->execute([$event['id']]);
                        
                        echo "  ✓ Evento atualizado: {$event['title']}\n";
                        $totalEventsSynced++;
                    }
                }
            } catch (Exception $e) {
                echo "  ✗ Erro ao sincronizar evento {$event['id']}: {$e->getMessage()}\n";
                $totalErrors++;
            }
        }
        
        // Buscar eventos excluídos do app que ainda existem no Google
        $stmtDeleted = $pdo->prepare("
            SELECT event_id, google_event_id 
            FROM google_calendar_sync_log 
            WHERE user_id = ? 
              AND action = 'deleted' 
              AND direction = 'app_to_google'
              AND synced_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
        ");
        $stmtDeleted->execute([$userId]);
        $deletedEvents = $stmtDeleted->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($deletedEvents as $deleted) {
            if (!empty($deleted['google_event_id'])) {
                try {
                    $googleCal->deleteEvent($deleted['google_event_id'], $deleted['event_id']);
                    echo "  ✓ Evento excluído do Google: {$deleted['google_event_id']}\n";
                    $totalEventsSynced++;
                } catch (Exception $e) {
                    echo "  ✗ Erro ao excluir do Google: {$e->getMessage()}\n";
                    $totalErrors++;
                }
            }
        }
        
    } catch (Exception $e) {
        echo "⚠️  Erro para usuário $userId: {$e->getMessage()}\n";
        $totalErrors++;
    }
}

echo "\n✅ Sincronização concluída!\n";
echo "   Eventos sincronizados: $totalEventsSynced\n";
echo "   Erros: $totalErrors\n";
