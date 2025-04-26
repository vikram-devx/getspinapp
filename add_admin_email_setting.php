<?php
/**
 * This script adds admin_email setting to the admin_settings table if it doesn't exist
 */

require_once 'includes/config.php';
require_once 'includes/db.php';

try {
    $db = Database::getInstance();
    $conn = $db->getConnection();
    
    // Check if admin_settings table exists
    $tables = $conn->query("SELECT name FROM sqlite_master WHERE type='table' AND name='admin_settings'")->fetchAll();
    
    if (!count($tables)) {
        // Create admin_settings table if it doesn't exist
        $conn->exec("CREATE TABLE admin_settings (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            setting_key TEXT NOT NULL UNIQUE,
            value TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )");
        
        echo "Created admin_settings table.<br>";
    }
    
    // Check if admin_email setting exists
    $stmt = $conn->prepare("SELECT * FROM admin_settings WHERE setting_key = 'admin_email'");
    $stmt->execute();
    $exists = $stmt->fetch();
    
    if (!$exists) {
        // Add admin_email setting
        $stmt = $conn->prepare("INSERT INTO admin_settings (setting_key, value) VALUES ('admin_email', '')");
        if ($stmt->execute()) {
            echo "Added admin_email setting.<br>";
        } else {
            echo "Failed to add admin_email setting.<br>";
        }
    } else {
        echo "admin_email setting already exists.<br>";
    }
    
    echo "Done!<br>";
    
} catch (PDOException $e) {
    echo "Database error: " . $e->getMessage() . "<br>";
}
?>