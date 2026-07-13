<?php

namespace App\Controllers;

use App\AuthMiddleware;
use App\Models\Schedule;
use App\Models\ScheduleUser;

class ScheduleController extends Controller
{
    /**
     * Tela 03: Lista de Escalas
     */
    public function index()
    {
        AuthMiddleware::requireLogin();
        
        $scheduleModel = new Schedule($this->pdo);
        $upcoming = $scheduleModel->getUpcoming();
        $past = $scheduleModel->getPast();

        $this->render('escalas/index', [
            'upcoming' => $upcoming,
            'past' => $past
        ]);
    }

    /**
     * Tela 04: Detalhe da Escala
     */
    public function show($id)
    {
        AuthMiddleware::requireLogin();

        $scheduleModel = new Schedule($this->pdo);
        $schedule = $scheduleModel->getWithParticipants($id);

        if (!$schedule) {
            $_SESSION['flash']['error'] = 'Escala não encontrada.';
            $this->redirect('/escalas');
        }

        $this->render('escalas/show', ['schedule' => $schedule]);
    }

    /**
     * Tela 05: Criar Escala (GET)
     */
    public function create()
    {
        AuthMiddleware::requireLogin();
        if ($_SESSION['user_role'] !== 'admin') {
            $_SESSION['flash']['error'] = 'Acesso restrito.';
            $this->redirect('/escalas');
        }

        $this->render('escalas/form');
    }

    /**
     * Tela 05: Salvar nova Escala (POST)
     */
    public function store()
    {
        AuthMiddleware::requireLogin();
        if ($_SESSION['user_role'] !== 'admin') {
            $this->redirect('/escalas');
        }
        csrf_verify();

        $scheduleModel = new Schedule($this->pdo);
        $scheduleId = $scheduleModel->create([
            'event_date' => $_POST['event_date'] ?? date('Y-m-d'),
            'event_time' => $_POST['event_time'] ?? '09:00:00',
            'event_type' => $_POST['event_type'] ?? 'Culto de Domingo',
            'notes' => $_POST['notes'] ?? null
        ]);

        $_SESSION['flash']['success'] = 'Escala criada com sucesso!';
        $this->redirect("/escalas/{$scheduleId}");
    }

    /**
     * Tela 05: Editar Escala (GET)
     */
    public function edit($id)
    {
        AuthMiddleware::requireLogin();
        if ($_SESSION['user_role'] !== 'admin') {
            $this->redirect('/escalas');
        }

        $scheduleModel = new Schedule($this->pdo);
        $schedule = $scheduleModel->find($id);

        $this->render('escalas/form', ['schedule' => $schedule]);
    }

    /**
     * Tela 05: Atualizar Escala (POST)
     */
    public function update($id)
    {
        AuthMiddleware::requireLogin();
        if ($_SESSION['user_role'] !== 'admin') {
            $this->redirect('/escalas');
        }
        csrf_verify();

        // Implementação simplificada para o MVP. Adicionar bind dinâmico depois.
        $stmt = $this->pdo->prepare("UPDATE schedules SET event_date = :d, event_time = :t, event_type = :type, notes = :n WHERE id = :id");
        $stmt->execute([
            'd' => $_POST['event_date'],
            't' => $_POST['event_time'],
            'type' => $_POST['event_type'],
            'n' => $_POST['notes'],
            'id' => $id
        ]);

        $_SESSION['flash']['success'] = 'Escala atualizada!';
        $this->redirect("/escalas/{$id}");
    }

    /**
     * Ação do Músico: Confirmar ou Recusar (POST)
     */
    public function updateStatus($id)
    {
        AuthMiddleware::requireLogin();
        csrf_verify();

        $userId = $_SESSION['user_id'] ?? 0;
        $status = $_POST['status'] ?? 'pending';
        $note = $_POST['absence_note'] ?? null;

        if (in_array($status, ['confirmed', 'declined'])) {
            $suModel = new ScheduleUser($this->pdo);
            $suModel->updateStatus($id, $userId, $status, $note);
            $_SESSION['flash']['success'] = 'Status atualizado!';
        }

        $this->redirect("/escalas/{$id}");
    }

    /**
     * Tela 06: Faltas (GET)
     */
    public function attendance($id)
    {
        AuthMiddleware::requireLogin();
        if ($_SESSION['user_role'] !== 'admin') {
            $this->redirect('/escalas');
        }

        $scheduleModel = new Schedule($this->pdo);
        $schedule = $scheduleModel->getWithParticipants($id);

        $this->render('escalas/faltas', ['schedule' => $schedule]);
    }

    /**
     * Tela 06: Salvar Faltas (POST)
     */
    public function storeAttendance($id)
    {
        AuthMiddleware::requireLogin();
        if ($_SESSION['user_role'] !== 'admin') {
            $this->redirect('/escalas');
        }
        csrf_verify();

        $statuses = $_POST['status'] ?? []; // ex: ['user_id' => 'absent']
        $suModel = new ScheduleUser($this->pdo);

        foreach ($statuses as $userId => $status) {
            $suModel->updateStatus($id, $userId, $status);
        }

        $_SESSION['flash']['success'] = 'Faltas registradas com sucesso!';
        $this->redirect("/escalas/{$id}");
    }
}
