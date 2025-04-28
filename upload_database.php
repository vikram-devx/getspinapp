<?php
// This script allows uploading a SQLite database file
// For use with hosting environments where file permissions can't be fixed through PHP
include 'includes/config.php';

// Only allow admin access to this file
require_once 'includes/auth.php';
$auth = new Auth();
if (!$auth->isLoggedIn() || !$auth->isAdmin()) {
    header('Location: /login');
    exit;
}

// Check if the form was submitted
$success = false;
$error = '';
$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Check if a file was uploaded
    if (isset($_FILES['database_file']) && $_FILES['database_file']['error'] === UPLOAD_ERR_OK) {
        $uploaded_file = $_FILES['database_file']['tmp_name'];
        
        // Verify it's an SQLite database (basic check)
        $header = file_get_contents($uploaded_file, false, null, 0, 16);
        if (substr($header, 0, 6) === 'SQLite') {
            // Create backup of the existing database
            if (file_exists(DB_PATH)) {
                $backup_file = DB_PATH . '.backup_' . time();
                if (!@copy(DB_PATH, $backup_file)) {
                    $error = "Failed to create a backup of the existing database.";
                } else {
                    $message .= "Created backup at: " . htmlspecialchars($backup_file) . "<br>";
                }
            }
            
            // Move the uploaded file to the database location
            if (empty($error)) {
                if (move_uploaded_file($uploaded_file, DB_PATH)) {
                    // Ensure proper permissions
                    @chmod(DB_PATH, 0666);
                    $success = true;
                    $message .= "Database uploaded successfully!";
                } else {
                    $error = "Failed to move the uploaded file to the database location.";
                }
            }
        } else {
            $error = "The uploaded file does not appear to be a valid SQLite database.";
        }
    } else {
        $error = "Please select a database file to upload.";
    }
}

// Create a fresh empty database that can be downloaded
$empty_db_path = __DIR__ . '/data/empty_db.sqlite';
try {
    if (!file_exists(dirname($empty_db_path))) {
        @mkdir(dirname($empty_db_path), 0777, true);
    }
    $pdo = new PDO('sqlite:' . $empty_db_path);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->exec("CREATE TABLE IF NOT EXISTS users (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        username TEXT NOT NULL UNIQUE,
        email TEXT NOT NULL UNIQUE,
        password TEXT NOT NULL,
        points INTEGER NOT NULL DEFAULT 0,
        is_admin INTEGER NOT NULL DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");
    $stmt = $pdo->prepare("INSERT INTO users 
                (username, email, password, is_admin) 
            VALUES 
                ('admin', 'admin@example.com', :password, 1)");
    $stmt->bindValue(':password', password_hash('admin123', PASSWORD_DEFAULT));
    $stmt->execute();
    $empty_db_created = true;
} catch (Exception $e) {
    $empty_db_created = false;
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Upload Database File</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css">
    <style>
        body { padding-top: 20px; }
        .container { max-width: 800px; }
    </style>
</head>
<body>
    <div class="container">
        <h1 class="mb-4">Database Management</h1>
        
        <?php if ($success): ?>
        <div class="alert alert-success">
            <?php echo $message; ?>
        </div>
        <?php elseif (!empty($error)): ?>
        <div class="alert alert-danger">
            <?php echo $error; ?>
        </div>
        <?php endif; ?>
        
        <div class="card mb-4">
            <div class="card-header bg-primary text-white">
                <h2 class="h5 mb-0">Upload Database File</h2>
            </div>
            <div class="card-body">
                <p>Use this form to upload a SQLite database file. This is useful if your hosting environment prevents writing to the database through PHP.</p>
                
                <form method="post" enctype="multipart/form-data">
                    <div class="mb-3">
                        <label for="database_file" class="form-label">Database File</label>
                        <input type="file" class="form-control" id="database_file" name="database_file" required>
                    </div>
                    
                    <div class="alert alert-warning">
                        <strong>Warning:</strong> Uploading a new database will replace your existing one. Make sure to backup your data first.
                    </div>
                    
                    <button type="submit" class="btn btn-primary">Upload Database</button>
                </form>
            </div>
        </div>
        
        <div class="card mb-4">
            <div class="card-header bg-info text-white">
                <h2 class="h5 mb-0">Download Current Database</h2>
            </div>
            <div class="card-body">
                <p>Download your current database file for backup purposes.</p>
                
                <?php if (file_exists(DB_PATH)): ?>
                <a href="data/rewards_app.sqlite" class="btn btn-info" download>Download Current Database</a>
                <?php else: ?>
                <div class="alert alert-warning">
                    No database file found.
                </div>
                <?php endif; ?>
            </div>
        </div>
        
        <div class="card mb-4">
            <div class="card-header bg-success text-white">
                <h2 class="h5 mb-0">Fresh Empty Database</h2>
            </div>
            <div class="card-body">
                <p>Download a fresh empty database with just the basic structure and an admin user.</p>
                
                <?php if ($empty_db_created): ?>
                <a href="data/empty_db.sqlite" class="btn btn-success" download>Download Empty Database</a>
                <div class="mt-2 text-muted">
                    <small>Default admin login: username: <strong>admin</strong>, password: <strong>admin123</strong></small>
                </div>
                <?php else: ?>
                <div class="alert alert-warning">
                    Could not create empty database.
                </div>
                <?php endif; ?>
            </div>
        </div>
        
        <div class="card mb-4">
            <div class="card-header bg-secondary text-white">
                <h2 class="h5 mb-0">Manual Steps for Hosting Environment</h2>
            </div>
            <div class="card-body">
                <p>If you're using a shared hosting environment with file permission restrictions, follow these steps:</p>
                
                <ol>
                    <li>Download your current database using the button above for backup</li>
                    <li>Download the empty database</li>
                    <li>Using your hosting control panel's file manager, upload the empty database to <code><?php echo htmlspecialchars(DB_PATH); ?></code></li>
                    <li>Set the file permissions to 666 (rw-rw-rw-) using your hosting control panel</li>
                    <li>Log in with the admin user provided in the empty database</li>
                    <li>Reconfigure your application settings</li>
                </ol>
                
                <a href="/" class="btn btn-secondary">Return to Homepage</a>
            </div>
        </div>
    </div>
</body>
</html>