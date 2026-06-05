<?php
namespace App\Models;

use PDO;

class PrayerRequest extends Model
{
    protected string $table = 'prayer_requests';

    public function getAll(?string $category = null): array
    {
        $where = '';
        $params = [];

        if ($category && $category !== 'todos') {
            $where = 'WHERE pr.category = :cat';
            $params['cat'] = $category;
        }

        $stmt = $this->pdo->prepare("
            SELECT pr.*,
                   u.name AS author_name,
                   u.avatar_color,
                   (SELECT COUNT(*) FROM prayer_interactions pi WHERE pi.prayer_id = pr.id AND pi.type = 'pray') AS pray_count,
                   (SELECT COUNT(*) FROM prayer_interactions pi WHERE pi.prayer_id = pr.id AND pi.type = 'comment') AS comment_count
            FROM prayer_requests pr
            LEFT JOIN users u ON pr.user_id = u.id
            $where
            ORDER BY pr.is_urgent DESC, pr.created_at DESC
        ");
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getById(int $id): ?array
    {
        $stmt = $this->pdo->prepare("
            SELECT pr.*,
                   u.name AS author_name,
                   u.avatar_color,
                   (SELECT COUNT(*) FROM prayer_interactions pi WHERE pi.prayer_id = pr.id AND pi.type = 'pray') AS pray_count
            FROM prayer_requests pr
            LEFT JOIN users u ON pr.user_id = u.id
            WHERE pr.id = :id
        ");
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public function getComments(int $prayerId): array
    {
        $stmt = $this->pdo->prepare("
            SELECT pi.*, u.name AS author_name, u.avatar_color
            FROM prayer_interactions pi
            JOIN users u ON pi.user_id = u.id
            WHERE pi.prayer_id = :id AND pi.type = 'comment'
            ORDER BY pi.created_at ASC
        ");
        $stmt->execute(['id' => $prayerId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function hasPrayed(int $prayerId, int $userId): bool
    {
        $stmt = $this->pdo->prepare(
            "SELECT 1 FROM prayer_interactions WHERE prayer_id = :pid AND user_id = :uid AND type = 'pray'"
        );
        $stmt->execute(['pid' => $prayerId, 'uid' => $userId]);
        return (bool) $stmt->fetchColumn();
    }

    public function pray(int $prayerId, int $userId): void
    {
        $stmt = $this->pdo->prepare("
            INSERT IGNORE INTO prayer_interactions (prayer_id, user_id, type)
            VALUES (:pid, :uid, 'pray')
        ");
        $stmt->execute(['pid' => $prayerId, 'uid' => $userId]);

        $this->pdo->prepare(
            "UPDATE prayer_requests SET prayer_count = prayer_count + 1 WHERE id = :id"
        )->execute(['id' => $prayerId]);
    }

    public function addComment(int $prayerId, int $userId, string $comment): void
    {
        $stmt = $this->pdo->prepare("
            INSERT INTO prayer_interactions (prayer_id, user_id, type, comment)
            VALUES (:pid, :uid, 'comment', :comment)
        ");
        $stmt->execute(['pid' => $prayerId, 'uid' => $userId, 'comment' => $comment]);
    }

    public function create(array $data): int
    {
        $stmt = $this->pdo->prepare("
            INSERT INTO prayer_requests (user_id, title, description, category, is_urgent, is_anonymous)
            VALUES (:user_id, :title, :description, :category, :is_urgent, :is_anonymous)
        ");
        $stmt->execute([
            'user_id'      => $data['user_id'],
            'title'        => $data['title'],
            'description'  => $data['description'] ?? null,
            'category'     => $data['category'] ?? 'other',
            'is_urgent'    => $data['is_urgent'] ?? 0,
            'is_anonymous' => $data['is_anonymous'] ?? 0,
        ]);
        return (int) $this->pdo->lastInsertId();
    }
}
