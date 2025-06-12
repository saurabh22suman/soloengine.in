<?php
// Script to check the current theme value in the database (ADMIN ACCESS ONLY)
session_start();

// SECURITY: Require admin authentication
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    error_log("Unauthorized theme check attempt from IP: " . ($_SERVER['REMOTE_ADDR'] ?? 'unknown'));
    http_response_code(403);
    die('Access denied. Admin authentication required.');
}

require_once 'includes/db_connect.php';

// Get a database connection
$pdo = getDbConnection();

try {
    // Get the current theme value only (no sensitive data)
    $stmt = $pdo->query("SELECT id, username, theme FROM admin_settings");
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "Admin settings (theme info only):\n";
    foreach ($rows as $row) {
        echo "ID: " . htmlspecialchars($row['id']) . "\n";
        echo "Username: " . htmlspecialchars($row['username']) . "\n";
        echo "Theme: " . htmlspecialchars($row['theme'] ?? 'NULL') . "\n";
        echo "-------------------\n";
    }
    
    // Ensure the theme value is set
    $count = $pdo->exec("UPDATE admin_settings SET theme = 'light' WHERE theme IS NULL OR theme = ''");
    echo "$count rows updated with default theme.\n";
    
    echo "\n<a href='admin.php'>Return to Admin Panel</a>";
    
} catch (PDOException $e) {
    error_log("Database error in check_theme.php: " . $e->getMessage());
    echo "Error checking theme settings. Please try again.\n";
}
?> 