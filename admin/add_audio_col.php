<?php
require_once '../includes/db.php';

try {
    // Add audio_path column
    $pdo->exec("ALTER TABLE user_unavailability ADD COLUMN audio_path VARCHAR(255) DEFAULT NULL");
    echo "Column 'audio_path' added successfully to 'user_unavailability' table.<br>";
} catch (PDOException $e) {
    if (strpos($e->getMessage(), 'Duplicate column') !== false) {
        echo "Column 'audio_path' already exists.<br>";
    } else {
        echo "Error adding column: " . $e->getMessage() . "<br>";
    }
}

// Create uploads directory if it doesn't exist
$uploadDir = '../uploads/audio';
if (!file_exists($uploadDir)) {
    if (mkdir($uploadDir, 0777, true)) {
        echo "Directory 'uploads/audio' created successfully.<br>";
    } else {
        echo "Failed to create directory 'uploads/audio'.<br>";
    }
} else {
    echo "Directory 'uploads/audio' already exists.<br>";
}
?>
