<?php
/**
 * RAG Database Schema Verification
 * 
 * Verifies and creates the RAG database schema if it doesn't exist.
 * 
 * SECURITY: This script must be run with admin privileges
 */

// Start session for security verification
session_start();

// CRITICAL SECURITY CHECK: Only allow admin users to run this script
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    // Log unauthorized access attempt
    error_log("Unauthorized schema verification attempt from IP: " . $_SERVER['REMOTE_ADDR']);
    http_response_code(403);
    header('Location: admin.php');
    exit('Access denied');
}

// Include database connection
require_once 'includes/db_connect.php';
$pdo = getDbConnection();

// Set PDO to throw exceptions on error
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// Function to check if tables exist
function tablesExist($pdo) {
    $requiredTables = ['rag_documents', 'rag_chunks', 'rag_chat_history', 'rag_rate_limits'];
    $existingTables = [];
    $missingTables = [];
    
    try {
        // Get list of tables from SQLite
        $result = $pdo->query("SELECT name FROM sqlite_master WHERE type='table'");
        $tables = $result->fetchAll(PDO::FETCH_COLUMN);
        
        foreach ($requiredTables as $table) {
            if (in_array($table, $tables)) {
                $existingTables[] = $table;
            } else {
                $missingTables[] = $table;
            }
        }
        
        return [
            'all_exist' => empty($missingTables),
            'existing' => $existingTables,
            'missing' => $missingTables
        ];
    } catch (PDOException $e) {
        error_log("Error checking tables: " . $e->getMessage());
        return ['all_exist' => false, 'existing' => [], 'missing' => $requiredTables];
    }
}

// Create tables in a transaction to ensure all-or-nothing execution
function createMissingTables($pdo, $missingTables) {
    try {
        $pdo->beginTransaction();
        
        // Create schemas as needed
        if (in_array('rag_documents', $missingTables)) {
            $pdo->exec("
                CREATE TABLE IF NOT EXISTS rag_documents (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    title TEXT NOT NULL,
                    source TEXT NOT NULL,
                    content TEXT NOT NULL,
                    metadata TEXT DEFAULT NULL,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                )
            ");
        }
        
        if (in_array('rag_chunks', $missingTables)) {
            $pdo->exec("
                CREATE TABLE IF NOT EXISTS rag_chunks (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    document_id INTEGER NOT NULL,
                    chunk_index INTEGER NOT NULL,
                    content TEXT NOT NULL,
                    embedding BLOB NOT NULL,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    FOREIGN KEY (document_id) REFERENCES rag_documents(id) ON DELETE CASCADE
                )
            ");
            
            $pdo->exec("CREATE INDEX IF NOT EXISTS idx_rag_chunks_document_id ON rag_chunks(document_id)");
        }
        
        if (in_array('rag_chat_history', $missingTables)) {
            $pdo->exec("
                CREATE TABLE IF NOT EXISTS rag_chat_history (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    session_id TEXT NOT NULL,
                    user_message TEXT NOT NULL,
                    system_response TEXT NOT NULL,
                    context_ids TEXT DEFAULT NULL,
                    ip_address TEXT NOT NULL,
                    embedding BLOB DEFAULT NULL,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                )
            ");
            
            $pdo->exec("CREATE INDEX IF NOT EXISTS idx_rag_chat_history_session ON rag_chat_history(session_id)");
        }
        
        if (in_array('rag_rate_limits', $missingTables)) {
            $pdo->exec("
                CREATE TABLE IF NOT EXISTS rag_rate_limits (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    ip_address TEXT NOT NULL,
                    request_count INTEGER DEFAULT 1,
                    first_request_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    last_request_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    UNIQUE(ip_address)
                )
            ");
        }
        
        $pdo->commit();
        return true;
    } catch (PDOException $e) {
        $pdo->rollBack();
        error_log("Error creating tables: " . $e->getMessage());
        return false;
    }
}

// Check if tables exist
$tableStatus = tablesExist($pdo);
$success = true;
$message = '';

// Create missing tables if needed
if (!$tableStatus['all_exist']) {
    $success = createMissingTables($pdo, $tableStatus['missing']);
    if ($success) {
        $message = "Successfully created missing tables: " . implode(', ', $tableStatus['missing']);
    } else {
        $message = "Failed to create missing tables: " . implode(', ', $tableStatus['missing']);
    }
} else {
    $message = "All required tables already exist.";
}

// Create page header
$pageTitle = "RAG Database Verification";
require_once 'header.php';
?>

<div class="container mt-5">
    <div class="card">
        <div class="card-header">
            <h2>RAG Database Verification</h2>
        </div>
        <div class="card-body">
            <?php if ($success): ?>
                <div class="alert alert-success">
                    <?= htmlspecialchars($message) ?>
                </div>
                
                <h3>Database Schema Status:</h3>
                <table class="table table-bordered">
                    <thead>
                        <tr>
                            <th>Table</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach (['rag_documents', 'rag_chunks', 'rag_chat_history', 'rag_rate_limits'] as $table): ?>
                            <tr>
                                <td><code><?= htmlspecialchars($table) ?></code></td>
                                <td>
                                    <?php if (in_array($table, $tableStatus['existing']) || in_array($table, $tableStatus['missing']) && $success): ?>
                                        <span class="badge bg-success">Available</span>
                                    <?php else: ?>
                                        <span class="badge bg-danger">Missing</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                
                <h3>Next Steps:</h3>
                <ol>
                    <li>Configure your <code>.env</code> file with proper API keys (<a href="test_env.php">Verify Configuration</a>)</li>
                    <li><a href="rag_admin.php">Add documents</a> to the knowledge base</li>
                    <li>Use the <a href="test_embedding.php">embedding test tool</a> to verify API functionality</li>
                    <li>Try the <a href="chat.php">chat interface</a></li>
                </ol>
            <?php else: ?>
                <div class="alert alert-danger">
                    <?= htmlspecialchars($message) ?>
                </div>
                
                <p>Please check your database configuration and permissions.</p>
            <?php endif; ?>
            
            <div class="mt-4">
                <a href="admin.php" class="btn btn-primary">Back to Admin</a>
                <?php if (!$success): ?>
                    <a href="verify_rag_db.php" class="btn btn-warning">Try Again</a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
