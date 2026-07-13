<?php
namespace App\Controllers;

use App\AuthMiddleware;
use PDO;

class SearchController extends Controller
{
    public function index()
    {
        AuthMiddleware::requireLogin();

        $q    = trim($_GET['q'] ?? '');
        $songs     = [];
        $members   = [];
        $schedules = [];

        if (mb_strlen($q) >= 2) {
            $like = '%' . $q . '%';

            $stmtSongs = $this->pdo->prepare("
                SELECT id, title, artist, tone, bpm
                FROM songs
                WHERE title LIKE :q OR artist LIKE :q
                ORDER BY title ASC
                LIMIT 10
            ");
            $stmtSongs->execute(['q' => $like]);
            $songs = $stmtSongs->fetchAll(PDO::FETCH_ASSOC);

            $stmtMembers = $this->pdo->prepare("
                SELECT id, name, instrument, avatar_color, role
                FROM users
                WHERE name LIKE :q OR instrument LIKE :q
                ORDER BY name ASC
                LIMIT 8
            ");
            $stmtMembers->execute(['q' => $like]);
            $members = $stmtMembers->fetchAll(PDO::FETCH_ASSOC);

            $stmtSched = $this->pdo->prepare("
                SELECT id, event_date, event_time, event_type
                FROM schedules
                WHERE event_type LIKE :q OR COALESCE(notes,'') LIKE :q
                ORDER BY event_date DESC
                LIMIT 6
            ");
            $stmtSched->execute(['q' => $like]);
            $schedules = $stmtSched->fetchAll(PDO::FETCH_ASSOC);
        }

        $total = count($songs) + count($members) + count($schedules);

        $this->render('app/busca', compact('q', 'songs', 'members', 'schedules', 'total'));
    }
}
