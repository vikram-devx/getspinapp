<?php
// We need to simulate a web request to make sure all config variables are set
$_SERVER['HTTP_HOST'] = 'localhost';
$_SERVER['REQUEST_SCHEME'] = 'http';

// Include the database configuration 
require_once 'includes/config.php';
require_once 'includes/db.php';

// Get the database instance (using the singleton pattern from db.php)
$database = Database::getInstance();
$pdo = $database->getConnection();

try {
    // Create promo_slides table
    $query = "CREATE TABLE IF NOT EXISTS promo_slides (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        title TEXT NOT NULL,
        description TEXT,
        button_text TEXT,
        button_url TEXT,
        image_path TEXT NOT NULL,
        active INTEGER DEFAULT 1,
        display_order INTEGER DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";
    
    $pdo->exec($query);
    echo "Promo slides table created successfully!";
    
    // Check if there are already slides
    $checkStmt = $pdo->query("SELECT COUNT(*) FROM promo_slides");
    $count = $checkStmt->fetchColumn();
    
    // Only insert the default slide if the table is empty
    if ($count == 0) {
        // Insert a default promo slide
        $insertQuery = "INSERT INTO promo_slides (title, description, button_text, button_url, image_path, active, display_order) 
                        VALUES (
                            'Welcome to GetSpins App',
                            'Earn Rewards for Completing Simple Tasks',
                            'Get Started',
                            'register.php',
                            'assets/img/promo-default.jpg',
                            1,
                            1
                        )";
        
        $pdo->exec($insertQuery);
        echo "<br>Default promo slide inserted successfully!";
    } else {
        echo "<br>Slides already exist, no default slide was added.";
    }
    
} catch(PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>