<?php

namespace App\Models;

use PDO;

class Song extends Model
{
    protected string $table = 'songs';

    /**
     * Retorna todas as músicas ordenadas por título
     */
    public function getAll(): array
    {
        $stmt = $this->pdo->query("SELECT * FROM {$this->table} ORDER BY title ASC");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Busca músicas por termo
     */
    public function search(string $term): array
    {
        $term = '%' . $term . '%';
        $stmt = $this->pdo->prepare("SELECT * FROM {$this->table} WHERE title LIKE :t OR artist LIKE :a ORDER BY title ASC");
        $stmt->execute(['t' => $term, 'a' => $term]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Cria uma nova música
     */
    public function create(array $data): int
    {
        $sql = "INSERT INTO {$this->table} 
                (title, artist, bpm, duration, tone, link_letra, link_cifra, link_audio, link_video, notes) 
                VALUES 
                (:title, :artist, :bpm, :duration, :tone, :link_letra, :link_cifra, :link_audio, :link_video, :notes)";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            'title' => $data['title'],
            'artist' => $data['artist'] ?? null,
            'bpm' => $data['bpm'] ? (int) $data['bpm'] : null,
            'duration' => $data['duration'] ?? null,
            'tone' => $data['tone'] ?? null,
            'link_letra' => $data['link_letra'] ?? null,
            'link_cifra' => $data['link_cifra'] ?? null,
            'link_audio' => $data['link_audio'] ?? null,
            'link_video' => $data['link_video'] ?? null,
            'notes' => $data['notes'] ?? null
        ]);
        
        return (int) $this->pdo->lastInsertId();
    }

    /**
     * Atualiza uma música existente
     */
    public function update(int $id, array $data): void
    {
        $sql = "UPDATE {$this->table} SET 
                title = :title, 
                artist = :artist, 
                bpm = :bpm, 
                duration = :duration, 
                tone = :tone, 
                link_letra = :link_letra, 
                link_cifra = :link_cifra, 
                link_audio = :link_audio, 
                link_video = :link_video, 
                notes = :notes 
                WHERE id = :id";
                
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            'id' => $id,
            'title' => $data['title'],
            'artist' => $data['artist'] ?? null,
            'bpm' => $data['bpm'] ? (int) $data['bpm'] : null,
            'duration' => $data['duration'] ?? null,
            'tone' => $data['tone'] ?? null,
            'link_letra' => $data['link_letra'] ?? null,
            'link_cifra' => $data['link_cifra'] ?? null,
            'link_audio' => $data['link_audio'] ?? null,
            'link_video' => $data['link_video'] ?? null,
            'notes' => $data['notes'] ?? null
        ]);
    }
}
