<?php

namespace App\Models;

use PDO;

class UserSetting extends Model
{
    protected string $table = 'user_settings';

    /**
     * Retorna todas as configurações de um usuário como array key => value.
     */
    public function allFor(int $userId): array
    {
        $stmt = $this->pdo->prepare("SELECT setting_key, setting_value FROM {$this->table} WHERE user_id = :uid");
        $stmt->execute(['uid' => $userId]);

        $out = [];
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $out[$row['setting_key']] = $row['setting_value'];
        }
        return $out;
    }

    /**
     * Lê uma configuração específica (com fallback).
     */
    public function get(int $userId, string $key, $default = null)
    {
        $stmt = $this->pdo->prepare("SELECT setting_value FROM {$this->table} WHERE user_id = :uid AND setting_key = :k");
        $stmt->execute(['uid' => $userId, 'k' => $key]);
        $val = $stmt->fetchColumn();
        return $val === false ? $default : $val;
    }

    /**
     * Grava/atualiza uma configuração (upsert via UNIQUE user_id+setting_key).
     */
    public function set(int $userId, string $key, string $value): void
    {
        $sql = "INSERT INTO {$this->table} (user_id, setting_key, setting_value)
                VALUES (:uid, :k, :v)
                ON DUPLICATE KEY UPDATE setting_value = :v2";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['uid' => $userId, 'k' => $key, 'v' => $value, 'v2' => $value]);
    }
}
