<?php
/**
 * RAG Chat API Endpoint
 * 
 * Handles chat interactions via AJAX requests
 * 
 * SECURITY: Implements CSRF protection, rate limiting, and input validation
 */

// Start session for security checks
session_start();

// Set content type to JSON
header('Content-Type: application/json');

// Default error response
$response = [
    'status' => 'error',
    'message' => 'An unexpected error occurred',
    'sources' => []
];

// Get the raw POST data
$rawData = file_get_contents('php://input');
$data = json_decode($rawData, true);

// Initialize rate limit flag
$rateLimitExceeded = false;

try {
    // SECURITY: Validate session and CSRF token
    if (!isset($_SESSION['chat_csrf_token']) || 
        !isset($data['csrf_token']) || 
        $_SESSION['chat_csrf_token'] !== $data['csrf_token']) {
        throw new Exception('Invalid security token');
    }
    
    // SECURITY: Validate input
    if (!isset($data['message']) || empty(trim($data['message']))) {
        throw new Exception('Message cannot be empty');
    }
    
    // SECURITY: Limit message length
    if (strlen($data['message']) > 1000) {
        throw new Exception('Message too long (maximum 1000 characters)');
    }
    
    // Get the message
    $message = trim($data['message']);
    
    // SECURITY: Get user's IP for rate limiting
    $ipAddress = $_SERVER['REMOTE_ADDR'];
    if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        $ipAddress = $_SERVER['HTTP_X_FORWARDED_FOR'];
    }
    
    // Include the chat handler
    require_once 'includes/chat_handler.php';
    
    // Process the message
    $chatHandler = new ChatHandler();
    $chatResponse = $chatHandler->processMessage($_SESSION['chat_session_id'], $message, $ipAddress);
    
    // Check if rate limit was exceeded
    if ($chatResponse['status'] === 'error' && strpos($chatResponse['message'], 'Rate limit') !== false) {
        $rateLimitExceeded = true;
    }
    
    // Set the response
    $response = $chatResponse;
    
} catch (Exception $e) {
    // Log the error
    error_log('Chat API error: ' . $e->getMessage());
    
    // Set the error message in the response
    $response['message'] = $e->getMessage();
}

// Add rate limit information if exceeded
if ($rateLimitExceeded) {
    $response['rate_limited'] = true;
}

// Return the response
echo json_encode($response);
exit;
