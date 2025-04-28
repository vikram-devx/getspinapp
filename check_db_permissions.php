<?php
include 'includes/config.php';

// Check if database directory exists
echo "Checking database directory...\n";
if (file_exists(__DIR__ . '/data')) {
    echo "Data directory exists.\n";
    
    // Check permissions on data directory
    $perms = substr(sprintf('%o', fileperms(__DIR__ . '/data')), -4);
    echo "Data directory permissions: " . $perms . "\n";
    
    // Check if database file exists
    if (file_exists(DB_PATH)) {
        echo "Database file exists.\n";
        
        // Check permissions on database file
        $perms = substr(sprintf('%o', fileperms(DB_PATH)), -4);
        echo "Database file permissions: " . $perms . "\n";
        
        // Try to fix permissions
        echo "Setting directory permissions to 0777...\n";
        chmod(__DIR__ . '/data', 0777);
        
        echo "Setting database file permissions to 0666...\n";
        chmod(DB_PATH, 0666);
        
        // Verify permissions again
        $perms = substr(sprintf('%o', fileperms(__DIR__ . '/data')), -4);
        echo "Data directory permissions after fix: " . $perms . "\n";
        
        $perms = substr(sprintf('%o', fileperms(DB_PATH)), -4);
        echo "Database file permissions after fix: " . $perms . "\n";
        
        // Try to write to the database
        echo "Testing database write access...\n";
        try {
            $db = new PDO('sqlite:' . DB_PATH);
            $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            
            // Try to create a test table
            $db->exec("CREATE TABLE IF NOT EXISTS _permission_test (id INTEGER PRIMARY KEY, test TEXT)");
            
            // Try to insert data
            $db->exec("INSERT INTO _permission_test (test) VALUES ('test_" . time() . "')");
            
            // Read data back
            $stmt = $db->query("SELECT * FROM _permission_test ORDER BY id DESC LIMIT 1");
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($result) {
                echo "Database write test successful! Wrote and read: " . $result['test'] . "\n";
            } else {
                echo "Database write test failed. Could not read test data.\n";
            }
            
            // Clean up test table
            $db->exec("DROP TABLE _permission_test");
            
        } catch (PDOException $e) {
            echo "Database write test failed with error: " . $e->getMessage() . "\n";
        }
    } else {
        echo "Database file does not exist.\n";
    }
} else {
    echo "Data directory does not exist.\n";
}

echo "\nDone!";
?>