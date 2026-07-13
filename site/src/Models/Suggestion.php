<?php
namespace App\Models;

use PDO;

class Suggestion extends Model
{
    protected string $table = 'song_suggestions';

    public function getByStatus(string $status = 'pending'): array
    {
        $stmt = $this->pdo->prepare("
            SELECT ss.*, u.name AS user_name, u.avatar_color
            FROM song_suggestions ss
            JOIN users u ON ss.user_id = u.id
            WHERE ss.status = :status
            ORDER BY ss.created_at DESC
        ");
        $stmt->execute(['status' => $status]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function countByStatus(): array
    {
        $stmt = $this->pdo->query("
            SELECT status, COUNT(*) AS total
            FROM song_suggestions
            GROUP BY status
        ");
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $counts = ['pending' => 0, 'approved' => 0, 'rejected' => 0];
        foreach ($rows as $r) {
            $counts[$r['status']] = (int)$r['total'];
        }
        return $counts;
    }

    public function create(array $data): int
    {
        $stmt = $this->pdo->prepare("
            INSERT INTO song_suggestions (user_id, title, artist, link, notes, status)
            VALUES (:user_id, :title, :artist, :link, :notes, 'pending')
        ");
        $stmt->execute([
            'user_id' => $data['user_id'],
            'title'   => $data['title'],
            'artist'  => $data['artist'] ?? '',
            'link'    => $data['link'] ?: null,
            'notes'   => $data['notes'] ?? '',
        ]);
        return (int)$this->pdo->lastInsertId();
    }

    public function updateStatus(int $id, string $status): void
    {
        $stmt = $this->pdo->prepare("UPDATE song_suggestions SET status = :status WHERE id = :id");
        $stmt->execute(['status' => $status, 'id' => $id]);
    }

    public function countPending(): int
    {
        return (int)$this->pdo->query(
            "SELECT COUNT(*) FROM song_suggestions WHERE status = 'pending'"
        )->fetchColumn();
    }
}
