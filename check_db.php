<?php
require_once 'includes/config.php';
require_once 'includes/database.php';

// Get database instance
$db = Database::getInstance();
$conn = $db->getConnection();

// Get all tables
$tables = [];
$sql = "SELECT name FROM sqlite_master WHERE type='table'";
$result = $conn->query($sql);
if ($result) {
    while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
        $tables[] = $row['name'];
    }
}

echo "Tables in database:\n";
print_r($tables);

// Check task_progress table specifically
echo "\nChecking task_progress table:\n";
if (in_array('task_progress', $tables)) {
    $sql = "PRAGMA table_info(task_progress)";
    $result = $conn->query($sql);
    if ($result) {
        $columns = [];
        while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
            $columns[] = [
                'name' => $row['name'],
                'type' => $row['type']
            ];
        }
        print_r($columns);
    }
} else {
    echo "task_progress table not found.\n";
}

// Check if 'canceled' is a valid status in existing records
echo "\nChecking existing statuses in task_progress:\n";
try {
    $sql = "SELECT DISTINCT status FROM task_progress";
    $result = $conn->query($sql);
    if ($result) {
        $statuses = [];
        while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
            $statuses[] = $row['status'];
        }
        print_r($statuses);
    }
} catch (PDOException $e) {
    echo "Error checking statuses: " . $e->getMessage() . "\n";
}