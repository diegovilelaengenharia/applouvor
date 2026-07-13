<?php
namespace App\Models;

use PDO;

class Member extends Model
{
    protected string $table = 'users';

    public function getAll(string $search = '', string $sort = 'name'): array
    {
        $orderBy = $sort === 'presenca' ? 'presenca_pct DESC, u.name ASC' : 'u.name ASC';

        $sql = "
            SELECT u.id, u.name, u.role, u.instrument, u.avatar_color,
                   COUNT(su.id) AS total_escalas,
                   ROUND(
                       CASE WHEN COUNT(su.id) > 0
                            THEN SUM(CASE WHEN su.status != 'absent' THEN 1 ELSE 0 END) / COUNT(su.id) * 100
                            ELSE 0 END
                   ) AS presenca_pct
            FROM users u
            LEFT JOIN schedule_users su ON su.user_id = u.id
        ";
        $params = [];
        if ($search !== '') {
            $sql .= " WHERE u.name LIKE :search OR u.instrument LIKE :search";
            $params['search'] = "%{$search}%";
        }
        $sql .= " GROUP BY u.id ORDER BY {$orderBy}";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getById(int $id): ?array
    {
        $stmt = $this->pdo->prepare("
            SELECT u.id, u.name, u.role, u.instrument, u.phone, u.email,
                   u.avatar_color, u.bio, u.birth_date,
                   COUNT(su.id) AS total_escalas,
                   ROUND(
                       CASE WHEN COUNT(su.id) > 0
                            THEN SUM(CASE WHEN su.status != 'absent' THEN 1 ELSE 0 END) / COUNT(su.id) * 100
                            ELSE 0 END
                   ) AS presenca_pct
            FROM users u
            LEFT JOIN schedule_users su ON su.user_id = u.id
            WHERE u.id = :id
            GROUP BY u.id
        ");
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public function getNextSchedules(int $userId): array
    {
        $stmt = $this->pdo->prepare("
            SELECT s.id, s.title, s.date, s.time, su.status
            FROM schedules s
            JOIN schedule_users su ON su.schedule_id = s.id AND su.user_id = :uid
            WHERE s.date >= CURDATE()
            ORDER BY s.date ASC
            LIMIT 3
        ");
        $stmt->execute(['uid' => $userId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getBirthdaysByMonth(): array
    {
        $stmt = $this->pdo->query("
            SELECT id, name, instrument, avatar_color, phone, birth_date,
                   MONTH(birth_date) AS birth_month,
                   DAY(birth_date)   AS birth_day
            FROM users
            WHERE birth_date IS NOT NULL
            ORDER BY birth_month, birth_day
        ");
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $months = [];
        foreach ($rows as $row) {
            $months[(int)$row['birth_month']][] = $row;
        }
        return $months;
    }

    public function createMember(array $data): int
    {
        $stmt = $this->pdo->prepare("
            INSERT INTO users (name, role, instrument, phone, email, password, avatar_color)
            VALUES (:name, 'user', :instrument, :phone, :email, :password, :color)
        ");
        $stmt->execute([
            'name'       => $data['name'],
            'instrument' => $data['instrument'] ?? '',
            'phone'      => $data['phone'] ?? '',
            'email'      => $data['email'] ?: null,
            'password'   => password_hash($data['password'], PASSWORD_BCRYPT),
            'color'      => $data['avatar_color'] ?? '#2E7EED',
        ]);
        return (int)$this->pdo->lastInsertId();
    }
}
