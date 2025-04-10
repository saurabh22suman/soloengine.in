<?php
// Script to check the current theme value in the database
require_once 'includes/db_connect.php';

// Get a database connection
$pdo = getDbConnection();

try {
    // Get the current value
    $stmt = $pdo->query("SELECT id, username, password, theme FROM admin_settings");
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "Admin settings table contents:\n";
    foreach ($rows as $row) {
        echo "ID: " . $row['id'] . "\n";
        echo "Username: " . $row['username'] . "\n";
        echo "Password: " . $row['password'] . "\n";
        echo "Theme: " . (isset($row['theme']) ? $row['theme'] : 'NULL') . "\n";
        echo "-------------------\n";
    }
    
    // Ensure the theme value is set
    $count = $pdo->exec("UPDATE admin_settings SET theme = 'light' WHERE theme IS NULL OR theme = ''");
    echo "$count rows updated with default theme.\n";
    
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?> 