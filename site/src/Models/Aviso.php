<?php
namespace App\Models;

use PDO;

class Aviso extends Model
{
    protected string $table = 'avisos';

    public function getAll(): array
    {
        $stmt = $this->pdo->query("
            SELECT a.*, u.name AS author_name
            FROM avisos a
            LEFT JOIN users u ON a.user_id = u.id
            ORDER BY a.fixado DESC,
                     FIELD(a.prioridade,'urgente','alta','media','baixa'),
                     a.created_at DESC
        ");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getActive(): array
    {
        $stmt = $this->pdo->query("
            SELECT a.*, u.name AS author_name
            FROM avisos a
            LEFT JOIN users u ON a.user_id = u.id
            WHERE a.data_expiracao IS NULL OR a.data_expiracao >= CURDATE()
            ORDER BY a.fixado DESC,
                     FIELD(a.prioridade,'urgente','alta','media','baixa'),
                     a.created_at DESC
        ");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getLatestImportant(): ?array
    {
        $stmt = $this->pdo->query("
            SELECT a.*, u.name AS author_name
            FROM avisos a
            LEFT JOIN users u ON a.user_id = u.id
            WHERE (a.data_expiracao IS NULL OR a.data_expiracao >= CURDATE())
              AND a.prioridade IN ('urgente','alta')
            ORDER BY a.fixado DESC, a.created_at DESC
            LIMIT 1
        ");
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public function getById(int $id): ?array
    {
        $stmt = $this->pdo->prepare("
            SELECT a.*, u.name AS author_name
            FROM avisos a
            LEFT JOIN users u ON a.user_id = u.id
            WHERE a.id = :id
        ");
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public function create(array $data): int
    {
        $stmt = $this->pdo->prepare("
            INSERT INTO avisos (titulo, conteudo, tipo, prioridade, fixado, data_expiracao, user_id)
            VALUES (:titulo, :conteudo, :tipo, :prioridade, :fixado, :data_expiracao, :user_id)
        ");
        $stmt->execute([
            'titulo'          => $data['titulo'],
            'conteudo'        => $data['conteudo'],
            'tipo'            => $data['tipo'] ?? 'geral',
            'prioridade'      => $data['prioridade'] ?? 'media',
            'fixado'          => $data['fixado'] ?? 0,
            'data_expiracao'  => $data['data_expiracao'] ?: null,
            'user_id'         => $data['user_id'],
        ]);
        return (int) $this->pdo->lastInsertId();
    }

    public function delete(int $id): void
    {
        $stmt = $this->pdo->prepare("DELETE FROM avisos WHERE id = :id");
        $stmt->execute(['id' => $id]);
    }
}
