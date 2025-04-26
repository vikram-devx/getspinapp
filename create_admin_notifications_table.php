<?php
require_once 'includes/config.php';
require_once 'includes/db.php';

try {
    $db = Database::getInstance();
    $conn = $db->getConnection();

    // Create admin_notifications table
    $conn->exec("CREATE TABLE IF NOT EXISTS admin_notifications (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        title TEXT NOT NULL,
        message TEXT NOT NULL,
        type TEXT NOT NULL DEFAULT 'info',
        is_read INTEGER DEFAULT 0,
        related_id INTEGER DEFAULT NULL,
        related_type TEXT DEFAULT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");

    // Create admin_settings table if not exists (for email notification settings)
    $conn->exec("CREATE TABLE IF NOT EXISTS admin_settings (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        setting_key TEXT NOT NULL UNIQUE,
        setting_value TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");

    // Add default notification settings
    $stmt = $conn->prepare("INSERT OR IGNORE INTO admin_settings (setting_key, setting_value) VALUES (?, ?)");
    $stmt->execute(['notification_email_enabled', '1']);
    $stmt->execute(['notification_email', '']);
    
    echo "Admin notifications tables created successfully!";
} catch (PDOException $e) {
    echo "Error creating tables: " . $e->getMessage();
}
?>