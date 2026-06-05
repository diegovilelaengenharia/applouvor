<?php

namespace App\Models;

use PDO;

class Unavailability extends Model
{
    protected string $table = 'user_unavailability';

    /**
     * Períodos de indisponibilidade de um usuário (mais recentes primeiro).
     */
    public function forUser(int $userId): array
    {
        $stmt = $this->pdo->prepare("SELECT * FROM {$this->table} WHERE user_id = :uid ORDER BY start_date DESC");
        $stmt->execute(['uid' => $userId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Cria um novo período de indisponibilidade.
     */
    public function create(array $data): int
    {
        $sql = "INSERT INTO {$this->table} (user_id, start_date, end_date, reason)
                VALUES (:uid, :start, :end, :reason)";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            'uid'    => $data['user_id'],
            'start'  => $data['start_date'],
            'end'    => $data['end_date'],
            'reason' => $data['reason'] ?? null,
        ]);
        return (int) $this->pdo->lastInsertId();
    }

    /**
     * Remove um período — só se pertencer ao usuário (segurança).
     */
    public function deleteOwned(int $id, int $userId): void
    {
        $stmt = $this->pdo->prepare("DELETE FROM {$this->table} WHERE id = :id AND user_id = :uid");
        $stmt->execute(['id' => $id, 'uid' => $userId]);
    }
}
