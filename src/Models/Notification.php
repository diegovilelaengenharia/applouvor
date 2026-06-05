<?php
namespace App\Models;

use PDO;

class Notification extends Model
{
    protected string $table = 'notifications';

    public function forUser(int $userId, ?string $type = null): array
    {
        $where = 'WHERE n.user_id = :uid';
        $params = ['uid' => $userId];

        if ($type && $type !== 'todas') {
            $where .= ' AND n.type = :type';
            $params['type'] = $type;
        }

        $stmt = $this->pdo->prepare("
            SELECT n.*
            FROM notifications n
            $where
            ORDER BY n.created_at DESC
            LIMIT 100
        ");
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function countUnread(int $userId): int
    {
        $stmt = $this->pdo->prepare(
            "SELECT COUNT(*) FROM notifications WHERE user_id = :uid AND is_read = 0"
        );
        $stmt->execute(['uid' => $userId]);
        return (int) $stmt->fetchColumn();
    }

    public function markRead(int $id, int $userId): void
    {
        $stmt = $this->pdo->prepare(
            "UPDATE notifications SET is_read = 1 WHERE id = :id AND user_id = :uid"
        );
        $stmt->execute(['id' => $id, 'uid' => $userId]);
    }

    public function markAllRead(int $userId): void
    {
        $stmt = $this->pdo->prepare(
            "UPDATE notifications SET is_read = 1 WHERE user_id = :uid"
        );
        $stmt->execute(['uid' => $userId]);
    }
}
