<?php
// Simple script to reset the database
require_once 'includes/db_connect.php';

// This will trigger the database initialization if it doesn't exist
// or use the existing one if it does
$pdo = getDbConnection();

// Make sure the theme column exists in admin_settings table
try {
    $pdo->exec("ALTER TABLE admin_settings ADD COLUMN theme TEXT DEFAULT 'light'");
    echo "Database initialized or updated successfully!";
} catch (PDOException $e) {
    // Column might already exist, that's fine
    echo "Database check completed!";
}
?> 