<?php
require_once 'includes/config.php';
require_once 'includes/db.php';

// Get database instance
$db = Database::getInstance();
$conn = $db->getConnection();

try {
    // Check if the column already exists
    $stmt = $conn->query("PRAGMA table_info(task_progress)");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $columnExists = false;
    foreach ($columns as $column) {
        if ($column['name'] === 'is_user_canceled') {
            $columnExists = true;
            break;
        }
    }
    
    if (!$columnExists) {
        // Add the is_user_canceled column to task_progress table
        $sql = "ALTER TABLE task_progress ADD COLUMN is_user_canceled INTEGER DEFAULT 0";
        $conn->exec($sql);
        echo "Column 'is_user_canceled' successfully added to task_progress table.";
        
        // Update any existing failed task entries that were manually canceled
        $updateSql = "UPDATE task_progress SET is_user_canceled = 1 WHERE status = 'failed' AND progress_message = 'Task canceled by user'";
        $rowsAffected = $conn->exec($updateSql);
        echo "<br>Updated $rowsAffected existing task entries.";
    } else {
        echo "Column 'is_user_canceled' already exists in task_progress table.";
    }
    
    // Display current table structure
    echo "<h2>Updated Task Progress Table Structure:</h2>";
    $stmt = $conn->query("PRAGMA table_info(task_progress)");
    $updatedColumns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "<pre>";
    print_r($updatedColumns);
    echo "</pre>";
    
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>