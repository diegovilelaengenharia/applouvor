<?php
namespace App\Models;

use PDO;

class Devotional extends Model
{
    protected string $table = 'devotionals';

    public function getAll(): array
    {
        $stmt = $this->pdo->query("
            SELECT d.*,
                   u.name AS author_name,
                   (SELECT COUNT(*) FROM devotional_reads dr WHERE dr.devotional_id = d.id) AS read_count,
                   (SELECT COUNT(*) FROM devotional_comments dc WHERE dc.devotional_id = d.id) AS comment_count
            FROM devotionals d
            LEFT JOIN users u ON d.user_id = u.id
            ORDER BY d.created_at DESC
        ");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getById(int $id): ?array
    {
        $stmt = $this->pdo->prepare("
            SELECT d.*,
                   u.name AS author_name,
                   u.instrument,
                   (SELECT COUNT(*) FROM devotional_reads dr WHERE dr.devotional_id = d.id) AS read_count,
                   (SELECT COUNT(*) FROM devotional_comments dc WHERE dc.devotional_id = d.id) AS comment_count
            FROM devotionals d
            LEFT JOIN users u ON d.user_id = u.id
            WHERE d.id = :id
        ");
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public function getComments(int $devotionalId): array
    {
        $stmt = $this->pdo->prepare("
            SELECT dc.*, u.name AS author_name, u.avatar_color
            FROM devotional_comments dc
            JOIN users u ON dc.user_id = u.id
            WHERE dc.devotional_id = :id
            ORDER BY dc.created_at ASC
        ");
        $stmt->execute(['id' => $devotionalId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function hasRead(int $devotionalId, int $userId): bool
    {
        $stmt = $this->pdo->prepare(
            "SELECT 1 FROM devotional_reads WHERE devotional_id = :did AND user_id = :uid"
        );
        $stmt->execute(['did' => $devotionalId, 'uid' => $userId]);
        return (bool) $stmt->fetchColumn();
    }

    public function markRead(int $devotionalId, int $userId): void
    {
        $stmt = $this->pdo->prepare("
            INSERT IGNORE INTO devotional_reads (devotional_id, user_id)
            VALUES (:did, :uid)
        ");
        $stmt->execute(['did' => $devotionalId, 'uid' => $userId]);
    }

    public function addComment(int $devotionalId, int $userId, string $comment): int
    {
        $stmt = $this->pdo->prepare("
            INSERT INTO devotional_comments (devotional_id, user_id, comment)
            VALUES (:did, :uid, :comment)
        ");
        $stmt->execute(['did' => $devotionalId, 'uid' => $userId, 'comment' => $comment]);
        return (int) $this->pdo->lastInsertId();
    }

    public function getUserStreak(int $userId): int
    {
        $stmt = $this->pdo->prepare("
            SELECT DATE(completed_at) as read_date
            FROM devotional_reads
            WHERE user_id = :uid
            GROUP BY DATE(completed_at)
            ORDER BY read_date DESC
        ");
        $stmt->execute(['uid' => $userId]);
        $dates = $stmt->fetchAll(PDO::FETCH_COLUMN);

        $streak = 0;
        $today = new \DateTime('today');

        foreach ($dates as $dateStr) {
            $date = new \DateTime($dateStr);
            $diff = (int) $today->diff($date)->days;
            if ($diff === $streak) {
                $streak++;
                $today = $date;
            } else {
                break;
            }
        }

        return $streak;
    }
}
