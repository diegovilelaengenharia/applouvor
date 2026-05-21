<?php
namespace App\Repositories;

use PDO;
use Exception;

class ScheduleRepository
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * Busca escalas futuras
     */
    public function getFutureSchedules(string $filterType = ''): array
    {
        $sql = "SELECT * FROM schedules WHERE event_date >= CURDATE()";
        if (!empty($filterType)) {
            $sql .= " AND event_type = :eventType";
        }
        $sql .= " ORDER BY event_date ASC";

        $stmt = $this->pdo->prepare($sql);
        if (!empty($filterType)) {
            $stmt->bindValue(':eventType', $filterType);
        }
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Busca escalas passadas
     */
    public function getPastSchedules(string $filterType = '', int $limit = 15): array
    {
        $sql = "SELECT * FROM schedules WHERE event_date < CURDATE()";
        if (!empty($filterType)) {
            $sql .= " AND event_type = :eventType";
        }
        $sql .= " ORDER BY event_date DESC LIMIT :limit";

        $stmt = $this->pdo->prepare($sql);
        if (!empty($filterType)) {
            $stmt->bindValue(':eventType', $filterType);
        }
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Busca todos os participantes de uma lista de escalas
     */
    public function getParticipantsByScheduleIds(array $scheduleIds): array
    {
        if (empty($scheduleIds)) return [];
        $inQuery = implode(',', array_fill(0, count($scheduleIds), '?'));
        
        $sql = "SELECT su.schedule_id, su.user_id, u.name, u.photo, u.avatar, u.avatar_color, su.status 
                FROM schedule_users su 
                JOIN users u ON su.user_id = u.id 
                WHERE su.schedule_id IN ($inQuery)";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($scheduleIds);
        
        $participantsMap = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $participantsMap[$row['schedule_id']][] = $row;
        }
        return $participantsMap;
    }

    /**
     * Busca contagem de músicas de uma lista de escalas
     */
    public function getSongCountsByScheduleIds(array $scheduleIds): array
    {
        if (empty($scheduleIds)) return [];
        $inQuery = implode(',', array_fill(0, count($scheduleIds), '?'));
        
        $sql = "SELECT schedule_id, COUNT(*) as total FROM schedule_songs WHERE schedule_id IN ($inQuery) GROUP BY schedule_id";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($scheduleIds);
        
        $countsMap = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $countsMap[$row['schedule_id']] = $row['total'];
        }
        return $countsMap;
    }

    /**
     * Busca os detalhes de uma escala pelo ID
     */
    public function getById(int $id): ?array
    {
        $stmt = $this->pdo->prepare("SELECT * FROM schedules WHERE id = ?");
        $stmt->execute([$id]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ?: null;
    }

    /**
     * Busca membros detalhados de uma escala específica
     */
    public function getParticipants(int $scheduleId): array
    {
        $stmt = $this->pdo->prepare("
            SELECT su.*, u.id as user_id, u.name, u.instrument, u.avatar, u.avatar_color, u.photo,
                   su.instrument as assigned_instrument, su.is_rehearsed
            FROM schedule_users su 
            JOIN users u ON su.user_id = u.id 
            WHERE su.schedule_id = ? 
            ORDER BY u.name
        ");
        $stmt->execute([$scheduleId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Busca músicas de uma escala específica
     */
    public function getSongs(int $scheduleId): array
    {
        $stmt = $this->pdo->prepare("
            SELECT ss.*, s.id as song_id, s.title, s.artist, s.tone, s.bpm, 
                   s.link_letra, s.link_cifra, s.link_audio, s.link_video 
            FROM schedule_songs ss 
            JOIN songs s ON ss.song_id = s.id 
            WHERE ss.schedule_id = ? 
            ORDER BY ss.position
        ");
        $stmt->execute([$scheduleId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Busca os comentários da escala
     */
    public function getComments(int $scheduleId): array
    {
        $stmt = $this->pdo->prepare("
            SELECT sc.*, u.name, u.avatar, u.avatar_color
            FROM schedule_comments sc
            JOIN users u ON sc.user_id = u.id
            WHERE sc.schedule_id = ?
            ORDER BY sc.created_at ASC
        ");
        $stmt->execute([$scheduleId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Busca roteiro da escala
     */
    public function getRoteiro(int $scheduleId): array
    {
        $stmt = $this->pdo->prepare("
            SELECT r.id, r.order_position, r.item_type, r.title,
                   r.song_id, r.custom_tone, r.nota_interna,
                   s.title as song_title, s.artist as song_artist, s.tone as song_tone
            FROM schedule_roteiro r
            LEFT JOIN songs s ON s.id = r.song_id
            WHERE r.schedule_id = ?
            ORDER BY r.order_position ASC, r.id ASC
        ");
        $stmt->execute([$scheduleId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Busca push subscriptions dos participantes pendentes
     */
    public function getPendingParticipantsPushSubscriptions(int $scheduleId): array
    {
        $stmt = $this->pdo->prepare("
            SELECT su.user_id, ps.endpoint, ps.p256dh, ps.auth
            FROM schedule_users su
            JOIN push_subscriptions ps ON ps.user_id = su.user_id
            WHERE su.schedule_id = ? AND su.status = 'pending'
        ");
        $stmt->execute([$scheduleId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Atualiza o status de ensaio de um usuário na escala
     */
    public function toggleRehearsal(int $scheduleId, int $userId, int $newState): bool
    {
        $stmt = $this->pdo->prepare("UPDATE schedule_users SET is_rehearsed = ? WHERE schedule_id = ? AND user_id = ?");
        return $stmt->execute([$newState, $scheduleId, $userId]);
    }

    /**
     * Adiciona comentário na escala
     */
    public function addComment(int $scheduleId, int $userId, string $comment): bool
    {
        $stmt = $this->pdo->prepare("INSERT INTO schedule_comments (schedule_id, user_id, comment) VALUES (?, ?, ?)");
        return $stmt->execute([$scheduleId, $userId, $comment]);
    }

    /**
     * Deleta um comentário (verifica dono)
     */
    public function deleteComment(int $commentId, int $userId, bool $isAdmin = false): bool
    {
        $stmt = $this->pdo->prepare("SELECT user_id FROM schedule_comments WHERE id = ?");
        $stmt->execute([$commentId]);
        $owner = $stmt->fetchColumn();
        
        if ($owner == $userId || $isAdmin) {
            return $this->pdo->prepare("DELETE FROM schedule_comments WHERE id = ?")->execute([$commentId]);
        }
        return false;
    }

    /**
     * Deleta escala completa (Admin)
     */
    public function deleteSchedule(int $id): void
    {
        $this->pdo->beginTransaction();
        try {
            $this->pdo->prepare("DELETE FROM schedule_users WHERE schedule_id = ?")->execute([$id]);
            $this->pdo->prepare("DELETE FROM schedule_songs WHERE schedule_id = ?")->execute([$id]);
            $this->pdo->prepare("DELETE FROM schedules WHERE id = ?")->execute([$id]);
            $this->pdo->commit();
        } catch (Exception $e) {
            $this->pdo->rollBack();
            throw $e;
        }
    }

    /**
     * Atualiza dados básicos, membros e músicas de uma escala (Admin)
     */
    public function updateSchedule(int $id, array $data, array $members, array $songs): void
    {
        $this->pdo->beginTransaction();
        try {
            // Atualizar Agenda
            $notes = $data['notes'] ?? '';
            $stmt = $this->pdo->prepare("UPDATE schedules SET event_type = ?, event_date = ?, event_time = ?, notes = ? WHERE id = ?");
            $stmt->execute([$data['event_type'], $data['event_date'], $data['event_time'], $notes, $id]);
            
            // Atualizar Membros
            $this->pdo->prepare("DELETE FROM schedule_users WHERE schedule_id = ?")->execute([$id]);
            if (!empty($members)) {
                $stmtUser = $this->pdo->prepare("INSERT INTO schedule_users (schedule_id, user_id, instrument, status, is_rehearsed) VALUES (?, ?, ?, 'pending', 0)");
                foreach ($members as $uid => $role) {
                    $roleToSave = (is_string($role) && !empty($role)) ? $role : null;
                    if(is_numeric($uid) && $uid > 0) {
                        try {
                            $stmtUser->execute([$id, $uid, $roleToSave]);
                        } catch (\PDOException $e) { }
                    }
                }
            }

            // Atualizar Músicas
            $this->pdo->prepare("DELETE FROM schedule_songs WHERE schedule_id = ?")->execute([$id]);
            if (!empty($songs)) {
                $stmtSong = $this->pdo->prepare("INSERT INTO schedule_songs (schedule_id, song_id, position) VALUES (?, ?, ?)");
                foreach ($songs as $pos => $sid) {
                    $stmtSong->execute([$id, $sid, $pos + 1]);
                }
            }

            $this->pdo->commit();
        } catch (Exception $e) {
            $this->pdo->rollBack();
            throw $e;
        }
    }
}
