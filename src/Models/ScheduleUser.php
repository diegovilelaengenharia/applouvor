<?php

namespace App\Models;

use PDO;

class ScheduleUser extends Model
{
    protected string $table = 'schedule_users';

    /**
     * Adiciona um usuário a uma escala
     */
    public function assignUser(int $scheduleId, int $userId, string $instrument = null): void
    {
        // Verifica se já não está escalado
        $stmt = $this->pdo->prepare("SELECT id FROM {$this->table} WHERE schedule_id = :sid AND user_id = :uid");
        $stmt->execute(['sid' => $scheduleId, 'uid' => $userId]);
        if ($stmt->fetch()) {
            return; // Já está na escala
        }

        $sql = "INSERT INTO {$this->table} (schedule_id, user_id, assigned_instrument, status) VALUES (:sid, :uid, :inst, 'pending')";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            'sid' => $scheduleId,
            'uid' => $userId,
            'inst' => $instrument
        ]);
    }

    /**
     * Atualiza o status de um músico (confirmar/recusar)
     */
    public function updateStatus(int $scheduleId, int $userId, string $status, string $note = null): void
    {
        $sql = "UPDATE {$this->table} SET status = :status, absence_note = :note WHERE schedule_id = :sid AND user_id = :uid";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            'status' => $status,
            'note' => $note,
            'sid' => $scheduleId,
            'uid' => $userId
        ]);
    }
}
