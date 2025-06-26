<?php
// Script to add order_index field to the experience table (ADMIN ACCESS ONLY)
session_start();

// SECURITY: Require admin authentication
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    error_log("Unauthorized database migration attempt from IP: " . ($_SERVER['REMOTE_ADDR'] ?? 'unknown'));
    http_response_code(403);
    die('Access denied. Admin authentication required.');
}

require_once 'includes/db_connect.php';

// Get a database connection
$pdo = getDbConnection();

try {
    // Start transaction
    $pdo->beginTransaction();
    
    // Check if the column already exists to avoid errors
    $columnExists = false;
    $columns = $pdo->query("PRAGMA table_info(experience)")->fetchAll(PDO::FETCH_ASSOC);
    foreach ($columns as $column) {
        if ($column['name'] === 'order_index') {
            $columnExists = true;
            break;
        }
    }
    
    if (!$columnExists) {
        // Add order_index column to experience table with default values based on id
        $pdo->exec("ALTER TABLE experience ADD COLUMN order_index INTEGER");
        
        // Update all existing records with order based on id (assuming lower id = older entry = higher on list)
        $stmt = $pdo->query("SELECT id FROM experience ORDER BY id");
        $ids = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        foreach ($ids as $index => $id) {
            $orderIndex = count($ids) - $index; // Reverse order (highest number = top of list)
            $updateStmt = $pdo->prepare("UPDATE experience SET order_index = ? WHERE id = ?");
            $updateStmt->execute([$orderIndex, $id]);
        }
        
        $message = "Successfully added order_index field to experience table and initialized values.";
        $success = true;
    } else {
        $message = "The order_index column already exists in the experience table.";
        $success = true;
    }
    
    // Commit transaction
    $pdo->commit();
} catch (PDOException $e) {
    // Rollback on error
    $pdo->rollBack();
    error_log("Database error in add_experience_order.php: " . $e->getMessage());
    $message = "Error updating database structure: " . $e->getMessage();
    $success = false;
}

// Create page header
$pageTitle = "Database Migration - Experience Order";
require_once 'header.php';
?>

<div class="container mt-4">
    <div class="card">
        <div class="card-header">
            <h2>Database Migration - Experience Order</h2>
        </div>
        <div class="card-body">
            <?php if ($success): ?>
                <div class="alert alert-success">
                    <?= htmlspecialchars($message) ?>
                </div>
                
                <p>This migration adds the capability to sort/order experience items using drag and drop in the admin panel.</p>
                <p>Next step: Go to the Experience tab in the Admin panel to arrange your experience items.</p>
            <?php else: ?>
                <div class="alert alert-danger">
                    <?= htmlspecialchars($message) ?>
                </div>
            <?php endif; ?>
            
            <div class="mt-3">
                <a href="admin.php" class="btn btn-primary">Back to Admin Panel</a>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
