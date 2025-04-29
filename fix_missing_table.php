<?php
// Include the database connection
require_once 'includes/db_connect.php';

// Get database connection
$pdo = getDbConnection();

// Check if the certificates_conferences table exists
$tableExists = false;
try {
    $result = $pdo->query("SELECT name FROM sqlite_master WHERE type='table' AND name='certificates_conferences'");
    $tableExists = $result->rowCount() > 0;
} catch (PDOException $e) {
    die("Error checking table existence: " . $e->getMessage());
}

// If the table doesn't exist, create it
if (!$tableExists) {
    try {
        // Create the certificates_conferences table
        $pdo->exec('CREATE TABLE certificates_conferences (
            id INTEGER PRIMARY KEY,
            title TEXT,
            description TEXT,
            date TEXT,
            type TEXT,
            issuer TEXT,
            url TEXT
        )');
        
        echo "Successfully created the certificates_conferences table.";
    } catch (PDOException $e) {
        die("Error creating table: " . $e->getMessage());
    }
} else {
    echo "The certificates_conferences table already exists.";
}
?> 