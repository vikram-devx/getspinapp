<?php
require_once 'includes/config.php';
require_once 'includes/db.php';

// Add is_blocked column to users table
try {
    $db = Database::getInstance();
    $conn = $db->getConnection();
    
    // Check if column already exists
    $stmt = $conn->query("PRAGMA table_info(users)");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $columnExists = false;
    
    foreach ($columns as $column) {
        if ($column['name'] === 'is_blocked') {
            $columnExists = true;
            break;
        }
    }
    
    if (!$columnExists) {
        // Add the is_blocked column with default value 0 (not blocked)
        $conn->exec("ALTER TABLE users ADD COLUMN is_blocked INTEGER DEFAULT 0 NOT NULL");
        echo "✅ Successfully added is_blocked column to users table<br>";
    } else {
        echo "ℹ️ is_blocked column already exists in users table<br>";
    }
    
    echo "Migration completed successfully!";
} catch (PDOException $e) {
    die("Error: " . $e->getMessage());
}
?>

<a href="admin/users.php" class="btn btn-primary mt-3">Go to User Management</a>