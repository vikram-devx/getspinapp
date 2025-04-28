<?php
// This is a special utility for extremely restrictive hosting environments
// where normal permissions fixes don't work
include 'includes/config.php';

echo "<!DOCTYPE html>
<html lang='en'>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <title>Special DB Fix Utility</title>
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
        <h1 class='mb-4'>Special Database Fix for Restricted Hosting</h1>
        <div class='alert alert-warning'>
            This utility is designed for hosting environments with severe restrictions on file permissions.
        </div>";

$action = isset($_GET['action']) ? $_GET['action'] : '';
$data_dir = __DIR__ . '/data';
$db_file = DB_PATH;
$temp_db = $data_dir . '/temp_db_' . time() . '.sqlite';
$backup_file = $data_dir . '/db_backup_' . time() . '.sqlite';

// Display database info
echo "<div class='card mb-4'>
        <div class='card-header'>
            <h2 class='h5 mb-0'>Database Information</h2>
        </div>
        <div class='card-body'>";

echo "<p>Database path: " . htmlspecialchars($db_file) . "</p>";
if (file_exists($db_file)) {
    echo "<div class='status-box status-success'>Database file exists</div>";
    echo "<p>File size: " . filesize($db_file) . " bytes</p>";
    echo "<p>File permissions: " . substr(sprintf('%o', fileperms($db_file)), -4) . "</p>";
    echo "<p>Last modified: " . date("Y-m-d H:i:s", filemtime($db_file)) . "</p>";
} else {
    echo "<div class='status-box status-error'>Database file does not exist!</div>";
}

echo "</div>
    </div>";

// Backup process
if ($action == 'backup') {
    echo "<div class='card mb-4'>
            <div class='card-header'>
                <h2 class='h5 mb-0'>Backup Process</h2>
            </div>
            <div class='card-body'>";
    
    if (file_exists($db_file)) {
        echo "<div>Attempting to create backup...</div>";
        $db_content = @file_get_contents($db_file);
        
        if ($db_content !== false) {
            if (@file_put_contents($backup_file, $db_content)) {
                echo "<div class='status-box status-success'>Backup created successfully at: " . htmlspecialchars($backup_file) . "</div>";
                echo "<div>Backup file size: " . filesize($backup_file) . " bytes</div>";
            } else {
                echo "<div class='status-box status-error'>Failed to create backup file!</div>";
            }
        } else {
            echo "<div class='status-box status-error'>Could not read the database file!</div>";
        }
    } else {
        echo "<div class='status-box status-error'>Database file does not exist, nothing to backup!</div>";
    }
    
    echo "</div>
        </div>";
}

// Create empty writable DB
if ($action == 'create_new') {
    echo "<div class='card mb-4'>
            <div class='card-header'>
                <h2 class='h5 mb-0'>Create New Database</h2>
            </div>
            <div class='card-body'>";
    
    // First create a backup if the DB exists
    if (file_exists($db_file)) {
        echo "<div>Creating backup before replacing database...</div>";
        $db_content = @file_get_contents($db_file);
        
        if ($db_content !== false) {
            if (@file_put_contents($backup_file, $db_content)) {
                echo "<div class='status-box status-success'>Backup created at: " . htmlspecialchars($backup_file) . "</div>";
            } else {
                echo "<div class='status-box status-error'>Failed to create backup! Aborting operation for safety.</div>";
                echo "</div></div>";
                goto show_actions;
            }
        } else {
            echo "<div class='status-box status-error'>Could not read database for backup! Aborting operation for safety.</div>";
            echo "</div></div>";
            goto show_actions;
        }
    }
    
    // Create temp database - using PDO so it creates a valid SQLite file
    try {
        echo "<div>Creating new empty database...</div>";
        
        // First create a temporary file
        $temp_pdo = new PDO('sqlite:' . $temp_db);
        $temp_pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        // Create minimal structure so PHP can connect to it 
        $temp_pdo->exec("CREATE TABLE temp_setup (id INTEGER PRIMARY KEY, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP)");
        $temp_pdo->exec("INSERT INTO temp_setup (id) VALUES (1)");
        $temp_pdo = null; // Close connection
        
        // Read the newly created database
        $new_db_content = @file_get_contents($temp_db);
        if ($new_db_content === false) {
            echo "<div class='status-box status-error'>Could not read the temporary database!</div>";
            echo "</div></div>";
            goto show_actions;
        }
        
        // Overwrite the main database
        if (@file_put_contents($db_file, $new_db_content)) {
            echo "<div class='status-box status-success'>Successfully created new writable database!</div>";
            @unlink($temp_db); // Clean up temp file
        } else {
            echo "<div class='status-box status-error'>Failed to replace the database file!</div>";
        }
    } catch (Exception $e) {
        echo "<div class='status-box status-error'>Error creating database: " . htmlspecialchars($e->getMessage()) . "</div>";
    }
    
    echo "</div>
        </div>";
}

