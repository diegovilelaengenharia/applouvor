<?php
namespace App\Controllers;

use App\AuthMiddleware;
use PDO;

class AutoScheduleController extends Controller
{
    public function index()
    {
        AuthMiddleware::requireAdmin();
        $this->render('escalas/auto', ['suggestions' => null, 'params' => []]);
    }

    public function generate()
    {
        AuthMiddleware::requireAdmin();
        csrf_verify();

        $date = trim($_POST['event_date'] ?? '');
        $type = trim($_POST['event_type'] ?? 'Culto de Domingo');

        if (!$date) {
            $date = date('Y-m-d', strtotime('next sunday'));
        }

        $stmt = $this->pdo->prepare("
            SELECT u.id, u.name, u.instrument, u.avatar_color,
                   COUNT(su.id)       AS total_escalas,
                   MAX(s.event_date)  AS last_escala
            FROM users u
            LEFT JOIN schedule_users su ON su.user_id = u.id
            LEFT JOIN schedules s ON s.id = su.schedule_id AND su.status != 'declined'
            WHERE NOT EXISTS (
                SELECT 1 FROM user_unavailability uv
                WHERE uv.user_id = u.id
                  AND :date BETWEEN uv.start_date AND uv.end_date
            )
            GROUP BY u.id
            ORDER BY total_escalas ASC, last_escala ASC NULLS FIRST
        ");
        $stmt->execute(['date' => $date]);
        $suggestions = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $params = compact('date', 'type');
        $this->render('escalas/auto', compact('suggestions', 'params'));
    }

    public function confirm()
    {
        AuthMiddleware::requireAdmin();
        csrf_verify();

        $date    = trim($_POST['event_date'] ?? '');
        $type    = trim($_POST['event_type'] ?? 'Culto de Domingo');
        $userIds = array_values(array_filter(array_map('intval', $_POST['user_ids'] ?? [])));

        if (!$date || empty($userIds)) {
            $_SESSION['flash']['error'] = 'Data e ao menos um membro são obrigatórios.';
            $this->redirect('/escalas/auto');
        }

        $ins = $this->pdo->prepare("
            INSERT INTO schedules (event_date, event_time, event_type)
            VALUES (:date, '19:00:00', :type)
        ");
        $ins->execute(['date' => $date, 'type' => $type]);
        $scheduleId = (int)$this->pdo->lastInsertId();

        $addUser = $this->pdo->prepare("
            INSERT INTO schedule_users (schedule_id, user_id, status)
            VALUES (:sid, :uid, 'pending')
        ");
        foreach ($userIds as $uid) {
            $addUser->execute(['sid' => $scheduleId, 'uid' => $uid]);
        }

        $_SESSION['flash']['success'] = 'Escala criada com sucesso!';
        $this->redirect("/escalas/{$scheduleId}");
    }
}
