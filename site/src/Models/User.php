<?php

namespace App\Models;

use PDO;

class User extends Model
{
    protected string $table = 'users';

    /**
     * Atualiza os dados de perfil do usuário (Tela 12).
     */
    public function updateProfile(int $id, array $data): void
    {
        $sql = "UPDATE {$this->table} SET
                name = :name,
                email = :email,
                phone = :phone,
                instrument = :instrument,
                bio = :bio,
                birth_date = :birth_date
                WHERE id = :id";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            'id'         => $id,
            'name'       => $data['name'],
            'email'      => $data['email'] ?? null,
            'phone'      => $data['phone'] ?? null,
            'instrument' => $data['instrument'] ?? null,
            'bio'        => $data['bio'] ?? null,
            'birth_date' => !empty($data['birth_date']) ? $data['birth_date'] : null,
        ]);
    }

    /**
     * Atualiza apenas a senha (hash bcrypt) — Tela 34.
     */
    public function updatePassword(int $id, string $hash): void
    {
        $stmt = $this->pdo->prepare("UPDATE {$this->table} SET password = :p WHERE id = :id");
        $stmt->execute(['p' => $hash, 'id' => $id]);
    }
}
