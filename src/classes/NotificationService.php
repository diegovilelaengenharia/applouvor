<?php
namespace App\Services;

use PDO;
use Exception;
use App\DB;

class NotificationService
{
    private PDO $pdo;
    private string $vapidPublic;
    private string $vapidPrivate;
    private string $subject;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
        $this->vapidPublic  = defined('VAPID_PUBLIC_KEY')  ? VAPID_PUBLIC_KEY  : (getenv('VAPID_PUBLIC_KEY')  ?: '');
        $this->vapidPrivate = defined('VAPID_PRIVATE_KEY') ? VAPID_PRIVATE_KEY : (getenv('VAPID_PRIVATE_KEY') ?: '');
        $this->subject = 'mailto:contato@pibolveira.com';
    }

    /**
     * Envia push notification para um usuário específico
     */
    public function sendPushToUser(int $userId, string $title, string $body, ?string $url = null): array
    {
        if (empty($this->vapidPublic) || empty($this->vapidPrivate)) {
            error_log("NotificationService: VAPID keys não configuradas");
            return ['success' => false, 'message' => 'VAPID keys não configuradas', 'sent' => 0];
        }

        try {
            // Buscar subscrições do usuário
            $stmt = $this->pdo->prepare("SELECT endpoint, p256dh, auth FROM push_subscriptions WHERE user_id = ?");
            $stmt->execute([$userId]);
            $subscriptions = $stmt->fetchAll(PDO::FETCH_ASSOC);

            if (empty($subscriptions)) {
                return ['success' => true, 'message' => 'Nenhuma assinatura encontrada para o usuário', 'sent' => 0];
            }

            require_once __DIR__ . '/../web_push_helper.php';
            $pushHelper = new \WebPushHelper($this->vapidPublic, $this->vapidPrivate, $this->subject);

            $payload = [
                'title' => $title,
                'body'  => $body,
                'url'   => $url ?? '/'
            ];

            $sent = 0;
            foreach ($subscriptions as $sub) {
                $ok = $pushHelper->sendNotification($sub, $payload);
                if ($ok) $sent++;
            }

            return ['success' => true, 'message' => 'Notificações enviadas', 'sent' => $sent];
        } catch (Exception $e) {
            error_log("NotificationService::sendPushToUser erro: " . $e->getMessage());
            return ['success' => false, 'message' => $e->getMessage(), 'sent' => 0];
        }
    }

    /**
     * Dispara push convocação para participantes pendentes de uma escala
     */
    public function sendConvocNotification(int $scheduleId, string $eventType, string $eventDate, string $eventTime, array $pushTargets): int
    {
        if (empty($this->vapidPublic) || empty($this->vapidPrivate) || empty($pushTargets)) {
            return 0;
        }

        try {
            require_once __DIR__ . '/../web_push_helper.php';
            $pushHelper = new \WebPushHelper($this->vapidPublic, $this->vapidPrivate, $this->subject);

            $formattedDate = date('d/m', strtotime($eventDate));
            $formattedTime = substr($eventTime, 0, 5);

            $convocPayload = [
                'title' => 'Nova Escala',
                'body'  => "Voce foi escalado para " . $eventType . " em $formattedDate as $formattedTime. Confirme no app!",
                'url'   => '/applouvor/admin/escala_detalhe.php?id=' . (int)$scheduleId,
            ];

            $sent = 0;
            foreach ($pushTargets as $target) {
                $sub = ['endpoint' => $target['endpoint'], 'p256dh' => $target['p256dh'], 'auth' => $target['auth']];
                $ok = $pushHelper->sendNotification($sub, $convocPayload);
                if ($ok) $sent++;
            }
            return $sent;
        } catch (Exception $e) {
            error_log("NotificationService::sendConvocNotification erro: " . $e->getMessage());
            return 0;
        }
    }
}
