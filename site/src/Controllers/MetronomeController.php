<?php
namespace App\Controllers;

use App\AuthMiddleware;
use PDO;

class MetronomeController extends Controller
{
    public function index()
    {
        AuthMiddleware::requireLogin();

        $scheduleId = (int)($_GET['escala'] ?? 0);
        $songs      = [];
        $schedule   = null;

        if ($scheduleId) {
            $stmt = $this->pdo->prepare("SELECT id, event_date, event_type FROM schedules WHERE id = :id");
            $stmt->execute(['id' => $scheduleId]);
            $schedule = $stmt->fetch(PDO::FETCH_ASSOC) ?: null;

            if ($schedule) {
                $stmt = $this->pdo->prepare("
                    SELECT so.id, so.title, so.artist, so.bpm, so.tone, ss.presentation_order
                    FROM schedule_songs ss
                    JOIN songs so ON so.id = ss.song_id
                    WHERE ss.schedule_id = :sid
                    ORDER BY ss.presentation_order ASC
                ");
                $stmt->execute(['sid' => $scheduleId]);
                $songs = $stmt->fetchAll(PDO::FETCH_ASSOC);
            }
        } else {
            // Next upcoming schedule with songs
            $stmt = $this->pdo->query("
                SELECT s.id AS schedule_id, s.event_date, s.event_type,
                       so.id, so.title, so.artist, so.bpm, so.tone, ss.presentation_order
                FROM schedules s
                JOIN schedule_songs ss ON ss.schedule_id = s.id
                JOIN songs so ON so.id = ss.song_id
                WHERE s.event_date >= CURDATE()
                ORDER BY s.event_date ASC, ss.presentation_order ASC
                LIMIT 20
            ");
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
            if ($rows) {
                $schedule = ['id' => $rows[0]['schedule_id'], 'event_date' => $rows[0]['event_date'], 'event_type' => $rows[0]['event_type']];
                $songs = $rows;
            }
        }

        $this->render('app/metronomo', compact('songs', 'schedule'));
    }
}
