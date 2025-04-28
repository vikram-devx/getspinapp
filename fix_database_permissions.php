<?php
// Script to fix database permissions for SQLite
include 'includes/config.php';

echo "<h1>Database Permissions Fix Utility</h1>";
echo "<p>This utility script checks and fixes database permissions issues.</p>";

// Check if fix was requested
$fix_requested = isset($_GET['fix']) && $_GET['fix'] == 'yes';

// Check database directory
echo "<h2>Step 1: Checking Data Directory</h2>";
$data_dir = __DIR__ . '/data';

if (!file_exists($data_dir)) {
    echo "<div style='color: red;'>Data directory does not exist. Creating it now...</div>";
    if (mkdir($data_dir, 0777, true)) {
        echo "<div style='color: green;'>Data directory created successfully.</div>";
    } else {
        echo "<div style='color: red;'>Failed to create data directory.</div>";
    }
} else {
    echo "<div style='color: green;'>Data directory exists.</div>";
}

// Check permissions on data directory
$perms = substr(sprintf('%o', fileperms($data_dir)), -4);
echo "<div>Current data directory permissions: " . $perms . "</div>";

if ($perms != '0777' && $fix_requested) {
    echo "<div>Setting data directory permissions to 0777...</div>";
    if (chmod($data_dir, 0777)) {
        echo "<div style='color: green;'>Data directory permissions fixed.</div>";
    } else {
        echo "<div style='color: red;'>Failed to set data directory permissions.</div>";
    }
    $perms = substr(sprintf('%o', fileperms($data_dir)), -4);
    echo "<div>Data directory permissions after fix: " . $perms . "</div>";
}

// Check database file
echo "<h2>Step 2: Checking Database File</h2>";
if (file_exists(DB_PATH)) {
    echo "<div style='color: green;'>Database file exists.</div>";
    
    // Check permissions on database file
    $perms = substr(sprintf('%o', fileperms(DB_PATH)), -4);
    echo "<div>Current database file permissions: " . $perms . "</div>";
    
    if ($perms != '0666' && $fix_requested) {
        echo "<div>Setting database file permissions to 0666...</div>";
        if (chmod(DB_PATH, 0666)) {
            echo "<div style='color: green;'>Database file permissions fixed.</div>";
        } else {
            echo "<div style='color: red;'>Failed to set database file permissions.</div>";
        }
        $perms = substr(sprintf('%o', fileperms(DB_PATH)), -4);
        echo "<div>Database file permissions after fix: " . $perms . "</div>";
    }
} else {
    echo "<div style='color: orange;'>Database file does not exist yet. It will be created when the application runs.</div>";
}

// Test database connection
echo "<h2>Step 3: Testing Database Connection</h2>";
if (file_exists(DB_PATH)) {
    try {
        $db = new PDO('sqlite:' . DB_PATH);
        $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        echo "<div style='color: green;'>Successfully connected to database.</div>";
        
        // Only test write if requested
        if ($fix_requested) {
            echo "<div>Testing write access...</div>";
            try {
                // Use a unique test table name to avoid conflicts
                $test_table = '_fix_permissions_test_' . time();
                
                // Try to create a test table
                $db->exec("CREATE TABLE IF NOT EXISTS {$test_table} (id INTEGER PRIMARY KEY, test TEXT)");
                
                // Try to insert data
                $test_value = 'permission_test_' . time();
                $db->exec("INSERT INTO {$test_table} (test) VALUES ('{$test_value}')");
                
                // Read data back
                $stmt = $db->query("SELECT * FROM {$test_table} ORDER BY id DESC LIMIT 1");
                $result = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($result && $result['test'] === $test_value) {
                    echo "<div style='color: green;'>Database write test successful!</div>";
                } else {
                    echo "<div style='color: red;'>Database write test failed. Could not read test data.</div>";
                }
                
                // Clean up test table
                $db->exec("DROP TABLE {$test_table}");
                echo "<div>Test table cleaned up.</div>";
                
            } catch (PDOException $e) {
                echo "<div style='color: red;'>Database write test failed with error: " . htmlspecialchars($e->getMessage()) . "</div>";
            }
        }
    } catch (PDOException $e) {
        echo "<div style='color: red;'>Failed to connect to database: " . htmlspecialchars($e->getMessage()) . "</div>";
    }
}

// Show action buttons
echo "<h2>Actions</h2>";
if ($fix_requested) {
    echo "<div style='color: green;'>Permission fixes have been applied.</div>";
    echo "<p><a href='fix_database_permissions.php' style='display: inline-block; padding: 10px 15px; background-color: #007bff; color: white; text-decoration: none; border-radius: 4px;'>Check Again</a></p>";
} else {
    echo "<p>Click the button below to fix database permissions:</p>";
    echo "<p><a href='fix_database_permissions.php?fix=yes' style='display: inline-block; padding: 10px 15px; background-color: #dc3545; color: white; text-decoration: none; border-radius: 4px;'>Fix Permissions</a></p>";
}

echo "<p><a href='/' style='display: inline-block; padding: 10px 15px; background-color: #6c757d; color: white; text-decoration: none; border-radius: 4px;'>Return to Homepage</a></p>";
?>