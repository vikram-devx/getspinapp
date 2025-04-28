<?php
// Configuration settings for the application
session_start();

// Database Configuration - SQLite
define('DB_TYPE', 'sqlite');
define('DB_PATH', __DIR__ . '/../data/rewards_app.sqlite');

// OGAds API Configuration
// Get API key from environment variable if available, otherwise use the default
define('OGADS_API_KEY', getenv('OGADS_API_KEY') ? getenv('OGADS_API_KEY') : '31621|84YSBHzNxHsFkLk6aRohyLBWYn7f0HGQpHBHQo8F1ae7067f');
define('OGADS_API_URL', 'https://unlockcontent.net/api/v2');

// Application Settings
define('APP_NAME', 'GetSpins App');
define('APP_URL', 'http://' . $_SERVER['HTTP_HOST']);
define('ADMIN_EMAIL', 'admin@rewardsapp.com');

// Points & Rewards Configuration
define('POINTS_CONVERSION_RATE', 100); // 100 points = $1.00

// Error Reporting
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Timezone
date_default_timezone_set('UTC');

// Create data directory if it doesn't exist and ensure proper permissions
if (!file_exists(__DIR__ . '/../data')) {
    mkdir(__DIR__ . '/../data', 0777, true);
}

// Try to set proper permissions on the data directory for live environments
// Use error suppression operator to avoid warnings if permission denied
if (file_exists(__DIR__ . '/../data')) {
    @chmod(__DIR__ . '/../data', 0777);
}

// If database file exists, try to set write permissions
// Use error suppression operator to avoid warnings if permission denied
if (file_exists(DB_PATH)) {
    @chmod(DB_PATH, 0666);
}

// Load EmailJS settings from database if they exist
function loadEmailJSSettings() {
    try {
        $db = new PDO('sqlite:' . DB_PATH);
        $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        $stmt = $db->query("SELECT setting_key, setting_value FROM admin_settings WHERE setting_key IN ('emailjs_user_id', 'emailjs_service_id', 'emailjs_template_id')");
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            if (!empty($row['setting_value'])) {
                putenv($row['setting_key'] . "=" . $row['setting_value']);
            }
        }
    } catch (PDOException $e) {
        // Silently fail - database may not be initialized yet
        error_log("Error loading EmailJS settings: " . $e->getMessage());
    }
}

// Only load settings if the admin_settings table exists
if (file_exists(DB_PATH)) {
    loadEmailJSSettings();
}
?>
