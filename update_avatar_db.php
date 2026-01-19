<?php
// update_avatar_db.php
require_once 'includes/db.php';

try {
    // Assuming we want to update the first user or 'admin' user
    // First, let's find the user. We'll try to find a user with role 'admin' or ID 1
    $stmt = $pdo->prepare("SELECT id, name FROM users WHERE role = 'admin' LIMIT 1");
    $stmt->execute();
    $user = $stmt->fetch();

    if (!$user) {
        // Fallback to ID 1
        $stmt = $pdo->prepare("SELECT id, name FROM users WHERE id = 1 LIMIT 1");
        $stmt->execute();
        $user = $stmt->fetch();
    }

    if ($user) {
        $userId = $user['id'];
        $avatarFilename = 'diego_avatar.jpg';

        $updateStmt = $pdo->prepare("UPDATE users SET avatar = :avatar WHERE id = :id");
        $updateStmt->execute([':avatar' => $avatarFilename, ':id' => $userId]);

        echo "Avatar updated successfully for user: " . $user['name'] . " (ID: " . $userId . ")";

        // Also try to update session if running in same context which is impossible via CLI usually
        // But for development, this script just updates the DB.
    } else {
        echo "No suitable user found to update.";
    }
} catch (PDOException $e) {
    echo "Error updating database: " . $e->getMessage();
}
