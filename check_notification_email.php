<?php
/**
 * This script checks the notification email settings in the database
 */

require_once 'includes/config.php';
require_once 'includes/db.php';

try {
    $db = Database::getInstance();
    $conn = $db->getConnection();
    
    // Check notification email
    $stmt = $conn->query("SELECT setting_key, setting_value FROM admin_settings WHERE setting_key IN ('notification_email', 'notification_email_enabled')");
    $settings = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<h2>Notification Email Settings</h2>";
    if (count($settings) == 0) {
        echo "No notification email settings found in database.<br>";
    } else {
        echo "<pre>";
        foreach ($settings as $setting) {
            echo "{$setting['setting_key']}: {$setting['setting_value']}\n";
        }
        echo "</pre>";
    }
    
    // Check EmailJS settings
    $stmt = $conn->query("SELECT setting_key, setting_value FROM admin_settings WHERE setting_key IN ('emailjs_user_id', 'emailjs_service_id', 'emailjs_template_id')");
    $emailjs_settings = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<h2>EmailJS Settings</h2>";
    if (count($emailjs_settings) == 0) {
        echo "No EmailJS settings found in database.<br>";
    } else {
        echo "<pre>";
        foreach ($emailjs_settings as $setting) {
            // Don't show full values for security, just if they're set
            $value = !empty($setting['setting_value']) ? "[SET]" : "[EMPTY]";
            echo "{$setting['setting_key']}: {$value}\n";
        }
        echo "</pre>";
    }
    
    // Check environment variables
    echo "<h2>Environment Variables</h2>";
    echo "<pre>";
    echo "EMAILJS_USER_ID: " . (getenv("EMAILJS_USER_ID") ? "[SET]" : "[EMPTY]") . "\n";
    echo "EMAILJS_SERVICE_ID: " . (getenv("EMAILJS_SERVICE_ID") ? "[SET]" : "[EMPTY]") . "\n";
    echo "EMAILJS_TEMPLATE_ID: " . (getenv("EMAILJS_TEMPLATE_ID") ? "[SET]" : "[EMPTY]") . "\n";
    echo "</pre>";
    
} catch (PDOException $e) {
    echo "Database error: " . $e->getMessage();
}
?>