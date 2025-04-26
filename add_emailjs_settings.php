<?php
/**
 * This script adds EmailJS configuration settings to the admin_settings table
 * For existing installations that don't have these settings yet
 */

require_once 'includes/config.php';
require_once 'includes/db.php';

try {
    $db = Database::getInstance();
    $conn = $db->getConnection();
    
    // Define the EmailJS settings to add
    $emailjs_settings = [
        'emailjs_user_id',
        'emailjs_service_id',
        'emailjs_template_id'
    ];
    
    // Check if each setting exists, add if not
    foreach ($emailjs_settings as $setting_key) {
        $stmt = $conn->prepare("SELECT COUNT(*) FROM admin_settings WHERE setting_key = ?");
        $stmt->execute([$setting_key]);
        $exists = (int)$stmt->fetchColumn();
        
        if (!$exists) {
            // Add the setting with empty value
            $stmt = $conn->prepare("INSERT INTO admin_settings (setting_key, setting_value) VALUES (?, '')");
            $stmt->execute([$setting_key]);
            echo "Added setting: {$setting_key}<br>";
        } else {
            echo "Setting already exists: {$setting_key}<br>";
        }
    }
    
    echo "EmailJS settings setup complete!<br>";
    
} catch (PDOException $e) {
    echo "Database error: " . $e->getMessage() . "<br>";
}
?>