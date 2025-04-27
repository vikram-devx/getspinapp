<?php
require_once 'includes/init.php';

// Initialize Database
$db = Database::getInstance();
$conn = $db->getConnection();

// Check task_progress table structure
try {
    $query = "PRAGMA table_info(task_progress)";
    $stmt = $conn->query($query);
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<h2>Task Progress Table Columns:</h2>";
    echo "<pre>";
    print_r($columns);
    echo "</pre>";
    
    // Check for any existing records
    $query = "SELECT * FROM task_progress LIMIT 5";
    $stmt = $conn->query($query);
    $records = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<h2>Sample Records:</h2>";
    echo "<pre>";
    print_r($records);
    echo "</pre>";
    
} catch (PDOException $e) {
    echo "<h2>Error:</h2>";
    echo "<p>" . $e->getMessage() . "</p>";
}
?>