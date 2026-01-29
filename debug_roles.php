<?php
require_once 'includes/db.php';

// Check if 'Teclado' role exists
$stmt = $pdo->query("SELECT * FROM roles WHERE name LIKE '%Teclado%'");
$roles = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "<h2>Roles matching 'Teclado'</h2>";
if ($roles) {
    echo "<pre>" . print_r($roles, true) . "</pre>";
} else {
    echo "No role found for 'Teclado'.<br>";
}

// Check Mariana's user
$stmt = $pdo->query("SELECT * FROM users WHERE name LIKE '%Mariana%'");
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "<h2>User Mariana</h2>";
echo "<pre>" . print_r($users, true) . "</pre>";

// Check her current roles
if (!empty($users)) {
    $uid = $users[0]['id'];
    $stmt = $pdo->prepare("SELECT r.* FROM user_roles ur JOIN roles r ON ur.role_id = r.id WHERE ur.user_id = ?");
    $stmt->execute([$uid]);
    $uRoles = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "<h2>Mariana's Assigned Roles</h2>";
    echo "<pre>" . print_r($uRoles, true) . "</pre>";
}
?>
