<?php
namespace App\Controllers;

use App\AuthMiddleware;
use PDO;

class AgendaController extends Controller
{
    public function index()
    {
        AuthMiddleware::requireLogin();

        $filter = $_GET['filtro'] ?? 'proximos';

        if ($filter === 'passados') {
            $stmt = $this->pdo->query("
                SELECT s.id, s.event_date, s.event_time, s.event_type, s.notes,
                       COUNT(DISTINCT ss.song_id) AS total_musicas,
                       COUNT(DISTINCT su.user_id) AS total_membros
                FROM schedules s
                LEFT JOIN schedule_songs ss ON ss.schedule_id = s.id
                LEFT JOIN schedule_users su ON su.schedule_id = s.id AND su.status != 'declined'
                WHERE s.event_date < CURDATE()
                GROUP BY s.id
                ORDER BY s.event_date DESC
                LIMIT 30
            ");
        } else {
            $stmt = $this->pdo->query("
                SELECT s.id, s.event_date, s.event_time, s.event_type, s.notes,
                       COUNT(DISTINCT ss.song_id) AS total_musicas,
                       COUNT(DISTINCT su.user_id) AS total_membros
                FROM schedules s
                LEFT JOIN schedule_songs ss ON ss.schedule_id = s.id
                LEFT JOIN schedule_users su ON su.schedule_id = s.id AND su.status != 'declined'
                WHERE s.event_date >= CURDATE()
                GROUP BY s.id
                ORDER BY s.event_date ASC
            ");
        }

        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Group by month key (Y-m)
        $grouped = [];
        foreach ($rows as $row) {
            $monthKey = date('Y-m', strtotime($row['event_date']));
            $grouped[$monthKey][] = $row;
        }

        $this->render('app/agenda', compact('grouped', 'filter'));
    }
}
