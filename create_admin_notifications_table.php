<?php
/**
 * Create admin notifications table
 * 
 * This script creates the admin_notifications table for storing admin notifications.
 */

require_once 'includes/config.php';
require_once 'includes/db.php';

// Get database connection
$db = Database::getInstance();
$conn = $db->getConnection();

try {
    // Check if table already exists
    $tableExists = false;
    try {
        $conn->query("SELECT 1 FROM admin_notifications LIMIT 1");
        $tableExists = true;
        echo "Table 'admin_notifications' already exists.<br>";
    } catch (PDOException $e) {
        // Table doesn't exist, which is expected
        $tableExists = false;
    }

    if (!$tableExists) {
        // Create the admin_notifications table
        $query = "CREATE TABLE admin_notifications (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            title TEXT NOT NULL,
            message TEXT NOT NULL,
            type TEXT DEFAULT 'info',
            is_read INTEGER DEFAULT 0,
            related_id INTEGER DEFAULT NULL,
            related_type TEXT DEFAULT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )";
        
        $conn->exec($query);
        echo "Table 'admin_notifications' created successfully.<br>";
        
        // Create indexes for better performance
        $conn->exec("CREATE INDEX idx_notifications_is_read ON admin_notifications (is_read)");
        $conn->exec("CREATE INDEX idx_notifications_type ON admin_notifications (type)");
        $conn->exec("CREATE INDEX idx_notifications_created_at ON admin_notifications (created_at)");
        
        echo "Notification indexes created successfully.<br>";
        
        // Create sample notification
        $stmt = $conn->prepare("INSERT INTO admin_notifications (title, message, type) VALUES (?, ?, ?)");
        $stmt->execute([
            'Welcome to Admin Notifications',
            'This system will notify you when users redeem rewards or other important events occur.',
            'system'
        ]);
        
        echo "Sample notification created successfully.<br>";
    }
    
    // Create or update admin settings for email notifications
    try {
        $tableExists = false;
        try {
            $conn->query("SELECT 1 FROM admin_settings LIMIT 1");
            $tableExists = true;
        } catch (PDOException $e) {
            // Table doesn't exist
            $tableExists = false;
        }
        
        if (!$tableExists) {
            // Create admin_settings table
            $query = "CREATE TABLE admin_settings (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                setting_key TEXT NOT NULL UNIQUE,
                setting_value TEXT DEFAULT NULL,
                setting_description TEXT DEFAULT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )";
            
            $conn->exec($query);
            echo "Table 'admin_settings' created successfully.<br>";
        }
        
        // Check if notification settings exist
        $stmt = $conn->prepare("SELECT COUNT(*) FROM admin_settings WHERE setting_key IN ('notification_email', 'notification_email_enabled')");
        $stmt->execute();
        $count = $stmt->fetchColumn();
        
        if ($count < 2) {
            // Add notification email settings
            $conn->exec("INSERT OR IGNORE INTO admin_settings (setting_key, setting_value, setting_description) 
                        VALUES ('notification_email', '', 'Email address to receive admin notifications')");
            
            $conn->exec("INSERT OR IGNORE INTO admin_settings (setting_key, setting_value, setting_description) 
                        VALUES ('notification_email_enabled', '0', 'Enable or disable email notifications (1 = enabled, 0 = disabled)')");
            
            echo "Notification email settings created successfully.<br>";
        } else {
            echo "Notification email settings already exist.<br>";
        }
        
    } catch (PDOException $e) {
        echo "Error creating admin settings: " . $e->getMessage() . "<br>";
    }
    
    echo "<p>Setup completed successfully!</p>";
    echo "<a href='admin/index.php' class='btn btn-primary'>Go to Admin Dashboard</a>";
    
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "<br>";
}
?>