// Test database
if ($action == 'test') {
    echo "<div class='card mb-4'>
            <div class='card-header'>
                <h2 class='h5 mb-0'>Database Test</h2>
            </div>
            <div class='card-body'>";
    
    if (file_exists($db_file)) {
        try {
            $db = new PDO('sqlite:' . $db_file);
            $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            echo "<div class='status-box status-success'>Successfully connected to database!</div>";
            
            // Test writing
            $test_table = '_test_' . time();
            try {
                $db->exec("CREATE TABLE IF NOT EXISTS {$test_table} (id INTEGER PRIMARY KEY, test TEXT)");
                $db->exec("INSERT INTO {$test_table} (test) VALUES ('test_" . time() . "')");
                $result = $db->query("SELECT * FROM {$test_table} LIMIT 1")->fetch(PDO::FETCH_ASSOC);
                
                if ($result) {
                    echo "<div class='status-box status-success'>Database write test successful!</div>";
                    $db->exec("DROP TABLE {$test_table}");
                } else {
                    echo "<div class='status-box status-error'>Could not read test data!</div>";
                }
            } catch (PDOException $e) {
                echo "<div class='status-box status-error'>Write test failed: " . htmlspecialchars($e->getMessage()) . "</div>";
            }
            
            // Check tables
            try {
                $tables = $db->query("SELECT name FROM sqlite_master WHERE type='table'")->fetchAll(PDO::FETCH_COLUMN);
                
                if (count($tables) > 0) {
                    echo "<div class='status-box status-info'>Tables in database: " . implode(', ', $tables) . "</div>";
                } else {
                    echo "<div class='status-box status-warning'>No tables found in database!</div>";
                }
            } catch (PDOException $e) {
                echo "<div class='status-box status-error'>Error listing tables: " . htmlspecialchars($e->getMessage()) . "</div>";
            }
            
        } catch (PDOException $e) {
            echo "<div class='status-box status-error'>Database connection failed: " . htmlspecialchars($e->getMessage()) . "</div>";
        }
    } else {
        echo "<div class='status-box status-error'>Database file does not exist!</div>";
    }
    
    echo "</div>
        </div>";
}

// Show actions
show_actions:
echo "<div class='card mb-4'>
        <div class='card-header'>
            <h2 class='h5 mb-0'>Available Actions</h2>
        </div>
        <div class='card-body'>
            <p>Choose an action to fix database issues:</p>
            
            <div class='d-flex flex-wrap gap-2'>
                <a href='fix_readonly_db.php?action=backup' class='btn btn-info'>Backup Database</a>
                <a href='fix_readonly_db.php?action=create_new' class='btn btn-warning'>Create New Empty Database</a>
                <a href='fix_readonly_db.php?action=test' class='btn btn-primary'>Test Database</a>
                <a href='/' class='btn btn-secondary'>Return to Homepage</a>
            </div>
            
            <div class='alert alert-info mt-4'>
                <h5>Instructions:</h5>
                <ol>
                    <li>First click 'Backup Database' to save your current data</li>
                    <li>Then click 'Create New Empty Database' to fix permissions</li>
                    <li>Finally, click 'Test Database' to verify it's working</li>
                    <li>After these steps, you'll need to reconfigure your app settings</li>
                </ol>
            </div>
            
            <div class='alert alert-warning mt-4'>
                <strong>Important:</strong> Creating a new database will reset your application.
                You will need to set up your admin account and settings again.
            </div>
        </div>
    </div>
    
    <div class='card mb-4'>
        <div class='card-header'>
            <h5 class='mb-0'>For Hosting Provider</h5>
        </div>
        <div class='card-body'>
            <p>If you're on a shared hosting environment, ask your hosting provider to:</p>
            <ol>
                <li>Set the correct ownership on your data directory to the web server user</li>
                <li>Ensure your PHP process has write permissions to SQLite databases</li>
                <li>Check if SELinux or other security policies are blocking database writes</li>
            </ol>
        </div>
    </div>
    
    </div>
</body>
</html>";
?>