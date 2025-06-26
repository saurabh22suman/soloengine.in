<?php
/**
 * RAG Database Schema
 * 
 * Creates tables for the RAG feature including:
 * - rag_documents: Stores source documents for RAG system
 * - rag_chunks: Stores text chunks with their embeddings
 * - rag_chat_history: Stores user chat history
 * 
 * SECURITY: This script must be run with admin privileges
 */

// Start session for security verification
session_start();

// CRITICAL SECURITY CHECK: Only allow admin users to run this script
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    // Log unauthorized access attempt
    error_log("Unauthorized schema setup attempt from IP: " . $_SERVER['REMOTE_ADDR']);
    http_response_code(403);
    header('Location: admin.php');
    exit('Access denied');
}

// Include database connection
require_once 'includes/db_connect.php';
$pdo = getDbConnection();

// Set PDO to throw exceptions on error
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// Create tables in a transaction to ensure all-or-nothing execution
try {
    $pdo->beginTransaction();
    
    // Table for storing source documents
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
    
    // Table for storing document chunks and their embeddings
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS rag_chunks (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            document_id INTEGER NOT NULL,
            chunk_index INTEGER NOT NULL,
            content TEXT NOT NULL,
            embedding BLOB NOT NULL,  -- Binary storage for vector embedding
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (document_id) REFERENCES rag_documents(id) ON DELETE CASCADE
        )
    ");
    
    // Create index for faster vector searches
    $pdo->exec("CREATE INDEX IF NOT EXISTS idx_rag_chunks_document_id ON rag_chunks(document_id)");
    
    // Table for storing chat history
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS rag_chat_history (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            session_id TEXT NOT NULL,
            user_message TEXT NOT NULL,
            system_response TEXT NOT NULL,
            context_ids TEXT DEFAULT NULL,  -- Comma-separated chunk IDs used for context
            ip_address TEXT NOT NULL,
            embedding BLOB DEFAULT NULL,    -- Optional embedding of the query
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )
    ");
    
    // Create index on session_id for faster history lookups
    $pdo->exec("CREATE INDEX IF NOT EXISTS idx_rag_chat_history_session ON rag_chat_history(session_id)");
    
    // Table for rate limiting
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
    
    $pdo->commit();
    $success = true;
    $message = "RAG database schema created successfully.";
} catch (PDOException $e) {
    $pdo->rollBack();
    $success = false;
    $message = "Database error: " . $e->getMessage();
    error_log("RAG schema creation error: " . $e->getMessage());
}

// Create page header
$pageTitle = "RAG Database Schema Setup";
require_once 'header.php';
?>

<div class="container mt-5">
    <div class="card">
        <div class="card-header">
            <h2>RAG Database Schema Setup</h2>
        </div>
        <div class="card-body">
            <?php if ($success): ?>
                <div class="alert alert-success">
                    <?= htmlspecialchars($message) ?>
                </div>
                
                <h3>Created Tables:</h3>
                <ul>
                    <li><code>rag_documents</code> - Stores source documents</li>
                    <li><code>rag_chunks</code> - Stores document chunks and vector embeddings</li>
                    <li><code>rag_chat_history</code> - Stores user chat history</li>
                    <li><code>rag_rate_limits</code> - Manages rate limiting</li>
                </ul>
                
                <h3>Next Steps:</h3>
                <ol>
                    <li>Configure your <code>.env</code> file with proper API keys</li>
                    <li>Add documents to the knowledge base</li>
                    <li>Generate embeddings for the documents</li>
                </ol>
            <?php else: ?>
                <div class="alert alert-danger">
                    <?= htmlspecialchars($message) ?>
                </div>
            <?php endif; ?>
            
            <div class="mt-4">
                <a href="admin.php" class="btn btn-primary">Back to Admin</a>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
