<?php
namespace App\Repositories;

use PDO;
use Exception;

class MusicRepository
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * Busca músicas filtrando por texto, tag ou tom
     */
    public function getSongs(?string $search = '', ?int $tagId = null, ?string $tone = null, int $limit = 50): array
    {
        $sql = "
            SELECT s.*, MAX(sch.event_date) as last_played
            FROM songs s
        ";

        if ($tagId) {
            $sql .= " JOIN song_tags st ON s.id = st.song_id ";
        }

        $sql .= "
            LEFT JOIN schedule_songs ss ON ss.song_id = s.id
            LEFT JOIN schedules sch ON sch.id = ss.schedule_id
            WHERE 1=1
        ";

        $params = [];

        if ($tagId) {
            $sql .= " AND st.tag_id = :tagId ";
            $params['tagId'] = $tagId;
        } elseif ($tone) {
            $sql .= " AND s.tone = :tone ";
            $params['tone'] = $tone;
        }

        if (!empty($search)) {
            $sql .= " AND (s.title LIKE :search OR s.artist LIKE :search) ";
            $params['search'] = "%$search%";
        }

        $sql .= " GROUP BY s.id ORDER BY s.title ASC LIMIT :limit";

        $stmt = $this->pdo->prepare($sql);
        foreach ($params as $key => $val) {
            $stmt->bindValue(":$key", $val);
        }
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Busca tags de uma música
     */
    public function getSongTags(int $songId): array
    {
        $stmt = $this->pdo->prepare("
            SELECT t.id, t.name, t.color 
            FROM tags t 
            JOIN song_tags st ON t.id = st.tag_id 
            WHERE st.song_id = ? 
            ORDER BY t.name
        ");
        $stmt->execute([$songId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Busca todas as tags com contagem de músicas
     */
    public function getTagsWithCount(): array
    {
        $sql = "
            SELECT t.*, COUNT(st.song_id) as count 
            FROM tags t 
            LEFT JOIN song_tags st ON t.id = st.tag_id 
            GROUP BY t.id 
            ORDER BY t.name ASC
        ";
        $stmt = $this->pdo->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Busca uma tag específica
     */
    public function getTagById(int $tagId): ?array
    {
        $stmt = $this->pdo->prepare("SELECT name, color FROM tags WHERE id = ?");
        $stmt->execute([$tagId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ?: null;
    }

    /**
     * Busca todos os artistas com contagem de músicas
     */
    public function getArtistsWithCount(): array
    {
        $sql = "
            SELECT artist as name, COUNT(*) as count 
            FROM songs 
            WHERE artist IS NOT NULL AND artist != '' 
            GROUP BY artist 
            ORDER BY artist ASC
        ";
        $stmt = $this->pdo->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Busca tags mais usadas por um artista
     */
    public function getTopTagsByArtist(string $artist, int $limit = 2): array
    {
        $sql = "
            SELECT t.name, t.color, COUNT(*) as usage_count
            FROM songs s
            JOIN song_tags st ON s.id = st.song_id
            JOIN tags t ON st.tag_id = t.id
            WHERE s.artist = :artist
            GROUP BY t.id
            ORDER BY usage_count DESC
            LIMIT :limit
        ";
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(':artist', $artist);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Busca tons mais usados por um artista
     */
    public function getTopTonesByArtist(string $artist, int $limit = 2): array
    {
        $sql = "
            SELECT tone, COUNT(*) as usage_count
            FROM songs
            WHERE artist = :artist AND tone IS NOT NULL AND tone != ''
            GROUP BY tone
            ORDER BY usage_count DESC
            LIMIT :limit
        ";
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(':artist', $artist);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Busca os tons com contagem de músicas
     */
    public function getTonesWithCount(): array
    {
        $sql = "
            SELECT tone as name, COUNT(*) as count 
            FROM songs 
            WHERE tone IS NOT NULL AND tone != '' 
            GROUP BY tone 
            ORDER BY tone ASC
        ";
        $stmt = $this->pdo->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
