<?php
// Audit Script - Production Environment Check
// Usage: Upload to server, access via browser, then DELETE IMMEDIATELY.

require_once 'includes/config.php';
require_once 'includes/db.php';

echo '<!DOCTYPE html><html><head><title>System Audit</title><style>body{font-family:sans-serif;max-width:800px;margin:2rem auto;padding:1rem;} .ok{color:green;font-weight:bold;} .fail{color:red;font-weight:bold;} h2{border-bottom:1px solid #ccc;padding-bottom:0.5rem;}</style></head><body>';
echo '<h1>Production System Audit</h1>';
echo '<p>Environment: <strong>' . (defined('APP_ENV') ? APP_ENV : 'UNKNOWN') . '</strong></p>';

// 1. Security Checks
echo '<h2>1. Security Configuration</h2>';
echo '<ul>';

// Check display_errors
$displayErrors = ini_get('display_errors');
if ($displayErrors == 0 || strtolower($displayErrors) == 'off') {
    echo '<li>display_errors: <span class="ok">OFF (Secure)</span></li>';
} else {
    echo '<li>display_errors: <span class="fail">ON (Insecure: ' . $displayErrors . ')</span> - Should be Off in production.</li>';
}

// Check if .env exists (should NOT exist in Prod)
if (!file_exists(__DIR__ . '/.env')) {
    echo '<li>.env File: <span class="ok">Not Found (Correct)</span></li>';
} else {
    echo '<li>.env File: <span class="fail">Found!</span> - Ensure this is intentional and secured.</li>';
}

echo '</ul>';

// 2. Database Integrity
echo '<h2>2. Database Integrity</h2>';
try {
    // Check connection
    if ($pdo) {
        echo '<p>Database Connection: <span class="ok">OK</span></p>';
        
        // Check Tables
        $expectedTables = ['users', 'songs', 'scales', 'roles', 'avisos']; // Add critical tables here
        $foundTables = [];
        $stmt = $pdo->query("SHOW TABLES");
        $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        echo '<ul>';
        foreach ($expectedTables as $table) {
            if (in_array($table, $tables)) {
                echo "<li>Table '$table': <span class='ok'>Exists</span></li>";
            } else {
                echo "<li>Table '$table': <span class='fail'>MISSING</span></li>";
            }
        }
        echo '</ul>';
        
        // Check Charset
        $stmt = $pdo->query("SELECT @@character_set_database, @@collation_database");
        $charset = $stmt->fetch(PDO::FETCH_ASSOC);
        echo "<p>Charset: " . $charset['@@character_set_database'] . " (" . $charset['@@collation_database'] . ")</p>";
        
    } else {
        echo '<p>Database Connection: <span class="fail">FAILED</span></p>';
    }
} catch (PDOException $e) {
    echo '<p class="fail">Database Error: ' . $e->getMessage() . '</p>';
}

// 3. File System
echo '<h2>3. File System Check</h2>';
$criticalFiles = [
    'index.php',
    'includes/config.php',
    'includes/db.php',
    'includes/auth.php',
    'assets/css/design-system.css'
];

echo '<ul>';
foreach ($criticalFiles as $file) {
    if (file_exists(__DIR__ . '/' . $file)) {
        echo "<li>File '$file': <span class='ok'>Found</span></li>";
    } else {
        echo "<li>File '$file': <span class='fail'>MISSING</span></li>";
    }
}
echo '</ul>';

echo '<p><strong>Recommnedation:</strong> If all checks are green, delete this script immediately.</p>';
echo '</body></html>';
