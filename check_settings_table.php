<?php
require_once 'includes/config.php';
require_once 'includes/db.php';

// Connect to the database
$db = Database::getInstance();
$conn = $db->getConnection();

// Check if settings table exists
$stmt = $conn->query("SELECT name FROM sqlite_master WHERE type='table' AND name='settings'");
$table_exists = $stmt->fetch(PDO::FETCH_ASSOC);

if ($table_exists) {
    echo "<h1>Settings Table Exists</h1>";
    
    // Get settings table schema
    $stmt = $conn->query("PRAGMA table_info(settings)");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo "<h2>Settings Table Schema</h2>";
    echo "<table border='1'>";
    echo "<tr><th>CID</th><th>Name</th><th>Type</th><th>NotNull</th><th>Default</th><th>PK</th></tr>";
    foreach ($columns as $column) {
        echo "<tr>";
        echo "<td>" . $column['cid'] . "</td>";
        echo "<td>" . $column['name'] . "</td>";
        echo "<td>" . $column['type'] . "</td>";
        echo "<td>" . $column['notnull'] . "</td>";
        echo "<td>" . $column['dflt_value'] . "</td>";
        echo "<td>" . $column['pk'] . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    // Get current settings
    $stmt = $conn->query("SELECT * FROM settings");
    $settings = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<h2>Current Settings</h2>";
    echo "<table border='1'>";
    echo "<tr><th>ID</th><th>Key</th><th>Value</th><th>Created At</th><th>Updated At</th></tr>";
    foreach ($settings as $setting) {
        echo "<tr>";
        echo "<td>" . $setting['id'] . "</td>";
        echo "<td>" . $setting['setting_key'] . "</td>";
        echo "<td>" . $setting['setting_value'] . "</td>";
        echo "<td>" . $setting['created_at'] . "</td>";
        echo "<td>" . $setting['updated_at'] . "</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<h1>Settings Table Does Not Exist</h1>";
    echo "<p>We need to create it.</p>";
}
?>