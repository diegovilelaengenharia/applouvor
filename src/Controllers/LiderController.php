<?php
namespace App\Controllers;

use App\AuthMiddleware;

class LiderController extends Controller
{
    // Tela 24: Painel do Líder
    public function index()
    {
        AuthMiddleware::requireAdmin();

        $pendingSugestoes = (int)$this->pdo->query(
            "SELECT COUNT(*) FROM song_suggestions WHERE status = 'pending'"
        )->fetchColumn();

        $escalaSemFaltas = (int)$this->pdo->query("
            SELECT COUNT(*) FROM schedules s
            WHERE s.date < CURDATE()
              AND NOT EXISTS (
                  SELECT 1 FROM schedule_users su
                  WHERE su.schedule_id = s.id
                    AND su.status IN ('confirmed', 'absent', 'declined')
              )
        ")->fetchColumn();

        $confirmacoesPendentes = (int)$this->pdo->query(
            "SELECT COUNT(*) FROM schedule_users WHERE status = 'pending'"
        )->fetchColumn();

        $this->render('app/lider', compact(
            'pendingSugestoes', 'escalaSemFaltas', 'confirmacoesPendentes'
        ));
    }
}
