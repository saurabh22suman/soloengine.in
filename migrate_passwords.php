<?php
// Migration script to convert plain text passwords to hashed passwords
session_start();

require_once 'includes/db_connect.php';

// Check if we're running from command line (allows migration without web authentication)
$isCommandLine = php_sapi_name() === 'cli';

// SECURITY: Require admin authentication for web access, but allow CLI access
if (!$isCommandLine) {
    if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
        error_log("Unauthorized password migration attempt from IP: " . ($_SERVER['REMOTE_ADDR'] ?? 'unknown'));
        http_response_code(403);
        echo '<div style="padding: 20px; font-family: Arial, sans-serif;">';
        echo '<h2>Password Migration Required</h2>';
        echo '<p>It appears you need to migrate from plain text passwords to secure hashed passwords.</p>';
        echo '<p><strong>Option 1:</strong> Try logging in to the admin panel with your current password. ';
        echo 'The system will automatically migrate your password on successful login.</p>';
        echo '<p><strong>Option 2:</strong> Run this script from command line:</p>';
        echo '<pre style="background: #f5f5f5; padding: 10px; border-radius: 4px;">cd ' . __DIR__ . "\nphp migrate_passwords.php</pre>";
        echo '<p><a href="admin.php">← Return to Admin Login</a></p>';
        echo '</div>';
        exit;
    }
}

try {
    $pdo = getDbConnection();
    
    // Get current admin settings
    $stmt = $pdo->prepare('SELECT id, username, password FROM admin_settings');
    $stmt->execute();
    $admins = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $migrated = 0;
    $alreadyHashed = 0;
    
    foreach ($admins as $admin) {
        // Check if password is already hashed (PHP password hashes start with $)
        if (!password_get_info($admin['password'])['algo']) {
            // Plain text password, need to hash it
            $hashedPassword = password_hash($admin['password'], PASSWORD_DEFAULT);
            $updateStmt = $pdo->prepare('UPDATE admin_settings SET password = ? WHERE id = ?');
            $updateStmt->execute([$hashedPassword, $admin['id']]);
            $migrated++;
            
            if ($isCommandLine) {
                echo "Migrated password for user: " . $admin['username'] . "\n";
            }
        } else {
            $alreadyHashed++;
            if ($isCommandLine) {
                echo "Password already hashed for user: " . $admin['username'] . "\n";
            }
        }
    }
    
    if ($isCommandLine) {
        echo "\nPassword migration completed successfully!\n";
        echo "Migrated: $migrated password(s)\n";
        echo "Already hashed: $alreadyHashed password(s)\n";
        if ($migrated > 0) {
            echo "\n✅ All passwords are now securely hashed!\n";
        }
    } else {
        echo "Password migration completed successfully!<br>";
        echo "Migrated $migrated password(s) to secure hashed format.<br>";
        echo "Already hashed: $alreadyHashed password(s).<br>";
        echo "<a href='admin.php'>Return to Admin Panel</a>";
    }
    
    // Log the migration
    error_log("Password migration completed by " . ($isCommandLine ? "CLI" : "admin: " . ($_SESSION['admin_username'] ?? 'unknown')) . " - $migrated passwords migrated, $alreadyHashed already hashed");
    
} catch (PDOException $e) {
    error_log("Password migration error: " . $e->getMessage());
    if ($isCommandLine) {
        echo "Error during password migration: " . $e->getMessage() . "\n";
        exit(1);
    } else {
        echo "Error during password migration. Please try again or contact administrator.";
    }
}
?>