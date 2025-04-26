<?php
require_once 'includes/config.php';
require_once 'includes/db.php';

// Connect to the database
$db = Database::getInstance();
$conn = $db->getConnection();

// Get users table schema
$stmt = $conn->query("PRAGMA table_info(users)");
$columns = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "<h1>Users Table Schema</h1>";
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

// Check if transactions table exists
$stmt = $conn->query("SELECT name FROM sqlite_master WHERE type='table' AND name='transactions'");
$table_exists = $stmt->fetch(PDO::FETCH_ASSOC);

if ($table_exists) {
    // Get transactions table schema
    $stmt = $conn->query("PRAGMA table_info(transactions)");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo "<h1>Transactions Table Schema</h1>";
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
}

// Check if redemptions table exists
$stmt = $conn->query("SELECT name FROM sqlite_master WHERE type='table' AND name='redemptions'");
$table_exists = $stmt->fetch(PDO::FETCH_ASSOC);

if ($table_exists) {
    // Get redemptions table schema
    $stmt = $conn->query("PRAGMA table_info(redemptions)");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo "<h1>Redemptions Table Schema</h1>";
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
}
?>