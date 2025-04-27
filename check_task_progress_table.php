<?php
require_once 'includes/config.php';
require_once 'includes/db.php';

// Get database instance
$db = Database::getInstance();
$conn = $db->getConnection();

try {
    // Check the structure of the task_progress table
    $sql = "PRAGMA table_info(task_progress)";
    $stmt = $conn->query($sql);
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<h2>Task Progress Table Structure:</h2>";
    echo "<pre>";
    print_r($columns);
    echo "</pre>";
    
    // Check for any sample data
    $sql = "SELECT * FROM task_progress LIMIT 1";
    $stmt = $conn->query($sql);
    $sample = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo "<h2>Sample Data:</h2>";
    echo "<pre>";
    print_r($sample);
    echo "</pre>";
    
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>