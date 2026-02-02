<?php
/**
 * API de Notificações
 * Endpoints para gerenciar notificações
 */

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/notification_system.php';

header('Content-Type: application/json; charset=utf-8');

// Verificar autenticação
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Não autenticado']);
    exit;
}

$userId = $_SESSION['user_id'];
$action = $_GET['action'] ?? '';

$notificationSystem = new NotificationSystem($pdo);

try {
    switch ($action) {
        case 'list':
            $limit = (int) ($_GET['limit'] ?? 50);
            $offset = (int) ($_GET['offset'] ?? 0);
            $unreadOnly = isset($_GET['unread_only']) && $_GET['unread_only'] === 'true';
            
            $notifications = $notificationSystem->getByUser($userId, $limit, $offset, $unreadOnly);
            
            echo json_encode([
                'success' => true,
                'notifications' => $notifications,
                'count' => count($notifications)
            ]);
            break;
            
        case 'count_unread':
            $count = $notificationSystem->countUnread($userId);
            
            echo json_encode([
                'success' => true,
                'count' => $count
            ]);
            break;

        case 'stats':
            $stmtStats = $notificationSystem->pdo->prepare("
                SELECT 
                    COUNT(*) as total,
                    SUM(CASE WHEN is_read = 0 THEN 1 ELSE 0 END) as unread,
                    SUM(CASE WHEN is_read = 1 THEN 1 ELSE 0 END) as `read`
                FROM notifications 
                WHERE user_id = ?
            ");
            $stmtStats->execute([$userId]);
            $stats = $stmtStats->fetch(PDO::FETCH_ASSOC); // Fetch as associative array

            // Ensure values are integers (checked for nulls)
            $response = [
                'total' => (int)($stats['total'] ?? 0),
                'unread' => (int)($stats['unread'] ?? 0),
                'read' => (int)($stats['read'] ?? 0)
            ];

            echo json_encode([
                'success' => true,
                'stats' => $response
            ]);
            break;

        case 'public_key':
             $configFile = __DIR__ . '/../includes/vapid_config.php';
             if (file_exists($configFile)) {
                 $config = require $configFile;
                 echo json_encode([
                     'success' => true,
                     'publicKey' => $config['publicKey']
                 ]);
             } else {
                 echo json_encode(['success' => false, 'error' => 'Configuração não encontrada']);
             }
             break;
            
        case 'mark_read':
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                throw new Exception('Método não permitido');
            }
            
            $data = json_decode(file_get_contents('php://input'), true);
            $notificationId = $data['id'] ?? null;
            
            if (!$notificationId) {
                throw new Exception('ID da notificação não fornecido');
            }
            
            $success = $notificationSystem->markAsRead($notificationId, $userId);
            
            echo json_encode([
                'success' => $success,
                'message' => $success ? 'Notificação marcada como lida' : 'Erro ao marcar como lida'
            ]);
            break;
            
        case 'mark_all_read':
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                throw new Exception('Método não permitido');
            }
            
            $success = $notificationSystem->markAllAsRead($userId);
            
            echo json_encode([
                'success' => $success,
                'message' => $success ? 'Todas as notificações marcadas como lidas' : 'Erro ao marcar como lidas'
            ]);
            break;
            
        case 'delete':
            if ($_SERVER['REQUEST_METHOD'] !== 'DELETE' && $_SERVER['REQUEST_METHOD'] !== 'POST') {
                throw new Exception('Método não permitido');
            }
            
            $data = json_decode(file_get_contents('php://input'), true);
            $notificationId = $data['id'] ?? null;
            
            if (!$notificationId) {
                throw new Exception('ID da notificação não fornecido');
            }
            
            $success = $notificationSystem->delete($notificationId, $userId);
            
            echo json_encode([
                'success' => $success,
                'message' => $success ? 'Notificação deletada' : 'Erro ao deletar notificação'
            ]);
            break;
            
        default:
            throw new Exception('Ação inválida');
    }
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>
