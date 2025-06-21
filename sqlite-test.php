<?php
// Simple script to test if SQLite is working

echo "<h1>SQLite Test</h1>";

// Check if PDO SQLite extension is loaded
echo "<h2>PHP Extension Check:</h2>";
echo "<p>PDO SQLite Extension: " . (extension_loaded('pdo_sqlite') ? "<span style='color:green'>Loaded ✓</span>" : "<span style='color:red'>Not Loaded ✗</span>") . "</p>";
echo "<p>SQLite3 Extension: " . (extension_loaded('sqlite3') ? "<span style='color:green'>Loaded ✓</span>" : "<span style='color:red'>Not Loaded ✗</span>") . "</p>";

// Try to create a test database
echo "<h2>Database Connection Test:</h2>";
try {
    // Create data directory if it doesn't exist
    if (!file_exists('data')) {
        mkdir('data', 0777, true);
        echo "<p>Created data directory</p>";
    }
    
    // Try to open/create a test database
    $dbFile = 'data/test.db';
    $pdo = new PDO('sqlite:' . $dbFile);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Create a test table
    $pdo->exec("CREATE TABLE IF NOT EXISTS test (id INTEGER PRIMARY KEY, name TEXT)");
    
    // Insert a test record
    $pdo->exec("INSERT INTO test (name) VALUES ('Test " . date('Y-m-d H:i:s') . "')");
    
    // Read from the table
    $stmt = $pdo->query("SELECT * FROM test ORDER BY id DESC LIMIT 5");
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<p style='color:green'>Database connection successful! ✓</p>";
    echo "<p>Records in test table:</p>";
    echo "<ul>";
    foreach ($rows as $row) {
        echo "<li>ID: " . $row['id'] . " - Name: " . $row['name'] . "</li>";
    }
    echo "</ul>";
      // Check file permissions
    echo "<p>Database file permissions: " . substr(sprintf('%o', fileperms($dbFile)), -4) . "</p>";
    echo "<p>Database file owner ID: " . fileowner($dbFile) . "</p>";
    
} catch (PDOException $e) {
    echo "<p style='color:red'>Database error: " . $e->getMessage() . "</p>";
}

echo "<h2>PHP Info (SQLite Section):</h2>";
ob_start();
phpinfo(INFO_MODULES);
$phpinfo = ob_get_clean();

// Extract the SQLite section
if (preg_match('/<h2>PDO_SQLITE<\/h2>.*?<table.*?>(.*?)<\/table>/s', $phpinfo, $matches)) {
    echo "<table>" . $matches[1] . "</table>";
} else {
    echo "<p>Could not find PDO_SQLITE section in phpinfo()</p>";
}

echo "<p><a href='/'>Return to Homepage</a></p>";
