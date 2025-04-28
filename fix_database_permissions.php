<?php
// Script to fix database permissions for SQLite
include 'includes/config.php';

echo "<!DOCTYPE html>
<html lang='en'>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <title>Database Fix Utility</title>
    <link rel='stylesheet' href='https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.1.3/css/bootstrap.min.css'>
    <style>
        body { padding: 20px; }
        .status-box { margin-top: 10px; padding: 15px; border-radius: 5px; }
        .status-success { background-color: #d4edda; color: #155724; }
        .status-error { background-color: #f8d7da; color: #721c24; }
        .status-warning { background-color: #fff3cd; color: #856404; }
        .status-info { background-color: #d1ecf1; color: #0c5460; }
    </style>
</head>
<body>
    <div class='container'>
        <h1 class='mb-4'>Database Permissions Fix Utility</h1>
        <div class='alert alert-info'>
            This utility helps fix database permission issues for SQLite on shared hosting environments.
        </div>";

// Check if fix was requested
$fix_requested = isset($_GET['fix']) && $_GET['fix'] == 'yes';
$force_copy = isset($_GET['force_copy']) && $_GET['force_copy'] == 'yes';

// Check database directory
echo "<div class='card mb-4'>
        <div class='card-header'>
            <h2 class='h5 mb-0'>Step 1: Checking Data Directory</h2>
        </div>
        <div class='card-body'>";

$data_dir = __DIR__ . '/data';

if (!file_exists($data_dir)) {
    echo "<div class='status-box status-error'>Data directory does not exist. Creating it now...</div>";
    if (@mkdir($data_dir, 0777, true)) {
        echo "<div class='status-box status-success'>Data directory created successfully.</div>";
    } else {
        echo "<div class='status-box status-error'>Failed to create data directory.</div>";
    }
} else {
    echo "<div class='status-box status-success'>Data directory exists at: " . htmlspecialchars($data_dir) . "</div>";
}

// Check permissions on data directory
$perms = substr(sprintf('%o', fileperms($data_dir)), -4);
echo "<div>Current data directory permissions: " . $perms . "</div>";

if ($perms != '0777' && $fix_requested) {
    echo "<div>Attempting to set data directory permissions to 0777...</div>";
    if (@chmod($data_dir, 0777)) {
        echo "<div class='status-box status-success'>Data directory permissions fixed.</div>";
    } else {
        echo "<div class='status-box status-warning'>Could not change directory permissions directly. Trying alternative methods...</div>";
        
        // Test if we can write to the directory
        $test_file = $data_dir . '/.write_test_' . time();
        if (@file_put_contents($test_file, 'test')) {
            echo "<div class='status-box status-success'>Directory is writable despite permissions.</div>";
            @unlink($test_file);
        } else {
            echo "<div class='status-box status-error'>Cannot write to data directory. Contact your hosting provider.</div>";
        }
    }
    $perms = substr(sprintf('%o', fileperms($data_dir)), -4);
    echo "<div>Data directory permissions after fix attempt: " . $perms . "</div>";
}

echo "</div>
    </div>";

// Check database file
echo "<div class='card mb-4'>
        <div class='card-header'>
            <h2 class='h5 mb-0'>Step 2: Checking Database File</h2>
        </div>
        <div class='card-body'>";

if (file_exists(DB_PATH)) {
    echo "<div class='status-box status-success'>Database file exists at: " . htmlspecialchars(DB_PATH) . "</div>";
    
    // Check permissions on database file
    $perms = substr(sprintf('%o', fileperms(DB_PATH)), -4);
    echo "<div>Current database file permissions: " . $perms . "</div>";
    
    if (($perms != '0666' && $fix_requested) || $force_copy) {
        echo "<div>Attempting to set database file permissions to 0666...</div>";
        if (@chmod(DB_PATH, 0666)) {
            echo "<div class='status-box status-success'>Database file permissions fixed.</div>";
        } else {
            echo "<div class='status-box status-warning'>Could not change file permissions directly. Trying alternative methods...</div>";
            
            // Try to make a copy of the database and replace the original
            if ($force_copy || $fix_requested) {
                $backup_file = DB_PATH . '.backup_' . time();
                $db_content = @file_get_contents(DB_PATH);
                
                if ($db_content !== false) {
                    // First create a backup
                    if (@file_put_contents($backup_file, $db_content)) {
                        echo "<div class='status-box status-success'>Created backup of database at: " . htmlspecialchars($backup_file) . "</div>";
                        
                        // Now try to replace the original file by writing the content again
                        if (@file_put_contents(DB_PATH, $db_content)) {
                            echo "<div class='status-box status-success'>Successfully rewrote database file to fix ownership.</div>";
                        } else {
                            echo "<div class='status-box status-error'>Could not rewrite database file.</div>";
                        }
                    } else {
                        echo "<div class='status-box status-error'>Could not create backup of database.</div>";
                    }
                } else {
                    echo "<div class='status-box status-error'>Could not read database content for backup.</div>";
                }
            }
        }
        $perms = substr(sprintf('%o', fileperms(DB_PATH)), -4);
        echo "<div>Database file permissions after fix attempt: " . $perms . "</div>";
    }
} else {
    echo "<div class='status-box status-warning'>Database file does not exist yet. It will be created when the application runs.</div>";
}

echo "</div>
    </div>";

// Test database connection
echo "<div class='card mb-4'>
        <div class='card-header'>
            <h2 class='h5 mb-0'>Step 3: Testing Database Connection</h2>
        </div>
        <div class='card-body'>";

if (file_exists(DB_PATH)) {
    try {
        $db = new PDO('sqlite:' . DB_PATH);
        $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        echo "<div class='status-box status-success'>Successfully connected to database.</div>";
        
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
                    echo "<div class='status-box status-success'>Database write test successful!</div>";
                } else {
                    echo "<div class='status-box status-error'>Database write test failed. Could not read test data.</div>";
                }
                
                // Clean up test table
                $db->exec("DROP TABLE {$test_table}");
                echo "<div>Test table cleaned up.</div>";
                
            } catch (PDOException $e) {
                echo "<div class='status-box status-error'>Database write test failed with error: " . htmlspecialchars($e->getMessage()) . "</div>";
                
                if (strpos($e->getMessage(), 'readonly database') !== false) {
                    echo "<div class='status-box status-warning'>
                            Your database is in read-only mode. This typically happens when:
                            <ul>
                                <li>The PHP process does not have write permissions to the database file</li>
                                <li>The database file is owned by a different user than the PHP process</li>
                                <li>The hosting environment has restrictions on file permissions</li>
                            </ul>
                            Try using the 'Force Copy Database' option below.
                          </div>";
                }
            }
        }
    } catch (PDOException $e) {
        echo "<div class='status-box status-error'>Failed to connect to database: " . htmlspecialchars($e->getMessage()) . "</div>";
    }
}

echo "</div>
    </div>";

// Show action buttons
echo "<div class='card mb-4'>
        <div class='card-header'>
            <h2 class='h5 mb-0'>Actions</h2>
        </div>
        <div class='card-body'>";

if ($fix_requested) {
    echo "<div class='status-box status-success'>Permission fixes have been applied.</div>";
    echo "<div class='d-flex gap-2 mt-3'>
            <a href='fix_database_permissions.php' class='btn btn-primary'>Check Again</a>
            <a href='fix_database_permissions.php?fix=yes&force_copy=yes' class='btn btn-warning'>Force Copy Database</a>
            <a href='/' class='btn btn-secondary'>Return to Homepage</a>
          </div>";
} else {
    echo "<p>Click the button below to fix database permissions:</p>";
    echo "<div class='d-flex gap-2'>
            <a href='fix_database_permissions.php?fix=yes' class='btn btn-danger'>Fix Permissions</a>
            <a href='/' class='btn btn-secondary'>Return to Homepage</a>
          </div>";
}

echo "</div>
    </div>
    
    <div class='card mb-4'>
        <div class='card-header'>
            <h2 class='h5 mb-0'>Instructions for Hosting Providers</h2>
        </div>
        <div class='card-body'>
            <p>If you're still experiencing database permission issues after using this tool, please provide these instructions to your hosting provider:</p>
            
            <div class='status-box status-info'>
                <h5>Hosting Provider Instructions</h5>
                <ol>
                    <li>The application uses SQLite database located at: " . htmlspecialchars(DB_PATH) . "</li>
                    <li>The web server process needs full read/write permissions to both the database file and its parent directory.</li>
                    <li>Please set ownership of the database file to the web server user (e.g., www-data, apache, nobody).</li>
                    <li>Ensure the following permissions:
                        <ul>
                            <li>Database directory: chmod 0777 (or at minimum 0755 with proper ownership)</li>
                            <li>Database file: chmod 0666 (or at minimum 0644 with proper ownership)</li>
                        </ul>
                    </li>
                </ol>
            </div>
        </div>
    </div>
    
    </div>
</body>
</html>";
?>