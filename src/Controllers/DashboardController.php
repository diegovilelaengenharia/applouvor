<?php
namespace App\Controllers;

use App\AuthMiddleware;
use App\Models\Schedule;
use App\Models\ScheduleUser;
use App\Models\Aviso;
use App\Models\Notification;

class DashboardController extends Controller
{
    // Tela 02: Dashboard / Início
    public function index()
    {
        AuthMiddleware::requireLogin();

        $userId = (int) $_SESSION['user_id'];

        // Próximo culto em que o usuário está escalado
        $scheduleModel = new Schedule($this->pdo);
        $upcoming = $scheduleModel->getUpcoming();
        $nextSchedule = null;
        $myStatus = null;
        $scheduleUserId = null;

        foreach ($upcoming as $s) {
            $stmt = $this->pdo->prepare("
                SELECT id, status FROM schedule_users
                WHERE schedule_id = :sid AND user_id = :uid
                LIMIT 1
            ");
            $stmt->execute(['sid' => $s['id'], 'uid' => $userId]);
            $su = $stmt->fetch(\PDO::FETCH_ASSOC);
            if ($su) {
                $nextSchedule   = $s;
                $myStatus       = $su['status'];
                $scheduleUserId = $su['id'];

                // Carregar as músicas da escala
                $songs = $this->pdo->prepare("
                    SELECT s.title, s.artist, ss.presentation_order
                    FROM schedule_songs ss
                    JOIN songs s ON ss.song_id = s.id
                    WHERE ss.schedule_id = :sid
                    ORDER BY ss.presentation_order
                    LIMIT 5
                ");
                $songs->execute(['sid' => $s['id']]);
                $nextSchedule['songs'] = $songs->fetchAll(\PDO::FETCH_ASSOC);
                break;
            }
        }

        // Aviso mais recente e importante
        $avisoModel = new Aviso($this->pdo);
        $latestAviso = $avisoModel->getLatestImportant();

        // Contagem de notificações não lidas
        $notifModel  = new Notification($this->pdo);
        $unreadCount = $notifModel->countUnread($userId);

        $this->render('app/dashboard', [
            'userName'       => $_SESSION['user_name'] ?? 'Músico',
            'userRole'       => $_SESSION['user_role'] ?? 'user',
            'userInstrument' => $_SESSION['user_instrument'] ?? null,
            'nextSchedule'   => $nextSchedule,
            'myStatus'       => $myStatus,
            'scheduleUserId' => $scheduleUserId,
            'latestAviso'    => $latestAviso,
            'unreadCount'    => $unreadCount,
        ]);
    }

    // Confirmar ou recusar presença (POST /dashboard/presenca/{suId})
    public function updatePresence(int $suId)
    {
        AuthMiddleware::requireLogin();
        csrf_verify();

        $action = $_POST['action'] ?? '';
        $userId = (int) $_SESSION['user_id'];

        // Garante que o registro pertence ao usuário logado
        $stmt = $this->pdo->prepare("
            SELECT id FROM schedule_users WHERE id = :id AND user_id = :uid
        ");
        $stmt->execute(['id' => $suId, 'uid' => $userId]);

        if ($stmt->fetch()) {
            $newStatus = $action === 'confirm' ? 'confirmed' : 'declined';
            $this->pdo->prepare(
                "UPDATE schedule_users SET status = :status WHERE id = :id"
            )->execute(['status' => $newStatus, 'id' => $suId]);
        }

        $this->redirect('/dashboard');
    }
}
