<?php
// Script to reset the database (ADMIN ACCESS ONLY)
session_start();

// SECURITY: Require admin authentication
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    error_log("Unauthorized database reset attempt from IP: " . ($_SERVER['REMOTE_ADDR'] ?? 'unknown'));
    http_response_code(403);
    die('Access denied. Admin authentication required.');
}

// Include the database connection
require_once 'includes/db_connect.php';

// Function to create database backup with timestamp
function createDatabaseBackup($dbFile) {
    if (!file_exists($dbFile)) {
        return false;
    }
    
    $backupDir = dirname($dbFile) . '/backups';
    if (!file_exists($backupDir)) {
        mkdir($backupDir, 0755, true);
    }
    
    $timestamp = date('Y-m-d_H-i-s');
    $backupFile = $backupDir . '/resume_backup_' . $timestamp . '.db';
    
    if (copy($dbFile, $backupFile)) {
        // Keep only last 3 backups
        $backups = glob($backupDir . '/resume_backup_*.db');
        usort($backups, function($a, $b) {
            return filemtime($b) - filemtime($a); // Sort by modification time, newest first
        });
        
        // Remove old backups (keep only 3 most recent)
        for ($i = 3; $i < count($backups); $i++) {
            unlink($backups[$i]);
        }
        
        return $backupFile;
    }
    
    return false;
}

// Database file path
$dbFile = __DIR__ . '/data/resume.db';

// Create backup before reset
$backupFile = false;
if (file_exists($dbFile)) {
    $backupFile = createDatabaseBackup($dbFile);
    if ($backupFile) {
        echo "Database backup created: " . basename($backupFile) . "<br>";
        error_log("Database backup created by admin: " . basename($backupFile));
    } else {
        echo "Warning: Could not create database backup<br>";
        error_log("Failed to create database backup during reset");
    }
    
    // Delete the existing database file
    unlink($dbFile);
    echo "Existing database deleted.<br>";
}

// Get a database connection (this will re-initialize the database)
$pdo = getDbConnection();

echo "Database reset and initialized successfully!<br>";
echo "<a href='admin.php'>Return to Admin Panel</a>";

// Log the reset action for security audit
error_log("Database reset completed by admin: " . ($_SESSION['admin_username'] ?? 'unknown'));
?> 