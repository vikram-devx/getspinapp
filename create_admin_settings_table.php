<?php
/**
 * Create Admin Settings Table
 * 
 * This script creates the admin_settings table for storing admin notification settings and other admin-specific configurations.
 */

require_once 'includes/config.php';
require_once 'includes/db.php';

// Get database instance
$db = Database::getInstance();
$conn = $db->getConnection();

try {
    // Check if admin_settings table already exists
    try {
        $conn->query("SELECT 1 FROM admin_settings LIMIT 1");
        // If the query succeeds, the table exists
        echo "Table 'admin_settings' already exists.<br>";
    } catch (PDOException $e) {
        // Table doesn't exist, create it
        echo "Creating 'admin_settings' table...<br>";
        
        // Create the admin_settings table
        $query = "CREATE TABLE admin_settings (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            setting_key TEXT NOT NULL,
            setting_value TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )";
        
        $conn->exec($query);
        echo "Table 'admin_settings' created successfully.<br>";
        
        // Create indexes
        $conn->exec("CREATE UNIQUE INDEX idx_admin_settings_key ON admin_settings (setting_key)");
        
        // Insert default settings
        $default_settings = [
            ['notification_email', ''],
            ['notification_email_enabled', '0']
        ];
        
        $stmt = $conn->prepare("INSERT INTO admin_settings (setting_key, setting_value) VALUES (?, ?)");
        
        foreach ($default_settings as $setting) {
            $stmt->execute($setting);
        }
        
        echo "Default admin settings inserted successfully.<br>";
    }
    
    echo "Admin settings table setup complete!";
    
} catch (PDOException $e) {
    die("Database error: " . $e->getMessage());
}
?>