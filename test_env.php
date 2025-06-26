<?php
/**
 * Environment Loader Test File
 * 
 * Tests the functionality of the EnvLoader class.
 * SECURITY: Never leave this file in production environments!
 */

require_once 'includes/env_loader.php';

// Initialize session for admin check
session_start();

// CRITICAL SECURITY CHECK: Only allow admin users to access this test
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    // Log unauthorized access attempt
    error_log("Unauthorized admin access attempt from IP: " . $_SERVER['REMOTE_ADDR']);
    http_response_code(403);
    header('Location: admin.php');
    exit('Access denied');
}

// Create page header
$pageTitle = "Environment Configuration Test";
require_once 'header.php';

try {
    $env = EnvLoader::getInstance();
    
    // Test if API key is loaded (without displaying the actual key for security)
    $apiKey = $env->get('HUGGINGFACE_API_KEY');
    $apiKeyStatus = !empty($apiKey) ? 'Found (hidden for security)' : 'MISSING';
    
    // If API key is the example value, warn the user
    if ($apiKey === 'your_huggingface_api_key_here') {
        $apiKeyStatus = 'ERROR: Using example key. Please update with your actual API key.';
    }
    
    // Test numeric values
    $vectorDimension = $env->get('VECTOR_DIMENSION');
    $chunkSize = $env->get('CHUNK_SIZE');
    $chunkOverlap = $env->get('CHUNK_OVERLAP');
    $maxRequests = $env->get('MAX_REQUESTS_PER_MINUTE');
    
    // Test boolean values
    $rateLimit = $env->get('ENABLE_RATE_LIMITING') ? 'Enabled' : 'Disabled';
    
    // Test paths
    $vectorIndexPath = $env->get('VECTOR_INDEX_PATH');
    $vectorIndexStatus = 'Not found';
    
    // Check if vector index directory exists and is writable
    if (!empty($vectorIndexPath)) {
        $fullPath = dirname(__DIR__) . '/' . $vectorIndexPath;
        if (file_exists($fullPath)) {
            $vectorIndexStatus = is_writable($fullPath) ? 'Found and writable' : 'Found but NOT writable';
        } else {
            // Try to create the directory
            if (@mkdir($fullPath, 0755, true)) {
                $vectorIndexStatus = 'Created and writable';
            } else {
                $vectorIndexStatus = 'Failed to create directory';
            }
        }
    }
} catch (Exception $e) {
    // Handle configuration errors
    $error = $e->getMessage();
}
?>

<div class="container mt-5">
    <div class="card">
        <div class="card-header">
            <h2>Environment Configuration Test</h2>
        </div>
        <div class="card-body">
            <?php if (isset($error)): ?>
                <div class="alert alert-danger">
                    <strong>Error:</strong> <?= htmlspecialchars($error) ?>
                </div>
                <div class="alert alert-warning">
                    <p>Please create a <code>.env</code> file in the root directory based on <code>.env.example</code>.</p>
                    <p>Make sure to set all required variables.</p>
                </div>
            <?php else: ?>
                <h3>Configuration Status</h3>
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>Variable</th>
                            <th>Status/Value</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td><code>HUGGINGFACE_API_KEY</code></td>
                            <td><?= htmlspecialchars($apiKeyStatus) ?></td>
                        </tr>
                        <tr>
                            <td><code>VECTOR_DIMENSION</code></td>
                            <td><?= htmlspecialchars($vectorDimension) ?></td>
                        </tr>
                        <tr>
                            <td><code>CHUNK_SIZE</code></td>
                            <td><?= htmlspecialchars($chunkSize) ?></td>
                        </tr>
                        <tr>
                            <td><code>CHUNK_OVERLAP</code></td>
                            <td><?= htmlspecialchars($chunkOverlap) ?></td>
                        </tr>
                        <tr>
                            <td><code>MAX_REQUESTS_PER_MINUTE</code></td>
                            <td><?= htmlspecialchars($maxRequests) ?></td>
                        </tr>
                        <tr>
                            <td><code>ENABLE_RATE_LIMITING</code></td>
                            <td><?= htmlspecialchars($rateLimit) ?></td>
                        </tr>
                        <tr>
                            <td><code>VECTOR_INDEX_PATH</code></td>
                            <td><?= htmlspecialchars($vectorIndexPath) ?> (<?= htmlspecialchars($vectorIndexStatus) ?>)</td>
                        </tr>
                    </tbody>
                </table>
                
                <h3 class="mt-4">Recommendations</h3>
                <ul>
                    <?php if ($apiKey === 'your_huggingface_api_key_here'): ?>
                    <li class="text-danger">Update your Hugging Face API key in the .env file</li>
                    <?php endif; ?>
                    
                    <?php if ($vectorIndexStatus !== 'Found and writable' && $vectorIndexStatus !== 'Created and writable'): ?>
                    <li class="text-danger">Make sure the vector index directory exists and is writable</li>
                    <?php endif; ?>
                    
                    <?php if ($chunkSize < 100): ?>
                    <li class="text-warning">Your chunk size seems small. Consider increasing it for better context.</li>
                    <?php endif; ?>
                    
                    <?php if ($chunkOverlap > $chunkSize / 2): ?>
                    <li class="text-warning">Chunk overlap is very high relative to chunk size. This may cause performance issues.</li>
                    <?php endif; ?>
                </ul>
            <?php endif; ?>
            
            <div class="mt-4">
                <a href="admin.php" class="btn btn-primary">Back to Admin</a>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
