<?php
// Configuration settings for the application
session_start();

// Database Configuration - SQLite
define('DB_TYPE', 'sqlite');
define('DB_PATH', __DIR__ . '/../data/rewards_app.sqlite');

// OGAds API Configuration
define('OGADS_API_KEY', '31621|84YSBHzNxHsFkLk6aRohyLBWYn7f0HGQpHBHQo8F1ae7067f');
define('OGADS_API_URL', 'https://unlockcontent.net/api/v2');

// Application Settings
define('APP_NAME', 'Rewards App');
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

// Create data directory if it doesn't exist
if (!file_exists(__DIR__ . '/../data')) {
    mkdir(__DIR__ . '/../data', 0777, true);
}
?>
