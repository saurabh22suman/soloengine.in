<?php
// Script to initialize the database

// Include the database connection
include_once 'includes/db_connect.php';

// Get a database connection (this will also initialize if needed)
$pdo = getDbConnection();

echo "Database initialized successfully!";
?> 