<?php
require_once 'includes/config.php';
require_once 'includes/db.php';

// Get database instance
$db = Database::getInstance();
$conn = $db->getConnection();

try {
    // Modify the CHECK constraint to include 'canceled' status
    $sql = "CREATE TABLE task_progress_new (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        user_id INTEGER NOT NULL,
        offer_id INTEGER NOT NULL,
        status TEXT CHECK(status IN ('started', 'in_progress', 'completed', 'failed', 'canceled')) NOT NULL,
        progress_percent INTEGER DEFAULT 0,
        progress_message TEXT,
        estimated_completion_time DATETIME,
        completion_time DATETIME,
        last_updated DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id)
    )";
    
    // Begin transaction
    $conn->beginTransaction();
    
    // Create new table with updated constraint
    $conn->exec($sql);
    
    // Copy data from old table to new
    $conn->exec("INSERT INTO task_progress_new 
                 SELECT * FROM task_progress");
                 
    // Drop old table
    $conn->exec("DROP TABLE task_progress");
    
    // Rename new table to original name
    $conn->exec("ALTER TABLE task_progress_new RENAME TO task_progress");
    
    // Commit transaction
    $conn->commit();
    
    echo "Successfully updated task_progress table to include 'canceled' status.";
    
} catch (PDOException $e) {
    // Rollback transaction on error
    if ($conn->inTransaction()) {
        $conn->rollBack();
    }
    
    echo "Error updating task_progress table: " . $e->getMessage();
}
?>