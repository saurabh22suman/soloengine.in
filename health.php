<?php
// Simple health check endpoint for Dokploy monitoring

// Set content type to JSON
header('Content-Type: application/json');

// Check if database connection works
$dbCheck = false;
try {
    require_once 'includes/db_connect.php';
    $pdo = getDbConnection();
    $stmt = $pdo->query("SELECT 1");
    if ($stmt !== false) {
        $dbCheck = true;
    }
} catch (Exception $e) {
    // Silently fail - we'll report the error in the response
}

// Build response
$response = [
    'status' => 'ok',
    'timestamp' => time(),
    'service' => 'php-portfolio',
    'database' => $dbCheck ? 'connected' : 'error',
    'php_version' => PHP_VERSION
];

// If database check failed, set status to warn
if (!$dbCheck) {
    $response['status'] = 'warn';
}

// Return response
echo json_encode($response);
