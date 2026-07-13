<?php

namespace App\Models;

use PDO;

class Schedule extends Model
{
    protected string $table = 'schedules';

    /**
     * Retorna todas as escalas ordenadas pela data (cultos futuros primeiro)
     */
    public function getUpcoming(): array
    {
        $stmt = $this->pdo->query("SELECT * FROM {$this->table} WHERE event_date >= CURDATE() ORDER BY event_date ASC, event_time ASC");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Retorna as escalas antigas
     */
    public function getPast(): array
    {
        $stmt = $this->pdo->query("SELECT * FROM {$this->table} WHERE event_date < CURDATE() ORDER BY event_date DESC, event_time DESC");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Retorna a escala com seus participantes (users)
     */
    public function getWithParticipants(int $id): array
    {
        // 1. Pega a escala
        $schedule = $this->find($id);
        if (!$schedule) {
            return [];
        }

        // 2. Pega os usuários
        $sql = "
            SELECT su.*, u.name, u.avatar_color, u.avatar
            FROM schedule_users su
            JOIN users u ON su.user_id = u.id
            WHERE su.schedule_id = :id
            ORDER BY su.id ASC
        ";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['id' => $id]);
        $participants = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $schedule['participants'] = $participants;
        
        return $schedule;
    }

    /**
     * Cria uma nova escala
     */
    public function create(array $data): int
    {
        $sql = "INSERT INTO {$this->table} (event_date, event_time, event_type, notes) VALUES (:event_date, :event_time, :event_type, :notes)";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            'event_date' => $data['event_date'],
            'event_time' => $data['event_time'] ?? '09:00:00',
            'event_type' => $data['event_type'] ?? 'Culto de Domingo',
            'notes' => $data['notes'] ?? null
        ]);
        
        return (int) $this->pdo->lastInsertId();
    }
}
