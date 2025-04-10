<?php
// Database connection using SQLite
function getDbConnection() {
    $dbFile = __DIR__ . '/../data/resume.db';
    $dbExists = file_exists($dbFile);
    
    // Create the data directory if it doesn't exist
    if (!file_exists(dirname($dbFile))) {
        mkdir(dirname($dbFile), 0755, true);
    }
    
    try {
        $pdo = new PDO('sqlite:' . $dbFile);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        // If the database was just created, initialize it
        if (!$dbExists) {
            initializeDatabase($pdo);
            populateDefaultData($pdo);
        }
        
        return $pdo;
    } catch (PDOException $e) {
        die("Database connection failed: " . $e->getMessage());
    }
}

// Initialize database schema
function initializeDatabase($pdo) {
    // Profile table
    $pdo->exec('CREATE TABLE profile (
        id INTEGER PRIMARY KEY,
        name TEXT,
        job_title TEXT,
        summary TEXT,
        email TEXT,
        phone TEXT,
        location TEXT,
        linkedin TEXT,
        website TEXT,
        github TEXT,
        profile_image TEXT
    )');
    
    // Experience table
    $pdo->exec('CREATE TABLE experience (
        id INTEGER PRIMARY KEY,
        job_title TEXT,
        company TEXT,
        start_date TEXT,
        end_date TEXT,
        location TEXT,
        description TEXT
    )');
    
    // Education table
    $pdo->exec('CREATE TABLE education (
        id INTEGER PRIMARY KEY,
        degree TEXT,
        institution TEXT,
        start_date TEXT,
        end_date TEXT,
        location TEXT,
        description TEXT
    )');
    
    // Skills table
    $pdo->exec('CREATE TABLE skills (
        id INTEGER PRIMARY KEY,
        category TEXT,
        name TEXT,
        level INTEGER
    )');
    
    // Achievements table
    $pdo->exec('CREATE TABLE achievements (
        id INTEGER PRIMARY KEY,
        title TEXT,
        description TEXT,
        date TEXT
    )');
    
    // Projects table
    $pdo->exec('CREATE TABLE projects (
        id INTEGER PRIMARY KEY,
        title TEXT,
        description TEXT,
        technologies TEXT,
        link TEXT,
        image TEXT
    )');
    
    // Admin credentials table
    $pdo->exec('CREATE TABLE admin_settings (
        id INTEGER PRIMARY KEY,
        username TEXT,
        password TEXT,
        theme TEXT DEFAULT "light"
    )');
    
    // Insert default admin account
    $pdo->exec("INSERT INTO admin_settings (id, username, password, theme) VALUES (1, 'admin', 'admin123', 'light')");
}

// This function will be implemented in populate_db.php
function populateDefaultData($pdo) {
    include_once __DIR__ . '/populate_db.php';
    populateDatabase($pdo);
}
?> 