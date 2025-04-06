<?php
// Script to reset the database (for development purposes only)

// Include the database connection
require_once 'includes/db_connect.php';

// Delete the existing database file
$dbFile = __DIR__ . '/data/resume.db';
if (file_exists($dbFile)) {
    unlink($dbFile);
    echo "Existing database deleted.<br>";
}

// Get a database connection (this will re-initialize the database)
$pdo = getDbConnection();

echo "Database reset and initialized successfully!";
?> 