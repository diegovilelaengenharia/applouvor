<?php
namespace App\Controllers;

use App\AuthMiddleware;
use App\Models\Member;
use PDO;

class ReportController extends Controller
{
    // Tela 22: Relatórios / Visão Geral
    public function index()
    {
        AuthMiddleware::requireAdmin();

        $period = $_GET['periodo'] ?? '1m';
        $days   = match($period) { '7d' => 7, '3m' => 90, default => 30 };
        $since  = date('Y-m-d', strtotime("-{$days} days"));

        $totalEscalas = $this->fetchScalar(
            "SELECT COUNT(*) FROM schedules WHERE date >= :since", ['since' => $since]
        );

        $totalMusicasTocadas = $this->fetchScalar("
            SELECT COUNT(DISTINCT ss.song_id)
            FROM schedule_songs ss
            JOIN schedules s ON s.id = ss.schedule_id
            WHERE s.date >= :since
        ", ['since' => $since]);

        $totalMembrosEscalados = $this->fetchScalar("
            SELECT COUNT(DISTINCT su.user_id)
            FROM schedule_users su
            JOIN schedules s ON s.id = su.schedule_id
            WHERE s.date >= :since
        ", ['since' => $since]);

        $totalMembros = $this->fetchScalar("SELECT COUNT(*) FROM users");

        $totalPresencaMedia = (int)($this->fetchScalar("
            SELECT ROUND(AVG(pct)) FROM (
                SELECT user_id,
                       SUM(CASE WHEN su.status != 'absent' THEN 1 ELSE 0 END) / COUNT(*) * 100 AS pct
                FROM schedule_users su
                JOIN schedules s ON s.id = su.schedule_id
                WHERE s.date >= :since
                GROUP BY su.user_id
            ) t
        ", ['since' => $since]) ?? 0);

        $totalFaltas = $this->fetchScalar("
            SELECT COUNT(*) FROM schedule_users su
            JOIN schedules s ON s.id = su.schedule_id
            WHERE su.status = 'absent' AND s.date >= :since
        ", ['since' => $since]);

        $totalIndisponibilidades = $this->fetchScalar(
            "SELECT COUNT(*) FROM user_unavailability WHERE created_at >= :since",
            ['since' => $since]
        );

        $stmt = $this->pdo->prepare("
            SELECT so.title, so.artist, COUNT(*) AS vezes
            FROM schedule_songs ss
            JOIN songs so ON so.id = ss.song_id
            JOIN schedules s ON s.id = ss.schedule_id
            WHERE s.date >= :since
            GROUP BY so.id
            ORDER BY vezes DESC
            LIMIT 5
        ");
        $stmt->execute(['since' => $since]);
        $topMusicasRows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $this->render('app/relatorios', compact(
            'period', 'totalEscalas', 'totalMusicasTocadas',
            'totalMembrosEscalados', 'totalMembros', 'totalPresencaMedia',
            'totalFaltas', 'totalIndisponibilidades', 'topMusicasRows'
        ));
    }

    // Tela 23: Aniversariantes
    public function birthdays()
    {
        AuthMiddleware::requireLogin();
        $model   = new Member($this->pdo);
        $porMes  = $model->getBirthdaysByMonth();
        $mesAtual = (int)date('n');
        $this->render('app/aniversariantes', compact('porMes', 'mesAtual'));
    }

    private function fetchScalar(string $sql, array $params = []): int
    {
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return (int)($stmt->fetchColumn() ?? 0);
    }
}
