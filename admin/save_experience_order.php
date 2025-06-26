<?php
/**
 * Save Experience Order - AJAX Endpoint
 * 
 * Updates the order_index for experience items based on the drag-and-drop sorting in the admin panel
 * 
 * SECURITY: Protected by admin authentication check and CSRF token validation
 */

// Start session for security verification
session_start();

// Set JSON content type for AJAX response
header('Content-Type: application/json');

// Default error response
$response = [
    'success' => false,
    'message' => 'An unexpected error occurred'
];

// CRITICAL SECURITY CHECK: Only allow admin users to access this endpoint
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    // Log unauthorized access attempt
    error_log("Unauthorized experience order update attempt from IP: " . $_SERVER['REMOTE_ADDR']);
    http_response_code(403);
    $response['message'] = 'Access denied. Admin authentication required.';
    echo json_encode($response);
    exit;
}

// Validate request method
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    $response['message'] = 'Method not allowed';
    echo json_encode($response);
    exit;
}

// Get POST data
$postData = json_decode(file_get_contents('php://input'), true);

// Validate CSRF token
if (!isset($postData['csrf_token']) || !isset($_SESSION['csrf_token']) || $postData['csrf_token'] !== $_SESSION['csrf_token']) {
    error_log("CSRF token validation failed in experience order update");
    http_response_code(403);
    $response['message'] = 'CSRF token validation failed';
    echo json_encode($response);
    exit;
}

// Validate order data
if (!isset($postData['order']) || !is_array($postData['order']) || empty($postData['order'])) {
    $response['message'] = 'Invalid order data';
    echo json_encode($response);
    exit;
}

// Include database connection
require_once '../includes/db_connect.php';
$pdo = getDbConnection();

try {
    // Start transaction
    $pdo->beginTransaction();
    
    // Prepare statement for updating order_index
    $stmt = $pdo->prepare("UPDATE experience SET order_index = ? WHERE id = ?");
    
    // Process each item in the order array
    foreach ($postData['order'] as $index => $id) {
        // Validate ID format
        if (!is_numeric($id)) {
            continue;
        }
        
        // Convert to integers for safety
        $id = (int)$id;
        $orderIndex = count($postData['order']) - $index; // Reverse order (highest number = top of list)
        
        // Update the database
        $stmt->execute([$orderIndex, $id]);
    }
    
    // Commit transaction
    $pdo->commit();
    
    // Success response
    $response['success'] = true;
    $response['message'] = 'Experience order updated successfully';
    
} catch (PDOException $e) {
    // Rollback on error
    $pdo->rollBack();
    error_log("Database error in save_experience_order.php: " . $e->getMessage());
    $response['message'] = 'Database error occurred';
}

// Send JSON response
echo json_encode($response);
exit;
