<?php
require_once 'includes/db.php';

$userId = 23;
$roleId = 4;

try {
    // Check if not already exists (just in case)
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM user_roles WHERE user_id = ? AND role_id = ?");
    $stmt->execute([$userId, $roleId]);
    if ($stmt->fetchColumn() == 0) {
        $stmt = $pdo->prepare("INSERT INTO user_roles (user_id, role_id) VALUES (?, ?)");
        $stmt->execute([$userId, $roleId]);
        echo "Sucesso: Role 'Teclado' atribuída para Mariana.";
    } else {
        echo "Mariana já possui essa role.";
    }
} catch (PDOException $e) {
    echo "Erro: " . $e->getMessage();
}
?>
