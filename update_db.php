<?php
// Script to add the theme column to the admin_settings table
require_once 'includes/db_connect.php';

// Get a database connection
$pdo = getDbConnection();

// Check if the theme column exists
try {
    $stmt = $pdo->query("PRAGMA table_info(admin_settings)");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "Checking admin_settings table structure...\n";
    
    $themeColumnExists = false;
    foreach ($columns as $column) {
        echo "Found column: " . $column['name'] . "\n";
        if ($column['name'] === 'theme') {
            $themeColumnExists = true;
            break;
        }
    }
    
    // Add the theme column if it doesn't exist
    if (!$themeColumnExists) {
        echo "Theme column does not exist. Adding it now...\n";
        $pdo->exec("ALTER TABLE admin_settings ADD COLUMN theme TEXT DEFAULT 'light'");
        
        // Verify the column was added
        $stmt = $pdo->query("PRAGMA table_info(admin_settings)");
        $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $themeColumnAdded = false;
        
        foreach ($columns as $column) {
            if ($column['name'] === 'theme') {
                $themeColumnAdded = true;
                break;
            }
        }
        
        if ($themeColumnAdded) {
            echo "Theme column added successfully!\n";
            
            // Update all rows with default theme
            $pdo->exec("UPDATE admin_settings SET theme = 'light' WHERE theme IS NULL");
            echo "Default theme set for all rows.\n";
        } else {
            echo "Failed to add theme column.\n";
        }
    } else {
        echo "Theme column already exists.\n";
    }
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?> 