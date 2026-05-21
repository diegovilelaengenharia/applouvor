<?php
// admin/debug_check_users.php - Listar usuários e senhas hash (diagnóstico)
header('Content-Type: text/plain; charset=utf-8');
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once '../src/config/db.php';

echo "=== USUÁRIOS DO SISTEMA ===\n\n";
$stmt = $pdo->query("SELECT id, name, role, LEFT(password, 40) as pwd_prefix, last_login FROM users ORDER BY id");
$rows = $stmt->fetchAll();
foreach ($rows as $row) {
    echo "ID: {$row['id']} | Nome: {$row['name']} | Role: {$row['role']}\n";
    echo "  Senha (início): {$row['pwd_prefix']}...\n";
    echo "  É hash bcrypt: " . (str_starts_with($row['pwd_prefix'], '$2y$') ? 'SIM' : 'NÃO (texto plano)') . "\n";
    echo "  Último login: {$row['last_login']}\n\n";
}
echo "Total: " . count($rows) . " usuários\n";
echo "=== FIM ===\n";
