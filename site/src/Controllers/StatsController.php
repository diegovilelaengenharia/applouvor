<?php
namespace App\Controllers;

use App\AuthMiddleware;
use PDO;

class StatsController extends Controller
{
    public function repertorio()
    {
        AuthMiddleware::requireLogin();

        $period = $_GET['periodo'] ?? '30d';
        $since  = match($period) {
            '3m'  => date('Y-m-d', strtotime('-3 months')),
            'year'=> date('Y-m-d', strtotime('-1 year')),
            'all' => '2000-01-01',
            default => date('Y-m-d', strtotime('-30 days')),
        };

        $totalSongs = (int)$this->pdo->query("SELECT COUNT(*) FROM songs")->fetchColumn();

        $stmtPlayed = $this->pdo->prepare(
            "SELECT COUNT(DISTINCT ss.song_id) FROM schedule_songs ss
             JOIN schedules s ON s.id = ss.schedule_id WHERE s.event_date >= :since"
        );
        $stmtPlayed->execute(['since' => $since]);
        $totalPlayed = (int)$stmtPlayed->fetchColumn();

        $stmtTop = $this->pdo->prepare("
            SELECT so.id, so.title, so.artist, so.tone, so.bpm,
                   COUNT(ss.id) AS vezes
            FROM schedule_songs ss
            JOIN songs so ON so.id = ss.song_id
            JOIN schedules s ON s.id = ss.schedule_id
            WHERE s.event_date >= :since
            GROUP BY so.id
            ORDER BY vezes DESC
            LIMIT 10
        ");
        $stmtTop->execute(['since' => $since]);
        $topSongs = $stmtTop->fetchAll(PDO::FETCH_ASSOC);

        $stmtTones = $this->pdo->prepare("
            SELECT so.tone, COUNT(*) AS vezes
            FROM schedule_songs ss
            JOIN songs so ON so.id = ss.song_id
            JOIN schedules s ON s.id = ss.schedule_id
            WHERE s.event_date >= :since AND so.tone IS NOT NULL AND so.tone != ''
            GROUP BY so.tone
            ORDER BY vezes DESC
            LIMIT 8
        ");
        $stmtTones->execute(['since' => $since]);
        $toneDistrib = $stmtTones->fetchAll(PDO::FETCH_ASSOC);

        $stmtForgotten = $this->pdo->query("
            SELECT so.id, so.title, so.artist, MAX(s.event_date) AS last_played
            FROM songs so
            LEFT JOIN schedule_songs ss ON ss.song_id = so.id
            LEFT JOIN schedules s ON s.id = ss.schedule_id
            GROUP BY so.id
            HAVING last_played IS NULL OR last_played < DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
            ORDER BY last_played ASC
            LIMIT 5
        ");
        $forgotten = $stmtForgotten->fetchAll(PDO::FETCH_ASSOC);

        $this->render('repertorio/stats', compact(
            'period', 'totalSongs', 'totalPlayed', 'topSongs', 'toneDistrib', 'forgotten'
        ));
    }
}
