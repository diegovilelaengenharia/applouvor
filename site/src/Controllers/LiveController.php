<?php
namespace App\Controllers;

use App\AuthMiddleware;
use PDO;

class LiveController extends Controller
{
    private function getScheduleWithSongs(int $id): ?array
    {
        $stmt = $this->pdo->prepare("SELECT * FROM schedules WHERE id = :id");
        $stmt->execute(['id' => $id]);
        $schedule = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$schedule) return null;

        $stmt = $this->pdo->prepare("
            SELECT so.id, so.title, so.artist, so.bpm, so.tone, so.link_cifra, ss.presentation_order
            FROM schedule_songs ss
            JOIN songs so ON so.id = ss.song_id
            WHERE ss.schedule_id = :sid
            ORDER BY ss.presentation_order ASC
        ");
        $stmt->execute(['sid' => $id]);
        $schedule['songs'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return $schedule;
    }

    public function live(int $id)
    {
        AuthMiddleware::requireLogin();
        $schedule = $this->getScheduleWithSongs($id);
        if (!$schedule) {
            $_SESSION['flash']['error'] = 'Escala não encontrada.';
            $this->redirect('/escalas');
        }
        $currentIndex = max(0, (int)($_GET['musica'] ?? 0));
        $this->render('escalas/ao-vivo', compact('schedule', 'currentIndex'));
    }

    public function rehearsal(int $id)
    {
        AuthMiddleware::requireLogin();
        $schedule = $this->getScheduleWithSongs($id);
        if (!$schedule) {
            $_SESSION['flash']['error'] = 'Escala não encontrada.';
            $this->redirect('/escalas');
        }
        $this->render('escalas/ensaio', compact('schedule'));
    }

    public function setlist(int $id)
    {
        AuthMiddleware::requireLogin();
        $schedule = $this->getScheduleWithSongs($id);
        if (!$schedule) {
            $_SESSION['flash']['error'] = 'Escala não encontrada.';
            $this->redirect('/escalas');
        }

        $stmt = $this->pdo->prepare("
            SELECT u.name, u.instrument, su.assigned_instrument
            FROM schedule_users su
            JOIN users u ON u.id = su.user_id
            WHERE su.schedule_id = :sid AND su.status != 'declined'
            ORDER BY u.name ASC
        ");
        $stmt->execute(['sid' => $id]);
        $participants = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $this->render('escalas/setlist', compact('schedule', 'participants'));
    }

    public function suggestSetlist(int $id)
    {
        AuthMiddleware::requireAdmin();
        $schedule = $this->getScheduleWithSongs($id);
        if (!$schedule) {
            $_SESSION['flash']['error'] = 'Escala não encontrada.';
            $this->redirect('/escalas');
        }

        $weeks = max(1, (int)($_GET['semanas'] ?? 4));
        $qty   = min(8, max(3, (int)($_GET['qty'] ?? 5)));
        $since = date('Y-m-d', strtotime("-{$weeks} weeks"));

        $stmt = $this->pdo->prepare("
            SELECT so.id, so.title, so.artist, so.bpm, so.tone,
                   MAX(s.event_date) AS last_played,
                   COUNT(ss2.id)    AS times_played
            FROM songs so
            LEFT JOIN schedule_songs ss2 ON ss2.song_id = so.id
            LEFT JOIN schedules s ON s.id = ss2.schedule_id
            GROUP BY so.id
            HAVING (last_played IS NULL OR last_played < :since)
            ORDER BY times_played ASC, RAND()
            LIMIT :qty
        ");
        $stmt->bindValue(':since', $since);
        $stmt->bindValue(':qty', $qty, PDO::PARAM_INT);
        $stmt->execute();
        $suggestions = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $this->render('escalas/setlist-sugerida', compact('schedule', 'suggestions', 'qty', 'weeks'));
    }

    public function saveSetlist(int $id)
    {
        AuthMiddleware::requireAdmin();
        csrf_verify();

        $songIds = array_values(array_filter(array_map('intval', $_POST['songs'] ?? [])));
        if (empty($songIds)) {
            $_SESSION['flash']['error'] = 'Selecione ao menos uma música.';
            $this->redirect("/escalas/{$id}/setlist-sugerida");
        }

        $del = $this->pdo->prepare("DELETE FROM schedule_songs WHERE schedule_id = :sid");
        $del->execute(['sid' => $id]);

        $ins = $this->pdo->prepare("
            INSERT INTO schedule_songs (schedule_id, song_id, presentation_order)
            VALUES (:sid, :song_id, :ord)
        ");
        foreach ($songIds as $order => $songId) {
            $ins->execute(['sid' => $id, 'song_id' => $songId, 'ord' => $order + 1]);
        }

        $_SESSION['flash']['success'] = 'Setlist salvo na escala!';
        $this->redirect("/escalas/{$id}");
    }
}
