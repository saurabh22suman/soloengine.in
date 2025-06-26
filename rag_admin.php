<?php
/**
 * Admin RAG Management
 * 
 * Admin interface for managing RAG documents and monitoring chat usage
 * 
 * SECURITY: Protected by admin authentication check
 */

// Start session for security verification
session_start();

// CRITICAL SECURITY CHECK: Only allow admin users to access this page
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    // Log unauthorized access attempt
    error_log("Unauthorized admin access attempt from IP: " . $_SERVER['REMOTE_ADDR']);
    http_response_code(403);
    header('Location: admin.php');
    exit('Access denied');
}

// Include necessary files
require_once 'includes/db_connect.php';
require_once 'includes/vector_store.php';

$pdo = getDbConnection();
$vectorStore = new VectorStore();

// Process form submissions
$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Add new document
    if (isset($_POST['action']) && $_POST['action'] === 'add_document') {
        // Validate CSRF token
        if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
            $error = "CSRF validation failed";
        } else {
            // Validate inputs
            $title = trim($_POST['title'] ?? '');
            $source = trim($_POST['source'] ?? '');
            $content = trim($_POST['content'] ?? '');
            
            if (empty($title) || empty($content)) {
                $error = "Title and content are required";
            } else {
                // Prepare metadata if available
                $metadata = [];
                if (!empty($_POST['meta_keys']) && is_array($_POST['meta_keys'])) {
                    foreach ($_POST['meta_keys'] as $index => $key) {
                        if (!empty($key) && isset($_POST['meta_values'][$index])) {
                            $metadata[$key] = $_POST['meta_values'][$index];
                        }
                    }
                }
                
                // Add document to vector store
                $documentId = $vectorStore->addDocument($title, $source, $content, $metadata);
                
                if ($documentId) {
                    $message = "Document added successfully (ID: {$documentId})";
                } else {
                    $error = "Failed to add document";
                }
            }
        }
    }
    
    // Delete document
    if (isset($_POST['action']) && $_POST['action'] === 'delete_document') {
        // Validate CSRF token
        if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
            $error = "CSRF validation failed";
        } else {
            $documentId = (int)($_POST['document_id'] ?? 0);
            
            if ($documentId <= 0) {
                $error = "Invalid document ID";
            } else {
                $success = $vectorStore->removeDocument($documentId);
                
                if ($success) {
                    $message = "Document deleted successfully";
                } else {
                    $error = "Failed to delete document";
                }
            }
        }
    }
    
    // Update document
    if (isset($_POST['action']) && $_POST['action'] === 'update_document') {
        // Validate CSRF token
        if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
            $error = "CSRF validation failed";
        } else {
            // Validate inputs
            $documentId = (int)($_POST['document_id'] ?? 0);
            $title = trim($_POST['title'] ?? '');
            $source = trim($_POST['source'] ?? '');
            $content = trim($_POST['content'] ?? '');
            
            if ($documentId <= 0 || empty($title) || empty($content)) {
                $error = "Document ID, title, and content are required";
            } else {
                // Prepare metadata if available
                $metadata = [];
                if (!empty($_POST['meta_keys']) && is_array($_POST['meta_keys'])) {
                    foreach ($_POST['meta_keys'] as $index => $key) {
                        if (!empty($key) && isset($_POST['meta_values'][$index])) {
                            $metadata[$key] = $_POST['meta_values'][$index];
                        }
                    }
                }
                
                // Update document in vector store
                $success = $vectorStore->updateDocument($documentId, $title, $source, $content, $metadata);
                
                if ($success) {
                    $message = "Document updated successfully";
                } else {
                    $error = "Failed to update document";
                }
            }
        }
    }
    
    // Clear chat history
    if (isset($_POST['action']) && $_POST['action'] === 'clear_history') {
        // Validate CSRF token
        if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
            $error = "CSRF validation failed";
        } else {
            try {
                $stmt = $pdo->query("DELETE FROM rag_chat_history");
                $message = "Chat history cleared successfully";
            } catch (PDOException $e) {
                error_log("Error clearing chat history: " . $e->getMessage());
                $error = "Failed to clear chat history";
            }
        }
    }
    
    // Reset rate limits
    if (isset($_POST['action']) && $_POST['action'] === 'reset_limits') {
        // Validate CSRF token
        if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
            $error = "CSRF validation failed";
        } else {
            try {
                $stmt = $pdo->query("DELETE FROM rag_rate_limits");
                $message = "Rate limits reset successfully";
            } catch (PDOException $e) {
                error_log("Error resetting rate limits: " . $e->getMessage());
                $error = "Failed to reset rate limits";
            }
        }
    }
}

