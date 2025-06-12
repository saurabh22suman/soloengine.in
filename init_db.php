<?php
// Script to initialize the database (ADMIN ACCESS ONLY if database exists)
session_start();

// Check if database already exists
$dbFile = __DIR__ . '/data/resume.db';
$dbExists = file_exists($dbFile);

// SECURITY: If database exists, require admin authentication
if ($dbExists) {
    if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
        http_response_code(403);
        error_log("Unauthorized database init attempt from IP: " . ($_SERVER['REMOTE_ADDR'] ?? 'unknown'));
        die('Access denied. Database already exists and admin authentication is required.');
    }
}

// Include the database connection
require_once 'includes/db_connect.php';

// This will trigger the database initialization if it doesn't exist
// or use/update the existing one if it does
$pdo = getDbConnection();

// Make sure the theme column exists in admin_settings table
try {
    $pdo->exec("ALTER TABLE admin_settings ADD COLUMN theme TEXT DEFAULT 'light'");
    echo "Database initialized or updated successfully!<br>";
} catch (PDOException $e) {
    // Column might already exist, that's fine
    echo "Database check completed!<br>";
}

echo "<a href='admin.php'>Go to Admin Panel</a>";

// Log the action
if ($dbExists) {
    error_log("Database update completed by admin: " . ($_SESSION['admin_username'] ?? 'unknown'));
} else {
    error_log("New database initialized");
}
?> 