// Generate CSRF token if not exists
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Get documents list
$documents = $vectorStore->listDocuments();

// Get edit document if requested
$editDocument = null;
if (isset($_GET['edit']) && is_numeric($_GET['edit'])) {
    $editId = (int)$_GET['edit'];
    $editDocument = $vectorStore->getDocument($editId);
}

// Get chat statistics
try {
    $chatStats = $pdo->query("
        SELECT COUNT(*) as total_messages,
               COUNT(DISTINCT session_id) as total_sessions,
               MAX(created_at) as last_message
        FROM rag_chat_history
    ")->fetch(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $chatStats = ['total_messages' => 0, 'total_sessions' => 0, 'last_message' => 'N/A'];
}

// Get recent messages
try {
    $recentMessages = $pdo->query("
        SELECT user_message, system_response, created_at
        FROM rag_chat_history
        ORDER BY created_at DESC
        LIMIT 10
    ")->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $recentMessages = [];
}

// Create page header
$pageTitle = "RAG Management";
require_once 'header.php';
?>

<div class="container mt-4">
    <h1>RAG Knowledge Base Management</h1>
    
    <?php if (!empty($message)): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <?= htmlspecialchars($message) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    <?php endif; ?>
    
    <?php if (!empty($error)): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <?= htmlspecialchars($error) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    <?php endif; ?>
    
    <ul class="nav nav-tabs mb-4" id="ragTab" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link active" id="documents-tab" data-bs-toggle="tab" data-bs-target="#documents" type="button" role="tab" aria-controls="documents" aria-selected="true">Documents</button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="add-document-tab" data-bs-toggle="tab" data-bs-target="#add-document" type="button" role="tab" aria-controls="add-document" aria-selected="false">Add Document</button>
        </li>
        <?php if ($editDocument): ?>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="edit-document-tab" data-bs-toggle="tab" data-bs-target="#edit-document" type="button" role="tab" aria-controls="edit-document" aria-selected="false">Edit Document</button>
        </li>
        <?php endif; ?>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="statistics-tab" data-bs-toggle="tab" data-bs-target="#statistics" type="button" role="tab" aria-controls="statistics" aria-selected="false">Statistics</button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="settings-tab" data-bs-toggle="tab" data-bs-target="#settings" type="button" role="tab" aria-controls="settings" aria-selected="false">Settings</button>
        </li>
    </ul>
    
    <div class="tab-content" id="ragTabContent">
        <!-- Documents List Tab -->
        <div class="tab-pane fade show active" id="documents" role="tabpanel" aria-labelledby="documents-tab">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Knowledge Base Documents</h5>
                </div>
                <div class="card-body">
                    <?php if (empty($documents)): ?>
                        <div class="alert alert-info">No documents in the knowledge base yet.</div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-striped table-hover">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Title</th>
                                        <th>Source</th>
                                        <th>Chunks</th>
                                        <th>Last Updated</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($documents as $doc): ?>
                                        <tr>
                                            <td><?= htmlspecialchars($doc['id']) ?></td>
                                            <td><?= htmlspecialchars($doc['title']) ?></td>
                                            <td><?= htmlspecialchars($doc['source']) ?></td>
                                            <td><?= htmlspecialchars($doc['chunk_count']) ?></td>
                                            <td><?= htmlspecialchars($doc['updated_at']) ?></td>
                                            <td>
                                                <div class="btn-group btn-group-sm">
                                                    <a href="?edit=<?= htmlspecialchars($doc['id']) ?>" class="btn btn-primary">Edit</a>
                                                    <button type="button" class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#deleteModal<?= htmlspecialchars($doc['id']) ?>">Delete</button>
                                                </div>
                                                
                                                <!-- Delete Modal -->
                                                <div class="modal fade" id="deleteModal<?= htmlspecialchars($doc['id']) ?>" tabindex="-1" aria-hidden="true">
                                                    <div class="modal-dialog">
                                                        <div class="modal-content">
                                                            <div class="modal-header">
                                                                <h5 class="modal-title">Confirm Delete</h5>
                                                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                            </div>
                                                            <div class="modal-body">
                                                                Are you sure you want to delete the document "<strong><?= htmlspecialchars($doc['title']) ?></strong>"?
                                                            </div>
                                                            <div class="modal-footer">
                                                                <form method="post">
                                                                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
                                                                    <input type="hidden" name="action" value="delete_document">
                                                                    <input type="hidden" name="document_id" value="<?= htmlspecialchars($doc['id']) ?>">
                                                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                                    <button type="submit" class="btn btn-danger">Delete</button>
                                                                </form>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <!-- Add Document Tab -->
        <div class="tab-pane fade" id="add-document" role="tabpanel" aria-labelledby="add-document-tab">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Add New Document</h5>
                </div>
                <div class="card-body">
                    <form method="post" class="needs-validation" novalidate>
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
                        <input type="hidden" name="action" value="add_document">
                        
                        <div class="mb-3">
                            <label for="title" class="form-label">Document Title <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="title" name="title" required>
                            <div class="invalid-feedback">Please provide a title</div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="source" class="form-label">Source (optional)</label>
                            <input type="text" class="form-control" id="source" name="source">
                            <div class="form-text">Examples: "Website", "Resume", "Project Description", etc.</div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="content" class="form-label">Document Content <span class="text-danger">*</span></label>
                            <textarea class="form-control" id="content" name="content" rows="10" required></textarea>
                            <div class="invalid-feedback">Please provide content</div>
                            <div class="form-text">The content will be automatically divided into chunks for optimal embedding.</div>
                        </div>
                        
                        <div class="mb-3" id="metadata-container">
                            <label class="form-label">Metadata (optional)</label>
                            <div class="metadata-row row mb-2">
                                <div class="col-md-5">
                                    <input type="text" class="form-control" name="meta_keys[]" placeholder="Key">
                                </div>
                                <div class="col-md-5">
                                    <input type="text" class="form-control" name="meta_values[]" placeholder="Value">
                                </div>
                                <div class="col-md-2">
                                    <button type="button" class="btn btn-sm btn-outline-danger remove-meta">Remove</button>
                                </div>
                            </div>
                            <button type="button" class="btn btn-sm btn-outline-secondary" id="add-meta">Add Metadata Field</button>
                        </div>
                        
                        <div class="d-flex justify-content-end">
                            <button type="submit" class="btn btn-primary">Add Document</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        
        <!-- Edit Document Tab -->
        <?php if ($editDocument): ?>
        <div class="tab-pane fade" id="edit-document" role="tabpanel" aria-labelledby="edit-document-tab">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Edit Document</h5>
                </div>
                <div class="card-body">
                    <form method="post" class="needs-validation" novalidate>
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
                        <input type="hidden" name="action" value="update_document">
                        <input type="hidden" name="document_id" value="<?= htmlspecialchars($editDocument['id']) ?>">
                        
                        <div class="mb-3">
                            <label for="edit-title" class="form-label">Document Title <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="edit-title" name="title" value="<?= htmlspecialchars($editDocument['title']) ?>" required>
                            <div class="invalid-feedback">Please provide a title</div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="edit-source" class="form-label">Source (optional)</label>
                            <input type="text" class="form-control" id="edit-source" name="source" value="<?= htmlspecialchars($editDocument['source']) ?>">
                        </div>
                        
                        <div class="mb-3">
                            <label for="edit-content" class="form-label">Document Content <span class="text-danger">*</span></label>
                            <textarea class="form-control" id="edit-content" name="content" rows="10" required><?= htmlspecialchars($editDocument['content']) ?></textarea>
                            <div class="invalid-feedback">Please provide content</div>
                        </div>
                        
                        <div class="mb-3" id="edit-metadata-container">
                            <label class="form-label">Metadata (optional)</label>
                            <?php if (!empty($editDocument['metadata'])): ?>
                                <?php foreach ($editDocument['metadata'] as $key => $value): ?>
                                <div class="metadata-row row mb-2">
                                    <div class="col-md-5">
                                        <input type="text" class="form-control" name="meta_keys[]" placeholder="Key" value="<?= htmlspecialchars($key) ?>">
                                    </div>
                                    <div class="col-md-5">
                                        <input type="text" class="form-control" name="meta_values[]" placeholder="Value" value="<?= htmlspecialchars($value) ?>">
                                    </div>
                                    <div class="col-md-2">
                                        <button type="button" class="btn btn-sm btn-outline-danger remove-meta">Remove</button>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <div class="metadata-row row mb-2">
                                    <div class="col-md-5">
                                        <input type="text" class="form-control" name="meta_keys[]" placeholder="Key">
                                    </div>
                                    <div class="col-md-5">
                                        <input type="text" class="form-control" name="meta_values[]" placeholder="Value">
                                    </div>
                                    <div class="col-md-2">
                                        <button type="button" class="btn btn-sm btn-outline-danger remove-meta">Remove</button>
                                    </div>
                                </div>
                            <?php endif; ?>
                            <button type="button" class="btn btn-sm btn-outline-secondary" id="edit-add-meta">Add Metadata Field</button>
                        </div>
                        
                        <div class="d-flex justify-content-between">
                            <a href="rag_admin.php" class="btn btn-secondary">Cancel</a>
                            <button type="submit" class="btn btn-primary">Update Document</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        <?php endif; ?>
        
        <!-- Statistics Tab -->
        <div class="tab-pane fade" id="statistics" role="tabpanel" aria-labelledby="statistics-tab">
            <div class="row">
                <div class="col-md-6 mb-4">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0">Chat Statistics</h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <div class="card bg-light">
                                        <div class="card-body text-center">
                                            <h2><?= htmlspecialchars($chatStats['total_messages']) ?></h2>
                                            <p class="mb-0">Total Messages</p>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <div class="card bg-light">
                                        <div class="card-body text-center">
                                            <h2><?= htmlspecialchars($chatStats['total_sessions']) ?></h2>
                                            <p class="mb-0">Total Sessions</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <p><strong>Last Message:</strong> <?= htmlspecialchars($chatStats['last_message'] ?? 'Never') ?></p>
                            
                            <div class="d-flex justify-content-center mt-3">
                                <button type="button" class="btn btn-warning" data-bs-toggle="modal" data-bs-target="#clearHistoryModal">
                                    Clear Chat History
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-6 mb-4">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0">Knowledge Base Statistics</h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <div class="card bg-light">
                                        <div class="card-body text-center">
                                            <h2><?= count($documents) ?></h2>
                                            <p class="mb-0">Documents</p>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <div class="card bg-light">
                                        <div class="card-body text-center">
                                            <?php
                                            $totalChunks = 0;
                                            foreach ($documents as $doc) {
                                                $totalChunks += (int)$doc['chunk_count'];
                                            }
                                            ?>
                                            <h2><?= $totalChunks ?></h2>
                                            <p class="mb-0">Total Chunks</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="d-flex justify-content-center mt-3">
                                <a href="test_env.php" class="btn btn-info">Check Configuration</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">Recent Chat Messages</h5>
                </div>
                <div class="card-body">
                    <?php if (empty($recentMessages)): ?>
                        <div class="alert alert-info">No chat messages yet.</div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>User Message</th>
                                        <th>System Response</th>
                                        <th>Time</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($recentMessages as $msg): ?>
                                        <tr>
                                            <td><?= htmlspecialchars(substr($msg['user_message'], 0, 100)) ?><?= strlen($msg['user_message']) > 100 ? '...' : '' ?></td>
                                            <td><?= htmlspecialchars(substr($msg['system_response'], 0, 100)) ?><?= strlen($msg['system_response']) > 100 ? '...' : '' ?></td>
                                            <td><?= htmlspecialchars($msg['created_at']) ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <!-- Settings Tab -->
        <div class="tab-pane fade" id="settings" role="tabpanel" aria-labelledby="settings-tab">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">RAG System Settings</h5>
                </div>
                <div class="card-body">
                    <div class="alert alert-info">
                        <p>Configuration settings are stored in the <code>.env</code> file. You can modify them there.</p>
                        <p>After making changes to the <code>.env</code> file, you may need to restart the application.</p>
                    </div>
                    
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <h5>Rate Limiting</h5>
                            <p>Current rate limits are applied per IP address.</p>
                            
                            <div class="d-flex">
                                <button type="button" class="btn btn-warning" data-bs-toggle="modal" data-bs-target="#resetLimitsModal">
                                    Reset Rate Limits
                                </button>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <h5>Database Operations</h5>
                            <div class="d-flex gap-2">
                                <a href="rag_setup_db.php" class="btn btn-danger">Reinitialize Tables</a>
                                <a href="test_env.php" class="btn btn-info">Test Environment</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Clear History Modal -->
    <div class="modal fade" id="clearHistoryModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Confirm Clear History</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>Are you sure you want to clear all chat history?</p>
                    <p class="text-danger"><strong>Warning:</strong> This action cannot be undone.</p>
                </div>
                <div class="modal-footer">
                    <form method="post">
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
                        <input type="hidden" name="action" value="clear_history">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-warning">Clear History</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Reset Rate Limits Modal -->
    <div class="modal fade" id="resetLimitsModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Confirm Rate Limit Reset</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>Are you sure you want to reset all rate limits?</p>
                    <p>This will allow all clients to make new requests immediately.</p>
                </div>
                <div class="modal-footer">
                    <form method="post">
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
                        <input type="hidden" name="action" value="reset_limits">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-warning">Reset Limits</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Form validation
    const forms = document.querySelectorAll('.needs-validation');
    
    Array.from(forms).forEach(function(form) {
        form.addEventListener('submit', function(event) {
            if (!form.checkValidity()) {
                event.preventDefault();
                event.stopPropagation();
            }
            
            form.classList.add('was-validated');
        }, false);
    });
    
    // Metadata fields functionality
    const addMetaBtn = document.getElementById('add-meta');
    const metaContainer = document.getElementById('metadata-container');
    
    if (addMetaBtn && metaContainer) {
        addMetaBtn.addEventListener('click', function() {
            const row = document.createElement('div');
            row.className = 'metadata-row row mb-2';
            row.innerHTML = `
                <div class="col-md-5">
                    <input type="text" class="form-control" name="meta_keys[]" placeholder="Key">
                </div>
                <div class="col-md-5">
                    <input type="text" class="form-control" name="meta_values[]" placeholder="Value">
                </div>
                <div class="col-md-2">
                    <button type="button" class="btn btn-sm btn-outline-danger remove-meta">Remove</button>
                </div>
            `;
            
            // Insert before the add button
            metaContainer.insertBefore(row, addMetaBtn);
            
            // Add event listener to the remove button
            row.querySelector('.remove-meta').addEventListener('click', function() {
                row.remove();
            });
        });
    }
    
    // For edit form
    const editAddMetaBtn = document.getElementById('edit-add-meta');
    const editMetaContainer = document.getElementById('edit-metadata-container');
    
    if (editAddMetaBtn && editMetaContainer) {
        editAddMetaBtn.addEventListener('click', function() {
            const row = document.createElement('div');
            row.className = 'metadata-row row mb-2';
            row.innerHTML = `
                <div class="col-md-5">
                    <input type="text" class="form-control" name="meta_keys[]" placeholder="Key">
                </div>
                <div class="col-md-5">
                    <input type="text" class="form-control" name="meta_values[]" placeholder="Value">
                </div>
                <div class="col-md-2">
                    <button type="button" class="btn btn-sm btn-outline-danger remove-meta">Remove</button>
                </div>
            `;
            
            // Insert before the add button
            editMetaContainer.insertBefore(row, editAddMetaBtn);
            
            // Add event listener to the remove button
            row.querySelector('.remove-meta').addEventListener('click', function() {
                row.remove();
            });
        });
    }
    
    // Add remove metadata functionality to existing rows
    document.querySelectorAll('.remove-meta').forEach(button => {
        button.addEventListener('click', function() {
            this.closest('.metadata-row').remove();
        });
    });
    
    // Auto-activate the Edit tab if we're editing
    <?php if ($editDocument): ?>
    document.getElementById('edit-document-tab').click();
    <?php endif; ?>
    
    // Activate the tab specified in URL hash if present
    const hash = window.location.hash;
    if (hash) {
        const tab = document.querySelector(`[data-bs-target="${hash}"]`);
        if (tab) {
            tab.click();
        }
    }
});
</script>

<?php require_once 'includes/footer.php'; ?>
